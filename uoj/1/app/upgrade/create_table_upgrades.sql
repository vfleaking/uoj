create table upgrades (
	name varchar(50) NOT NULL,
	status ENUM('up', 'down'),
	updated_at datetime NOT NULL,
	
	PRIMARY KEY (name)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
