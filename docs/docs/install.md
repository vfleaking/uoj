# 安装

## 教练我想安装！

请参见 [README.md](https://github.com/vfleaking/uoj/blob/master/README.md) 来安装。

嘿嘿现在我假设你已经是超级管理员了！

那么你就可以新建题目了！

看看看，problem/1 这个文件夹，里面有一道样题，赶紧动手上传！（啊感觉我应该多给几道样题的）

关于如何传题请参见[题目文档](problem/)

啊如果你在 “与svn仓库同步” 的过程中发现 <samp>compile error</samp> 并且还说 <samp>no comment</samp> 的话……多半是……UOJ 尝试 ptrace 然而 ptrace 被禁了……

被禁了可能是被 docker 禁了，可以加一句 `--cap-add SYS_PTRACE` （见 [README.md](https://github.com/vfleaking/uoj/blob/master/README.md)）……要是这样不能解决问题……是 Ubuntu/openSUSE 环境嘛？请尝试用下面的命令阻止 AppArmor 对 docker 的行为产生影响。当然把第二行加到 `rc.local` 里就不用每次重启都输入一遍啦~（详细解释戳 [→ 这里](https://github.com/docker/docker/issues/7276)）

```sh
sudo apt-get install apparmor-utils
aa-complain /etc/apparmor.d/docker
```

要是这样还是不能解决问题……估计就是被黑恶势力禁掉了。。。大概就不是咱们这边的锅了，找找是哪个黑恶势力禁的吧。。。

另：如果上述方法不能解决 ptrace 问题也可以考虑使用 `--privileged`，但是这么做会导致 docker 失去其原有的隔离性而带来潜在的风险，不建议在生产环境中使用。

传题都没问题的话，感觉就可以愉快使用 UOJ 了！

## 教练我还想折腾！

嗯！来来来我们来折腾一波。

有个很厉害的配置文件，在 /var/www/uoj/app/.config.php 里，你可以配置一波……一个完全体如下：（有可能本文档没有跟上代码更新的速度，请以旁边的那个 .default-config.php 为准）

```php
<?php
return [
	'database' => [                         // 数据库相关
		'database'  => 'app_uoj233',            // 数据库名称
		'username' => 'root',                   // 用户名
		'password' => '',                       // 密码
		'host' => '127.0.0.1'                   // 数据库主机名
	],
	'web' => [                              // 网址相关
		'main' => [                             // 网站主体
			'protocol' => 'http',                   // 传输协议
			'host' => 'local_uoj.ac',               // 主机名
			'port' => 80                            // 端口
		],
		'blog' => [                             // UOJ 博客 （用户名放前面之后成为完整的域名）
			'protocol' => 'http',                   // 传输协议
			'host' => 'blog.local_uoj.ac',          // 主机名
			'port' => 80                            // 端口
		]
	],
	'security' => [                         // 安全相关（不要动）
		'user' => [
			'client_salt' => 'salt0'
		],
		'cookie' => [
			'checksum_salt' => ['salt1', 'salt2', 'salt3']
		],
	],
	'mail' => [                             // 邮件相关（SMTP 协议发送）
		'noreply' => [                          // noreply 邮箱
			'username' => 'noreply@none',
			'password' => 'noreply',
			'host' => 'smtp.sina.com',
			'port' => 25
		]
	],
	'judger' => [                           // 测评相关（不要动）
		'socket' => [                           // 与测评机的 socket 服务器通讯的设置
			'port' => '233',                        // 端口
			'password' => 'password233'             // 认证密码（证明自己 UOJ 服务器）
		]
	],
	'svn' => [                              // svn 相关（不要动）
		'our-root' => [                         // 每个题目的 svn 仓库自带的仓库管理员
			'username' => 'our-root',               // 管理员用户名
			'password' => 'our-root'                // 密码
		]
	],
	'switch' => [                           // 一些开关
		'ICP-license' => false,                 // ICP 备案信息的显示
		'web-analytics' => false,			    // 网站流量统计（记 uoj.ac 名下……想统计自己的得改代码）
		'blog-use-subdomain' => true			// 每个人的博客使用独立的子域名
	]
];
```

### 域名
如果你想使用自己的域名，改 config 即可。

如果你是搭校内网站这种的，可能没有 DNS。你可以选择自己搭校内的 DNS 解析，或者自己改 hosts。

或者你想成为一条咸鱼，可以直接在 config 里的 host 那里写 ip。

Q：为什么博客用不了？

A：博客的话需要给每个用户的博客域名进行解析，最好是用泛解析解决，这是出于安全考虑。如果没这条件，可以将配置文件里`switch`中的`blog-use-subdomain`改成false，这样博客url将以子目录的形式出现。

### 邮箱
noreply 邮箱的目的是发一些莫名其妙的邮件，比如 svn 密码和 “找回密码”。

你可以随便找一个邮箱小号把账号密码塞这里，但是记得查查邮件服务商的 SMTP 服务器名和端口。以及，有些邮箱是需要手动开启某个开关才能允许 SMTP 发送邮件的。
