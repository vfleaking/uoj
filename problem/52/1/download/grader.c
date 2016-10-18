/* This is sample grader for the contestant */
#include "kth.h"
#include <stdio.h>
#include <assert.h>

#define MaxN 100000
#define INF 2147483647

static int a_n, b_n, c_n;
static int a[MaxN];
static int b[MaxN];
static int c[MaxN];

static int tot_get;

int get_a(int p)
{
	tot_get++;
	if (0 <= p && p < a_n)
		return a[p];
	return INF;
}
int get_b(int p)
{
	tot_get++;
	if (0 <= p && p < b_n)
		return b[p];
	return INF;
}
int get_c(int p)
{
	tot_get++;
	if (0 <= p && p < c_n)
		return c[p];
	return INF;
}

int main()
{
	int i;
	int res, k;

	assert(scanf("%d %d %d %d", &a_n, &b_n, &c_n, &k) == 4);
	for (i = 0; i < a_n; i++)
		assert(scanf("%d", &a[i]) == 1);
	for (i = 0; i < b_n; i++)
		assert(scanf("%d", &b[i]) == 1);
	for (i = 0; i < c_n; i++)
		assert(scanf("%d", &c[i]) == 1);

	tot_get = 0;
	res = query_kth(a_n, b_n, c_n, k);
	printf("%d %d\n", res, tot_get);

	return 0;
}
