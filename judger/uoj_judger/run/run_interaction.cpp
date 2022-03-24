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
#include "uoj_run.h"

using namespace std;

struct RunCmdData {
	string cmd;
	pid_t pid;

	vector<int> ipipes, opipes;
};
struct PipeData {
	runp::interaction::pipe_config config;
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
		if (freopen("/dev/null", "r", stdin) == NULL) {
			throw system_error(errno, system_category());
		}
		if (freopen("/dev/null", "w", stdout) == NULL) {
			throw system_error(errno, system_category());
		}
		if (freopen("/dev/null", "w", stderr) == NULL) {
			throw system_error(errno, system_category());
		}

		for (int i = 0; i < (int)pipes.size(); i++) {
			if (pipes[i].config.from - 1 == id) {
				dup2(pipes[i].ipipefd[1], 128 + pipes[i].ipipefd[1]);
			}
			if (pipes[i].config.to - 1 == id) {
				dup2(pipes[i].opipefd[0], 128 + pipes[i].opipefd[0]);
			}
			close(pipes[i].ipipefd[0]);
			close(pipes[i].ipipefd[1]);
			close(pipes[i].opipefd[0]);
			close(pipes[i].opipefd[1]);
		}
		for (int i = 0; i < (int)pipes.size(); i++) {
			if (pipes[i].config.from - 1 == id) {
				dup2(128 + pipes[i].ipipefd[1], pipes[i].config.from_fd);
				close(128 + pipes[i].ipipefd[1]);
			}
			if (pipes[i].config.to - 1 == id) {
				dup2(128 + pipes[i].opipefd[0], pipes[i].config.to_fd);
				close(128 + pipes[i].opipefd[0]);
			}
		}
	}

	void wait_pipe_io(int pipe_id) {
		FILE *sf = NULL;
		if (!pipes[pipe_id].config.saving_file_name.empty()) {
			sf = fopen(pipes[pipe_id].config.saving_file_name.c_str(), "w");
		}

		int ifd = pipes[pipe_id].ipipefd[0];
		int ofd = pipes[pipe_id].opipefd[1];

		FILE *inf = fdopen(ifd, "r");
		FILE *ouf = fdopen(ofd, "w");

		try {
			pipes[pipe_id].eptr = nullptr;

			// const int L = 4096;
			// char buf[L];

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

		if (sf) {
			fclose(sf);
		}
		if (inf) {
			fclose(inf);
		}
		if (ouf) {
			fclose(ouf);
		}
	}
public:
	RunInteraction(const runp::interaction::config &config) {
		cmds.resize(config.cmds.size());
		for (int i = 0; i < (int)config.cmds.size(); i++) {
			cmds[i].cmd = config.cmds[i];
		}
		
		pipes.resize(config.pipes.size());
		for (int i = 0; i < (int)config.pipes.size(); i++) {
			pipes[i].config = config.pipes[i];
			cmds[pipes[i].config.from - 1].opipes.push_back(i);
			cmds[pipes[i].config.to - 1].ipipes.push_back(i);
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
				execl("/bin/sh", "sh", "-c", cmds[i].cmd.c_str(), NULL);
				exit(1); // exec failed, exit 1
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
	runp::interaction::config *config = (runp::interaction::config*)state->input;

	try {
		switch (key) {
			case 'p':
				config->pipes.push_back(runp::interaction::pipe_config(arg));
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

runp::interaction::config parse_args(int argc, char **argv) {
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

	runp::interaction::config config;

	argp_parse(&run_interaction_argp, argc, argv, ARGP_NO_ARGS | ARGP_IN_ORDER, 0, &config);

	return config;
}

int main(int argc, char **argv) {
	signal(SIGPIPE, SIG_IGN);

	RunInteraction ri(parse_args(argc, argv));
	ri.join();

	return 0;
}
