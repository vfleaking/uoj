#include "testlib.h"

int main()
{
	registerValidation();

	int n_a = inf.readInt(0, 100000, "n_a");
	inf.readSpace();
	int n_b = inf.readInt(0, 100000, "n_b");
	inf.readSpace();
	int n_c = inf.readInt(0, 100000, "n_c");
	inf.readSpace();
	inf.readInt(1, n_a + n_b + n_c, "k");
	inf.readEoln();
	int last;
	last = 1;
	for (int i = 0; i < n_a; i++)
	{
		int cur = inf.readInt(1, 1000000000, "a[i]");
		ensuref(last <= cur, "a[i - 1] must be less than or equal to a[i]");
		last = cur;
		if (i + 1 < n_a)
			inf.readSpace();
	}
	inf.readEoln();

	last = 1;
	for (int i = 0; i < n_b; i++)
	{
		int cur = inf.readInt(1, 1000000000, "b[i]");
		ensuref(last <= cur, "b[i - 1] must be less than or equal to b[i]");
		last = cur;
		if (i + 1 < n_b)
			inf.readSpace();
	}
	inf.readEoln();

	last = 1;
	for (int i = 0; i < n_c; i++)
	{
		int cur = inf.readInt(1, 1000000000, "c[i]");
		ensuref(last <= cur, "c[i - 1] must be less than or equal to c[i]");
		last = cur;
		if (i + 1 < n_c)
			inf.readSpace();
	}
	inf.readEoln();

	inf.readEof();
}
