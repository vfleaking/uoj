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
#include <memory>
#include <filesystem>

#include <unistd.h>
#include <sys/file.h>
#include <sys/stat.h>
#include <sys/wait.h>

#include "uoj_secure.h"
#include "uoj_run.h"

namespace fs = std::filesystem;
using namespace std;

/*========================== string ====================== */

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

template <typename T>
inline string list_to_string(const T &list) {
	ostringstream sout;
	sout << "{";
	bool is_first = false;
	for (auto &t : list) {
		if (!is_first) {
			sout << ", ";
		}
		sout << t;
	}
	sout << "}";
	return sout.str();
}

/*========================== random  ====================== */

inline string gen_token() {
	u64 seed = time(NULL);
	FILE *f = fopen("/dev/urandom", "r");
	if (f) {
		for (int i = 0; i < 8; i++)
			seed = seed << 8 | (u8)fgetc(f);
		fclose(f);
	}
	uoj_mt_rand_engine rnd(seed);
	return rnd.randstr(64);
}

/*========================== crypto  ====================== */

inline string file_get_contents(const string &name) {
	string out;
	FILE *f = fopen(name.c_str(), "r");
	if (!f) {
		return out;
	}

	const int BUFFER_SIZE = 1024;
	u8 buffer[BUFFER_SIZE + 1];
	while (!feof(f)) {
		int ret = fread(buffer, 1, BUFFER_SIZE, f);
		if (ret < 0) {
			break;
		}
		out.append((char *)buffer, ret);
	}
	fclose(f);
	return out;
}
inline bool file_put_contents(const string &name, const string &m) {
	FILE *f = fopen(name.c_str(), "w");
	if (!f) {
		return false;
	}
	int c = fwrite(m.data(), 1, m.length(), f);
	fclose(f);
	return c == (int)m.length();
}

inline bool file_encrypt(const string &fi, const string &fo, const string &key) {
	string m = file_get_contents(fi);
	uoj_cipher cipher(key);
	cipher.encrypt(m);
	return file_put_contents(fo, m);
}
inline bool file_decrypt(const string &fi, const string &fo, const string &key) {
	string m = file_get_contents(fi);
	uoj_cipher cipher(key);
	if (cipher.decrypt(m)) {
		file_put_contents(fo, m);
		return true;
	} else {
		file_put_contents(fo, "Unauthorized output");
		return false;
	}
}

/*========================= file ====================== */

string file_preview(const string &name, const int &len = 100) {
	FILE *f = fopen(name.c_str(), "r");
	if (f == NULL) {
		return "";
	}

	string res = "";
	if (len == -1) {
		int c;
		while (c = fgetc(f), c != EOF) {
			res += c;
		}
	} else {
		int c;
		while (c = fgetc(f), c != EOF && (int)res.size() < len + 4) {
			res += c;
		}
		if ((int)res.size() > len + 3) {
			res.resize(len);
			res += "...";
		}
	}
	fclose(f);
	return res;
}
/*
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
}*/
int file_hide_token(const string &token, const string &in, const string &out) {
	ifstream fin(in);
	ofstream fout(out);

	if (!fin || !fout) {
		return -1;
	}

	string target = token + "\n";
	int L = max(1 << 15, (int)target.length());
	char buf[L];

	fin.read(buf, target.length());
	if (fin.bad()) {
		return -1;
	}
	if (!(fin.good() && equal(target.begin(), target.end(), buf))) {
		fout << "???" << endl;
		return 1;
	}

	while (!fin.eof()) {
		fin.read(buf, L);
		fout.write(buf, fin.gcount());
		if (fin.bad()) {
			return -1;
		}
	}
	return 0;
}
bool file_replace_tokens(const string &name, const string &token, const string &new_token) {
	string esc_name = escapeshellarg(name);
	string esc_token = escapeshellarg(token);
	string esc_new_token = escapeshellarg(new_token);
	return executef("sed -i s/%s/%s/g %s", esc_token.c_str(), esc_new_token.c_str(), esc_name.c_str()) == 0;
}
bool file_copy(const string &a, const string &b) { // copy a to b
	string esc_a = escapeshellarg(a);
	string esc_b = escapeshellarg(b);
	return executef("cp %s %s -f 2>/dev/null", esc_a.c_str(), esc_b.c_str()) == 0; // the most cubao implementation in the world
}
bool file_hardlink(const string &a, const string &b) { // copy a to b
	string esc_a = escapeshellarg(a);
	string esc_b = escapeshellarg(b);
	return executef("cp %s %s -lf 2>/dev/null", esc_a.c_str(), esc_b.c_str()) == 0; // the most cubao implementation in the world
}
bool file_move(const string &a, const string &b) { // move a to b
	string esc_a = escapeshellarg(a);
	string esc_b = escapeshellarg(b);
	return executef("mv %s %s", esc_a.c_str(), esc_b.c_str()) == 0; // the most cubao implementation in the world
}

/*======================= file End ==================== */

/*====================== parameter ==================== */

const runp::limits_t RL_DEFAULT(1, 256, 64);
const runp::limits_t RL_JUDGER_DEFAULT(600, 10 * 1024, 128);  // 10GB. change it if needed
const runp::limits_t RL_CHECKER_DEFAULT(5, 256, 64);
const runp::limits_t RL_INTERACTOR_DEFAULT(1, 256, 64);
const runp::limits_t RL_VALIDATOR_DEFAULT(5, 256, 64);
const runp::limits_t RL_TRANSFORMER_DEFAULT(30, 512, 256);
const runp::limits_t RL_COMPILER_DEFAULT(15, 512, 64);

struct InfoBlock {
	string title;
	string content;
	int orig_size;

	static InfoBlock empty(const string &title) {
		InfoBlock info;
		info.title = title;
		info.content = "";
		info.orig_size = -1;
		return info;
	}
	static InfoBlock from_string(const string &title, const string &content) {
		InfoBlock info;
		info.title = title;
		info.content = content;
		info.orig_size = -1;
		return info;
	}
	static InfoBlock from_file(const string &title, const string &name, int preview_size = 100) {
		InfoBlock info;
		info.title = title;
		info.content = file_preview(name, preview_size);
		info.orig_size = -1;
		return info;
	}
	static InfoBlock from_file_with_size(const string &title, const string &name, int preview_size = 100) {
		InfoBlock info;
		info.title = title;
		info.content = file_preview(name, preview_size);
		info.orig_size = fs::file_size(name);
		return info;
	}

	string to_str() const {
		if (title == "res") {
			return "<res>" + htmlspecialchars(content) + "</res>";
		}

		string str = "<info-block";
		str += " title=\"";
		for (const char &c : title) {
			str += c == '_' ? ' ' : c;
		}
		str += "\"";
		if (orig_size != -1) {
			str += " size=\"" + to_string(orig_size) + "\"";
		}
		str += ">" + htmlspecialchars(content) + "</info-block>";

		return str;
	}
};

struct PointInfo {
	int num;
	int scr = 0;
	int ust = -1, usm = -1;
	string info, res;

	vector<InfoBlock> li;

	static PointInfo extra_test_passed() {
		PointInfo po;
		po.num = -1;
		po.info = "Extra Test Passed";
		return po;
	}

	void set_info(const string &info) {
		if (info == "default") {
			if (scr == 0) {
				this->info = "Wrong Answer";
			} else if (scr == 100) {
				this->info = "Accepted";
			} else {
				this->info = "Acceptable Answer";
			}
		} else {
			this->info = info;
		}
	}
};

/*struct CustomTestInfo  {
	int ust, usm;
	string info, exp, out;

	CustomTestInfo(const int &_ust, const int &_usm, const string &_info,
			const string &_exp, const string &_out)
			: ust(_ust), usm(_usm), info(_info),
			exp(_exp), out(_out) {
	}
};*/

struct run_checker_result {
	runp::RS_TYPE type;
	int scr;
	string info;

	static run_checker_result from_file(const string &file_name, const runp::result &rres) {
		run_checker_result res;
		res.type = rres.type;

		if (rres.type != runp::RS_AC) {
			res.scr = 0;
		} else {
			FILE *fres = fopen(file_name.c_str(), "r");
			char type[21];
			if (fres == NULL || fscanf(fres, "%20s", type) != 1) {
				return run_checker_result::failed_result();
			}
			if (strcmp(type, "ok") == 0) {
				res.scr = 100;
			} else if (strcmp(type, "points") == 0) {
				double d;
				if (fscanf(fres, "%lf", &d) != 1) {
					return run_checker_result::failed_result();
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
	
	static run_checker_result failed_result() {
		run_checker_result res;
		res.type = runp::RS_JGF;
		res.scr = 0;
		res.info = "Checker Failed";
		return res;
	}
};
struct run_validator_result {
	runp::RS_TYPE type;
	bool succeeded;
	string info;
	
	static run_validator_result failed_result() {
		run_validator_result res;
		res.type = runp::RS_JGF;
		res.succeeded = 0;
		res.info = "Validator Failed";
		return res;
	}
};
struct run_compiler_result {
	runp::RS_TYPE type;
	bool succeeded;
	string info;

	static run_compiler_result failed_result() {
		run_compiler_result res;
		res.type = runp::RS_JGF;
		res.succeeded = false;
		res.info = "Compile Failed";
		return res;
	}
};
struct run_transformer_result {
	runp::RS_TYPE type;
	bool succeeded;
	string info;
};

// see also: run_simple_interaction
struct run_simple_interaction_result {
	runp::result res; // prog
	run_checker_result ires; // interactor
};

fs::path main_path;
fs::path work_path;
fs::path data_path;
fs::path result_path;

int tot_time   = 0;
int max_memory = 0;
int tot_score  = 0;
string uoj_errcode; // empty means no error
ostringstream details_out;
map<string, string> uconfig;

void uoj_error(const string &_uoj_errcode) {
	if (uoj_errcode.empty()) {
		uoj_errcode = _uoj_errcode;
	}
}

class jgf_error : exception {
public:
	string code;
protected:
	string info;
public:
    explicit jgf_error(const string &code, const string &info = "") : code(code), info(info) {
		if (this->info.empty()) {
			this->info = runp::rstype_str(runp::RS_JGF);
		}
	}

	virtual const char* what() const noexcept override {
		return this->info.c_str();
	}
};

/*==================== parameter End ================== */

/*====================== config set =================== */

void print_config() {
	for (auto &p : uconfig) {
		cerr << p.first << " = " << p.second << endl;
	}
}
void load_config(const string &filename) {
	ifstream fin(filename.c_str());
	if (!fin) {
		return;
	}
	string key, val;
	while (fin >> key >> val) {
		uconfig[key] = val;
	}
}
string conf_str(const string &key, int num, const string &val) {
	ostringstream sout;
	sout << key << "_" << num;
	if (uconfig.count(sout.str()) == 0) {
		return val;
	}
	return uconfig[sout.str()];
}
string conf_str(const string &key, const string &val) {
	if (uconfig.count(key) == 0) {
		return val;
	}
	return uconfig[key];
}
string conf_str(const string &key) {
	return conf_str(key, "");
}
int conf_int(const string &key, const int &val) {
	if (uconfig.count(key) == 0) {
		return val;
	}
	return atoi(uconfig[key].c_str());
}
int conf_int(const string &key, int num, const int &val) {
	ostringstream sout;
	sout << key << "_" << num;
	if (uconfig.count(sout.str()) == 0) {
		return conf_int(key, val);
	}
	return atoi(uconfig[sout.str()].c_str());
}
int conf_int(const string &key)  {
	return conf_int(key, 0);
}
string conf_file_name_with_num(string s, int num) {
	ostringstream name;
	if (num < 0) {
		name << "ex_";
	}
	name << conf_str(s + "_pre", s) << abs(num) << "." << conf_str(s + "_suf", "txt");
	return name.str();
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
runp::limits_t conf_run_limit(string pre, const int &num, const runp::limits_t &val) {
	if (!pre.empty()) {
		pre += "_";
	}
	runp::limits_t limits;
	limits.time = conf_int(pre + "time_limit", num, val.time);
	limits.memory = conf_int(pre + "memory_limit", num, val.memory);
	limits.output = conf_int(pre + "output_limit", num, val.output);
	limits.real_time = conf_int(pre + "real_time_limit", num, val.real_time);
	limits.stack = conf_int(pre + "stack_limit", num, val.real_time);
	return limits;
}
runp::limits_t conf_run_limit(const int &num, const runp::limits_t &val) {
	return conf_run_limit("", num, val);
}
void conf_add(const string &key, const string &val)  {
	if (uconfig.count(key)) {
		return;
	}
	uconfig[key] = val;
}
bool conf_has(const string &key)  {
	return uconfig.count(key);
}
bool conf_is(const string &key, const string &val)  {
	return uconfig.count(key) && uconfig[key] == val;
}

template<typename TK, typename TV>
void map_add(map<TK, TV> &m, const initializer_list<pair<TK, TV>> &vs) {
	for (const auto &v : vs) {
		if (!m.count(v.first)) {
			m[v.first] = v.second;
		}
	}
}

/*==================== config set End ================= */

/*====================== info print =================== */

void add_point_info(const PointInfo &info, bool update_tot_score = true) {
	if (info.num >= 0) {
		if(info.ust >= 0) {
			tot_time += info.ust;
		}
		if(info.usm >= 0) {
			max_memory = max(max_memory, info.usm);
		}
	}
	if (update_tot_score) {
        tot_score += info.scr;
	}

	details_out << "<test num=\"" << info.num << "\""
		<< " score=\"" << info.scr << "\""
		<< " info=\"" << htmlspecialchars(info.info) << "\""
		<< " time=\"" << info.ust << "\""
		<< " memory=\"" << info.usm << "\">" << endl;

	for (const InfoBlock &b : info.li) {
		if (b.title == "input" && conf_str("show_in", "on") != "on") {
			continue;
		}
		if (b.title == "output" && conf_str("show_out", "on") != "on") {
			continue;
		}
		if (conf_str("show_" + b.title, "on") != "on") {
			continue;
		}
		details_out << b.to_str() << endl;
	}
	if (conf_str("show_res", "on") == "on") {
		details_out << "<res>" << htmlspecialchars(info.res) << "</res>" << endl;
	}
	details_out << "</test>" << endl;
}
void add_custom_test_info(const PointInfo &info) {
	if(info.ust >= 0) {
		tot_time += info.ust;
	}
	if(info.usm >= 0) {
		max_memory = max(max_memory, info.usm);
	}

	details_out << "<custom-test info=\"" << htmlspecialchars(info.info) << "\""
		<< " time=\"" << info.ust << "\""
		<< " memory=\"" << info.usm << "\">" << endl;
	for (const InfoBlock &b : info.li) {
		if (b.title == "input" && conf_str("show_in", "off") != "on") {
			continue;
		}
		if (b.title == "output" && conf_str("show_out", "on") != "on") {
			continue;
		}
		details_out << b.to_str() << endl;
	}
	if (conf_str("show_res", "on") == "on") {
		details_out << "<res>" << htmlspecialchars(info.res) << "</res>" << endl;
	}
	details_out << "</custom-test>" << endl;
}
void add_subtask_info(const int &num, const int &scr, const string &info, const vector<PointInfo> &points) {
	details_out << "<subtask num=\"" << num << "\""
		<< " score=\"" << scr << "\""
		<< " info=\"" << htmlspecialchars(info) << "\">" << endl;
	tot_score += scr;
	for (const PointInfo &point : points) {
		add_point_info(point, false);
	}
	details_out << "</subtask>" << endl;
}
[[noreturn]] void end_judge_ok() {
	ofstream fres(result_path / "result.txt");
	if (uoj_errcode.empty()) {
		fres << "score " << tot_score << "\n";
		fres << "time " << tot_time << "\n";
		fres << "memory " << max_memory << "\n";
	} else {
		fres << "error Judgment Failed\n";
	}
	fres << "details\n";
	if (uoj_errcode.empty()) {
		fres << "<tests>\n";
	} else {
		fres << "<tests errcode=\"" << htmlspecialchars(uoj_errcode) << "\">\n";
	}
	fres << details_out.str();
	fres << "</tests>\n";
	fres.close();
	exit(uoj_errcode.empty() && fres ? 0 : 1);
}
[[noreturn]] void end_judge_judgment_failed(const string &info) {
	ofstream fres(result_path / "result.txt");
	fres << "error Judgment Failed\n";
	fres << "details\n";
	fres << "<error>" << htmlspecialchars(info) << "</error>\n";
	fres.close();
	exit(0);
}
[[noreturn]] void end_judge_compile_error(const run_compiler_result &res) {
	ofstream fres(result_path / "result.txt");
	fres << "error Compile Error\n";
	fres << "details\n";
	fres << "<error>" << htmlspecialchars(res.info) << "</error>\n";
	fres.close();
	exit(0);
}

void report_judge_status(const char *status) {
	FILE *f = fopen((result_path / "cur_status.txt").c_str(), "a");
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

/*========================== run ====================== */

// namespace for run_program
namespace runp {
	inline string get_type_from_lang(const string &lang) {
		if (lang == "Python2.7") {
			return "python2.7";
		} else if (lang == "Python3") {
			return "python3";
		} else if (lang == "Java7") {
			return "java7";
		} else if (lang == "Java8") {
			return "java8";
		} else {
			return "default";
		}
	}

	// internal programs for run_program
	namespace internal {
		result nonempty(const config &rpc) {
			result res;
			res.ust = res.usm = 0;
			if (rpc.rest_args.size() != 3) {
				res.exit_code = -1;
				res.type = RS_RE;
				return res;
			}

			ofstream ferr(rpc.error_file_name);

			if (fs::file_size(rpc.rest_args[1]) > 0) {
				ferr << "ok nonempty file" << endl;
			} else {
				ferr << "wrong answer empty file" << endl;
			}
			res.exit_code = 0;
			res.type = RS_AC;
			return res;
		}

		result cp(const config &rpc) {
			result res;
			res.ust = res.usm = 0;
			if (rpc.rest_args.size() != 2) {
				res.exit_code = -1;
				res.type = RS_RE;
				return res;
			}

			if (file_hardlink(rpc.rest_args[0], rpc.rest_args[1]) || file_copy(rpc.rest_args[0], rpc.rest_args[1])) {
				res.exit_code = 0;
				res.type = RS_AC;
			} else {
				res.exit_code = -1;
				res.type = RS_RE;
			}
			return res;
		}

		result hide_token(const config &rpc) {
			result res;
			res.ust = res.usm = 0;
			if (rpc.rest_args.size() != 2) {
				res.exit_code = -1;
				res.type = RS_RE;
				return res;
			}

			int ret = file_hide_token(conf_str("token", ""), rpc.rest_args[0], rpc.rest_args[1]);
			if (ret == -1) {
				res.exit_code = -1;
				res.type = RS_RE;
			} else {
				res.type = RS_AC;
				res.exit_code = ret;
				if (res.exit_code != 0) {
					ofstream ferr(rpc.error_file_name);
					ferr << "Invalid Output" << endl;
				}
			}
			return res;
		}

		map<string, function<result(const config &)>> call_table = {
			{"nonempty", nonempty},
			{"cp", cp},
			{"hide_token", hide_token}
		};
	}

	result run(const config &rpc) {
		if (rpc.type == "internal" && internal::call_table.count(rpc.program_name)) {
			return internal::call_table[rpc.program_name](rpc);
		} else if (rpc.program_name.empty()) {
			throw jgf_error("RPFALEM"); // run_program failed, because program is empty
		}
		string cmd = rpc.get_cmd();
		if (execute(rpc.get_cmd()) != 0) {
			uoj_error("RPFAL"); // run_program failed
			return result::failed_result();
		}
		return result::from_file(rpc.result_file_name);
	}
}

// for all run_xxx functions, rpc is passed
// rpc should set program name, args, type, limits, readable/writable files
// run_xxx will fill in in/out/err/res file names, and may reset args, readable files, etc.
run_validator_result run_validator(runp::config rpc, const string &input_file_name) {
	rpc.result_file_name = result_path / "run_validator_result.txt";
	rpc.input_file_name = input_file_name;
	rpc.output_file_name = "/dev/null";
	rpc.error_file_name = result_path / "validator_error.txt";
	runp::result ret = runp::run(rpc);

	run_validator_result res;
	res.type = ret.type;
	if (ret.type != runp::RS_AC || ret.exit_code != 0) {
		res.succeeded = false;
		res.info = file_preview(rpc.error_file_name);
	} else {
		res.succeeded = true;
	}
	return res;
}

run_checker_result run_checker(runp::config rpc,
                               const string &input_file_name,
							   const string &output_file_name,
							   const string &answer_file_name) {
	rpc.result_file_name = result_path / "run_checker_result.txt";
	rpc.input_file_name = "/dev/null";
	rpc.output_file_name = "/dev/null";
	rpc.error_file_name = result_path / "checker_error.txt";
	rpc.readable_file_names.insert(rpc.readable_file_names.end(), {
		input_file_name, output_file_name, answer_file_name
	});
	rpc.rest_args = {
		fs::canonical(input_file_name),
		fs::canonical(output_file_name),
		fs::canonical(answer_file_name)
	};
	return run_checker_result::from_file(
		rpc.error_file_name,
		runp::run(rpc)
	);
}

template <typename... Args>
run_compiler_result run_compiler(runp::config rpc) {
	rpc.result_file_name = result_path / "run_compiler_result.txt";
	rpc.input_file_name = "/dev/null";
	rpc.output_file_name = "stderr";
	rpc.error_file_name = result_path / "compiler_result.txt";
	rpc.type = "compiler";

	runp::result ret = runp::run(rpc);
	run_compiler_result res;
	res.type = ret.type;
	res.succeeded = ret.type == runp::RS_AC && ret.exit_code == 0;
	if (!res.succeeded) {
		if (ret.type == runp::RS_AC) {
			res.info = file_preview(result_path / "compiler_result.txt", 10240);
		} else if (ret.type == runp::RS_JGF) {
			res.info = "No Comment";
		} else {
			res.info = "Compiler " + runp::rstype_str(ret.type);
		}
	}
	return res;
}

runp::result run_submission_program(runp::config rpc, const string &input_file_name, const string &output_file_name) {
	rpc.result_file_name = result_path / "run_submission_program.txt";
	rpc.input_file_name = input_file_name;
	rpc.output_file_name = output_file_name;
	rpc.error_file_name = "/dev/null";

	runp::result res = runp::run(rpc);
	if (res.type == runp::RS_AC && res.exit_code != 0) {
		res.type = runp::RS_RE;
	}
	return res;
}

void prepare_interactor() {
	static bool prepared = false;
	if (prepared) {
		return;
	}
	string data_path_std = data_path / "interactor";
	string work_path_std = work_path / "interactor";
	executef("cp %s %s", data_path_std.c_str(), work_path_std.c_str());
	conf_add("interactor_language", "C++");
	prepared = true;
}

// simple: prog <---> interactor <---> data
run_simple_interaction_result run_simple_interaction(
		const string &input_file_name,
		const string &answer_file_name,
		const string &real_input_file_name,
		const string &real_output_file_name,
		runp::config rpc,
		runp::config irpc) {
	prepare_interactor();

	rpc.result_file_name = result_path / "run_submission_program.txt";
	rpc.input_file_name = "stdin";
	rpc.output_file_name = "stdout";
	rpc.error_file_name = "/dev/null";

	irpc.result_file_name = result_path / "run_interactor_program.txt";
	irpc.readable_file_names.push_back(input_file_name);
	irpc.readable_file_names.push_back(answer_file_name);
	irpc.input_file_name = "stdin";
	irpc.output_file_name = "stdout";
	irpc.error_file_name = result_path / "interactor_error.txt";
	irpc.program_name = data_path / "interactor";
	irpc.rest_args = {
		input_file_name,
		"/dev/stdin",
		answer_file_name
	};
	irpc.limits.real_time = rpc.limits.real_time = rpc.limits.time + irpc.limits.time + 1;

	runp::interaction::config ric;
	ric.cmds.push_back(rpc.get_cmd());
	ric.cmds.push_back(irpc.get_cmd());

	// from:fd, to:fd
	ric.pipes.push_back(runp::interaction::pipe_config(2, 1, 1, 0, real_input_file_name));
	ric.pipes.push_back(runp::interaction::pipe_config(1, 1, 2, 0, real_output_file_name));
	
	runp::interaction::run(ric);

	run_simple_interaction_result rires;
	runp::result res = runp::result::from_file(rpc.result_file_name);
	run_checker_result ires = run_checker_result::from_file(irpc.error_file_name, runp::result::from_file(irpc.result_file_name));

	if (res.type == runp::RS_AC && res.exit_code != 0) {
		res.type = runp::RS_RE;
	}

	if (ires.type == runp::RS_JGF) {
		ires.info = "Interactor Judgment Failed";
	}
	if (ires.type == runp::RS_TLE) {
		ires.type = runp::RS_AC;
		res.type = runp::RS_TLE;
	}

	rires.res = res;
	rires.ires = ires;
	return rires;
}

run_transformer_result transform_file(runp::config rpc, const string &in, const string &out) {
	run_transformer_result res;
	if (in == out) {
		res.type = runp::RS_AC;
		res.succeeded = true;
	} else {
		rpc.result_file_name = result_path / "run_trans_result.txt";
		rpc.input_file_name = in;
		rpc.output_file_name = out;
		rpc.error_file_name = result_path / "trans_error.txt";
		runp::result ret = runp::run(rpc);
		res.type = ret.type;
		res.succeeded = ret.type == runp::RS_AC && ret.exit_code == 0;
		if (!res.succeeded) {
			res.info = file_preview(rpc.error_file_name);
		}
	}
	return res;
}

/*======================== run End ==================== */

/*======================== compile ==================== */

run_compiler_result compile(const string &name)  {
	string lang = conf_str(name + "_language");
	runp::config rpc(main_path / "run" / "compile", {
		"--custom", main_path / "run" / "runtime",
		"--lang", lang,
		name
	});
	rpc.limits = RL_COMPILER_DEFAULT;
	return run_compiler(rpc);
}

run_compiler_result compile_with_implementer(const string &name, const string &implementer = "implementer")  {
	string lang = conf_str(name + "_language");
	if (conf_has(name + "_unit_name")) {
		file_put_contents(work_path / (name + ".unit_name"), conf_str(name + "_unit_name"));
	}
	runp::config rpc(main_path / "run" / "compile", {
		"--custom", main_path / "run" / "runtime",
		"--impl", implementer, "--lang", lang,
		name
	});
	rpc.limits = RL_COMPILER_DEFAULT;
	return run_compiler(rpc);
}

/*====================== compile End ================== */

/*======================    test     ================== */

/*
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
*/

/*
	base class for testing one point

	for each member function, it returns false only if an internal error
	occurs (due to OS, File System, or problem setter).
	po.info stores the information that will be shown to the user the
	first function returning false is responsible to set po.info and set
	uoj_error with an error code.
	
	if an error occurs due to the user (e.g., the user's program RE),
	po.info is set while the member function does not have to return false.
	To check errors regardless its origin, use check(), which returns
	false if po.info is non-empty and po.scr != 100.
*/
class TestPoint {
public:
	typedef int Author;
	static const int AUTHOR_USERBIT = 1 << 8;
	static const char TITLE_DISABLED[];
	static const char AUTHOR_NONAME[];

	enum {
		AUTHOR_SETTER = 0,
		AUTHOR_STD
	};
	enum {
		AUTHOR_USER = AUTHOR_USERBIT,
		AUTHOR_USER2
	};

	int num = 0;
	int validate_input_before_test = -1;
	map<string, fs::path> fname;
	map<string, string> ftitle;
	map<string, int> fpreview_size;
	map<string, Author> fauthor;
	map<Author, string> author_name {
		{AUTHOR_SETTER, "Problem Setter"},
		{AUTHOR_STD, "Standard Program"},
		{AUTHOR_USER, AUTHOR_NONAME}
	};
	map<string, runp::config> program;

	PointInfo test();
	PointInfo hack_test();
	PointInfo custom_test();

private:
	void config_sanity_check();

protected:
	PointInfo po;

	fs::path get_fname(const string &fkey, const string &default_val = "");
	string get_basename(const string &key);
	int get_fpreview_size(const string &fkey, int default_val = 100);

	runp::config get_program(const string &id);
	runp::config search_program(const initializer_list<string> &ids);

	void set_res_if_empty(const string &res);
	void set_info_if_empty(const string &info);
	void set_info_if_empty(const string &info, Author author);
	bool add_info_block(const string &fkey);

	bool check();
	bool prepare_io();
	bool preprocess_input(const string &input);
	bool postprocess_output(const string &output);
	bool validate(const string &fkey);
	bool encode(const string &input);
	bool encrypt(const string &input);
	bool decrypt(const string &output);
	bool decode(const string &output);
	bool grade(const string &input, const string &output, const string &answer);
	bool run_custom(const string &id, const string &input, const string &output);

	virtual void complete_basic_config();
	virtual void complete_config();
	virtual void complete_hack_config();
	virtual void complete_custom_test_config();

	virtual bool generate_answer(const string &input, const string &answer);
	
	virtual bool _test();
	virtual bool _hack_test();
	virtual bool _custom_test();
};

const char TestPoint::TITLE_DISABLED[] = "disabled";
const char TestPoint::AUTHOR_NONAME[] = "noname";

PointInfo TestPoint::test() {
	try {
		complete_basic_config();
		complete_config();
		config_sanity_check();
		_test();
	} catch (jgf_error &e) {
		uoj_error(e.code);
		set_info_if_empty(runp::rstype_str(runp::RS_JGF));
	}
	if (po.info.empty()) {
		uoj_error("PINFEM"); // po.info is empty
		po.info = runp::rstype_str(runp::RS_JGF);
	}
	return po;
}

PointInfo TestPoint::hack_test() {
	try {
		complete_basic_config();
		complete_hack_config();
		config_sanity_check();
		_hack_test();
	} catch (jgf_error &e) {
		uoj_error(e.code);
		set_info_if_empty(runp::rstype_str(runp::RS_JGF));
	}
	if (po.info.empty()) {
		uoj_error("PINFEM"); // po.info is empty
		po.info = runp::rstype_str(runp::RS_JGF);
	}
	if (uoj_errcode.empty()) {
		po.scr = po.scr != 100;
	}
	return po;
}

PointInfo TestPoint::custom_test() {
	try {
		complete_basic_config();
		complete_custom_test_config();
		config_sanity_check();
		_custom_test();
	} catch (jgf_error &e) {
		uoj_error(e.code);
		set_info_if_empty(runp::rstype_str(runp::RS_JGF));
	}
	if (po.info.empty()) {
		uoj_error("PINFEM"); // po.info is empty
		po.info = runp::rstype_str(runp::RS_JGF);
	}
	return po;
}

void TestPoint::config_sanity_check() {
	if (validate_input_before_test == -1) {
		throw jgf_error("CFGERR1"); // config error
	}
	for (const auto &ft : ftitle) {
		if (!fname.count(ft.first)) {
			throw jgf_error("CFGERR2"); // config error
		}
	}
	for (const auto &fp : fpreview_size) {
		if (!fname.count(fp.first)) {
			throw jgf_error("CFGERR3"); // config error
		}
	}
	for (const auto &fn : fname) {
		if (!fauthor.count(get_basename(fn.first))) {
			throw jgf_error("CFGERR4"); // config error
		}
		if (!author_name.count(fauthor[get_basename(fn.first)])) {
			throw jgf_error("CFGERR5"); // config error
		}
	}
}

fs::path TestPoint::get_fname(const string &fkey, const string &default_val) {
	return fname.count(fkey) ? fname[fkey] : fs::path(conf_str(fkey, default_val));
}

string TestPoint::get_basename(const string &key) {
	size_t pos = key.find_last_of('.');
	if (pos == string::npos) {
		return key;
	} else {
		return key.substr(0, pos);
	}
}

int TestPoint::get_fpreview_size(const string &fkey, int default_val) {
	return fpreview_size.count(fkey) ? fpreview_size[fkey] : default_val;
}

runp::config TestPoint::get_program(const string &id) {
	try {
		return program.at(id);
	} catch (out_of_range &e) {
		throw jgf_error("PROGNF1", "Program " + id + " Not Found"); // program not found
	}
}
runp::config TestPoint::search_program(const initializer_list<string> &ids) {
	for (auto &id : ids) {
		if (program.count(id)) {
			return program[id];
		}
	}
	throw jgf_error("PROGNF2", "Program " + list_to_string(ids) + " Not Found"); // program not found
}

void TestPoint::set_res_if_empty(const string &res) {
	if (po.res.empty()) {
		po.res = res;
	}
}

void TestPoint::set_info_if_empty(const string &info) {
	if (po.info.empty()) {
		po.set_info(info);
	}
}

void TestPoint::set_info_if_empty(const string &info, Author author) {
	if (po.info.empty()) {
		if (!author_name.count(author)) {
			po.set_info(info);
		} else {
			po.set_info(info + " (" + author_name[author] + ")");
		}
	}
}

bool TestPoint::add_info_block(const string &fkey) {
	if (ftitle.count(fkey) && ftitle[fkey] != "disabled") {
		po.li.push_back(InfoBlock::from_file_with_size(ftitle[fkey], fname[fkey], get_fpreview_size(fkey)));
	}
	return true;
}

bool TestPoint::check() {
	return po.info.empty() || po.scr == 100;
}

bool TestPoint::prepare_io() {
	try {
		fs::remove_all(work_path / "io");
		fs::create_directories(work_path / "io");
		return true;
	} catch (exception &e) {
		throw jgf_error("PPIOFAL"); // prepare_io failed
	}
}

bool TestPoint::preprocess_input(const string &input) {
	return validate(input) && check()
		&& encode(input) && check()
		&& encrypt(input) && check();
}

bool TestPoint::postprocess_output(const string &output) {
	return decrypt(output) && check()
		&& decode(output) && check();
}

bool TestPoint::validate(const string &fkey) {
	if (!validate_input_before_test) {
		return true;
	}

	run_validator_result ret = run_validator(get_program("val"), fname[fkey]);
	if (ret.type != runp::RS_AC) {
		throw jgf_error("VALFAL", "Validator " + runp::rstype_str(ret.type)); // validator failed
	} else if (!ret.succeeded) {
		set_res_if_empty(ret.info);
		set_info_if_empty("Invalid Input", fauthor[fkey]);
		if (!(fauthor[fkey] & AUTHOR_USERBIT)) {
			throw jgf_error("INVINV"); // invalid input (found by val)
		}
	}
	return true;
}

bool TestPoint::encode(const string &input) {
	run_transformer_result ret = transform_file(
		search_program({input + "encoder", "encoder"}),
		fname[input], fname[input + ".plain"]
	);
	add_info_block(input + ".plain");
	if (ret.type != runp::RS_AC) {
		throw jgf_error("ECOFAL", "Encoder " + runp::rstype_str(ret.type)); // encoder failed
	} else if (!ret.succeeded) {
		set_res_if_empty(ret.info);
		set_info_if_empty("Invalid Input", fauthor[input]);
		if (!(fauthor[input] & AUTHOR_USERBIT)) {
			throw jgf_error("INVINO"); // invalid input (found by encode)
		}
	}
	return true;
}

bool TestPoint::encrypt(const string &input) {
	run_transformer_result ret = transform_file(
		search_program({input + "encrypter", "encrypter"}),
		fname[input + ".plain"], fname[input + ".raw"]
	);
	add_info_block(input + ".raw");
	if (ret.type != runp::RS_AC) {
		throw jgf_error("ECRFAL", "Encrypter " + runp::rstype_str(ret.type)); // encrypter failed
	} else if (!ret.succeeded) {
		set_res_if_empty(ret.info);
		set_info_if_empty("Invalid Input", fauthor[input]);
		if (!(fauthor[input] & AUTHOR_USERBIT)) {
			throw jgf_error("INVINC"); // invalid input (found by encrypt)
		}
	}
	return true;
}

bool TestPoint::decrypt(const string &output) {
	run_transformer_result ret = transform_file(
		search_program({output + "decrypter", "decrypter"}),
		fname[output + ".raw"], fname[output + ".plain"]
	);
	add_info_block(output + ".plain");
	if (ret.type != runp::RS_AC) {
		throw jgf_error("DCRFAL", "Decrypter " + runp::rstype_str(ret.type)); // decrypter failed
	} else if (!ret.succeeded) {
		set_res_if_empty(ret.info);
		if (!(fauthor[output] & AUTHOR_USERBIT)) {
			set_info_if_empty("Invalid Output", fauthor[output]);
			throw jgf_error("INTWAC"); // internal WA (found by encrypt)
		}
		set_info_if_empty("Wrong Answer", fauthor[output]);
	}
	return true;
}

bool TestPoint::decode(const string &output) {
	run_transformer_result ret = transform_file(
		search_program({output + "decoder", "decoder"}),
		fname[output + ".plain"], fname[output]
	);
	add_info_block(output);
	if (ret.type != runp::RS_AC) {
		throw jgf_error("DCOFAL", "Decrypter " + runp::rstype_str(ret.type)); // decoder failed
	} else if (!ret.succeeded) {
		set_res_if_empty(ret.info);
		if (!(fauthor[output] & AUTHOR_USERBIT)) {
			set_info_if_empty("Invalid Output", fauthor[output]);
			throw jgf_error("INTWAO"); // internal WA (found by encode)
		}
		set_info_if_empty("Wrong Answer", fauthor[output]);
	}
	return true;
}

bool TestPoint::generate_answer(const string &input, const string &answer) {
	runp::result ret = run_submission_program(
		get_program("std"),
		fname["input.raw"], fname["answer.raw"]
	);
	if (ret.type != runp::RS_AC) {
		set_info_if_empty("Standard Program " + runp::rstype_str(ret.type));
		throw jgf_error("STDFAL"); // std failed
	}
	return true;
}

bool TestPoint::grade(const string &input, const string &output, const string &answer) {
	run_checker_result ret = run_checker(
		get_program("chk"),
		fname[input], fname[output], fname[answer]
	);
	if (ret.type != runp::RS_AC) {
		uoj_error("CHKFAL"); // checker failed
		set_info_if_empty("Checker " + runp::rstype_str(ret.type));
		return false;
	} else {
		po.scr = ret.scr;
		set_res_if_empty(ret.info);
		set_info_if_empty("default");
	}
	return true;
}

bool TestPoint::run_custom(const string &id, const string &input, const string &output) {
	if (!program.count(id)) {
		throw jgf_error("PRGNF"); // program not found
	}

	runp::result ret = run_submission_program(
		get_program(id),
		fname[input + ".raw"], fname[output + ".raw"]
	);
	set_res_if_empty(ret.extra);
	if (ret.type != runp::RS_AC) {
		set_info_if_empty(runp::rstype_str(ret.type));
		if (ret.type == runp::RS_JGF) {
			throw jgf_error("RPUSRJGF"); // run_program(user's program) JGF
		}
	} else {
		set_info_if_empty("Success");
		po.scr = 100;
		po.ust = ret.ust;
		po.usm = ret.usm;
	}
	return true;
}

void TestPoint::complete_basic_config() {
	po.num = num;
	if (validate_input_before_test == -1) {
		validate_input_before_test = conf_is("validate_input_before_test", "on");
	}
}

void TestPoint::complete_config() {
	map_add(fauthor, {
		{"input", AUTHOR_SETTER},
		{"output", AUTHOR_USER},
		{"answer", AUTHOR_SETTER}
	});

	map_add(fname, {
		{"input", data_path / conf_input_file_name(num)},
		{"input.plain", work_path / "io" / "plain_input.txt"},
		{"input.raw", work_path / "io" / "raw_input.txt"},
		{"output.raw", work_path / "io" / "raw_output.txt"},
		{"output.plain", work_path / "io" / "plain_output.txt"},
		{"output", work_path / "io" / "output.txt"},
		{"answer", data_path / conf_output_file_name(num)}
	});

	if (get_fname("input_encoder", "cp") == "cp") {
		map_add(ftitle, {
			{"input", "input"},
			{"output.plain", "output"}
		});
	} else {
		map_add(ftitle, {
			{"input", "data_for_input_generation"},
			{"input.plain", "input"},
			{"output.plain", "output"}
		});
	}
}

void TestPoint::complete_hack_config() {
	map_add(fauthor, {
		{"input", AUTHOR_USER},
		{"output", AUTHOR_USER},
		{"answer", AUTHOR_STD}
	});

	map_add(fname, {
		{"input", work_path / "hack_input.txt"},
		{"input.plain", work_path / "io" / "plain_input.txt"},
		{"input.raw", work_path / "io" / "raw_input.txt"},
		{"output.raw", work_path / "io" / "raw_output.txt"},
		{"output.plain", work_path / "io" / "plain_output.txt"},
		{"output", work_path / "pro_output.txt"},
		{"answer.raw", work_path / "io" / "raw_std_output.txt"},
		{"answer.plain", work_path / "io" / "plain_std_output.txt"},
		{"answer", work_path / "std_output.txt"}
	});

	if (get_fname("input_encoder", "cp") == "cp") {
		map_add(ftitle, {
			{"input", "input"},
			{"output.plain", "output"}
		});
	} else {
		map_add(ftitle, {
			{"input", "data_for_input_generation"},
			{"input.plain", "input"},
			{"output.plain", "output"}
		});
	}
}

void TestPoint::complete_custom_test_config() {
	map_add(fauthor, {
		{"input", AUTHOR_USER},
		{"output", AUTHOR_USER}
	});

	map_add(fname, {
		{"input.plain", work_path / "input.txt"},
		{"input.raw", work_path / "io" / "raw_input.txt"},
		{"output.raw", work_path / "io" / "raw_output.txt"},
		{"output.plain", work_path / "io" / "plain_output.txt"}
	});

	if (get_fname("input_encoder", "cp") == "cp") {
		map_add(ftitle, {
			{"input", "input"},
			{"output.plain", "output"}
		});
	} else {
		map_add(ftitle, {
			{"input", "data_for_input_generation"},
			{"input.plain", "input"},
			{"output.plain", "output"}
		});
	}

	map_add(fpreview_size, {
		{"output.plain", 2048}
	});
}

bool TestPoint::_test() {
	uoj_error("TPTSTCAL"); // TestPoint::_test() is called
	po.set_info(runp::rstype_str(runp::RS_JGF));
	return false;
}
bool TestPoint::_hack_test() {
	uoj_error("TPHKTCAL"); // TestPoint::_hack_test() is called
	po.set_info(runp::rstype_str(runp::RS_JGF));
	return false;
}
bool TestPoint::_custom_test() {
	return prepare_io() && add_info_block("input") && encrypt("input")
		&& run_custom("answer", "input", "output") && decrypt("output") && check();
}

class SubmitAnswerTestPoint : public TestPoint {
protected:
	virtual void complete_config() override {
		map_add(fname, {
			{"input", data_path / conf_input_file_name(num)},
			{"input.plain", data_path / conf_input_file_name(num)},
			{"output.plain", work_path / conf_output_file_name(num)},
			{"output", work_path / conf_output_file_name(num)},
			{"answer", data_path / conf_output_file_name(num)}
		});
		TestPoint::complete_config();
	}

	virtual bool _test() override {
		return prepare_io() && add_info_block("input") && encode("input") && check()
			&& decode("output") && check()
			&& grade("input", "output", "answer") && check();
	}
};

class SingleProgramTestPoint : public TestPoint {
protected:
	virtual bool run() {
		if (!program.count("answer")) {
			uoj_error("PRGNF"); // program not found
			set_info_if_empty(runp::rstype_str(runp::RS_JGF));
			return false;
		}

		runp::result ret = run_submission_program(
			get_program("answer"),
			fname["input.raw"],
			fname["output.raw"]
		);
		if (ret.type != runp::RS_AC) {
			set_info_if_empty(runp::rstype_str(ret.type));
			set_res_if_empty(ret.extra);
			if (ret.type == runp::RS_JGF) {
				uoj_error("RPUSRJGF"); // run_program(user's program) JGF
				return false;
			}
		} else {
			po.ust = ret.ust;
			po.usm = ret.usm;
		}
		return true;
	}

	virtual bool _test() override {
		return prepare_io() && add_info_block("input") && preprocess_input("input")
			&& run() && postprocess_output("output")
			&& grade("input", "output", "answer") && check();
	}

	virtual bool _hack_test() override {
		return prepare_io() && add_info_block("input") && preprocess_input("input")
			&& generate_answer("input", "answer") && postprocess_output("answer")
			&& run() && postprocess_output("output")
			&& grade("input", "output", "answer") && check();
	}
};

class SimpleInteractionTestPoint : public SingleProgramTestPoint {
public:
	enum {
		AUTHOR_INTERACTOR = 1 << 4
	};

protected:
	virtual void complete_config() override {
		map_add(fauthor, {
			{"program_input", AUTHOR_INTERACTOR}
		});
		map_add(author_name, {
			{AUTHOR_INTERACTOR, "Interactor"}
		});
		map_add(fname, {
			{"program_input.raw", work_path / "io" / "raw_program_input.txt"},
			{"program_input.plain", work_path / "io" / "plain_program_input.txt"}
		});

		if (get_fname("input_encoder", "cp") == "cp") {
			map_add(ftitle, {
				{"input", "input_to_interactor"},
				{"input.plain", TITLE_DISABLED},
				{"program_input.plain", "input"}
			});
		}
		SingleProgramTestPoint::complete_config();
	}

	virtual bool run() {
		if (!program.count("answer") || !program.count("interactor")) {
			uoj_error("PRGNF"); // program not found
			set_info_if_empty(runp::rstype_str(runp::RS_JGF));
			return false;
		}

		// conf_run_limit(num, RL_DEFAULT),
		// conf_run_limit("interactor", num, RL_INTERACTOR_DEFAULT),
		run_simple_interaction_result rires = run_simple_interaction(
			fname["input.raw"],
			fname["answer"],
			fname["program_input.raw"],
			fname["output.raw"],
			get_program("answer"),
			get_program("interactor")
		);

		if (rires.ires.type != runp::RS_AC) {
			uoj_error("INTFAL"); // interactor failed
			set_info_if_empty("Interactor " + runp::rstype_str(rires.ires.type));
			return false;
		} else if (rires.res.type != runp::RS_AC) {
			set_info_if_empty(runp::rstype_str(rires.res.type));
			if (rires.res.type == runp::RS_JGF) {
				uoj_error("RPUSRJGF"); // run_program(user's program) JGF
				return false;
			}
		} else {
			po.scr = rires.ires.scr;
			po.ust = rires.res.ust;
			po.usm = rires.res.usm;
			po.res = rires.ires.info;
			po.set_info("default");
		}
		return true;
	}

	virtual bool run_std() {
		// ???
		return true;
	}

	virtual bool _test() override {
		return prepare_io() && add_info_block("input") && preprocess_input("input")
			&& run() && decrypt("program_input") && decrypt("output") && check();
	}

	virtual bool _hack_test() override {
		return prepare_io() && add_info_block("input") && preprocess_input("input")
			// && run_std()
			&& run() && decrypt("program_input") && decrypt("output") && check();
	}
};

/*
class TwoRoundTestPoint : public TestPoint {
public:
	string alice_name = "alice";
	string bob_name = "bob";

protected:
	virtual void complete_config() override {
		TestPoint::complete_config();
	}

	virtual bool transport() {
		return true;
	}

	virtual bool run_alice() {
		return true;
	}
	
	virtual bool run_bob() {
		return true;
	}

	virtual bool _test() override {
		return prepare_io() && prepare_input("input")
			&& run_alice() && decrypt("alice_output") && check()
			&& decode("alice_output") && check()
			&& transport()
			&& prepare_input("bob_input")
			&& run_bob() && decrypt("bob_output") && check()
			&& decode("bob_output") && check()
			&& grade() && check();
	}
};*/

/*
PointInfo test_point(const string &name, const int &num, TestPointConfig tpc = TestPointConfig()) {
	tpc.auto_complete(num);

	if (tpc.validate_input_before_test) {
		RunValidatorResult val_ret = run_validator(
				tpc.input_file_name,
				conf_run_limit("validator", 0, RL_VALIDATOR_DEFAULT),
				conf_str("validator"));
		if (val_ret.type != RS_AC) {
			return PointInfo(num, 0, -1, -1,
					"Validator " + runp::rstype_str(val_ret.type),
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
		runp::result pro_ret;
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
						runp::rstype_str(pro_ret.type),
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
					"Checker " + runp::rstype_str(chk_ret.type),
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
		RunSimpleInteractionResult rires = run_simple_interaction(
				tpc.input_file_name,
				tpc.answer_file_name,
				real_input_file_name,
				real_output_file_name,
				conf_run_limit(num, RL_DEFAULT),
				conf_run_limit("interactor", num, RL_INTERACTOR_DEFAULT),
				name);

		if (rires.ires.type != RS_AC) {
			return PointInfo(num, 0, -1, -1,
					"Interactor " + runp::rstype_str(rires.ires.type),
					file_preview(real_input_file_name), file_preview(real_output_file_name),
					"");
		}
		if (rires.res.type != RS_AC) {
			return PointInfo(num, 0, -1, -1,
					runp::rstype_str(rires.res.type),
					file_preview(real_input_file_name), file_preview(real_output_file_name),
					"");
		}

		return PointInfo(num, rires.ires.scr, rires.res.ust, rires.res.usm, 
				"default",
				file_preview(real_input_file_name), file_preview(real_output_file_name),
				rires.ires.info);
	}
}*/

/*
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
				"Validator " + runp::rstype_str(val_ret.type),
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
		runp::config rpc;
		rpc.result_file_name = result_path + "/run_standard_program.txt";
		runp::result std_ret = run_submission_program(
				tpc.input_file_name,
				tpc.answer_file_name,
				conf_run_limit("standard", 0, default_std_run_limit),
				"std",
				rpc);
		if (std_ret.type != RS_AC) {
			return PointInfo(0, 0, -1, -1,
					"Standard Program " + runp::rstype_str(std_ret.type),
					file_preview(tpc.input_file_name), "",
					"");
		}
		if (conf_has("token")) {
			file_hide_token(tpc.answer_file_name, conf_str("token", ""));
		}
	} else {
		runp::config rpc;
		rpc.result_file_name = result_path + "/run_standard_program.txt";
		string real_output_file_name = tpc.answer_file_name;
		string real_input_file_name = tpc.output_file_name + ".real_output.txt";
		RunSimpleInteractionResult rires = run_simple_interaction(
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
					"Interactor " + runp::rstype_str(rires.ires.type) + " (Standard Program)",
					file_preview(real_input_file_name), "",
					"");
		}
		if (rires.res.type != RS_AC) {
			return PointInfo(0, 0, -1, -1,
					"Standard Program " + runp::rstype_str(rires.res.type),
					file_preview(real_input_file_name), "",
					"");
		}
	}

	PointInfo po = test_point(name, 0, tpc);
	po.scr = po.scr != 100;
	return po;
}*/

/*
CustomTestInfo ordinary_custom_test(const string &name) {
	RunLimit lim = conf_run_limit(0, RL_DEFAULT);
	lim.time += 2;

	string input_file_name = work_path + "/input.txt";
	string output_file_name = work_path + "/output.txt";

	esult pro_ret = run_submission_program(
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
		info = runp::rstype_str(pro_ret.type);
	}
	string exp;
	if (pro_ret.type == RS_TLE) {
		exp = "<p>[<strong>time limit:</strong> " + vtos(lim.time) + "s]</p>";
	}
	return CustomTestInfo(pro_ret.ust, pro_ret.usm, 
			info, exp, file_preview(output_file_name, 2048));
}
*/

int scale_score(int scr100, int full) {
	return scr100 * full / 100;
}

/*======================  test End   ================== */

/*======================    judger   ================== */

struct SubtaskInfo {
	bool passed;
	int score;

	SubtaskInfo() {
	}
	SubtaskInfo(const bool &_p, const int &_s)
		: passed(_p), score(_s){}
};

class Judger {
protected:
	map<string, runp::config> program;

	bool add_program(const string &id, const runp::config &candidate) {
		if (program.count(id)) {
			return false;
		}
		if (candidate.type == "internal") {
			program[id] = candidate;
			return true;
		}
		if (fs::exists(candidate.program_name)) {
			program[id] = candidate;
			return true;
		}
		return false;
	}

	bool compile_and_add_program(const string &name) {
		run_compiler_result c_ret = !conf_is("with_implementer", "on") ? compile("answer") : compile_with_implementer("answer");
		if (!c_ret.succeeded) {
			end_judge_compile_error(c_ret);
		}

		runp::config rpc("./" + name);
		rpc.set_type(runp::get_type_from_lang(conf_str(name + "_language")));
		add_program(name, rpc);
		return true;
	}

	void add_readable_to_program(runp::config &rpc) {
		int p = 1;
		while (true) {
			string fname = conf_str("readable", p, "");
			if (fname.empty()) {
				break;
			}
			if (fname[0] != '/') {
				fname = work_path / fname;
			}
			rpc.readable_file_names.push_back(fname);
			p++;
		}
	}

	void add_writable_to_program(runp::config &rpc) {
		int p = 1;
		while (true) {
			string fname = conf_str("writable", p, "");
			if (fname.empty()) {
				break;
			}
			if (fname[0] != '/') {
				fname = work_path / fname;
			}
			rpc.writable_file_names.push_back(fname);
			p++;
		}
	}

	virtual void configure_programs_for_point(int num) {
		if (program.count("std")) {
			program["std"].limits = conf_run_limit("standard", num, conf_run_limit(num, RL_DEFAULT));
		}
		if (program.count("chk")) {
			program["chk"].limits = conf_run_limit("checker", num, RL_CHECKER_DEFAULT);
		}
		if (program.count("val")) {
			program["val"].limits = conf_run_limit("validator", num, RL_VALIDATOR_DEFAULT);
		}

		for (auto &name : {"encoder", "encrypter", "decrypter", "decoder"}) {
			if (program.count(name)) {
				program[name].limits = conf_run_limit(name, num, RL_TRANSFORMER_DEFAULT);
			}
		}
	}

	virtual void prepare() {
		if (conf_has("use_builtin_checker")) {
			add_program("chk", runp::config(main_path / "builtin" / "checker" / conf_str("use_builtin_checker")));
		}

		// search for chk, val, std, encoder, decoder, etc.
		for (auto &p : fs::directory_iterator(data_path)) {
			if ((p.status().permissions() & fs::perms::others_exec) != fs::perms::none) {
				add_program(p.path().filename(), runp::config(p.path()));
			}
		}

		add_program("encoder", runp::config("cp").set_type("internal"));
		add_program("encrypter", runp::config("cp").set_type("internal"));
		if (conf_has("token")) {
			add_program("decrypter", runp::config("hide_token").set_type("internal"));
		} else {
			add_program("decrypter", runp::config("cp").set_type("internal"));
		}
		add_program("decoder", runp::config("cp").set_type("internal"));

		for (auto &kv : program) {
			add_readable_to_program(kv.second);
			add_writable_to_program(kv.second);
		}
	}

	virtual void prepare_ordinary_test() {
	}

	virtual void prepare_sample_test() {
	}

	virtual void prepare_hack_test() {
	}

	virtual void prepare_custom_test() {
	}

	virtual PointInfo test_point(int num) {
		end_judge_judgment_failed(runp::rstype_str(runp::RS_JGF));
	}

	virtual PointInfo test_sample_point(int num) {
		end_judge_judgment_failed("Sample test is not supported in this problem.");
	}

	virtual PointInfo test_hack_point() {
		end_judge_judgment_failed("Hack is not supported in this problem.");
	}

	virtual PointInfo test_custom_point() {
		end_judge_judgment_failed("Custom test is not supported in this problem.");
	}

public:
	virtual int n_tests() {
		return conf_int("n_tests", 10);
	}

	virtual int n_ex_tests() {
		return conf_int("n_ex_tests", 0);
	}

	virtual int n_sample_tests() {
		return conf_int("n_sample_tests", 0);
	}

	virtual int sample_test_point_score(int num) {
		return 100 / this->n_sample_tests();
	}

	virtual bool is_hack_enabled() {
		return true;
	}

	virtual bool is_custom_test_enabled() {
		return true;
	}

	virtual void ordinary_test() {
		int n = conf_int("n_tests", 10);
		int m = this->n_ex_tests();
		int nT = conf_int("n_subtasks", 0);

		this->prepare();
		this->prepare_ordinary_test();

		bool passed = true;
		if (nT == 0) { // OI
			for (int i = 1; i <= n; i++) {
				report_judge_status_f("Judging Test #%d", i);
				PointInfo po = this->test_point(i);
				if (po.scr != 100) {
					passed = false;
				}
				po.scr = scale_score(po.scr, conf_int("point_score", i, 100 / n));
				add_point_info(po);
			}
		} else if (nT == 1 && conf_str("subtask_type", 1, "packed") == "packed") { // ACM
			for (int i = 1; i <= n; i++) {
				report_judge_status_f("Judging Test #%d", i);
				PointInfo po = this->test_point(i);
				if (po.scr != 100) {
					passed = false;
					po.scr = i == 1 ? 0 : -100;
					add_point_info(po);
					break;
				} else {
					po.scr = i == 1 ? 100 : 0;
					add_point_info(po);
				}
			}
		} else { // subtask
			map<int, SubtaskInfo> subtasks;
			map<int,int> minScore;
			for (int t = 1; t <= nT; t++) {
				string subtaskType = conf_str("subtask_type", t, "packed");
				int startI = conf_int("subtask_end", t - 1, 0) + 1;
				int endI = conf_int("subtask_end", t, 0);

				vector<PointInfo> points;
				minScore[t] = 100;

				vector<int> dependences;
				if (conf_str("subtask_dependence", t, "none") == "many") {
					string cur = "subtask_dependence_" + to_string(t);
					int p = 1;
					while (conf_int(cur, p, 0) != 0) {
						dependences.push_back(conf_int(cur, p, 0));
						p++;
					}
				} else if (conf_int("subtask_dependence", t, 0) != 0) {
					dependences.push_back(conf_int("subtask_dependence", t, 0));
				}
				bool skipped = false;
				for (vector<int>::iterator it = dependences.begin(); it != dependences.end(); it++) {
					if (subtaskType == "packed") {
						if (!subtasks[*it].passed) {
							skipped = true;
							break;
						}
					} else if (subtaskType == "min") {
						minScore[t] = min(minScore[t], minScore[*it]);
						if (minScore[t] == 0) {
							skipped = true;
							break;
						}
					}
				}
				if (skipped) {
					add_subtask_info(t, 0, "Skipped", points);
					continue;
				}

				int tfull = conf_int("subtask_score", t, 100 / nT);
				int tscore = scale_score(minScore[t], tfull);
				string info = "Accepted";
				for (int i = startI; i <= endI; i++) {
					report_judge_status_f("Judging Test #%d of Subtask #%d", i, t);
					PointInfo po = this->test_point(i);
					if (subtaskType == "packed") {
						if (po.scr != 100) {
							passed = false;
							po.scr = i == startI ? 0 : -tfull;
							tscore = 0;
							points.push_back(po);
							info = po.info;
							break;
						} else {
							po.scr = i == startI ? tfull : 0;
							tscore = tfull;
							points.push_back(po);
						}
					} else if (subtaskType == "min") {
						minScore[t] = min(minScore[t], po.scr);
						if (po.scr != 100) {
							passed = false;
						}
						po.scr = scale_score(po.scr, tfull);
						if (po.scr <= tscore) {
							tscore = po.scr;
							points.push_back(po);
							info = po.info;
							if (tscore == 0) {
								break;
							}
						} else {
							points.push_back(po);
						}
					}
				}

				subtasks[t] = SubtaskInfo(info == "Accepted", tscore);

				add_subtask_info(t, tscore, info, points);
			}
		}
		if (!passed) {
			end_judge_ok();
		}

		tot_score = 100;
		for (int i = 1; i <= m; i++) {
			report_judge_status_f("Judging Extra Test #%d", i);
			PointInfo po = this->test_point(-i);
			if (po.scr != 100) {
				po.num = -1;
				po.info = "Extra Test Failed : " + po.info + " on " + to_string(i);
				po.scr = -3;
				add_point_info(po);
				end_judge_ok();
			}
		}
		if (m != 0) {
			add_point_info(PointInfo::extra_test_passed());
		}
		end_judge_ok();
	}

	virtual void hack_test() {
		if (!this->is_hack_enabled()) {
			end_judge_judgment_failed("Hack is not supported in this problem.");
		} else {
			this->prepare();
			this->prepare_hack_test();
			add_point_info(this->test_hack_point());
			end_judge_ok();
		}
	}

	virtual void sample_test() {
		this->prepare();
		this->prepare_sample_test();
		int n = this->n_sample_tests();
		bool passed = true;
		for (int i = 1; i <= n; i++) {
			report_judge_status_f("Judging Sample Test #%d", i);
			PointInfo po = this->test_sample_point(i);
			if (po.scr != 100) {
				passed = false;
			}
			po.scr = scale_score(po.scr, this->sample_test_point_score(i));
			add_point_info(po);
		}
		if (passed) {
			tot_score = 100;
		}
		end_judge_ok();
	}

	virtual void custom_test() {
    	if (!is_custom_test_enabled()) {
			end_judge_judgment_failed("Custom test is not supported in this problem.");
		} else {
			this->prepare();
			this->prepare_custom_test();

			report_judge_status_f("Judging");
			add_custom_test_info(this->test_custom_point());
			
			end_judge_ok();
		}
	}
	
	virtual void judge() {
		if (conf_is("test_new_hack_only", "on")) {
			this->hack_test();
		} else if (conf_is("test_sample_only", "on")) {
			this->sample_test();
		} else if (conf_is("custom_test", "on")) {
			this->custom_test();
		} else {
			this->ordinary_test();
		}
	}
};

template<typename TP>
class OrdinaryJudger : public Judger {
protected:
	virtual void configure_programs_for_point(int num) {
		program["answer"].limits = conf_run_limit(num, RL_DEFAULT);
		Judger::configure_programs_for_point(num);
	}

	virtual void prepare() override {
		report_judge_status_f("Compiling");
		compile_and_add_program("answer");
		Judger::prepare();
	}

	virtual PointInfo test_point(int num) override {
		TP tp;
		tp.num = num;
		Judger::configure_programs_for_point(num);
		tp.program = program;
		return tp.test();
	}

	virtual PointInfo test_sample_point(int num) override {
		PointInfo po = this->test_point(-num);
		po.num = num;
		return po;
	}

	virtual PointInfo test_hack_point() override {
		TP tp;
		tp.num = 0;
		tp.validate_input_before_test = true;
		Judger::configure_programs_for_point(0);
		tp.program = program;
		return tp.hack_test();
	}

	virtual PointInfo test_custom_point() override {
		TP tp;
		tp.num = 0;
		Judger::configure_programs_for_point(0);
		tp.program = program;
		tp.program["answer"].limits.time += 2;
		return tp.custom_test();
	}
};

class SubmitAnswerJudger : public Judger {
protected:
	virtual PointInfo test_point(int num) override {
		SubmitAnswerTestPoint tp;
		tp.num = num;
		Judger::configure_programs_for_point(num);
		tp.program = program;
		return tp.test();
	}

	virtual PointInfo test_sample_point(int num) override {
		if (conf_is("check_existence_only_in_sample_test", "on")) {
			SubmitAnswerTestPoint tp;
			tp.num = num;
			tp.program = program;
			tp.program["chk"] = runp::config("nonempty").set_type("internal");
			Judger::configure_programs_for_point(num);
			return tp.test();
		} else {
			PointInfo po = this->test_point(num);
			if (po.scr != 0) {
				po.info = "Accepted";
				po.scr = 100;
			}
			po.res = "no comment";
			return po;
		}
	}

public:
	virtual int n_ex_tests() override {
		return 0;
	}

	virtual int n_sample_tests() override {
		return this->n_tests();
	}

	virtual int sample_test_point_score(int num) override {
		return conf_int("point_score", num, 100 / this->n_sample_tests());
	}

	virtual bool is_hack_enabled() override {
		return false;
	}

	virtual bool is_custom_test_enabled() override {
		return false;
	}
};

/*====================== judger end  ================== */

/*======================= conf init =================== */

void main_judger_init(int argc, char **argv)  {
	try {
		main_path = fs::read_symlink("/proc/self/exe").parent_path();
		work_path = fs::current_path() / "work";
		result_path = fs::current_path() / "result";
		load_config(work_path / "submission.conf");
		data_path = main_path / "data" / conf_str("problem_id");
		load_config(data_path / "problem.conf");

		if (fs::is_directory(data_path / "require")) {
			fs::copy(data_path / "require", work_path, fs::copy_options::update_existing | fs::copy_options::recursive);
		}

		if (conf_is("use_builtin_judger", "on")) {
			conf_add("judger", main_path / "builtin" / "judger" / "judger");
		} else {
			conf_add("judger", data_path / "judger");
		}

		runp::run_path = main_path / "run";

	} catch (exception &e) {
		cerr << e.what() << endl;
		exit(1);
	}
}
void judger_init(int argc, char **argv) {
	if (argc != 5) {
		cerr << "judger: argc != 5" << endl;
		exit(1);
	}
	main_path = argv[1];
	work_path = argv[2];
	result_path = argv[3];
	data_path = argv[4];
	load_config(work_path / "submission.conf");
	load_config(data_path / "problem.conf");
	runp::run_path = main_path / "run";

	fs::current_path(work_path);
}

/*===================== conf init End ================= */

/*===================== default judger ================ */

int default_judger_main(int argc, char **argv) {
	judger_init(argc, argv);

	Judger *judger;
	if (conf_is("submit_answer", "on")) {
		judger = new SubmitAnswerJudger();
	} else if (conf_is("interaction_mode", "on")) {
		judger = new OrdinaryJudger<SimpleInteractionTestPoint>();
	} else {
		judger = new OrdinaryJudger<SingleProgramTestPoint>();
	}
	judger->judge();

	return -1; // error
}

/*===================== default judger End ============ */