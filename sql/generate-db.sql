--
-- Plink Database
--
-- Current schema by Tristan Mastrodicasa
--

-- Generate database --

SELECT "Re-create the database" AS "INFO";

DROP DATABASE IF EXISTS plink;
CREATE DATABASE IF NOT EXISTS plink
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_general_ci;

USE plink;

-- Clear tables --

SELECT "Clearing Tables" AS "INFO";

DROP TABLE IF EXISTS activity_data, 
hashtag_data, 
notification_data, 
open_graph, 
pass_data, 
photo_data, 
post_comments, 
post_data, 
post_loaded, 
post_statistics, 
system_messages, 
temp_user_data, 
user_data, 
user_data_extra, 
user_settings, 
user_statistics;

-- Create tables --

SELECT "Creating New Tables" AS "INFO";

CREATE TABLE activity_data (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `cuserid` int(11) NOT NULL, 
  `huserid` int(11) NOT NULL, 
  `postid` int(11) DEFAULT NULL, 
  `action` int(11) NOT NULL, 
  `committed` tinyint(1) NOT NULL, 
  `date` date NOT NULL, 
  `time` time NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE hashtag_data (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `postid` int(11) NOT NULL, 
  `hashtag` text COLLATE `latin1_swedish_ci`, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE notification_data (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `postid` int(11) NOT NULL, 
  `userid` int(11) NOT NULL, 
  `atype` int(11) NOT NULL, 
  `notify` tinyint(1) NOT NULL, 
  `count` int(11) NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE open_graph (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `postid` int(11) NOT NULL, 
  `url` longtext COLLATE `latin1_swedish_ci`, 
  `title` longtext COLLATE `latin1_swedish_ci`, 
  `description` longtext COLLATE `latin1_swedish_ci` DEFAULT NULL, 
  `media` longtext COLLATE `latin1_swedish_ci` DEFAULT NULL, 
  `mediafit` tinyint(1) DEFAULT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE pass_data (
  `userid` int(11) NOT NULL, 
  `password` varchar(254) COLLATE `latin1_swedish_ci` NOT NULL, 
  `salt` varchar(254) COLLATE `latin1_swedish_ci` NOT NULL 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE photo_data (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `postid` int(11) DEFAULT NULL, 
  `userid` int(11) NOT NULL, 
  `type` char(1) COLLATE `latin1_swedish_ci` NOT NULL, 
  `filesize` int(11) NOT NULL, 
  `propic` tinyint(1) NOT NULL, 
  `date` date NOT NULL, 
  `time` time NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE post_comments (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `postid` int(11) NOT NULL, 
  `userid` int(11) NOT NULL, 
  `written` int(11) NOT NULL, 
  `date` date NOT NULL, 
  `time` time NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE post_data (
  `postid` int(11) NOT NULL AUTO_INCREMENT, 
  `userid` int(11) NOT NULL, 
  `type` int(11) NOT NULL, 
  `written` text COLLATE `latin1_swedish_ci` DEFAULT NULL, 
  `url` tinyint(1) NOT NULL, 
  `topic` int(11) NOT NULL, 
  `original` tinyint(1) DEFAULT NULL, 
  `originalpostid` int(11) DEFAULT NULL, 
  `date` date NOT NULL, 
  `time` time NOT NULL, 
PRIMARY KEY `PRIMARY` (`postid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE post_loaded (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `postid` int(11) NOT NULL, 
  `userid` int(11) NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE post_statistics (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `postid` int(11) NOT NULL, 
  `endorsements` int(11) NOT NULL, 
  `reposts` int(11) NOT NULL, 
  `comments` int(11) NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE system_messages (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `userid` int(11) NOT NULL, 
  `subject` int(11) NOT NULL, 
  `seen` tinyint(1) NOT NULL, 
  `refid` int(11) DEFAULT NULL, 
  `date` date NOT NULL, 
  `time` time NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE temp_user_data (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `username` varchar(20) COLLATE `latin1_swedish_ci` NOT NULL, 
  `name` varchar(20) COLLATE `latin1_swedish_ci` NOT NULL, 
  `email` varchar(254) COLLATE `latin1_swedish_ci` NOT NULL, 
  `school` int(11) NOT NULL, 
  `password` varchar(254) COLLATE `latin1_swedish_ci` NOT NULL, 
  `salt` varchar(254) COLLATE `latin1_swedish_ci` NOT NULL, 
  `reference` int(11) DEFAULT NULL, 
  `activatekey` varchar(30) COLLATE `latin1_swedish_ci` NOT NULL, 
  `date` date NOT NULL, 
  `time` time NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE user_data (
  `userid` int(11) NOT NULL AUTO_INCREMENT, 
  `username` varchar(20) COLLATE `latin1_swedish_ci` NOT NULL, 
  `name` varchar(20) COLLATE `latin1_swedish_ci` NOT NULL, 
  `level` int(11) NOT NULL, 
  `xp` int(11) NOT NULL, 
  `date` date NOT NULL, 
  `time` time NOT NULL, 
PRIMARY KEY `PRIMARY` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE user_data_extra (
  `userid` int(11) NOT NULL, 
  `email` varchar(254) COLLATE `latin1_swedish_ci` NOT NULL, 
  `bemail` varchar(254) COLLATE `latin1_swedish_ci` NOT NULL, 
  `school` int(11) NOT NULL, 
  `date` date NOT NULL, 
  `time` time NOT NULL 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE user_settings (
  `userid` int(11) NOT NULL, 
  `allsubpost` tinyint(1) DEFAULT NULL 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE user_statistics (
  `id` int(11) NOT NULL AUTO_INCREMENT, 
  `userid` int(11) NOT NULL, 
  `subscribers` int(11) NOT NULL, 
  `admirations` int(11) NOT NULL, 
  `endorsements` int(11) NOT NULL, 
  `posts` int(11) NOT NULL, 
PRIMARY KEY `PRIMARY` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT "Done" AS "INFO";