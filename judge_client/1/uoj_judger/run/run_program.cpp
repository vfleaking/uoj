#include <iostream>
#include <cstdio>
#include <cstdlib>
#include <unistd.h>
#include <asm/unistd.h>
#include <sys/ptrace.h>
#include <sys/wait.h>
#include <sys/resource.h>
#include <sys/user.h>
#include <fcntl.h>
#include <cstring>
#include <string>
#include <vector>
#include <set>
#include <argp.h>
#include "uoj_env.h"
using namespace std;

struct RunResult {
	int result;
	int ust;
	int usm;
	int exit_code;

	RunResult(int _result, int _ust = -1, int _usm = -1, int _exit_code = -1)
			: result(_result), ust(_ust), usm(_usm), exit_code(_exit_code) {
		if (result != RS_AC) {
			ust = -1, usm = -1;
		}
	}
};

int put_result(RunResult res) {
	printf("%d %d %d %d\n", res.result, res.ust, res.usm, res.exit_code);
	if (res.result == RS_JGF) {
		return 1;
	} else {
		return 0;
	}
}

struct RunProgramConfig
{
	int time_limit;
	int memory_limit;
	int output_limit;
	int stack_limit;
	string input_file_name;
	string output_file_name;
	string error_file_name;
	string work_path;
	string type;
	vector<string> extra_readable_files;
	bool allow_proc;
	bool safe_mode;
	bool need_show_trace_details;

	string program_name;
	string program_basename;
	vector<string> argv;
};

char self_path[PATH_MAX + 1] = {};

#include "run_program_conf.h"

argp_option run_program_argp_options[] =
{
	{"tl"                 , 'T', "TIME_LIMIT"  , 0, "Set time limit (in second)"                            ,  1},
	{"ml"                 , 'M', "MEMORY_LIMIT", 0, "Set memory limit (in mb)"                              ,  2},
	{"ol"                 , 'O', "OUTPUT_LIMIT", 0, "Set output limit (in mb)"                              ,  3},
	{"sl"                 , 'S', "STACK_LIMIT" , 0, "Set stack limit (in mb)"                               ,  4},
	{"in"                 , 'i', "IN"          , 0, "Set input file name"                                   ,  5},
	{"out"                , 'o', "OUT"         , 0, "Set output file name"                                  ,  6},
	{"err"                , 'e', "ERR"         , 0, "Set error file name"                                   ,  7},
	{"work-path"          , 'w', "WORK_PATH"   , 0, "Set the work path of the program"                      ,  8},
	{"type"               , 't', "TYPE"        , 0, "Set the program type (for some program such as python)",  9},
	{"add-readable"       , 500, "FILE"        , 0, "Add a readable file"                                   , 10},
	{"unsafe"             , 501, 0             , 0, "Don't check dangerous syscalls"                        , 11},
	{"show-trace-details" , 502, 0             , 0, "Show trace details"                                    , 12},
	{"allow-proc"         , 503, 0             , 0, "Allow fork, exec... etc."                              , 13},
	{"add-readable-raw"   , 504, "FOLDER"      , 0, "Add a readable (don't transform to its real path)"     , 14},
	{0}
};
error_t run_program_argp_parse_opt (int key, char *arg, struct argp_state *state)
{
	RunProgramConfig *config = (RunProgramConfig*)state->input;

	switch (key)
	{
		case 'T':
			config->time_limit = atoi(arg);
			break;
		case 'M':
			config->memory_limit = atoi(arg);
			break;
		case 'O':
			config->output_limit = atoi(arg);
			break;
		case 'S':
			config->stack_limit = atoi(arg);
			break;
		case 'i':
			config->input_file_name = arg;
			break;
		case 'o':
			config->output_file_name = arg;
			break;
		case 'e':
			config->error_file_name = arg;
			break;
		case 'w':
			config->work_path = realpath(arg);
			if (config->work_path.empty()) {
				argp_usage(state);
			}
			break;
		case 't':
			config->type = arg;
			break;
		case 500:
			config->extra_readable_files.push_back(realpath(arg));
			break;
		case 501:
			config->safe_mode = false;
			break;
		case 502:
			config->need_show_trace_details = true;
			break;
		case 503:
			config->allow_proc = true;
			break;
		case 504:
			config->extra_readable_files.push_back(arg);
			break;
		case ARGP_KEY_ARG:
			config->argv.push_back(arg);
			for (int i = state->next; i < state->argc; i++) {
				config->argv.push_back(state->argv[i]);
			}
			state->next = state->argc;
			break;
		case ARGP_KEY_END:
			if (state->arg_num == 0) {
				argp_usage(state);
			}
			break;
		default:
			return ARGP_ERR_UNKNOWN;
	}
	return 0;
}
char run_program_argp_args_doc[] = "program arg1 arg2 ...";
char run_program_argp_doc[] = "run_program: a tool to run program safely";

argp run_program_argp = {
	run_program_argp_options,
	run_program_argp_parse_opt,
	run_program_argp_args_doc,
	run_program_argp_doc
};

RunProgramConfig run_program_config;

void parse_args(int argc, char **argv) {
	run_program_config.time_limit = 1;
	run_program_config.memory_limit = 256;
	run_program_config.output_limit = 64;
	run_program_config.stack_limit = 1024;
	run_program_config.input_file_name = "stdin";
	run_program_config.output_file_name = "stdout";
	run_program_config.error_file_name = "stderr";
	run_program_config.work_path = "";
	run_program_config.type = "default";
	run_program_config.safe_mode = true;
	run_program_config.need_show_trace_details = false;
	run_program_config.allow_proc = false;

	argp_parse(&run_program_argp, argc, argv, ARGP_NO_ARGS | ARGP_IN_ORDER, 0, &run_program_config);

	run_program_config.stack_limit = min(run_program_config.stack_limit, run_program_config.memory_limit);

	if (!run_program_config.work_path.empty()) {
		if (chdir(run_program_config.work_path.c_str()) == -1) {
			exit(put_result(RS_JGF));
		}
	}

	if (run_program_config.type == "java7u76" || run_program_config.type == "java8u31") {
		run_program_config.program_name = run_program_config.argv[0];
	} else {
		run_program_config.program_name = realpath(run_program_config.argv[0]);
	}
	if (run_program_config.work_path.empty()) {
		run_program_config.work_path = dirname(run_program_config.program_name);
		run_program_config.program_basename = basename(run_program_config.program_name);
		run_program_config.argv[0] = "./" + run_program_config.program_basename;

		if (chdir(run_program_config.work_path.c_str()) == -1) {
			exit(put_result(RS_JGF));
		}
	}

	if (run_program_config.type == "python2.7") {
		string pre[4] = {"/usr/bin/python2.7", "-E", "-s", "-B"};
		run_program_config.argv.insert(run_program_config.argv.begin(), pre, pre + 4);
	} else if (run_program_config.type == "python3.4") {
		string pre[3] = {"/usr/bin/python3.4", "-I", "-B"};
		run_program_config.argv.insert(run_program_config.argv.begin(), pre, pre + 3);
	} else if (run_program_config.type == "java7u76") {
		string pre[3] = {abspath(0, string(self_path) + "/../runtime/jdk1.7.0_76/bin/java"), "-Xmx1024m", "-Xss256m"};
		run_program_config.argv.insert(run_program_config.argv.begin(), pre, pre + 3);
	} else if (run_program_config.type == "java8u31") {
		string pre[3] = {abspath(0, string(self_path) + "/../runtime/jdk1.8.0_31/bin/java"), "-Xmx1024m", "-Xss256m"};
		run_program_config.argv.insert(run_program_config.argv.begin(), pre, pre + 3);
	}
}

void set_limit(int r, int rcur, int rmax = -1)  {
	if (rmax == -1)
		rmax = rcur;
	struct rlimit l;
	if (getrlimit(r, &l) == -1) {
		exit(55);
	}
	l.rlim_cur = rcur;
	l.rlim_max = rmax;
	if (setrlimit(r, &l) == -1) {
		exit(55);
	}
}
void run_child() {
	set_limit(RLIMIT_CPU, run_program_config.time_limit, run_program_config.time_limit + 2);
	set_limit(RLIMIT_FSIZE, run_program_config.output_limit << 20);
	set_limit(RLIMIT_STACK, run_program_config.stack_limit << 20);

	if (run_program_config.input_file_name != "stdin") {
		if (freopen(run_program_config.input_file_name.c_str(), "r", stdin) == NULL) {
			exit(11);
		}
	}
	if (run_program_config.output_file_name != "stdout" && run_program_config.output_file_name != "stderr") {
		if (freopen(run_program_config.output_file_name.c_str(), "w", stdout) == NULL) {
			exit(12);
		}
	}
	if (run_program_config.error_file_name != "stderr") {
		if (run_program_config.error_file_name == "stdout") {
			if (dup2(1, 2) == -1) {
				exit(13);
			}
		} else {
			if (freopen(run_program_config.error_file_name.c_str(), "w", stderr) == NULL) {
				exit(14);
			}
		}
		
		if (run_program_config.output_file_name == "stderr") {
			if (dup2(2, 1) == -1) {
				exit(15);
			}
		}
	}

	char *env_path_str = getenv("PATH");
	char *env_lang_str = getenv("LANG");
	char *env_shell_str = getenv("SHELL");
	string env_path = env_path_str ? env_path_str : "";
	string env_lang = env_lang_str ? env_lang_str : "";
	string env_shell = env_shell_str ? env_shell_str : "";

	clearenv();
	setenv("USER", "poor_program", 1);
	setenv("LOGNAME", "poor_program", 1);
	setenv("HOME", run_program_config.work_path.c_str(), 1);
	if (env_lang_str) {
		setenv("LANG", env_lang.c_str(), 1);
	}
	if (env_path_str) {
		setenv("PATH", env_path.c_str(), 1);
	}
	setenv("PWD", run_program_config.work_path.c_str(), 1);
	if (env_shell_str) {
		setenv("SHELL", env_shell.c_str(), 1);
	}

	char **program_c_argv = new char*[run_program_config.argv.size() + 1];
	for (size_t i = 0; i < run_program_config.argv.size(); i++) {
		program_c_argv[i] = new char[run_program_config.argv[i].size() + 1];
		strcpy(program_c_argv[i], run_program_config.argv[i].c_str());
	}
	program_c_argv[run_program_config.argv.size()] = NULL;

	if (ptrace(PTRACE_TRACEME, 0, NULL, NULL) == -1) {
		exit(16);
	}
	if (execv(program_c_argv[0], program_c_argv) == -1) {
		exit(17);
	}
}

const int MaxNRPChildren = 50;
struct rp_child_proc {
	pid_t pid;
	int mode;
};
int n_rp_children;
pid_t rp_timer_pid;
rp_child_proc rp_children[MaxNRPChildren];

int rp_children_pos(pid_t pid) {
	for (int i = 0; i < n_rp_children; i++) {
		if (rp_children[i].pid == pid) {
			return i;
		}
	}
	return -1;
}
int rp_children_add(pid_t pid) {
	if (n_rp_children == MaxNRPChildren) {
		return -1;
	}
	rp_children[n_rp_children].pid = pid;
	rp_children[n_rp_children].mode = -1;
	n_rp_children++;
	return 0;
}
void rp_children_del(pid_t pid) {
	int new_n = 0;
	for (int i = 0; i < n_rp_children; i++) {
		if (rp_children[i].pid != pid) {
			rp_children[new_n++] = rp_children[i];
		}
	}
	n_rp_children = new_n;
}

void stop_child(pid_t pid) {
	kill(pid, SIGKILL);
}
void stop_all() {
	kill(rp_timer_pid, SIGKILL);
	for (int i = 0; i < n_rp_children; i++) {
		kill(rp_children[i].pid, SIGKILL);
	}
}

RunResult trace_children() {
	rp_timer_pid = fork();
	if (rp_timer_pid == -1) {
		stop_all();
		return RunResult(RS_JGF);
	} else if (rp_timer_pid == 0) {
		struct timespec ts;
		ts.tv_sec = run_program_config.time_limit + 2;
		ts.tv_nsec = 0;
		nanosleep(&ts, NULL);
		exit(0);
	}

	if (run_program_config.need_show_trace_details) {
		cerr << "timerpid " << rp_timer_pid << endl;
	}

	pid_t prev_pid = -1;
	while (true) {
		int stat = 0;
		int sig = 0;
		struct rusage ruse;
		
		pid_t pid = wait4(-1, &stat, __WALL, &ruse);
		if (run_program_config.need_show_trace_details) {
			if (prev_pid != pid) {
				cerr << "----------" << pid << "----------" << endl;
			}
			prev_pid = pid;
		}
		if (pid == rp_timer_pid) {
			if (WIFEXITED(stat) || WIFSIGNALED(stat)) {
				stop_all();
				return RunResult(RS_TLE);
			}
			continue;
		}
		
		int p = rp_children_pos(pid);
		if (p == -1) {
			if (run_program_config.need_show_trace_details) {
				fprintf(stderr, "new_proc  %lld\n", (long long int)pid);
			}
			if (rp_children_add(pid) == -1) {
				stop_child(pid);
				stop_all();
				return RunResult(RS_DGS);
			}
			p = n_rp_children - 1;
		}

		int usertim = ruse.ru_utime.tv_sec * 1000 + ruse.ru_utime.tv_usec / 1000;
		int usermem = ruse.ru_maxrss;
		if (usertim > run_program_config.time_limit * 1000) {
			stop_all();
			return RunResult(RS_TLE);
		}
		if (usermem > run_program_config.memory_limit * 1024) {
			stop_all();
			return RunResult(RS_MLE);
		}

		if (WIFEXITED(stat)) {
			if (run_program_config.need_show_trace_details) {
				fprintf(stderr, "exit     : %d\n", WEXITSTATUS(stat));
			}
			if (rp_children[0].mode == -1) {
				stop_all();
				return RunResult(RS_JGF, -1, -1, WEXITSTATUS(stat));
			} else {
				if (pid == rp_children[0].pid) {
					stop_all();
					return RunResult(RS_AC, usertim, usermem, WEXITSTATUS(stat));
				} else {
					rp_children_del(pid);
					continue;
				}
			}
		}

		if (WIFSIGNALED(stat)) {
			if (run_program_config.need_show_trace_details) {
				fprintf(stderr, "sig exit : %d\n", WTERMSIG(stat));
			}
			if (pid == rp_children[0].pid) {
				switch(WTERMSIG(stat)) {
				case SIGXCPU: // nearly impossible
					stop_all();
					return RunResult(RS_TLE);
				case SIGXFSZ:
					stop_all();
					return RunResult(RS_OLE);
				default:
					stop_all();
					return RunResult(RS_RE);
				}
			} else {
				rp_children_del(pid);
				continue;
			}
		}
		
		if (WIFSTOPPED(stat)) {
			sig = WSTOPSIG(stat);
			
			if (rp_children[p].mode == -1) {
				if ((p == 0 && sig == SIGTRAP) || (p != 0 && sig == SIGSTOP)) {
					if (p == 0) {
						int ptrace_opt = PTRACE_O_EXITKILL | PTRACE_O_TRACESYSGOOD;
						if (run_program_config.safe_mode) {
							ptrace_opt |= PTRACE_O_TRACECLONE | PTRACE_O_TRACEFORK | PTRACE_O_TRACEVFORK;
							ptrace_opt |= PTRACE_O_TRACEEXEC;
						}
						if (ptrace(PTRACE_SETOPTIONS, pid, NULL, ptrace_opt) == -1) {
							stop_all();
							return RunResult(RS_JGF);
						}
					}
					sig = 0;
				}
				rp_children[p].mode = 0;
			} else if (sig == (SIGTRAP | 0x80)) {
				if (rp_children[p].mode == 0) {
					if (run_program_config.safe_mode) {
						if (!check_safe_syscall(pid, run_program_config.need_show_trace_details)) {
							stop_all();
							return RunResult(RS_DGS);
						}
					}
					rp_children[p].mode = 1;
				} else {
					if (run_program_config.safe_mode) {
						on_syscall_exit(pid, run_program_config.need_show_trace_details);
					}
					rp_children[p].mode = 0;
				}
				
				sig = 0;
			} else if (sig == SIGTRAP) {
				switch ((stat >> 16) & 0xffff) {
					case PTRACE_EVENT_CLONE:
					case PTRACE_EVENT_FORK:
					case PTRACE_EVENT_VFORK:
						sig = 0;
						break;
					case PTRACE_EVENT_EXEC:
						rp_children[p].mode = 1;
						sig = 0;
						break;
					case 0:
						break;
					default:
						stop_all();
						return RunResult(RS_JGF);
				}
			}

			if (sig != 0) {
				if (run_program_config.need_show_trace_details) {
					fprintf(stderr, "sig      : %d\n", sig);
				}
			}
			
			switch(sig) {
			case SIGXCPU:
				stop_all();
				return RunResult(RS_TLE);
			case SIGXFSZ:
				stop_all();
				return RunResult(RS_OLE);
			}
		}
		
		ptrace(PTRACE_SYSCALL, pid, NULL, sig);
	}
}

RunResult run_parent(pid_t pid) {
	init_conf(run_program_config);
	
	n_rp_children = 0;

	rp_children_add(pid);
	return trace_children();
}
int main(int argc, char **argv) {
	self_path[readlink("/proc/self/exe", self_path, PATH_MAX)] = '\0';
	parse_args(argc, argv);

	pid_t pid = fork();
	if (pid == -1) {
		return put_result(RS_JGF);
	} else if (pid == 0) {
		run_child();
	} else {
		return put_result(run_parent(pid));
	}
	return put_result(RS_JGF);
}
