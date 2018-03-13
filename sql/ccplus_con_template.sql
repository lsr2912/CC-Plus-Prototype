--
drop database if exists ccplus_con_template;
--
create database ccplus_con_template;
use ccplus_con_template;
--
-- Table structure for table `institution`
--
DROP TABLE IF EXISTS `institution`;
CREATE TABLE `institution` (
  `inst_id` int(7) unsigned NOT NULL auto_increment,
  `name` varchar(128) default NULL,
  `active` tinyint(1) DEFAULT '0',
  `notes` text default NULL,
  `type` enum('PubUni','PubLib','Special','Private','Other') DEFAULT NULL,
  `sushiIPRange` varchar(60) DEFAULT '',
  `shibURL` varchar(50) NOT NULL,
  `fte` int(7) NOT NULL DEFAULT 0,
  PRIMARY KEY  (`inst_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(7) unsigned NOT NULL auto_increment,
  `inst_id` int(7) NOT NULL DEFAULT '0',
  `email` varchar(128) NOT NULL default '',
  `password` varchar(64) NOT NULL,
  `first_name` varchar(128) default NULL,
  `last_name` varchar(128) default NULL,
  `phone` varchar(64) NOT NULL,
  `role` int(7) unsigned NOT NULL DEFAULT '3',
  `optin_alerts` tinyint(1) DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `password_change_required` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `last_login` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`user_id`),
  KEY `inst_id` (`inst_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `roles`
--
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `role_id` int(7) unsigned NOT NULL,
  `name` varchar(128) NOT NULL default '',
  PRIMARY KEY (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `roles` VALUES (1,'Admin'),(10,'Manager'),(20,'User');

--
-- Table structure for table `provider`
--
DROP TABLE IF EXISTS `provider`;
CREATE TABLE `provider` (
  `prov_id` int(7) unsigned NOT NULL auto_increment,
  `name` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `server_url` varchar(132) DEFAULT NULL,
  `security` enum('None','HTTP','WSSE') DEFAULT 'None',
  `auth_username` varchar(64) DEFAULT NULL,
  `auth_password` varchar(64) DEFAULT NULL,
  `day_of_month` tinyint(2) NOT NULL DEFAULT '15',
  PRIMARY KEY (`prov_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `JR1_Report_Data`
--
DROP TABLE IF EXISTS `JR1_Report_Data`;
CREATE TABLE `JR1_Report_Data` (
  `jrnl_id` int(11) NOT NULL DEFAULT '0',
  `prov_id` int(7) NOT NULL DEFAULT '0',
  `plat_id` int(7) NOT NULL DEFAULT '0',
  `inst_id` int(7) NOT NULL DEFAULT '0',
  `yearmon` char(7) NOT NULL DEFAULT '',
  `DOI` varchar(64) NOT NULL DEFAULT '',
  `PropID` varchar(64) NOT NULL DEFAULT '',
  `RP_HTML`int(11) NOT NULL DEFAULT '0',
  `RP_PDF` int(11) NOT NULL DEFAULT '0',
  `RP_TTL` int(11) NOT NULL DEFAULT '0',
  KEY `jrnl_id_ix` (`jrnl_id`),
  KEY `prov_id_ix` (`prov_id`),
  KEY `plat_id_ix` (`plat_id`),
  KEY `inst_id_ix` (`inst_id`),
  KEY `combined_index` (`yearmon`,`inst_id`,`prov_id`,`plat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `Temp_JR1`
--
DROP TABLE IF EXISTS `Temp_JR1`;
CREATE TABLE `Temp_JR1` (
  `jrnl_id` int(11) NOT NULL DEFAULT '0',
  `prov_id` int(7) NOT NULL DEFAULT '0',
  `plat_id` int(7) NOT NULL DEFAULT '0',
  `inst_id` int(7) NOT NULL DEFAULT '0',
  `yearmon` char(7) NOT NULL DEFAULT '',
  `DOI` varchar(64) NOT NULL DEFAULT '',
  `PropID` varchar(64) NOT NULL DEFAULT '',
  `RP_HTML`int(11) NOT NULL DEFAULT '0',
  `RP_PDF` int(11) NOT NULL DEFAULT '0',
  `RP_TTL` int(11) NOT NULL DEFAULT '0',
  KEY `jrnl_id_ix` (`jrnl_id`),
  KEY `prov_id_ix` (`prov_id`),
  KEY `plat_id_ix` (`plat_id`),
  KEY `inst_id_ix` (`inst_id`),
  KEY `combined_index` (`yearmon`,`inst_id`,`prov_id`,`plat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `JR5_Report_Data`
--
DROP TABLE IF EXISTS `JR5_Report_Data`;
CREATE TABLE `JR5_Report_Data` (
  `jrnl_id` int(11) NOT NULL DEFAULT '0',
  `prov_id` int(7) NOT NULL DEFAULT '0',
  `plat_id` int(7) NOT NULL DEFAULT '0',
  `inst_id` int(7) NOT NULL DEFAULT '0',
  `yearmon` char(7) NOT NULL DEFAULT '',
  `DOI` varchar(64) NOT NULL DEFAULT '',
  `PropID` varchar(64) NOT NULL DEFAULT '',
  `YOP_InPress` int(7) NOT NULL DEFAULT '0',
  `YOP_2017` int(7) NOT NULL DEFAULT '0',
  `YOP_2016` int(7) NOT NULL DEFAULT '0',
  `YOP_2015` int(7) NOT NULL DEFAULT '0',
  `YOP_2014` int(7) NOT NULL DEFAULT '0',
  `YOP_2013` int(7) NOT NULL DEFAULT '0',
  `YOP_2012` int(7) NOT NULL DEFAULT '0',
  `YOP_2011` int(7) NOT NULL DEFAULT '0',
  `YOP_2010` int(7) NOT NULL DEFAULT '0',
  `YOP_2009` int(7) NOT NULL DEFAULT '0',
  `YOP_2008` int(7) NOT NULL DEFAULT '0',
  `YOP_2007` int(7) NOT NULL DEFAULT '0',
  `YOP_2006` int(7) NOT NULL DEFAULT '0',
  `YOP_2005` int(7) NOT NULL DEFAULT '0',
  `YOP_2004` int(7) NOT NULL DEFAULT '0',
  `YOP_2003` int(7) NOT NULL DEFAULT '0',
  `YOP_2002` int(7) NOT NULL DEFAULT '0',
  `YOP_2001` int(7) NOT NULL DEFAULT '0',
  `YOP_2000` int(7) NOT NULL DEFAULT '0',
  `YOP_Pre-2000` int(7) NOT NULL DEFAULT '0',
  `YOP_Unknown` int(7) NOT NULL DEFAULT '0',
  KEY `jrnl_id_ix` (`jrnl_id`),
  KEY `prov_id_ix` (`prov_id`),
  KEY `plat_id_ix` (`plat_id`),
  KEY `inst_id_ix` (`inst_id`),
  KEY `combined_index` (`yearmon`,`inst_id`,`prov_id`,`plat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `Temp_JR5`
--
DROP TABLE IF EXISTS `Temp_JR5`;
CREATE TABLE `Temp_JR5` (
  `jrnl_id` int(11) NOT NULL DEFAULT '0',
  `prov_id` int(7) NOT NULL DEFAULT '0',
  `plat_id` int(7) NOT NULL DEFAULT '0',
  `inst_id` int(7) NOT NULL DEFAULT '0',
  `yearmon` char(7) NOT NULL DEFAULT '',
  `DOI` varchar(64) NOT NULL DEFAULT '',
  `PropID` varchar(64) NOT NULL DEFAULT '',
  `YOP_InPress` int(7) NOT NULL DEFAULT '0',
  `YOP_2017` int(7) NOT NULL DEFAULT '0',
  `YOP_2016` int(7) NOT NULL DEFAULT '0',
  `YOP_2015` int(7) NOT NULL DEFAULT '0',
  `YOP_2014` int(7) NOT NULL DEFAULT '0',
  `YOP_2013` int(7) NOT NULL DEFAULT '0',
  `YOP_2012` int(7) NOT NULL DEFAULT '0',
  `YOP_2011` int(7) NOT NULL DEFAULT '0',
  `YOP_2010` int(7) NOT NULL DEFAULT '0',
  `YOP_2009` int(7) NOT NULL DEFAULT '0',
  `YOP_2008` int(7) NOT NULL DEFAULT '0',
  `YOP_2007` int(7) NOT NULL DEFAULT '0',
  `YOP_2006` int(7) NOT NULL DEFAULT '0',
  `YOP_2005` int(7) NOT NULL DEFAULT '0',
  `YOP_2004` int(7) NOT NULL DEFAULT '0',
  `YOP_2003` int(7) NOT NULL DEFAULT '0',
  `YOP_2002` int(7) NOT NULL DEFAULT '0',
  `YOP_2001` int(7) NOT NULL DEFAULT '0',
  `YOP_2000` int(7) NOT NULL DEFAULT '0',
  `YOP_Pre-2000` int(7) NOT NULL DEFAULT '0',
  `YOP_Unknown` int(7) NOT NULL DEFAULT '0',
  KEY `jrnl_id_ix` (`jrnl_id`),
  KEY `prov_id_ix` (`prov_id`),
  KEY `plat_id_ix` (`plat_id`),
  KEY `inst_id_ix` (`inst_id`),
  KEY `combined_index` (`yearmon`,`inst_id`,`prov_id`,`plat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `ingest_record`
--
DROP TABLE IF EXISTS `ingest_record`;
CREATE TABLE `ingest_record` (
  `ID` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `inst_id` int(7) unsigned NOT NULL DEFAULT '0',
  `prov_id` int(7) unsigned NOT NULL DEFAULT '0',
  `report_xref` int(7) NOT NULL DEFAULT '0',
  `yearmon` char(7) NOT NULL DEFAULT '',
  `status` enum('Saved','Failed','Deleted') NOT NULL DEFAULT 'Saved',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `sushi_settings`
--
DROP TABLE IF EXISTS `sushi_settings`;
CREATE TABLE `sushi_settings` (
  `ID` int(10) unsigned NOT NULL,
  `inst_id` int(10) NOT NULL,
  `prov_id` int(10) NOT NULL,
  `extension` text default NULL,
  `RequestorID` varchar(64) NOT NULL default '',
  `RequestorName` varchar(64) NOT NULL default '',
  `RequestorEmail` varchar(64) NOT NULL default '',
  `CustRefID` varchar(64) NOT NULL default '',
  `CustRefName` varchar(64) NOT NULL default '',
  PRIMARY KEY (`ID`)
  KEY `inst_id_ix` (`inst_id`),
  KEY `prov_id_ix` (`prov_id`),
  KEY `combined_index` (`inst_id`,`prov_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Structure for table `institution_aliases`
--
DROP TABLE IF EXISTS `institution_aliases`;
CREATE TABLE `institution_aliases` (
  `ID` int(7) unsigned NOT NULL auto_increment,
  `inst_id` int(7) unsigned NOT NULL default 0,
  `prov_id` int(7) unsigned NOT NULL default 0,
  `alias` varchar(128) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `alert_settings`
--
DROP TABLE IF EXISTS `alert_settings`;
CREATE TABLE `alert_settings` (
  `ID` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) DEFAULT '1',
  `metric_xref` int(7) unsigned NOT NULL default 0,
  `variance` int(7) DEFAULT NULL,
  `timespan` int(7) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table alerts
--
DROP TABLE IF EXISTS `alerts`;
CREATE TABLE `alerts` (
  `ID` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `yearmon` char(7) NOT NULL DEFAULT '',
  `settings_id` int(7) default 0,
  `failed_id` int(7) default 0,
  `status` enum('Active','Silent','Delete') DEFAULT NULL,
  `prov_id` int(7) default 0,
  `modified_by` int(7) default NULL,
  `time_stamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `counter_xref`
--
DROP TABLE IF EXISTS `counter_xref`;
CREATE TABLE `counter_xref` (
  `ID` int(7) unsigned NOT NULL auto_increment,
  `prov_id` int(7) unsigned NOT NULL,
  `report_xref` int(7) unsigned NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `failed_ingest`
--
DROP TABLE IF EXISTS `failed_ingest`;
CREATE TABLE `failed_ingest` (
  `ID` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `settings_id` int(7) unsigned NOT NULL DEFAULT '0',
  `report_xref` int(7) unsigned NOT NULL DEFAULT '0',
  `report_name` char(20) NOT NULL,
  `yearmon` char(7) NOT NULL DEFAULT '',
  `process_step` char(20) NOT NULL,
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `detail` char(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
