#include <iostream>
#include <thread>
#include <unistd.h>
#include <csignal>
#include <cstring>
using namespace std;

int main()
{
    int *p = new int[500];
    p += 10;
    delete []p;
	return 0;
}
