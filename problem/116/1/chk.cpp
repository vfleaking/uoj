#include "testlib.h"
#include <iostream>
#include <cstdio>
#include <fstream>
#include <string>
#include <cstring>
#include <cstdarg>
#include <cstdlib>
#include <cassert>
#include <sstream>
using namespace std;

const int MaxCodeL = 10000;
const int L = 1 << 23;

int code_l;
int code[MaxCodeL + 1][7];

int data[L];

ifstream fsrc;

NORETURN inline void runtime_error()
{
	quitf(_wa, "Runtime Error");
}
NORETURN inline void time_limit_exceeded()
{
	quitf(_wa, "Time Limit Exceeded");
}

inline void read_expression(int *p)
{
	char t = inf.readToken("[$#@]")[0];
	if (t == '@')
		*p++ = 0;
	else if (t == '$')
		*p++ = 1;
	else if (t == '#')
		*p++ = 2;
	*p++ = inf.readInt();
}

inline void read_instruction()
{
	code_l++;
	if (code_l > MaxCodeL)
		quitf(_fail, "this code is too long");
	string t = inf.readToken();
	if (t == "=")
	{
		code[code_l][0] = 1;
		read_expression(code[code_l] + 1);
		read_expression(code[code_l] + 3);
	}
	else if (t == "getc")
	{
		code[code_l][0] = 2;
		read_expression(code[code_l] + 1);
	}
	else if (t == "putc")
	{
		code[code_l][0] = 3;
		read_expression(code[code_l] + 1);
	}
	else if (t == "geti")
	{
		code[code_l][0] = 4;
		read_expression(code[code_l] + 1);
	}
	else if (t == "puti")
	{
		code[code_l][0] = 5;
		read_expression(code[code_l] + 1);
	}
	else if (t == "if")
	{
		code[code_l][0] = 6;
		read_expression(code[code_l] + 1);
		read_expression(code[code_l] + 3);
	}
	else if (t == "+")
	{
		code[code_l][0] = 7;
		read_expression(code[code_l] + 1);
		read_expression(code[code_l] + 3);
		read_expression(code[code_l] + 5);
	}
	else if (t == "-")
	{
		code[code_l][0] = 8;
		read_expression(code[code_l] + 1);
		read_expression(code[code_l] + 3);
		read_expression(code[code_l] + 5);
	}
	else if (t == "*")
	{
		code[code_l][0] = 9;
		read_expression(code[code_l] + 1);
		read_expression(code[code_l] + 3);
		read_expression(code[code_l] + 5);
	}
	else if (t == "/")
	{
		code[code_l][0] = 10;
		read_expression(code[code_l] + 1);
		read_expression(code[code_l] + 3);
		read_expression(code[code_l] + 5);
	}
	else if (t == "<")
	{
		code[code_l][0] = 11;
		read_expression(code[code_l] + 1);
		read_expression(code[code_l] + 3);
		read_expression(code[code_l] + 5);
	}
	else if (t == "==")
	{
		code[code_l][0] = 12;
		read_expression(code[code_l] + 1);
		read_expression(code[code_l] + 3);
		read_expression(code[code_l] + 5);
	}
	else
		quitf(_fail, "invalid instruction type \"%s\"", t.c_str());
}
inline void input()
{
	code_l = 0;
	while (!inf.seekEof())
		read_instruction();
}

inline void check_bound(int p)
{
	if (0 > p || p >= L)
		runtime_error();
}
inline int get(int p)
{
	check_bound(p);
	return data[p];
}
inline int get_expr(int *p)
{
	switch (*p++)
	{
		case 0:
			return *p++;
		case 1:
			return get(*p++);
		case 2:
			return get(get(*p++));
		default:
			runtime_error();
	}
}
inline void set(int *p, int nv)
{
	switch (*p++)
	{
		case 1:
			check_bound(*p);
			data[*p] = nv;
			break;
		case 2:
			check_bound(*p);
			p = data + *p;
			check_bound(*p);
			data[*p] = nv;
			break;
		default:
			runtime_error();
	}
}

inline int arithmetic_eval(int l, int t, int r)
{
	switch (t)
	{
		case 7:
			return l + r;
		case 8:
			return l - r;
		case 9:
			return l * r;
		case 10:
			if (r == 0)
				runtime_error();
			if (l >= 0)
				return l / r;
			else
				return -((-l - 1) / r + 1);
		case 11:
			return l < r;
		case 12:
			return l == r;
		default:
			runtime_error();
	}
}

stringstream pout;

inline void run()
{
	int p = 1;
	int cnt = 0;
	while (p != -1)
	{
		if (!(1 <= p && p <= code_l))
			runtime_error();
		int t = code[p][0];
		int c;
		if (cnt == 10000000)
			time_limit_exceeded();
		switch (t)
		{
			case 1:
				set(code[p] + 1, get_expr(code[p] + 3));
				p++;
				break;
			case 2:
				set(code[p] + 1, ouf.readChar());
				p++;
				break;
			case 3:
				c = get_expr(code[p] + 1);
				pout << (char)c;
				p++;
				break;
			case 4:
				set(code[p] + 1, ouf.readInt());
				p++;
				break;
			case 5:
				pout << get_expr(code[p] + 1);
				p++;
				break;
			case 6:
				if (get_expr(code[p] + 1))
					p = get_expr(code[p] + 3);
				else
					p++;
				break;
			default:
				set(code[p] + 5, arithmetic_eval(get_expr(code[p] + 1), t, get_expr(code[p] + 3)));
				p++;
		}
		cnt++;
	}
}

int main(int argc, char **argv)
{
	registerTestlibCmd(argc, argv);

	input();

	run();

	int cnt = 0;
	string str;
	while (getline(pout, str))
	{
		if (str == "ok")
			cnt++;
	}

	while (!ouf.seekEof())
		ouf.readString();

	if (cnt < 10)
		quitp((double)cnt / 10, "there are %d lines \"ok\"", cnt);
	else
		quitf(_ok, "there are %d lines \"ok\"", cnt);
}
