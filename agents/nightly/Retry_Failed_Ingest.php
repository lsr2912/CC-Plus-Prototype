#!/usr/bin/php
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
// Retry_Failed_Ingest.php
//
// Command-line PHP script to retry previously failed ingests. Will only retry if
// #-attempts is less than max-attempts from admin-settings, and for attempts that ran
// before today (because if 1st attempt was today, we don't RE-try until tomorrow.)
//
// This script doesn't track or report the results of retried, it only causes them
// to run. The "targeted" scripts that retry the report(s) produce logs/messages.
//
// Usage:
//    # php Retry_Failed_Ingest.php
//
//--------------------------------------------------------------------------------------
include_once('ccplus/constants.inc.php');
include_once('ccplus/dbutils.inc.php');
include_once('ccplus/statsutils.inc.php');
global $ccp_usr_cnx;
global $ccp_adm_cnx;

// Loop through all defined consortia
//
$consortia = ccp_get_consortia();
foreach ( $consortia as $_Con ) {

  // Open database handles (force new ones) for the consortium
  //
  $_db = "ccplus_" . $_Con['ccp_key'];
  $ccp_usr_cnx = ccp_open_db($_db, "User", 1);
  $ccp_adm_cnx = ccp_open_db($_db, "Admin", 1);

  // Get an array of the settings for failed attempts with #-retries
  // less than MAX (from constants.inc) that happened before today.
  //
  $failed = ccp_failed_ingests( 0, MAX_INGEST_RETRIES );

  // Loop through the settings and retry the reports
  //
  foreach ( $failed as $target ) {

<<<<<<< refs/remotes/origin/master
    // If target RequestorID is not null, call out the SUSHI ingest
    //
    $command = "php ";
    if ( $target['RequestorID'] != "" ) {

      $command .= CCPLUSAGENTS . "nightly/Sushi_ingest.php" . " Cons=" . $_Con['ID'];
      $command .= " Inst=" . $target['inst_id'] . " Prov=" . $target['prov_id'];

    // Otherwise, we'll treat the retry as a custom scripted retrieval
    //
    } else {
      $command = $target['service_path'];
    }

    $command .= " Month=" . $target['yearmon'] . " Retry=" . $target['Failed_ID'];
    $command .= " Report=" . $target['report_name'];

    print $_Con['ccp_key'] . " : Ingest retry : " . $command . "\n";
=======
    // Call out the SUSHI ingest
    //
    $command = "php ";
    $command .= CCPLUSAGENTS . "nightly/Sushi_ingest.php" . " Cons=" . $_Con['ID'];
    $command .= " Inst=" . $target['inst_id'] . " Prov=" . $target['prov_id'];
    $command .= " Month=" . $target['yearmon'] . " Retry=" . $target['Failed_ID'];
    $command .= " Report=" . $target['report_name'];

    fwrite(STDOUT,$_Con['ccp_key'] . " : Ingest retry : " . $command . "\n");
>>>>>>> CC-Plus Version 0.2

    // Execute the command to retry
    //
    $result = system($command);

  }

}

?>
