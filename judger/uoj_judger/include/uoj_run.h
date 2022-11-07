#include <string>
#include <vector>
#include <map>
#include <sstream>
#include <fstream>
#include <cstdarg>
#include <filesystem>
#include <exception>
#include <stdexcept>

#define UOJ_GCC "/usr/bin/gcc-10"
#define UOJ_GPLUSPLUS "/usr/bin/g++-10"
#define UOJ_PYTHON2_7 "/usr/bin/python2.7"
#define UOJ_PYTHON3 "/usr/bin/python3.9"
#define UOJ_FPC "/usr/bin/fpc"
#define UOJ_JDK8 "jdk1.8.0_202"
#define UOJ_OPEN_JDK11 "/usr/lib/jvm/java-11-openjdk-amd64"
#define UOJ_OPEN_JDK17 "/usr/lib/jvm/java-17-openjdk-amd64"

std::string escapeshellarg(int arg) {
	return std::to_string(arg);
}
std::string escapeshellarg(const std::string &arg) {
	std::string res = "'";
	for (char c : arg) {
		if (c == '\'') {
			res += "'\\''";
		} else {
			res += c;
		}
	}
	res += "'";
	return res;
}

template <typename T>
std::ostream& spaced_out(std::ostream &out, const T &arg) {
    return out << arg;
}

template <typename T, typename... Args>
std::ostream& spaced_out(std::ostream &out, const T &arg, const Args& ...rest) {
    return spaced_out(out << arg << " ", rest...);
}

template <typename T>
std::ostream& add_spaced_out(std::ostream &out, const T &arg) {
    return out << " " << arg;
}

template <typename T, typename... Args>
std::ostream& add_spaced_out(std::ostream &out, const T &arg, const Args& ...rest) {
    return spaced_out(out << " " << arg, rest...);
}

template <typename... Args>
int execute(const Args& ...args) {
    std::ostringstream sout;
    spaced_out(sout, args...);
#ifdef UOJ_SHOW_EVERY_CMD
	std::cerr << sout.str() << std::endl;
#endif
	int status = system(sout.str().c_str());
    if (status == -1 || !WIFEXITED(status)) {
        return -1;
    }
    return WEXITSTATUS(status);
}

int executef(const char *fmt, ...) {
	const int L = 1 << 10;
	char cmd[L];
	va_list ap;
	va_start(ap, fmt);
	int res = vsnprintf(cmd, L, fmt, ap);
	if (res < 0 || res >= L) {
		return -1;
	}
	res = execute(cmd);
	va_end(ap);
	return res;
}

class cannot_determine_class_name_error : std::invalid_argument {
public:
    explicit cannot_determine_class_name_error()
        : std::invalid_argument("cannot determine the class name!") {}
};

std::string get_class_name_from_file(const std::string &fname) {
	std::ifstream fin(fname);
	if (!fin) {
		throw cannot_determine_class_name_error();
	}
	std::string class_name;
	if (!(fin >> class_name)) {
		throw cannot_determine_class_name_error();
	}
	if (class_name.length() > 100) {
		throw cannot_determine_class_name_error();
	}
	for (char &c : class_name) {
		if (!isalnum(c) && c != '_') {
			throw cannot_determine_class_name_error();
		}
	}
	return class_name;
}

bool put_class_name_to_file(const std::string &fname, const std::string &class_name) {
	std::ofstream fout(fname);
	if (!fout) {
		return false;
	}
	if (!(fout << class_name << std::endl)) {
		return false;
	}
	return true;
}

std::map<std::string, std::string> lang_upgrade_map = {
    {"Java7" , "Java8"    },
    {"Java14", "Java17"   },
};

std::string upgraded_lang(const std::string &lang) {
	return lang_upgrade_map.count(lang) ? lang_upgrade_map[lang] : lang;
}

namespace runp {
	namespace fs = std::filesystem;
	fs::path run_path;

	struct limits_t {
		double time;
		int memory;
		int output;
		double real_time;
		int stack;

		limits_t() = default;
		limits_t(const double &_time, const int &_memory, const int &_output)
				: time(_time), memory(_memory), output(_output), real_time(-1), stack(-1) {
		}
	};

	// result type
	enum RS_TYPE {
		RS_AC = 0,
		RS_WA = 1,
		RS_RE = 2,
		RS_MLE = 3,
		RS_TLE = 4,
		RS_OLE = 5,
		RS_DGS = 6,
		RS_JGF = 7
	};

	inline std::string rstype_str(RS_TYPE id)  {
		switch (id) {
		case RS_AC: return "Accepted";
		case RS_WA: return "Wrong Answer";
		case RS_RE : return "Runtime Error";
		case RS_MLE: return "Memory Limit Exceeded";
		case RS_TLE: return "Time Limit Exceeded";
		case RS_OLE: return "Output Limit Exceeded";
		case RS_DGS: return "Dangerous Syscalls";
		case RS_JGF: return "Judgment Failed";
		default    : return "Unknown Result";
		}
	}

	inline std::string get_type_from_lang(std::string lang) {
		lang = upgraded_lang(lang);
		if (lang == "Python2.7") {
			return "python2.7";
		} else if (lang == "Python3") {
			return "python3";
		} else if (lang == "Java8") {
			return "java8";
		} else if (lang == "Java11") {
			return "java11";
		} else if (lang == "Java17") {
			return "java17";
		} else {
			return "default";
		}
	}

	struct result {
		static std::string result_file_name;

		RS_TYPE type;
		std::string extra;
		int ust, usm;
		int exit_code;

		result() = default;
		result(RS_TYPE type, std::string extra, int ust = -1, int usm = -1, int exit_code = -1)
				: type(type), extra(extra), ust(ust), usm(usm), exit_code(exit_code) {
			if (this->type != RS_AC) {
				this->ust = -1, this->usm = -1;
			}
		}

		static result failed_result() {
			result res;
			res.type = RS_JGF;
			res.ust = -1;
			res.usm = -1;
			return res;
		}

		static result from_file(const std::string &file_name) {
			result res;
			FILE *fres = fopen(file_name.c_str(), "r");
			if (!fres) {
				return result::failed_result();
			}
			int type;
			if (fscanf(fres, "%d %d %d %d\n", &type, &res.ust, &res.usm, &res.exit_code) != 4) {
				fclose(fres);
				return result::failed_result();
			}
			res.type = (RS_TYPE)type;

			int L = 1 << 15;
			char buf[L];
			while (!feof(fres)) {
				int c = fread(buf, 1, L, fres);
				res.extra.append(buf, c);
				if (ferror(fres)) {
					fclose(fres);
					return result::failed_result();
				}
			}
			fclose(fres);
			return res;
		}

		[[noreturn]] void dump_and_exit() {
			FILE *f;
			if (result_file_name == "stdout") {
				f = stdout;
			} else if (result_file_name == "stderr") {
				f = stderr;
			} else {
				f = fopen(result_file_name.c_str(), "w");
			}
			fprintf(f, "%d %d %d %d\n", this->type, this->ust, this->usm, this->exit_code);
			fprintf(f, "%s\n", this->extra.c_str());
			if (f != stdout && f != stderr) {
				fclose(f);
			}
			exit(this->type == RS_JGF ? 1 : 0);
		}
	};

	std::string result::result_file_name("stdout");

	template <typename T1, typename T2>
	inline std::ostream& add_runp_arg(std::ostream &out, const std::pair<T1, std::vector<T2>> &arg) {
		for (const auto &t : arg.second) {
			out << " --" << arg.first << "=" << escapeshellarg(t);
		}
		return out;
	}
	template <typename T1, typename T2>
	inline std::ostream& add_runp_arg(std::ostream &out, const std::pair<T1, T2> &arg) {
		return out << " --" << arg.first << "=" << escapeshellarg(arg.second);
	}
	inline std::ostream& add_runp_arg(std::ostream &out, const std::vector<std::string> &arg) {
		for (const auto &t : arg) {
			out << " " << escapeshellarg(t);
		}
		return out;
	}
	inline std::ostream& add_runp_arg(std::ostream &out, const std::string &arg) {
		return out << " " << escapeshellarg(arg);
	}

	struct config {
		std::vector<std::string> readable_file_names; // other than stdin
		std::vector<std::string> writable_file_names; // other than stdout, stderr
		std::string result_file_name;
		std::string input_file_name;
		std::string output_file_name;
		std::string error_file_name = "/dev/null";
		std::string type = "default";
		std::string work_path;
		limits_t limits;
		std::string program_name;
		std::vector<std::string> rest_args;

		// full args (possbily with interpreter)
		std::vector<std::string> full_args;

		bool unsafe = false;
		bool allow_proc = false;
		bool need_show_trace_details = false;

		config(std::string program_name = "", const std::vector<std::string> &rest_args = {})
			: program_name(program_name), rest_args(rest_args) {
		}

		config &set_type(const std::string &type) {
			this->type = type;
			return *this;
		}

		std::string get_cmd() const {
			std::ostringstream sout;
			sout << escapeshellarg(run_path / "run_program");

			if (this->need_show_trace_details) {
				add_runp_arg(sout, "--show-trace-details");
			}

			add_runp_arg(sout, std::make_pair("res", this->result_file_name));
			add_runp_arg(sout, std::make_pair("in", this->input_file_name));
			add_runp_arg(sout, std::make_pair("out", this->output_file_name));
			add_runp_arg(sout, std::make_pair("err", this->error_file_name));
			add_runp_arg(sout, std::make_pair("type", this->type));

			// limits
			add_runp_arg(sout, std::make_pair("tl", this->limits.time));
			add_runp_arg(sout, std::make_pair("ml", this->limits.memory));
			add_runp_arg(sout, std::make_pair("ol", this->limits.output));
			if (this->limits.real_time != -1) {
				add_runp_arg(sout, std::make_pair("rtl", this->limits.real_time));
			}
			if (this->limits.stack != -1) {
				add_runp_arg(sout, std::make_pair("sl", this->limits.stack));
			}

			if (this->unsafe) {
				add_runp_arg(sout, "--unsafe");
			}
			if (this->allow_proc) {
				add_runp_arg(sout, "--allow-proc");
			}

			if (!this->work_path.empty()) {
				add_runp_arg(sout, std::make_pair("work-path", this->work_path));
			}

			add_runp_arg(sout, std::make_pair("add-readable", this->readable_file_names));
			add_runp_arg(sout, std::make_pair("add-writable", this->writable_file_names));

			add_runp_arg(sout, this->program_name);
			add_runp_arg(sout, this->rest_args);

			return sout.str();
		}

		void gen_full_args() {
			// assume that current_path() == work_path

			full_args.clear();
			full_args.push_back(program_name);
			full_args.insert(full_args.end(), rest_args.begin(),rest_args.end());

			if (type == "java8") {
				full_args[0] = get_class_name_from_file(fs::path(full_args[0]) / ".main_class_name");

				std::string jdk = UOJ_JDK8;
				full_args.insert(full_args.begin(), {
					fs::canonical(run_path / "runtime" / jdk / "bin" / "java"), "-Xmx2048m", "-Xss1024m",
					"-XX:ActiveProcessorCount=1",
					"-classpath", program_name
				});
			} else if (type == "java11" || type == "java17") {
				full_args[0] = get_class_name_from_file(fs::path(full_args[0]) / ".main_class_name");

				std::string jdk;
				if (type == "java11") {
					jdk = UOJ_OPEN_JDK11;
				} else { // if (type == "java17") {
					jdk = UOJ_OPEN_JDK17;
				}
				full_args.insert(full_args.begin(), {
					fs::canonical(fs::path(jdk) / "bin" / "java"), "-Xmx2048m", "-Xss1024m",
					"-XX:ActiveProcessorCount=1",
					"-classpath", program_name
				});
			} else if (type == "python2.7") {
				full_args.insert(full_args.begin(), {
					UOJ_PYTHON2_7, "-E", "-s", "-B"
				});
			} else if (type == "python3") {
				full_args.insert(full_args.begin(), {
					UOJ_PYTHON3, "-I", "-B"
				});
			}
		}
	};
}

namespace runp::interaction {
	struct pipe_config {
		int from, from_fd;
		int to, to_fd;
		std::string saving_file_name;

		pipe_config() = default;
		pipe_config(int _from, int _from_fd, int _to, int _to_fd, const std::string &_saving_file_name = "")
				: from(_from), from_fd(_from_fd), to(_to), to_fd(_to_fd), saving_file_name(_saving_file_name) {}
		pipe_config(const std::string &str) {
			if (sscanf(str.c_str(), "%d:%d-%d:%d", &from, &from_fd, &to, &to_fd) != 4) {
				throw std::invalid_argument("bad init str for pipe");
			}
		}
	};

	struct config {
		std::vector<std::string> cmds;
		std::vector<pipe_config> pipes;

		std::string get_cmd() const {
			std::ostringstream sout;
			sout << escapeshellarg(run_path / "run_interaction");
			for (auto &cmd : cmds) {
				sout << " " << escapeshellarg(cmd);
			}
			for (auto &pipe : pipes) {
				sout << " " << "-p";
				sout << " " << pipe.from << ":" << pipe.from_fd;
				sout << "-" << pipe.to << ":" << pipe.to_fd;

				if (!pipe.saving_file_name.empty()) {
					sout << " " << "-s";
					sout << " " << escapeshellarg(pipe.saving_file_name);
				}
			}
			return sout.str();
		}
	};

	/*
	 * @return interaction return value
	 **/
	int run(const config &ric) {
		return execute(ric.get_cmd().c_str());
	}
}
