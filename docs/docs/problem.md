# 题目
## 上传题目
新建题目只有超级管理员有权限，所以想加题先要联系超级管理员君，点一下题库右下方的 “新建题目” 按钮。

新建题目后，超级管理员或该题目的题目管理员可以看到题目后台。后台分为三个选项卡：

1. 编辑：题面编辑页面
2. 管理者：题目管理员列表管理页面
3. 数据：题目数据管理页面

当然还有最后个 “返回” 选项卡退出后台。下面将逐一介绍这三个选项卡。

###编辑
该页面可以对题面本身进行编辑，还可以管理题目的标签。

####编辑题面
题面用 Markdown 编写。

理论上题面是可以自由编写的，但还是有一些推荐的格式。可参照 UOJ 上对应类型的题面（传统题，交互题，提交答案题等）

一些推荐的规则：

1. 中文与英文、数字之间加一个空格隔开。
2. 输入输出样例用 `<pre>` 标签包围，并且以一个空行结尾。（方便大家复制样例到终端后不用再打回车）
3. 题面中最高级标题为三级标题。这是因为题目名称是二级标题。
4. 名称超过一个字符的数学符号要用 mathrm。例如 `\mathrm{sgn}`， `\mathrm{lca}`。
4. 注意 `\max`, `\min`, `\gcd` 都是有现成的。
5. 注意 xor 这种名称超过一个字符的二元运算符请用 `\mathbin{\mathrm{xor}}`。
6. 一行内的计算机输入输出和常量字符串表达式请用 `<samp>` 标签，例如 `请输出 “<samp>NO SOLUTION</samp>”`， `<samp>aaa</samp> 中有三个 <samp>a</samp>`。
7. 一行内的计算机代码请用 <code>`</code> 括起来，就像上面的规则那样。

可参考下面这个例子：
```markdown
读入一个整数 $n$，表示题目中提到的 $3$ 位大爷 AC 的总题数。请输出他们分别 AC 的总题数。如果你不能正确读入，那么将获得 $0$ 分。前 $3$ 个测试点你正确读入即可获得 $6$ 分，第 4 个测试点你正确读入只能获得 $3$ 分。如果你不会做这道题，请直接输出 “<samp>WOBUHUI</samp>”

下面有一个样例：
<pre>
233

</pre>
```


####编辑标签
只需要用英文逗号隔开标签填入文本框就行。

推荐你使用如下几条规则填写标签：

1. 标签的目的是标出题目类型，方便用户检索题目。一般来说，标签顺序基本为从小范围到大范围。
2. 最前面的几个标签是这题所需要的前置技能，这里假定 “二分查找” 之类过于基础的技能选手已经掌握。
3. 接下来是这道题的大方法，比如 “贪心”、“DP”、“乱搞”、“构造”、“分治”……
4. 接下来，如果这道题是非传统题，用一个标签注明非传统题类型，比如 “提交答案”、“交互式”、“通讯”。
5. 接下来，如果这道题是模板题，用一个标签注明 “模板题”。
6. 接下来，如果这道题是不用脑子想就能做出的题，例如 NOIP 第一题难度，用一个标签注明 “水题”。
7. 最后，如果这题的来源比较重要，用一个标签注明。比如 “UOJ Round”、“NOI”、“WC”。
8. 前置技能中，“数学” 太过宽泛不能作为标签，但 “数论” 可以作为前置技能。
9. 如果有多个解法，每个解法的前值技能和大方法都不太一样，那么尽可能都标上去。
10. “乱搞” 标签不宜滥用。


###管理者
只有题目管理员和超级管理员才能看到后台，但只有题目管理员才能管理题目数据。

可以通过这个页面增加或删除题目管理员。

###数据
UOJ 使用 svn 管理题目数据，svn 仓库地址可以在本页面上找到。当你点了本页面的某个按钮后，UOJ 将把 svn 密码发送到你的邮箱。这个 svn 密码是不随题目变化而变化的。

但是，初始时 UOJ 是没有配置过用于给大家发邮件的邮箱的。所以如果你还没有配置过，请直接在数据库的 user\_info 表中找到 svn\_password 一栏存储的值；如果你愿意折腾一下，可以在 /var/www/uoj/app/.config.php 里配置一下邮箱，详情见[安装](/install/)咯～

1. 下载 svn，即 subversion。如果是 win 可以用 TortoiseSVN，如果是 ubuntu 直接 apt-get 一发，也可以用 smartsvn。
2. 学习怎么使用 svn。（蛮好懂的吧，会用 checkout，add，remove，commit 什么的就行了）
3. 在题目数据管理页面，获取 svn 密码。
4. checkout 这道题。
5. 在你的 working copy 下，建立文件夹 1，然后在 1 这个文件夹下放置题目数据什么的。
6. 搞完之后 commit。
7. 在网页上点“与SVN仓库同步”，就可以啦！

####题目配置格式
主要麻烦是要写个 problem.conf，下面主要介绍传统题的配置。

例如：

<pre>
use_builtin_judger on
use_builtin_checker ncmp
n_tests 10
n_ex_tests 5
n_sample_tests 1
input_pre www
input_suf in
output_pre www
output_suf out
time_limit 1
memory_limit 256
output_limit 64

</pre>

假如你 input 文件是 www1.in, www2.in, ... 这个样子，那么你需要在 problem.conf 里记录：

<pre>
input_pre www
input_suf in

</pre>

output 文件同理。 

extra test 是指额外数据，在 AC 的情况下会测额外数据，如果某个额外数据通不过会被倒扣3分。

至于样例，样例一定是前几个额外数据，所以有一个 `n_sample_tests` 表示是前多少组是样例。你需要把题面中的样例和大样例放进额外数据里。

额外数据命名形如 ex\_www1.in, ex\_www2.in, ... 这个样子。

checker 是指判断选手输出是否正确的。一般来说输出结果为整数序列，用 ncmp 就够了，它会比较标准答案的整数序列和选手输出的整数序列。如果是忽略所有空白字符，进行字符串序列的比较，可以用wcmp。如果你想按行比较（不忽略行末空格，但忽略文末回车），可以使用 fcmp。如果想手写 checker，请使用 Codeforces 的 [testlib](http://codeforces.com/testlib) 库写 checker。

你需要写好后命名为 chk.cpp，然后在 problem.conf 中去掉 “use\_builtin\_checker” 一行。

如果你的题目的输入数据可以被检验是否满足题目要求，那么请写一个 validator。比如“保证数据随机”“保证数据均为人手工输入”等就无法检验输入数据是否满足题目要求。

validator 的作用就是检查你输入数据造得是否合法。比如你输入保证是个连通图，validator 就得检验这个图是不是连通的。还是请用 testlib 写，教程还是之前提到的那个教程。写好后命名为 val.cpp 就行。

另外注意时间限制**不能为小数**。

#### 提交答案题的配置
范例：

<pre>
use_builtin_judger on
submit_answer on
n_tests 10
input_pre www
input_suf in
output_pre www
output_suf out

</pre>

So easy！

不过还没完……你要写个 checker。当然如果你是道 [最小割计数](http://uoj.ac/problem/85) 就不用写了。。。老样子写个 use\_builtin\_checker ncmp 就行。

提答题经常会面临给部分分，请使用 quitp 函数。由于一些历史原因（求不吐槽 QAQ），假设测试点满分为 a，则
```cpp
quitp(x, "haha");
```
将会给 floor(a * round(100x) / 100) 分。是不是很复杂……其实很简单，当你这个测试点想给 p 分的时候，只要
```cpp
quitp(ceil(100.0 * p / a) / 100, "haha");
```
就行了。（好麻烦啊）

假设你已经写好了，赞！

但是下发的本地 checker 怎么办？

首先你得会写本地 checker。如果只是传参进来一个测试点编号，检查测试点是否正确，那么只要善用 registerTestlib 就行了。如果你想让 checker 与用户交互，请使用 registerInteraction。特别地，如果你想让 checker 通过终端与用户进行交互，请使用如下代码（好丑啊）
```cpp
	char *targv[] = {argv[0], "inf_file.out", (char*)"stdout"};
#ifdef __EMSCRIPTEN__
	setvbuf(stdin, NULL, _IONBF, 0);
#endif
	registerInteraction(3, targv);
```

那么怎么下发文件呢？你可以在文件夹 1/ 下新建一个文件夹 download，里面放的所有东西都会打包发给选手啦～

#### 交叉编译

由于你的本地 checker 可能需要发布到各个平台，所以你也许需要交叉编译。交叉编译的意思是在一个平台上编译出其他平台上的可执行文件。好！下面是 Ubuntu 上交叉编译小教程时间。

首先不管三七二十一装一些库：（请注意 libc 和 libc++ 可能有更新的版本）
```sh
sudo apt-get install libc6-dev-i386
sudo apt-get install lib32stdc++6 lib32stdc++-4.8-dev
sudo apt-get install mingw32
```

然后就能编译啦：
```sh
g++ localchk.cpp -o checker_linux64 -O2
g++ localchk.cpp -m32 -o checker_linux32 -O2
i586-mingw32msvc-g++ localchk.cpp -o checker_win32.exe -O2
```

问：那其他系统怎么办？有个叫 emscripten 的东西，可以把 C++ 代码编译成 javascript。请务必使用 UOJ 上的 testlib，而不是 CF 上的那个版本。由于 testlib 有些黑科技，UOJ 上的 testlib 对 emscripten 才是兼容的。

[emscripten 下载地址](http://kripken.github.io/emscripten-site/docs/getting_started/downloads.html) （建议翻墙提升下载速度）

编译就用
```sh
em++ localchk.cpp -o checker.js -O2
```

#### 交互题的配置
嘛……其实并没有 UOJ 内部并没有显式地支持交互式（真正的程序与程序交互的版本还没发布出来），只是提供了 require、implementer 和 token 这两个东西。

除了前文所述的 download 这个文件夹，还有一个叫 require 的神奇文件夹。在测评时，这个文件夹里的所有文件均会被移动到与选手源程序同一目录下。在这个文件夹下，你可以放置交互库，供编译时使用。

再来说 implementer 的用途。如果你在 problem.conf 里设置 `with_implementer on` 的话，各个语言的编译命令将变为：

* C++: g++ code implementer.cpp code.cpp -lm -O2 -DONLINE_JUDGE
* C: gcc code implementer.c code.c -lm -O2 -DONLINE_JUDGE
* Pascal: fpc implementer.pas -o code -O2

好……不管那些奇怪的选手的话，一个交互题就搞好了……你需要写 implementer.cpp、implementer.c 和 implementer.pas。当然你不想兹瓷某个语言的话不写对应的交互库就好了。

如果是 C/C++，正确姿势是搞一个统一的头文件放置接口，让 implementer 和选手程序都 include 这个头文件。把主函数写在 implementer 里，连起来编译时程序就是从 implementer 开始执行的了。

如果是 Pascal，正确姿势是让选手写一个 pascal unit 上来，在 implementer.pas 里 uses 一下。除此之外也要搞另外一个 pascal unit，里面存交互库的各种接口，让 implementer 和选手程序都 uses 一下。

哦另外 require 文件夹的内容显然不会被下发……如果你想下发一个样例交互库，可以放到 download 文件夹里。

好下面来解释什么是 token……考虑一道典型的交互题，交互库跟选手函数交流得很愉快，最后给了个满分。此时交互库输出了 “AC!!!” 的字样，也可能输出了选手一共调用了几次接口。但是聪明的选手发现，只要自己手动输出一下 “AC!!!” 然后果断退出似乎就能骗过测评系统了娃哈哈。

这是因为选手函数和交互库已经融为一体成为了一个统一的程序，测评系统分辨出谁是谁。这时候，token 就出来拯救世界了。

在 problem.conf 里配置一个奇妙的 token，比如 `token wahaha233`，然后把这个 token 写进交互库的代码里（线上交互库，不是样例交互库）。在交互库准备输出任何东西之前，先输出一下这个 token。当测评系统要给选手判分时，首先判断文件的第一行是不是 token，如果不是，直接零分。这样就避免了奇怪的选手 hack 测评系统了。这里的 token 判定是在调用 checker 之前，测评系统会把 token 去掉之后的选手输出喂给 checker。（所以一道交互题的 token 要好好保护好）

另外另外，记得在 C/C++ 的交互库里给全局变量开 static，意为仅本文件可访问。

#### 意想不到的非传统题
吼，假如你有一道题不属于上述任何一种题目类型 —— 恭喜你中奖啦！得自己写 judger 啦！

judger 的任务：给你选手提交的文件，把测评结果告诉我。

把 problem.conf 里的 `use_builtin_judger on` 这行去掉，搞一个 judger 放在题目目录下，就好了。

噢对怎么搞这个 judger 呢。。。全自动啦233。。。你需要写个 Makefile……比如
```makefile
export INCLUDE_PATH
CXXFLAGS = -I$(INCLUDE_PATH) -O2

all: chk judger

% : %.cpp
	$(CXX) $(CXXFLAGS) $< -o $@
```

什么？你问 judger 应该怎么写？

很抱歉的是这部分我封装得并不是很方便……请 include 一下 uoj_judger.h 来辅助你写 judger……参照 QUINE 这个样题……

说实在的 uoj_judger.h 写得太丑了（不过反正我会用2333）。期待神犇再来造一遍轮子，或者用 python 码个框架（需要解决 python 启动就耗时 40ms 的坑爹问题）

哦对，要使用自己的 judger 的话就只有超级管理员能 “与 SVN 仓库同步” 了。另外无论你的 judger 如何折腾，测评也是有 10 分钟 的时间限制的。

## 样题
在 problem 文件夹下有我们给出的几道样题。

1. 一个典型的传统题
	* \#1. A + B Problem。
2. 一个典型的非传统题：
	* \#8. Quine。
3. 一个典型的交互题：
	* \#52. 【UR \#4】元旦激光炮。
4. 一个典型的 ACM 赛制题（错一个就 0 分）：
	* \#79. 一般图最大匹配。
5. 一个典型的提交答案题：
	* \#116. 【ZJOI2015】黑客技术。
6. 一个典型的 subtask 制的题：
	* \#225. 【UR #15】奥林匹克五子棋。
