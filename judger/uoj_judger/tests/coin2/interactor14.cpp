#include "testlib.h"
#include<cstdio>
#include<cstdlib>
#include<cstring>
#include<vector>
#include<sstream>
#include<iostream>
using namespace std;

int main(int argc, char **argv) {
  registerTestlibCmd(argc, argv);

  int T, n, a, d;
  T = inf.readInt();
  cout << T << endl;
  while(T--) {
    n = inf.readInt();
    a = inf.readInt();
    d = inf.readInt();
    cout << n << endl;
    bool ok = false;
    string s, cmd;
    for(int i = 0; i <= 5; i++) {
      cmd = ouf.readToken("Test|Answer", "cmd");
      if(cmd == "Test") {
        if(i == 5) quitf(_wa, "too many tests");
        
        vector<int> coins;
        int c;
        static int vis[200];
        memset(vis, 0, sizeof(vis));
        while(!ouf.seekEoln()) {
          c = ouf.readInt(1, n, "coin");
          if(vis[c]) quitf(_wa, "duplicated coin");
          vis[c] = 1;
          coins.push_back(c);
        }
        if(coins.empty()) quitf(_wa, "no coin for command Test");
        if(coins.size() % 2 != 0) quitf(_wa, "odd number of coins for command Test");
        int half = coins.size() / 2;
        int diff = 0;
        for(int i = 0; i < coins.size(); i++)
          if(i < half) diff += (coins[i] == a ? 1+d : 1);
          else diff -= (coins[i] == a ? 1+d : 1);
        if(diff > 0) diff = 1;
        else if(diff < 0) diff = -1;
        
        cout << diff << endl;
      }
      else if(cmd == "Answer") {
        int b = ouf.readInt(1, n, "b"), r = ouf.readInt(-1, 1, "r");
        if(a != b) expectedButFound(_wa, a, b);
        if(d != r) expectedButFound(_wa, d, r);
        break;
      }
    }
  }
  
  quitf(_ok, "correct");
}
