#include "uoj_judger.h"

/*
void hack_test()	{
	put_status("Judging Hack ...");
	in = work_path "/hack_input.txt";
	out = work_path "/pro_output.txt";
	ans = work_path "/std_output.txt";

	run_result rn;
	if (maker) {
		if (compile("maker") == false) {
			compile_info_update();
			put_info(1, 0, -1, -1, "Hack Failed : Maker Compile Error", "", "", read_file(result_path "/compile_result.txt", ""));
			end_info(0);
		}
		rn = run_maker(mtL, mmL, moL, info);
		if (rn.type != 0) {
			put_info(1, 0, -1, -1, "Hack Failed : " + info, "", "", "");
			end_info(0);
		}
	}
	if (getValid(in, info));
	else	put_info(1, 0, -1, -1, "Hack Failed : Illegal Input", read_file(in, "", 100), "", info), end_info(0);
	rn = run_program(stp, in, ans, tL, mL, oL, info);
	if (rn.type != 0)	put_info(1, 0, -1, -1, "Hack Failed : std " + info, read_file(in, "", 100), "", ""), end_info(0);
	rn = run_program(pro, in, out, tL, mL, oL, info);
	int ust = rn.ust, usm = rn.usm;
	if (rn.type != 0)	put_info(1, 1, -1, -1, "Hack Successfully : " + info, read_file(in, "", 100), "", ""), end_info(0);
	rn = run_judge(stL, smL, soL, info);
	if (rn.type != 0)	put_info(1, 1, -1, -1, "Hack Successfully : Checker " + info, read_file(in, "", 100), read_file(out, "", 100), ""), end_info(0);
	else	if (abs(SCORE() - 1) < 1e-5)	put_info(1, 0, ust, usm, "Hack failed : Answer is Correct", read_file(in, "", 100), read_file(out, "", 100), info), end_info(0);
	else	put_info(1, 1, ust, usm, "Hack Successfully : Wrong Answer", read_file(in, "", 100), read_file(out, "", 100), read_file(res, "", 100)), end_info(0);
	exit(0);
}
void sample_test()	{
	n = conf_int("n_sample_tests", n);
	for (int i = 1; i <= n; ++i)	{
		put_status("Judging Sample Test ... " + toStr(i)); 
		in = mdata_path + "/" + input(-i);
		ans = mdata_path + "/" + output(-i);
		out = work_path "/" + output(-i);

		int S = conf_int("sample_point_score", i, 100 / n);

		if (valid)	{
			if (getValid(in, info));
			else	{
				put_info(i, 0, -1, -1, "Illegal Input", read_file(in, "", 100), "", info);
				continue;
			}
		}
		
		run_result rn;
		if (submit)	{
			 if (file_exist(out))	put_info(i, 0, -1, -1, "File not exists", "", "", "");
			 else	{
				 rn = run_judge(stL, smL, soL, info);
				 if (rn.type != 0)	put_info(i, 0, -1, -1, "Checker " + info, read_file(in, "", 100), read_file(out, "", 100), "");
				 else	{
					 string ninfo;
					 if (abs(SCORE() - 1) < 1e-5)	ninfo = "Accepted";
					 else	if (abs(SCORE()) < 1e-5)	ninfo = "Wrong Answer";
					 else	ninfo = "Acceptable Output";
					 put_info(i, SCORE() * S, -1, -1, ninfo, read_file(in, "", 100), read_file(out, "", 100), read_file(res, ""));
				 }
			 }
			 continue;
		}
		
		rn = run_program(pro, in, out, tL, mL, oL, info);
		int ust = rn.ust, usm = rn.usm;
		if (rn.type != 0)	{
			 put_info(i, 0, -1, -1, info, read_file(in, "", 100), "", "");
			 continue;
		}
		rn = run_judge(stL, smL, soL, info);
		if (rn.type != 0)	put_info(i, 0, -1, -1, "Checker " + info, read_file(in, "", 100), read_file(out, "", 100), "");
		else	{
			string ninfo;
			if (abs(SCORE() - 1) < 1e-5)	ninfo = "Accepted", ++cnt;
			else	if (abs(SCORE()) < 1e-5)	ninfo = "Wrong Answer";
			else	ninfo = "Acceptable Output";
			put_info(i, SCORE() * S, ust, usm, ninfo, read_file(in, "", 100), read_file(out, "", 100), read_file(res, ""));
		}
	}
	if (cnt == n) totScore = 100;
	end_info(0);
}
void normal_test()	{
	n = conf_int("n_tests", 10);
	m = conf_int("n_ex_tests", 0);
	for (int i = 1; i <= n; ++i)	{
		put_status("Judging Test ... " + toStr(i));
		in = mdata_path + "/" + input(i);
		ans = mdata_path + "/" + output(i);
		out = work_path "/" + output(i);
		
		int ntL = conf_int("time_limit", i, tL),
				nmL = conf_int("memory_limit", i, mL),
				noL = conf_int("output_limit", i, oL),
			 nstL = conf_int("checker_time_limit", i, stL),
			 nsmL = conf_int("checker_memory_limit", i, smL),
			 nsoL = conf_int("checker_output_limit", i, soL);
			 
		int S = conf_int("point_score", i, 100 / n);

		if (valid)	{
			if (getValid(in, info));
			else	{
				put_info(i, 0, -1, -1, "Illegal Input", read_file(in, "", 100), "", info);
				continue;
			}
		}
		
		run_result rn;
		if (submit)	{
			 if (file_exist(out))	put_info(i, 0, -1, -1, "File not exists", "", "", "");
			 else	{
				 rn = run_judge(nstL, nsmL, nsoL, info);
				 if (rn.type != 0)	put_info(i, 0, -1, -1, "Checker " + info, read_file(in, "", 100), read_file(out, "", 100), "");
				 else	{
					 string ninfo;
					 if (abs(SCORE() - 1) < 1e-5)	ninfo = "Accepted";
					 else	if (abs(SCORE()) < 1e-5)	ninfo = "Wrong Answer";
					 else	ninfo = "Acceptable Output";
					 put_info(i, SCORE() * S, -1, -1, ninfo, read_file(in, "", 100), read_file(out, "", 100), read_file(res, ""));
				 }
			 }
			 continue;
		}
		
		rn = run_program(pro, in, out, ntL, nmL, noL, info);
		int ust = rn.ust, usm = rn.usm;
		if (rn.type != 0)	{
			 put_info(i, 0, -1, -1, info, read_file(in, "", 100), "", "");
			 continue;
		}
		rn = run_judge(nstL, nsmL, nsoL, info);
		if (rn.type != 0)	put_info(i, 0, -1, -1, "Checker " + info, read_file(in, "", 100), read_file(out, "", 100), "");
		else	{
			string ninfo;
			if (abs(SCORE() - 1) < 1e-5)	ninfo = "Accepted", ++cnt;
			else	if (abs(SCORE()) < 1e-5)	ninfo = "Wrong Answer";
			else	ninfo = "Acceptable Output";
			put_info(i, SCORE() * S, ust, usm, ninfo, read_file(in, "", 100), read_file(out, "", 100), read_file(res, ""));
		}
	}
	if (cnt != n)	end_info(0);
	totScore = 100;
	bool pass = true;
	for (int i = 1; i <= m; ++i)	{
		put_status("Judging Extra Test ... " + toStr(i));
		in = mdata_path + "/" + input(-i);
		ans = mdata_path + "/" + output(-i);
		out = work_path "/" + output(-i);
		run_result rn;

		if (valid)	{
			if (getValid(in, info));
			else	{
				put_info(-1, -3, -1, -1, "Extra Test Failed : Illegal Input on " + toStr(i), read_file(in, "", 100), "", info);
				pass = false;	break;
			}
		}
		
		rn = run_program(pro, in, out, tL, mL, oL, info);
		int ust = rn.ust, usm = rn.usm;
		if (rn.type != 0)	{
			 put_info(-1, -3, -1, -1, "Extra Test Failed : " + info + " on " + toStr(i), read_file(in, "", 100), "", "");
			 pass = false;	break;
		}
		rn = run_judge(stL, smL, soL, info);
		if (rn.type != 0)	{
			 put_info(-1, -3, -1, -1, "Extra Test Failed : Checker " + info + " on " + toStr(i), read_file(in, "", 100), read_file(out, "", 100), "");
			 pass = false;	break;
		}
		else	if (abs(SCORE() - 1) < 1e-5);
		else	{
			 put_info(-1, -3, ust, usm, "Extra Test Failed : Wrong Answer on " + toStr(i), read_file(in, "", 100), read_file(out, "", 100), read_file(res, "", 100));
			 pass = false;	break;
		}
	}
	if (pass	&&	m)	put_info(-1, 0, -1, -1, "Pass Extra Test", "", "", "");
	end_info(0);
}
void new_ex_test()	{
	bool pass = true;
	put_status("Judging New Extra Test ... ");
	int R = conf_int("n_ex_tests", 0);
	for (int i = R; i <= R; ++i)	{ 
		in = mdata_path + "/" + input(-i);
		ans = mdata_path + "/" + output(-i);
		if (file_exist(in))	in = mdata_path + "/" + input(i);
		if (file_exist(ans))	ans = mdata_path + "/" + input(i);
		
		out = work_path "/" + output(-i);
		run_result rn;
		
		if (valid)	{
			if (getValid(in, info));
			else	{
				put_info(1, 0, -1, -1, "Extra Test Failed : Illegal Input on " + toStr(i), read_file(in, "", 100), "", info);
				pass = false;	break;
			}
		}
		
		rn = run_program(pro, in, out, tL, mL, oL, info);
		int ust = rn.ust, usm = rn.usm;
		if (rn.type != 0)	{
			put_info(1, 0, -1, -1, "Extra Test Failed : " + info + " on " + toStr(i), read_file(in, "", 100), "", "");
			pass = false;	break;
		}
		rn = run_judge(stL, smL, soL, info);
		if (rn.type != 0)	{
			put_info(1, 0, -1, -1, "Extra Test Failed : Checker " + info + " on " + toStr(i), read_file(in, "", 100), read_file(out, "", 100), "");
			pass = false;	break;
		}
		else	if (abs(SCORE() - 1) < 1e-5);
		else	{
			put_info(1, 0, ust, usm, "Extra Test Failed : Wrong Answer on " + toStr(i), read_file(in, "", 100), read_file(out, "", 100), read_file(res, "", 100));
			pass = false;	break;
		}
	}
	if (pass)	put_info(1, 1, -1, -1, "Pass Extra Test", "", "", "");
	end_info(0);
}
*/

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
		set<int> passedSubtasks;
		for (int t = 1; t <= nT; t++) {
			int startI = conf_int("subtask_end", t - 1, 0) + 1;
			int endI = conf_int("subtask_end", t, 0);

			vector<PointInfo> points;

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
				if (!passedSubtasks.count(*it)) {
					skipped = true;
					break;
				}
			}
			if (skipped) {
				add_subtask_info(t, 0, "Skipped", points);
				continue;
			}

			int tfull = conf_int("subtask_score", t, 100 / nT);
			int tscore = tfull;
			string info = "Accepted";
			for (int i = startI; i <= endI; i++) {
				report_judge_status_f("Judging Test #%d of Subtask #%d", i, t);
				PointInfo po = test_point("answer", i);
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
			}

			if (info == "Accepted") {
				passedSubtasks.insert(t);
			}

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

		prepare_run_standard_program();
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
			PointInfo po = test_point("answer", i);
			if (po.scr != 0) {
				po.info = "Accepted";
				po.scr = 100;
			}
			po.scr = scale_score(po.scr, conf_int("point_score", i, 100 / n));
			po.res = "no comment";
			add_point_info(po);
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
	
	/*
	submit = conf_is("submit_answer", "on");
	hack	 = conf_is("test_new_hack_only", "on");
	sample = conf_is("test_sample_only", "on");
	maker	= conf_is("make_hack_test", "on");
	valid	= conf_is("validate_input_before_test", "on");
	newt	 = conf_is("test_new_extra", "on");
	
	if (submit == 0)	{
		if (compile("answer") == false)	end_info(-1);
	}

	jud = config["use_checker"];
	pro = work_path "/answer";
	mak = work_path "/maker";
	stp = mdata_path + "/std";
	chk = mdata_path + "/val";
	vre = result_path"/valid_result.txt";
	sco = result_path"/checker_score.txt";
	res = result_path"/checker_result.txt";
	

	tL = conf_int("time_limit", 1);
	mL = conf_int("memory_limit", 256);
	oL = conf_int("output_limit", 64);
	stL = conf_int("checker_time_limit", 10);
	smL = conf_int("checker_memory_limit", 256);
	soL = conf_int("checker_output_limit", 64);
	vtL = conf_int("validator_time_limit", 10);
	vmL = conf_int("validator_memory_limit", 256);
	voL = conf_int("validator_output_limit", 64);
	mtL = conf_int("maker_time_limit", 1);
	mmL = conf_int("maker_memory_limit", 256);
	moL = conf_int("maker_output_limit", 64);
	
	if (hack)	hack_test();
	if (sample)	sample_test();
	if (newt)	new_ex_test();
	normal_test();
	*/
}
