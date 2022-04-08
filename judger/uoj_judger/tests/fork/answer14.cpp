#include <iostream>
#include <unistd.h>
using namespace std;

int main()
{
    fork();
    cout << "hello world!" << endl;
    return 0;
}
