<?php
//-------------------------------------------------------------------------------------- 
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
//--------------------------------------------------------------------------------------
//
// Function to return sushi settings
// Arguments:
//   $_inst : limit the return-set based on institution ID (default:all)
//   $_prov : limit the return-set based on provider ID (default:all)
//   $_day  : limit the return-set based on day_of_month match (default:all)
// Returns an array of joined rows from the provider and susi_settings tables
//
if (!function_exists("ccp_get_sushi_settings")) {
  function ccp_get_sushi_settings( $_inst=0, $_prov=0, $_day=0 ) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT Prov.name AS Provider,Inst.name AS inst_name,Prov.*,SuS.* FROM provider AS Prov";
    $_qry .= " LEFT OUTER JOIN sushi_settings AS SuS ON SuS.prov_id=Prov.prov_id";
    $_qry .= " LEFT JOIN institution AS Inst ON Inst.inst_id=SuS.inst_id";
    $_where = "";
    if ($_inst>0) { $_where .= "SuS.inst_id=$_inst"; }
    if ($_prov>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Prov.prov_id=$_prov";
    }
    if ($_day>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "day_of_month=$_day";
    }
    if ( $_where != "" ) $_qry .= " WHERE " . $_where;

    // Execute query, prepare results
    //
    $settings=array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {

        // If ID is 0 or null, there are no SUSHI settings for the provider
        //
        if ( $row['ID'] == null || $row['ID'] == 0 ) { continue; }

        array_push($settings,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the settings
    //
    return $settings;
  }
}

// Pull stats settings for failed ingest attempts
//  Arguments: $max_retries : includes only entries with <= max_retries
//                            (default is all)
//             $setting_id  : requests failed report ID/name pairs for a given setting_id
//             $from_today  : includes any that were logged TODAY() ; Default is NO
//
//  Returns : $settings : an array of report-settings for the failed attempts
//    OR    : $reports  : an array of reports that failed for a given setting_id
//
if (!function_exists("ccp_failed_ingests")) {
  function ccp_failed_ingests( $setting_id=0, $max_retries=0, $from_today=0 ) {
    
    // Connect to the database as a user
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $where = " WHERE ";
    if ( $from_today == 0 ) {  $where .= "timestamp<CURDATE()"; }
    if ( $setting_id == 0 ) {	// Pull settings rows that failed for ALL reports
      $_qry  = "SELECT FA.ID as Failed_ID, Prov.name AS Provider, FA.*, SU.*";
      $_qry .= " FROM failed_ingest as FA";
      $_qry .= " LEFT JOIN sushi_settings as SU ON FA.settings_id=SU.ID";
      $_qry .= " LEFT JOIN provider as Prov ON SU.prov_id=Prov.prov_id";
    } else {
      $_qry  = "SELECT DISTINCT report_xref AS ID, report_name AS name FROM failed_ingest";
      if ( $where != " WHERE " ) { $where .= " AND "; }
      $where .= "settings_id=" . $setting_id;
    }
    if ( $max_retries !=0 ) {
      if ( $where != " WHERE " ) { $where .= " AND "; }
      $where .= "retry_count<" . $max_retries;
    }

    // Tack on where clause if needed
    //
    if ( $where != " WHERE " ) { $_qry .= $where; }

    // Run the query
    //
    $targets = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push ( $targets, $row );
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    return $targets;

  }
}

// Log a stats ingest attempt
//
if (!function_exists("ccp_record_ingest")) {
  function ccp_record_ingest( $prov_id=0 , $inst_id=0, $report_id, $yearmon, $status ) {

    // Connect to database as admin
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Execute the query to add the record
    //
    $_qry  = "INSERT INTO ingest_record (inst_id,prov_id,report_xref,yearmon,status) VALUES (?,?,?,?,?)";
    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute(array($inst_id, $prov_id, $report_id, $yearmon, $status));
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }
}

// Test for a prior, failed ingest attempt with retries pending
// Returns : ID of the failed_ingest record, or zero if no match
//
if (!function_exists("ccp_retries_pending")) {
  function ccp_retries_pending( $prov_id, $inst_id, $report, $yearmon ) {

    // Connect to database as user
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    $failed_ID = 0;

    // Build the query
    //
    $_qry  = "SELECT FI.ID AS failed_ID,retry_count FROM failed_ingest AS FI";
    $_qry .= " LEFT JOIN sushi_settings AS Su ON FI.settings_id=Su.ID";
    $_qry .= " WHERE FI.report_name='" . $report . "' AND FI.yearmon='" . $yearmon . "' AND";
    $_qry .= " Su.inst_id=" . $inst_id . " AND Su.prov_id= " . $prov_id;

    // Execute query
    //
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        if ( $row['retry_count'] < MAX_INGEST_RETRIES ) { $failed_ID = $row['failed_ID']; }
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }
    return $failed_ID;
  }
}

// Function to clear a failed_ingest log entry
// (called for a successful retry)
//
if (!function_exists("ccp_clear_failed")) {
  function ccp_clear_failed( $retryID ) {

    // Connect to database as admin
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Execute the query to delete the entry
    //
    $_qry  = "DELETE FROM failed_ingest WHERE ID=" . $retryID;
    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute();
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }
  }
}

// Log detail for failed stats ingests in failed_ingests.
// Also adds a success='N' entry into the stats_ingest_log
//
if (!function_exists("ccp_log_failed_ingest")) {
  function ccp_log_failed_ingest( $_prov, $_inst, $setting_id, $report_id, $report_name, $yearmon, $step, $detail, $retryID=0 ) {

    // Connect to database as admin
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Is this is a failed retry?
    //
    if ( $retryID != 0 ) {

      // Query for the record that was retried and bump the counter
      //
      $_qry  = "SELECT retry_count FROM failed_ingest WHERE ID=" . $retryID;

      // Execute query
      //
      $_count = 0;
      try {
        $_result = $ccp_adm_cnx->query($_qry);
        while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
          $_count = $row['retry_count'];
        }
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }
      $_count++;

      // Update the retry counter
      //
      $_qry  = "UPDATE failed_ingest SET retry_count=? WHERE ID=?";

      try {
        $sth = $ccp_adm_cnx->prepare($_qry);
        $sth->execute(array($_count, $retryID));
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }

    // Otherwise, execute the query to add a new record
    //
    } else {
      $_qry  = "INSERT INTO failed_ingest (settings_id,report_xref,report_name,yearmon,process_step,retry_count,detail)";
      $_qry .= "  VALUES (?,?,?,?,?,?,?)";

      try {
        $sth = $ccp_adm_cnx->prepare($_qry);
        $sth->execute(array($setting_id, $report_id, $report_name, $yearmon, $step, 0, $detail));
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }
    }

    // Drop a record in the global stats log
    //
    ccp_record_ingest( $_prov, $_inst, $report_id, $yearmon, "Failed" );

  }
}

// Log a manual ingest attempt
//   This function stores info for the cron-agent that executes every
//   10-minutes (or so). The agent handles updating the database and
//   moving the report files where they belong.
//
if (!function_exists("ccp_record_manual")) {
  function ccp_record_manual( $xml_file, $csv_file, $report_id, $yearmon, $prov_id, $con_key, $inst_id) {

    $status = 0;

    // Force new connection to global database as admin
    //
    global $ccp_adm_cnx;
    $ccp_adm_cnx = ccp_open_db("ccplus_global", "Admin", 1);

    // Execute the query to add the record
    //
    $_qry  = "INSERT INTO Manual_Staging (XML_File,CSV_File,report_ID,yearmon,prov_id,con_key,inst_id)";
    $_qry .= " VALUES (?,?,?,?,?,?,?)";

    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute(array($xml_file, $csv_file, $report_id, $yearmon, $prov_id, $con_key, $inst_id));
      $status = 1;
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Close database connection and return
    //
    $ccp_adm_cnx = null;
    return $status;
  }
}

// Function to return From and To dates of statistics records available
// across a given report table
//
//  Arguments:
//    $_report : Report to query pull range from
//    $_prov   : Limit range to a single vendor (default is all)
//    $_plat   : Limit range to a single platform (default is all)
//    $_inst   : Limit range to a single institution (default is all)
//
//  Returns : $range : array of ['from'] and ['to'] strings as YYYY-MM
//
if (!function_exists("ccp_stats_available")) {
  function ccp_stats_available( $report, $_prov=0, $_plat=0, $_inst=0 ) {

    $range = array("from"=>"", "to"=>"");

    // Connect to the database as a user
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Build query to get min/max yearmon
    //
    $_Table = $report . "_Report_Data";
    $_qry = "SELECT min(yearmon) AS MINYM, max(yearmon) AS MAXYM from " . $_Table;
    $_where = "";
    if ( $_prov != 0 ) { $_where .= "prov_id=" . $_prov; }
    if ( $_plat!=0 ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "plat_id=$_plat";
    }
    if ( $_inst!=0 ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "inst_id=$_inst";
     }
    if ( $_where != "" ) { $_qry .= " WHERE " . $_where; }

    // Execute query
    //
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $range['from'] = $row['MINYM'];
        $range['to'] = $row['MAXYM'];
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    return $range;
  }
}

// Function to return available reports given various constraints
//
//  Arguments:
//    $_from   : Start of date range as YYYY-MM (0=last-month)
//    $_to     : End of date range as YYYY-MM (0=last-month)
//    $_prov   : Limit range to a single vendor (default is all)
//    $_plat   : Limit range to a single platform (default is all)
//    $_inst   : Limit range to a single institution (default is all)
//
//  Returns : array of report ID+name pairs
//
if (!function_exists("ccp_repts_available")) {
  function ccp_repts_available( $_from, $_to, $_prov=0, $_plat=0, $_inst=0 ) {

    $reports = array();

    // Connect to the database as a user
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Allow 0's for from/to dates
    //
    if ($_from == 0) { $_from = date("Y-m", strtotime("-1 months") ); }
    if ($_to == 0) { $_to = date("Y-m", strtotime("-1 months") ); }

    // Get all reports in the system from global table
    //
    $all_reports = array();
    $_qry  = "SELECT * FROM ccplus_global.Reports";
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($all_reports,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Setup common where clause for queries to come
    //
    $_where = "WHERE yearmon BETWEEN '" . $_from . "' AND '" . $_to . "'";
    if ( $_prov != 0 ) { $_where .= " AND prov_id=" . $_prov; }
    if ( $_plat != 0 ) { $_where .= " AND plat_id=" . $_plat; }
    if ( $_inst != 0 ) { $_where .= " AND inst_id=" . $_inst; }

    // Query data table for all reports to get record counts.
    // If count>0, add the report to the return array
    //
    foreach ( $all_reports as $_rpt ) {
      $_qry = "SELECT count(*) AS recs FROM " . $_rpt['Report_Name'] . "_Report_Data " . $_where;
      try {
        $_result = $ccp_usr_cnx->query($_qry);
        $row = $_result->fetch(PDO::FETCH_ASSOC);
        if ( $row['recs'] > 0 ) { $reports[] = $_rpt; }

      } catch (PDOException $e) {
        echo $e->getMessage();
      }
    }
    return $reports;
  }
}

// Function to return a count of statistics records
//
// Arguments:
//   $_rept  : Report to be pulled (must begin with a known report)
//   $_inst  : Limit output to a specific institution ID
//   $_prov  : Limit output to a specific provider ID
//   $_plat  : Limit output to a specific platform ID
//   $_from  : Pull records from year-month
//   $_to    : Pull records to year-month
//             (To pull a range, $_from and $_to BOTH need to be non-null)
//   $_DB    : Database (name) to be checked
// Returns:
//   $_count : number of matching records
//
if (!function_exists("ccp_count_report_records")) {
  function ccp_count_report_records( $_rept, $_inst=0, $_prov=0, $_plat=0, $_from="", $_to="", $_DB="" ) {

    // Setup database connection; if $_DB is null, use session to
    // try and figure it out.
    //
    global $ccp_usr_cnx;
    if ( $_DB == "" ) { $_DB = "ccplus_" . $_SESSION['ccp_con_key']; }
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db($_DB); }

    //  Build query based on $_rept (required)
    //
    if ( substr($_rept,0,3) == "JR1" ) {
      $table = "JR1_Report_Data";
    } else if ($_rept == "JR5" ) {
      $table = "JR5_Report_Data";
    } else {
      return false;
    }
    $_qry = "SELECT COUNT(*) AS RecCount FROM " . $table;

    // Build where clause
    //
    $_where = "";
    if ($_inst>0) { $_where .= "inst_id=$_inst"; }
    if ($_prov>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "prov_id=$_prov";
    }
    if ($_plat>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "plat_id=$_plat";
    }
    if ($_from!="" && $_to!="") {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "STR_TO_DATE(yearmon,'%Y-%m') BETWEEN ";
      $_where .= "STR_TO_DATE('" . $_from . "','%Y-%m') AND ";
      $_where .= "STR_TO_DATE('" . $_to . "','%Y-%m')";
    }
    if ( $_where != "" ) $_qry .= " WHERE " . $_where;

    // Execute the delete query
    //
    $_count = 0;
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $_count = $row['RecCount'];
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }
    return $_count;
  }
}

// Return cumulative counts for a given provider over a given timeframe
// A Provider ID is required. If start-month = end-month = zero, the where
// clause has no date filter.
//
// JR1 looks for a single column, and defaults to "RP_TTL"
// JR5 sums up all YOP_*** columns into a single value
//
// Arguments: $_prov : The provider ID to collect data for
//            $_rept : Report name to be queried
//            $_colx : Column in the report to process 
//            $_inst : Limit by-institution (0 = all)
//            $_smo  : month (0 or YYYY-MM)
//            $_emo  : month (0 or YYYY-MM)
//            $_DB   : Database being queried
//
//  Returns : $retval : the answer for the requested function
//
if (!function_exists("ccp_total_usage")) {
  function ccp_total_usage( $_prov, $_rept, $_colx="", $_inst=0, $_smo=0, $_emo=0, $_DB ) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    $retval = 0;
    if ( $_prov==0 ) { return $retval; }

    // Split start and end dates
    //
    if ( $_smo != 0 && $_emo != 0 ) {
      if ( $_emo < $_smo ) { return $retval; }
    }

    // Setup an array of column-names and the table to be queried
    //
    $_table = $_rept . "_Report_Data";
    if ( $_rept == "JR1" ) {
      if ( $_colx == "" ) { $_colx = "RP_TTL"; }
      $cols = array($_colx);
    } else if ( $_rept == "JR5" ) {
      if ( $_colx == "" ) {
        $cols = ccp_get_yop_columns($_DB);
      } else {
        $cols = array($_colx);
      }
    }

    // Setup the query
    //
    $_qry  = "SELECT ";
    foreach ($cols as $_c) {
       $_qry .= "SUM(`" . $_c . "`)+";
    }
    $_qry = preg_replace("/\+$/","",$_qry);
    $_qry .= " AS RETVAL FROM " . $_table;

    // Build the where clause
    //
    $_where = " WHERE prov_id=$_prov";
    if ($_inst>0) { $_where .= " AND inst_id=$_inst"; }
    if ( $_smo != 0 && $_emo != 0 ) {
      $_where .= " AND STR_TO_DATE(yearmon,'%Y-%m') BETWEEN ";
      $_where .= "STR_TO_DATE('" . $_smo . "','%Y-%m') AND ";
      $_where .= "STR_TO_DATE('" . $_emo . "','%Y-%m')";
    }
    $_qry .= $_where;

    // Run the query
    //
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $retval = $row['RETVAL'];
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    return $retval;
  }
}

// Return stats counts from the JR1_Report_Data table over a given
// timeframe. A non-zero value for provider/platform/inst will limit
// the results based on the values given.
// If start-month = end-month = zero, the where clause has no date filter.
// The function defaults to SUM, but others supported by MySQL are allowed.
//
//  Arguments: $_prov     : The provider ID to collect data for
//             $_plat     : The platform ID to collect data for
//             $_inst     : The institution ID to collect data for
//             $_smo      : month (0 or YYYY-MM)	
//             $_emo      : month (0 or YYYY-MM)
//             $_view     : data view-by ("Jrnl", "Inst", "Both")
//             $_ordby    : custom (optional) sort order - defaults to "Total_TTL"
//
//  Returns : $totals : an array of counts
//
//
if (!function_exists("ccp_jr1_usage")) {
  function ccp_jr1_usage( $_prov, $_plat, $_inst, $_smo=0, $_emo=0, $_view="Jrnl", $_ordby="" ) {

    // Connect to database as user
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    $counts = array();

    // Check date range and column variables
    //
    if ( $_smo != 0 && $_emo != 0 ) {
      if ( $_smo > $_emo ) { return $counts; }
    }
    $months = createYMarray($_smo, $_emo);

    // Start building the query
    //
    if ( $_view == "Inst" ) {
      $_qry  = "SELECT Inst.name as Title, Prov.name as provider, Plat.name as platform,";
    } else if ( $_view == "Both" ) {
      $_qry  = "SELECT Jrnl.Title as Title, Prov.name as provider, Plat.name as platform,";
      $_qry .= "Inst.name as inst_name,DOI,PropID,ISSN,eISSN,";
    } else {	// view-by-journal
      $_qry  = "SELECT Jrnl.Title as Title, Prov.name as provider, Plat.name as platform,";
      $_qry .= "DOI,PropID,ISSN,eISSN,";
    }
    $_qry .= "SUM(RP_TTL) as Total_TTL, SUM(RP_PDF) as Total_PDF, SUM(RP_HTML) as Total_HTML";
    foreach ($months as $_ym) {
      $_qry .= ",SUM(IF (yearmon='".$_ym."',RP_TTL,0)) AS '".prettydate($_ym)."_TTL'"; 
      $_qry .= ",SUM(IF (yearmon='".$_ym."',RP_PDF,0)) AS '".prettydate($_ym)."_PDF'";
      $_qry .= ",SUM(IF (yearmon='".$_ym."',RP_HTML,0)) AS '".prettydate($_ym)."_HTML'";
    }
    $_qry .= " FROM JR1_Report_Data AS Data";
    $_qry .= " INNER JOIN ccplus_global.Platform AS Plat ON Plat.ID=Data.plat_id";
    $_qry .= " INNER JOIN provider AS Prov ON Prov.prov_id=Data.prov_id";
    if ( $_view == "Inst" ) {
      $_qry .= " INNER JOIN institution AS Inst ON Inst.inst_id=Data.inst_id";
    } else if ( $_view == "Both" ) {
      $_qry .= " INNER JOIN ccplus_global.Journal AS Jrnl ON Jrnl.ID=Data.jrnl_id";
      $_qry .= " INNER JOIN institution AS Inst ON Inst.inst_id=Data.inst_id";
    } else {	// view-by-journal
      $_qry .= " INNER JOIN ccplus_global.Journal AS Jrnl ON Jrnl.ID=Data.jrnl_id";
    }

    // Finish the where clause and the query
    //
    $_where = "";
    if ( $_smo != 0 && $_emo != 0 ) {
      $_where .= "yearmon BETWEEN '$_smo' AND '$_emo'";
    }
    if ($_prov>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Data.prov_id=$_prov";
    }
    if ($_plat>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Data.plat_id=$_plat";
    }
    if ($_inst>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Data.inst_id=$_inst";
    }
    if ( $_where != "" ) { $_qry .= " WHERE " . $_where; }
    if ( $_view == "Both" ) {
      $_qry .= " GROUP BY Title,inst_name,provider,platform";
    } else {
      $_qry .= " GROUP BY Title,provider,platform";
    }
    $_qry .= " ORDER BY ";
    $_qry .= ($_ordby=="") ? "Total_TTL DESC" : $_ordby;

    // Run the query
    //
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($counts,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    return $counts;
  }
}

// Return stats counts from the JR5_Report_Data table over a given
// timeframe. A non-zero value for provider/platform/inst will limit
// the results based on the values given.
// If start-month = end-month = zero, the where clause has no date filter.
// The function defaults to SUM, but others supported by MySQL are allowed.
//
//  Arguments: $_prov     : The provider ID to collect data for
//             $_plat     : The platform ID to collect data for
//             $_inst     : The institution ID to collect data for
//             $_smo      : month (0 or YYYY-MM)	
//             $_emo      : month (0 or YYYY-MM)
//             $_view     : data view-by ("Jrnl", "Inst")
//
//  Returns : $totals : an array of counts
//
//
if (!function_exists("ccp_jr5_usage")) {
  function ccp_jr5_usage( $_prov, $_plat, $_inst, $_smo=0, $_emo=0, $_view="Jrnl") {

    // Connect to database as user
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    $counts = array();

    // Split start and end dates
    //
    if ( $_smo != 0 && $_emo != 0 ) {
      if ( $_smo > $_emo ) { return $counts; }
    }

    // Setup the query
    //
    if ( $_view == "Inst" ) {
      $_qry  = "SELECT Inst.name as Title, Prov.name as provider, Plat.name as platform";
    } else if ( $_view == "Both" ) {
      $_qry  = "SELECT Jrnl.Title as Title, Prov.name as provider, Plat.name as platform,";
      $_qry .= "Inst.name as inst_name,DOI,PropID,ISSN,eISSN";
    } else {	// view-by-journal
      $_qry  = "SELECT Jrnl.Title as Title, Prov.name as provider, Plat.name as platform,";
      $_qry .= "DOI,PropID,ISSN,eISSN";
    }

    // Add YOP_#### columns to the query
    //
    $_TTL = ",SUM(";
    $yops = ccp_get_yop_columns();
    foreach ( $yops as $_col ) {
      $_qry .= ",SUM(`" . $_col. "`) AS `" . $_col . "`";
      $_TTL .= "`" . $_col . "`+";
    }
    $_qry .= preg_replace("/\+$/",") AS Total",$_TTL);

    // Table Specs
    //
    $_qry .= " FROM JR5_Report_Data AS Data";
    $_qry .= " INNER JOIN ccplus_global.Platform AS Plat ON Plat.ID=Data.plat_id";
    $_qry .= " INNER JOIN provider AS Prov ON Prov.prov_id=Data.prov_id";
    if ( $_view == "Inst" ) {
      $_qry .= " INNER JOIN institution AS Inst ON Inst.inst_id=Data.inst_id";
    } else if ( $_view == "Both" ) {
      $_qry .= " INNER JOIN ccplus_global.Journal AS Jrnl ON Jrnl.ID=Data.jrnl_id";
      $_qry .= " INNER JOIN institution AS Inst ON Inst.inst_id=Data.inst_id";
    } else {	// view-by-journal
      $_qry .= " INNER JOIN ccplus_global.Journal AS Jrnl ON Jrnl.ID=Data.jrnl_id";
    }

    // Finish the query
    //
    $_where = "";
    if ( $_smo != 0 && $_emo != 0 ) {
      $_where .= " WHERE yearmon BETWEEN '$_smo' AND '$_emo'";
    }
    if ($_prov>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Data.prov_id=$_prov";
    }
    if ($_plat>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Data.plat_id=$_plat";
    }
    if ($_inst>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Data.inst_id=$_inst";
    }
    if ( $_where != "" ) { $_qry .= $_where; }
    if ( $_view == "Both" ) {
      $_qry .= " GROUP BY Title,inst_name,provider,platform";
    } else {
      $_qry .= " GROUP BY Title,provider,platform";
    }
    $_qry .= " ORDER BY Total DESC";

    // Run the query
    //
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($counts,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    return $counts;
  }
}

// Return an array of IDs (Provider/Platform/Inst) with statistics stored
// for a given range of dates
//
//  Arguments: $id_type : string value "PROV" , "PLAT", or "INST"
//             $report  : Report being queried (e.g. "JR1", "JR5")
//             $_from   : Start of date range as YYYY-MM (0=last month)
//             $_to     : End of date range as YYYY-MM (0=last month)
//             $_prov   : limit the return-set based on provider ID (default:all)
//             $_plat   : limit the return-set based on platform ID (default:all)
//             $_inst   : limit the return-set based on inst ID (default:all)
//
//  Returns : an array of distinct ID-Name pairs of the id_type from
//            the report-data table within the given timeframe
//
if (!function_exists("ccp_stats_ID_list")) {
  function ccp_stats_ID_list( $id_type, $report, $_from=0, $_to=0, $_prov=0, $_plat=0, $_inst=0 ) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    $_Table = $report . "_Report_Data";
    $returnIDs = array();

    // Allow 0's for from/to dates
    //
    if ($_from == 0) { $_from = date("Y-m", strtotime("-1 months") ); }
    if ($_to == 0) { $_to = date("Y-m", strtotime("-1 months") ); }

    // Check input dates
    //
    $from_yr = date("Y", strtotime($_from) );
    $from_mo = date("n", strtotime($_from) );
    $to_yr = date("Y", strtotime($_to) );
    $to_mo = date("n", strtotime($_to) );
    if ( ($from_yr > $to_yr) || ( ($from_yr==$to_yr) && ($from_mo>$to_mo) ) ) { return $returnIDs; }

    // Build the query for what we're after
    //
    $_qry  = "";
    if ( $id_type == "PROV" ) {
      $_qry  = "SELECT DISTINCT data.prov_id,name FROM " . $_Table . " AS data";
      $_qry .= " LEFT JOIN provider AS Pr on Pr.prov_id=data.prov_id";
    } else if ( $id_type == "PLAT" ) {
      $_qry = "SELECT DISTINCT data.plat_id,name FROM " . $_Table. " AS data";
      $_qry .= " LEFT JOIN ccplus_global.Platform AS Pl on Pl.ID=data.plat_id";
    } else if ( $id_type == "INST" ) {
      $_qry = "SELECT DISTINCT data.inst_id,name FROM " . $_Table. " AS data";
      $_qry .= " LEFT JOIN institution AS II on II.inst_id=data.inst_id";
    } else {
      return $returnIDs;
    }

    // Setup the where clause
    //
    $_qry .= " WHERE yearmon BETWEEN '" . $_from . "' AND '" . $_to . "'";
    if ($_prov>0) { $_qry .= " AND prov_id=" . $_prov; }
    if ($_plat>0) { $_qry .= " AND plat_id=" . $_plat; }
    if ($_inst>0) { $_qry .= " AND inst_id=" . $_inst; }
    $_qry .= " ORDER BY name ASC";

    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($returnIDs,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    return $returnIDs;
  }
}

// Function to erase statistics records
//
// Arguments:
//   $_rept : Report to be pulled (must begin with a known report)
//   $_inst : Limit output to a specific institution ID
//   $_prov : Limit output to a specific provider ID
//   $_plat : Limit output to a specific platform ID
//   $_from : Pull records from year-month
//   $_to   : Pull records to year-month
//            (To pull a range, $_from and $_to BOTH need to be non-null)
//   $_DB   : Database (name) to be checked
// Returns:
//   Count of records deleted
//
if (!function_exists("ccp_erase_report_records")) {
  function ccp_erase_report_records( $_rept, $_inst=0, $_prov=0, $_plat=0, $_from="", $_to="", $_DB="" ) {

    // Setup database connection; if $_DB is null, use session to
    // try and figure it out.
    //
    global $ccp_adm_cnx;
    if ( $_DB == "" ) { $_DB = "ccplus_" . $_SESSION['ccp_con_key']; }
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db($_DB, "Admin"); }

    //  Build query based on $_rept (required)
    //
    if ( substr($_rept,0,3) == "JR1" ) {
      $table = "JR1_Report_Data";
    } else if ($_rept == "JR5" ) {
      $table = "JR5_Report_Data";
    } else {
      return false;
    }
    $_qry = "DELETE FROM " . $table;

    // Build where clause
    //
    $_where = "";
    if ($_inst>0) { $_where .= "inst_id=$_inst"; }
    if ($_prov>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "prov_id=$_prov";
    }
    if ($_plat>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "plat_id=$_plat";
    }
    if ($_from!="" && $_to!="") {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "STR_TO_DATE(yearmon,'%Y-%m') BETWEEN ";
      $_where .= "STR_TO_DATE('" . $_from . "','%Y-%m') AND ";
      $_where .= "STR_TO_DATE('" . $_to . "','%Y-%m')";
    }
    if ( $_where != "" ) $_qry .= " WHERE " . $_where;

    // Execute the delete query
    //
    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute();
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }
    return $sth->rowCount();
  }
}

// Function to return ingest log entries as an array 
//
// Arguments:
//   $_prov : limit the return-set based on provider ID (default:all)
//   $_inst : limit the return-set based on inst ID (default:all)
//   $_from : limit from this start-month as YYYY-MM ( default:all)
//   $_to   : limit up through this end-month as YYYY-MM (default: all)
//   $_stat : limit based on status (default: all)
//   $_rept : limit the return-set based on report ID (default:all)
//   $_InID : limit the result to a single ingest entry (ID=$_InID), default is ALL,
//            but if non-zero, all other inputs are ignored.
//
// Returns:
//   $records ; matching entries from the log table
//
if (!function_exists("ccp_get_ingest_record")) {
  function ccp_get_ingest_record( $_prov=0, $_inst=0, $_from=0, $_to=0, $_stat="ALL", $_rept=0, $_InID=0 ) {

    $records = array();
    if ( $_InID == 0 ) {
      if ( $_to < $_from ) { return $records; }
      if ( $_stat!="ALL" && $_stat!="Saved" && $_stat!="Failed" && $_stat!="Deleted" ) { return $records; }
    }

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT IR.*,Prov.name as prov_name,Inst.name as inst_name,Rpt.Report_Name,Rpt.revision,";
    $_qry .= " Rpt.ID as report_ID, CONCAT(Rpt.Report_Name, ' (v', Rpt.revision, ')') AS report_name,";
    $_qry .= " FI.ID as failed_ID FROM ingest_record AS IR";
    $_qry .= " LEFT JOIN provider AS Prov ON IR.prov_id=Prov.prov_id";
    $_qry .= " LEFT JOIN institution AS Inst ON IR.inst_id=Inst.inst_id";
    $_qry .= " LEFT JOIN sushi_settings AS Sus ON (IR.inst_id=Sus.inst_id AND IR.prov_id=Sus.prov_id)";
    $_qry .= " LEFT JOIN ccplus_global.Reports AS Rpt ON IR.report_xref=Rpt.ID";
    $_qry .= " LEFT JOIN failed_ingest AS FI ON";
    $_qry .= " (FI.yearmon=IR.yearmon AND FI.report_xref=IR.report_xref AND FI.settings_id=Sus.ID)";

    // Setup where clause
    //
    $where = " WHERE ";
    if ( $_InID != 0 ) {
      $where .= "IR.ID=" . $_InID;
    } else {
      if ( $_prov!=0 ) { $where .= "IR.prov_id=" . $_prov . " AND "; }
      if ( $_inst!=0 ) { $where .= "IR.inst_id=" . $_inst . " AND "; }
      if ($_from!=0 && $_to!=0) {
        $where .= "STR_TO_DATE(IR.yearmon,'%Y-%m') BETWEEN ";
        $where .= "STR_TO_DATE('" . $_from . "','%Y-%m') AND ";
        $where .= "STR_TO_DATE('" . $_to . "','%Y-%m') AND ";
      }
      // if ( $_from!=0 ) { $where .= "IR.yearmon>='" . $_from . "' AND "; }
      // if ( $_to!=0 )   { $where .= "IR.yearmon<='" .   $_to . "' AND "; }
      if ( $_stat!="ALL") { $where .= "status='" . $_stat . "' AND "; }
      if ( $_rept!=0 ) { $where .= "Rpt.ID=" . $_rept; }
      $where = preg_replace("/ AND $/","",$where);
    }

    // Execute query, prepare results
    //
    if ( $where != " WHERE " ) { $_qry .= $where; }
    $_qry .= " ORDER BY IR.timestamp ASC";

    $alerts=array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        if ( $_InID != 0 ) { return $row; }
        array_push($records,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the rows
    return $records;
  }
}

// Confirm JR5 table structure
//   Function accepts array of year-strings (may include 'YOP_Pre-YYY0' or 'YOP_Unknown'),
//   and compares the strings against existing column-names for JR5_Report_Data.
//   If a YOP-column in the database is "missing", the table is altered to add it.
//   YOP's should conform to spec (See: process_counter_JR5v4 in counter4_processors).
// NOTE:
//   This function DOES NOT error-check the YOP-values! If a value in $YOPS does not
//   have a matching column, the column gets created! 
// Arguments:
//   $YOPS    : Array of Year-of-Publication strings
//   $_DB     : Database (name) to be checked
//   $_Table  : Database table to check (optional)
// Returns TRUE on SUCCESS, FALSE otherwise
//
if (!function_exists("ccp_confirm_JR5_schema")) {
  function ccp_confirm_JR5_schema( $YOPS, $_DB, $_Table="JR5_Report_Data" ) {

    // Connect to database as admin. Admin is presumed to have ALL rights,
    // regardless of the database being checked/altered.
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Setup and run query to get the current structure
    //
    $COLS = array();
    $_qry  = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS";
    $_qry .= " WHERE TABLE_SCHEMA='" . $_DB . "' AND TABLE_NAME='" . $_Table . "'";
    try {
      $_result = $ccp_adm_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_NUM) ) { $COLS[] = $row[0]; }
    } catch (PDOException $e) {
      return FALSE;
    }

    // Check YOPS against the database columns, track any that are missing
    //
    $Missing = array();
    foreach ( $YOPS as $_yop ) {
      if ( !in_array($_yop,$COLS)) { $Missing[] = $_yop; }
    }

    // If columns are missing, update the table by adding columns
    //
    foreach ( $Missing as $new_yop ) {
      $alter_qry  = "ALTER TABLE '" . $_Table . "' ADD COLUMN " . $new_yop;
      $alter_qry .= "  int(7) NOT NULL DEFAULT 0 AFTER YOP_InPress";
      try {
        $result = $ccp_adm_cnx->query($alter_qry);
      } catch (PDOException $e) {
        return FALSE;
      }
    }
    return TRUE;
  }
}

// Pull report records from the database
// Arguments:
//   $_rept : Report to be pulled (must begin with a known report)
//   $_inst : Limit output to a specific institution ID
//   $_prov : Limit output to a specific provider ID
//   $_plat : Limit output to a specific platform ID
//   $_from : Pull records from year-month
//   $_to   : Pull records to year-month
//            (To pull a range, $_from and $_to BOTH need to be non-null)
//   $_DB   : Database (name) to be checked
// Returns:
//   $records  : An array of matching records
// NOTE:
//   Since there may be multiple records for a single journal (same Issn/Eissn,
//   but different title in the raw XML), the Count values are summed and the
//   records are returned as a single row via "GROUP BY title".
//
if (!function_exists("ccp_get_report_records")) {
  function ccp_get_report_records( $_rept, $_inst=0, $_prov=0, $_plat=0, $_from="", $_to="", $_DB="" ) {

    $records=array();

    // Setup database connection; if $_DB is null, use session to
    // try and figure it out.
    //
    global $ccp_usr_cnx;
    if ( $_DB == "" ) { $_DB = "ccplus_" . $_SESSION['ccp_con_key']; }
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db($_DB); }

    //  Build query based on $_rept (required)
    //
    $_qry  = "SELECT Jrnl.Title AS journal_title,Jrnl.ISSN,Jrnl.eISSN,Plat.name AS platform_name,";
    $_qry .= "inst_id,yearmon,DOI,PropID";
    if ( substr($_rept,0,3) == "JR1" ) {
      $table = "JR1_Report_Data";
      $_qry .= ",RP_HTML,RP_PDF,RP_TTL";

    } else if ($_rept == "JR5" ) {
      $table = "JR5_Report_Data";

      // Add YOP_#### columns to the query
      //
      $yops = ccp_get_yop_columns($_DB);
      foreach ( $yops as $_col ) {
        $_qry .= ",sum(`" . $_col. "`) AS `" . $_col . "`";
      }

    } else {
      return $records;
    }
    $_qry .= " FROM " . $table . " AS Data";

    // The JOIN and WHERE clauses are the same (at least for JR1/JR5)
    //
    $_qry .= " LEFT JOIN ccplus_global.Journal AS Jrnl ON Jrnl.ID=Data.jrnl_id";
    $_qry .= " LEFT JOIN ccplus_global.Platform AS Plat ON Plat.ID=Data.plat_id";
    $_where = "";
    if ($_inst>0) { $_where .= "inst_id=$_inst"; }
    if ($_prov>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "prov_id=$_prov";
    }
    if ($_plat>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "plat_id=$_plat";
    }
    if ($_from!="" && $_to!="") {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "STR_TO_DATE(yearmon,'%Y-%m') BETWEEN ";
      $_where .= "STR_TO_DATE('" . $_from . "','%Y-%m') AND ";
      $_where .= "STR_TO_DATE('" . $_to . "','%Y-%m')";
    }
    if ( $_where != "" ) $_qry .= " WHERE " . $_where;
    if ( substr($_rept,0,2) == "JR" ) { $_qry .= " GROUP BY Data.jrnl_id"; }
    $_qry .= " ORDER BY journal_title ASC";

    // Execute query, prepare results
    //
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($records,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the rows
    return $records;

  }
}

// Function to return YOP-column-names for the JR5_Report_Data table
// This table will "grow" new columns over time, so the function sends
// back what is currently in the table.
// Arguments:
//   $_DB  : (Optional) Database (name) to be checked
// Returns:
//   $columns : An array of column names
//
if (!function_exists("ccp_get_yop_columns")) {
  function ccp_get_yop_columns( $_DB="" ) {

    $columns=array();

    // Setup database connection; if $_DB is null, use session to
    // try and figure it out.
    //
    global $ccp_usr_cnx;
    if ( $_DB == "" ) { $_DB = "ccplus_" . $_SESSION['ccp_con_key']; }
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db($_DB); }

    // Query for the (YOP) column names in JR5 table
    //
    $table = "JR5_Report_Data";
    $yop_qry  = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='";
    $yop_qry .= $_DB . "' AND TABLE_NAME='" . $table . "' AND COLUMN_NAME LIKE 'YOP%'";

    try {
      $_result = $ccp_usr_cnx->query($yop_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $columns[] = $row['COLUMN_NAME'];
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
      return $columns;
    }

    // Return the rows
    return $columns;
  }
}
?>
