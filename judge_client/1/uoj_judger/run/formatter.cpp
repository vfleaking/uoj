#include<cstdio>
#include<cstdlib>
#include<cstring>
#include<cmath>
#include<algorithm>
using namespace std;
typedef long long LL;
int main()
{
    //freopen("1.in","r",stdin);
    char c,last;
    int nSpace=0,nR=0,first;
    while(1)
    {
        last=c,c=getchar();
        if(c==EOF)
        {
            if(last!='\n')
                putchar('\n');
            break;
        }
        else if(c!='\r'&&c!=' ')
        {
            if(c!='\n'&&first==0)
            {
                for(int j=1;j<=nSpace;++j)
                    putchar(' ');
                for(int j=1;j<=nR;++j)
                    putchar('\r');
            }
            else if(c!='\n')
            {
                for(int j=1;j<=nR;++j)
                    putchar('\r');
                for(int j=1;j<=nSpace;++j)
                    putchar(' ');
            }
            nSpace=nR=0;
            putchar(c);
        }
        else if(c==' ')
        {
            ++nSpace;
            if(nR==0)
                first=0;
        }
        else
        {
            ++nR;
            if(nSpace==0)
                first=1;
        }
    }
    return 0;
}
