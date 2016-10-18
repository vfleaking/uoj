{$mode objfpc}
{$m+}

unit graderhelperlib;
interface

function get_a(p : longint) : longint;
function get_b(p : longint) : longint;
function get_c(p : longint) : longint;

procedure grader_init_9f3d9739b11c2a4b08ea48512ac467f6(var out_n_a, out_n_b, out_n_c, k : longint);
procedure grader_print_9f3d9739b11c2a4b08ea48512ac467f6(res : longint);

implementation

var
	n_a, n_b, n_c, k : longint;
	a, b, c : array[0..99999] of longint;
	tot_get : longint;

procedure grader_init_9f3d9739b11c2a4b08ea48512ac467f6(var out_n_a, out_n_b, out_n_c, k : longint);
var
	i : longint;
begin
	readln(n_a, n_b, n_c, k);
	out_n_a := n_a;
	out_n_b := n_b;
	out_n_c := n_c;
	for i := 0 to n_a - 1 do read(a[i]);
	for i := 0 to n_b - 1 do read(b[i]);
	for i := 0 to n_c - 1 do read(c[i]);
	
	tot_get := 0;
end;

procedure grader_print_9f3d9739b11c2a4b08ea48512ac467f6(res : longint);
begin
	writeln(res, ' ', tot_get);
end;

function get_a(p : longint) : longint;
begin
	inc(tot_get);
	if (0 <= p) and (p < n_a) then exit(a[p]);
	exit(2147483647)
end;
function get_b(p : longint) : longint;
begin
	inc(tot_get);
	if (0 <= p) and (p < n_b) then exit(b[p]);
	exit(2147483647)
end;
function get_c(p : longint) : longint;
begin
	inc(tot_get);
	if (0 <= p) and (p < n_c) then exit(c[p]);
	exit(2147483647)
end;

end.
