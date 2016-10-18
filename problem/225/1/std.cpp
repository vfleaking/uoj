#include<cstdio>
#include<cstdlib>
#include<iostream>
#include<algorithm>
#include<cstring>
#include<queue>
#include<vector>
#include<map>
#include<set>
#include<stack>
#include<string>
#include<cmath>
#include<cctype>
using namespace std;
const int maxlongint=2147483647;
const int inf=1000000000;
vector<pair<int,int> >v[2];
int main()
{
	int n,m,k;
	cin>>n>>m>>k;
	if(k<=min(min(n,m),2))
	{
		printf("-1\n");
		return 0;
	}
	if(min(n,m)==1)
	{
		for(int i=1;i<=n;i++)
			for(int j=1;j<=m;j++)
				if((i+j)%2)
					v[0].push_back(make_pair(i,j));
				else
					v[1].push_back(make_pair(i,j));
	}
	else
	{
		for(int i=1;i<=n;i++)
			for(int j=1;j<=m;j++)
				if(i%4<=1)
					v[j%2].push_back(make_pair(i,j));
				else
					v[(j%2)^1].push_back(make_pair(i,j));
	}
	if(v[0].size()<v[1].size())
		swap(v[0],v[1]);
	for(int j=0;j<v[1].size();j++)
		for(int i=0;i<=1;i++)
			printf("%d %d\n",v[i][j].first,v[i][j].second);
	if(v[0].size()>v[1].size())
		printf("%d %d\n",v[0][v[1].size()].first,v[0][v[1].size()].second);
}
