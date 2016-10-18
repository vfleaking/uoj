{ This is sample grader for the contestant }
program grader;

uses kth, graderhelperlib;

var
	n_a, n_b, n_c, k : longint;
	res : longint;
begin
	grader_init(n_a, n_b, n_c, k);
	
	res := query_kth(n_a, n_b, n_c, k);
	
	grader_print(res);
end.
