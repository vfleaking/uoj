# 题目

只有超级管理员有新建题目的权限。如果你是超级管理员，你可以点击题库页面右下方的 “新建题目” 按钮来新建一个题目。然后，你就可以在题目后台对题目进行管理。后台分为三个选项卡：

1. **编辑：**题面编辑页面
2. **管理者：**题目管理员列表管理页面
3. **数据：**题目数据管理页面

如果你不是超级管理员，请联系超级管理员帮忙添加题目，然后帮你加上管理该题目的权限。

下面我们将逐一介绍这三个选项卡（当然还有最后个 “返回” 选项卡退出后台，就不介绍了），然后我们将详细介绍题目数据需要满足的格式要求。

## 一、选项卡：编辑
该页面可以对题面本身进行编辑，还可以管理题目的标签。

### ▶ 编辑题面
题面用 Markdown 编写。

理论上题面是可以自由编写的，但还是有一些推荐的格式。可参照 UOJ 上对应类型的题面（传统题、交互题、提交答案题等）

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
读入一个整数 $n$，表示题目中提到的 $n$ 位大爷 AC 的总题数。 请分别输出他们 AC 的总题数。

如果你不能正确读入，那么你将获得 $0$ 分。

前 $3$ 个测试点你正确读入即可获得 $6$ 分，第 4 个测试点你正确读入只能获得 $3$ 分。

如果你不会做这道题，请直接输出 “<samp>WOBUHUI</samp>”。

下面是一个样例：
<pre>
233

</pre>
```

### ▶ 编辑标签
只需要用**英文逗号**隔开标签填入文本框就行。

推荐你使用如下几条规则填写标签：

1. 标签的目的是标出题目类型，方便用户检索题目。一般来说，标签顺序基本为从小范围到大范围。
2. 最前面的几个标签是这题所需要的前置技能，这里假定 “二分查找” 之类过于基础的技能选手已经掌握。
3. 接下来是这道题的大方法，比如 “贪心”、“DP”、“乱搞”、“构造”、“分治”……
4. 接下来，如果这道题是非传统题，用一个标签注明非传统题类型，比如 “提交答案”、“交互式”、“通讯”。
5. 接下来，如果这道题是模板题，用一个标签注明 “模板题”。
6. 接下来，如果这道题是不用脑子想就能做出的题，例如 NOIP 第一题难度，用一个标签注明 “水题”。
7. 最后，如果这题的来源比较重要，用一个标签注明。比如 “UOJ Round”、“NOI”、“WC”。
8. 前置技能中，“数学” 太过宽泛不能作为标签，但 “数论” 可以作为前置技能。
9. 如果有多个解法，每个解法的前置技能和大方法都不太一样，那么尽可能都标上去。
10. “乱搞” 标签不宜滥用。

## 二、标签页：管理者

可以通过这个页面增加或删除题目管理员。+mike 表示把 mike 加入题目管理员列表，-mike 表示把 mike 从题目管理员列表中移除。

题目管理员和超级管理员都能看到题目后台，但只有题目管理员才能通过 SVN 管理题目数据。所以，必要时可以将超级管理员也添加为题目管理员。当然，除了通过 SVN 管理题目数据之外，直接通过网页浏览器管理也是可以的。



## 三、标签页：数据

UOJ 使用 SVN 管理题目数据，SVN 仓库地址可以在本页面上找到。配置题目一般分两步，第一步是上传题目数据，第二步是让 UOJ 根据上传来的数据，生成一份供测评使用的数据包。下面我们将依次进行介绍。

### ▶ 上传原始数据

上传数据有三种方式。第一种方法是**点击 “浏览 SVN 仓库” 进行上传**。点击后会进入到一个文件浏览器界面，然后再点击右上角上传，就可以把本地文件直接拖拽过来上传了。

第二种方法是**点击 “直接上传一个压缩包” 进行上传**。点击后会弹出一个对话框，然后你就可以直接上传一个包含题目数据的 zip 压缩包了。上传后你可以点击 “浏览 SVN 仓库” 检查上传内容是否正确。

第三种方式是**通过本地 SVN 客户端上传**。这个有点复杂，你需要了解一点 SVN 的基础知识。不过你如果已经学会了使用 git 的话，SVN 是不难掌握的。

题外话：在我们开发 UOJ 核心代码的 2014 年，SVN 和 git 似乎还难分伯仲。当时考虑到 [Codeforces](http://codeforces.com/) 附属的造题网站 Polygon 使用的是 SVN，所以我们最后让 UOJ 使用的也是 SVN 而非大家可能更熟知的 git。

想要使用 SVN，你得先获取你 UOJ 用户所对应的 SVN 密码。当你点击了按钮 “我会用 SVN 客户端，请发我 SVN 账号密码” 之后，UOJ 就会把你的 SVN 账号密码发送到你的邮箱。这个 SVN 密码是不随题目变化而变化的，所以你只用获取一次，然后好好保存。

但是，初始时 UOJ 是没有配置过用于给大家发邮件的邮箱的。所以如果你还没有配置过，请直接在数据库的 `user_info` 表中找到 `svn_password` 一栏存储的值；如果你愿意折腾一下，可以在 `/var/www/uoj/app/.config.php` 里配置一下邮箱，详情见[安装教程](/install/)咯～

下面是使用 SVN 上传的一点指南：

1. 下载 SVN，即 subversion。如果是 win 可以用 TortoiseSVN，如果是 ubuntu 可以直接 `apt install`，也可以使用 smartsvn。
2. 学习怎么使用 SVN，学到大约会用 checkout、add、remove、commit 的程度就可以了。
3. 在题目数据管理页面，获取 svn 密码。
4. 根据“数据”标签页上的 SVN 仓库地址，checkout 这道题。
5. 在你的 working copy 下，**建立名为 `1` 的文件夹**，然后在 1 这个文件夹下放置题目数据。这一点非常重要，如果不放在该文件夹下，UOJ 将无法识别。
6. 搞完之后 commit。
7. 在网页上点“与SVN仓库同步”，就可以啦！


将 UOJ 设计为数据都放在文件夹 `1` 下初衷是为了方便大家存同一题目的不同版本（其中主版本叫 `1`），但这样确实给传题的管理员们造成了很多困惑。所以如果网速可观，**推荐大家点击 “浏览 SVN 仓库” 上传数据**，这样你就不用考虑文件夹 `1` 的问题了，直接将数据放在根目录即可。

### ▶ 生成测评数据包

UOJ 这一部分的交互界面做得有点晦涩。。

上传了原始数据之后，你可以点击右侧按钮 **“与svn仓库同步”** 来让 UOJ 对你上传的原始数据进行格式检查，并最终生成测评时使用的数据包。一般而言，这一步骤主要完成的是如下操作：

1. 检查原始数据中的输入输出文件里的换行符。如果是 `\r\n`，会被自动替换为 `\n`。多余的行末空格也会被替换掉。
2. 编译测评所需的文件，比如答案检查器（chk）、标准程序（std）、数据检验器（val）。
3. 将 download 文件夹下的文件打包成压缩包，作为该题的附件供选手下载。

总之，你需要在上传数据之后点击 “与svn仓库同步”，检查是否有报错。顺利生成测评数据包之后，这道题的测评功能才会正常工作。

另外还要注意 Hack 功能的开关。初始时，题目是允许 Hack 的。这意味着你只有在上传 std 和 val 后，“与svn仓库同步” 才会停止报错。但如果你不想写，或者暂时不打算写，你可以点击 **“禁止使用 Hack”** 的按钮来生成测评数据包。

## 四、题目数据格式

下面我们先从传统题的配置讲起，然后再依次展开介绍各类非传统题的配置方式。

### ▶ 传统题的数据格式

#### 1. 数据配置文件

假设你要上传一道传统题，输入文件是 `www1.in`, `www2.in`, ... 这个样子，输出文件 `www1.out`, `www2.out`, ...。你想设时间限制为 1s，空间限制为 256MB，那么你需要在上传这些输入输出文件的同时，编写一个类似下面这样的数据配置文件 `problem.conf`：

```text
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
```

当然你也可以通过点击 “修改数据配置” 按钮，来在线修改该数据配置文件的内容。

**测评类型：**`use_builtin_judger on` 这一行指定了该题使用 UOJ 内置的测评器进行测评，绝大多数情况下都只需要使用内置的测评器。后面我们将介绍如何使用自定义的测评器。

**输入输出文件：**UOJ 会根据 `#!c++ printf("%s%d.%s", input_pre, i, input_suf)` 这种方式来匹配第 i 个输入文件，你可以将 `input_pre` 和 `input_suf` 改成自己想要的值。输出文件同理。

**额外数据：**extra test 是指额外数据，在 AC 的情况下会测额外数据，如果某个额外数据通不过会被倒扣3分。额外数据中，输入文件命名形如 `ex_www1.in`, `ex_www2.in`, ... 这个样子，一般来说就是 `#!c++ printf("ex_%s%d.%s", input_pre, i, input_suf)`，输出文件同理。

**样例数据：**样例必须被设置为前若干个额外数据。所以数据配置文件里有一个 `n_sample_tests` 参数，表示的是前多少组是样例。你需要把题面中的样例和大样例统统放进额外数据里。

**答案检查器（chk）：**数据配置文件中，`use_builtin_checker ncmp` 的意思是将本题的 checker 设置为 `ncmp`。checker 是指判断选手输出是否正确的答案检查器。一般来说，如果输出结果为整数序列，那么用 `ncmp` 就够了。`ncmp` 会比较标准答案的整数序列和选手输出的整数序列。如果是忽略所有空白字符，进行字符串序列的比较，可以用 `wcmp`。如果你想按行比较（不忽略行末空格，但忽略文末回车），可以使用 `fcmp`。

如果想使用自定义的 checker，请使用 Codeforces 的 [testlib](http://codeforces.com/testlib) 库编写 checker，并命名为 `chk.cpp`，然后在 problem.conf 中去掉 “`use_builtin_checker`” 这一行。

**时空限制：**`time_limit` 控制的是一个测试点的时间限制，单位为秒。`memory_limit` 控制的是一个测试点的空间限制，单位为 MB。`output_limit` 控制的是程序的输出长度限制，单位也为 MB。注意这些限制**都不能为小数**。

在目前的 UOJ 代码架构下，想让时间限制为小数似乎不是一件难事，但一直因为各种原因拖着没写。。希望有路过的有为青年可以帮帮忙咯。


#### 2. 配置 Hack 功能

如果想让你的题目支持 Hack 功能，那么你还要额外上传点程序。

**数据检验器（val）**：数据检验器的功能是检验你的题目的输入数据是否满足题目要求。比如你输入保证是个连通图，validator 就得检验这个图是不是连通的。但比如 “保证数据随机” “保证数据均为人手工输入” 等就无法进行检验。请使用 Codeforces 的 [testlib](http://codeforces.com/testlib) 库编写，写好后命名为 val.cpp 即可。

**标准程序（std）**：标准程序的功能是给定输入，生成标准答案。默认时空限制与选手程序相同。Hack 时，chk 将会根据 std 的输出，判断选手的输出是否正确。

#### 3. 辅助测评程序

我们暂且将 chk、val、std 这种称为辅助测评程序。上面我们已经介绍了这些辅助测评程序各自的用途，下面我们介绍两个有用的配置。

**配置语言：**默认情况下，类似 `std.cpp` 的文件名将会被识别为 C++ 03（即编写 UOJ 核心代码时 g++ 的默认 C++ 语言标准）。如果想使用 C++ 11、C++ 14、C++ 17、C++ 20，请将文件名改为 `std11.cpp`、`std14.cpp`、`std17.cpp`、`std20.cpp`。类似的也可以有 `chk14.cpp`、`val20.cpp`。下面是文件后缀名到语言的映射表（详见 `app/models/UOJLang.php`）：

```php
<?php
$suffix_map = [
    '20.cpp' => 'C++20',
    '17.cpp' => 'C++17',
    '14.cpp' => 'C++14',
    '11.cpp' => 'C++11',
    '.cpp'   => 'C++', // C++ 03
    '.c'     => 'C',
    '.pas'   => 'Pascal',
    '2.7.py' => 'Python2.7',
    '.py'    => 'Python3',
    '7.java' => 'Java7',
    '8.java' => 'Java8',
    '11.java' => 'Java11',
    '14.java' => 'Java14',
];
```

**配置时空限制：**默认情况下，chk 和 val 的时空限制为 5s + 256MB，std 的时空限制与选手程序相同。如果你想让这些辅助测评程序拥有更宽松的时空限制，你可以在 `problem.conf` 中加入
```text
<name>_time_limit 100
<name>_memory_limit 1024
```
来将其时间限制设置为 100s，空间限制设置为 1024MB。其中，chk 对应的 `<name>` 为 `checker`，val 对应的 `<name>` 为 `validator`，std 对应的 `<name>` 为 `standard`。

#### 4. 测试点分值

默认情况下，如果测试点全部通过，那么直接给 100 分。否则，如果你有 `n` 个测试点，那么每个测试点的分值为 `#!c++ floor(100/n)`。

如果你想改变其中某个测试点的分值，可以在 `problem.conf` 里加入类似 `point_score_5 20` 这样的配置（意为把第 5 个测试点的分值改为 20）。

UOJ 支持出题人设置测试点的分值的数值类型。共有两种：整数和实数。在整数模式中，每个测试点的总分值必须为整数，实际得分也为整数；在实数模式中，测试点的分值可以有小数，最后实际得分会用浮点数进行运算，四舍五入到小数点后若干位。默认模式为整数模式，也可手动设置：`score_type int`；实数模式设置方法形如 `score_type real-3`，意为实数模式 + 保留小数点后 3 位。可以通过改变后面的这个 “3” 来控制保留到小数点后几位（可以设为 0 到 10 之间的任意整数）。

有些题目（特别是提答题）会有给单个测试点打部分分的需求。此时请使用自定义答案检查器 chk，并使用 quitp 函数。在整数模式下，假设测试点满分为 $a$，则
```cpp
quitp(x, "haha");
```
将会给 `#!c++ floor(a * round(100 * x) / 100)` 分。即，先将 $x$ 四舍五入到小数点后两位，得到 $x'$，然后给分 $\lfloor a \cdot x' \rfloor$。这样设计是因为 UOJ 期望你输出的 $x$ 是一个两位小数。当你这个测试点想给 $p$ 分（$p$ 为整数）的时候，你输出的两位小数 $x$ 需要满足

\[
    \frac{p}{a} \le x < \frac{p+1}{a}
\]

因此，最稳妥的办法是：
```cpp
quitp(ceil(100.0 * p / a) / 100, "haha");
```
虽然这个上取整有点反直觉。


为什么整数模式下，得分是下取整的？因为下取整才可以保证答案有误的时候得分不为满分。如果想要四舍五入，可以使用实数模式。在实数模式下，若设置的精度为 $d$，那么 UOJ 期望你精确地输出 $x$，而最后得分会是将 $a \cdot x$ 四舍五入到小数点后第 $d$ 位所得到的结果。因此，当你想给 $p$ 分（$p$ 可以是小数），那么直接像下面这样写就好了：
```cpp
quitp((double)p / a, "haha");
```

如果你的题目是一道以子任务形式评分的题目，那么你可以用如下语法划分子任务和设定分值：
```text
n_subtasks 6
subtask_end_1 5
subtask_score_1 10
subtask_end_2 10
subtask_score_2 10
subtask_end_3 15
subtask_score_3 10
subtask_end_4 20
subtask_score_4 20
subtask_end_5 25
subtask_score_5 20
subtask_end_6 40
subtask_score_6 30
```

可以用 `subtask_type_5 <type>` 将第 5 个子任务的评分类型设置为 `packed` 或 `min`。其中 `packed` 表示错一个就零分，`min` 表示测评子任务内所有测试点并取得分的最小值。默认值为 `packed`。

可以用 `subtask_used_time_type_5 <type>` 将第 5 个子任务统计程序用时的方式设置为 `sum` 或 `max`。其中 `sum` 表示将子任务内测试点的用时加起来，`min` 表示取子任务内测试点的用时最大值。默认值为 `sum`。

可以用 `subtask_dependence_5 3` 来将第 3 个子任务添加为第 5 个子任务的依赖任务，相当于测评时第 5 个子任务会拥有第 3 个子任务的所有测试点。所以如果第 3 个子任务中有一个错了，并且第 5 个子任务的评分类型为 `packed`，那么第 5 个子任务也会自动零分。如果你想给一个子任务添加多个依赖任务，比如同时依赖 3 和 4，那么你可以用如下语法：
```text
subtask_dependence_5 many
subtask_dependence_5_1 3
subtask_dependence_5_2 4
```


#### 5. 目录结构

下面是存储一道传统题数据的文件夹所可能具有的结构：

* `www1.in`, `www2.in`, ... ：输入文件
* `www1.out`, `www2.out`, ... ：输出文件
* `ex_www1.in`, `ex_www2.in`, ... ：额外输入文件
* `ex_www1.out`, `ex_www2.out`, ... ：额外输出文件
* `problem.conf`：数据配置文件
* `chk.cpp`：答案检查器
* `val.cpp`：数据检验器
* `std.cpp`：标准程序
* `download/`：一个文件夹，该文件夹下的文件会被打包成压缩包，作为该题的附件供选手下载。
* `require/`：一个文件夹，该文件夹下的文件会被复制到选手程序运行时所在的工作目录。

注意：如果你是手动通过 SVN 客户端操作，那么你需要把这些文件都放在名为 `1/` 的文件夹下。

### ▶ 提交答案题的配置
范例：

```text
use_builtin_judger on
submit_answer on
n_tests 10
input_pre www
input_suf in
output_pre www
output_suf out
```

So easy！

不过还没完，因为一般来说你还要写个 chk。当然如果你是道 [最小割计数](http://uoj.ac/problem/85) 就不用写了，直接用 `use_builtin_checker ncmp` 就行。

假设你已经写好 chk 了，你会发现你还需要发给选手一个本地可以运行的版本，这怎么办呢？如果只是传参进来一个测试点编号，检查测试点是否正确，那么只要善用 registerTestlib 就行了。如果你想让 checker 与用户交互，请使用 registerInteraction。特别地，如果你想让 checker 通过终端与用户进行交互，请使用如下代码（有点丑啊）
```cpp
	char *targv[] = {argv[0], "inf_file.out", (char*)"stdout"};
#ifdef __EMSCRIPTEN__
	setvbuf(stdin, NULL, _IONBF, 0);
#endif
	registerInteraction(3, targv);
```

那么怎么下发文件呢？你可以把编译好的 chk 放在文件夹 download 下，该文件夹下的所有文件都会被打包发给选手。

**交叉编译小技巧：**（本段写于 2016 年）由于你的本地 checker 可能需要发布到各个平台，所以你也许需要交叉编译。交叉编译的意思是在一个平台上编译出其他平台上的可执行文件。好！下面是 Ubuntu 上交叉编译小教程时间。首先不管三七二十一装一些库：（请注意 libc 和 libc++ 可能有更新的版本）
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

问：那其他系统怎么办？有个叫 [emscripten](http://kripken.github.io/emscripten-site/docs/getting_started/downloads.html) 的东西，可以把 C++ 代码编译成 javascript。请务必使用 UOJ 上的 testlib，而不是 CF 上的那个版本。由于 testlib 有些黑科技，UOJ 上的 testlib 对 emscripten 才是兼容的。编译就用
```sh
em++ localchk.cpp -o checker.js -O2
```

### ▶ 交互题的配置
UOJ 内部并没有显式地支持交互式，而是提供了 require、implementer 和 token 这几个东西。

#### 1. require 文件夹

除了前文所述的 download 这个文件夹，还有一个叫 require 的神奇文件夹。在测评时，这个文件夹里的所有文件均会被移动到与选手源程序同一目录下。在这个文件夹下，你可以放置交互库，供编译时使用。

另外，require 文件夹的内容显然不会被下发……如果你想下发一个样例交互库，可以自己放到 download 文件夹里。

#### 2. implementer 代码

再来说 implementer 的用途。如果你在 `problem.conf` 里设置 `with_implementer on` 的话，各个语言的编译命令将变为：

* C++: `g++ code implementer.cpp code.cpp -lm -O2 -DONLINE_JUDGE`
* C: `gcc code implementer.c code.c -lm -O2 -DONLINE_JUDGE`
* Pascal: `fpc implementer.pas -o code -O2`

这样，一个交互题就大致做好了。你需要写 `implementer.cpp`、`implementer.c` 和 `implementer.pas`。当然你不想支持某个语言的话，就可以不写对应的交互库。

如果是 C/C++，正确姿势是搞一个统一的头文件放置接口，让 implementer 和选手程序都 include 这个头文件。把主函数写在 implementer 里，连起来编译时程序就是从 implementer 开始执行的了。

如果是 Pascal，正确姿势是让选手写一个 pascal unit 上来，在 implementer.pas 里 uses 一下。除此之外也要搞另外一个 pascal unit，里面存交互库的各种接口，让 implementer 和选手程序都 uses 一下。

#### 3. token 机制

下面我们来解释什么是 token。考虑一道典型的交互题，交互库跟选手函数交流得很愉快，最后给了个满分。此时交互库输出了 “`AC!!!`” 的字样，也可能输出了选手一共调用了几次接口。但是聪明的选手发现，只要自己手动输出一下 “`AC!!!`” 然后果断退出似乎就能骗过测评系统了哈哈哈。

这是因为选手函数和交互库已经融为一体成为了一个统一的程序，测评系统分不出谁是谁。这时候，token 就出来拯救世界了。

在 `problem.conf` 里配置一个奇妙的 token，比如 `token wahaha233`，然后把这个 token 写进交互库的代码里（线上交互库，不是样例交互库）。在交互库准备输出任何东西之前，先输出一下这个 token。当测评系统要给选手判分时，首先判断文件的第一行是不是 token，如果不是，直接零分。这样就避免了奇怪的选手 hack 测评系统了。这里的 token 判定是在调用 checker 之前，测评系统会把 token 去掉之后的选手输出喂给 checker。（所以一道交互题的 token 要好好保护好）

不过这样的防御手段并不是绝对的。理论上只要选手能编写一个能理解交互库的机器码的程序，试图自动模拟交互库行为，那是很难防住的。

另外，记得在 C/C++ 的交互库里给全局变量开 `static`，意为仅本文件里的代码可访问（即仅交互库可访问）。

### ▶ 简单通信题的配置

这里我们考虑最简单的通信题。有一个交互器 interactor，选手程序的输出会变成 interactor 的输入，而 interactor 的输出会变成选手程序的输入。

（有时这种题目也叫交互题。。但为了不致混淆我们这里称之为通信题。）

如果你想配置这样的题目，只需要在 `problem.conf` 中加一行
```text
interaction_mode on
```
然后编写程序 interactor.cpp，跟输入输出文件放在一起即可。interactor 也属于辅助测评程序，可以参照 “辅助测评程序” 这一小节的内容进行配置。比如，通过添加一行 `interactor_time_limit 2` 可将 interactor 的时间限制设为 2s。

UOJ 的交互器 interactor 与 Codeforces 的交互器有一点点不同。在 Codeforces 的测评逻辑里，交互器与选手程序交互后，会把一些信息输出到文件，然后再交由答案检查器 chk 作出最终评分。但是 UOJ 里的交互器相当于 interactor + chk，也就是说交互器需要直接给出最终评分。所以，当你在使用 [testlib](http://codeforces.com/testlib) 库编写交互器时，需要按照编写答案检查器的方式，在 `#!c++ main` 函数里第一句写上
```c++
registerTestlibCmd(argc, argv);
```
之后你就可以通过 `#!c++ ouf` 获取选手程序输出，通过 `#!c++ cout` 或者 `#!c++ printf` 向选手程序输出信息（不要忘了刷新缓冲区），通过 `#!c++ quitf` 或者 `#!c++ quitp` 打分。注意，**不要按照 Codeforces 的习惯**调用 `registerInteraction`。

如果你想实现更复杂一点通信，比如多个程序相互之间通信，那么你可能需要参考下一节 “意想不到的非传统题” 的内容自己编写 judger 了。

### ▶ 意想不到的非传统题

假如你有一道题不属于上述任何一种题目类型 —— 那你就得自己写测评器了！

测评器（judger）的任务是：给你选手提交的文件，输出一个测评结果。

把 problem.conf 里的 `use_builtin_judger on` 这行去掉，放一个 judger 程序的源代码放在题目数据目录下，就好了。

但怎么编写 judger 呢。。。？首先你需要写个 Makefile，UOJ 会根据你的 Makefile 和 judger 源代码生成 judger。如果你要使用自定义答案检查器 chk，那么你也需要在 Makefile 里面指定 chk 的编译方式。比如下面这个例子：
```makefile
export INCLUDE_PATH
CXXFLAGS = -I$(INCLUDE_PATH) -O2

all: chk judger

% : %.cpp
	$(CXX) $(CXXFLAGS) $< -o $@
```

什么？你问 judger 应该怎么写？

这部分文档我只能先【坑着】。不过这里有一点有助于你自己看懂代码的指南：

1. 你可以先从 UOJ 内置的测评器读起，在 `uoj_judger/builtin/judger/judger.cpp`；
2. 你会发现它引用了头文件 `uoj_judger.h`，大概读一读；
3. `uoj_judger.h` 调用了沙箱 `uoj_judger/run/run_program.cpp`，大概理解一下沙箱的参数和输入输出方式；
4. `uoj_judger.h` 还会用 `uoj_judger/run/compile.cpp` 来编译程序，用 `uoj_judger/run/run_interaction.cpp` 来处理程序间的通信，大概读一读；
5. 回过头来仔细理解一下 `uoj_judger.h` 的代码逻辑。

不过说实话 `uoj_judger.h` 封装得并不是很好。所以有计划重写一个封装得更好的库 `uoj_judger_v2.h`，这个库拖拖拉拉写了一半，但暂且还是先放了出来，供大家参考。期待神犇来造一造轮子！

最后，如果要使用自定义 judger 的话，就只有超级管理员能点击 “与 SVN 仓库同步” 了。这是因为 judger 的行为几乎不受限制，只能交给值得信赖的人编写。不过无论你的 judger 如何折腾，测评也是有 10 分钟的时间限制的。

## 四、样题

在[这个仓库](https://github.com/vfleaking/uoj-sample-problems)里我们公开了一些来自 UOJ 官网的题目数据，供大家参考。
