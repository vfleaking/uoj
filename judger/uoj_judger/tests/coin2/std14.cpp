// by Mahbub
#include<list>
#include<bitset>
#include<iostream>
#include<cstdio>
#include<algorithm>
#include<vector>
#include<set>
#include<map>
#include<functional>
#include<string>
#include<cstring>
#include<cstdlib>
#include<queue>
#include<utility>
#include<fstream>
#include<sstream>
#include<cmath>
#include<stack>
#include<assert.h>

using namespace std;

#define MEM(a, b) memset(a, (b), sizeof(a))
#define CLR(a) memset(a, 0, sizeof(a))
#define MAX(a, b) ((a) > (b) ? (a) : (b))
#define MIN(a, b) ((a) < (b) ? (a) : (b))
#define ABS(X) ( (X) > 0 ? (X) : ( -(X) ) )
#define S(X) ( (X) * (X) )
#define SZ(V) (int )V.size()
#define FORN(i, n) for(i = 0; i < n; i++)
#define FORAB(i, a, b) for(i = a; i <= b; i++)
#define ALL(V) V.begin(), V.end()
#define IN(A, B, C)  ((B) <= (A) && (A) <= (C))

typedef pair<int,int> PII;
typedef pair<double, double> PDD;
typedef vector<int> VI;
typedef vector<PII > VP;

#define AIN(A, B, C) assert(IN(A, B, C))

//typedef int LL;
//typedef long long int LL;
//typedef __int64 LL;

/*
Syntax to print into stderr for auto mode
#ifdef AUTO_MODE
  cerr << "--> " << x << "\n";
#endif
Syntax to print into stdout with flush in C++
cout << x << "\n" << flush;
*/

int Test(VI &V1, VI &V2)
{
	cout << "Test";
	int i;
	FORN(i, SZ(V1))	{cout << " " << V1[i];}
	FORN(i, SZ(V2)) {cout << " " << V2[i];}
	cout << endl;

	int ret;
	cin >> ret;
	return ret;
}

void Answer(int x, int r)
{
	cout << "Answer " << x << " " << r << endl;
}

void bktk(VI V, int res)
{
	if(SZ(V) == 1)
	{
		Answer(V[0], res);
		return;
	}

	if(SZ(V) == 2)
	{
		VI V1; V1.push_back(V[0]);
		VI V2; V2.push_back(V[1]);
		if(Test(V1, V2) == res) Answer(V[0], res);
		else Answer(V[1], res);
		return;
	}

	int i, sz = SZ(V);
	VI V1, V2, V3;
	for(i = 0; i < sz; i++)
	{
		if(i < (sz + 1)/3) V1.push_back(V[i]);
		else if(i < 2 * ((sz + 1)/3)) V2.push_back(V[i]);
		else V3.push_back(V[i]);
	}

	int now = Test(V1, V2);
	if(now == 0)
	{
		bktk(V3, res);
		return;
	}

	if(now == res) bktk(V1, res);
	else bktk(V2, res);
}

void check(int n, VI V, int prev_result = -1000)
{
	int sz = SZ(V);
	VI V1, V2, V3;
	int i, bound = (sz + 1) / 3;

	FORN(i, sz)
	{
		if(i < bound) V1.push_back(V[i]);
		else if(i < 2 * bound) V2.push_back(V[i]);
		else V3.push_back(V[i]);
	}

	int p3, res1, res2, expected, sp3;
	sz = MAX(SZ(V3), SZ(V1));
	if(39 < SZ(V) && SZ(V) <= 120) expected = sz - 13;
	else if(12 < SZ(V) && SZ(V) <= 39) expected = sz - 4;
	else if(3 < SZ(V) && SZ(V) <= 12) expected = sz - 1;
	else expected = 1;

	if(prev_result == -1000) res1 = Test(V1, V2);
	else res1 = prev_result;

	for(i = 0; i < expected; i++) 
	{
		swap(V2[SZ(V2) - i - 1], V3[SZ(V3) - i - 1]);
		swap(V1[SZ(V1) - i - 1], V2[SZ(V2) - i - 1]);
	}
	res2 = Test(V1, V2);

	if(res1 == res2)
	{
		if(39 < SZ(V) && SZ(V) <= 120) expected = 39;
		else if(12 < SZ(V) && SZ(V) <= 39) expected = 12;
		else if(3 < SZ(V) && SZ(V) <= 12) expected = 3;
		else expected = 3;

		for(i = SZ(V1) - 1; i >= expected / 3; i--) V1.pop_back();
		for(i = SZ(V2) - 1; i >= expected / 3; i--) V2.pop_back();
		for(i = SZ(V3) - 1; i >= expected / 3; i--) V3.pop_back();

		V.clear();
		for(i = 0; i < SZ(V1); i++) V.push_back(V1[i]);
		for(i = 0; i < SZ(V2); i++) V.push_back(V2[i]);
		for(i = 0; i < SZ(V3); i++) V.push_back(V3[i]);

		check(n, V, res2);
	}
	else
	{
		VI C1, C2, C3;
		for(i = 0; i < expected; i++)
		{
			C1.push_back(V1[SZ(V1) - i - 1]);
			C2.push_back(V2[SZ(V2) - i - 1]);
			C3.push_back(V3[SZ(V3) - i - 1]);
		}

		if(res1 == 0) bktk(C1, res2);
		else if(res2 == 0) bktk(C3, -res1);
		else bktk(C2, res1);
	}
}

int main()
{
	int T, n, i;

	cin >> T;
	AIN(T, 1, 15000);

	while(T--)
	{
		cin >> n;
		AIN(n, 3, 120);

		VI V;
		for(i = 1; i <= n; i++) V.push_back(i);
		check(n, V);
	}
  
	return 0;
}
