#include <iostream>
#include <cstdio>
#include <algorithm>
using namespace std;

const int MaxN = 500;
const int MaxM = 124750;

inline int getint()
{
	char c;
	while (c = getchar(), '0' > c || c > '9');

	int res = c - '0';
	while (c = getchar(), '0' <= c && c <= '9')
		res = res * 10 + c - '0';
	return res;
}

struct halfEdge
{
	int u;
	halfEdge *next;
};
halfEdge adj_pool[MaxM * 2], *adj_tail = adj_pool;

int n, m;
halfEdge *adj[MaxN + 1];

int n_matches;
int mate[MaxN + 1];
int q_n;
int q[MaxN];
int col[MaxN + 1], next[MaxN + 1], bel[MaxN + 1];

inline void addEdge(int v, int u)
{
	adj_tail->u = u, adj_tail->next = adj[v], adj[v] = adj_tail++;
}

inline int get_lca(int v, int u)
{
	v = bel[v], u = bel[u];
	static bool book[MaxN + 1];
	fill(book + 1, book + n + 1, false);
	while (true)
	{
		if (v)
		{
			if (book[v])
				return v;
			book[v] = true;
			v = mate[v] ? bel[next[mate[v]]] : 0;
		}
		swap(v, u);
	}
}
inline void go_up(int v, int u, int mv)
{
	while (bel[v] != mv)
	{
		next[v] = u;
		if (col[mate[v]] == 1)
			col[mate[v]] = 0, q[q_n++] = mate[v];
		if (v == bel[v])
			bel[v] = mv;
		if (mate[v] == bel[mate[v]])
			bel[mate[v]] = mv;
		u = mate[v];
		v = next[mate[v]];
	}
}
inline void after_go_up()
{
	for (int v = 1; v <= n; v++)
		bel[v] = bel[bel[v]];
}
bool match(int sv)
{
	q_n = 0;
	for (int v = 1; v <= n; v++)
		col[v] = -1, bel[v] = v;
	col[sv] = 0, q[q_n++] = sv;
	for (int i = 0; i < q_n; i++)
	{
		int v = q[i];
		for (halfEdge *e = adj[v]; e; e = e->next)
		{
			if (col[e->u] == -1)
			{
				next[e->u] = v, col[e->u] = 1;
				int nv = mate[e->u];
				if (!nv)
				{
					int u = e->u;
					while (u)
					{
						int nu = mate[next[u]];
						mate[u] = next[u], mate[next[u]] = u;
						u = nu;
					}
					return true;
				}
				col[nv] = 0, q[q_n++] = nv;
			}
			else if (bel[v] != bel[e->u] && col[e->u] == 0)
			{
				int lca = get_lca(v, e->u);
				go_up(v, e->u, lca);
				go_up(e->u, v, lca);
				after_go_up();
			}
		}
	}
	return false;
}

void calc_max_match()
{
	n_matches = 0;
	for (int v = 1; v <= n; v++)
		if (!mate[v] && match(v))
			n_matches++;
}

int main()
{
	n = getint(), m = getint();
	for (int i = 0; i < m; i++)
	{
		int v = getint(), u = getint();
		addEdge(v, u), addEdge(u, v);
	}

	calc_max_match();

	printf("%d\n", n_matches);
	for (int v = 1; v <= n; v++)
		printf("%d ", mate[v]);
	printf("\n");

	return 0;
}
