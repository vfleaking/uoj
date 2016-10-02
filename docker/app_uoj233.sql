-- phpMyAdmin SQL Dump
-- version 4.2.7.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2016-08-29 09:19:31
-- 服务器版本： 5.5.38-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `app_uoj233`
--
CREATE DATABASE IF NOT EXISTS `app_uoj233` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `app_uoj233`;

-- --------------------------------------------------------

--
-- 表的结构 `best_ac_submissions`
--

CREATE TABLE IF NOT EXISTS `best_ac_submissions` (
  `problem_id` int(11) NOT NULL,
  `submitter` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `submission_id` int(11) NOT NULL,
  `used_time` int(11) NOT NULL,
  `used_memory` int(11) NOT NULL,
  `tot_size` int(11) NOT NULL,
  `shortest_id` int(11) NOT NULL,
  `shortest_used_time` int(11) NOT NULL,
  `shortest_used_memory` int(11) NOT NULL,
  `shortest_tot_size` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `blogs`
--

CREATE TABLE IF NOT EXISTS `blogs` (
`id` int(11) NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `content` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `poster` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `content_md` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `zan` int(11) NOT NULL,
  `is_hidden` tinyint(1) NOT NULL,
  `type` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'B',
  `is_draft` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `blogs_comments`
--

CREATE TABLE IF NOT EXISTS `blogs_comments` (
`id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `poster` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `zan` int(11) NOT NULL,
  `reply_id` int(11) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `blogs_tags`
--

CREATE TABLE IF NOT EXISTS `blogs_tags` (
`id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `tag` varchar(30) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `click_zans`
--

CREATE TABLE IF NOT EXISTS `click_zans` (
  `type` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `target_id` int(11) NOT NULL,
  `val` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `contests`
--

CREATE TABLE IF NOT EXISTS `contests` (
`id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_time` datetime NOT NULL,
  `last_min` int(11) NOT NULL,
  `player_num` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `extra_config` varchar(200) NOT NULL,
  `zan` int(11) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `contests_notice`
--

CREATE TABLE IF NOT EXISTS `contests_notice` (
  `contest_id` int(11) NOT NULL,
  `title` varchar(30) NOT NULL,
  `content` varchar(500) NOT NULL,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `contests_permissions`
--

CREATE TABLE IF NOT EXISTS `contests_permissions` (
  `username` varchar(20) NOT NULL,
  `contest_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `contests_problems`
--

CREATE TABLE IF NOT EXISTS `contests_problems` (
  `problem_id` int(11) NOT NULL,
  `contest_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `contests_registrants`
--

CREATE TABLE IF NOT EXISTS `contests_registrants` (
  `username` varchar(20) NOT NULL,
  `user_rating` int(11) NOT NULL,
  `contest_id` int(11) NOT NULL,
  `has_participated` tinyint(1) NOT NULL,
  `rank` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `contests_submissions`
--

CREATE TABLE IF NOT EXISTS `contests_submissions` (
  `contest_id` int(11) NOT NULL,
  `submitter` varchar(20) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `penalty` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `custom_test_submissions`
--

CREATE TABLE IF NOT EXISTS `custom_test_submissions` (
`id` int(10) unsigned NOT NULL,
  `problem_id` int(10) unsigned NOT NULL,
  `submit_time` datetime NOT NULL,
  `submitter` varchar(20) NOT NULL,
  `content` text NOT NULL,
  `judge_time` datetime DEFAULT NULL,
  `result` blob NOT NULL,
  `status` varchar(20) NOT NULL,
  `status_details` varchar(100) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `hacks`
--

CREATE TABLE IF NOT EXISTS `hacks` (
`id` int(10) unsigned NOT NULL,
  `problem_id` int(10) unsigned NOT NULL,
  `contest_id` int(10) unsigned DEFAULT NULL,
  `submission_id` int(10) unsigned NOT NULL,
  `hacker` varchar(20) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `input` varchar(150) NOT NULL,
  `input_type` char(20) NOT NULL,
  `submit_time` datetime NOT NULL,
  `judge_time` datetime DEFAULT NULL,
  `success` tinyint(1) DEFAULT NULL,
  `details` blob NOT NULL,
  `is_hidden` tinyint(1) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `important_blogs`
--

CREATE TABLE IF NOT EXISTS `important_blogs` (
  `blog_id` int(11) NOT NULL,
  `level` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `judger_info`
--

CREATE TABLE IF NOT EXISTS `judger_info` (
  `judger_name` varchar(50) NOT NULL,
  `password` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ip` char(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `problems`
--

CREATE TABLE IF NOT EXISTS `problems` (
`id` int(10) unsigned NOT NULL,
  `title` text NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `submission_requirement` text,
  `hackable` tinyint(1) NOT NULL DEFAULT '1',
  `extra_config` varchar(500) NOT NULL DEFAULT '{"view_content_type":"ALL","view_details_type":"ALL"}',
  `zan` int(11) NOT NULL,
  `ac_num` int(11) NOT NULL DEFAULT '0',
  `submit_num` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `problems_contents`
--

CREATE TABLE IF NOT EXISTS `problems_contents` (
  `id` int(11) NOT NULL,
  `statement` mediumtext NOT NULL,
  `statement_md` mediumtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `problems_permissions`
--

CREATE TABLE IF NOT EXISTS `problems_permissions` (
  `username` varchar(20) NOT NULL,
  `problem_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `problems_tags`
--

CREATE TABLE IF NOT EXISTS `problems_tags` (
`id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `tag` varchar(30) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `search_requests`
--

CREATE TABLE IF NOT EXISTS `search_requests` (
`id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `remote_addr` varchar(50) NOT NULL,
  `type` enum('search','autocomplete') NOT NULL,
  `cache_id` int(11) NOT NULL,
  `q` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `result` mediumtext NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `submissions`
--

CREATE TABLE IF NOT EXISTS `submissions` (
`id` int(10) unsigned NOT NULL,
  `problem_id` int(10) unsigned NOT NULL,
  `contest_id` int(10) unsigned DEFAULT NULL,
  `submit_time` datetime NOT NULL,
  `submitter` varchar(20) NOT NULL,
  `content` text NOT NULL,
  `language` varchar(15) NOT NULL,
  `tot_size` int(11) NOT NULL,
  `judge_time` datetime DEFAULT NULL,
  `result` blob NOT NULL,
  `status` varchar(20) NOT NULL,
  `result_error` varchar(20) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `used_time` int(11) NOT NULL DEFAULT '0',
  `used_memory` int(11) NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL,
  `status_details` varchar(100) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `user_info`
--

CREATE TABLE IF NOT EXISTS `user_info` (
  `usergroup` char(1) NOT NULL DEFAULT 'U',
  `username` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` char(32) NOT NULL,
  `svn_password` char(10) NOT NULL,
  `rating` int(11) NOT NULL DEFAULT '1500',
  `qq` bigint(20) NOT NULL,
  `sex` char(1) NOT NULL DEFAULT 'U',
  `ac_num` int(11) NOT NULL,
  `register_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remote_addr` varchar(50) NOT NULL,
  `http_x_forwarded_for` varchar(50) NOT NULL,
  `remember_token` char(60) NOT NULL,
  `motto` varchar(200) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `user_msg`
--

CREATE TABLE IF NOT EXISTS `user_msg` (
`id` int(10) unsigned NOT NULL,
  `sender` varchar(20) NOT NULL,
  `receiver` varchar(20) NOT NULL,
  `message` varchar(5000) NOT NULL,
  `send_time` datetime NOT NULL,
  `read_time` datetime DEFAULT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `user_system_msg`
--

CREATE TABLE IF NOT EXISTS `user_system_msg` (
`id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `content` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `receiver` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `send_time` datetime NOT NULL,
  `read_time` datetime DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `best_ac_submissions`
--
ALTER TABLE `best_ac_submissions`
 ADD PRIMARY KEY (`problem_id`,`submitter`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs_comments`
--
ALTER TABLE `blogs_comments`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs_tags`
--
ALTER TABLE `blogs_tags`
 ADD PRIMARY KEY (`id`), ADD KEY `blog_id` (`blog_id`), ADD KEY `tag` (`tag`);

--
-- Indexes for table `click_zans`
--
ALTER TABLE `click_zans`
 ADD PRIMARY KEY (`type`,`target_id`,`username`);

--
-- Indexes for table `contests`
--
ALTER TABLE `contests`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contests_notice`
--
ALTER TABLE `contests_notice`
 ADD KEY `contest_id` (`contest_id`);

--
-- Indexes for table `contests_permissions`
--
ALTER TABLE `contests_permissions`
 ADD PRIMARY KEY (`username`,`contest_id`);

--
-- Indexes for table `contests_problems`
--
ALTER TABLE `contests_problems`
 ADD PRIMARY KEY (`problem_id`,`contest_id`);

--
-- Indexes for table `contests_registrants`
--
ALTER TABLE `contests_registrants`
 ADD PRIMARY KEY (`contest_id`,`username`);

--
-- Indexes for table `contests_submissions`
--
ALTER TABLE `contests_submissions`
 ADD PRIMARY KEY (`contest_id`,`submitter`,`problem_id`);

--
-- Indexes for table `custom_test_submissions`
--
ALTER TABLE `custom_test_submissions`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hacks`
--
ALTER TABLE `hacks`
 ADD PRIMARY KEY (`id`), ADD KEY `submission_id` (`submission_id`), ADD KEY `is_hidden` (`is_hidden`,`problem_id`);

--
-- Indexes for table `important_blogs`
--
ALTER TABLE `important_blogs`
 ADD PRIMARY KEY (`blog_id`);

--
-- Indexes for table `judger_info`
--
ALTER TABLE `judger_info`
 ADD PRIMARY KEY (`judger_name`);

--
-- Indexes for table `problems`
--
ALTER TABLE `problems`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `problems_contents`
--
ALTER TABLE `problems_contents`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `problems_permissions`
--
ALTER TABLE `problems_permissions`
 ADD PRIMARY KEY (`username`,`problem_id`);

--
-- Indexes for table `problems_tags`
--
ALTER TABLE `problems_tags`
 ADD PRIMARY KEY (`id`), ADD KEY `problem_id` (`problem_id`), ADD KEY `tag` (`tag`);

--
-- Indexes for table `search_requests`
--
ALTER TABLE `search_requests`
 ADD PRIMARY KEY (`id`), ADD KEY `remote_addr` (`remote_addr`,`created_at`), ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
 ADD PRIMARY KEY (`id`), ADD KEY `is_hidden` (`is_hidden`,`problem_id`);

--
-- Indexes for table `user_info`
--
ALTER TABLE `user_info`
 ADD PRIMARY KEY (`username`), ADD KEY `rating` (`rating`,`username`), ADD KEY `ac_num` (`ac_num`,`username`);

--
-- Indexes for table `user_msg`
--
ALTER TABLE `user_msg`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_system_msg`
--
ALTER TABLE `user_system_msg`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `blogs_comments`
--
ALTER TABLE `blogs_comments`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `blogs_tags`
--
ALTER TABLE `blogs_tags`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `contests`
--
ALTER TABLE `contests`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `custom_test_submissions`
--
ALTER TABLE `custom_test_submissions`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `hacks`
--
ALTER TABLE `hacks`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `problems`
--
ALTER TABLE `problems`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `problems_tags`
--
ALTER TABLE `problems_tags`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `search_requests`
--
ALTER TABLE `search_requests`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_msg`
--
ALTER TABLE `user_msg`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_system_msg`
--
ALTER TABLE `user_system_msg`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
