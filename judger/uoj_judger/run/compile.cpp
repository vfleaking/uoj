#include <iostream>
#include <cstdio>
#include <fstream>
#include <sstream>
#include <string>
#include <vector>
#include <utility>
#include <stdexcept>
#include <argp.h>

#include "uoj_run.h"

namespace fs = std::filesystem;

class language_not_supported_error : public std::invalid_argument {
public:
    explicit language_not_supported_error()
        : std::invalid_argument("This language has not been supported yet.") {}
};

class fail_to_read_src_error : public std::runtime_error {
public:
    explicit fail_to_read_src_error(const std::string &what = "An error occurs when trying to read the source code.")
        : std::runtime_error(what) {}
};

class compile_error : public std::invalid_argument {
public:
    explicit compile_error(const std::string &what)
        : std::invalid_argument(what) {}
};

struct compile_config {
    std::string name;
    std::string src;
    std::string lang;
    std::string implementer;
    std::string custom_compiler_path;
    std::vector<std::string> cinclude_dirs;

    void auto_find_src() {
        if (!src.empty()) {
            return;
        }

        std::pair<const char *, const char *> search_list[] = {
            {".code"  , ""         },
            {"20.cpp" , "C++20"    },
            {"17.cpp" , "C++17"    },
            {"14.cpp" , "C++14"    },
            {"11.cpp" , "C++11"    },
            {".cpp"   , "C++03"    },
            {".c"     , "C"        },
            {".pas"   , "Pascal"   },
            {"2.7.py" , "Python2.7"},
            {".py"    , "Python3"  },
            {"7.java" , "Java7"    },
            {"8.java" , "Java8"    },
            {"11.java", "Java11"   },
            {"14.java", "Java14"   },
        };
        for (auto &p : search_list) {
            if (fs::is_regular_file(name + p.first)) {
                src = fs::canonical(name + p.first);
                if (lang.empty()) {
                    lang = p.second;
                }
                return;
            }
        }
    }
};

error_t compile_argp_parse_opt(int key, char *arg, struct argp_state *state) {
    compile_config *config = (compile_config*)state->input;

	try {
		switch (key) {
        case 'i':
            config->implementer = arg;
            break;
        case 'l':
            config->lang = arg;
            break;
        case 'c':
            config->custom_compiler_path = arg;
            break;
        case 'I':
            config->cinclude_dirs.push_back(arg);
            break;
        case ARGP_KEY_ARG:
            config->name = arg;
            break;
        case ARGP_KEY_END:
            if (state->arg_num != 1) {
                argp_usage(state);
            }
            break;
        default:
            return ARGP_ERR_UNKNOWN;
		}
	} catch (std::exception &e) {
		argp_usage(state);
	}

	return 0;
}

compile_config parse_args(int argc, char **argv) {
	argp_option argp_options[] = {
		{"src"     , 's',  "SOURCE_CODE"  , 0, "set the path to source code"                                ,  1},
		{"impl"    , 'i',  "IMPLEMENTER"  , 0, "set the implementer name"                                   ,  2},
		{"lang"    , 'l',  "LANGUAGE"     , 0, "set the language"                                           ,  3},
		{"custom"  , 'c',  "CUSTOM"       , 0, "path to custom compilers (those are not placed in /usr/bin)",  4},
        {"cinclude", 'I',  "DIRECTORY"    , 0, "add the directory dir to the list of directories to be searched for header files during preprocessing (for C/C++)", 5},
		{0}
	};
	char argp_args_doc[] = "name";
	char argp_doc[] = "compile: a tool to compile programs";

	argp compile_argp = {
		argp_options,
		compile_argp_parse_opt,
		argp_args_doc,
		argp_doc
	};

	compile_config config;
	argp_parse(&compile_argp, argc, argv, ARGP_NO_ARGS | ARGP_IN_ORDER, 0, &config);

    config.auto_find_src();
    if (config.src.empty() || !fs::is_regular_file(config.src)) {
        throw fail_to_read_src_error();
    }

	return config;
}

bool is_illegal_keyword(const std::string &name) {
	return name == "__asm" || name == "__asm__" || name == "asm";
}

bool has_illegal_keywords_in_file(const std::string &src) {
    std::ifstream fin(src);
    if (!fin) {
        throw fail_to_read_src_error();
    }

    const int L = 1 << 15;
    char buf[L];
    std::string key;

    while (!fin.eof()) {
        fin.read(buf, L);
        int cnt = fin.gcount();
        for (char *p = buf; p != buf + cnt; p++) {
            char c = *p;
            if (isalnum(c) || c == '_') {
                if (key.size() < 20) {
                    key += c;
                } else {
                    if (is_illegal_keyword(key)) {
                        return true;
                    }
                    key.erase(key.begin());
                    key += c;
                }
            }
            else {
                if (is_illegal_keyword(key)) {
                    return true;
                }
                key.clear();
            }
        }
        if (fin.bad()) {
            throw fail_to_read_src_error();
        }
    }
	return false;
}

std::string get_java_main_class(const std::string &src) {
    std::ifstream fin(src);
    if (!fin) {
        throw fail_to_read_src_error();
    }

	const int L = 1 << 15;
	char buf[L];
	std::string s;

	int mode = 0;

	while (!fin.eof()) {
        fin.read(buf, L);
        int cnt = fin.gcount();
		for (char *p = buf; p != buf + cnt; p++) {
			s += *p;
			switch (mode) {
            case 0:
                switch (*p) {
                case '/':
                    mode = 1;
                    break;
                case '\'':
                    mode = 5;
                    break;
                case '\"':
                    mode = 6;
                    break;
                }
                break;
            case 1:
                switch (*p) {
                case '/':
                    mode = 2;
                    s.pop_back();
                    s.pop_back();
                    break;
                case '*':
                    mode = 3;
                    s.pop_back();
                    s.pop_back();
                    break;
                }
                break;
            case 2:
                s.pop_back();
                switch (*p) {
                case '\n':
                    s += '\n';
                    mode = 0;
                    break;
                }
                break;
            case 3:
                s.pop_back();
                switch (*p) {
                case '*':
                    mode = 4;
                    break;
                }
                break;
            case 4:
                s.pop_back();
                switch (*p) {
                case '/':
                    s += ' ';
                    mode = 0;
                    break;
                }
                break;
            case 5:
                switch (*p) {
                case '\'':
                    mode = 0;
                    break;
                case '\\':
                    mode = 7;
                    break;
                }
            case 6:
                switch (*p) {
                case '\"':
                    mode = 0;
                    break;
                case '\\':
                    mode = 8;
                    break;
                }
            case 7:
                mode = 5;
                break;
            case 8:
                mode = 6;
                break;
			}
		}
        if (fin.bad()) {
            throw fail_to_read_src_error();
        }
	}

	bool valid[256];
	std::fill(valid, valid + 256, false);
    std::fill(valid + 'a', valid + 'z' + 1, true);
    std::fill(valid + 'A', valid + 'Z' + 1, true);
    std::fill(valid + '0', valid + '9' + 1, true);
	valid['.'] = true;
	valid['_'] = true;

	std::vector<std::string> tokens;
	for (size_t p = 0, np = 0; p < s.length(); p = np) {
		while (np < s.length() && valid[(unsigned char)s[np]]) {
			np++;
		}
		if (np == p) {
			np++;
		} else {
			tokens.push_back(s.substr(p, np - p));
		}
	}
	if (tokens.size() > 0 && tokens[0] == "package") {
        throw compile_error("Please don't specify the package.");
	}

	for (size_t i = 0; i + 1 < tokens.size(); i++) {
		if (tokens[i] == "class") {
            std::string name = tokens[i + 1];
			if (name.length() > 100) {
                throw compile_error("The name of the main class is too long.");
            }
            for (size_t k = 0; k < name.length(); k++) {
                if (!isalnum(name[k]) && name[k] != '_') {
                    throw compile_error("The name of the main class should only contain letters, numbers and underscore sign.");
                }
            }
            if (!isalpha(name[0])) {
                throw compile_error("The name of the main class cannot begin with a number.");
            }
            return tokens[i + 1];
		}
	}

    throw compile_error("Cannot find the main class.");
}

int compile_cpp(const compile_config &conf, const std::string &std) {
    std::ostringstream sflags;
    spaced_out(sflags, "-lm", "-O2", "-DONLINE_JUDGE", "-std=" + std);
    for (auto dir : conf.cinclude_dirs) {
        add_spaced_out(sflags, "-I" + dir);
    }

    if (conf.implementer.empty()) {
        return execute(
            UOJ_GPLUSPLUS, sflags.str(), "-o", conf.name,
            "-x", "c++", conf.src
        );
    } else {
        return execute(
            UOJ_GPLUSPLUS, sflags.str(), "-o", conf.name,
            conf.implementer + ".cpp", "-x", "c++", conf.src
        );
    }
}
int compile_c(const compile_config &conf) {
    std::ostringstream sflags;
    spaced_out(sflags, "-lm", "-O2", "-DONLINE_JUDGE");
    for (auto dir : conf.cinclude_dirs) {
        add_spaced_out(sflags, "-I" + dir);
    }

    if (conf.implementer.empty()) {
        return execute(
            UOJ_GCC, sflags.str(), "-o", conf.name,
            "-x", "c", conf.src
        );
    } else {
        return execute(
            UOJ_GCC, sflags.str(), "-o", conf.name,
            conf.implementer + ".c", "-x", "c", conf.src
        );
    }
}
int compile_python2_7(const compile_config &conf) {
    if (!conf.implementer.empty()) {
        throw language_not_supported_error();
    }

    std::string dfile = "__pycode__/" + conf.name + ".py";
	std::string compiler_code =
		"import py_compile\n"
		"import sys\n"
		"try:\n"
		"    py_compile.compile('" + conf.src + "', cfile='" + conf.name + "', dfile='" + dfile + "', doraise=True)\n"
		"    sys.exit(0)\n"
		"except Exception as e:\n"
		"    print e\n"
		"    sys.exit(1)\n";
	return execute(UOJ_PYTHON2_7, "-E", "-s", "-B", "-O", "-c", escapeshellarg(compiler_code));
}
int compile_python3(const compile_config &conf) {
    if (!conf.implementer.empty()) {
        throw language_not_supported_error();
    }
    std::string dfile = "__pycode__/" + conf.name + ".py";
	std::string compiler_code =
		"import py_compile\n"
		"import sys\n"
		"try:\n"
		"    py_compile.compile('" + conf.src + "', cfile='" + conf.name + "', dfile='" + dfile + "', doraise=True)\n"
		"    sys.exit(0)\n"
		"except Exception as e:\n"
		"    print(e)\n"
		"    sys.exit(1)\n";
	return execute(UOJ_PYTHON3, "-I", "-B", "-O", "-c", escapeshellarg(compiler_code));
}
int compile_java(const compile_config &conf, const std::string &jdk) {
    if (!conf.implementer.empty()) {
        throw language_not_supported_error();
    }

    try {
        std::string main_class = get_java_main_class(conf.src);
        fs::remove_all(conf.name);
        fs::create_directory(conf.name);
        fs::copy_file(conf.src, fs::path(conf.name) / (main_class + ".java"));
        int ret = execute(
            "cd", conf.name,
            "&&", jdk + "/bin/javac", main_class + ".java"
        );
        fs::remove(fs::path(conf.name) / (main_class + ".java"));
        put_class_name_to_file(fs::path(conf.name) / ".main_class_name", main_class);
        return ret;
    } catch (std::system_error &e) {
        throw compile_error("System Error");
    }
}
int compile_pas(const compile_config &conf) {
    if (conf.implementer.empty()) {
        return execute(UOJ_FPC, conf.src, "-O2");
    } else {
        try {
            std::string unit_name = get_class_name_from_file(conf.name + ".unit_name");
            if (!unit_name.empty()) {
                fs::copy_file(conf.src, unit_name + ".pas");
            }
            int ret = execute(UOJ_FPC, conf.implementer + ".pas", "-o" + conf.name, "-O2");
            if (!unit_name.empty()) {
                fs::remove(unit_name + ".pas");
            }
            return ret;
        } catch (std::system_error &e) {
            throw compile_error("System Error");
        }
    }
}

int compile(const compile_config &conf) {
	if ((conf.lang.length() > 0 && conf.lang[0] == 'C') && has_illegal_keywords_in_file(conf.src)) {
        std::cerr << "Compile Failed: assembly language detected" << std::endl;
        return 1;
	}

    if (conf.lang == "C++" || conf.lang == "C++03") {
        return compile_cpp(conf, "c++03");
    } else if (conf.lang == "C++11") {
        return compile_cpp(conf, "c++11");
    } else if (conf.lang == "C++14") {
        return compile_cpp(conf, "c++14");
    } else if (conf.lang == "C++17") {
        return compile_cpp(conf, "c++17");
    } else if (conf.lang == "C++20") {
        return compile_cpp(conf, "c++20");
    } else if (conf.lang == "C") {
        return compile_c(conf);
    } else if (conf.lang == "Python2.7") {
        return compile_python2_7(conf);
    } else if (conf.lang == "Python3") {
        return compile_python3(conf);
    } else if (conf.lang == "Java7") {
        return compile_java(conf, conf.custom_compiler_path + "/" + UOJ_JDK7);
    } else if (conf.lang == "Java8") {
        return compile_java(conf, conf.custom_compiler_path + "/" + UOJ_JDK8);
    } else if (conf.lang == "Java11") {
        return compile_java(conf, UOJ_OPEN_JDK11);
    } else if (conf.lang == "Java14") {
        return compile_java(conf, UOJ_OPEN_JDK14);
    } else if (conf.lang == "Pascal") {
        return compile_pas(conf);
    } else {
        throw language_not_supported_error();
    }
}

int main(int argc, char **argv) {
    try {
        return compile(parse_args(argc, argv));
    } catch (std::exception &e) {
        std::cerr << e.what() << std::endl;
        return 1;
    }
}

/* old code */
/*
RunCompilerResult compile_c(const string &name, const string &path = work_path) {
	return run_compiler(
		path,
		"/usr/bin/gcc-4.8", "-o", name, "-x", "c", name + ".code",
		"-lm", "-O2", "-DONLINE_JUDGE"
	);
}
RunCompilerResult compile_pas(const string &name, const string &path = work_path) {
	return run_compiler(
		path,
		"/usr/bin/fpc-2.6.2", name + ".code",
		"-O2"
	);
}
RunCompilerResult compile_cpp(const string &name, const string &path = work_path) {
	return run_compiler(
		path,
		"/usr/bin/g++-4.8", "-o", name, "-x", "c++", name + ".code",
		"-lm", "-O2", "-DONLINE_JUDGE"
	);
}
RunCompilerResult compile_cpp11(const string &name, const string &path = work_path) {
	return run_compiler(
		path,
		"/usr/bin/g++-4.8", "-o", name, "-x", "c++", name + ".code",
		"-lm", "-O2", "-DONLINE_JUDGE", "-std=c++11"
	);
}
RunCompilerResult compile_python2_7(const string &name, const string &path = work_path) {
	string compiler_code =
		"import py_compile\n"
		"import sys\n"
		"try:\n"
		"    py_compile.compile('" + name + ".code', '" + name + "', doraise=True)\n"
		"    sys.exit(0)\n"
		"except Exception as e:\n"
		"    print e\n"
		"    sys.exit(1)\n";

	return run_compiler(
		path,
		"/usr/bin/python2.7", "-E", "-s", "-B", "-O", "-c", compiler_code
	);
}
RunCompilerResult compile_python3(const string &name, const string &path = work_path) {
	string compiler_code =
		"import py_compile\n"
		"import sys\n"
		"try:\n"
		"    py_compile.compile('" + name + ".code', '" + name + "', doraise=True)\n"
		"    sys.exit(0)\n"
		"except Exception as e:\n"
		"    print e\n"
		"    sys.exit(1)\n";

	return run_compiler(
		path,
		"/usr/bin/python3.4", "-I", "-B", "-O", "-c", compiler_code
	);
}
RunCompilerResult compile_java7(const string &name, const string &path = work_path) {
	RunCompilerResult ret = prepare_java_source(name, path);
	if (!ret.succeeded)
		return ret;

	string main_class = conf_str(name + "_main_class");

	executef("rm %s/%s -rf 2>/dev/null; mkdir %s/%s", path.c_str(), name.c_str(), path.c_str(), name.c_str());
	executef("echo package %s\\; | cat - %s/%s.code >%s/%s/%s.java", name.c_str(), path.c_str(), name.c_str(), path.c_str(), name.c_str(), main_class.c_str());

	return run_compiler(
		path + "/" + name,
		main_path + "/run/runtime/jdk1.7.0_76/bin/javac", main_class + ".java"
	);
}
RunCompilerResult compile_java8(const string &name, const string &path = work_path) {
	RunCompilerResult ret = prepare_java_source(name, path);
	if (!ret.succeeded)
		return ret;

	string main_class = conf_str(name + "_main_class");

	executef("rm %s/%s -rf 2>/dev/null; mkdir %s/%s", path.c_str(), name.c_str(), path.c_str(), name.c_str());
	executef("echo package %s\\; | cat - %s/%s.code >%s/%s/%s.java", name.c_str(), path.c_str(), name.c_str(), path.c_str(), name.c_str(), main_class.c_str());

	return run_compiler(
		path + "/" + name,
		main_path + "/run/runtime/jdk1.8.0_31/bin/javac", main_class + ".java"
	);
}

RunCompilerResult compile(const char *name)  {
	string lang = conf_str(string(name) + "_language");

	if ((lang == "C++" || lang == "C++11" || lang == "C") && has_illegal_keywords_in_file(work_path + "/" + name + ".code"))
	{
		RunCompilerResult res;
		res.type = RS_DGS;
		res.ust = -1;
		res.usm = -1;
		res.succeeded = false;
		res.info = "Compile Failed";
		return res;
	}

	if (lang == "C++") {
		return compile_cpp(name);
	}
	if (lang == "C++11") {
		return compile_cpp11(name);
	}
	if (lang == "Python2.7") {
		return compile_python2_7(name);
	}
	if (lang == "Python3") {
		return compile_python3(name);
	}
	if (lang == "Java7") {
		return compile_java7(name);
	}
	if (lang == "Java8") {
		return compile_java8(name);
	}
	if (lang == "C") {
		return compile_c(name);
	}
	if (lang == "Pascal") {
		return compile_pas(name);
	}

	RunCompilerResult res = RunCompilerResult::failed_result();
	res.info = "This language is not supported yet.";
	return res;
}

RunCompilerResult compile_c_with_implementer(const string &name, const string &path = work_path, const string &implementer="implementer") {
	return run_compiler(
		path, 
		"/usr/bin/gcc-4.8", "-o", name,
		implementer + ".c",
		"-x", "c", name + ".code",
		"-lm", "-O2", "-DONLINE_JUDGE"
	);
}
RunCompilerResult compile_pas_with_implementer(const string &name, const string &path = work_path, const string &implementer="implementer") {
	executef("cp %s %s", (path + "/" + name + ".code").c_str(), (path + "/" + conf_str(name + "_unit_name") + ".pas").c_str());
	return run_compiler(
		path.c_str(),
		"/usr/bin/fpc-2.6.2", implementer + ".pas", "-o" + name,
		"-O2"
	);
}
RunCompilerResult compile_cpp_with_implementer(const string &name, const string &path = work_path, const string &implementer="implementer") {
	return run_compiler(
		path.c_str(),
		"/usr/bin/g++-4.8", "-o", name,
		implementer + ".cpp",
		"-x", "c++", name + ".code",
		"-lm", "-O2", "-DONLINE_JUDGE"
	);
}
RunCompilerResult compile_cpp11_with_implementer(const string &name, const string &path = work_path, const string &implementer="implementer") {
	return run_compiler(
		path.c_str(),
		"/usr/bin/g++-4.8", "-o", name,
		implementer + ".cpp",
		"-x", "c++", name + ".code",
		"-lm", "-O2", "-DONLINE_JUDGE", "-std=c++11"
	);
}

RunCompilerResult compile_with_implementer(const char *name, const string &implementer="implementer")  {
	string lang = conf_str(string(name) + "_language");

	if (has_illegal_keywords_in_file(work_path + "/" + name + ".code")) {
		RunCompilerResult res;
		res.type = RS_DGS;
		res.ust = -1;
		res.usm = -1;
		res.succeeded = false;
		res.info = "Compile Failed";
		return res;
	}

	if (lang == "C++") {
		return compile_cpp_with_implementer(name, work_path, implementer);
	}
	if (lang == "C++11") {
		return compile_cpp11_with_implementer(name, work_path, implementer);
	}
	if (lang == "C") {
		return compile_c_with_implementer(name, work_path, implementer);
	}
	if (lang == "Pascal") {
		return compile_pas_with_implementer(name, work_path, implementer);
	}

	RunCompilerResult res = RunCompilerResult::failed_result();
	res.info = "This language is not supported yet.";
	return res;
}
*/