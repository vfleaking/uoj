#include "testlib.h"
#include<iostream>
#include<cmath>
#include<cstring>
#include<cstdio>
#include<algorithm>
#include<set>
#include<vector>
using namespace std;
int mp[510][510],f[510][510][4];
int main(int argc, char **argv)
{
	registerTestlibCmd(argc, argv);
	int n=inf.readInt(1,1000,"n");
	inf.readSpace();
	int m=inf.readInt(1,1000,"m");
	inf.readSpace();
	int k=inf.readInt(1,1000000000,"k");
	if(k<=min(min(n,m),2))
	{
		int t=ouf.readInt();
		if(t==-1)
			quitf(_ok, "correct");
		else
			quitf(_wa,"Your construction does not meet the problem condition");
	}
	for(int i=1;i<=n*m;i++)
	{
		int t1=ouf.readInt(1,n,"x_i"),t2=ouf.readInt(1,m,"y_i");
		if(mp[t1][t2])
			quitf(_wa,"Your construction does not meet the problem condition");
		mp[t1][t2]=i%2+1;
	}
	for(int i=1;i<=n;i++)
		for(int j=1;j<=m;j++)
		{
			if(mp[i][j]==mp[i-1][j])
				f[i][j][0]=f[i-1][j][0]+1;
			else
				f[i][j][0]=1;
			if(mp[i][j]==mp[i-1][j+1])
				f[i][j][1]=f[i-1][j+1][1]+1;
			else
				f[i][j][1]=1;		
			if(mp[i][j]==mp[i-1][j-1])
				f[i][j][2]=f[i-1][j-1][2]+1;
			else
				f[i][j][2]=1;	
			if(mp[i][j]==mp[i][j-1])
				f[i][j][3]=f[i][j-1][3]+1;
			else
				f[i][j][3]=1;
			for(int x=0;x<=3;x++)
				if(f[i][j][x]>=k)
					quitf(_wa,"Your construction does not meet the problem condition");
		}
	quitf(_ok, "correct");
}
