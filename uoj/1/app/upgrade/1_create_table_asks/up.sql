CREATE TABLE IF NOT EXISTS `contests_asks` (
  `id` int(11) NOT NULL primary key AUTO_INCREMENT,
  `contest_id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `post_time` datetime NOT NULL,
  `reply_time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
