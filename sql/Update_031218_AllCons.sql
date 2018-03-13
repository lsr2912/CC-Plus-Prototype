---------------------------------------------------------------
-- Update the consortia database schema(s)
-- Only needs to be run once as part of the 2018-Mar-12 update.
---------------------------------------------------------------
-- Apply the updates below to each CC-Plus consortium database
-- setup on this system. Do at least:
--    # mysql ccplus_con_template < Updates_031218_AllCons.sql
-- , and then for each consortium:
--  # mysql ccplus_SOMEKEY < Updates_031218_AllCons.sql
--
alter table institution drop column admin_userid;
alter table alerts drop column inst_id;
--
update provider set security='None' where security='IP';
alter table provider change column security security enum('None','HTTP','WSSE') DEFAULT 'None';
--
alter table JR1_Report_Data DROP COLUMN metric_xref;
alter table JR5_Report_Data DROP COLUMN metric_xref;
alter table Temp_JR1 DROP COLUMN metric_xref;
alter table Temp_JR5 DROP COLUMN metric_xref;
