---------------------------------------------------------------
-- Update the global Metrics table - schema and data
-- Only needs to be run once as part of the 2018-Mar-12 update.
--   # mysql < Updates_031218_Global.sql
---------------------------------------------------------------
--
use ccplus_global;
--
--
-- Table structure for table `Metrics`
--
DROP TABLE IF EXISTS `Metrics`;
CREATE TABLE `Metrics` (
  `ID` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `rept_id` int(7) unsigned NOT NULL DEFAULT 0,
  `col_xref` varchar(32) DEFAULT NULL,
  `legend` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Data for `Metrics`
--
INSERT INTO `Metrics` VALUES (1,1,'RP_TTL','Full-Text Article Requests (Total)'),(2,1,'RP_PDF','Full-Text Article Requests (PDF)'), (3,1,'RP_HTML','Full-Text Article Requests (HTML)'), (4,2,NULL,'Total Full-Text Article Requests');
