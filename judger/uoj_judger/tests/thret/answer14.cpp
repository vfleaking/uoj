#include <iostream>
#include <thread>
#include <unistd.h>
using namespace std;

void t1()
{
	while (true);
}

void t2()
{
	while (true);
}

int main()
{
	thread th1(t1);
	thread th2(t2);

	int x = 5;
	for (int i = 0; i < 10000000; i++) {
		x = x * x;
	}

	sleep(2);
	
	return 0;
}
