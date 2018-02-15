--
drop database if exists ccplus_global;
--
create database ccplus_global;
use ccplus_global;

--
-- Table structure for table Admin_Settings
--
DROP TABLE IF EXISTS `Admin_Settings`;
CREATE TABLE `Admin_Settings` (
  `admin_email` varchar(128) NOT NULL default '',
  `report_root` varchar(128) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table Consortia
--
DROP TABLE IF EXISTS `Consortia`;
CREATE TABLE `Consortia` (
  `ID` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `ccp_key` char(8) NOT NULL default '',
  `name` varchar(128) NOT NULL default '',
  `email` varchar(128) NOT NULL default '',
  `status` enum('Active','Inactive') NOT NULL default 'Active',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `Reports`
--
DROP TABLE IF EXISTS `Reports`;
CREATE TABLE `Reports` (
  `ID` int(7) NOT NULL AUTO_INCREMENT,
  `Report_Name` varchar(255) DEFAULT NULL,
  `revision` varchar(12) NOT NULL DEFAULT '4',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Data for `Reports`
--
INSERT INTO `Reports` VALUES (1,'JR1','4'),(2,'JR5','4');

--
-- Table structure for table `Metrics`
--
DROP TABLE IF EXISTS `Metrics`;
CREATE TABLE `Metrics` (
  `ID` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `rept_id` int(7) unsigned NOT NULL DEFAULT 0,
  `legend` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Data for `Metrics`
--
INSERT INTO `Metrics` VALUES (1,1,'Full-Text Article Requests (Total)'),(2,1,'Full-Text Article Requests (PDF)'), (3,1,'Full-Text Article Requests (HTML)'), (4,2,'Full-Text Article Requests');

--
-- Table structure for table packages
--
DROP TABLE IF EXISTS `Packages`;
CREATE TABLE `Packages` (
  `ID` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL default '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `Journal`
--
DROP TABLE IF EXISTS `Journal`;
CREATE TABLE `Journal` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `ISSN` varchar(9) DEFAULT NULL,
  `eISSN` varchar(9) DEFAULT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `JISSN_ix` (`ISSN`),
  KEY `JeISSN_ix` (`eISSN`),
  KEY `Title` (`Title`),
  FULLTEXT KEY `title_index` (`Title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `Platform`
--
DROP TABLE IF EXISTS `Platform`;
CREATE TABLE `Platform` (
  `ID` int(7) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `Manual_Staging`
--
DROP TABLE IF EXISTS `Manual_Staging`;
CREATE TABLE `Manual_Staging` (
  `ID` int(7) NOT NULL AUTO_INCREMENT,
  `XML_File` varchar(255) DEFAULT NULL,
  `CSV_File` varchar(255) DEFAULT NULL,
  `report_ID` int(7) NOT NULL DEFAULT '0',
  `yearmon` char(7) NOT NULL DEFAULT '0',
  `prov_id` int(7) NOT NULL DEFAULT '0',
  `con_key` char(8) NOT NULL DEFAULT '',
  `inst_id` int(7) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
