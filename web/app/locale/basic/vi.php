<?php
return [
	'login' => 'Đăng nhập',
	'register' => 'Đăng ký',
	'logout' => 'Đăng xuất',
	'my profile' => 'Thông tin cá nhân',
	'private message' => 'Tin nhắn riêng',
	'system message' => 'Tin nhắn hệ thống',
	'system manage' => 'Quản lý hệ thống',
	'contests' => 'Cuộc thi',
	'problems' => 'Bài tập',
	'submissions' => 'Bài nộp',
	'hacks' => 'Hack!',
	'blogs' => 'Blog',
	'announcements' => 'Thông báo',
	'all the announcements' => 'Tất cả thông báo……',
	'help' => 'Trợ giúp',
	'search' => 'Tìm kiếm',
	'top rated - all users' => 'Bảng xếp hạng - Tất cả người dùng',
	'top rated - active users' => 'Bảng xếp hạng - Người dùng hoạt động',
	'top rated > all users' => 'Bảng xếp hạng <span class="glyphicon glyphicon-chevron-right"></span> Tất cả người dùng',
	'top rated > active users' => 'Bảng xếp hạng <span class="glyphicon glyphicon-chevron-right"></span> Người dùng hoạt động',
	'username' => 'Tên người dùng',
	'password' => 'Mật khẩu',
	'new password' => 'Mật khẩu mới',
	'email' => 'Email',
	'QQ' => 'QQ',
	'sex' => 'Giới tính',
	'motto' => 'Phương châm',
	'rating' => 'Xếp hạng',
	'view all' => 'Xem tất cả',
	'appraisal' => 'Đánh giá',
	'submit' => 'Nộp bài',
	'browse' => 'Duyệt',
	'score range' => 'Khoảng điểm',
	'details' => 'Chi tiết',
	'hours' => function($h) {
		return "$h giờ";
	},
	'title' => 'Tiêu đề',
	'content' => 'Nội dung',
	'time' => 'Thời gian',
	'none' => 'Không có',
	'user profile' => 'Thông tin người dùng',
	'send private message' => 'Gửi tin nhắn riêng',
	'modify my profile' => 'Thay đổi thông tin cá nhân',
	'visit his blog' => function($name) {
		return "Truy cập blog của $name";
	},
	'rating changes' => 'Thay đổi xếp hạng',
	'accepted problems' => 'Bài tập đã AC',
	'n problems in total' => function($n) {
		return "Tổng cộng $n bài";
	},
	'please enter your password for authorization' => 'Vui lòng nhập mật khẩu để xác thực',
	'please enter your new profile' => 'Vui lòng nhập thông tin cá nhân mới',
	'leave it blank if you do not want to change the password' => 'Để trống nếu bạn không muốn thay đổi mật khẩu',
	'change avatar help' => 'Muốn đổi ảnh đại diện? Xem <a href="/faq">Trợ giúp</a>',
	'enter your username' => 'Nhập tên người dùng',
	'enter your email' => 'Nhập Email',
	'enter your password' => 'Nhập mật khẩu',
	're-enter your password' => 'Nhập lại mật khẩu',
	'enter your new password' => 'Nhập mật khẩu mới',
	're-enter your new password' => 'Nhập lại mật khẩu mới',
	'enter your QQ' => 'Nhập QQ',
	'refuse to answer' => 'Không trả lời',
	'male' => 'Nam',
	'female' => 'Nữ',
	
	'active rule' => function($m) {
		if (is_numeric($m) && $m % 12 == 0) {
			$m = ($m/12).' năm';
		} else {
			$m = "{$m} tháng";
		}
		return "Trong cuộc thi mới nhất trên UOJ và tất cả các cuộc thi diễn ra trong vòng <strong>{$m}</strong> trước đó, nếu một người dùng tham gia bất kỳ cuộc thi nào trong số đó, họ được coi là người dùng hoạt động.";
	}
];
