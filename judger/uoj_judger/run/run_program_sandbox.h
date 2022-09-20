#include <iostream>
#include <sstream>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <string>
#include <fstream>
#include <vector>
#include <set>
#include <algorithm>
#include <unistd.h>
#include <sys/ptrace.h>
#include <sys/wait.h>
#include <sys/stat.h>
#include <sys/resource.h>
#include <sys/user.h>
#include <sys/time.h>
#include <sys/prctl.h>
#include <fcntl.h>
#include <argp.h>
#include <seccomp.h>
#include "uoj_run.h"

enum EX_CHECK_TYPE : unsigned {
	ECT_NONE              = 0,
	ECT_CNT               = 1,
	ECT_FILE_OP           = 1 << 1,                         // it is a file operation
	ECT_END_AT            = 1 << 2,                         // this file operation ends with "at" (e.g., openat)
	ECT_FILEAT_OP         = ECT_FILE_OP | ECT_END_AT,       // it is a file operation ended with "at"
	ECT_FILE_W            = 1 << 3,                         // intend to write
	ECT_FILE_R            = 1 << 4,                         // intend to read
	ECT_FILE_S            = 1 << 5,                         // intend to stat
	ECT_CHECK_OPEN_FLAGS  = 1 << 6,                         // check flags to determine whether it is to read/write (for open and openat)
	ECT_FILE2_W           = 1 << 7,                         // intend to write (2nd file)
	ECT_FILE2_R           = 1 << 8,                         // intend to read  (2nd file)
	ECT_FILE2_S           = 1 << 9,                         // intend to stat  (2nd file)
	ECT_CLONE_THREAD      = 1 << 10,                        // for clone(). Check that clone is making a non-suspicious thread
	ECT_KILL_SIG0_ALLOWED = 1 << 11,                        // forbid kill but killing with sig0 is allowed
};

struct syscall_info {
    EX_CHECK_TYPE extra_check;
    int max_cnt;
    bool should_soft_ban = false;
    bool is_kill = false;

    syscall_info()
		: extra_check(ECT_CNT), max_cnt(0) {}
    syscall_info(unsigned extra_check, int max_cnt)
        : extra_check((EX_CHECK_TYPE)extra_check), max_cnt(max_cnt) {}
    
    static syscall_info unlimited() {
        return syscall_info(ECT_NONE, -1);
    }

	static syscall_info count_based(int max_cnt) {
		return syscall_info(ECT_CNT, max_cnt);
	}

    static syscall_info with_extra_check(unsigned extra_check, int max_cnt = -1) {
		if (max_cnt != -1) {
			extra_check |= ECT_CNT;
		}
		return syscall_info(extra_check, max_cnt);
    }

    static syscall_info kill_type_syscall(unsigned extra_check = ECT_CNT, int max_cnt = 0) {
		if (max_cnt != -1) {
			extra_check |= ECT_CNT;
		}
        syscall_info res(extra_check, max_cnt);
        res.is_kill = true;
        return res;
    }

    static syscall_info soft_ban() {
        syscall_info res(ECT_CNT, 0);
        res.should_soft_ban = true;
        return res;
    }
};

#include "run_program_conf.h"

namespace fs = std::filesystem;
using namespace std;

typedef unsigned long long int reg_val_t;
#define REG_SYSCALL orig_rax
#define REG_RET rax
#define REG_ARG0 rdi
#define REG_ARG1 rsi
#define REG_ARG2 rdx
#define REG_ARG3 rcx

enum CHILD_PROC_FLAG : unsigned {
	CPF_STARTUP = 1u << 0,
	CPF_IGNORE_ONE_SIGSTOP = 1u << 2
};

struct rp_child_proc {
	pid_t pid;

	unsigned flags;

	struct user_regs_struct reg = {};
	int syscall = -1;
	string error;
	bool suspicious = false;
    bool try_to_create_new_process = false;

	void set_error_for_suspicious(const string &error);
	void set_error_for_kill();
	void soft_ban_syscall(int set_no);
	bool check_safe_syscall();
	bool check_file_permission(const string &op, const string &fn, char mode);
};

const size_t MAX_PATH_LEN = 512;
const uint64_t MAX_FD_ID = 1 << 20;

runp::config run_program_config;

set<string> writable_file_name_set;
set<string> readable_file_name_set;
set<string> statable_file_name_set;
set<string> soft_ban_file_name_set;

syscall_info syscall_info_set[N_SYSCALL];

pid_t get_tgid_from_pid(pid_t pid) {
	ifstream fin("/proc/" + to_string(pid) + "/status");
	string key;
	while (fin >> key) {
		if (key == "Tgid:") {
			pid_t tgid;
			if (fin >> tgid) {
				return tgid;
			} else {
				return -1;
			}
		}
	}
	return -1;
}

string basename(const string &path) {
	size_t p = path.rfind('/');
	if (p == string::npos) {
		return path;
	} else {
		return path.substr(p + 1);
	}
}
string dirname(const string &path) {
	size_t p = path.rfind('/');
	if (p == string::npos) {
		return "";
	} else {
		return path.substr(0, p);
	}
}
string realpath(const string &path) {
	if (path.empty() || path.size() > MAX_PATH_LEN) {
		return "";
	}
	char real[PATH_MAX + 1] = {};
	if (realpath(path.c_str(), real) == NULL) {
		return "";
	}
	return real;
}
string realpath_for_write(const string &path) {
	string real = realpath(path);
	if (!real.empty()) {
		return real;
	}

	string b = basename(path);
	real = realpath(dirname(path));
	if (real.empty() || b == "." || b == "..") {
		return "";
	}
	real += "/" + b;
	return real;
}
string readlink(const string &path) {
	static char buf[MAX_PATH_LEN + 1];
	ssize_t n = readlink(path.c_str(), buf, MAX_PATH_LEN + 1);
	if (n > (ssize_t)MAX_PATH_LEN) {
		return "";
	} else {
		buf[n] = '\0';
		return buf;
	}
}
string getcwd() {
	char cwd[MAX_PATH_LEN + 1];
	if (getcwd(cwd, MAX_PATH_LEN) == NULL) {
		return "";
	} else {
		return cwd;
	}
}
string getcwdp(pid_t pid) {
	return realpath("/proc/" + (pid == 0 ? "self" : to_string(pid)) + "/cwd");
}
string abspath(const string &path, pid_t pid, int fd = AT_FDCWD) {
	static int depth = 0;
	if (depth == 10) {
		return "";
	}
	if (path.empty() || path.size() > MAX_PATH_LEN) {
		return "";
	}

	vector<string> lv;
	for (string cur = path; !cur.empty(); cur = dirname(cur)) {
		lv.push_back(basename(cur));
	}
	reverse(lv.begin(), lv.end());

	string pos;
	if (path[0] == '/') {
		pos = "/";
	} else if (fd == AT_FDCWD) {
		pos = getcwdp(pid);
	} else {
		depth++;
		pos = abspath("/proc/self/fd/" + to_string(fd), pid);
		depth--;
	}
	if (pos.empty()) {
		return "";
	}

	struct stat stat_buf;
	bool reachable = true;
	for (auto &v : lv) {
		if (reachable) {
			if (lstat(pos.c_str(), &stat_buf) < 0 || !S_ISDIR(stat_buf.st_mode)) {
				reachable = false;
			}
		}

		if (reachable) {
			if (v == ".") {
				continue;
			} else if (v == "..") {
				pos = dirname(pos);
				if (pos.empty()) {
					pos = "/";
				}
				continue;
			}
		}

		if (v.empty()) {
			continue;
		}
		if (pos.back() != '/') {
			pos += '/';
		}
		pos += v;
		if (pos.size() > MAX_PATH_LEN) {
			return "";
		}

		if (reachable) {
			string realpos;
			if (pos == "/proc/self") {
				realpos = "/proc/" + to_string(get_tgid_from_pid(pid));
			} else if (pos == "/proc/thread-self") {
				realpos = "/proc/" + to_string(get_tgid_from_pid(pid)) + "/" + to_string(pid);
			} else {
				if (lstat(pos.c_str(), &stat_buf) < 0) {
					reachable = false;
					continue;
				}
				if (!S_ISLNK(stat_buf.st_mode)) {
					continue;
				}
				realpos = readlink(pos);
				if (!realpos.empty()) {
					if (realpos[0] != '/') {
						realpos = dirname(pos) + "/" + realpos;
					}
				}
			}

			depth++;
			realpos = abspath(realpos, pid);
			depth--;
			if (!realpos.empty()) {
				pos = realpos;
			}
		}
	}

	return pos;
}

inline bool is_in_set_smart(string name, const set<string> &s) {
	if (name.size() > MAX_PATH_LEN) {
		return false;
	}
	if (s.count(name)) {
		return true;
	}
	int level;
	for (level = 0; !name.empty(); name = dirname(name), level++) {
		if (level == 1 && s.count(name + "/*")) {
			return true;
		}
		if (s.count(name + "/")) {
			return true;
		}
	}
	if (level == 1 && s.count("/*")) {
		return true;
	}
	if (s.count("/")) {
		return true;
	}
	return false;
}

inline bool is_writable_file(string name) {
	if (name == "/") {
		return writable_file_name_set.count("system_root");
	}
	return is_in_set_smart(name, writable_file_name_set);
}
inline bool is_readable_file(const string &name) {
	if (name == "/") {
		return readable_file_name_set.count("system_root");
	}
	return is_in_set_smart(name, readable_file_name_set);
}
inline bool is_statable_file(const string &name) {
	if (name == "/") {
		return statable_file_name_set.count("system_root");
	}
	return is_in_set_smart(name, statable_file_name_set);
}
inline bool is_soft_ban_file(const string &name) {
	if (name == "/") {
		return soft_ban_file_name_set.count("system_root");
	}
	return is_in_set_smart(name, soft_ban_file_name_set);
}

void add_file_permission(const string &file_name, char mode) {
	if (file_name.empty()) {
		return;
	}
	if (mode == 'w') {
		writable_file_name_set.insert(file_name);
	} else if (mode == 'r') {
		readable_file_name_set.insert(file_name);
	} else if (mode == 's') {
		statable_file_name_set.insert(file_name);
	}
	if (file_name == "system_root") {
		return;
	}
	for (string name = dirname(file_name); !name.empty(); name = dirname(name)) {
		statable_file_name_set.insert(name);
	}
}

void init_conf() {    
	const runp::config &config = run_program_config;
	add_file_permission(config.work_path, 'r');
	add_file_permission(config.work_path + "/", 's');
	if (folder_program_type_set.count(config.type)) {
		add_file_permission(realpath(config.program_name) + "/", 'r');
	} else {
		add_file_permission(realpath(config.program_name), 'r');
	}

	vector<string> loads;
	loads.push_back("default");
	if (config.allow_proc) {
		loads.push_back("allow_proc");
	}
	if (config.type != "default") {
		loads.push_back(config.type);
	}
	
	for (string type : loads) {
		if (allowed_syscall_list.count(type)) {
			for (const auto &kv : allowed_syscall_list[type]) {
				syscall_info_set[kv.first] = kv.second;
			}
		}
		if (soft_ban_file_name_list.count(type)) {
			for (const auto &name : soft_ban_file_name_list[type]) {
				soft_ban_file_name_set.insert(name);
			}
		}
		if (statable_file_name_list.count(type)) {
			for (const auto &name : statable_file_name_list[type]) {
				add_file_permission(name, 's');
			}
		}
		if (readable_file_name_list.count(type)) {
			for (const auto &name : readable_file_name_list[type]) {
				add_file_permission(name, 'r');
			}
		}
		if (writable_file_name_list.count(type)) {
			for (const auto &name : writable_file_name_list[type]) {
				add_file_permission(name, 'w');
			}
		}
	}

    for (const auto &name : config.readable_file_names) {
		add_file_permission(name, 'r');
    }
	for (const auto &name : config.writable_file_names) {
		add_file_permission(name, 'w');
	}

	if (config.type == "java8") {
		add_file_permission(runp::run_path.string() + "/runtime/" + UOJ_JDK8 + "/", 'r');
	} else if (config.type == "python2.7" || config.type == "python3") {
		soft_ban_file_name_set.insert(dirname(realpath(config.program_name)) + "/__pycode__/");
	} else if (config.type == "compiler") {
		add_file_permission(config.work_path + "/", 'w');
		add_file_permission(runp::run_path.string() + "/runtime/", 'r');
	}

	readable_file_name_set.insert(writable_file_name_set.begin(), writable_file_name_set.end());
	statable_file_name_set.insert(readable_file_name_set.begin(), readable_file_name_set.end());
}

string read_string_from_regs(reg_val_t addr, pid_t pid) {
	int max_len = MAX_PATH_LEN + sizeof(reg_val_t);
	char res[max_len + 1], *ptr = res;
	while (ptr != res + max_len) {
		*(reg_val_t*)ptr = ptrace(PTRACE_PEEKDATA, pid, addr, NULL);
		for (size_t i = 0; i < sizeof(reg_val_t); i++, ptr++, addr++) {
			if (*ptr == 0) {
				return res;
			}
		}
	}
	res[max_len] = 0;
	return res;
}
string read_abspath_from_regs(reg_val_t addr, pid_t pid) {
	string p = read_string_from_regs(addr, pid);
	string a = abspath(p, pid);
	if (run_program_config.need_show_trace_details) {
		fprintf(stderr, "path     : %s -> %s\n", p.c_str(), a.c_str());
	}
	return a;
}
string read_abspath_from_regs(reg_val_t fd, reg_val_t addr, pid_t pid) {
	if (fd > MAX_FD_ID && (int)fd != AT_FDCWD) {
		return "";
	}
	string p = read_string_from_regs(addr, pid);
	string a = abspath(p, pid, (int)fd);
	if (run_program_config.need_show_trace_details) {
		fprintf(stderr, "path     : %s -> %s\n", p.c_str(), a.c_str());
	}
	return a;
}

bool set_seccomp_bpf() {
    scmp_filter_ctx ctx = seccomp_init(SCMP_ACT_TRACE(0));
    if (!ctx) {
        return false;
    }

    try {
		for (int no : supported_soft_ban_errno_list) {
			if (seccomp_rule_add(ctx, SCMP_ACT_ERRNO(no), SYSCALL_SOFT_BAN_MASK | no, 0) < 0) {
				throw system_error();
			}
		}

		for (int i = 0; i < N_SYSCALL; i++) {
			if (syscall_info_set[i].extra_check == ECT_NONE) {
				if (syscall_info_set[i].should_soft_ban) {
					if (seccomp_rule_add(ctx, SCMP_ACT_ERRNO(EPERM), i, 0) < 0) {
						throw system_error();
					}
				} else {
					if (seccomp_rule_add(ctx, SCMP_ACT_ALLOW, i, 0) < 0) {
						throw system_error();
					}
				}
			}
		}
		seccomp_load(ctx);
    } catch (system_error &e) {
		seccomp_release(ctx);
        return false;
    }
	seccomp_release(ctx);
    return true;
}

void rp_child_proc::set_error_for_suspicious(const string &error) {
	this->suspicious = true;
	this->error = "suspicious system call invoked: " + error;
}

void rp_child_proc::set_error_for_kill() {
	this->suspicious = false;
	reg_val_t sig = this->syscall == __NR_tgkill ? this->reg.REG_ARG2 : this->reg.REG_ARG1;
	this->error = "signal sent via " + syscall_name[this->syscall] + ": ";
	if (sig != (unsigned)sig) {
		this->error += "Unknown signal " + to_string(sig);
	} else {
		this->error += strsignal((int)sig);
	}
}

void rp_child_proc::soft_ban_syscall(int set_no = EPERM) {
	this->reg.REG_SYSCALL = SYSCALL_SOFT_BAN_MASK | set_no;
	ptrace(PTRACE_SETREGS, pid, NULL, &this->reg);
}

bool rp_child_proc::check_file_permission(const string &op, const string &fn, char mode) {
	string real_fn;
	if (!fn.empty()) {
		real_fn = mode == 'w' ? realpath_for_write(fn) : realpath(fn);
	}
	if (real_fn.empty()) {
		// file not found
		// ban this syscall softly
		this->soft_ban_syscall(ENOENT);
		return true;
	}

	string path_proc_self = "/proc/" + to_string(get_tgid_from_pid(this->pid));
	if (real_fn.compare(0, path_proc_self.size() + 1, path_proc_self + "/") == 0) {
		real_fn = "/proc/self" + real_fn.substr(path_proc_self.size());
	} else if (real_fn == path_proc_self) {
		real_fn = "/proc/self";
	}

	bool ok;
	switch (mode) {
	case 'w':
		ok = is_writable_file(real_fn);
		break;
	case 'r':
		ok = is_readable_file(real_fn);
		break;
	case 's':
		ok = is_statable_file(real_fn);
		break;
	default:
		ok = false;
		break;
	}

	if (ok) {
		return true;
	}

	if (run_program_config.need_show_trace_details) {
		fprintf(stderr, "check file permission %s : %s\n", op.c_str(), real_fn.c_str());
		fprintf(stderr, "[readable]\n");
		for (auto s: readable_file_name_set) {
			cerr << s << endl;
		}
		fprintf(stderr, "[writable]\n");
		for (auto s: writable_file_name_set) {
			cerr << s << endl;
		}
	}

	if (is_soft_ban_file(real_fn)) {
		this->soft_ban_syscall(EACCES);
		return true;
	} else {
		this->set_error_for_suspicious("intended to access a file without permission: " + op);
		return false;
	}
}

bool rp_child_proc::check_safe_syscall() {
	ptrace(PTRACE_GETREGS, pid, NULL, &reg);

	int cur_instruction = ptrace(PTRACE_PEEKTEXT, pid, reg.rip - 2, NULL) & 0xffff;
	if (cur_instruction != 0x050f) {
		if (run_program_config.need_show_trace_details) {
			fprintf(stderr, "informal syscall  %d\n", cur_instruction);
		}
		this->set_error_for_suspicious("incorrect opcode " + to_string(cur_instruction));
		return false;
	}

	if (0 > (long long int)reg.REG_SYSCALL || (long long int)reg.REG_SYSCALL >= N_SYSCALL)  {
		this->set_error_for_suspicious(to_string(reg.REG_SYSCALL));
		return false;
	}
	syscall = (int)reg.REG_SYSCALL;
	if (run_program_config.need_show_trace_details) {
		fprintf(stderr, "[syscall %s]\n", syscall_name[syscall].c_str());
	}
    this->try_to_create_new_process = syscall == __NR_fork || syscall == __NR_clone || syscall == __NR_vfork;

	auto &cursc = syscall_info_set[syscall];

	if (cursc.extra_check & ECT_CNT) {
		if (cursc.max_cnt == 0) {
			if (cursc.should_soft_ban) {
				this->soft_ban_syscall();
				return true;
			} else {
				if (cursc.is_kill) {
					this->set_error_for_kill();
				} else {
					this->set_error_for_suspicious(syscall_name[syscall]);
				}
				return false;
			}
		}
		cursc.max_cnt--; 
	}

	if (cursc.extra_check & ECT_KILL_SIG0_ALLOWED) {
		reg_val_t sig = this->syscall == __NR_tgkill ? this->reg.REG_ARG2 : this->reg.REG_ARG1;
		if (sig != 0) {
			this->set_error_for_kill();
			return false;
		}
	}

	if (cursc.extra_check & ECT_FILE_OP) {
		string fn;
		if (cursc.extra_check & ECT_END_AT) {
			fn = read_abspath_from_regs(reg.REG_ARG0, reg.REG_ARG1, pid);
		} else {
			fn = read_abspath_from_regs(reg.REG_ARG0, pid);
		}

		string textop = syscall_name[syscall];
		char mode = 'w';
		if (cursc.extra_check & ECT_CHECK_OPEN_FLAGS) {
			reg_val_t flags = cursc.extra_check & ECT_END_AT ? reg.REG_ARG2 : reg.REG_ARG1;
			switch (flags & O_ACCMODE) {
			case O_RDONLY:
				if ((flags & O_CREAT) == 0 && (flags & O_EXCL) == 0 && (flags & O_TRUNC) == 0) {
					textop += " (for read)";
					mode = 'r';
				} else {
					textop += " (for read & write)";
				}
				break;
			case O_WRONLY:
				textop += " (for write)";
				break;
			case O_RDWR:
				textop += " (for read & write)";
				break;
			default:
				textop += " (with invalid flags)";
				break;
			}
		} else if (cursc.extra_check & ECT_FILE_S) {
			mode = 's';
		} else if (cursc.extra_check & ECT_FILE_R) {
			mode = 'r';
		} else if (cursc.extra_check & ECT_FILE_W) {
			mode = 'w';
		} // else, error!

		if (run_program_config.need_show_trace_details) {
			fprintf(stderr, "%-8s : %s\n", syscall_name[syscall].c_str(), fn.c_str());
		}
		if (!check_file_permission(textop, fn, mode)) {
			return false;
		}

		if (cursc.extra_check & ECT_FILE2_S) {
			mode = 's';
		} else if (cursc.extra_check & ECT_FILE2_R) {
			mode = 'r';
		} else if (cursc.extra_check & ECT_FILE2_W) {
			mode = 'w';
		} else {
			mode = '?';
		}
		if (mode != '?') {
			if (cursc.extra_check & ECT_END_AT) {
				fn = read_abspath_from_regs(reg.REG_ARG2, reg.REG_ARG3, pid);
			} else {
				fn = read_abspath_from_regs(reg.REG_ARG1, pid);
			}
			if (run_program_config.need_show_trace_details) {
				fprintf(stderr, "%-8s : %s\n", syscall_name[syscall].c_str(), fn.c_str());
			}
			if (!check_file_permission(textop, fn, mode)) {
				return false;
			}
		}
	}

	if (cursc.extra_check & ECT_CLONE_THREAD) {
		reg_val_t flags = reg.REG_ARG0;
		if (!(flags & CLONE_THREAD)) {
			this->set_error_for_suspicious("intended to create a new process");
			return false;
		}
		auto standard_flags = CLONE_VM | CLONE_FS | CLONE_FILES | CLONE_SIGHAND;
		standard_flags |= CLONE_SYSVSEM | CLONE_SETTLS |CLONE_PARENT_SETTID | CLONE_CHILD_CLEARTID;
		if (!(flags & standard_flags)) {
			this->set_error_for_suspicious("intended to create a non-standard thread");
			return false;
		}
	}

	return true;
}
