#include "uoj_judger_v2.h"

int main(int argc, char **argv)  {
	main_judger_init(argc, argv);

	runp::config rpc(conf_str("judger"), {
		main_path, work_path, result_path, data_path
	});
	rpc.result_file_name = result_path / "run_judger_result.txt";
	rpc.input_file_name = "/dev/null";
	rpc.output_file_name = "/dev/null";
	rpc.error_file_name = "stderr";
	rpc.limits = conf_run_limit("judger", 0, RL_JUDGER_DEFAULT);
	rpc.unsafe = true;
	runp::result res = runp::run(rpc);
	if (res.type != runp::RS_AC) {
		end_judge_judgment_failed("Judgment Failed : Judger " + runp::rstype_str(res.type));
	}
	return 0;
}