<?php echoUOJPageHeader(UOJLocale::get('help')) ?>
<article class="uoj-article">
	<header>
		<h2 class="page-header">常见问题及其解答</h2>
	</header>
	<section>
		<header>
			<h4>1. 什么是UOJ</h4>
		</header>
		<p>见 <a href="https://uoj.ac/blog/4">https://uoj.ac/blog/4</a>。 </p>
	</section>
	<section>
		<header>
			<h4>2. 注册后怎么上传头像</h4>
		</header>
		<p>UOJ不提供头像存储服务。每到一个网站都要上传一个头像挺烦的对不对？UOJ支持Gravatar，请使用Gravatar吧！Gravatar是一个全球的头像存储服务，你的头像将会与你的电子邮箱绑定。在各大网站比如各种Wordpress还有各种OJ比如Vijos、Contest Hunter上，只要你电子邮箱填对了，那么你的头像也就立即能显示了！</p>
		<p>快使用Gravatar吧！ Gravatar地址：<a href="https://cn.gravatar.com/">https://cn.gravatar.com/</a>。进去后注册个帐号然后与邮箱绑定并上传头像，就ok啦！</p>
	</section>
	<section>
		<header>
			<h4>3. UOJ的测评环境</h4>
		</header>
		<p>测评环境是 Linux，Ubuntu 20.04，64位系统。</p>
		<p>C++的编译器是 g++ 10.3.0，编译命令：<code>g++ code.cpp -o code -lm -O2 -DONLINE_JUDGE</code>。如果选择 C++11 会在编译命令后面添加<code>-std=c++11</code>（C++14 / C++17 / C++20 同理）。</p>
		<p>C的编译器是 gcc 10.3.0，编译命令：<code>gcc code.c -o code -lm -O2 -DONLINE_JUDGE</code>。</p>
		<p>Pascal的编译器是 fpc 3.0.4，编译命令：<code>fpc code.pas -O2</code>。</p>
		<p>Java 7, 8, 11, 14使用的JDK分别为 Oracle JDK 7u80, Oracle JDK 8u202, OpenJDK 1.11.0, OpenJDK 1.14.0，编译命令：<code>javac code.java</code>。</p>
		<p>Python会先编译为优化过的字节码<samp>.pyo</samp>文件。支持的Python版本分别为 Python 2.7.18 和 3.9.5。</p>
	</section>
	<section>
		<header>
			<h4>4. 递归 10<sup>7</sup> 层怎么没爆栈啊</h4>
		</header>
		<p>没错就是这样！除非是特殊情况，UOJ测评程序时的栈大小与该题的空间限制是相等的！</p>
	</section>
	<section>
		<header>
			<h4>5. 博客使用指南</h4>
		</header>
		<p>见 <a href="https://uoj.ac/blog/7">https://uoj.ac/blog/7</a>。 </p>
	</section>
	<section>
		<header>
			<h4>6. 交互式类型的题怎么本地测试</h4>
		</header>
		<p>唔……好问题。交互式的题一般给了一个头文件要你include进来，以及一个实现接口的源文件grader。好像大家对多个源文件一起编译还不太熟悉。</p>
		<p>对于C++：<code>g++ -o code grader.cpp code.cpp</code></p>
		<p>对于C语言：<code>gcc -o code grader.c code.c</code></p>
		<p>如果你是悲催的电脑盲，实在不会折腾没关系！你可以把grader的文件内容完整地粘贴到你的code的include语句之后，就可以了！</p>
		<p>什么你是萌萌哒Pascal选手？一般来说都会给个grader，你需要写一个Pascal单元。这个grader会使用你的单元。所以你只需要把源文件取名为单元名 + <code>.pas</code>，然后：</p>
		<p>对于Pascal语言：<code>fpc grader.pas</code></p>
		<p>就可以啦！</p>
	</section>
	<section>
		<header>
			<h4>7. 联系方式</h4>
		</header>
		<p>如果你想出题、想办比赛，或者发现了BUG，或者对网站有什么建议，可以通过下面的方式联系我们：</p>
		<ul>
			<li>UOJ私信联系vfleaking。</li>
			<li>邮件联系vfleaking@163.com。</li>
			<li>你也可以进QQ群水水，群号是197293072，Universal OJ用户群。</li>
		</ul>
	</section>
</article>

<?php echoUOJPageFooter() ?>
