#include "testlib.h"

int main(int argc, char * argv[])
{
	registerTestlibCmd(argc, argv);

	int ja = ans.readInt();
	int pa = ouf.readInt();

	if (ja != pa)
		quitf(_wa, "expected %d, found %d", ja, pa);

	int ti = ouf.readInt();

	if (ti <= 100)
		quitf(_ok, "answer is %d and %d calls", ja, ti);
	else if (ti <= 2000)
		quitp(0.6, "answer is %d and %d calls (greater than 100 but less than or equal to 2000)", ja, ti);
	else
		quitf(_wa, "answer is %d and %d calls (greater than 2000)", ja, ti);
}
