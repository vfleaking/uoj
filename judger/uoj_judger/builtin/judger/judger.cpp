#include "uoj_judger.h"

PointInfo submit_answer_test_point_with_auto_generation(int i, TestPointConfig tpc = TestPointConfig()) {
	static set<string> compiled;
	static set<int> generated;

	int output_file_id = conf_int("output_file_id", i, i);
	tpc.submit_answer = true;
	tpc.output_file_name = work_path + "/" + conf_output_file_name(conf_int("output_file_id", i, i));

	string gen_name = conf_str("output_gen", output_file_id, "");
	if (gen_name.empty()) {
		generated.insert(output_file_id);
	}
	if (generated.count(output_file_id)) {
		return test_point("", i, tpc);
	}

	tpc.limit = conf_run_limit("gen", output_file_id, RL_GENERATOR_DEFAULT);
	generated.insert(output_file_id);

	if (!compiled.count(gen_name)) {
		report_judge_status_f("Compiling %s", gen_name.c_str());
		if (auto c_ret = compile_submission_program(gen_name); !c_ret.succeeded) {
			end_judge_compile_error(c_ret);
		}
		compiled.insert(gen_name);
	}

	tpc.submit_answer = false;
	return test_point(gen_name, i, tpc);
}

void ordinary_test() {
	if (conf_is("submit_answer", "on")) {
		PrimaryDataTestConfig pdtc;
		pdtc.disable_ex_tests = true;
		primary_data_test([](int i) {
			return submit_answer_test_point_with_auto_generation(i);
		}, pdtc);
	} else {
		report_judge_status_f("Compiling");
		if (auto c_ret = compile_submission_program("answer"); !c_ret.succeeded) {
			end_judge_compile_error(c_ret);
		}
		primary_data_test([](int i) {
			return test_point("answer", i);
		});
	}
	end_judge_ok();
}

void hack_test() {
	if (conf_is("submit_answer", "on")) {
		end_judge_judgement_failed("Hack is not supported in this problem.");
	} else {
		if (auto c_ret = compile_submission_program("answer"); !c_ret.succeeded) {
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
		if (conf_is("check_existence_only_in_sample_test", "on")) {
			main_data_test([](int i) {
				TestPointConfig tpc = TestPointConfig();
				tpc.checker = "nonempty";
				return submit_answer_test_point_with_auto_generation(i, tpc);
			});
		} else {
			main_data_test([](int i) {
				PointInfo po = submit_answer_test_point_with_auto_generation(i);
				if (po.scr != 0) {
					po.info = "Accepted";
					po.scr = 100;
				}
				po.res = "no comment";
				return po;
			});
		}
		end_judge_ok();
	} else {
		report_judge_status_f("Compiling");
		if (auto c_ret = compile_submission_program("answer"); !c_ret.succeeded) {
			end_judge_compile_error(c_ret);
		}

		sample_data_test([](int i) {
			return test_point("answer", i);
		});
		end_judge_ok();
	}
}

void custom_test() {
	report_judge_status_f("Compiling");
	if (auto c_ret = compile_submission_program("answer"); !c_ret.succeeded) {
		end_judge_compile_error(c_ret);
	}

	CustomTestConfig ctc;
	if (conf_is("submit_answer", "on")) {
		ctc.base_limit = conf_run_limit("gen", 0, RL_GENERATOR_DEFAULT);
	}
	
	report_judge_status_f("Judging");
	add_custom_test_info(ordinary_custom_test("answer", ctc));
	
	end_judge_ok();
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
