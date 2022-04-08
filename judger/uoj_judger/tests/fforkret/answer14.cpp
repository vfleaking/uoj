#include <iostream>
#include <thread>
#include <unistd.h>
#include <sys/wait.h>
using namespace std;

void t1()
{
	while (true);
}

void t2()
{
    for (int i = 0; i < 1000000000; i++);
    for (int i = 0; i < 1000000000; i++);
    for (int i = 0; i < 1000000000; i++);
    for (int i = 0; i < 1000000000; i++);
}

int main()
{
    pid_t pid = fork();
	if (pid == 0) {
        if (fork() == 0) {
		    t2();
        } else {
            return 0;
        }
	}
    
    int stat;
    waitpid(pid, &stat, 0);
	
    return 0;
}
