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
// Update_Alerts
//
// Usage:
//   # php Update_Alerts.php
// Description:
//   This script processes statistics data from the CC-Plus database
//   tables and updates the alerts table as needed.
// Notes:
//   * Alerts which are "active" beyond SILENCE_DAYS (constants.inc) are
//     set to status='silent'.
//   * Alerts marked for deletion are deleted.
//   * Failed-Ingest alerts to-be-deleted have the corresponding retry_count in the
//     failed_ingest table reset to zero (the records in failed_ingest remain;
//     this causes retries to resume.)
//   * If an active alert is already set for a condition, nothing is changed
//     Otherwise, an entry is added to the alerts table.
//
//---------------------------------------------------------------------------------------
// Load templates and helper functions
//
include_once('ccplus/constants.inc.php');
include_once('ccplus/dbutils.inc.php');
include_once('ccplus/statsutils.inc.php');

// Pull info on all consortia in the system
//
$consortia = ccp_get_consortia();

// Loop over each consortium
//
foreach ( $consortia as $_Con ) {

  // Open database handles (force new ones)
  //
  $_db = "ccplus_" . $_Con['ccp_key'];
  $ccp_usr_cnx = ccp_open_db($_db, "User", 1);
  $ccp_adm_cnx = ccp_open_db($_db, "Admin", 1);

  // Get all alerts
  //
  $alerts = ccp_get_alerts('ALL');

  // Get alert settings and setup some date variables
  //
  $alert_settings = ccp_get_alert_settings();

  // Silence any alerts beyond SILENCE_DAYS
  //
  $upd_qry = "UPDATE alerts SET status='Silent',modified_by=0 WHERE ID=?";
  foreach ( $alerts as $_alert ) {

    if ( $_alert['status'] != 'Active' ) { continue; }

    // compute #-days between now and time_stamp
    //
    $days = floor( (time() - strtotime($_alert['time_stamp'])) / (60*60*24) );

    if ( $days > SILENCE_DAYS ) {
      try {
        $sth = $ccp_adm_cnx->prepare($upd_qry);
        $sth->execute(array($_alert['ID']));
      } catch (PDOException $e) {
        echo $e->getMessage();
      }
    }
  }

  // Check for and remove any alerts marked for deletion
  // ---------------------------------------------------
  // Setup query strings for use with the loop
  //
  $del_qry = "DELETE FROM alerts WHERE ID=?";
  $upd_qry = "UPDATE failed_ingest SET retry_count=0 WHERE ID=?";
  foreach ( $alerts as $_alert ) {

    if ( $_alert['status'] != 'Delete' ) { continue; }

    // If this alert is for a failed ingest, update the record now
    //
    if ( $_alert['failed_id'] != 0 ) {
      try {
        $sth = $ccp_adm_cnx->prepare($upd_qry);
        $sth->execute(array($_alert['failed_id']));
      } catch (PDOException $e) {
        echo $e->getMessage();
      }
    }

    // Delete the alert
    //
    try {
      $sth = $ccp_adm_cnx->prepare($del_qry);
      $sth->execute(array($_alert['ID']));
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }

  // Test for statistics-related alerts, by-provider.
  // Check stored data for out-of-tolerance variance.
  //
  $providers = ccp_get_providers();
  $_end = date("Y-m", strtotime("-1 months") );
  foreach ( $alert_settings as $alert ) {

    if ( !($alert['active']) ) { continue; }	// skip if disabled
    if ( $alert['timespan']==0 ) { continue; }	// don't divide by-zero

    // Set beginning point for comparisons (end set above).
    //
    $_begin = date("Y-m", strtotime("-" . ($alert['timespan']+1) . " months") );

    // Loop through all providers
    //
    foreach ( $providers as $_prov ) {

      // If there are no records stored for one month ago, skip this provider
      //
      $_PROV = $_prov['prov_id'];
      if ( ccp_count_report_records( $alert['Report_Name'],0,$_PROV,0,$_end,$_end,$_db) == 0 ) { continue; }

      // Get averages and last-month counts for the settings' report-measure
      //
      $range_total = ccp_total_usage($_PROV,$alert['Report_Name'],$alert['col_xref'],0,$_begin,$_end,$_db);
      $average = $range_total / MonthCount($_begin,$_end);

      if ( $average > 0 ) {

        // Get sum of counts for the resource for last month
        //
        $last_mo = ccp_total_usage($_PROV,$alert['Report_Name'],$alert['col_xref'],0,$_end,$_end,$_db);

        // If variance is out-of-bounds, set the alert
        //
        $variance = abs( (1 - $last_mo / $average) * 100 );
        if ( $variance > $alert['variance'] ) {
          // print " Setting alert for " . $_Con['name'] . "\n";
          // print "   Out-of-variance, Month=" . $_end . " , prov=" . $_PROV . " , alert_ID: " . $alert['ID'] . "\n";
          // print "   Total=" . $range_total . " , AVG=" . $average . " , Last_mo=" . $last_mo . " , VAR=" . $variance . "\n";
          $status = ccp_set_alert('Active', $_end, $_PROV, $alert['ID']);
          if ( !$status ) {
            $msg  = "Failed to set alert! " . $alert['Report_Name'] . "::" . $alert['legend'] . " out of range for ";
            $msg .= $_prov['name'] . " for month: " . $_end . ".\n";
            print $msg;
          }
        }	// end-if variance out-of-bounds?
      }		// average > 0?

    }	// end-foreach provider ID

  }	// end-foreach alert_settng

  // ---------------------------------------------------------------
  // Test for ingests that have failed max_retry times. Set an alert 
  // for any that have hit the limit.
  // ---------------------------------------------------------------
  $failed = ccp_failed_ingests();
  foreach ( $failed as $_attempt ) {
    if ( $_attempt['retry_count'] < MAX_INGEST_RETRIES ) { continue; }
    // print " Setting alert for " . $_Con['name'] . "\n";
    // print "   Month: " . $_end . " , prov=" . $_attempt['prov_id'] . " , failed_ID: " . $_attempt['Failed_ID'] . "\n";
    $status = ccp_set_alert('Active', $_attempt['yearmon'], $_attempt['prov_id'], 0, $_attempt['Failed_ID']);
  }

}	// foreach consortium 
?>
