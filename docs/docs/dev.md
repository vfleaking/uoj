# 开发
听说你想写代码？

svn://local\_uoj.ac/uoj 和 svn://local\_uoj.ac/judge_client 欢迎你（如果你没改 UOJ 的 hostname 的话），这是两个 svn 仓库，至于 svn 仓库权限问题……你需要手工加一下……

在 /var/svn/uoj/conf/passwd 这个文件中你可以加一行
<pre>
uoj = 666666
</pre>
来增加一位名为 "<samp>uoj</samp>"，密码为 "<samp>666666</samp>" 的 svn 仓库管理员。

之后你就可以随意用 svn 玩耍了哇咔咔……

在本地写完之后，如果你想与别人分享，你可以把代码再放到 git 中（什么鬼？）再 push 到 github。

如果你想更新的东西已经不局限于网站代码，还想对数据库、文件系统之类的折腾一番，请在 app/upgrade 目录下建立文件夹，举个例子，叫 `2333_create_table_qaq`。即，以一串数字开头，后面加一个下划线，再后面随便取名字吧，仅能包含数字字母和下划线。

在这个文件夹下你可以放一些小脚本。大概 UOJ 运行这些小脚本是这么个逻辑：
```php
if (is_file("{$dir}/upgrade.php")) {
	$fun = include "{$dir}/upgrade.php";
	$fun("up");
}
if (is_file("{$dir}/up.sql")) {
	runSQL("{$dir}/up.sql");
}
if (is_file("{$dir}/upgrade.sh")) {
	runShell("/bin/bash {$dir}/upgrade.sh up");
}
```
你只需要在 /var/www/uoj/app 下执行 `php cli.php upgrade:up 2333_create_table_qaq` 就可以运行了，这个运行过程我们称为 `up`。

请务必再写一下还原的小脚本，我们称为 `down`。写完代码后你需要保证 `up` 再 `down` 能回到原来的系统。与 `up` 类似，你需要执行 `php cli.php upgrade:down 2333_create_table_qaq`，执行 `down` 的逻辑如下：
```php
if (is_file("{$dir}/upgrade.php")) {
	$fun = include "{$dir}/upgrade.php";
	$fun("down");
}
if (is_file("{$dir}/down.sql")) {
	runSQL("{$dir}/down.sql");
}
if (is_file("{$dir}/upgrade.sh")) {
	runShell("/bin/bash {$dir}/upgrade.sh down");
}
```

在数据库中，UOJ 会记录自己已经加载了哪些 upgrade，当你执行 `php cli.php upgrade:latest` 的时候，UOJ 会把所有已经在 app/upgrade 文件夹下但还没有加载的 upgrade 都给 `up` 一下，并且是按前缀上的那一串数字从小到大执行。所以写好了这种小 upgrade 之后，你就可以跟别人分享了！

不过关于架构什么的介绍我还是先
<p style="font-size:233px">坑着</p>
