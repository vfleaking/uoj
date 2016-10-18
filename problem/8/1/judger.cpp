#include "uoj_judger.h"

int main(int argc, char **argv) {
	judger_init(argc, argv);

	report_judge_status_f("Compiling");
	RunCompilerResult c_ret = compile("answer");
	if (!c_ret.succeeded) {
		end_judge_compile_error(c_ret);
	}

	report_judge_status_f("Judging Test");
	TestPointConfig tpc;
	tpc.input_file_name = "/dev/null";
	tpc.output_file_name = work_path + "/output.txt";
	tpc.answer_file_name = work_path + "/answer.code";
	PointInfo po = test_point("answer", 1, tpc);
	add_point_info(po);

	end_judge_ok();
}
