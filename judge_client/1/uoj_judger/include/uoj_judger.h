#include <iostream>
#include <algorithm>
#include <sstream>
#include <fstream>
#include <vector>
#include <set>
#include <map>
#include <cstdio>
#include <cstdlib>
#include <climits>
#include <cmath>
#include <cstdlib>
#include <ctime>
#include <cstring>
#include <string>
#include <cstdarg>

#include <unistd.h>
#include <sys/file.h>
#include <sys/wait.h>

#include "uoj_env.h"
using namespace std;

/*========================== execute ====================== */

string escapeshellarg(const string &arg) {
	string res = "'";
	for (size_t i = 0; i < arg.size(); i++) {
		if (arg[i] == '\'') {
			res += "'\\''";
		} else {
			res += arg[i];
		}
	}
	res += "'";
	return res;
}

string realpath(const string &path) {
	char real[PATH_MAX + 1];
	if (realpath(path.c_str(), real) == NULL) {
		return "";
	}
	return real;
}


int execute(const char *cmd) {
	return system(cmd);
}

int executef(const char *fmt, ...) {
	const int MaxL = 512;
	char cmd[MaxL];
	va_list ap;
	va_start(ap, fmt);
	int res = vsnprintf(cmd, MaxL, fmt, ap);
	if (res < 0 || res >= MaxL) {
		return -1;
	}
	res = execute(cmd);
	va_end(ap);
	return res;
}

/*======================== execute End ==================== */

/*========================= file ====================== */

string file_preview(const string &name, const size_t &len = 100) {
	FILE *f = fopen(name.c_str(), "r");
	if (f == NULL) {
		return "";
	}
	string res = "";
	int c;
	while (c = fgetc(f), c != EOF && res.size() < len + 4) {
		res += c;
	}
	if (res.size() > len + 3) {
		res.resize(len);
		res += "...";
	}
	fclose(f);
	return res;
}
void file_hide_token(const string &name, const string &token) {
	executef("cp %s %s.bak", name.c_str(), name.c_str());

	FILE *rf = fopen((name + ".bak").c_str(), "r");
	FILE *wf = fopen(name.c_str(), "w");
	int c;
	for (int i = 0; i <= (int)token.length(); i++)
	{
		c = fgetc(rf);
		if (c != (i < (int)token.length() ? token[i] : '\n'))
		{
			fprintf(wf, "Unauthorized output\n");
			fclose(rf);
			fclose(wf);
			return;
		}
	}
	while (c = fgetc(rf), c != EOF) {
		fputc(c, wf);
	}
	fclose(rf);
	fclose(wf);
}

/*======================= file End ==================== */

/*====================== parameter ==================== */

struct RunLimit {
	int time;
	int real_time;
	int memory;
	int output;

	RunLimit() {
	}
	RunLimit(const int &_time, const int &_memory, const int &_output)
			: time(_time), memory(_memory), output(_output), real_time(-1) {
	}
};

const RunLimit RL_DEFAULT = RunLimit(1, 256, 64);
const RunLimit RL_JUDGER_DEFAULT = RunLimit(600, 1024, 128);
const RunLimit RL_CHECKER_DEFAULT = RunLimit(5, 256, 64);
const RunLimit RL_INTERACTOR_DEFAULT = RunLimit(1, 256, 64);
const RunLimit RL_VALIDATOR_DEFAULT = RunLimit(5, 256, 64);
const RunLimit RL_MARKER_DEFAULT = RunLimit(5, 256, 64);
const RunLimit RL_COMPILER_DEFAULT = RunLimit(15, 512, 64);

struct PointInfo  {
	int num;
	int scr;
	int ust, usm;
	string info, in, out, res;

	PointInfo(const int &_num, const int &_scr,
			const int &_ust, const int &_usm, const string &_info,
			const string &_in, const string &_out, const string &_res)
			: num(_num), scr(_scr),
			ust(_ust), usm(_usm), info(_info),
			in(_in), out(_out), res(_res) {
		if (info == "default") {
			if (scr == 0) {
				info = "Wrong Answer";
			} else if (scr == 100) {
				info = "Accepted";
			} else {
				info = "Acceptable Answer";
			}
		}
	}
};

struct CustomTestInfo  {
	int ust, usm;
	string info, exp, out;

	CustomTestInfo(const int &_ust, const int &_usm, const string &_info,
			const string &_exp, const string &_out)
			: ust(_ust), usm(_usm), info(_info),
			exp(_exp), out(_out) {
	}
};

struct RunResult {
	int type;
	int ust, usm;
	int exit_code;

	static RunResult failed_result() {
		RunResult res;
		res.type = RS_JGF;
		res.ust = -1;
		res.usm = -1;
		return res;
	}

	static RunResult from_file(const string &file_name) {
		RunResult res;
		FILE *fres = fopen(file_name.c_str(), "r");
		if (fres == NULL || fscanf(fres, "%d %d %d %d", &res.type, &res.ust, &res.usm, &res.exit_code) != 4) {
			return RunResult::failed_result();
		}
		fclose(fres);
		return res;
	}
};
struct RunCheckerResult {
	int type;
	int ust, usm;
	int scr;
	string info;

	static RunCheckerResult from_file(const string &file_name, const RunResult &rres) {
		RunCheckerResult res;
		res.type = rres.type;
		res.ust = rres.ust;
		res.usm = rres.usm;

		if (rres.type != RS_AC) {
			res.scr = 0;
		} else {
			FILE *fres = fopen(file_name.c_str(), "r");
			char type[21];
			if (fres == NULL || fscanf(fres, "%20s", type) != 1) {
				return RunCheckerResult::failed_result();
			}
			if (strcmp(type, "ok") == 0) {
				res.scr = 100;
			} else if (strcmp(type, "points") == 0) {
				double d;
				if (fscanf(fres, "%lf", &d) != 1) {
					return RunCheckerResult::failed_result();
				} else {
					res.scr = (int)floor(100 * d + 0.5);
				}
			} else {
				res.scr = 0;
			}
			fclose(fres);
		}
		res.info = file_preview(file_name);
		return res;
	}
	
	static RunCheckerResult failed_result() {
		RunCheckerResult res;
		res.type = RS_JGF;
		res.ust = -1;
		res.usm = -1;
		res.scr = 0;
		res.info = "Checker Judgment Failed";
		return res;
	}
};
struct RunValidatorResult {
	int type;
	int ust, usm;
	bool succeeded;
	string info;
	
	static RunValidatorResult failed_result() {
		RunValidatorResult res;
		res.type = RS_JGF;
		res.ust = -1;
		res.usm = -1;
		res.succeeded = 0;
		res.info = "Validator Judgment Failed";
		return res;
	}
};
struct RunCompilerResult {
	int type;
	int ust, usm;
	bool succeeded;
	string info;

	static RunCompilerResult failed_result() {
		RunCompilerResult res;
		res.type = RS_JGF;
		res.ust = -1;
		res.usm = -1;
		res.succeeded = false;
		res.info = "Compile Failed";
		return res;
	}
};

// see also: run_simple_interaction
struct RunSimpleInteractionResult {
	RunResult res; // prog
	RunCheckerResult ires; // interactor
};

int problem_id;
string main_path;
string work_path;
string data_path;
string result_path;

int tot_time   = 0;
int max_memory = 0;
int tot_score  = 0;
ostringstream details_out;
//vector<PointInfo> points_info;
map<string, string> config;

/*==================== parameter End ================== */

/*====================== info print =================== */

template <class T>
inline string vtos(const T &v) {
	ostringstream sout;
	sout << v;
	return sout.str();
}

inline string htmlspecialchars(const string &s) {
	string r;
	for (int i = 0; i < (int)s.length(); i++) {
		switch (s[i]) {
		case '&' : r += "&amp;"; break;
		case '<' : r += "&lt;"; break;
		case '>' : r += "&gt;"; break;
		case '"' : r += "&quot;"; break;
		case '\0': r += "<b>\\0</b>"; break;
		default  : r += s[i]; break;
		}
	}
	return r;
}

inline string info_str(int id)  {
	switch (id) {
	case RS_MLE: return "Memory Limit Exceeded";
	case RS_TLE: return "Time Limit Exceeded";
	case RS_OLE: return "Output Limit Exceeded";
	case RS_RE : return "Runtime Error";
	case RS_DGS: return "Dangerous Syscalls";
	case RS_JGF: return "Judgment Failed";
	default    : return "Unknown Result";
	}
}
inline string info_str(const RunResult &p)  {
	return info_str(p.type);
}

void add_point_info(const PointInfo &info) {
	if (info.num >= 0) {
		if(info.ust >= 0) {
			tot_time += info.ust;
		}
		if(info.usm >= 0) {
			max_memory = max(max_memory, info.usm);
		}
	}
	tot_score += info.scr;

	details_out << "<test num=\"" << info.num << "\""
		<< " score=\"" << info.scr << "\""
		<< " info=\"" << htmlspecialchars(info.info) << "\""
		<< " time=\"" << info.ust << "\""
		<< " memory=\"" << info.usm << "\">" << endl;
	details_out << "<in>" << htmlspecialchars(info.in) << "</in>" << endl;
	details_out << "<out>" << htmlspecialchars(info.out) << "</out>" << endl;
	details_out << "<res>" << htmlspecialchars(info.res) << "</res>" << endl;
	details_out << "</test>" << endl;
}
void add_custom_test_info(const CustomTestInfo &info) {
	if(info.ust >= 0) {
		tot_time += info.ust;
	}
	if(info.usm >= 0) {
		max_memory = max(max_memory, info.usm);
	}

	details_out << "<custom-test info=\"" << htmlspecialchars(info.info) << "\""
		<< " time=\"" << info.ust << "\""
		<< " memory=\"" << info.usm << "\">" << endl;
	if (!info.exp.empty()) {
		details_out << info.exp << endl;
	}
	details_out << "<out>" << htmlspecialchars(info.out) << "</out>" << endl;
	details_out << "</custom-test>" << endl;
}
void add_subtask_info(const int &num, const int &scr, const string &info, const vector<PointInfo> &points) {
	details_out << "<subtask num=\"" << num << "\""
		<< " score=\"" << scr << "\""
		<< " info=\"" << htmlspecialchars(info) << "\">" << endl;
	for (vector<PointInfo>::const_iterator it = points.begin(); it != points.end(); it++) {
		add_point_info(*it);
	}
	details_out << "</subtask>" << endl;
}
void end_judge_ok() {
	FILE *fres = fopen((result_path + "/result.txt").c_str(), "w");
	fprintf(fres, "score %d\n", tot_score);
	fprintf(fres, "time %d\n", tot_time);
	fprintf(fres, "memory %d\n", max_memory);
	fprintf(fres, "details\n");
	fprintf(fres, "<tests>\n");
	fprintf(fres, "%s", details_out.str().c_str());
	fprintf(fres, "</tests>\n");
	fclose(fres);
	exit(0);
}
void end_judge_judgement_failed(const string &info) {
	FILE *fres = fopen((result_path + "/result.txt").c_str(), "w");
	fprintf(fres, "error Judgment Failed\n");
	fprintf(fres, "details\n");
	fprintf(fres, "<error>%s</error>\n", htmlspecialchars(info).c_str());
	fclose(fres);
	exit(1);
}
void end_judge_compile_error(const RunCompilerResult &res) {
	FILE *fres = fopen((result_path + "/result.txt").c_str(), "w");
	fprintf(fres, "error Compile Error\n");
	fprintf(fres, "details\n");
	fprintf(fres, "<error>%s</error>\n", htmlspecialchars(res.info).c_str());
	fclose(fres);
	exit(0);
}

void report_judge_status(const char *status) {
	FILE *f = fopen((result_path + "/cur_status.txt").c_str(), "a");
	if (f == NULL) {
		return;
	}
	if (flock(fileno(f), LOCK_EX) != -1) {
		if (ftruncate(fileno(f), 0) != -1) {
			fprintf(f, "%s\n", status);
			fflush(f);
		}
		flock(fileno(f), LOCK_UN);
	}
	fclose(f);
}
bool report_judge_status_f(const char *fmt, ...) {
	const int MaxL = 512;
	char status[MaxL];
	va_list ap;
	va_start(ap, fmt);
	int res = vsnprintf(status, MaxL, fmt, ap);
	if (res < 0 || res >= MaxL) {
		return false;
	}
	report_judge_status(status);
	va_end(ap);
	return true;
}

/*==================== info print End ================= */

/*====================== config set =================== */

void print_config() {
	for (map<string, string>::iterator it = config.begin(); it != config.end(); ++it) {
		cerr << it->first << " = " << it->second << endl;
	}
}
void load_config(const string &filename) {
	ifstream fin(filename.c_str());
	if (!fin) {
		return;
	}
	string key;
	string val;
	while (fin >> key >> val) {
		config[key] = val;
	}
}
string conf_str(const string &key, int num, const string &val) {
	ostringstream sout;
	sout << key << "_" << num;
	if (config.count(sout.str()) == 0) {
		return val;
	}
	return config[sout.str()];
}
string conf_str(const string &key, const string &val) {
	if (config.count(key) == 0) {
		return val;
	}
	return config[key];
}
string conf_str(const string &key) {
	return conf_str(key, "");
}
int conf_int(const string &key, const int &val) {
	if (config.count(key) == 0) {
		return val;
	}
	return atoi(config[key].c_str());
}
int conf_int(const string &key, int num, const int &val) {
	ostringstream sout;
	sout << key << "_" << num;
	if (config.count(sout.str()) == 0) {
		return conf_int(key, val);
	}
	return atoi(config[sout.str()].c_str());
}
int conf_int(const string &key)  {
	return conf_int(key, 0);
}
string conf_input_file_name(int num) {
	ostringstream name;
	if (num < 0) {
		name << "ex_";
	}
	name << conf_str("input_pre", "input") << abs(num) << "." << conf_str("input_suf", "txt");
	return name.str();
}
string conf_output_file_name(int num) {
	ostringstream name;
	if (num < 0) {
		name << "ex_";
	}
	name << conf_str("output_pre", "output") << abs(num) << "." << conf_str("output_suf", "txt");
	return name.str();
}
RunLimit conf_run_limit(string pre, const int &num, const RunLimit &val) {
	if (!pre.empty()) {
		pre += "_";
	}
	RunLimit limit;
	limit.time = conf_int(pre + "time_limit", num, val.time);
	limit.memory = conf_int(pre + "memory_limit", num, val.memory);
	limit.output = conf_int(pre + "output_limit", num, val.output);
	return limit;
}
RunLimit conf_run_limit(const int &num, const RunLimit &val) {
	return conf_run_limit("", num, val);
}
void conf_add(const string &key, const string &val)  {
	if (config.count(key))  return;
	config[key] = val;
}
bool conf_has(const string &key)  {
	return config.count(key) != 0;
}
bool conf_is(const string &key, const string &val)  {
	if (config.count(key) == 0)  return false;
	return config[key] == val;
}

/*==================== config set End ================= */

/*========================== run ====================== */

struct RunProgramConfig {
	vector<string> readable_file_names; // other than stdin
	string result_file_name;
	string input_file_name;
	string output_file_name;
	string error_file_name;
	string type;
	string work_path;
	RunLimit limit;
	vector<string> argv;

	RunProgramConfig() {
		int p = 1;
		while (conf_str("readable", p, "") != "") {
			readable_file_names.push_back(conf_str("readable", p, ""));
			p++;
		}
		result_file_name = result_path + "/run_program_result.txt";
		type = "default";
		work_path = ::work_path;

		for (vector<string>::iterator it = readable_file_names.begin(); it != readable_file_names.end(); it++) {
			if (!it->empty() && (*it)[0] != '/') {
				*it = ::work_path + "/" + *it;
			}
		}
	}

	void set_argv(const char *program_name, ...) {
		argv.clear();
		argv.push_back(program_name);
		va_list vl;
		va_start(vl, program_name);
		for (const char *arg = va_arg(vl, const char *); arg; arg = va_arg(vl, const char *)) {
			argv.push_back(arg);
		}
		va_end(vl);
	}

	void set_submission_program_name(string name) {
		string lang = conf_str(string(name) + "_language");
		type = "default";
		string program_name = name;
		if (lang == "Python2.7") {
			type = "python2.7";
		} else if (lang == "Python3") {
			type = "python3.4";
		} else if (lang == "Java7") {
			program_name += "." + conf_str(name + "_main_class");
			type = "java7u76";
		} else if (lang == "Java8") {
			program_name += "." + conf_str(name + "_main_class");
			type = "java8u31";
		}

		set_argv(program_name.c_str(), NULL);
	}

	string get_cmd() const {
		ostringstream sout;
		sout << main_path << "/run/run_program"
			<< " " << "--res=" << escapeshellarg(result_file_name)
			<< " " << "--in=" << escapeshellarg(input_file_name)
			<< " " <<"--out=" << escapeshellarg(output_file_name)
			<< " " << "--err=" << escapeshellarg(error_file_name)
			<< " " << "--tl=" << limit.time
			<< " " << "--ml=" << limit.memory
			<< " " << "--ol=" << limit.output
			<< " " << "--type=" << type
			<< " " << "--work-path=" << work_path
			/*<< " " << "--show-trace-details"*/;
		for (vector<string>::const_iterator it = readable_file_names.begin(); it != readable_file_names.end(); it++) {
			sout << " " << "--add-readable=" << escapeshellarg(*it);
		}
		for (vector<string>::const_iterator it = argv.begin(); it != argv.end(); it++) {
			sout << " " << escapeshellarg(*it);
		}
		return sout.str();
	}
};

struct PipeConfig {
	int from, to;
	int from_fd, to_fd;

	string saving_file_name;

	PipeConfig() {
	}
	PipeConfig(int _from, int _from_fd, int _to, int _to_fd, const string &_saving_file_name = "")
			: from(_from), from_fd(_from_fd), to(_to), to_fd(_to_fd), saving_file_name(_saving_file_name) {
	}
};
struct RunInteractionConfig {
	vector<string> cmds;
	vector<PipeConfig> pipes;

	string get_cmd() const {
		ostringstream sout;
		sout << main_path << "/run/run_interaction";
		for (int i = 0; i < (int)cmds.size(); i++) {
			sout << " " << escapeshellarg(cmds[i]);
		}
		for (int i = 0; i < (int)pipes.size(); i++) {
			sout << " " << "-p";
			sout << " " << pipes[i].from << ":" << pipes[i].from_fd;
			sout << "-" << pipes[i].to << ":" << pipes[i].to_fd;

			if (!pipes[i].saving_file_name.empty()) {
				sout << " " << "-s";
				sout << " " << escapeshellarg(pipes[i].saving_file_name);
			}
		}
		return sout.str();
	}
};

// @deprecated
// will be removed in the future
RunResult vrun_program(
		const char *run_program_result_file_name,
		const char *input_file_name,
		const char *output_file_name,
		const char *error_file_name,
		const RunLimit &limit,
		const vector<string> &rest) {
	ostringstream sout;
	sout << main_path << "/run/run_program"
		<< " " << "--res=" << escapeshellarg(run_program_result_file_name)
		<< " " << "--in=" << escapeshellarg(input_file_name)
		<< " " <<"--out=" << escapeshellarg(output_file_name)
		<< " " << "--err=" << escapeshellarg(error_file_name)
		<< " " << "--tl=" << limit.time
		<< " " << "--ml=" << limit.memory
		<< " " << "--ol=" << limit.output
		/*<< " " << "--show-trace-details"*/;
	for (vector<string>::const_iterator it = rest.begin(); it != rest.end(); it++) {
		sout << " " << escapeshellarg(*it);
	}

	if (execute(sout.str().c_str()) != 0) {
		return RunResult::failed_result();
	}
	return RunResult::from_file(run_program_result_file_name);
}

RunResult run_program(const RunProgramConfig &rpc) {
	if (execute(rpc.get_cmd().c_str()) != 0) {
		return RunResult::failed_result();
	}
	return RunResult::from_file(rpc.result_file_name);
}

// @return interaction return value
int run_interaction(const RunInteractionConfig &ric) {
	return execute(ric.get_cmd().c_str());
}

RunResult run_program(
		const char *run_program_result_file_name,
		const char *input_file_name,
		const char *output_file_name,
		const char *error_file_name,
		const RunLimit &limit, ...) {
	vector<string> argv;
	va_list vl;
	va_start(vl, limit);
	for (const char *arg = va_arg(vl, const char *); arg; arg = va_arg(vl, const char *)) {
		argv.push_back(arg);
	}
	va_end(vl);
	return vrun_program(run_program_result_file_name,
			input_file_name,
			output_file_name,
			error_file_name,
			limit,
			argv);
}

RunValidatorResult run_validator(
		const string &input_file_name,
		const RunLimit &limit,
		const string &program_name) {
	RunResult ret = run_program(
			(string(result_path) + "/run_validator_result.txt").c_str(),
			input_file_name.c_str(),
			"/dev/null",
			(string(result_path) + "/validator_error.txt").c_str(),
			limit,
			program_name.c_str(),
			NULL);

	RunValidatorResult res;
	res.type = ret.type;
	res.ust = ret.ust;
	res.usm = ret.usm;

	if (ret.type != RS_AC || ret.exit_code != 0) {
		res.succeeded = false;
		res.info = file_preview(result_path + "/validator_error.txt");
	} else {
		res.succeeded = true;
	}
	return res;
}
RunCheckerResult run_checker(
		const RunLimit &limit,
		const string &program_name,
		const string &input_file_name,
		const string &output_file_name,
		const string &answer_file_name) {
	RunResult ret = run_program(
			(string(result_path) + "/run_checker_result.txt").c_str(),
			"/dev/null",
			"/dev/null",
			(string(result_path) + "/checker_error.txt").c_str(),
			limit,
			("--add-readable=" + input_file_name).c_str(),
			("--add-readable=" + output_file_name).c_str(),
			("--add-readable=" + answer_file_name).c_str(),
			program_name.c_str(),
			realpath(input_file_name).c_str(),
			realpath(output_file_name).c_str(),
			realpath(answer_file_name).c_str(),
			NULL);

	return RunCheckerResult::from_file(result_path + "/checker_error.txt", ret);
}

RunCompilerResult run_compiler(const char *path, ...) {
	vector<string> argv;
	argv.push_back("--type=compiler");
	argv.push_back(string("--work-path=") + path);
	va_list vl;
	va_start(vl, path);
	for (const char *arg = va_arg(vl, const char *); arg; arg = va_arg(vl, const char *)) {
		argv.push_back(arg);
	}
	va_end(vl);

	RunResult ret = vrun_program(
			(result_path + "/run_compiler_result.txt").c_str(),
			"/dev/null",
			"stderr",
			(result_path + "/compiler_result.txt").c_str(),
			RL_COMPILER_DEFAULT,
			argv);
	RunCompilerResult res;
	res.type = ret.type;
	res.ust = ret.ust;
	res.usm = ret.usm; 
	res.succeeded = ret.type == RS_AC && ret.exit_code == 0;
	if (!res.succeeded) {
		if (ret.type == RS_AC) {
			res.info = file_preview(result_path + "/compiler_result.txt", 500);
		} else if (ret.type == RS_JGF) {
			res.info = "No Comment";
		} else {
			res.info = "Compiler " + info_str(ret.type);
		}
	}
	return res;
}

RunResult run_submission_program(
		const string &input_file_name,
		const string &output_file_name,
		const RunLimit &limit,
		const string &name,
		RunProgramConfig rpc = RunProgramConfig()) {
	rpc.result_file_name = result_path + "/run_submission_program.txt";
	rpc.input_file_name = input_file_name;
	rpc.output_file_name = output_file_name;
	rpc.error_file_name = "/dev/null";
	rpc.limit = limit;
	rpc.set_submission_program_name(name);

	RunResult res = run_program(rpc);
	if (res.type == RS_AC && res.exit_code != 0) {
		res.type = RS_RE;
	}
	return res;
}

void prepare_interactor() {
	static bool prepared = false;
	if (prepared) {
		return;
	}
	string data_path_std = data_path + "/interactor";
	string work_path_std = work_path + "/interactor";
	executef("cp %s %s", data_path_std.c_str(), work_path_std.c_str());
	conf_add("interactor_language", "C++");
	prepared = true;
}

// simple: prog <---> interactor <---> data
RunSimpleInteractionResult run_simple_interation(
		const string &input_file_name,
		const string &answer_file_name,
		const string &real_input_file_name,
		const string &real_output_file_name,
		const RunLimit &limit,
		const RunLimit &ilimit,
		const string &name,
		RunProgramConfig rpc = RunProgramConfig(),
		RunProgramConfig irpc = RunProgramConfig()) {
	prepare_interactor();

	rpc.result_file_name = result_path + "/run_submission_program.txt";
	rpc.input_file_name = "stdin";
	rpc.output_file_name = "stdout";
	rpc.error_file_name = "/dev/null";
	rpc.limit = limit;
	rpc.set_submission_program_name(name);

	irpc.result_file_name = result_path + "/run_interactor_program.txt";
	irpc.readable_file_names.push_back(input_file_name);
	irpc.readable_file_names.push_back(answer_file_name);
	irpc.input_file_name = "stdin";
	irpc.output_file_name = "stdout";
	irpc.error_file_name = result_path + "/interactor_error.txt";
	irpc.limit = ilimit;
	irpc.set_submission_program_name("interactor");
	irpc.argv.push_back(input_file_name);
	irpc.argv.push_back("/dev/stdin");
	irpc.argv.push_back(answer_file_name);

	irpc.limit.real_time = rpc.limit.real_time = rpc.limit.time + irpc.limit.time + 1;

	RunInteractionConfig ric;
	ric.cmds.push_back(rpc.get_cmd());
	ric.cmds.push_back(irpc.get_cmd());

	// from:fd, to:fd
	ric.pipes.push_back(PipeConfig(2, 1, 1, 0, real_input_file_name));
	ric.pipes.push_back(PipeConfig(1, 1, 2, 0, real_output_file_name));
	
	run_interaction(ric);

	RunSimpleInteractionResult rires;
	RunResult res = RunResult::from_file(rpc.result_file_name);
	RunCheckerResult ires = RunCheckerResult::from_file(irpc.error_file_name, RunResult::from_file(irpc.result_file_name));

	if (res.type == RS_AC && res.exit_code != 0) {
		res.type = RS_RE;
	}

	if (ires.type == RS_JGF) {
		ires.info = "Interactor Judgment Failed";
	}
	if (ires.type == RS_TLE) {
		ires.type = RS_AC;
		res.type = RS_TLE;
	}

	rires.res = res;
	rires.ires = ires;
	return rires;
}

void prepare_run_standard_program() {
	static bool prepared = false;
	if (prepared) {
		return;
	}
	string data_path_std = data_path + "/std";
	string work_path_std = work_path + "/std";
	executef("cp %s %s", data_path_std.c_str(), work_path_std.c_str());
	conf_add("std_language", "C++");
	prepared = true;
}

// @deprecated
// will be removed in the future
RunResult run_standard_program(
		const string &input_file_name,
		const string &output_file_name,
		const RunLimit &limit,
		RunProgramConfig rpc = RunProgramConfig()) {
	prepare_run_standard_program();
	rpc.result_file_name = result_path + "/run_standard_program.txt";
	return run_submission_program(
			input_file_name,
			output_file_name,
			limit,
			"std",
			rpc);
}

/*======================== run End ==================== */

/*======================== compile ==================== */

bool is_illegal_keyword(const string &name) {
	if (name == "__asm" || name == "__asm__" || name == "asm")
		return true;
	return false;
}

bool has_illegal_keywords_in_file(const string &name) {
	FILE *f = fopen(name.c_str(), "r");

	int c;
	string key;
	while ((c = fgetc(f)) != EOF)
	{
		if (('0' <= c && c <= '9') || ('a' <= c && c <= 'z') || ('A' <= c && c <= 'Z') || c == '_')
		{
			if (key.size() < 20)
				key += c;
			else
			{
				if (is_illegal_keyword(key))
					return true;
				key.erase(key.begin());
				key += c;
			}
		}
		else
		{
			if (is_illegal_keyword(key))
				return true;
			key.clear();
		}
	}
	if (is_illegal_keyword(key))
		return true;
	fclose(f);
	return false;
}

RunCompilerResult prepare_java_source(const string &name, const string &path = work_path) {
	FILE *f = fopen((path + "/" + name + ".code").c_str(), "r");

	const int L = 1024;

	std::string s;
	char buf[L + 1];

	int mode = 0;

	while (!feof(f)) {
		buf[fread(buf, 1, L, f)] = '\0';

		for (char *p = buf; *p; p++) {
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
							s.resize(s.length() - 2);
							break;
						case '*':
							mode = 3;
							s.resize(s.length() - 2);
							break;
					}
					break;
				case 2:
					s.resize(s.length() - 1);
					switch (*p) {
						case '\n':
							s += '\n';
							mode = 0;
							break;
					}
					break;
				case 3:
					s.resize(s.length() - 1);
					switch (*p) {
						case '*':
							mode = 4;
							break;
					}
					break;
				case 4:
					s.resize(s.length() - 1);
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
	}

	bool valid[256];
	fill(valid, valid + 256, false);
	for (int c = 'a'; c <= 'z'; c++)
		valid[c] = true;
	for (int c = 'A'; c <= 'Z'; c++)
		valid[c] = true;
	valid['.'] = true;
	valid['_'] = true;

	vector<string> tokens;
	for (int p = 0, np = 0; p < (int)s.length(); p = np) {
		while (np < (int)s.length() && valid[(unsigned char)s[np]]) {
			np++;
		}
		if (np == p) {
			np++;
		} else {
			tokens.push_back(s.substr(p, np - p));
		}
	}
	if (tokens.size() > 0 && tokens[0] == "package") {
		RunCompilerResult res;
		res.type = RS_WA;
		res.ust = -1;
		res.usm = -1;
		res.succeeded = false;
		res.info = "Please don't specify the package.";
		return res;
	}

	for (int i = 0; i + 1 < (int)tokens.size(); i++) {
		if (tokens[i] == "class") {
			if (tokens[i + 1].length() <= 100) {
				config[name + "_main_class"] = tokens[i + 1];

				RunCompilerResult res;
				res.type = RS_AC;
				res.ust = 0;
				res.usm = 0;
				res.succeeded = true;
				return res;
			} else {
				RunCompilerResult res;
				res.type = RS_WA;
				res.ust = -1;
				res.usm = -1;
				res.succeeded = false;
				res.info = "The name of the main class is too long.";
				return res;
			}
		}
	}

	RunCompilerResult res;
	res.type = RS_WA;
	res.ust = -1;
	res.usm = -1;
	res.succeeded = false;
	res.info = "Can't find the main class.";
	return res;
}

RunCompilerResult compile_c(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(), 
			"/usr/bin/gcc-4.8", "-o", name.c_str(), "-x", "c", (name + ".code").c_str(), "-lm", "-O2", "-DONLINE_JUDGE", NULL);
}
RunCompilerResult compile_pas(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/fpc-2.6.2", (name + ".code").c_str(), "-O2", NULL);
}
RunCompilerResult compile_cpp(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/g++-4.8", "-o", name.c_str(), "-x", "c++", (name + ".code").c_str(), "-lm", "-O2", "-DONLINE_JUDGE", NULL);
}
RunCompilerResult compile_cpp11(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/g++-4.8", "-o", name.c_str(), "-x", "c++", (name + ".code").c_str(), "-lm", "-O2", "-DONLINE_JUDGE", "-std=c++11", NULL);
}
RunCompilerResult compile_python2_7(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/python2.7", "-E", "-s", "-B", "-O", "-c",
			("import py_compile\nimport sys\ntry:\n    py_compile.compile('" + name + ".code'" + ", '" + name + "', doraise=True)\n    sys.exit(0)\nexcept Exception as e:\n    print e\n    sys.exit(1)").c_str(), NULL);
}
RunCompilerResult compile_python3(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/python3.4", "-I", "-B", "-O", "-c", ("import py_compile\nimport sys\ntry:\n    py_compile.compile('" + name + ".code'" + ", '" + name + "', doraise=True)\n    sys.exit(0)\nexcept Exception as e:\n    print(e)\n    sys.exit(1)").c_str(), NULL);
}
RunCompilerResult compile_java7(const string &name, const string &path = work_path) {
	RunCompilerResult ret = prepare_java_source(name, path);
	if (!ret.succeeded)
		return ret;

	string main_class = conf_str(name + "_main_class");

	executef("rm %s/%s -rf 2>/dev/null; mkdir %s/%s", path.c_str(), name.c_str(), path.c_str(), name.c_str());
	executef("echo package %s\\; | cat - %s/%s.code >%s/%s/%s.java", name.c_str(), path.c_str(), name.c_str(), path.c_str(), name.c_str(), main_class.c_str());

	return run_compiler((path + "/" + name).c_str(),
			(main_path + "/run/runtime/jdk1.7.0_76/bin/javac").c_str(), (main_class + ".java").c_str(), NULL);
}
RunCompilerResult compile_java8(const string &name, const string &path = work_path) {
	RunCompilerResult ret = prepare_java_source(name, path);
	if (!ret.succeeded)
		return ret;

	string main_class = conf_str(name + "_main_class");

	executef("rm %s/%s -rf 2>/dev/null; mkdir %s/%s", path.c_str(), name.c_str(), path.c_str(), name.c_str());
	executef("echo package %s\\; | cat - %s/%s.code >%s/%s/%s.java", name.c_str(), path.c_str(), name.c_str(), path.c_str(), name.c_str(), main_class.c_str());

	return run_compiler((path + "/" + name).c_str(),
			(main_path + "/run/runtime/jdk1.8.0_31/bin/javac").c_str(), (main_class + ".java").c_str(), NULL);
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

RunCompilerResult compile_c_with_implementer(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(), 
			"/usr/bin/gcc-4.8", "-o", name.c_str(), "implementer.c", "-x", "c", (name + ".code").c_str(), "-lm", "-O2", "-DONLINE_JUDGE", NULL);
}
RunCompilerResult compile_pas_with_implementer(const string &name, const string &path = work_path) {
	executef("cp %s %s", (path + "/" + name + ".code").c_str(), (path + "/" + conf_str(name + "_unit_name") + ".pas").c_str());
	return run_compiler(path.c_str(),
			"/usr/bin/fpc-2.6.2", "implementer.pas", ("-o" + name).c_str(), "-O2", NULL);
}
RunCompilerResult compile_cpp_with_implementer(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/g++-4.8", "-o", name.c_str(), "implementer.cpp", "-x", "c++", (name + ".code").c_str(), "-lm", "-O2", "-DONLINE_JUDGE", NULL);
}
RunCompilerResult compile_cpp11_with_implementer(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/g++-4.8", "-o", name.c_str(), "implementer.cpp", "-x", "c++", (name + ".code").c_str(), "-lm", "-O2", "-DONLINE_JUDGE", "-std=c++11", NULL);
}
/*
RunCompilerResult compile_python2_7(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/python2.7", "-E", "-s", "-B", "-O", "-c",
			("import py_compile\nimport sys\ntry:\n    py_compile.compile('" + name + ".code'" + ", '" + name + "', doraise=True)\n    sys.exit(0)\nexcept Exception as e:\n    print e\n    sys.exit(1)").c_str(), NULL);
}
RunCompilerResult compile_python3(const string &name, const string &path = work_path) {
	return run_compiler(path.c_str(),
			"/usr/bin/python3.4", "-I", "-B", "-O", "-c", ("import py_compile\nimport sys\ntry:\n    py_compile.compile('" + name + ".code'" + ", '" + name + "', doraise=True)\n    sys.exit(0)\nexcept Exception as e:\n    print(e)\n    sys.exit(1)").c_str(), NULL);
}
*/
RunCompilerResult compile_with_implementer(const char *name)  {
	string lang = conf_str(string(name) + "_language");

	if (has_illegal_keywords_in_file(work_path + "/" + name + ".code"))
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
		return compile_cpp_with_implementer(name);
	}
	if (lang == "C++11") {
		return compile_cpp11_with_implementer(name);
	}
	if (lang == "C") {
		return compile_c_with_implementer(name);
	}
	if (lang == "Pascal") {
		return compile_pas_with_implementer(name);
	}

	RunCompilerResult res = RunCompilerResult::failed_result();
	res.info = "This language is not supported yet.";
	return res;
}

/*====================== compile End ================== */

/*======================    test     ================== */

struct TestPointConfig {
	int submit_answer;

	int validate_input_before_test;
	string input_file_name;
	string output_file_name;
	string answer_file_name;

	TestPointConfig()
			: submit_answer(-1), validate_input_before_test(-1) {
	}

	void auto_complete(int num) {
		if (submit_answer == -1) {
			submit_answer = conf_is("submit_answer", "on");
		}
		if (validate_input_before_test == -1) {
			validate_input_before_test = conf_is("validate_input_before_test", "on");
		}
		if (input_file_name.empty()) {
			input_file_name = data_path + "/" + conf_input_file_name(num);
		}
		if (output_file_name.empty()) {
			output_file_name = work_path + "/" + conf_output_file_name(num);
		}
		if (answer_file_name.empty()) {
			answer_file_name = data_path + "/" + conf_output_file_name(num);
		}
	}
};

PointInfo test_point(const string &name, const int &num, TestPointConfig tpc = TestPointConfig()) {
	tpc.auto_complete(num);

	if (tpc.validate_input_before_test) {
		RunValidatorResult val_ret = run_validator(
				tpc.input_file_name,
				conf_run_limit("validator", 0, RL_VALIDATOR_DEFAULT),
				conf_str("validator"));
		if (val_ret.type != RS_AC) {
			return PointInfo(num, 0, -1, -1,
					"Validator " + info_str(val_ret.type),
					file_preview(tpc.input_file_name), "",
					"");
		} else if (!val_ret.succeeded) {
			return PointInfo(num, 0, -1, -1,
					"Invalid Input",
					file_preview(tpc.input_file_name), "",
					val_ret.info);
		}
	}

	if (!conf_is("interaction_mode", "on")) {
		RunResult pro_ret;
		if (!tpc.submit_answer) {
			pro_ret = run_submission_program(
					tpc.input_file_name.c_str(),
					tpc.output_file_name.c_str(),
					conf_run_limit(num, RL_DEFAULT),
					name);
			if (conf_has("token")) {
				file_hide_token(tpc.output_file_name, conf_str("token", ""));
			}
			if (pro_ret.type != RS_AC) {
				return PointInfo(num, 0, -1, -1,
						info_str(pro_ret.type),
						file_preview(tpc.input_file_name), file_preview(tpc.output_file_name),
						"");
			}
		} else {
			pro_ret.type = RS_AC;
			pro_ret.ust = -1;
			pro_ret.usm = -1;
			pro_ret.exit_code = 0;
		}

		RunCheckerResult chk_ret = run_checker(
				conf_run_limit("checker", num, RL_CHECKER_DEFAULT),
				conf_str("checker"),
				tpc.input_file_name,
				tpc.output_file_name,
				tpc.answer_file_name);
		if (chk_ret.type != RS_AC) {
			return PointInfo(num, 0, -1, -1,
					"Checker " + info_str(chk_ret.type),
					file_preview(tpc.input_file_name), file_preview(tpc.output_file_name),
					"");
		}

		return PointInfo(num, chk_ret.scr, pro_ret.ust, pro_ret.usm, 
				"default",
				file_preview(tpc.input_file_name), file_preview(tpc.output_file_name),
				chk_ret.info);
	} else {
		string real_output_file_name = tpc.output_file_name + ".real_input.txt";
		string real_input_file_name = tpc.output_file_name + ".real_output.txt";
		RunSimpleInteractionResult rires = run_simple_interation(
				tpc.input_file_name,
				tpc.answer_file_name,
				real_input_file_name,
				real_output_file_name,
				conf_run_limit(num, RL_DEFAULT),
				conf_run_limit("interactor", num, RL_INTERACTOR_DEFAULT),
				name);

		if (rires.ires.type != RS_AC) {
			return PointInfo(num, 0, -1, -1,
					"Interactor " + info_str(rires.ires.type),
					file_preview(real_input_file_name), file_preview(real_output_file_name),
					"");
		}
		if (rires.res.type != RS_AC) {
			return PointInfo(num, 0, -1, -1,
					info_str(rires.res.type),
					file_preview(real_input_file_name), file_preview(real_output_file_name),
					"");
		}

		return PointInfo(num, rires.ires.scr, rires.res.ust, rires.res.usm, 
				"default",
				file_preview(real_input_file_name), file_preview(real_output_file_name),
				rires.ires.info);
	}
}

PointInfo test_hack_point(const string &name, TestPointConfig tpc) {
	tpc.submit_answer = false;
	tpc.validate_input_before_test = false;
	tpc.auto_complete(0);
	RunValidatorResult val_ret = run_validator(
			tpc.input_file_name,
			conf_run_limit("validator", 0, RL_VALIDATOR_DEFAULT),
			conf_str("validator"));
	if (val_ret.type != RS_AC) {
		return PointInfo(0, 0, -1, -1,
				"Validator " + info_str(val_ret.type),
				file_preview(tpc.input_file_name), "",
				"");
	} else if (!val_ret.succeeded) {
		return PointInfo(0, 0, -1, -1,
				"Invalid Input",
				file_preview(tpc.input_file_name), "",
				val_ret.info);
	}

	RunLimit default_std_run_limit = conf_run_limit(0, RL_DEFAULT);

	prepare_run_standard_program();
	if (!conf_is("interaction_mode", "on")) {
		RunProgramConfig rpc;
		rpc.result_file_name = result_path + "/run_standard_program.txt";
		RunResult std_ret = run_submission_program(
				tpc.input_file_name,
				tpc.answer_file_name,
				conf_run_limit("standard", 0, default_std_run_limit),
				"std",
				rpc);
		if (std_ret.type != RS_AC) {
			return PointInfo(0, 0, -1, -1,
					"Standard Program " + info_str(std_ret.type),
					file_preview(tpc.input_file_name), "",
					"");
		}
		if (conf_has("token")) {
			file_hide_token(tpc.answer_file_name, conf_str("token", ""));
		}
	} else {
		RunProgramConfig rpc;
		rpc.result_file_name = result_path + "/run_standard_program.txt";
		string real_output_file_name = tpc.answer_file_name;
		string real_input_file_name = tpc.output_file_name + ".real_output.txt";
		RunSimpleInteractionResult rires = run_simple_interation(
				tpc.input_file_name,
				tpc.answer_file_name,
				real_input_file_name,
				real_output_file_name,
				conf_run_limit("standard", 0, default_std_run_limit),
				conf_run_limit("interactor", 0, RL_INTERACTOR_DEFAULT),
				"std",
				rpc);

		if (rires.ires.type != RS_AC) {
			return PointInfo(0, 0, -1, -1,
					"Interactor " + info_str(rires.ires.type) + " (Standard Program)",
					file_preview(real_input_file_name), "",
					"");
		}
		if (rires.res.type != RS_AC) {
			return PointInfo(0, 0, -1, -1,
					"Standard Program " + info_str(rires.res.type),
					file_preview(real_input_file_name), "",
					"");
		}
	}

	PointInfo po = test_point(name, 0, tpc);
	po.scr = po.scr != 100;
	return po;
}

CustomTestInfo ordinary_custom_test(const string &name) {
	RunLimit lim = conf_run_limit(0, RL_DEFAULT);
	lim.time += 2;

	string input_file_name = work_path + "/input.txt";
	string output_file_name = work_path + "/output.txt";

	RunResult pro_ret = run_submission_program(
			input_file_name,
			output_file_name,
			lim,
			name);
	if (conf_has("token")) {
		file_hide_token(output_file_name, conf_str("token", ""));
	}
	string info;
	if (pro_ret.type == RS_AC) {
		info = "Success";
	} else {
		info = info_str(pro_ret.type);
	}
	string exp;
	if (pro_ret.type == RS_TLE) {
		exp = "<p>[<strong>time limit:</strong> " + vtos(lim.time) + "s]</p>";
	}
	return CustomTestInfo(pro_ret.ust, pro_ret.usm, 
			info, exp, file_preview(output_file_name, 2048));
}

int scale_score(int scr100, int full) {
	return scr100 * full / 100;
}

/*======================  test End   ================== */

/*======================= conf init =================== */

void main_judger_init(int argc, char **argv)  {
	main_path = UOJ_WORK_PATH;
	work_path = main_path + "/work";
	result_path = string(UOJ_RESULT_PATH);
	load_config(work_path + "/submission.conf");
	problem_id = conf_int("problem_id");
	data_path = string(UOJ_DATA_PATH) + "/" + conf_str("problem_id");
	load_config(data_path + "/problem.conf");

	executef("cp %s/require/* %s 2>/dev/null", data_path.c_str(), work_path.c_str());

	if (conf_is("use_builtin_judger", "on")) {
		config["judger"] = string(UOJ_WORK_PATH) + "/builtin/judger/judger";
	} else {
		config["judger"] = data_path + "/judger";
	}
}
void judger_init(int argc, char **argv) {
	if (argc != 5) {
		exit(1);
	}
	main_path = argv[1];
	work_path = argv[2];
	result_path = argv[3];
	data_path = argv[4];
	load_config(work_path + "/submission.conf");
	problem_id = conf_int("problem_id");
	load_config(data_path + "/problem.conf");

	if (config.count("use_builtin_checker")) {
		config["checker"] = main_path + "/builtin/checker/" + config["use_builtin_checker"];
	} else {
		config["checker"] = data_path + "/chk";
	}
	config["validator"] = data_path + "/val";
}

/*===================== conf init End ================= */
