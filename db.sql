CREATE TABLE `redirects` (
	`slug` varchar(14) collate utf8_unicode_ci NOT NULL,
	`url` varchar(620) collate utf8_unicode_ci NOT NULL,
	`date` datetime NOT NULL,
	`referrals` int(10) NOT NULL default '0',
	PRIMARY KEY (`slug`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Used for the URL shortener';