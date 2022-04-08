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

struct run_cmd_data {
	string cmd;
	pid_t pid;

	vector<int> ipipes, opipes;
};
struct pipe_data {
	runp::interaction::pipe_config config;
	int ipipefd[2], opipefd[2];
	thread io_thread;
	exception_ptr eptr;
};

void write_all_or_throw(int fd, char *buf, int n) {
	int wcnt = 0;
	while (wcnt < n) {
		int ret = write(fd, buf + wcnt, n - wcnt);
		if (ret == -1) {
			throw system_error(errno, system_category());
		}
		wcnt += ret;
	}
}

class interaction_runner {
private:
	vector<run_cmd_data> cmds;
	vector<pipe_data> pipes;

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
		FILE *sf = nullptr;
		if (!pipes[pipe_id].config.saving_file_name.empty()) {
			sf = fopen(pipes[pipe_id].config.saving_file_name.c_str(), "w");
		}
		int ifd = pipes[pipe_id].ipipefd[0];
		int ofd = pipes[pipe_id].opipefd[1];
		int sfd = sf ? fileno(sf) : -1;

		int iflags = fcntl(ifd, F_GETFL);

		const int L = 4096;
		char buf[L];

		int sbuf_len = 0;
		char sbuf[L * 2];

		try {
			pipes[pipe_id].eptr = nullptr;

			while (true) {
				int cnt1 = read(ifd, buf, 1);
				if (cnt1 == -1) {
					throw system_error(errno, system_category());
				}
				if (cnt1 == 0) {
					break;
				}

				fcntl(ifd, F_SETFL, iflags | O_NONBLOCK);
				int cnt2 = read(ifd, buf + 1, L - 1);
				fcntl(ifd, F_SETFL, iflags);

				if (cnt2 == -1) {
					if (errno != EAGAIN) {
						throw system_error(errno, system_category());
					}
					cnt2 = 0;
				}

				write_all_or_throw(ofd, buf, cnt2 + 1);

				if (sf) {
					memcpy(sbuf + sbuf_len, buf, cnt2 + 1);
					sbuf_len += cnt2 + 1;
					if (sbuf_len > L) {
						write_all_or_throw(sfd, sbuf, sbuf_len);
						sbuf_len = 0;
					}
				}
			}
		} catch (exception &e) {
			cerr << e.what() << endl;
			pipes[pipe_id].eptr = current_exception();
		}

		if (sf) {
			if (sbuf_len > 0) {
				write_all_or_throw(sfd, sbuf, sbuf_len);
				sbuf_len = 0;
			}
			fclose(sf);
		}

		close(ifd);
		close(ofd);
	}
public:
	interaction_runner(const runp::interaction::config &config) {
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
			pipes[i].io_thread = thread(&interaction_runner::wait_pipe_io, this, i);
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

	interaction_runner ri(parse_args(argc, argv));
	ri.join();

	return 0;
}
