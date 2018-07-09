#include "uoj_judger.h"

struct SubtaskInfo {
	bool passed;
	int score;

	SubtaskInfo() {
	}
	SubtaskInfo(const bool &_p, const int &_s)
		: passed(_p), score(_s){}
};

void ordinary_test() {
	int n = conf_int("n_tests", 10);
	int m = conf_int("n_ex_tests", 0);
	int nT = conf_int("n_subtasks", 0);

	if (!conf_is("submit_answer", "on")) {
		report_judge_status_f("Compiling");
		RunCompilerResult c_ret = !conf_is("with_implementer", "on") ? compile("answer") : compile_with_implementer("answer");
		if (!c_ret.succeeded) {
			end_judge_compile_error(c_ret);
		}
	}

	bool passed = true;
	if (nT == 0) {
		for (int i = 1; i <= n; i++) {
			report_judge_status_f("Judging Test #%d", i);
			PointInfo po = test_point("answer", i);
			if (po.scr != 100) {
				passed = false;
			}
			po.scr = scale_score(po.scr, conf_int("point_score", i, 100 / n));
			add_point_info(po);
		}
	} else if (nT == 1) {
		for (int i = 1; i <= n; i++) {
			report_judge_status_f("Judging Test #%d", i);
			PointInfo po = test_point("answer", i);
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
	} else {
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
				string cur = "subtask_dependence_" + vtos(t);
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
				PointInfo po = test_point("answer", i);
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
					} else {
						points.push_back(po);
					}
				}
			}

			subtasks[t] = SubtaskInfo(info == "Accepted", tscore);

			add_subtask_info(t, tscore, info, points);
		}
	}
	if (conf_is("submit_answer", "on") || !passed) {
		end_judge_ok();
	}

	tot_score = 100;
	for (int i = 1; i <= m; i++) {
		report_judge_status_f("Judging Extra Test #%d", i);
		PointInfo po = test_point("answer", -i);
		if (po.scr != 100) {
			po.num = -1;
			po.info = "Extra Test Failed : " + po.info + " on " + vtos(i);
			po.scr = -3;
			add_point_info(po);
			end_judge_ok();
		}
	}
	if (m != 0) {
		PointInfo po(-1, 0, -1, -1, "Extra Test Passed", "", "", "");
		add_point_info(po);
	}
	end_judge_ok();
}

void hack_test() {
	if (conf_is("submit_answer", "on")) {
		end_judge_judgement_failed("Hack is not supported in this problem.");
	} else {
		RunCompilerResult c_ret = !conf_is("with_implementer", "on") ? compile("answer") : compile_with_implementer("answer");
		if (!c_ret.succeeded) {
			end_judge_compile_error(c_ret);
		}
		TestPointConfig tpc;
		tpc.input_file_name = work_path + "/hack_input.txt";
		tpc.output_file_name = work_path + "/pro_output.txt";
		tpc.answer_file_name = work_path + "/std_output.txt";

		PointInfo po = test_hack_point("answer", tpc);
		add_point_info(po);
		end_judge_ok();
	}
}

void sample_test() {
	if (conf_is("submit_answer", "on")) {
		int n = conf_int("n_tests", 10);
		for (int i = 1; i <= n; i++) {
			report_judge_status_f("Judging Test #%d", i);
			if (conf_is("check_existence_only_in_sample_test", "on")) {
				TestPointConfig tpc = TestPointConfig();
				tpc.auto_complete(i);

				string usrout = file_preview(tpc.output_file_name);
				if (usrout == "") {
					add_point_info(PointInfo(i, 0, -1, -1,
							"default",
							file_preview(tpc.input_file_name), usrout,
							"wrong answer empty file\n"));
				} else {
					PointInfo po = PointInfo(i, 100, -1, -1,
							"default",
							file_preview(tpc.input_file_name), usrout,
							"ok nonempty file\n");
					po.scr = scale_score(po.scr, conf_int("point_score", i, 100 / n));
					add_point_info(po);
				}
			} else {
				PointInfo po = test_point("answer", i);
				if (po.scr != 0) {
					po.info = "Accepted";
					po.scr = 100;
				}
				po.scr = scale_score(po.scr, conf_int("point_score", i, 100 / n));
				po.res = "no comment";
				add_point_info(po);
			}
		}
		end_judge_ok();
	} else {
		report_judge_status_f("Compiling");
		RunCompilerResult c_ret = !conf_is("with_implementer", "on") ? compile("answer") : compile_with_implementer("answer");
		if (!c_ret.succeeded) {
			end_judge_compile_error(c_ret);
		}

		int n = conf_int("n_sample_tests", 0);
		bool passed = true;
		for (int i = 1; i <= n; i++) {
			report_judge_status_f("Judging Sample Test #%d", i);
			PointInfo po = test_point("answer", -i);
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
		end_judge_ok();
	}
}

void custom_test() {
	if (conf_is("submit_answer", "on")) {
		end_judge_judgement_failed("Custom test is not supported in this problem.");
	} else {
		report_judge_status_f("Compiling");
		RunCompilerResult c_ret = !conf_is("with_implementer", "on") ? compile("answer") : compile_with_implementer("answer");
		if (!c_ret.succeeded) {
			end_judge_compile_error(c_ret);
		}
		
		report_judge_status_f("Judging");
		add_custom_test_info(ordinary_custom_test("answer"));
		
		end_judge_ok();
	}
}

int main(int argc, char **argv) {
	judger_init(argc, argv);

	if (conf_is("test_new_hack_only", "on")) {
		hack_test();
	} else if (conf_is("test_sample_only", "on")) {
		sample_test();
	} else if (conf_is("custom_test", "on")) {
		custom_test();
	} else {
		ordinary_test();
	}
}
