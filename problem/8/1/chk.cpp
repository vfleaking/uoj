#include "testlib.h"
#include <string>
#include <vector>
#include <sstream>

using namespace std;

int main(int argc, char * argv[])
{
	registerTestlibCmd(argc, argv);

	std::string strAnswer;

	size_t tot = 0;
	int n = 0;
	while (!ans.eof()) 
	{
		std::string j = ans.readString();

		if (j == "" && ans.eof())
			break;

		strAnswer = j;
		std::string p = ouf.readString();

		n++;

		if (j != p)
			quitf(_wa, "%d%s lines differ - expected: '%s', found: '%s'", n, englishEnding(n).c_str(), compress(j).c_str(), compress(p).c_str());

		for (int i = 0; i < (int)j.length(); i++)
			if (33 <= j[i] && j[i] <= 126)
				tot++;
	}

	if (tot < 10)
		quitf(_wa, "this code is too short");

	if (n == 1)
		quitf(_ok, "single line: '%s'", compress(strAnswer).c_str());

	quitf(_ok, "%d lines", n);
}
