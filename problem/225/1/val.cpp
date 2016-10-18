#include "testlib.h"
#include <bits/stdc++.h>

using namespace std;

#define REP(i, a, b) for (int i = (a), i##_end_ = (b); i < i##_end_; ++i)
#define debug(...) fprintf(stderr, __VA_ARGS__)
#define mp make_pair
#define x first
#define y second
#define pb push_back
#define SZ(x) (int((x).size()))
#define ALL(x) (x).begin(), (x).end()

template<typename T> inline bool chkmin(T &a, const T &b) { return a > b ? a = b, 1 : 0; }
template<typename T> inline bool chkmax(T &a, const T &b) { return a < b ? a = b, 1 : 0; }

typedef long long LL;

const int oo = 0x3f3f3f3f;

int n, m;

int main()
{
	registerValidation();
	long long a=inf.readInt(1,500,"n");
	inf.readSpace();
	long long b=inf.readInt(1,500,"m");
	inf.readSpace();
	long long c=inf.readInt(1,1000000000,"k");
	inf.readEoln();
	inf.readEof();
}
