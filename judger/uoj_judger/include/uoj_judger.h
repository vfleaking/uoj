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
#include <sys/stat.h>
#include <sys/wait.h>

#include "uoj_secure.h"
#include "uoj_run.h"

namespace fs = std::filesystem;
using namespace std;

/*========================== string ====================== */

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

/*========================== execute ====================== */

string realpath(const string &path) {
	char real[PATH_MAX + 1];
	if (realpath(path.c_str(), real) == NULL) {
		return "";
	}
	return real;
}

/*======================== execute End ==================== */

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
int file_size(const string &name) {
	struct stat st;
	stat(name.c_str(), &st);
	return (int)st.st_size;
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
void file_replace_tokens(const string &name, const string &token, const string &new_token) {
	string esc_name = escapeshellarg(name);
	string esc_token = escapeshellarg(token);
	string esc_new_token = escapeshellarg(new_token);
	executef("sed -i s/%s/%s/g %s", esc_token.c_str(), esc_new_token.c_str(), esc_name.c_str());
}
void file_copy(const string &a, const string &b) { // copy a to b
	string esc_a = escapeshellarg(a);
	string esc_b = escapeshellarg(b);
	executef("cp %s %s -f", esc_a.c_str(), esc_b.c_str()); // the most cubao implementation in the world
}
void file_move(const string &a, const string &b) { // move a to b
	string esc_a = escapeshellarg(a);
	string esc_b = escapeshellarg(b);
	executef("mv %s %s", esc_a.c_str(), esc_b.c_str()); // the most cubao implementation in the world
}

/*======================= file End ==================== */

/*====================== parameter ==================== */

typedef runp::limits_t RunLimit;
typedef runp::result RunResult;

const RunLimit RL_DEFAULT = RunLimit(1, 256, 64);
const RunLimit RL_GENERATOR_DEFAULT = RunLimit(2, 512, 64);
const RunLimit RL_JUDGER_DEFAULT = RunLimit(600, 2048, 128);  // 2048 = 2GB. change it if needed
const RunLimit RL_CHECKER_DEFAULT = RunLimit(5, 256, 64);
const RunLimit RL_INTERACTOR_DEFAULT = RunLimit(1, 256, 64);
const RunLimit RL_VALIDATOR_DEFAULT = RunLimit(5, 256, 64);
const RunLimit RL_COMPILER_DEFAULT = RunLimit(15, 512, 64);

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

	static InfoBlock from_file(const string &title, const string &name) {
		InfoBlock info;
		info.title = title;
		info.content = file_preview(name);
		info.orig_size = -1;
		return info;
	}
	static InfoBlock from_file_with_size(const string &title, const string &name) {
		InfoBlock info;
		info.title = title;
		info.content = file_preview(name);
		info.orig_size = file_size(name);
		return info;
	}

	string to_str() const {
		if (title == "in") {
			return "<in>" + htmlspecialchars(content) + "</in>";
		}
		if (title == "out") {
			return "<out>" + htmlspecialchars(content) + "</out>";
		}
		if (title == "res") {
			return "<res>" + htmlspecialchars(content) + "</res>";
		}

		string str = "<info-block";
		str += " title=\"" + title + "\"";
		if (orig_size != -1) {
			str += " size=\"" + vtos(orig_size) + "\"";
		}
		str += ">" + htmlspecialchars(content) + "</info-block>";

		return str;
	}
};

int scale_score(int scr100, int full) {
	return scr100 * full / 100;
}

struct PointInfo {
	static bool show_in;
	static bool show_out;
	static bool show_res;

	int num;
	int scr;
	int ust, usm;
	string info, in, out, res;

	bool use_li;
	vector<InfoBlock> li;

	PointInfo(const int &_num, const int &_scr,
			const int &_ust, const int &_usm, const string &_info = "default")
			: num(_num), scr(_scr),
			ust(_ust), usm(_usm), info(_info) {
		use_li = true;
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

	PointInfo(const int &_num, const int &_scr,
			const int &_ust, const int &_usm, const string &_info,
			const string &_in, const string &_out, const string &_res)
			: num(_num), scr(_scr),
			ust(_ust), usm(_usm), info(_info),
			in(_in), out(_out), res(_res) {
		use_li = false;
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

	friend inline ostream& operator<<(ostream &out, const PointInfo &info) {
		out << "<test num=\"" << info.num << "\""
			<< " score=\"" << info.scr << "\""
			<< " info=\"" << htmlspecialchars(info.info) << "\""
			<< " time=\"" << info.ust << "\""
			<< " memory=\"" << info.usm << "\">" << endl;
		if (!info.use_li) {
			if (PointInfo::show_in) {
				out << "<in>" << htmlspecialchars(info.in) << "</in>" << endl;
			}
			if (PointInfo::show_out) {
				out << "<out>" << htmlspecialchars(info.out) << "</out>" << endl;
			}
			if (PointInfo::show_res) {
				out << "<res>" << htmlspecialchars(info.res) << "</res>" << endl;
			}
		} else {
			for (const auto &b : info.li) {
				if (b.title == "in" && !PointInfo::show_in) {
					continue;
				}
				if (b.title == "out" && !PointInfo::show_out) {
					continue;
				}
				if (b.title == "res" && !PointInfo::show_res) {
					continue;
				}
				out << b.to_str() << endl;
			}
		}
		out << "</test>" << endl;
		return out;
	}
};

bool PointInfo::show_in = true;
bool PointInfo::show_out = true;
bool PointInfo::show_res = true;

struct CustomTestInfo  {
	int ust, usm;
	string info, exp, out;

	CustomTestInfo(const int &_ust, const int &_usm, const string &_info,
			const string &_exp, const string &_out)
			: ust(_ust), usm(_usm), info(_info),
			exp(_exp), out(_out) {
	}
};

struct SubtaskMetaInfo {
	int num;
	vector<int> points_id;
	string subtask_type;
	string subtask_used_time_type;
	vector<int> subtask_dependencies;
	int full_score;

	inline bool is_ordinary() {
		return subtask_type == "packed" && subtask_used_time_type == "sum";
	}
};

struct SubtaskInfo {
	SubtaskMetaInfo meta;

	bool passed = true;
	bool early_stop = false;
	int scr, ust = 0, usm = 0;
	string info = "Accepted";
	int unrescaled_min_score = 100;
	vector<PointInfo> points;

	SubtaskInfo() = default;
	SubtaskInfo(const SubtaskMetaInfo &meta) : meta(meta) {
		scr = meta.full_score;
	}

	inline void update_stats(int _ust, int _usm) {
		if (_ust >= 0) {
			if (meta.subtask_used_time_type == "max") {
				ust = max(ust, _ust);
			} else {
				ust += _ust;
			}
		}
		if (_usm >= 0) {
			usm = max(usm, _usm);
		}
	}

	inline bool resolve_dependencies(const map<int, SubtaskInfo> &subtasks) {
		for (const auto &p : meta.subtask_dependencies) {
			const auto &dep = subtasks.at(p);
			if (meta.subtask_type == "packed") {
				if (!dep.passed) {
					passed = false;
					scr = 0;
				}
			} else if (meta.subtask_type == "min") {
				unrescaled_min_score = min(unrescaled_min_score, dep.unrescaled_min_score);
				scr = scale_score(unrescaled_min_score, meta.full_score);
				if (!dep.passed) {
					passed = false;
					info = "Acceptable Answer";
				}
			}
			if (scr == 0) {
				return false;
			}
		}
		return true;
	}

	inline void add_point_info(PointInfo po) {
		unrescaled_min_score = min(unrescaled_min_score, po.scr);
		update_stats(po.ust, po.usm);

		if (meta.subtask_type == "packed") {
			if (po.scr != 100) {
				passed = false;
				early_stop = true;
				po.scr = points.empty() ? 0 : -meta.full_score;
				scr = 0;
				info = po.info;
			} else {
				po.scr = points.empty() ? meta.full_score : 0;
				scr = meta.full_score;
			}
		} else if (meta.subtask_type == "min") {
			if (po.scr != 100) {
				passed = false;
			}
			po.scr = scale_score(po.scr, meta.full_score);
			if (po.scr <= scr) {
				scr = po.scr;
				info = po.info;
				if (meta.full_score != 0 && scr == 0) {
					early_stop = true;
				}
			}
		}
		points.push_back(po);
	}

	friend inline ostream& operator<<(ostream &out, const SubtaskInfo &st_info) {
		out << "<subtask num=\"" << st_info.meta.num << "\""
			<< " score=\"" << st_info.scr << "\""
			<< " info=\"" << htmlspecialchars(st_info.info) << "\""
			<< " time=\"" << st_info.ust << "\""
			<< " memory=\"" << st_info.usm << "\""
			<< " type=\"" << st_info.meta.subtask_type << "\""
			<< " used-time-type=\"" << st_info.meta.subtask_used_time_type << "\""
			<< ">" << endl;
		for (const auto &info : st_info.points) {
			out << info;
		}
		out << "</subtask>" << endl;
		return out;
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

		if (rres.type != runp::RS_AC) {
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
		res.type = runp::RS_JGF;
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
		res.type = runp::RS_JGF;
		res.ust = -1;
		res.usm = -1;
		res.succeeded = 0;
		res.info = "Validator Judgment Failed";
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
typedef run_compiler_result RunCompilerResult;

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
map<string, string> config;

/*==================== parameter End ================== */

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
string conf_file_name_with_num(string s, int num) {
	ostringstream name;
	if (num < 0) {
		name << "ex_";
	}
	name << conf_str(s + "_pre", s) << abs(num) << "." << conf_str(s + "_suf", "txt");
	return name.str();
}
string conf_input_file_name(int num) {
	// return conf_file_name_with_num("input", num):
	ostringstream name;
	if (num < 0) {
		name << "ex_";
	}
	name << conf_str("input_pre", "input") << abs(num) << "." << conf_str("input_suf", "txt");
	return name.str();
}
string conf_output_file_name(int num) {
	// return conf_file_name_with_num("output", num):
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
runp::limits_t conf_run_limit(const int &num, const RunLimit &val) {
	return conf_run_limit("", num, val);
}
SubtaskMetaInfo conf_subtask_meta_info(string pre, const int &num) {
	if (!pre.empty()) {
		pre += "_";
	}

	int nT = conf_int(pre + "n_subtasks", 0);

	SubtaskMetaInfo meta;
	meta.num = num;

	int startI = conf_int(pre + "subtask_end", num - 1, 0) + 1;
	int endI = num < nT ? conf_int(pre + "subtask_end", num, 0) : conf_int(pre + "n_tests", 10);
	for (int i = startI; i <= endI; i++) {
		meta.points_id.push_back(i);
	}

	meta.subtask_type = conf_str(pre + "subtask_type", num, "packed");
	meta.subtask_used_time_type = conf_str(pre + "subtask_used_time_type", num, "sum");
	meta.full_score = conf_int(pre + "subtask_score", num, 100 / nT);
	if (conf_str("subtask_dependence", num, "none") == "many") {
		string cur = "subtask_dependence_" + vtos(num);
		int p = 1;
		while (conf_int(cur, p, 0) != 0) {
			meta.subtask_dependencies.push_back(conf_int(cur, p, 0));
			p++;
		}
	} else if (conf_int("subtask_dependence", num, 0) != 0) {
		meta.subtask_dependencies.push_back(conf_int("subtask_dependence", num, 0));
	}
	return meta;
}
SubtaskMetaInfo conf_subtask_meta_info(const int &num) {
	return conf_subtask_meta_info("", num);
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

/*====================== info print =================== */

inline string info_str(int id)  {
	return runp::rstype_str((runp::RS_TYPE)id);
}
inline string info_str(const RunResult &p)  {
	return info_str(p.type);
}

void add_point_info(const PointInfo &info, bool update_tot_score = true) {
	if (info.num >= 0) {
		if (info.ust >= 0) {
			tot_time += info.ust;
		}
		if (info.usm >= 0) {
			max_memory = max(max_memory, info.usm);
		}
	}
	if (update_tot_score) {
        tot_score += info.scr;
	}

	details_out << info;
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
void add_subtask_info(const SubtaskInfo &st_info) {
	if (st_info.ust >= 0) {
		tot_time += st_info.ust;
	}
	if (st_info.usm >= 0) {
		max_memory = max(max_memory, st_info.usm);
	}
	tot_score += st_info.scr;
	details_out << st_info;
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
	exit(0);
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

/*========================== run ====================== */

struct RunProgramConfig {
	vector<string> readable_file_names; // other than stdin
	vector<string> writable_file_names; // other than stdout, stderr
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

	void set_submission_program_name(const string &name) {
		string lang = conf_str(name + "_language");
		type = "default";
		if (lang == "Python2.7") {
			type = "python2.7";
		} else if (lang == "Python3") {
			type = "python3";
		} else if (lang == "Java7") {
			type = "java7";
		} else if (lang == "Java8") {
			type = "java8";
		} else if (lang == "Java11") {
			type = "java11";
		} else if (lang == "Java14") {
			type = "java14";
		}
		set_argv(name.c_str(), NULL);
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
		for (vector<string>::const_iterator it = writable_file_names.begin(); it != writable_file_names.end(); it++) {
			sout << " " << "--add-writable=" << escapeshellarg(*it);
		}
		for (vector<string>::const_iterator it = argv.begin(); it != argv.end(); it++) {
			sout << " " << escapeshellarg(*it);
		}
		return sout.str();
	}
};

typedef runp::interaction::pipe_config PipeConfig;
typedef runp::interaction::config RunInteractionConfig;

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

	if (execute(sout.str()) != 0) {
		return RunResult::failed_result();
	}
	return RunResult::from_file(run_program_result_file_name);
}

RunResult run_program(const RunProgramConfig &rpc) {
	if (execute(rpc.get_cmd()) != 0) {
		return RunResult::failed_result();
	}
	return RunResult::from_file(rpc.result_file_name);
}

RunResult run_program(const runp::config &rpc) {
	if (execute(rpc.get_cmd()) != 0) {
		return RunResult::failed_result();
	}
	return RunResult::from_file(rpc.result_file_name);
}

// @return interaction return value
int run_interaction(const RunInteractionConfig &ric) {
	return runp::interaction::run(ric);
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
			("--type=" + conf_str("val_run_type", "default")).c_str(),
			program_name.c_str(),
			NULL);

	RunValidatorResult res;
	res.type = ret.type;
	res.ust = ret.ust;
	res.usm = ret.usm;

	if (ret.type != runp::RS_AC || ret.exit_code != 0) {
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
			(result_path + "/run_checker_result.txt").c_str(),
			"/dev/null",
			"/dev/null",
			(result_path + "/checker_error.txt").c_str(),
			limit,
			("--add-readable=" + input_file_name).c_str(),
			("--add-readable=" + output_file_name).c_str(),
			("--add-readable=" + answer_file_name).c_str(),
			("--type=" + conf_str("chk_run_type", "default")).c_str(),
			program_name.c_str(),
			realpath(input_file_name).c_str(),
			realpath(output_file_name).c_str(),
			realpath(answer_file_name).c_str(),
			NULL);

	return RunCheckerResult::from_file(result_path + "/checker_error.txt", ret);
}

template <typename... Args>
run_compiler_result run_compiler(runp::config rpc) {
	rpc.result_file_name = result_path + "/run_compiler_result.txt";
	rpc.input_file_name = "/dev/null";
	rpc.output_file_name = "stderr";
	rpc.error_file_name = result_path + "/compiler_result.txt";
	rpc.type = "compiler";

	runp::result ret = run_program(rpc);
	run_compiler_result res;
	res.type = ret.type;
	res.succeeded = ret.type == runp::RS_AC && ret.exit_code == 0;
	if (!res.succeeded) {
		if (ret.type == runp::RS_AC) {
			res.info = file_preview(result_path + "/compiler_result.txt", 10240);
		} else if (ret.type == runp::RS_JGF) {
			res.info = "No Comment";
		} else {
			res.info = "Compiler " + runp::rstype_str(ret.type);
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

run_compiler_result compile(const string &name)  {
	string lang = conf_str(name + "_language");
	runp::config rpc(main_path + "/run/compile", {
		"--custom", main_path + "/run/runtime",
		"--lang", lang,
		name
	});
	rpc.limits = RL_COMPILER_DEFAULT;
	return run_compiler(rpc);
}

run_compiler_result compile_with_implementer(const string &name, const string &implementer = "implementer")  {
	string lang = conf_str(name + "_language");
	if (conf_has(name + "_unit_name")) {
		file_put_contents(work_path + "/" + name + ".unit_name", conf_str(name + "_unit_name"));
	}
	runp::config rpc(main_path + "/run/compile", {
		"--custom", main_path + "/run/runtime",
		"--impl", implementer, "--lang", lang,
		name
	});
	rpc.limits = RL_COMPILER_DEFAULT;
	return run_compiler(rpc);
}

run_compiler_result compile_submission_program(const string &name) {
	return !conf_is("with_implementer", "on") ? compile(name) : compile_with_implementer(name);
}

/*====================== compile End ================== */

/*======================    test     ================== */

struct TestPointConfig {
	int submit_answer = -1;
	int validate_input_before_test = -1;
	int disable_program_input = -1;
	string input_file_name;
	string output_file_name;
	string answer_file_name;
	RunLimit limit = RunLimit(-1, -1, -1);
	string checker;

	void auto_complete(int num) {
		if (submit_answer == -1) {
			submit_answer = conf_is("submit_answer", "on");
		}
		if (validate_input_before_test == -1) {
			validate_input_before_test = conf_is("validate_input_before_test", "on");
		}
		if (disable_program_input == -1) {
			disable_program_input = conf_str("disable_program_input", num, "off") == "on";
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
		if (limit.time == -1) {
			limit = conf_run_limit(num, RL_DEFAULT);
		}
		if (checker.empty()) {
			checker = conf_str("checker");
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
		if (val_ret.type != runp::RS_AC) {
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
					tpc.disable_program_input ? "/dev/null" : tpc.input_file_name.c_str(),
					tpc.output_file_name.c_str(),
					tpc.limit,
					name);
			if (conf_has("token")) {
				file_hide_token(tpc.output_file_name, conf_str("token", ""));
			}
			if (pro_ret.type != runp::RS_AC) {
				return PointInfo(num, 0, -1, -1,
						info_str(pro_ret.type),
						file_preview(tpc.input_file_name), file_preview(tpc.output_file_name),
						"");
			}
		} else {
			pro_ret.type = runp::RS_AC;
			pro_ret.ust = -1;
			pro_ret.usm = -1;
			pro_ret.exit_code = 0;
		}

		if (tpc.checker == "nonempty") {
			string usrout = file_preview(tpc.output_file_name);
			if (usrout == "") {
				return PointInfo(num, 0, -1, -1,
						"default",
						file_preview(tpc.input_file_name), usrout,
						"wrong answer empty file\n");
			} else {
				return PointInfo(num, 100, -1, -1,
						"default",
						file_preview(tpc.input_file_name), usrout,
						"ok nonempty file\n");
			}
		} else {
			RunCheckerResult chk_ret = run_checker(
					conf_run_limit("checker", num, RL_CHECKER_DEFAULT),
					tpc.checker,
					tpc.input_file_name,
					tpc.output_file_name,
					tpc.answer_file_name);
			if (chk_ret.type != runp::RS_AC) {
				return PointInfo(num, 0, -1, -1,
						"Checker " + info_str(chk_ret.type),
						file_preview(tpc.input_file_name), file_preview(tpc.output_file_name),
						"");
			}
			return PointInfo(num, chk_ret.scr, pro_ret.ust, pro_ret.usm, 
					"default",
					file_preview(tpc.input_file_name), file_preview(tpc.output_file_name),
					chk_ret.info);
		}
	} else {
		string real_output_file_name = tpc.output_file_name + ".real_input.txt";
		string real_input_file_name = tpc.output_file_name + ".real_output.txt";
		RunSimpleInteractionResult rires = run_simple_interation(
				tpc.input_file_name,
				tpc.answer_file_name,
				real_input_file_name,
				real_output_file_name,
				tpc.limit,
				conf_run_limit("interactor", num, RL_INTERACTOR_DEFAULT),
				name);

		if (rires.ires.type != runp::RS_AC) {
			return PointInfo(num, 0, -1, -1,
					"Interactor " + info_str(rires.ires.type),
					file_preview(real_input_file_name), file_preview(real_output_file_name),
					"");
		}
		if (rires.res.type != runp::RS_AC) {
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
	if (val_ret.type != runp::RS_AC) {
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
		if (std_ret.type != runp::RS_AC) {
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

		if (rires.ires.type != runp::RS_AC) {
			return PointInfo(0, 0, -1, -1,
					"Interactor " + info_str(rires.ires.type) + " (Standard Program)",
					file_preview(real_input_file_name), "",
					"");
		}
		if (rires.res.type != runp::RS_AC) {
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

struct CustomTestConfig {
	RunLimit base_limit = conf_run_limit(0, RL_DEFAULT);
};

CustomTestInfo ordinary_custom_test(const string &name, CustomTestConfig ctc = CustomTestConfig()) {
	RunLimit lim = ctc.base_limit;
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
	if (pro_ret.type == runp::RS_AC) {
		info = "Success";
	} else {
		info = info_str(pro_ret.type);
	}
	string exp;
	if (pro_ret.type == runp::RS_TLE) {
		exp = "<p>[<strong>time limit:</strong> " + vtos(lim.time) + "s]</p>";
	}
	return CustomTestInfo(pro_ret.ust, pro_ret.usm, 
			info, exp, file_preview(output_file_name, 2048));
}

// classifications of tests
// primary tests = main tests + extra tests
// sample test data points are usually some of the extra test data points

struct PrimaryDataTestConfig {
	bool disable_ex_tests = false;
};

template <typename TP>
bool main_data_test(TP test_point_func) {
	int n = conf_int("n_tests", 10);
	int nT = conf_int("n_subtasks", 0);

	bool passed = true;
	if (nT == 0) { // OI
		for (int i = 1; i <= n; i++) {
			report_judge_status_f("Judging Test #%d", i);
			PointInfo po = test_point_func(i);
			if (po.scr != 100) {
				passed = false;
			}
			po.scr = scale_score(po.scr, conf_int("point_score", i, 100 / n));
			add_point_info(po);
		}
	} else if (nT == 1 && conf_subtask_meta_info(1).is_ordinary()) { // ACM
		for (int i = 1; i <= n; i++) {
			report_judge_status_f("Judging Test #%d", i);
			PointInfo po = test_point_func(i);
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
		for (int t = 1; t <= nT; t++) {
			SubtaskInfo st_info(conf_subtask_meta_info(t));
			if (!st_info.resolve_dependencies(subtasks)) {
				st_info.info = "Skipped";
			} else {
				for (int i : st_info.meta.points_id) {
					report_judge_status_f("Judging Test #%d of Subtask #%d", i, t);
					PointInfo po = test_point_func(i);
					st_info.add_point_info(po);
					if (st_info.early_stop) {
						break;
					}
				}
			}
			subtasks[t] = st_info;
			passed = passed && st_info.passed;
			add_subtask_info(st_info);
		}
	}
	if (passed) {
		tot_score = 100;
	}
	return passed;
}

template <typename TP>
bool ex_data_test(TP test_point_func) {
	int m = conf_int("n_ex_tests", 0);
	if (m > 0) {
		for (int i = 1; i <= m; i++) {
			report_judge_status_f("Judging Extra Test #%d", i);
			PointInfo po = test_point_func(-i);
			if (po.scr != 100) {
				po.num = -1;
				po.info = "Extra Test Failed : " + po.info + " on " + vtos(i);
				po.scr = -3;
				add_point_info(po);
				return false;
			}
		}
		PointInfo po(-1, 0, -1, -1, "Extra Test Passed", "", "", "");
		add_point_info(po);
	}
	return true;
}

template <typename TP>
bool primary_data_test(TP test_point_func, const PrimaryDataTestConfig &pdtc = PrimaryDataTestConfig()) {
	bool passed = main_data_test(test_point_func);
	if (passed && !pdtc.disable_ex_tests) {
		passed = ex_data_test(test_point_func);
	}
	return passed;
}

template <typename TP>
bool sample_data_test(TP test_point_func) {
	int n = conf_int("n_sample_tests", 0);
	bool passed = true;
	for (int i = 1; i <= n; i++) {
		report_judge_status_f("Judging Sample Test #%d", i);
		PointInfo po = test_point_func(-i);
		po.num = i;
		if (po.scr != 100) {
			passed = false;
		}
		po.scr = scale_score(po.scr, 100 / n);
		add_point_info(po);
	}
	if (passed) {
		tot_score = 100;
	}
	return passed;
}

/*======================  test End   ================== */

/*======================= conf init =================== */

void main_judger_init(int argc, char **argv)  {
	cerr << "main_judger_init is deprecated. use uoj_judger_v2 instead!" << endl;
	exit(1);
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
	load_config(work_path + "/submission.conf");
	problem_id = conf_int("problem_id");
	load_config(data_path + "/problem.conf");
	runp::run_path = main_path + "/run";

	PointInfo::show_in = conf_str("show_in", "on") == "on";
	PointInfo::show_out = conf_str("show_out", "on") == "on";
	PointInfo::show_res = conf_str("show_res", "on") == "on";

	if (chdir(work_path.c_str()) != 0) {
		cerr << "invalid work path" << endl;
		exit(1);
	}

	if (config.count("use_builtin_checker")) {
		config["checker"] = main_path + "/builtin/checker/" + config["use_builtin_checker"];
	} else {
		config["checker"] = data_path + "/chk";
	}
	config["validator"] = data_path + "/val";
}

/*===================== conf init End ================= */
