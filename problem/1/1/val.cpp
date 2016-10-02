#include"testlib.h"
using namespace std;
int main()
{
	registerValidation();
	inf.readInt(0,1000000000,"A");
	inf.readSpace();
	inf.readInt(0,1000000000,"B");
	inf.readEoln();
	inf.readEof();
	return 0;
}
