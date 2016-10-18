#include "testlib.h"
#include <set>
using namespace std;

int main()
{
	registerValidation();

	int n = inf.readInt(2, 500, "n");
	inf.readSpace();
	int m = inf.readInt(1, 124750, "m");
	inf.readEoln();

	set< pair<int, int> > es;

	for (int i = 0; i < m; i++)
	{
		int v = inf.readInt(1, n, "v");
		inf.readSpace();
		int u = inf.readInt(1, n, "u");
		inf.readEoln();

		ensuref(v != u, "this graph contains self loops");
		ensuref(!es.count(make_pair(v, u)), "duplicate edges");
		es.insert(make_pair(v, u));
		es.insert(make_pair(u, v));
	}

	inf.readEof();
}
