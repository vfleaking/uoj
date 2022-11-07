#include "run_program_sandbox.h"

enum RUN_EVENT_TYPE {
	ET_SKIP,
	ET_EXIT,
	ET_SIGNALED,
	ET_REAL_TLE,
	ET_USER_CPU_TLE,
	ET_MLE,
	ET_OLE,
	ET_SECCOMP_STOP,
	ET_SIGNAL_DELIVERY_STOP,
	ET_RESTART,
};

struct run_event {
	RUN_EVENT_TYPE type;
	int pid = -1;
	rp_child_proc *cp;

	int sig = 0;
	int exitcode = 0;
	int pevent = 0;

	int usertim = 0, usermem = 0;
};

argp_option run_program_argp_options[] = {
	{"tl"                 , 'T', "TIME_LIMIT"  , 0, "Set time limit (in second)"                            ,  1},
	{"rtl"                , 'R', "TIME_LIMIT"  , 0, "Set real time limit (in second)"                       ,  2},
	{"ml"                 , 'M', "MEMORY_LIMIT", 0, "Set memory limit (in mb)"                              ,  3},
	{"ol"                 , 'O', "OUTPUT_LIMIT", 0, "Set output limit (in mb)"                              ,  4},
	{"sl"                 , 'S', "STACK_LIMIT" , 0, "Set stack limit (in mb)"                               ,  5},
	{"in"                 , 'i', "IN"          , 0, "Set input file name"                                   ,  6},
	{"out"                , 'o', "OUT"         , 0, "Set output file name"                                  ,  7},
	{"err"                , 'e', "ERR"         , 0, "Set error file name"                                   ,  8},
	{"work-path"          , 'w', "WORK_PATH"   , 0, "Set the work path of the program"                      ,  9},
	{"type"               , 't', "TYPE"        , 0, "Set the program type (for some program such as python)", 10},
	{"res"                , 'r', "RESULT_FILE" , 0, "Set the file name for outputing the result            ", 10},
	{"add-readable"       , 500, "FILE"        , 0, "Add a readable file"                                   , 11},
	{"add-writable"       , 505, "FILE"        , 0, "Add a writable file"                                   , 11},
	{"unsafe"             , 501, 0             , 0, "Don't check dangerous syscalls"                        , 12},
	{"show-trace-details" , 502, 0             , 0, "Show trace details"                                    , 13},
	{"allow-proc"         , 503, 0             , 0, "Allow fork, exec... etc."                              , 14},
	{"add-readable-raw"   , 504, "FILE"        , 0, "Add a readable (don't transform to its real path)"     , 15},
	{"add-writable-raw"   , 506, "FILE"        , 0, "Add a writable (don't transform to its real path)"     , 15},
	{0}
};
error_t run_program_argp_parse_opt (int key, char *arg, struct argp_state *state) {
	runp::config *config = (runp::config*)state->input;

	switch (key) {
		case 'T':
			config->limits.time = stod(arg);
			break;
		case 'R':
			config->limits.real_time = stod(arg);
			break;
		case 'M':
			config->limits.memory = atoi(arg);
			break;
		case 'O':
			config->limits.output = atoi(arg);
			break;
		case 'S':
			config->limits.stack = atoi(arg);
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
			config->work_path = arg;
			break;
		case 'r':
			config->result_file_name = arg;
			break;
		case 't':
			config->type = arg;
			break;
		case 500:
			config->readable_file_names.push_back(realpath(arg));
			break;
		case 501:
			config->unsafe = true;
			break;
		case 502:
			config->need_show_trace_details = true;
			break;
		case 503:
			config->allow_proc = true;
			break;
		case 504:
			config->readable_file_names.push_back(arg);
			break;
		case 505:
			config->writable_file_names.push_back(realpath_for_write(arg));
			break;
		case 506:
			config->writable_file_names.push_back(arg);
			break;
		case ARGP_KEY_ARG:
			config->program_name = arg;
			for (int i = state->next; i < state->argc; i++) {
				config->rest_args.push_back(state->argv[i]);
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

void parse_args(int argc, char **argv) {
	run_program_config.limits.time = 1;
	run_program_config.limits.real_time = -1;
	run_program_config.limits.memory = 256;
	run_program_config.limits.output = 64;
	run_program_config.limits.stack = 1024;
	run_program_config.input_file_name = "stdin";
	run_program_config.output_file_name = "stdout";
	run_program_config.error_file_name = "stderr";
	run_program_config.work_path = "";
	run_program_config.result_file_name = "stdout";
	run_program_config.type = "default";
	run_program_config.unsafe = false;
	run_program_config.need_show_trace_details = false;
	run_program_config.allow_proc = false;

	argp_parse(&run_program_argp, argc, argv, ARGP_NO_ARGS | ARGP_IN_ORDER, 0, &run_program_config);

	runp::result::result_file_name = run_program_config.result_file_name;

	if (run_program_config.limits.real_time == -1) {
		run_program_config.limits.real_time = run_program_config.limits.time + 2;
	}
	run_program_config.limits.stack = min(run_program_config.limits.stack, run_program_config.limits.memory);

	// NOTE: program_name is the full path of the program, not just the file name (but can start with "./")
	if (run_program_config.work_path.empty()) {
		run_program_config.work_path = realpath(getcwd());
		if (run_program_config.work_path.empty()) {
			// work path does not exist
			runp::result(runp::RS_JGF, "error code: WPDNE1").dump_and_exit();
		}
	} else {
		run_program_config.work_path = realpath(run_program_config.work_path);
		if (run_program_config.work_path.empty() || chdir(run_program_config.work_path.c_str()) == -1) {
			// work path does not exist
			runp::result(runp::RS_JGF, "error code: WPDNE2").dump_and_exit();
		}
	}
	if (realpath(run_program_config.program_name).empty()) {
		// invalid program name
		runp::result(runp::RS_JGF, "error code: INVPGN2").dump_and_exit();
	}
	if (!available_program_type_set.count(run_program_config.type)) {
		// invalid program type
		runp::result(runp::RS_JGF, "error code: INVPGT").dump_and_exit();
	}

	try {
		run_program_config.gen_full_args();
	} catch (exception &e) {
		// fail to generate full args
		runp::result(runp::RS_JGF, "error code: GFULARGS").dump_and_exit();
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

void set_user_cpu_time_limit(double tl) {
	struct itimerval val;
	long tl_sec = (long)tl;
	long tl_usec = (long)((tl - floor(tl)) * 1000 + 100) * 1000;
	if (tl_usec >= 1'000'000l) {
		tl_sec++;
		tl_usec -= 1'000'000l;
	}
	val.it_value = {tl_sec, tl_usec};
	val.it_interval = {0, 100 * 1000};
	setitimer(ITIMER_VIRTUAL, &val, NULL);
}

[[noreturn]] void run_child() {
	setpgid(0, 0);

	set_limit(RLIMIT_FSIZE, run_program_config.limits.output << 20);
	set_limit(RLIMIT_STACK, run_program_config.limits.stack << 20);
	// TODO: use https://man7.org/linux/man-pages/man3/vlimit.3.html to limit virtual memory

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

	char** program_c_argv = new char*[run_program_config.full_args.size() + 1];
	for (size_t i = 0; i < run_program_config.full_args.size(); i++) {
		program_c_argv[i] = run_program_config.full_args[i].data();
	}
	program_c_argv[run_program_config.full_args.size()] = NULL;

	if (ptrace(PTRACE_TRACEME, 0, NULL, NULL) == -1) {
		exit(16);
	}
	kill(getpid(), SIGSTOP);
	if (!run_program_config.unsafe && !set_seccomp_bpf()) {
		exit(99);
	}

	pid_t pid = fork();
	if (pid == 0) {
		set_user_cpu_time_limit(run_program_config.limits.time);
		execv(program_c_argv[0], program_c_argv);
		_exit(17);
	} else if (pid != -1) {
		int status;
		while (wait(&status) > 0);
	}
	exit(17);
}

// limit for the safe mode, an upper limit for the number of calls to fork/vfork/clone
const size_t MAX_TOTAL_RP_CHILDREN = 100;
size_t total_rp_children = 0;

struct timeval start_time;
struct timeval end_time;
pid_t rp_timer_pid;
vector<rp_child_proc> rp_children;
struct rusage *ruse0p = NULL;

bool has_real_TLE() {
	struct timeval elapsed;
	timersub(&end_time, &start_time, &elapsed);
	return elapsed.tv_sec >= run_program_config.limits.real_time;
}

int rp_children_pos(pid_t pid) {
	for (size_t i = 0; i < rp_children.size(); i++) {
		if (rp_children[i].pid == pid) {
			return (int)i;
		}
	}
	return -1;
}
void rp_children_add(pid_t pid) {
    rp_child_proc rpc;
    rpc.pid = pid;
    rpc.flags = CPF_STARTUP | CPF_IGNORE_ONE_SIGSTOP;
    rp_children.push_back(rpc);
}
void rp_children_del(pid_t pid) {
	size_t new_n = 0;
	for (size_t i = 0; i < rp_children.size(); i++) {
		if (rp_children[i].pid != pid) {
			rp_children[new_n++] = rp_children[i];
		}
	}
    rp_children.resize(new_n);
}

string get_usage_summary(struct rusage *rusep) {
	struct timeval elapsed;
	timersub(&end_time, &start_time, &elapsed);

	ostringstream sout;
	struct timeval total_cpu;
	timeradd(&rusep->ru_utime, &rusep->ru_stime, &total_cpu);

	sout << "[statistics]" << endl;
	sout << "user CPU / total CPU / elapsed real time: ";
	sout << rusep->ru_utime.tv_sec * 1000 + rusep->ru_utime.tv_usec / 1000 << "ms / ";
	sout << total_cpu.tv_sec * 1000 + total_cpu.tv_usec / 1000 << "ms / ";
	sout << elapsed.tv_sec * 1000 + elapsed.tv_usec / 1000 << "ms." << endl;
	sout << "max RSS: " << rusep->ru_maxrss << "kb." << endl;
	sout << "total number of threads: " << total_rp_children + 1 << "." << endl;
	sout << "voluntary / total context switches: " << rusep->ru_nvcsw << " / " << rusep->ru_nvcsw + rusep->ru_nivcsw << ".";

	return sout.str();
}

void stop_child(pid_t pid) {
	kill(pid, SIGKILL);
}

void stop_all(runp::result res) {
	struct rusage tmp, ruse, *rusep = ruse0p;

    kill(rp_timer_pid, SIGKILL);
	killpg(rp_children[0].pid, SIGKILL);

	// in case some process changes its pgid
    for (auto &rpc : rp_children) {
		kill(rpc.pid, SIGKILL);
    }

    int stat;
    while (true) {
        pid_t pid = wait4(-1, &stat, __WALL, &tmp);
        // cerr << "stop_all: wait " << pid << endl;
        if (pid < 0) {
            if (errno == EINTR) {
                continue;
            } else if (errno == ECHILD) {
                break;
            } else {
                res.dump_and_exit();
            }
        }

		if (pid != rp_timer_pid && pid != rp_children[0].pid) {
			if (res.type != runp::RS_AC) {
				if (rp_children.size() >= 2 && pid == rp_children[1].pid) {
					ruse = tmp;
					rusep = &ruse;
				}
			} else if (rp_children.size() >= 2 && pid != rp_children[1].pid) {
				res = runp::result(runp::RS_RE, "main thread exited before others");
			}
		}

        // it is possible that a newly created process hasn't been logged into rp_children
        // kill it for safty
        kill(pid, SIGKILL);
    }

	if (rusep) {
		res.extra += "\n";
		res.extra += get_usage_summary(rusep);
	}

	res.dump_and_exit();
}

run_event next_event() {
	static struct rusage ruse;
	static pid_t prev_pid = -1;
	run_event e;

	int stat = 0;
	
	e.pid = wait4(-1, &stat, __WALL, &ruse);
	const int wait_errno = errno;
	gettimeofday(&end_time, NULL);

	ruse0p = NULL;
	if (e.pid < 0) {
		if (wait_errno == EINTR) {
			e.type = ET_SKIP;
			return e;
		}
		stop_all(runp::result(runp::RS_JGF, "error code: WT4FAL")); // wait4 failed
	}

	if (run_program_config.need_show_trace_details) {
		if (prev_pid != e.pid) {
			cerr << "----------" << e.pid << "----------" << endl;
		}
		prev_pid = e.pid;
	}

	if (e.pid == rp_timer_pid) {
		e.type = WIFEXITED(stat) || WIFSIGNALED(stat) ? ET_REAL_TLE : ET_SKIP;
		return e;
	}

	if (has_real_TLE()) {
		e.type = ET_REAL_TLE;
		return e;
	}
	
	int p = rp_children_pos(e.pid);
	if (p == -1) {
		if (run_program_config.need_show_trace_details) {
			fprintf(stderr, "new_proc  %lld\n", (long long int)e.pid);
		}
		rp_children_add(e.pid);
		p = (int)rp_children.size() - 1;
	}

	e.cp = rp_children.data() + p;
	ruse0p = p == 1 ? &ruse : NULL;

	if (p >= 1) {
		e.usertim = ruse.ru_utime.tv_sec * 1000 + ruse.ru_utime.tv_usec / 1000;
		e.usermem = ruse.ru_maxrss;
		if (e.usertim > run_program_config.limits.time * 1000) {
			e.type = ET_USER_CPU_TLE;
			return e;
		}
		if (e.usermem > run_program_config.limits.memory * 1024) {
			e.type = ET_MLE;
			return e;
		}
	}

	if (WIFEXITED(stat)) {
		if (p == 0) {
			stop_all(runp::result(runp::RS_JGF, "error code: ZROEX")); // the 0th child process exited unexpectedly
		}
		e.type = ET_EXIT;
		e.exitcode = WEXITSTATUS(stat);
		return e;
	}

	if (WIFSIGNALED(stat)) {
		if (p == 0) {
			stop_all(runp::result(runp::RS_JGF, "error code: ZROSIG")); // the 0th child process signaled unexpectedly
		}
		e.type = ET_SIGNALED;
		e.sig = WTERMSIG(stat);
		return e;
	}

	if (!WIFSTOPPED(stat)) {
		stop_all(runp::result(runp::RS_JGF, "error code: NSTOP")); // expected WIFSTOPPED, but it is not
	}
	
	e.sig = WSTOPSIG(stat);
	e.pevent = (unsigned)stat >> 16;

    if (run_program_config.need_show_trace_details) {
        fprintf(stderr, "sig      : %s\n", strsignal(e.sig));
    }

	if (e.cp->flags & CPF_STARTUP) {
		int ptrace_opt = PTRACE_O_EXITKILL;
		if (p == 0 || !run_program_config.unsafe) {
			ptrace_opt |= PTRACE_O_TRACECLONE | PTRACE_O_TRACEFORK | PTRACE_O_TRACEVFORK;
		}
		if (!run_program_config.unsafe) {
			ptrace_opt |= PTRACE_O_TRACESECCOMP;
		}
		if (ptrace(PTRACE_SETOPTIONS, e.pid, NULL, ptrace_opt) == -1) {
			stop_all(runp::result(runp::RS_JGF, "error code: PTRCFAL")); // ptrace failed
		}
        e.cp->flags &= ~CPF_STARTUP;
	}
	
	switch (e.sig) {
	case SIGTRAP:
		switch (e.pevent) {
		case 0:
		case PTRACE_EVENT_CLONE:
		case PTRACE_EVENT_FORK:
		case PTRACE_EVENT_VFORK:
			e.sig = 0;
			e.type = ET_RESTART;
			return e;
		case PTRACE_EVENT_SECCOMP:
			e.sig = 0;
			e.type = ET_SECCOMP_STOP;
			return e;
		default:
			stop_all(runp::result(runp::RS_JGF, "error code: PTRCSIG")); // unknown ptrace signal
		}
    case SIGSTOP:
        if (e.cp->flags & CPF_IGNORE_ONE_SIGSTOP) {
            e.sig = 0;
            e.type = ET_RESTART;
            e.cp->flags &= ~CPF_IGNORE_ONE_SIGSTOP;
        } else {
            e.type = ET_SIGNAL_DELIVERY_STOP;
        }
        return e;
	case SIGVTALRM:
		// use rusage as the only standard for user CPU time TLE
		// if the program reaches this line... then something goes wrong (rusage says no TLE, but timer says TLE)
		// just ignore it and wait for another period
		e.sig = 0;
		e.type = ET_RESTART;
		return e;
	case SIGXFSZ:
		e.type = ET_OLE;
		return e;
	default:
		e.type = ET_SIGNAL_DELIVERY_STOP;
		return e;
	}
}

void dispatch_event(run_event&& e) {
	auto restart_op = PTRACE_CONT;

	switch (e.type) {
	case ET_SKIP:
		return;
	case ET_REAL_TLE:
		stop_all(runp::result(runp::RS_TLE, "elapsed real time limit exceeded: >" + to_string(run_program_config.limits.real_time) + "s"));
	case ET_USER_CPU_TLE:
		stop_all(runp::result(runp::RS_TLE, "user CPU time limit exceeded: >" + to_string(run_program_config.limits.time) + "s"));
	case ET_MLE:
		stop_all(runp::result(runp::RS_MLE, "max RSS >" + to_string(run_program_config.limits.memory) + "MB"));
	case ET_OLE:
		stop_all(runp::result(runp::RS_OLE, "output limit exceeded: >" + to_string(run_program_config.limits.output) + "MB"));
	case ET_EXIT:
		if (run_program_config.need_show_trace_details) {
			fprintf(stderr, "exit     : %d\n", e.exitcode);
		}
		if (rp_children[0].flags & CPF_STARTUP) {
			stop_all(runp::result(runp::RS_JGF, "error code: CPCMDER1")); // rp_children mode error
		} else if (rp_children.size() < 2 || (rp_children[1].flags & CPF_STARTUP)) {
			stop_all(runp::result(runp::RS_JGF, "error code: CPCMDER2")); // rp_children mode error
		} else {
			if (e.cp == rp_children.data() + 1) {
                stop_all(runp::result(runp::RS_AC, "exit with code " + to_string(e.exitcode), e.usertim, e.usermem, e.exitcode));
			} else {
				rp_children_del(e.pid);
			}
		}
		return;

	case ET_SIGNALED:
		if (run_program_config.need_show_trace_details) {
			fprintf(stderr, "sig exit : %s\n", strsignal(e.sig));
		}
		if (e.cp == rp_children.data() + 1) {
			stop_all(runp::result(runp::RS_RE, string("process terminated by signal: ") + strsignal(e.sig)));
		} else {
			rp_children_del(e.pid);
		}
		return;

	case ET_SECCOMP_STOP:
		if (e.cp != rp_children.data() + 0 && !run_program_config.unsafe) {
			if (!e.cp->check_safe_syscall()) {
				if (e.cp->suspicious) {
					stop_all(runp::result(runp::RS_DGS, e.cp->error));
				} else {
					stop_all(runp::result(runp::RS_RE, e.cp->error));
				}
			}
            if (e.cp->try_to_create_new_process) {
                total_rp_children++;
                if (total_rp_children > MAX_TOTAL_RP_CHILDREN) {
                    stop_all(runp::result(runp::RS_DGS, "the limit on the amount of child processes is exceeded"));
                }
            }
		}
		break;

	case ET_SIGNAL_DELIVERY_STOP:
		break;

	case ET_RESTART:
		break;
	}

	if (ptrace(restart_op, e.pid, NULL, e.sig) < 0) {
		if (errno != ESRCH) {
			stop_all(runp::result(runp::RS_JGF, "error code: PTRESFAL")); // ptrace restart failed
		}
	}
}

[[noreturn]] void trace_children() {
	rp_timer_pid = fork();
	if (rp_timer_pid == -1) {
		runp::result(runp::RS_JGF, "error code: FKFAL2").dump_and_exit(); // fork failed
	} else if (rp_timer_pid == 0) {
		struct timespec ts;
		ts.tv_sec = run_program_config.limits.real_time;
		ts.tv_nsec = 100 * 1000000;
		nanosleep(&ts, NULL);
		exit(0);
	}

	if (run_program_config.need_show_trace_details) {
		cerr << "timerpid " << rp_timer_pid << endl;
	}

	while (true) {
		dispatch_event(next_event());
	}
}

int main(int argc, char **argv) {
	try {
		fs::path self_path = fs::read_symlink("/proc/self/exe");
		runp::run_path = self_path.parent_path();
	} catch (exception &e) {
		runp::result(runp::RS_JGF, "error code: PTHFAL2").dump_and_exit(); // path failed
	}

	parse_args(argc, argv);
	init_conf();

	gettimeofday(&start_time, NULL);
	pid_t pid = fork();
	if (pid == -1) {
		runp::result(runp::RS_JGF, "error code: FKFAL2").dump_and_exit(); // fork failed
	} else if (pid == 0) {
		run_child();
	} else {
		rp_children_add(pid);
		trace_children();
	}
}
