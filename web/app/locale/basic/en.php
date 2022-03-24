<?php
return [
	'login' => 'Login',
	'register' => 'Register',
	'logout' => 'Logout',
	'my profile' => 'My Profile',
	'private message' => 'Private Messages',
	'system message' => 'System Messages',
	'system manage' => 'System Management',
	'contests' => 'Contests',
	'problems' => 'Problems',
	'submissions' => 'Submissions',
	'hacks' => 'Hack!',
	'blogs' => 'Blogs',
	'announcements' => 'Announcements',
	'all the announcements' => 'All the Announcements……',
	'help' => 'Help',
	'search' => 'Search',
	'top rated - all users' => 'Top Rated - All Users',
	'top rated - active users' => 'Top Rated - Active Users',
	'top rated > all users' => 'Top Rated <span class="glyphicon glyphicon-chevron-right"></span> All Users',
	'top rated > active users' => 'Top Rated <span class="glyphicon glyphicon-chevron-right"></span> Active Users',
	'username' => 'Username',
	'password' => 'Password',
	'new password' => 'New password',
	'email' => 'Email',
	'QQ' => 'QQ',
	'sex' => 'Sex',
	'motto' => 'Motto',
	'rating' => 'Rating',
	'view all' => 'View all',
	'appraisal' => 'Rating',
	'submit' => 'Submit',
	'browse' => 'Browse',
	'score range' => 'Score range',
	'details' => 'Details',
	'hours' => function($h) {
		return "$h ".($h <= 1 ? 'hour' : 'hours');
	},
	'title' => 'Title',
	'content' => 'Content',
	'time' => 'Time',
	'none' => 'None',
	'user profile' => 'User profile',
	'send private message' => 'Send private messages',
	'modify my profile' => 'Modify my profile',
	'visit his blog' => function($name) {
		return "Visit $name's blog";
	},
	'rating changes' => 'Rating changes',
	'accepted problems' => 'Accepted problems',
	'n problems in total' => function($n) {
		return "$n ".($n <= 1 ? 'problem' : 'problems');
	},
	'please enter your password for authorization' => 'Please enter your password for authorization',
	'please enter your new profile' => 'Please enter your new profile',
	'leave it blank if you do not want to change the password' => 'Leave it blank if you do not want to change the password',
	'change avatar help' => 'Do you want to change your avatar? Please see <a href="/faq">Help</a>',
	'enter your username' => 'Enter your username',
	'enter your email' => 'Enter your email',
	'enter your password' => 'Enter your password',
	're-enter your password' => 'Re-enter your password',
	'enter your new password' => 'Enter your new password',
	're-enter your new password' => 'Re-enter your new password',
	'enter your QQ' => 'Enter your QQ',
	'refuse to answer' => 'Refuse to answer',
	'male' => 'Male',
	'female' => 'Female',

	'active rule' => function($m) {
		if (is_numeric($m)) {
			if ($m % 12 == 0) {
				$m /= 12;
				$m = $m <= 1 ? "{$m} year" : "{$m} years";
			} else {
				$m = $m <= 1 ? "{$m} month" : "{$m} months";
			}
		} else {
			$m = "{$m} month(s)";
		}
		return "Among the latest contest on UOJ and all the contests that hold within $m before that, if a user participated in any of them, then this user is regarded as an active user.";
	}
];
