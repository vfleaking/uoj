#include <iostream>
#include <cstdio>
#include <cstdlib>
#include <exception>
#include <system_error>
#include <thread>
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

struct PipeConfig {
	int from, to;
	int from_fd, to_fd;

	string saving_file_name; // empty for none

	PipeConfig() {
	}
	PipeConfig(string str) {
		if (sscanf(str.c_str(), "%d:%d-%d:%d", &from, &from_fd, &to, &to_fd) != 4) {
			throw invalid_argument("bad init str for PipeConfig");
		}
		from -= 1;
		to -= 1;
	}
};

struct RunInteractionConfig {
	vector<string> cmds;
	vector<PipeConfig> pipes;
};

struct RunCmdData {
	string cmd;
	pid_t pid;

	vector<int> ipipes, opipes;
};
struct PipeData {
	PipeConfig config;
	int ipipefd[2], opipefd[2];
	thread io_thread;
	exception_ptr eptr;
};

class RunInteraction {
private:
	vector<RunCmdData> cmds;
	vector<PipeData> pipes;

	void prepare_fd() { // me
		for (int i = 0; i < (int)pipes.size(); i++) {
			close(pipes[i].ipipefd[1]);
			close(pipes[i].opipefd[0]);
		}
	}
	void prepare_fd_for_cmd(int id) {
		freopen("/dev/null", "r", stdin);
		freopen("/dev/null", "w", stdout);
		freopen("/dev/null", "w", stderr);

		for (int i = 0; i < (int)pipes.size(); i++) {
			if (pipes[i].config.from == id) {
				dup2(pipes[i].ipipefd[1], 128 + pipes[i].ipipefd[1]);
			}
			if (pipes[i].config.to == id) {
				dup2(pipes[i].opipefd[0], 128 + pipes[i].opipefd[0]);
			}
			close(pipes[i].ipipefd[0]);
			close(pipes[i].ipipefd[1]);
			close(pipes[i].opipefd[0]);
			close(pipes[i].opipefd[1]);
		}
		for (int i = 0; i < (int)pipes.size(); i++) {
			if (pipes[i].config.from == id) {
				dup2(128 + pipes[i].ipipefd[1], pipes[i].config.from_fd);
				close(128 + pipes[i].ipipefd[1]);
			}
			if (pipes[i].config.to == id) {
				dup2(128 + pipes[i].opipefd[0], pipes[i].config.to_fd);
				close(128 + pipes[i].opipefd[0]);
			}
		}
	}

	void wait_pipe_io(int pipe_id) {
		FILE *sf = NULL;
		if (!pipes[pipe_id].config.saving_file_name.empty())
			sf = fopen(pipes[pipe_id].config.saving_file_name.c_str(), "w");

		int ifd = pipes[pipe_id].ipipefd[0];
		int ofd = pipes[pipe_id].opipefd[1];

		FILE *inf = fdopen(ifd, "r");
		FILE *ouf = fdopen(ofd, "w");

		try {
			pipes[pipe_id].eptr = nullptr;

			const int L = 4096;
			char buf[L];

			while (true) {
				int c = fgetc(inf);
				if (c == EOF) {
					if (errno) {
						throw system_error(errno, system_category());
					}
					break;
				}

				if (fputc(c, ouf) == EOF) {
					throw system_error(errno, system_category());
				}
				fflush(ouf);

				if (fputc(c, sf) == EOF) {
					throw system_error(errno, system_category());
				}
			}
		} catch (exception &e) {
			cerr << e.what() << endl;
			pipes[pipe_id].eptr = current_exception();
		}

		fclose(sf);
		fclose(inf);
		fclose(ouf);
	}
public:
	RunInteraction(const RunInteractionConfig &config) {
		cmds.resize(config.cmds.size());
		for (int i = 0; i < (int)config.cmds.size(); i++) {
			cmds[i].cmd = config.cmds[i];
		}
		
		pipes.resize(config.pipes.size());
		for (int i = 0; i < (int)config.pipes.size(); i++) {
			pipes[i].config = config.pipes[i];
			cmds[pipes[i].config.from].opipes.push_back(i);
			cmds[pipes[i].config.to].ipipes.push_back(i);
		}

		for (int i = 0; i < (int)pipes.size(); i++) {
			if (pipe(pipes[i].ipipefd) == -1 || pipe(pipes[i].opipefd) == -1) {
				throw system_error(errno, system_category());
			}
		}
		for (int i = 0; i < (int)cmds.size(); i++) {
			cmds[i].pid = fork();
			if (cmds[i].pid == 0) {
				prepare_fd_for_cmd(i);
				system(cmds[i].cmd.c_str());
				exit(0);
			} else if (cmds[i].pid == -1) {
				throw system_error(errno, system_category());
			}
		}
		
		prepare_fd();
		for (int i = 0; i < (int)pipes.size(); i++) {
			pipes[i].io_thread = thread(&RunInteraction::wait_pipe_io, this, i);
		}
	}

	void join() {
		int status;
		while (wait(&status) > 0);

		for (int i = 0; i < (int)pipes.size(); i++) {
			pipes[i].io_thread.join();
		}
	}
};

error_t run_interaction_argp_parse_opt (int key, char *arg, struct argp_state *state) {
	RunInteractionConfig *config = (RunInteractionConfig*)state->input;

	try {
		switch (key) {
			case 'p':
				config->pipes.push_back(PipeConfig(arg));
				break;
			case 's':
				if (config->pipes.empty()) {
					argp_usage(state);
				}
				config->pipes.back().saving_file_name = arg;
				break;
			case ARGP_KEY_ARG:
				config->cmds.push_back(arg);
				break;
			case ARGP_KEY_END:
				if (state->arg_num == 0) {
					argp_usage(state);
				}
				break;
			default:
				return ARGP_ERR_UNKNOWN;
		}
	} catch (exception &e) {
		argp_usage(state);
	}

	return 0;
}

RunInteractionConfig parse_args(int argc, char **argv) {
	argp_option run_interaction_argp_options[] = {
		{"add-pipe"           , 'p', "PIPE"        , 0, "Add a pipe <from>:<fd>-<to>:<fd> (fd < 128)"           ,  1},
		{"save-pipe"          , 's', "FILE"        , 0, "Set last pipe saving file"                             ,  2},
		{0}
	};
	char run_interaction_argp_args_doc[] = "cmd1 cmd2 ...";
	char run_interaction_argp_doc[] = "run_interaction: a tool to run multiple commands with interaction";

	argp run_interaction_argp = {
		run_interaction_argp_options,
		run_interaction_argp_parse_opt,
		run_interaction_argp_args_doc,
		run_interaction_argp_doc
	};

	RunInteractionConfig config;

	argp_parse(&run_interaction_argp, argc, argv, ARGP_NO_ARGS | ARGP_IN_ORDER, 0, &config);

	return config;
}

int main(int argc, char **argv) {
	signal(SIGPIPE, SIG_IGN);

	RunInteraction ri(parse_args(argc, argv));
	ri.join();

	return 0;
}
