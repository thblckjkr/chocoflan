/***
* Copyright (c) thblckjkr 2019.
*
* Licensed under The MIT License
***/
use chocoflan;

--
-- Tables necessary for the framework running
--

CREATE TABLE `sessions` (
	`id` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
	`created` datetime DEFAULT CURRENT_TIMESTAMP, -- optional, requires MySQL 5.6.5+
	`modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- optional, requires MySQL 5.6.5+
	`data` blob DEFAULT NULL, -- for PostgreSQL use bytea instead of blob
	`expires` int(10) unsigned DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tables necessary for main functionality
--

CREATE TABLE IF NOT EXISTS `servers` (
	`id` int unsigned AUTO_INCREMENT,
	`name` varchar(20) COLLATE utf8_bin NOT NULL,
	`ip` int(11) unsigned NOT NULL,
	`port` int(5) NOT NULL,
	`username` varchar(32) COLLATE utf8_bin NOT NULL,
	`password` tinytext COLLATE utf8_bin NOT NULL,
	`type` char(3) NOT NULL,
	`created` DATETIME,
	`modified` DATETIME,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `connections` (
	`id` int unsigned AUTO_INCREMENT,
	`server_id` int unsigned,
	`created` datetime NOT NULL,
	`Status` char(3),
	`Message` text COLLATE utf8_bin,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
