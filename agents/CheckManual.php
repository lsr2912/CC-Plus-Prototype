#!/usr/bin/php
<?php
//---------------------------------------------------------------------------------------
// Copyright 2017,2018 Scott Ross
// This file is part of CC-Plus.
//
// CC-Plus is free software: you can redistribute it and/or modify it under the terms
// of the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// CC-Plus is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
// without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
// PURPOSE.  See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License along with CC-Plus.
// If not, see <http://www.gnu.org/licenses/>.
//---------------------------------------------------------------------------------------
// CC-Plus Manual Ingest processing script
//
// Usage:
//
//   # php CheckManual.php
//
// This script is intended to be used as/in a cron-job that executes every 10 minutes or
// so. It tests for the existence of "staged" statistics reports and processes them into
// the main report-tables. If no records indicate pending updates, the script just exits.
//
// For all reports waiting to be pushed in, on-success, the temporary table records are
// deleted, and the CSV/XML intermediate files are moved to their appropriate home(s).
//
//---------------------------------------------------------------------------------------
// Load templates and helper functions
//
include_once('ccplus/constants.inc.php');
include_once('ccplus/dbutils.inc.php');
include_once('ccplus/statsutils.inc.php');
global $ccp_adm_cnx;
global $ccp_usr_cnx;

// Make sure CCPLUSREPORTS (from constants.inc.php) exists
//
if (!file_exists(CCPLUSREPORTS)) { mkdir(CCPLUSREPORTS, 0755, true); }

// Open database
//
$ccp_adm_cnx = ccp_open_db("ccplus_global", "Admin");

// Get staging records
//
$records = array();
try {
  $_res = $ccp_adm_cnx->query("SELECT * FROM Manual_Staging");
  while ( $row = $_res->fetch(PDO::FETCH_ASSOC) ) {
    array_push($records,$row);
  }
} catch (PDOException $e) {
  echo $e->getMessage();
  exit();
}

// Process each row
//
foreach ($records as $_rec) {

  // Open Consortium-specific database
  // 
  $_Con = $_rec['con_key'];
  $_DB = "ccplus_".$_Con;
  $ccp_usr_cnx = ccp_open_db($_DB, "Admin", 1);

  // Get report, provider, and inst info
  //
  $_Rpt = ccp_get_reports($_rec['report_ID']);
  $_Prov = ccp_get_providers ($_rec['prov_id']);
  $_Inst = ccp_get_institutions ($_rec['inst_id']);
  $Temp_Table = "Temp_" . $_Rpt['Report_Name'];
  $Dest_Table = $_Rpt['Report_Name'] . "_Report_Data";

  // Remove any records in the target table matching this ingest
  //
  $del_qry  = "DELETE FROM " . $Dest_Table . " WHERE ";
  $del_qry .= "prov_id=? AND inst_id=? AND yearmon=?";

  try {
    $sth = $ccp_usr_cnx->prepare($del_qry);
    $res = $sth->execute(array($_rec['prov_id'],$_rec['inst_id'],$_rec['yearmon']));
    print $Dest_Table.": ".$sth->rowCount()."(del) , ";
  } catch (PDOException $e) {
    echo $e->getMessage();
    continue;
  }

  // If this is a JR5, make sure JR5_Report_Data has the same YOP columns
  // as Temp_JR5. Get YOPs from Temp_Table, confirm against Dest_Table.
  // 
  if ( $_Rpt['Report_Name'] == "JR5" ) {
    $YOPS = ccp_get_yop_columns($_DB , $Temp_Table);
    $status = ccp_confirm_JR5_schema($YOPS, $_DB, $Dest_Table);
  }

  // Copy records from temp table to main table
  //
  $move_qry  = "INSERT INTO " . $Dest_Table . " SELECT * FROM " . $Temp_Table;
  $move_qry .= " WHERE prov_id=? AND inst_id=? AND yearmon=?";

  try {
    $sth = $ccp_usr_cnx->prepare($move_qry);
    $res = $sth->execute(array($_rec['prov_id'],$_rec['inst_id'],$_rec['yearmon']));
    print $Dest_Table.": ".$sth->rowCount()."(ins) , ";
  } catch (PDOException $e) {
    echo $e->getMessage();
    continue;
  }

  // Remove matching records from the temp table
  //
  $del_qry  = "DELETE FROM " . $Temp_Table . " WHERE ";
  $del_qry .= "prov_id=? AND inst_id=? AND yearmon=?";

  try {
    $sth = $ccp_usr_cnx->prepare($del_qry);
    $res = $sth->execute(array($_rec['prov_id'],$_rec['inst_id'],$_rec['yearmon']));
    print $Temp_Table.": ".$sth->rowCount()."(del)\n";
  } catch (PDOException $e) {
    echo $e->getMessage();
    continue;
  }

  // Remove the entry from the Staging table
  //
  $del_qry = "DELETE FROM Manual_Staging WHERE ID=?";
  try {
    $sth = $ccp_adm_cnx->prepare($del_qry);
    $res = $sth->execute(array($_rec['ID']));
  } catch (PDOException $e) {
    echo $e->getMessage();
    continue;
  }

  // Move intermediate files; start by setting path and filenames
  // 
  $Begin = $_rec['yearmon'];
  $End = $Begin;
  $Begin .= '-01';
  $End .= '-'.date('t',strtotime($End.'-01'));
  $_RPT = $_Rpt['Report_Name'].'v'.$_Rpt['revision'].'_'.$Begin.'_'.$End;
  $ReportPath = CCPLUSREPORTS . $_Con . '/' . $_Inst['name'] . '/' . $_Prov['name'];
  $xml_dest = $ReportPath . '/XML/' . $_RPT . '.xml';
  $csv_dest = $ReportPath . '/COUNTER/' . $_RPT . '.csv';

  // Make sure the folders exist
  //
  if (!file_exists($ReportPath . '/XML')) {
    mkdir($ReportPath . '/XML', 0755, true);
  }
  if (!file_exists($ReportPath . '/COUNTER')) {
    mkdir($ReportPath . '/COUNTER', 0755, true);
  }

  // Move the files
  //
  rename ($_rec['XML_File'] , $xml_dest);
  rename ($_rec['CSV_File'] , $csv_dest);

  // Drop record in the ingest log
  //
  ccp_record_ingest($_rec['prov_id'],$_rec['inst_id'],$_rec['report_ID'],$_rec['yearmon'],"Saved",$_DB);

  // Print confirmation line to stdout
  //
  $msg  = "Finished ingest of : " . $_RPT . " for CON=" . $_Con;
  $msg .= " , PROV=" . $_rec['prov_id'] . " , INST=" . $_rec['inst_id'] . "\n";
  fwrite(STDOUT,$msg);

} // Loop over records in the staging table

// Close up database
//
$ccp_adm_cnx = null;
?>
