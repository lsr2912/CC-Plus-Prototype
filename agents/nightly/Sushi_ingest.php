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
// SUSHI ingest processing script
//
// Usage:
//
//   # php Sushi_ingest.php
//     Run without arguments, the script will process all providers and institutions
//      that are defined to be run TODAY, and will request report(s) for last month.
//
//   # php Sushi_ingest.php Month=YYYY-MM
//     Run with just a Month as an argument, the script will process ALL
//     reports for ALL providers and institutions regardless of the day-of-month setting
//     for the requested month (Month="lastmonth" works the same as no Month argument)
//
//   # php Sushi_ingest.php [[Cons=#] [Inst=#]] [Prov=#] [Month=YYYY-MM | lastmonth] [Report=<name>] [Retry=#]
//     Run with a consortia ID (Cons), and institution ID (Inst), or provider (Prov) ID,
//     the scripts processes ALL - or a specific - report(s) for last month, or a given month.
//     Inst argument only tested when a Cons argument is given. Adding "Retry=ID" causes
//     the logging to track and increment the counter for failed_ingest:ID if this run
//     is a retry of a prior, failed request.
//
// All successful or failed attempts are logged to the database.
//
//---------------------------------------------------------------------------------------
// Load templates and helper functions
//
include_once('ccplus/constants.inc.php');
include_once('ccplus/dbutils.inc.php');
include_once('ccplus/statsutils.inc.php');
include_once('ccplus/counter4_parsers.php');
include_once('ccplus/counter4_processors.php');
global $ccp_usr_cnx;
global $ccp_adm_cnx;

// CCPLUSREPORTS is defined in constants.inc.php
//
if (!file_exists(CCPLUSREPORTS)) {	// Make sure it exists
   mkdir(CCPLUSREPORTS, 0755, true);
}

// Check arguments; allow execution from command line
//
$_ARG_count = count($argv);
if ( $_ARG_count > 0 ) {

  // Roll up any command-line input arguments into the $_GET array
  //
  foreach ($argv as $arg) {
    $e=explode("=",$arg);
    if (count($e)==2) {
      $_GET[$e[0]]=$e[1];
    } else {
      $_GET[]=$e[0];
    }
  }
}

// Allow a retryID argument, default it to zero
//
$retryID = 0;
if ( isset($_GET['Retry']) ) { $retryID = $_GET['Retry']; }

// Default to ALL reports enabled, but allow an argument
//
$ReportArg = "ALL";
if ( isset($_GET['Report']) ) { $ReportArg = $_GET['Report']; }

// Default to last month, but allow an argument
//
$Marg = "lastmonth";
if ( isset($_GET['Month']) ) { $Marg = $_GET['Month']; }

// Setup month string for pulling the report and Begin/End for parsing
//
if (strtolower($Marg) == 'lastmonth') {
  $Begin = date("Y-m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
} else {
  $Begin = date("Y-m", strtotime($Marg));
}
$yearmon = $Begin;
$End = $Begin;
$Begin .= '-01';
$End .= '-'.date('t',strtotime($End.'-01'));

// Default to ALL Insts in ALL Consortia, but allow arguments
//
$con_id=0;
$inst_id=0;
if ( isset($_GET['Cons']) ) { $con_id = $_GET['Cons']; }
if ( $con_id != 0 ) { 
  $consortia = array( ccp_get_consortia($con_id) );
  if ( isset($_GET['Inst']) ) { $inst_id = $_GET['Inst']; }
} else {
  $consortia = ccp_get_consortia();
}

// Loop on consortia requested
//
foreach ( $consortia as $_Con ) {

  if ( $con_id == 0 ) {	// If running multiple consortia, print an info line
    fwrite(STDOUT,"Sushi Requests Begin For Consortium: " . $_Con['ccp_key'] . "\n");
  }

  // Open database handles (force new ones) for the consortium
  //
  $_db = "ccplus_" . $_Con['ccp_key'];
  $ccp_usr_cnx = ccp_open_db($_db, "User", 1);
  $ccp_adm_cnx = ccp_open_db($_db, "Admin", 1);

  // Setup the $providers array to hold ID's to process
  //
  $prov_id = 0;
  if ( isset($_GET['Prov']) ) {
    $providers = array( ccp_get_providers($_GET['Prov']) );
  } else {
    $providers = ccp_get_providers_ui( 1 );	// only active
  }

  // Loop on providers and pull settings for each as we go
  //
  foreach ( $providers as $_Prov ) {

    $_PROV = $_Prov['prov_id'];
    $ReportPath = CCPLUSREPORTS . $_Con['ccp_key'];

    // Get all ingest settings for the provider
    // 
    $day = 0;
    if ( $Marg == "lastmonth" ) { $day = date('j'); }	// Get just today's
    $ingest_settings = ccp_get_sushi_settings( $inst_id, $_PROV, $day );

    // An empty array means "not today", or no settings exist
    //
    if ( count($ingest_settings) == 0 ) { continue; }

    // Connect to service, setup SOAP client
    //
    include ('ccplus/sushi_connect.inc');

    // Apply WSSE authentication if required
    //
    if (preg_match("/WSSE/i", $Security)) {
      include ('ccplus/sushi_wsse.inc');
    }

    // Loop through targets and pull reports
    //
    $_Agent = uniqid("CCplusSUSHI:", true);
    foreach ( $ingest_settings as $_settings ) {

      $_INST = $_settings['inst_id'];

      // Get reports to be pulled
      //
      $_reports = ccp_get_counter_reports( $_PROV );
      foreach ( $_reports as $_Rpt ) {

        // Request the report, the result tells how many "continues" to execute
        //
        $_res = include('ccplus/sushi_request.inc');
        if ( !$_res ) {
          fwrite(STDOUT,"Cannot include CC-Plus SUSHI request template!\n");
          exit;
        }
        if ( $_res != "Success" ) {
          if ( $_res == 1 ) { continue; }
          if ( $_res == 2 ) { continue 2; }
        }

        // Set filename for XML and save it in a file
        // 
        $_RPT = $Report.'v'.$Release.'_'.$Begin.'_'.$End;
        $xml_file  = $ReportPath . '/' . $_settings['inst_name'] . '/' . $Provider . '/XML/' . $_RPT . '.xml';
        if (!file_exists($ReportPath . '/' . $_settings['inst_name'] . '/' . $Provider . '/XML')) {
          mkdir($ReportPath . '/' . $_settings['inst_name'] . '/' . $Provider . '/XML', 0755, true);
        }
        file_put_contents($xml_file, $xml);

        fwrite(STDOUT,"$Report retrieved from $Provider for " . $_settings['inst_name'] . " for $Begin to $End\n");
        fwrite(STDOUT,"XML Response saved as: '$xml_file'\n");

        // Parse the XML and save as a CSV file
        //
        $status = "";
        $counter_file  = $ReportPath . '/' . $_settings['inst_name'] . '/' . $Provider . '/COUNTER/' . $_RPT . '.csv';
        if (!file_exists($ReportPath . '/' . $_settings['inst_name'] . '/' . $Provider . '/COUNTER')) {
          mkdir($ReportPath . '/' . $_settings['inst_name'] . '/' . $Provider . '/COUNTER', 0755, true);
        }
        if ( $Report == "JR1" && $Release = "4" ) {
          $status = parse_counter_JR1v4 ( $xml_file, $counter_file, $Begin, $End );
<<<<<<< refs/remotes/origin/master
          if ( $status == "Success" ) { $status = process_counter_JR1v4 ( $counter_file, $_PROV, $_INST, $yearmon, $_db ); }
        } else if ( $Report == "JR5" && $Release = "4" ) {
          $status = parse_counter_JR5v4 ( $xml_file, $counter_file, $Begin, $End );
          if ( $status == "Success" ) { $status = process_counter_JR5v4 ( $counter_file, $_PROV, $_INST, $yearmon, $_db ); }
=======
          if ( $status == "Success" ) {
            // Clear out stored data if it exists...
            //
            if ( ccp_count_report_records($Report,$_INST,$_PROV,0,$yearmon,$yearmon,$_db) > 0 ) {
              $_erased = ccp_erase_report_records($Report,$_INST,$_PROV,0,$yearmon,$yearmon,$_db);
            }
            // Stored data from the CSV in the database
            //
            $status = process_counter_JR1v4 ( $counter_file, $_PROV, $_INST, $yearmon, $_db );
          }
        } else if ( $Report == "JR5" && $Release = "4" ) {
          $status = parse_counter_JR5v4 ( $xml_file, $counter_file, $Begin, $End );
          if ( $status == "Success" ) {
            // Clear out stored data if it exists...
            //
            if ( ccp_count_report_records($Report,$_INST,$_PROV,0,$yearmon,$yearmon,$_db) > 0 ) {
              $_erased = ccp_erase_report_records($Report,$_INST,$_PROV,0,$yearmon,$yearmon,$_db);
            }
            // Stored data from the CSV in the database
            //
            $status = process_counter_JR5v4 ( $counter_file, $_PROV, $_INST, $yearmon, $_db );
          }
>>>>>>> CC-Plus Version 0.2
        }
        if ($status != "Success") {
          fwrite(STDOUT, $status."\n");
          ccp_log_failed_ingest($_PROV, $_INST, $_settings['ID'], 0, $_Rpt['ID'], $yearmon, "CSV", $status, $retryID);
          continue;
        }

        // Drop a record in the global stats log
        //
        fwrite(STDOUT,"SUSHI : $Report successfully processed and saved as: '$counter_file'\n");
        ccp_record_ingest( $_PROV, $_INST, $_Rpt['ID'], $yearmon, "Saved" );

<<<<<<< refs/remotes/origin/master
        // If this was a retry attempt (and we made it this far) clear the failed record
        //
=======
        // If this was a retry attempt, OR there was a failed_ingest with a pending
        // retry that matches what just got successfully ingested, clear the failure
        // record to prevent further retries.
        //
        if ( $retryID == 0 ) {
          $pending_ID = ccp_retries_pending($_PROV, $_INST, $Report, $yearmon);
          if ( $pending_ID != 0 ) { $retryID = $pending_ID; }
        } 
>>>>>>> CC-Plus Version 0.2
        if ( $retryID != 0 ) { ccp_clear_failed($retryID); }

      }	// For-each report to retrieve
    }	// For-each ingest-target to process
  }	// For-each provider to process

  // Close up database handles
  //
  $ccp_usr_cnx = null;
  $ccp_adm_cnx = null;

}	// For-each consortium to process
?>
