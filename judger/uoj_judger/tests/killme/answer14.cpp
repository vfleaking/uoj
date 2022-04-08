#include <iostream>
#include <thread>
#include <unistd.h>
#include <csignal>
using namespace std;

int main()
{
    raise(-1);
	return 0;
}
