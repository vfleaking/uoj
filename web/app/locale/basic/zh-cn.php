<?php
return [
	'login' => '登录',
	'register' => '注册',
	'logout' => '登出',
	'my profile' => '个人信息',
	'private message' => '私信',
	'system message' => '系统消息',
	'system manage' => '系统管理',
	'contests' => '比赛',
	'problems' => '题库',
	'submissions' => '提交记录',
	'hacks' => 'Hack!',
	'blogs' => '博客',
	'announcements' => '公告',
	'all the announcements' => '所有公告……',
	'help' => '帮助',
	'search' => '搜索',
	'top rated - all users' => '比赛排行榜 - 所有用户',
	'top rated - active users' => '比赛排行榜 - 活跃用户',
	'top rated > all users' => '比赛排行榜 <span class="glyphicon glyphicon-chevron-right"></span> 所有用户',
	'top rated > active users' => '比赛排行榜 <span class="glyphicon glyphicon-chevron-right"></span> 活跃用户',
	'username' => '用户名',
	'password' => '密码',
	'new password' => '新密码',
	'email' => 'Email',
	'QQ' => 'QQ',
	'sex' => '性别',
	'motto' => '格言',
	'rating' => 'Rating',
	'view all' => '查看全部',
	'appraisal' => '评价',
	'submit' => '提交',
	'browse' => '浏览',
	'score range' => '分数范围',
	'details' => '详细',
	'hours' => function($h) {
		return "$h 小时";
	},
	'title' => '标题',
	'content' => '内容',
	'time' => '时间',
	'none' => '无',
	'user profile' => '用户信息',
	'send private message' => '发送私信',
	'modify my profile' => '更改个人信息',
	'visit his blog' => function($name) {
		return "访问 $name 的博客";
	},
	'rating changes' => 'Rating 变化',
	'accepted problems' => 'AC 过的题目',
	'n problems in total' => function($n) {
		return "共 $n 道题";
	},
	'please enter your password for authorization' => '请输入您的密码进行身份验证',
	'please enter your new profile' => '请输入新的个人信息',
	'leave it blank if you do not want to change the password' => '如果不想修改密码请留空',
	'change avatar help' => '想改头像？见<a href="/faq">帮助</a>',
	'enter your username' => '输入用户名',
	'enter your email' => '输入Email',
	'enter your password' => '输入密码',
	're-enter your password' => '重新输入密码',
	'enter your new password' => '输入新密码',
	're-enter your new password' => '重新输入新密码',
	'enter your QQ' => '输入QQ',
	'refuse to answer' => '拒绝回答',
	'male' => '男',
	'female' => '女',
	
	'active rule' => function($m) {
		if (is_numeric($m) && $m % 12 == 0) {
			$m = ($m/12).'年';
		} else {
			$m = "{$m}个月";
		}
		return "UOJ上的最新比赛及在此之前的<strong>{$m}内</strong>的所有比赛中，如果一个用户参加过其中任何一场比赛，则判定为活跃用户。";
	}
];