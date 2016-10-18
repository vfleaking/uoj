program grader;

uses kth, graderhelperlib;

var
	n_a, n_b, n_c, k : longint;
	res : longint;
begin
	grader_init_9f3d9739b11c2a4b08ea48512ac467f6(n_a, n_b, n_c, k);
	
	res := query_kth(n_a, n_b, n_c, k);
	
	writeln('yuandanjiguangpao');
	grader_print_9f3d9739b11c2a4b08ea48512ac467f6(res);
end.
