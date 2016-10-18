#include "kth.h"
#include <algorithm>

int query_kth(int n_a, int n_b, int n_c, int k)
{
	int ap = 0, bp = 0, cp = 0;
	while (k >= 3)
	{
		int l = k / 3;
		int ta = get_a(ap + l - 1), tb = get_b(bp + l - 1), tc = get_c(cp + l - 1);
		if (ta < tb && ta < tc)
			ap += l;
		else if (tb < tc)
			bp += l;
		else
			cp += l;
		k -= l;
	}

	int t_n = 0;
	int t[6];
	for (int i = 0; i < k; i++)
		t[t_n++] = get_a(ap + i);
	for (int i = 0; i < k; i++)
		t[t_n++] = get_b(bp + i);
	for (int i = 0; i < k; i++)
		t[t_n++] = get_c(cp + i);
	std::nth_element(t, t + k - 1, t + t_n);
	return t[k - 1];
}
