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
// Process a JR1 counter report
// Arguments: $in_csv  : filename of CSV to be loaded
//            $prov_id : vendor_id for the report
//            $inst_id : vendor_id for the report
//            $yearmon : Month to process as YYYY-MM
//            $DB      : Database to be used
//            $Table   : Database table (optional)
//
// Returns:
//  $status : a string containing an error, or the string "Success"
//--------------------------------------------------------------------------------------
if (!function_exists("process_counter_JR1v4")) {
  function process_counter_JR1v4 ($in_csv, $prov_id, $inst_id, $yearmon, $DB, $Table="") {

    // Get file contents into an array of strings
    //
    $data = file_get_contents ($in_csv);
    $report_recs = explode("\n", $data); 

    // Figure out which column we're after based on the column header record
    // it should be somewhere in the the first 10 records
    //
    $yearmon = trim($yearmon);
    $MonYr = date("M-Y", strtotime($yearmon));
    $found_header = FALSE;
    $hRec = array_shift( $report_recs );
    for ($ir=0; $ir<=10; $ir++) {
       $_loc = strpos( strtoupper($hRec), "PUBLISHER");
       if ( $_loc !== FALSE ) {
          $found_header = TRUE;
          break;
       } else {
          $hRec = array_shift( $report_recs );
       }
    }

    // Set column index ($_mcol) to the requested month
    //
    $_mcol = 0;
    if ($found_header) {
      $header_cols = explode(",",$hRec);
      $_cc = 0;
      foreach ( $header_cols as $icv ) {
        $icv = trim($icv);
        if ( strtoupper($icv) == strtoupper($MonYr) ) {
          $_mcol = $_cc;
          break;
        } else {
          $_cc++;
        }
      }
    }

    // Exit w/ error if header-rec or requested month column not found
    // 
    if ($_mcol == 0) { return "Could not find '$MonYr' in $in_csv"; }

    // Connect to the database as admin
    //
    global $ccp_adm_cnx;
    if ( $DB == "" ) { $DB = "ccplus_" . $_SESSION['ccp_con_key']; }
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db($DB,"Admin"); }

    // Table is optional, default to "live" table
    //
    if ( $Table == "" ) { $Table = "JR1_Report_Data"; }

    // Setup insert query template
    // 
    $metric = 1;	// Links to ccplus_global::Metrics -> JR1,"Full-Text Article Requests"
    $_qry  = "INSERT into $Table (jrnl_id,prov_id,plat_id,inst_id,metric_xref,yearmon,DOI,";
    $_qry .= "PropID,RP_HTML,RP_PDF,RP_TTL)";
    $_qry .= " VALUES (?,?,?,?,?,?,?,?,?,?,?)";

    // Process report records (once all headers are shifted off)
    //
    $hRec = array_shift( $report_recs );	// peel off Total record
    foreach ( $report_recs as $_Rec) {

      // Split the row on commas; allow for commas within quoted strings
      //
      $_cells = str_getcsv($_Rec , "," , "\"");
      if ( count($_cells)-1 < $_mcol ) { continue; }

      // Turn title and platform strings into ID #'s
      // (If the cells don't exist, the record is bad.. skip it)
      //
      if ( !isset($_cells[0]) || !isset($_cells[2]) ||
           !isset($_cells[5]) || !isset($_cells[6]) ) { continue; }
      $journal = ccp_find_journal($_cells[0], $_cells[5], $_cells[6]);
      $platform = ccp_find_platform($_cells[2]);

      // Store fields in variables
      //
      $_DOI = $_cells[3];
      $_PID = $_cells[4];
      $_TTL = $_cells[7];
      $_HTM = $_cells[8];
      $_PDF = $_cells[9];
      // $Count = $_cells[$_mcol];

      // Run the query, but don't bother writing records of all zero
      //
      if ( $_TTL>0 && $journal['ID']>0) {

        // Setup variables for the insert 
        //
        $_args = array($journal['ID'],$prov_id,$platform['ID'],$inst_id,$metric,$yearmon,$_DOI,$_PID,$_HTM,$_PDF,$_TTL);

        // execute the contract table insert
        //
        try {
          $sth = $ccp_adm_cnx->prepare($_qry);
          $sth->execute($_args);
        } catch (PDOException $e) {
          echo $e->getMessage();
        }
      }
    }
    return "Success";
  }
}

//--------------------------------------------------------------------------------------
// Process a JR5 counter report
// Arguments: $in_csv  : filename of CSV to be loaded
//            $prov_id : vendor_id for the report
//            $inst_id : vendor_id for the report
//            $yearmon : Month to process as YYYY-MM
//            $DB      : Database to be used
//            $Table   : Database table (optional)
//
// Returns:
//  $status : a string containing an error, or the string "Success"
//--------------------------------------------------------------------------------------
if (!function_exists("process_counter_JR5v4")) {
  function process_counter_JR5v4 ($in_csv, $prov_id, $inst_id, $yearmon, $DB, $Table="") {

    // Get file contents into an array of strings
    //
    $data = file_get_contents ($in_csv);
    $report_recs = explode("\n", $data); 

    // Shift off records to get to the header with the Year-of-Publication header 
    //
    $yearmon = trim($yearmon);
    $found_header = FALSE;
    $hRec = array_shift( $report_recs );
    for ($ir=0; $ir<=10; $ir++) {
       $_loc = strpos( strtoupper($hRec), "PUBLISHER");
       if ( $_loc !== FALSE ) {
          $found_header = TRUE;
          break;
       } else {
          $hRec = array_shift( $report_recs );
       }
    }

    // Split the header on commas, build an array of YOP's
    //
    $YOPS = array();
    $h_cells = str_getcsv($hRec , "," , "\"");
    foreach ( $h_cells as $idx=>$str ) {
      if ( preg_match('/Articles in Press/i', $str) ) {
        $YOPS[$idx] = "YOP_InPress";
        continue;
      }
      if ( !preg_match('/^YOP /i', $str) ) { continue; }
      $_year = strtoupper(substr($str,4));
      if ( preg_match('/-/', $_year) ) {
        if ( !preg_match('/^PRE-/', $_year) ) { continue; }
        $_range_end = trim(substr($_year,4));
        if ( ($_range_end % 10) != 0 ) { continue; }
        $YOPS[$idx] = "YOP_Pre-".$_range_end;
      } else if ( $_year == "UNKNOWN" ) {
        $YOPS[$idx] = "YOP_Unknown";
      } else if ( is_numeric($_year) ) {
        if ( $_year<2000 || $_year>date("Y") ) { continue; }
        $YOPS[$idx] = "YOP_" . $_year;
      }
    }

    // Shift off the "Totals" row
    //
    $hRec = array_shift( $report_recs );

    // Connect to the database as admin
    //
    global $ccp_adm_cnx;
    if ( $DB == "" ) { $DB = "ccplus_" . $_SESSION['ccp_con_key']; }
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db($DB,"Admin"); }

    // Table is optional, default to "live" table
    //
    if ( $Table == "" ) { $Table = "JR5_Report_Data"; }

    // Make sure that Table has all the columns it needs
    //
    $status = ccp_confirm_JR5_schema($YOPS, $DB, $Table);

    // Setup insert query template
    // 
    $metric = 2;	// Links to ccplus_global::Metrics -> JR5,"Full-Text Article Requests"
    $_qvar = "INSERT into $Table (jrnl_id,prov_id,plat_id,inst_id,metric_xref,yearmon,DOI,PropID";
    $_qval = " VALUES (?,?,?,?,?,?,?,?";
    foreach ( $YOPS as $yop ) {
      $_qvar .= ",`" . $yop . "`";
      $_qval .= ",?";
    }
    $_qry = $_qvar . ")" . $_qval . ")";

    // Process report records (once all headers are shifted off)
    //
    foreach ( $report_recs as $_Rec) {

      // Split the row on commas; allow for commas within quoted strings
      //
      $_cells = str_getcsv($_Rec , "," , "\"");

      // Turn title and platform strings into ID #'s
      // (If the cells don't exist, the record is bad.. skip it)
      // 
      if ( !isset($_cells[0]) || !isset($_cells[2]) || 
           !isset($_cells[5]) || !isset($_cells[6]) ) { continue; }
      $journal = ccp_find_journal($_cells[0], $_cells[5], $_cells[6]);
      $platform = ccp_find_platform($_cells[2]);

      // Store fields in variables
      //
      $_DOI = $_cells[3];
      $_PID = $_cells[4];

      // Run the query, but skip it if jrnl_id not set
      //
      if ( $journal['ID']>0) {

        // Setup insert arguments
        //
        $_args = array($journal['ID'],$prov_id,$platform['ID'],$inst_id,$metric,$yearmon,$_DOI,$_PID);
        foreach ( $YOPS as $idx=>$yop ) { $_args[] = $_cells[$idx]; }

        // execute the contract table insert
        //
        try {
          $sth = $ccp_adm_cnx->prepare($_qry);
          $sth->execute($_args);
        } catch (PDOException $e) {
          echo $e->getMessage();
        }
      }
    }
    return "Success";
  }
}
?>
