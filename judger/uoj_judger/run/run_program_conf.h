#include <string>
#include <vector>
#include <map>

#ifndef __x86_64__
#error only x86-64 is supported!
#endif

/*
 * a mask that tells seccomp that it should SCMP_ACT_ERRNO(no)
 * when syscall #(mask | no) is called
 * used to implement SCMP_ACT_ERRNO(no) using ptrace:
 *     set the syscall number to mask | no;
 *     PTRACE_CONT
 *     seccomp performs SCMP_ACT_ERRNO(no)
 */
const int SYSCALL_SOFT_BAN_MASK = 996 << 18;

std::vector<int> supported_soft_ban_errno_list = {
	ENOENT,     // No such file or directory
	EPERM,      // Operation not permitted
	EACCES,     // Permission denied
};

std::set<std::string> available_program_type_set = {
	"default", "python2.7", "python3", "java8", "java11", "java17", "compiler"
};

/*
 * folder program: the program to run is a folder, not a single regular file
 */
std::set<std::string> folder_program_type_set = {
	"java8", "java11", "java17"
};

std::map<std::string, std::vector<std::pair<int, syscall_info>>> allowed_syscall_list = {
	{"default", {
		{__NR_read           , syscall_info::unlimited()},
		{__NR_pread64        , syscall_info::unlimited()},
		{__NR_write          , syscall_info::unlimited()},
		{__NR_pwrite64       , syscall_info::unlimited()},
		{__NR_readv          , syscall_info::unlimited()},
		{__NR_writev         , syscall_info::unlimited()},
		{__NR_preadv         , syscall_info::unlimited()},
		{__NR_pwritev        , syscall_info::unlimited()},
		{__NR_sendfile       , syscall_info::unlimited()},

		{__NR_close          , syscall_info::unlimited()},
		{__NR_fstat          , syscall_info::unlimited()},
		{__NR_fstatfs        , syscall_info::unlimited()},
		{__NR_lseek          , syscall_info::unlimited()},
		{__NR_dup            , syscall_info::unlimited()},
		{__NR_dup2           , syscall_info::unlimited()},
		{__NR_dup3           , syscall_info::unlimited()},
		{__NR_ioctl          , syscall_info::unlimited()},
		{__NR_fcntl          , syscall_info::unlimited()},

		{__NR_gettid         , syscall_info::unlimited()},
		{__NR_getpid         , syscall_info::unlimited()},

		{__NR_mmap           , syscall_info::unlimited()},
		{__NR_mprotect       , syscall_info::unlimited()},
		{__NR_munmap         , syscall_info::unlimited()},
		{__NR_brk            , syscall_info::unlimited()},
		{__NR_mremap         , syscall_info::unlimited()},
		{__NR_msync          , syscall_info::unlimited()},
		{__NR_mincore        , syscall_info::unlimited()},
		{__NR_madvise        , syscall_info::unlimited()},

		{__NR_rt_sigaction   , syscall_info::unlimited()},
		{__NR_rt_sigprocmask , syscall_info::unlimited()},
		{__NR_rt_sigreturn   , syscall_info::unlimited()},
		{__NR_rt_sigpending  , syscall_info::unlimited()},
		{__NR_sigaltstack    , syscall_info::unlimited()},

		{__NR_getcwd         , syscall_info::unlimited()},
		{__NR_uname          , syscall_info::unlimited()},

		{__NR_exit           , syscall_info::unlimited()},
		{__NR_exit_group     , syscall_info::unlimited()},

		{__NR_arch_prctl     , syscall_info::unlimited()},

		{__NR_getrusage      , syscall_info::unlimited()},
		{__NR_getrlimit      , syscall_info::unlimited()},

		{__NR_gettimeofday   , syscall_info::unlimited()},
		{__NR_times          , syscall_info::unlimited()},
		{__NR_time           , syscall_info::unlimited()},
		{__NR_clock_gettime  , syscall_info::unlimited()},
		{__NR_clock_getres   , syscall_info::unlimited()},

		{__NR_restart_syscall, syscall_info::unlimited()},

        // for startup
		{__NR_setitimer      , syscall_info::count_based(1)},
		{__NR_execve         , syscall_info::count_based(1)},
		{__NR_set_robust_list, syscall_info::unlimited()   },

		// need to check file permissions
		{__NR_open           , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_CHECK_OPEN_FLAGS)},
		{__NR_openat         , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_CHECK_OPEN_FLAGS)},
		{__NR_readlink       , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_S)},
		{__NR_readlinkat     , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_FILE_S)},
		{__NR_access         , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_R)},
		{__NR_faccessat      , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_FILE_R)},
		{__NR_stat           , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_S)},
		{__NR_statfs         , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_S)},
		{__NR_lstat          , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_S)},
		{__NR_newfstatat     , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_S)},

		// kill could be DGS or RE
		{__NR_kill           , syscall_info::kill_type_syscall()},
		{__NR_tkill          , syscall_info::kill_type_syscall()},
		{__NR_tgkill         , syscall_info::kill_type_syscall()},

		// for python
		{__NR_prlimit64      , syscall_info::soft_ban()},

		// for python and java
		{__NR_sysinfo        , syscall_info::unlimited()},

		// python3 uses this call to generate random numbers
		// for fairness, all types of programs can use this call
		{__NR_getrandom      , syscall_info::unlimited()},

		// some python library uses epoll (e.g., z3-solver)
		{__NR_epoll_create   , syscall_info::unlimited()},
		{__NR_epoll_create1  , syscall_info::unlimited()},
		{__NR_epoll_ctl      , syscall_info::unlimited()},
		{__NR_epoll_wait     , syscall_info::unlimited()},
		{__NR_epoll_pwait    , syscall_info::unlimited()},

		// for java
		{__NR_geteuid        , syscall_info::unlimited()},
		{__NR_getuid         , syscall_info::unlimited()},
		{__NR_setrlimit      , syscall_info::soft_ban()},
		{__NR_socket         , syscall_info::soft_ban()},
		{__NR_connect        , syscall_info::soft_ban()},
	}},

	{"allow_proc", {
		{__NR_clone          , syscall_info::unlimited()},
		{__NR_fork           , syscall_info::unlimited()},
		{__NR_vfork          , syscall_info::unlimited()},
		{__NR_nanosleep      , syscall_info::unlimited()},
		{__NR_clock_nanosleep, syscall_info::unlimited()},
		{__NR_wait4          , syscall_info::unlimited()},
		
		{__NR_execve         , syscall_info::with_extra_check(ECT_FILE_OP | ECT_FILE_R)},
	}},

	{"python2.7", {
		{__NR_set_tid_address, syscall_info::count_based(1)},

		{__NR_futex          , syscall_info::unlimited()},
		{__NR_getdents       , syscall_info::unlimited()},
		{__NR_getdents64     , syscall_info::unlimited()},
	}},

	{"python3", {
		{__NR_set_tid_address, syscall_info::count_based(1)},

		{__NR_futex          , syscall_info::unlimited()},
		{__NR_getdents       , syscall_info::unlimited()},
		{__NR_getdents64     , syscall_info::unlimited()},
	}},

	{"java8", {
		{__NR_set_tid_address  , syscall_info::count_based(1)},
		{__NR_clone            , syscall_info::with_extra_check(ECT_CLONE_THREAD, 9)},

		{__NR_futex            , syscall_info::unlimited()},
		{__NR_getdents         , syscall_info::unlimited()},
		{__NR_getdents64       , syscall_info::unlimited()},

		{__NR_sched_getaffinity, syscall_info::unlimited()},
		{__NR_sched_yield      , syscall_info::unlimited()},
	}},

	{"java11", {
		{__NR_set_tid_address  , syscall_info::count_based(1)},
		{__NR_clone            , syscall_info::with_extra_check(ECT_CLONE_THREAD, 11)},
		{__NR_prctl            , syscall_info::unlimited()}, // TODO: add extra checks for prctl
		{__NR_prlimit64        , syscall_info::unlimited()}, // TODO: add extra checks for prlimit64

		{__NR_futex            , syscall_info::unlimited()},
		{__NR_getdents         , syscall_info::unlimited()},
		{__NR_getdents64       , syscall_info::unlimited()},

		{__NR_sched_getaffinity, syscall_info::unlimited()},
		{__NR_sched_yield      , syscall_info::unlimited()},

		{__NR_nanosleep        , syscall_info::unlimited()},
		{__NR_clock_nanosleep  , syscall_info::unlimited()},
	}},

	{"java17", {
		{__NR_set_tid_address  , syscall_info::count_based(1)},
		{__NR_clone            , syscall_info::with_extra_check(ECT_CLONE_THREAD, 13)},
		{__NR_prctl            , syscall_info::unlimited()}, // TODO: add extra checks for prctl
		{__NR_prlimit64        , syscall_info::unlimited()}, // TODO: add extra checks for prlimit64

		{__NR_futex            , syscall_info::unlimited()},
		{__NR_getdents         , syscall_info::unlimited()},
		{__NR_getdents64       , syscall_info::unlimited()},

		{__NR_sched_getaffinity, syscall_info::unlimited()},
		{__NR_sched_yield      , syscall_info::unlimited()},

		{__NR_nanosleep        , syscall_info::unlimited()},
		{__NR_clock_nanosleep  , syscall_info::unlimited()},
	}},

	{"compiler", {
		{__NR_set_tid_address  , syscall_info::unlimited()},
		{__NR_futex            , syscall_info::unlimited()},

		{__NR_clone            , syscall_info::unlimited()},
		{__NR_fork             , syscall_info::unlimited()},
		{__NR_vfork            , syscall_info::unlimited()},
		{__NR_nanosleep        , syscall_info::unlimited()},
		{__NR_clock_nanosleep  , syscall_info::unlimited()},
		{__NR_wait4            , syscall_info::unlimited()},

		{__NR_geteuid          , syscall_info::unlimited()},
		{__NR_getuid           , syscall_info::unlimited()},
		{__NR_getgid           , syscall_info::unlimited()},
		{__NR_getegid          , syscall_info::unlimited()},
		{__NR_getppid          , syscall_info::unlimited()},

		{__NR_setrlimit        , syscall_info::unlimited()},
		{__NR_prlimit64        , syscall_info::unlimited()},
		{__NR_prctl            , syscall_info::unlimited()},

		{__NR_pipe             , syscall_info::unlimited()},
		{__NR_pipe2            , syscall_info::unlimited()},

		// for java... we have no choice
		{__NR_socketpair       , syscall_info::unlimited()},
		{__NR_socket           , syscall_info::unlimited()},
		{__NR_getsockname      , syscall_info::unlimited()},
		{__NR_setsockopt       , syscall_info::unlimited()},
		{__NR_connect          , syscall_info::unlimited()},
		{__NR_sendto           , syscall_info::unlimited()},
		{__NR_poll             , syscall_info::unlimited()},
		{__NR_recvmsg          , syscall_info::unlimited()},
		{__NR_sysinfo          , syscall_info::unlimited()},

		{__NR_umask            , syscall_info::unlimited()},
		{__NR_getdents         , syscall_info::unlimited()},
		{__NR_getdents64       , syscall_info::unlimited()},

		{__NR_chdir            , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_S)},
		{__NR_fchdir           , syscall_info::unlimited()},

		{__NR_execve           , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_R)},
		{__NR_execveat         , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_FILE_R)},

		{__NR_truncate         , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_W)},
		{__NR_ftruncate        , syscall_info::unlimited()},

		{__NR_chmod            , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_W)},
		{__NR_fchmodat         , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_FILE_W)},
		{__NR_fchmod           , syscall_info::unlimited()},

		{__NR_rename           , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_W | ECT_FILE2_W)},
		{__NR_renameat         , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_FILE_W | ECT_FILE2_W)},
		{__NR_renameat2        , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_FILE_W | ECT_FILE2_W)},

		{__NR_unlink           , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_W)},
		{__NR_unlinkat         , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_FILE_W)},

		{__NR_mkdir            , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_W)},
		{__NR_mkdirat          , syscall_info::with_extra_check(ECT_FILEAT_OP | ECT_FILE_W)},

		{__NR_rmdir            , syscall_info::with_extra_check(ECT_FILE_OP   | ECT_FILE_W)},

		{__NR_fadvise64        , syscall_info::unlimited()},

		{__NR_sched_getaffinity, syscall_info::unlimited()},
		{__NR_sched_yield      , syscall_info::unlimited()},

		{__NR_kill           , syscall_info::kill_type_syscall(ECT_KILL_SIG0_ALLOWED, -1)},
		{__NR_tkill          , syscall_info::kill_type_syscall(ECT_KILL_SIG0_ALLOWED, -1)},
		{__NR_tgkill         , syscall_info::kill_type_syscall(ECT_KILL_SIG0_ALLOWED, -1)},
	}},
};

std::map<std::string, std::vector<std::string>> soft_ban_file_name_list = {
	{"default", {
		"/dev/tty",

		// for python 3.9
		"/usr/lib/python39.zip",

		// for java and javac...
		"/etc/nsswitch.conf",
		"/etc/passwd",
	}}
};

std::map<std::string, std::vector<std::string>> statable_file_name_list = {
	{"default", {}},
	
	{"python2.7", {
		"/usr",
		"/usr/bin",
		"/usr/lib",
	}},

	{"python3", {
		"/usr",
		"/usr/bin",
		"/usr/lib",
	}},

	{"java8", {
		"/usr/java/",
		"/tmp/",
	}},

	{"java11", {
		"/tmp/",
	}},

	{"java17", {
		"/tmp/",
	}},

	{"compiler", {
		"/*",
		"/boot/",
	}}
};

std::map<std::string, std::vector<std::string>> readable_file_name_list = {
	{"default", {
		"/lib/x86_64-linux-gnu/",
		"/usr/lib/x86_64-linux-gnu/",
		"/usr/lib/locale/",
		"/usr/share/zoneinfo/",
		"/etc/ld.so.nohwcap",
		"/etc/ld.so.preload",
		"/etc/ld.so.cache",
		"/etc/timezone",
		"/etc/localtime",
		"/etc/locale.alias",
		"/proc/self/",
		"/proc/*",
		"/dev/random",
		"/dev/urandom",
		"/sys/devices/system/cpu/", // for java & some python libraries
		"/proc/sys/vm/", // for java
	}},

	{"python2.7", {
		"/etc/python2.7/",
		"/usr/bin/python2.7",
		"/usr/lib/python2.7/",
		"/usr/bin/lib/python2.7/",
		"/usr/local/lib/python2.7/",
		"/usr/lib/pymodules/python2.7/",
		"/usr/bin/Modules/",
		"/usr/bin/pybuilddir.txt",
	}},

	{"python3", {
		"/etc/python3.9/",
		"/usr/bin/python3.9",
		"/usr/lib/python3.9/",
		"/usr/lib/python3/dist-packages/",
		"/usr/bin/lib/python3.9/",
		"/usr/local/lib/python3.9/",
		"/usr/bin/pyvenv.cfg",
		"/usr/pyvenv.cfg",
		"/usr/bin/Modules/",
		"/usr/bin/pybuilddir.txt",
		"/usr/lib/dist-python",
	}},

	{"java8", {
		"/sys/fs/cgroup/",
	}},

	{"java11", {
		UOJ_OPEN_JDK11 "/",
		"/sys/fs/cgroup/",
		"/etc/java-11-openjdk/",
		"/usr/share/java/",
	}},

	{"java17", {
		UOJ_OPEN_JDK17 "/",
		"/sys/fs/cgroup/",
		"/etc/java-17-openjdk/",
		"/usr/share/java/",
	}},

	{"compiler", {
		"system_root",
		"/usr/",
		"/lib/",
		"/lib64/",
		"/bin/",
		"/sbin/",
		"/sys/fs/cgroup/",
		"/proc/",
		"/etc/timezone",
		"/etc/python2.7/",
		"/etc/python3.9/",
		"/etc/fpc-3.0.4.cfg",
		"/etc/java-11-openjdk/",
		"/etc/java-17-openjdk/",
	}}
};

std::map<std::string, std::vector<std::string>> writable_file_name_list = {
	{"default", {
		"/dev/null",

		// for java11 and java17
		"/proc/self/coredump_filter",
	}},

	{"compiler", {
		"/tmp/",
	}}
};

const int N_SYSCALL = 335;
std::string syscall_name[N_SYSCALL] = {
	"read",
	"write",
	"open",
	"close",
	"stat",
	"fstat",
	"lstat",
	"poll",
	"lseek",
	"mmap",
	"mprotect",
	"munmap",
	"brk",
	"rt_sigaction",
	"rt_sigprocmask",
	"rt_sigreturn",
	"ioctl",
	"pread64",
	"pwrite64",
	"readv",
	"writev",
	"access",
	"pipe",
	"select",
	"sched_yield",
	"mremap",
	"msync",
	"mincore",
	"madvise",
	"shmget",
	"shmat",
	"shmctl",
	"dup",
	"dup2",
	"pause",
	"nanosleep",
	"getitimer",
	"alarm",
	"setitimer",
	"getpid",
	"sendfile",
	"socket",
	"connect",
	"accept",
	"sendto",
	"recvfrom",
	"sendmsg",
	"recvmsg",
	"shutdown",
	"bind",
	"listen",
	"getsockname",
	"getpeername",
	"socketpair",
	"setsockopt",
	"getsockopt",
	"clone",
	"fork",
	"vfork",
	"execve",
	"exit",
	"wait4",
	"kill",
	"uname",
	"semget",
	"semop",
	"semctl",
	"shmdt",
	"msgget",
	"msgsnd",
	"msgrcv",
	"msgctl",
	"fcntl",
	"flock",
	"fsync",
	"fdatasync",
	"truncate",
	"ftruncate",
	"getdents",
	"getcwd",
	"chdir",
	"fchdir",
	"rename",
	"mkdir",
	"rmdir",
	"creat",
	"link",
	"unlink",
	"symlink",
	"readlink",
	"chmod",
	"fchmod",
	"chown",
	"fchown",
	"lchown",
	"umask",
	"gettimeofday",
	"getrlimit",
	"getrusage",
	"sysinfo",
	"times",
	"ptrace",
	"getuid",
	"syslog",
	"getgid",
	"setuid",
	"setgid",
	"geteuid",
	"getegid",
	"setpgid",
	"getppid",
	"getpgrp",
	"setsid",
	"setreuid",
	"setregid",
	"getgroups",
	"setgroups",
	"setresuid",
	"getresuid",
	"setresgid",
	"getresgid",
	"getpgid",
	"setfsuid",
	"setfsgid",
	"getsid",
	"capget",
	"capset",
	"rt_sigpending",
	"rt_sigtimedwait",
	"rt_sigqueueinfo",
	"rt_sigsuspend",
	"sigaltstack",
	"utime",
	"mknod",
	"uselib",
	"personality",
	"ustat",
	"statfs",
	"fstatfs",
	"sysfs",
	"getpriority",
	"setpriority",
	"sched_setparam",
	"sched_getparam",
	"sched_setscheduler",
	"sched_getscheduler",
	"sched_get_priority_max",
	"sched_get_priority_min",
	"sched_rr_get_interval",
	"mlock",
	"munlock",
	"mlockall",
	"munlockall",
	"vhangup",
	"modify_ldt",
	"pivot_root",
	"_sysctl",
	"prctl",
	"arch_prctl",
	"adjtimex",
	"setrlimit",
	"chroot",
	"sync",
	"acct",
	"settimeofday",
	"mount",
	"umount2",
	"swapon",
	"swapoff",
	"reboot",
	"sethostname",
	"setdomainname",
	"iopl",
	"ioperm",
	"create_module",
	"init_module",
	"delete_module",
	"get_kernel_syms",
	"query_module",
	"quotactl",
	"nfsservctl",
	"getpmsg",
	"putpmsg",
	"afs_syscall",
	"tuxcall",
	"security",
	"gettid",
	"readahead",
	"setxattr",
	"lsetxattr",
	"fsetxattr",
	"getxattr",
	"lgetxattr",
	"fgetxattr",
	"listxattr",
	"llistxattr",
	"flistxattr",
	"removexattr",
	"lremovexattr",
	"fremovexattr",
	"tkill",
	"time",
	"futex",
	"sched_setaffinity",
	"sched_getaffinity",
	"set_thread_area",
	"io_setup",
	"io_destroy",
	"io_getevents",
	"io_submit",
	"io_cancel",
	"get_thread_area",
	"lookup_dcookie",
	"epoll_create",
	"epoll_ctl_old",
	"epoll_wait_old",
	"remap_file_pages",
	"getdents64",
	"set_tid_address",
	"restart_syscall",
	"semtimedop",
	"fadvise64",
	"timer_create",
	"timer_settime",
	"timer_gettime",
	"timer_getoverrun",
	"timer_delete",
	"clock_settime",
	"clock_gettime",
	"clock_getres",
	"clock_nanosleep",
	"exit_group",
	"epoll_wait",
	"epoll_ctl",
	"tgkill",
	"utimes",
	"vserver",
	"mbind",
	"set_mempolicy",
	"get_mempolicy",
	"mq_open",
	"mq_unlink",
	"mq_timedsend",
	"mq_timedreceive",
	"mq_notify",
	"mq_getsetattr",
	"kexec_load",
	"waitid",
	"add_key",
	"request_key",
	"keyctl",
	"ioprio_set",
	"ioprio_get",
	"inotify_init",
	"inotify_add_watch",
	"inotify_rm_watch",
	"migrate_pages",
	"openat",
	"mkdirat",
	"mknodat",
	"fchownat",
	"futimesat",
	"newfstatat",
	"unlinkat",
	"renameat",
	"linkat",
	"symlinkat",
	"readlinkat",
	"fchmodat",
	"faccessat",
	"pselect6",
	"ppoll",
	"unshare",
	"set_robust_list",
	"get_robust_list",
	"splice",
	"tee",
	"sync_file_range",
	"vmsplice",
	"move_pages",
	"utimensat",
	"epoll_pwait",
	"signalfd",
	"timerfd_create",
	"eventfd",
	"fallocate",
	"timerfd_settime",
	"timerfd_gettime",
	"accept4",
	"signalfd4",
	"eventfd2",
	"epoll_create1",
	"dup3",
	"pipe2",
	"inotify_init1",
	"preadv",
	"pwritev",
	"rt_tgsigqueueinfo",
	"perf_event_open",
	"recvmmsg",
	"fanotify_init",
	"fanotify_mark",
	"prlimit64",
	"name_to_handle_at",
	"open_by_handle_at",
	"clock_adjtime",
	"syncfs",
	"sendmmsg",
	"setns",
	"getcpu",
	"process_vm_readv",
	"process_vm_writev",
	"kcmp",
	"finit_module",
	"sched_setattr",
	"sched_getattr",
	"renameat2",
	"seccomp",
	"getrandom",
	"memfd_create",
	"kexec_file_load",
	"bpf",
	"execveat",
	"userfaultfd",
	"membarrier",
	"mlock2",
	"copy_file_range",
	"preadv2",
	"pwritev2",
	"pkey_mprotect",
	"pkey_alloc",
	"pkey_free",
	"statx",
	"io_pgetevents",
	"rseq"
};
