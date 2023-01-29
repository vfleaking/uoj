SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `app_uoj233`
--
CREATE DATABASE IF NOT EXISTS `app_uoj233` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `app_uoj233`;

-- --------------------------------------------------------

--
-- Table structure for table `best_ac_submissions`
--

CREATE TABLE `best_ac_submissions` (
  `problem_id` int NOT NULL,
  `submitter` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submission_id` int NOT NULL,
  `used_time` int NOT NULL,
  `used_memory` int NOT NULL,
  `tot_size` int NOT NULL,
  `shortest_id` int NOT NULL,
  `shortest_used_time` int NOT NULL,
  `shortest_used_memory` int NOT NULL,
  `shortest_tot_size` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci TABLESPACE `innodb_system`;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int NOT NULL,
  `title` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `active_time` datetime NOT NULL,
  `poster` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_md` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `zan` int NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL,
  `type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'B',
  `is_draft` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blogs_comments`
--

CREATE TABLE `blogs_comments` (
  `id` int NOT NULL,
  `blog_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `poster` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zan` int NOT NULL DEFAULT '0',
  `reply_id` int NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `reason_to_hide` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blogs_tags`
--

CREATE TABLE `blogs_tags` (
  `id` int NOT NULL,
  `blog_id` int NOT NULL,
  `tag` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `click_zans`
--

CREATE TABLE `click_zans` (
  `type` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `val` tinyint NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contests`
--

CREATE TABLE `contests` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime GENERATED ALWAYS AS ((`start_time` + interval `last_min` minute)) VIRTUAL NOT NULL,
  `last_min` int NOT NULL,
  `player_num` int NOT NULL DEFAULT '0',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra_config` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '{}',
  `zan` int NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contests_asks`
--

CREATE TABLE `contests_asks` (
  `id` int NOT NULL,
  `contest_id` int NOT NULL,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `reply_time` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `is_hidden` tinyint(1) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contests_notice`
--

CREATE TABLE `contests_notice` (
  `contest_id` int NOT NULL,
  `title` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contests_permissions`
--

CREATE TABLE `contests_permissions` (
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contest_id` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contests_problems`
--

CREATE TABLE `contests_problems` (
  `problem_id` int NOT NULL,
  `contest_id` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contests_registrants`
--

CREATE TABLE `contests_registrants` (
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_rating` int NOT NULL,
  `contest_id` int NOT NULL,
  `has_participated` tinyint(1) NOT NULL,
  `final_rank` int NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contests_submissions`
--

CREATE TABLE `contests_submissions` (
  `contest_id` int NOT NULL,
  `submitter` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `problem_id` int NOT NULL,
  `submission_id` int NOT NULL,
  `score` int NOT NULL,
  `penalty` int NOT NULL,
  `cnt` int DEFAULT NULL,
  `n_failures` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_test_submissions`
--

CREATE TABLE `custom_test_submissions` (
  `id` int UNSIGNED NOT NULL,
  `problem_id` int UNSIGNED NOT NULL,
  `submit_time` datetime NOT NULL,
  `submitter` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `judge_time` datetime DEFAULT NULL,
  `result` blob NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_details` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hacks`
--

CREATE TABLE `hacks` (
  `id` int UNSIGNED NOT NULL,
  `problem_id` int UNSIGNED NOT NULL,
  `contest_id` int UNSIGNED DEFAULT NULL,
  `submission_id` int UNSIGNED NOT NULL,
  `hacker` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_type` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submit_time` datetime NOT NULL,
  `judge_time` datetime DEFAULT NULL,
  `success` tinyint(1) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` blob NOT NULL,
  `is_hidden` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `important_blogs`
--

CREATE TABLE `important_blogs` (
  `blog_id` int NOT NULL,
  `level` int NOT NULL,
  `is_new` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `judger_info`
--

CREATE TABLE `judger_info` (
  `judger_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `display_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meta`
--

CREATE TABLE `meta` (
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `problems`
--

CREATE TABLE `problems` (
  `id` int UNSIGNED NOT NULL,
  `title` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `submission_requirement` mediumtext COLLATE utf8mb4_unicode_ci,
  `hackable` tinyint(1) NOT NULL DEFAULT '1',
  `extra_config` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '{"view_content_type":"ALL","view_details_type":"ALL"}',
  `zan` int NOT NULL DEFAULT '0',
  `ac_num` int NOT NULL DEFAULT '0',
  `submit_num` int NOT NULL DEFAULT '0',
  `assigned_to_judger` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'any'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci TABLESPACE `innodb_system`;

-- --------------------------------------------------------

--
-- Table structure for table `problems_contents`
--

CREATE TABLE `problems_contents` (
  `id` int NOT NULL,
  `statement` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `statement_md` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `problems_permissions`
--

CREATE TABLE `problems_permissions` (
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `problem_id` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `problems_tags`
--

CREATE TABLE `problems_tags` (
  `id` int NOT NULL,
  `problem_id` int NOT NULL,
  `tag` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_requests`
--

CREATE TABLE `search_requests` (
  `id` int NOT NULL,
  `created_at` datetime NOT NULL,
  `remote_addr` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('search','autocomplete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cache_id` int NOT NULL,
  `q` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `result` json NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int UNSIGNED NOT NULL,
  `problem_id` int UNSIGNED NOT NULL,
  `contest_id` int UNSIGNED DEFAULT NULL,
  `submit_time` datetime NOT NULL,
  `submitter` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tot_size` int NOT NULL,
  `judge_reason` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `judge_time` datetime DEFAULT NULL,
  `judger` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result` mediumblob NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `result_error` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` int DEFAULT NULL,
  `hide_score_to_others` tinyint(1) NOT NULL DEFAULT '0',
  `hidden_score` int DEFAULT NULL,
  `used_time` int NOT NULL DEFAULT '0',
  `used_memory` int NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL,
  `status_details` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions_history`
--

CREATE TABLE `submissions_history` (
  `id` int UNSIGNED NOT NULL,
  `submission_id` int UNSIGNED NOT NULL,
  `judge_reason` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `judge_time` datetime DEFAULT NULL,
  `judger` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result` mediumblob NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_details` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result_error` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` int DEFAULT NULL,
  `used_time` int NOT NULL DEFAULT '0',
  `used_memory` int NOT NULL DEFAULT '0',
  `major` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_updates`
--

CREATE TABLE `system_updates` (
  `id` int UNSIGNED NOT NULL,
  `time` datetime NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int UNSIGNED NOT NULL,
  `message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `upgrades`
--

CREATE TABLE `upgrades` (
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('up','down') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `usergroup` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'U',
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `svn_password` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int NOT NULL DEFAULT '1500',
  `qq` bigint NOT NULL DEFAULT '0',
  `sex` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'U',
  `ac_num` int NOT NULL DEFAULT '0',
  `register_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `expiration_time` datetime DEFAULT NULL,
  `remote_addr` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `http_x_forwarded_for` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remember_token` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `motto` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extra` json NOT NULL,
  `active_in_contest` tinyint(1) GENERATED ALWAYS AS (json_extract(`extra`,_utf8mb4'$.active_in_contest')) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_msg`
--

CREATE TABLE `user_msg` (
  `id` int UNSIGNED NOT NULL,
  `sender` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_time` datetime NOT NULL,
  `read_time` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_system_msg`
--

CREATE TABLE `user_system_msg` (
  `id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_time` datetime NOT NULL,
  `read_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_time` (`post_time`),
  ADD KEY `active_time` (`active_time`),
  ADD KEY `poster` (`poster`,`is_hidden`);

--
-- Indexes for table `blogs_comments`
--
ALTER TABLE `blogs_comments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reply_id` (`reply_id`,`id`),
  ADD KEY `blog_id` (`blog_id`,`post_time`),
  ADD KEY `blog_id_2` (`blog_id`,`reply_id`);

--
-- Indexes for table `blogs_tags`
--
ALTER TABLE `blogs_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `blog_id` (`blog_id`),
  ADD KEY `tag` (`tag`);

--
-- Indexes for table `click_zans`
--
ALTER TABLE `click_zans`
  ADD PRIMARY KEY (`type`,`target_id`,`username`);

--
-- Indexes for table `contests`
--
ALTER TABLE `contests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`,`id`) USING BTREE;

--
-- Indexes for table `contests_asks`
--
ALTER TABLE `contests_asks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contest_id` (`contest_id`,`is_hidden`,`username`) USING BTREE,
  ADD KEY `username` (`username`,`contest_id`) USING BTREE;

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
  ADD PRIMARY KEY (`problem_id`,`contest_id`),
  ADD KEY `contest_id` (`contest_id`,`problem_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `submitter` (`submitter`,`problem_id`,`id`),
  ADD KEY `judge_time` (`judge_time`,`id`);

--
-- Indexes for table `hacks`
--
ALTER TABLE `hacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `is_hidden` (`is_hidden`,`problem_id`),
  ADD KEY `status` (`status`),
  ADD KEY `judge_time` (`judge_time`);

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
-- Indexes for table `meta`
--
ALTER TABLE `meta`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `problems`
--
ALTER TABLE `problems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to_judger` (`assigned_to_judger`);

--
-- Indexes for table `problems_contents`
--
ALTER TABLE `problems_contents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `problems_permissions`
--
ALTER TABLE `problems_permissions`
  ADD PRIMARY KEY (`username`,`problem_id`),
  ADD KEY `problem_id` (`problem_id`);

--
-- Indexes for table `problems_tags`
--
ALTER TABLE `problems_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `problem_id` (`problem_id`),
  ADD KEY `tag` (`tag`);

--
-- Indexes for table `search_requests`
--
ALTER TABLE `search_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `remote_addr` (`remote_addr`,`created_at`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`,`id`),
  ADD KEY `result_error` (`result_error`),
  ADD KEY `problem_id` (`problem_id`,`id`),
  ADD KEY `language` (`language`,`id`),
  ADD KEY `language2` (`is_hidden`,`language`,`id`),
  ADD KEY `user_score` (`problem_id`,`submitter`,`score`,`id`),
  ADD KEY `problem_id2` (`is_hidden`,`problem_id`,`id`),
  ADD KEY `id2` (`is_hidden`,`id`),
  ADD KEY `problem_score2` (`is_hidden`,`problem_id`,`score`,`id`),
  ADD KEY `contest_submission_status` (`contest_id`,`status`),
  ADD KEY `submitter2` (`is_hidden`,`submitter`,`id`),
  ADD KEY `submitter` (`submitter`,`id`) USING BTREE,
  ADD KEY `contest_id` (`contest_id`,`is_hidden`) USING BTREE;

--
-- Indexes for table `submissions_history`
--
ALTER TABLE `submissions_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_judge_time` (`submission_id`,`judge_time`,`id`),
  ADD KEY `submission` (`submission_id`,`id`),
  ADD KEY `status_major` (`status`,`major`);

--
-- Indexes for table `system_updates`
--
ALTER TABLE `system_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id_time` (`type`,`target_id`,`time`),
  ADD KEY `type_time` (`type`,`time`);

--
-- Indexes for table `upgrades`
--
ALTER TABLE `upgrades`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `user_info`
--
ALTER TABLE `user_info`
  ADD PRIMARY KEY (`username`),
  ADD KEY `ac_num` (`ac_num`,`username`),
  ADD KEY `rating` (`username` DESC) USING BTREE,
  ADD KEY `active_in_contest` (`active_in_contest`,`rating`);

--
-- Indexes for table `user_msg`
--
ALTER TABLE `user_msg`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender` (`sender`),
  ADD KEY `receiver` (`receiver`),
  ADD KEY `read_time` (`receiver`,`read_time`) USING BTREE;

--
-- Indexes for table `user_system_msg`
--
ALTER TABLE `user_system_msg`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiver` (`receiver`,`read_time`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogs_comments`
--
ALTER TABLE `blogs_comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogs_tags`
--
ALTER TABLE `blogs_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contests`
--
ALTER TABLE `contests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contests_asks`
--
ALTER TABLE `contests_asks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_test_submissions`
--
ALTER TABLE `custom_test_submissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hacks`
--
ALTER TABLE `hacks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `problems`
--
ALTER TABLE `problems`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `problems_tags`
--
ALTER TABLE `problems_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_requests`
--
ALTER TABLE `search_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submissions_history`
--
ALTER TABLE `submissions_history`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_updates`
--
ALTER TABLE `system_updates`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_msg`
--
ALTER TABLE `user_msg`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_system_msg`
--
ALTER TABLE `user_system_msg`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
