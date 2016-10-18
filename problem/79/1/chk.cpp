#include "testlib.h"
#include <set>
using namespace std;

const int MaxN = 500;

int main(int argc, char **argv)
{
	registerTestlibCmd(argc, argv);

	int n = inf.readInt(1, 500, "n");
	int m = inf.readInt(1, 124750, "m");

	set< pair<int, int> > es;

	for (int i = 0; i < m; i++)
	{
		int v = inf.readInt(1, n, "v");
		int u = inf.readInt(1, n, "u");
		es.insert(make_pair(v, u));
		es.insert(make_pair(u, v));
	}

	int ans_n = ans.readInt();
	int ouf_n = ouf.readInt();
	if (ans_n != ouf_n)
		expectedButFound(_wa, vtos(ans_n), vtos(ouf_n), "the maximum number of matches");

	static int mate[MaxN + 1];
	int rouf_n = 0;
	for (int v = 1; v <= n; v++)
	{
		int u = ouf.readInt(0, n, "mate[v]");
		if (u)
		{
			rouf_n++;
			if (!es.count(make_pair(v, u)))
				quitf(_wa, "invalid match");
			mate[u] = v;
		}
	}
	if (ouf_n * 2 != rouf_n)
		quitf(_wa, "invalid match");
	for (int v = 1; v <= n; v++)
		if (mate[v] && mate[mate[v]] != v)
			quitf(_wa, "invalid match");

	quitf(_ok, "your answer is %d", ouf_n);
}
