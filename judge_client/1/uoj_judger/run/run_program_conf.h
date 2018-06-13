#ifdef __x86_64__
typedef unsigned long long int reg_val_t;
#define REG_SYSCALL orig_rax
#define REG_RET rax
#define REG_ARG0 rdi
#define REG_ARG1 rsi
#define REG_ARG2 rdx
#define REG_ARG3 rcx
#else
typedef long int reg_val_t;
#define REG_SYSCALL orig_eax
#define REG_RET eax
#define REG_ARG0 ebx
#define REG_ARG1 ecx
#define REG_ARG2 edx
#define REG_ARG3 esx
#endif

const size_t MaxPathLen = 200;

set<string> writable_file_name_set;
set<string> readable_file_name_set;
set<string> statable_file_name_set;
set<string> soft_ban_file_name_set;
int syscall_max_cnt[1000];
bool syscall_should_soft_ban[1000];

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
string getcwdp(pid_t pid) {
	char s[20];
	char cwd[MaxPathLen + 1];
	if (pid != 0) {
		sprintf(s, "/proc/%lld/cwd", (long long int)pid);
	} else {
		sprintf(s, "/proc/self/cwd");
	}
	int l = readlink(s, cwd, MaxPathLen);
	if (l == -1) {
		return "";
	}
	cwd[l] = '\0';
	return cwd;
}
string abspath(pid_t pid, const string &path) {
	if (path.size() > MaxPathLen) {
		return "";
	}
	if (path.empty()) {
		return path;
	} 
	string s;
	string b;
	size_t st;
	if (path[0] == '/') {
		s = "/";
		st = 1;
	} else {
		s = getcwdp(pid) + "/";
		st = 0;
	}
	for (size_t i = st; i < path.size(); i++) {
		b += path[i];
		if (path[i] == '/') {
			if (b == "../" && !s.empty()) {
				if (s == "./") {
					s = "../";
				} else if (s != "/") {
					size_t p = s.size() - 1;
					while (p > 0 && s[p - 1] != '/') {
						p--;
					}
					if (s.size() - p == 3 && s[p] == '.' && s[p + 1] == '.' && s[p + 2] == '/') {
						s += b;
					} else {
						s.resize(p);
					}
				}
			} else if (b != "./" && b != "/") {
				s += b;
			}
			b.clear();
		}
	}
	if (b == ".." && !s.empty()) {
		if (s == "./") {
			s = "..";
		} else if (s != "/") {
			size_t p = s.size() - 1;
			while (p > 0 && s[p - 1] != '/') {
				p--;
			}
			if (s.size() - p == 3 && s[p] == '.' && s[p + 1] == '.' && s[p + 2] == '/') {
				s += b;
			} else {
				s.resize(p);
			}
		}
	} else if (b != ".") {
		s += b;
	}
	if (s.size() >= 2 && s[s.size() - 1] == '/') {
		s.resize(s.size() - 1);
	}
	return s;
}
string realpath(const string &path) {
	char real[PATH_MAX + 1] = {};
	if (realpath(path.c_str(), real) == NULL) {
		return "";
	}
	return real;
}

inline bool is_in_set_smart(string name, const set<string> &s) {
	if (name.size() > MaxPathLen) {
		return false;
	}
	if (s.count(name)) {
		return true;
	}
	for (size_t i = 0; i + 1 < name.size(); i++) {
		if ((i == 0 || name[i - 1] == '/') && name[i] == '.' && name[i + 1] == '.' && (i + 2 == name.size() || name[i + 2] == '.')) {
			return false;
		}
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
	return is_in_set_smart(name, writable_file_name_set) || is_in_set_smart(realpath(name), readable_file_name_set);
}
inline bool is_readable_file(const string &name) {
	if (is_writable_file(name)) {
		return true;
	}
	if (name == "/") {
		return readable_file_name_set.count("system_root");
	}
	return is_in_set_smart(name, readable_file_name_set) || is_in_set_smart(realpath(name), readable_file_name_set);
}
inline bool is_statable_file(const string &name) {
	if (is_readable_file(name)) {
		return true;
	}
	if (name == "/") {
		return statable_file_name_set.count("system_root");
	}
	return is_in_set_smart(name, statable_file_name_set) || is_in_set_smart(realpath(name), statable_file_name_set);
}
inline bool is_soft_ban_file(const string &name) {
	if (name == "/") {
		return soft_ban_file_name_set.count("system_root");
	}
	return is_in_set_smart(name, soft_ban_file_name_set) || is_in_set_smart(realpath(name), soft_ban_file_name_set);
}

#ifdef __x86_64__
int syscall_max_cnt_list_default[][2] = {
	{__NR_read          , -1},
	{__NR_write         , -1},
	{__NR_readv         , -1},
	{__NR_writev        , -1},
	{__NR_open          , -1},
	{__NR_unlink        , -1},
	{__NR_close         , -1},
	{__NR_readlink      , -1},
	{__NR_openat        , -1},
	{__NR_unlinkat      , -1},
	{__NR_readlinkat    , -1},
	{__NR_stat          , -1},
	{__NR_fstat         , -1},
	{__NR_lstat         , -1},
	{__NR_lseek         , -1},
	{__NR_access        , -1},
	{__NR_dup           , -1},
	{__NR_dup2          , -1},
	{__NR_dup3          , -1},
	{__NR_ioctl         , -1},
	{__NR_fcntl         , -1},

	{__NR_mmap          , -1},
	{__NR_mprotect      , -1},
	{__NR_munmap        , -1},
	{__NR_brk           , -1},
	{__NR_mremap        , -1},
	{__NR_msync         , -1},
	{__NR_mincore       , -1},
	{__NR_madvise       , -1},
	
	{__NR_rt_sigaction  , -1},
	{__NR_rt_sigprocmask, -1},
	{__NR_rt_sigreturn  , -1},
	{__NR_rt_sigpending , -1},
	{__NR_sigaltstack   , -1},

	{__NR_getcwd        , -1},

	{__NR_exit          , -1},
	{__NR_exit_group    , -1},

	{__NR_arch_prctl    , -1},

	{__NR_gettimeofday  , -1},
	{__NR_getrlimit     , -1},
	{__NR_getrusage     , -1},
	{__NR_times         , -1},
	{__NR_time          , -1},
	{__NR_clock_gettime , -1},

	{__NR_restart_syscall, -1},

	{-1                 , -1}
};

int syscall_soft_ban_list_default[] = {
	-1
};

const char *readable_file_name_list_default[] = {
	"/etc/ld.so.nohwcap",
	"/etc/ld.so.preload",
	"/etc/ld.so.cache",
	"/lib/x86_64-linux-gnu/",
	"/usr/lib/x86_64-linux-gnu/",
	"/usr/lib/locale/locale-archive",
	"/proc/self/exe",
	"/etc/timezone",
	"/usr/share/zoneinfo/",
	"/dev/random",
	"/dev/urandom",
	"/proc/meminfo",
	"/etc/localtime",
	NULL
};

#else
#error T_T
#endif

void add_file_permission(const string &file_name, char mode) {
	if (mode == 'w') {
		writable_file_name_set.insert(file_name);
	} else if (mode == 'r') {
		readable_file_name_set.insert(file_name);
	} else if (mode == 's') {
		statable_file_name_set.insert(file_name);
	}
	for (string name = dirname(file_name); !name.empty(); name = dirname(name)) {
		statable_file_name_set.insert(name);
	}
}

void init_conf(const RunProgramConfig &config) {
	for (int i = 0; syscall_max_cnt_list_default[i][0] != -1; i++) {
		syscall_max_cnt[syscall_max_cnt_list_default[i][0]] = syscall_max_cnt_list_default[i][1];
	}
	for (int i = 0; syscall_soft_ban_list_default[i] != -1; i++) {
		syscall_should_soft_ban[syscall_soft_ban_list_default[i]] = true;
	}

	for (int i = 0; readable_file_name_list_default[i]; i++) {
		readable_file_name_set.insert(readable_file_name_list_default[i]);
	}
	statable_file_name_set.insert(config.work_path + "/");

	if (config.type != "java7u76" && config.type != "java8u31") {
		add_file_permission(config.program_name, 'r');
	} else {
		int p = config.program_name.find('.');
		if (p == string::npos) {
			readable_file_name_set.insert(config.work_path + "/");
		} else {
			readable_file_name_set.insert(config.work_path + "/" + config.program_name.substr(0, p) + "/");
		}
	}
	add_file_permission(config.work_path, 'r');

	for (vector<string>::const_iterator it = config.extra_readable_files.begin(); it != config.extra_readable_files.end(); it++) {
		add_file_permission(*it, 'r');
	}
	for (vector<string>::const_iterator it = config.extra_writable_files.begin(); it != config.extra_writable_files.end(); it++) {
		add_file_permission(*it, 'w');
	}

	writable_file_name_set.insert("/dev/null");

	if (config.allow_proc) {
		syscall_max_cnt[__NR_clone          ] = -1;
		syscall_max_cnt[__NR_fork           ] = -1;
		syscall_max_cnt[__NR_vfork          ] = -1;
		syscall_max_cnt[__NR_nanosleep      ] = -1;
		syscall_max_cnt[__NR_execve         ] = -1;
	}

	if (config.type == "python2.7") {
		syscall_max_cnt[__NR_set_tid_address] = 1;
		syscall_max_cnt[__NR_set_robust_list] = 1;
		syscall_max_cnt[__NR_futex          ] = -1;

		syscall_max_cnt[__NR_getdents       ] = -1;
		syscall_max_cnt[__NR_getdents64     ] = -1;

		readable_file_name_set.insert("/usr/bin/python2.7");
		readable_file_name_set.insert("/usr/lib/python2.7/");
		readable_file_name_set.insert("/usr/bin/lib/python2.7/");
		readable_file_name_set.insert("/usr/local/lib/python2.7/");
		readable_file_name_set.insert("/usr/lib/pymodules/python2.7/");
		readable_file_name_set.insert("/usr/bin/Modules/");
		readable_file_name_set.insert("/usr/bin/pybuilddir.txt");

		statable_file_name_set.insert("/usr");
		statable_file_name_set.insert("/usr/bin");
	} else if (config.type == "python3.4") {
		syscall_max_cnt[__NR_set_tid_address] = 1;
		syscall_max_cnt[__NR_set_robust_list] = 1;
		syscall_max_cnt[__NR_futex          ] = -1;

		syscall_max_cnt[__NR_getdents       ] = -1;
		syscall_max_cnt[__NR_getdents64     ] = -1;

		readable_file_name_set.insert("/usr/bin/python3.4");
		readable_file_name_set.insert("/usr/lib/python3.4/");
		readable_file_name_set.insert("/usr/lib/python3/");
		readable_file_name_set.insert("/usr/bin/lib/python3.4/");
		readable_file_name_set.insert("/usr/local/lib/python3.4/");
		readable_file_name_set.insert("/usr/bin/pyvenv.cfg");
		readable_file_name_set.insert("/usr/pyvenv.cfg");
		readable_file_name_set.insert("/usr/bin/Modules/");
		readable_file_name_set.insert("/usr/bin/pybuilddir.txt");
		readable_file_name_set.insert("/usr/lib/dist-python");

		statable_file_name_set.insert("/usr");
		statable_file_name_set.insert("/usr/bin");
		statable_file_name_set.insert("/usr/lib");
	} else if (config.type == "java7u76") {
		syscall_max_cnt[__NR_gettid         ] = -1;
		syscall_max_cnt[__NR_set_tid_address] = 1;
		syscall_max_cnt[__NR_set_robust_list] = 14;
		syscall_max_cnt[__NR_futex          ] = -1;

		syscall_max_cnt[__NR_uname          ] = 1;

		syscall_max_cnt[__NR_clone          ] = 13;

		syscall_max_cnt[__NR_getdents       ] = 4;

		syscall_max_cnt[__NR_clock_getres   ] = 2;

		syscall_max_cnt[__NR_setrlimit      ] = 1;

		syscall_max_cnt[__NR_sched_getaffinity] = -1;
		syscall_max_cnt[__NR_sched_yield    ] = -1;

		syscall_should_soft_ban[__NR_socket   ] = true;
		syscall_should_soft_ban[__NR_connect  ] = true;
		syscall_should_soft_ban[__NR_geteuid  ] = true;
		syscall_should_soft_ban[__NR_getuid   ] = true;

		soft_ban_file_name_set.insert("/etc/nsswitch.conf");
		soft_ban_file_name_set.insert("/etc/passwd");

		add_file_permission(abspath(0, string(self_path) + "/../runtime/jdk1.7.0_76") + "/", 'r');
		readable_file_name_set.insert("/sys/devices/system/cpu/");
		readable_file_name_set.insert("/proc/");
		statable_file_name_set.insert("/usr/java/");
		statable_file_name_set.insert("/tmp/");
	} else if (config.type == "java8u31") {
		syscall_max_cnt[__NR_gettid         ] = -1;
		syscall_max_cnt[__NR_set_tid_address] = 1;
		syscall_max_cnt[__NR_set_robust_list] = 15;
		syscall_max_cnt[__NR_futex          ] = -1;

		syscall_max_cnt[__NR_uname          ] = 1;

		syscall_max_cnt[__NR_clone          ] = 14;

		syscall_max_cnt[__NR_getdents       ] = 4;

		syscall_max_cnt[__NR_clock_getres   ] = 2;

		syscall_max_cnt[__NR_setrlimit      ] = 1;

		syscall_max_cnt[__NR_sched_getaffinity] = -1;
		syscall_max_cnt[__NR_sched_yield    ] = -1;

		syscall_should_soft_ban[__NR_socket   ] = true;
		syscall_should_soft_ban[__NR_connect  ] = true;
		syscall_should_soft_ban[__NR_geteuid  ] = true;
		syscall_should_soft_ban[__NR_getuid   ] = true;

		soft_ban_file_name_set.insert("/etc/nsswitch.conf");
		soft_ban_file_name_set.insert("/etc/passwd");

		add_file_permission(abspath(0, string(self_path) + "/../runtime/jdk1.8.0_31") + "/", 'r');
		readable_file_name_set.insert("/sys/devices/system/cpu/");
		readable_file_name_set.insert("/proc/");
		statable_file_name_set.insert("/usr/java/");
		statable_file_name_set.insert("/tmp/");
	} else if (config.type == "compiler") {
		syscall_max_cnt[__NR_gettid         ] = -1;
		syscall_max_cnt[__NR_set_tid_address] = -1;
		syscall_max_cnt[__NR_set_robust_list] = -1;
		syscall_max_cnt[__NR_futex          ] = -1;

		syscall_max_cnt[__NR_getpid         ] = -1;
		syscall_max_cnt[__NR_vfork          ] = -1;
		syscall_max_cnt[__NR_fork           ] = -1;
		syscall_max_cnt[__NR_clone          ] = -1;
		syscall_max_cnt[__NR_execve         ] = -1;
		syscall_max_cnt[__NR_wait4          ] = -1;

		syscall_max_cnt[__NR_clock_gettime  ] = -1;
		syscall_max_cnt[__NR_clock_getres   ] = -1;

		syscall_max_cnt[__NR_setrlimit      ] = -1;
		syscall_max_cnt[__NR_pipe           ] = -1;

		syscall_max_cnt[__NR_getdents64     ] = -1;
		syscall_max_cnt[__NR_getdents       ] = -1;

		syscall_max_cnt[__NR_umask          ] = -1;
		syscall_max_cnt[__NR_rename         ] = -1;
		syscall_max_cnt[__NR_chmod          ] = -1;
		syscall_max_cnt[__NR_mkdir          ] = -1;

		syscall_max_cnt[__NR_chdir          ] = -1;
		syscall_max_cnt[__NR_fchdir         ] = -1;

		syscall_max_cnt[__NR_ftruncate      ] = -1; // for javac = =

		syscall_max_cnt[__NR_sched_getaffinity] = -1; // for javac = =
		syscall_max_cnt[__NR_sched_yield      ] = -1; // for javac = =

		syscall_max_cnt[__NR_uname          ] = -1; // for javac = =
		syscall_max_cnt[__NR_sysinfo        ] = -1; // for javac = =

		syscall_should_soft_ban[__NR_socket   ] = true; // for javac
		syscall_should_soft_ban[__NR_connect  ] = true; // for javac
		syscall_should_soft_ban[__NR_geteuid  ] = true; // for javac
		syscall_should_soft_ban[__NR_getuid  ] = true; // for javac

		writable_file_name_set.insert("/tmp/");

		readable_file_name_set.insert(config.work_path);
		writable_file_name_set.insert(config.work_path + "/");

		readable_file_name_set.insert(abspath(0, string(self_path) + "/../runtime") + "/");

		readable_file_name_set.insert("system_root");
		readable_file_name_set.insert("/usr/");
		readable_file_name_set.insert("/lib/");
		readable_file_name_set.insert("/lib64/");
		readable_file_name_set.insert("/bin/");
		readable_file_name_set.insert("/sbin/");
		// readable_file_name_set.insert("/proc/meminfo");
		// readable_file_name_set.insert("/proc/self/");

		readable_file_name_set.insert("/sys/devices/system/cpu/");
		readable_file_name_set.insert("/proc/");
		soft_ban_file_name_set.insert("/etc/nsswitch.conf"); // for javac = =
		soft_ban_file_name_set.insert("/etc/passwd"); // for javac = =

		readable_file_name_set.insert("/etc/timezone");
		readable_file_name_set.insert("/etc/fpc-2.6.2.cfg.d/");
		readable_file_name_set.insert("/etc/fpc.cfg");

		statable_file_name_set.insert("/*");
	}
}

string read_string_from_regs(reg_val_t addr, pid_t pid) {
	char res[MaxPathLen + 1], *ptr = res;
	while (ptr != res + MaxPathLen) {
		*(reg_val_t*)ptr = ptrace(PTRACE_PEEKDATA, pid, addr, NULL);
		for (int i = 0; i < sizeof(reg_val_t); i++, ptr++, addr++) {
			if (*ptr == 0) {
				return res;
			}
		}
	}
	res[MaxPathLen] = 0;
	return res;
}
string read_abspath_from_regs(reg_val_t addr, pid_t pid) {
	return abspath(pid, read_string_from_regs(addr, pid));
}

inline void soft_ban_syscall(pid_t pid, user_regs_struct reg) {
	reg.REG_SYSCALL += 1024;
	ptrace(PTRACE_SETREGS, pid, NULL, &reg);
}

inline bool on_dgs_file_detect(pid_t pid, user_regs_struct reg, const string &fn) {
	if (is_soft_ban_file(fn)) {
		soft_ban_syscall(pid, reg);
		return true;
	} else {
		return false;
	}
}

inline bool check_safe_syscall(pid_t pid, bool need_show_trace_details) {
	struct user_regs_struct reg;
	ptrace(PTRACE_GETREGS, pid, NULL, &reg);

	int cur_instruction = ptrace(PTRACE_PEEKTEXT, pid, reg.rip - 2, NULL) & 0xffff;
	if (cur_instruction != 0x050f) {
		if (need_show_trace_details) {
			fprintf(stderr, "informal syscall  %d\n", cur_instruction);
		}
		return false;
	}

	int syscall = (int)reg.REG_SYSCALL;
	if (0 > syscall || syscall >= 1000)  {
		return false;
	}
	if (need_show_trace_details) {
		fprintf(stderr, "syscall  %d\n", (int)syscall);
	}

	if (syscall_should_soft_ban[syscall]) {
		soft_ban_syscall(pid, reg);
	} else if (syscall_max_cnt[syscall]-- == 0) {
		if (need_show_trace_details) {
			fprintf(stderr, "dgs      %d\n", (int)syscall);
		}
		return false;
	}

	if (syscall == __NR_open || syscall == __NR_openat) {
		reg_val_t fn_addr;
		reg_val_t flags;
		if (syscall == __NR_open) {
			fn_addr = reg.REG_ARG0;
			flags = reg.REG_ARG1;
		} else {
			fn_addr = reg.REG_ARG1;
			flags = reg.REG_ARG2;
		}
		string fn = read_abspath_from_regs(fn_addr, pid);
		if (need_show_trace_details) {
			fprintf(stderr, "open  ");

			switch (flags & O_ACCMODE) {
			case O_RDONLY:
				fprintf(stderr, "r ");
				break;
			case O_WRONLY:
				fprintf(stderr, "w ");
				break;
			case O_RDWR:
				fprintf(stderr, "rw");
				break;
			default:
				fprintf(stderr, "??");
				break;
			}
			fprintf(stderr, " %s\n", fn.c_str());
		}

		bool is_read_only = (flags & O_ACCMODE) == O_RDONLY &&
			(flags & O_CREAT) == 0 &&
			(flags & O_EXCL) == 0 &&
			(flags & O_TRUNC) == 0;
		if (is_read_only) {
			if (realpath(fn) != "" && !is_readable_file(fn)) {
				return on_dgs_file_detect(pid, reg, fn);
			}
		} else {
			if (!is_writable_file(fn)) {
				return on_dgs_file_detect(pid, reg, fn);
			}
		}
	} else if (syscall == __NR_readlink || syscall == __NR_readlinkat) {
		reg_val_t fn_addr;
		if (syscall == __NR_readlink) {
			fn_addr = reg.REG_ARG0;
		} else {
			fn_addr = reg.REG_ARG1;
		}
		string fn = read_abspath_from_regs(fn_addr, pid);
		if (need_show_trace_details) {
			fprintf(stderr, "readlink %s\n", fn.c_str());
		}
		if (!is_readable_file(fn)) {
			return on_dgs_file_detect(pid, reg, fn);
		}
	} else if (syscall == __NR_unlink || syscall == __NR_unlinkat) {
		reg_val_t fn_addr;
		if (syscall == __NR_unlink) {
			fn_addr = reg.REG_ARG0;
		} else {
			fn_addr = reg.REG_ARG1;
		}
		string fn = read_abspath_from_regs(fn_addr, pid);
		if (need_show_trace_details) {
			fprintf(stderr, "unlink   %s\n", fn.c_str());
		}
		if (!is_writable_file(fn)) {
			return on_dgs_file_detect(pid, reg, fn);
		}
	} else if (syscall == __NR_access) {
		reg_val_t fn_addr = reg.REG_ARG0;
		string fn = read_abspath_from_regs(fn_addr, pid);
		if (need_show_trace_details) {
			fprintf(stderr, "access   %s\n", fn.c_str());
		}
		if (!is_statable_file(fn)) {
			return on_dgs_file_detect(pid, reg, fn);
		}
	} else if (syscall == __NR_stat || syscall == __NR_lstat) {
		reg_val_t fn_addr = reg.REG_ARG0;
		string fn = read_abspath_from_regs(fn_addr, pid);
		if (need_show_trace_details) {
			fprintf(stderr, "stat     %s\n", fn.c_str());
		}
		if (!is_statable_file(fn)) {
			return on_dgs_file_detect(pid, reg, fn);
		}
	} else if (syscall == __NR_execve) {
		reg_val_t fn_addr = reg.REG_ARG0;
		string fn = read_abspath_from_regs(fn_addr, pid);
		if (need_show_trace_details) {
			fprintf(stderr, "execve   %s\n", fn.c_str());
		}
		if (!is_readable_file(fn)) {
			return on_dgs_file_detect(pid, reg, fn);
		}
	} else if (syscall == __NR_chmod || syscall == __NR_rename) {
		reg_val_t fn_addr = reg.REG_ARG0;
		string fn = read_abspath_from_regs(fn_addr, pid);
		if (need_show_trace_details) {
			fprintf(stderr, "change   %s\n", fn.c_str());
		}
		if (!is_writable_file(fn)) {
			return on_dgs_file_detect(pid, reg, fn);
		}
	}
	return true;
}

inline void on_syscall_exit(pid_t pid, bool need_show_trace_details) {
	struct user_regs_struct reg;
	ptrace(PTRACE_GETREGS, pid, NULL, &reg);
	if (need_show_trace_details) {
		if ((long long int)reg.REG_SYSCALL >= 1024) {
			fprintf(stderr, "ban sys  %lld\n", (long long int)reg.REG_SYSCALL - 1024);
		} else {
			fprintf(stderr, "exitsys  %lld (ret %d)\n", (long long int)reg.REG_SYSCALL, (int)reg.REG_RET);
		}
	}

	if ((long long int)reg.REG_SYSCALL >= 1024) {
		reg.REG_SYSCALL -= 1024;
		reg.REG_RET = -EACCES;
		ptrace(PTRACE_SETREGS, pid, NULL, &reg);
	}
}
