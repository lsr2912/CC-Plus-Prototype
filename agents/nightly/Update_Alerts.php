#!/usr/bin/php
<?php
//
// WARNING :: This script not ready (yet), still needs work
//
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
//   * Failed-Ingest alerts to-be-deleted have their retry_count reset to zero
//     (the records in failed_ingest remain; this causes retries to resume.)
//   * If an active alert is already set for a condition, nothing is changed
//     Otherwise, an entry is added to the alerts table.
//
//---------------------------------------------------------------------------------------
// Load templates and helper functions
//
include_once('ccplus/constants.inc.php');
include_once('ccplus/dbutils.inc.php');
include_once('ccplus/statsutils.inc.php');

// Connect to the database as admin
//
global $ccp_adm_cnx;
if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("ccplus_global","Admin"); }

// Get all alerts
//
$alerts = ccp_get_alerts('ALL');

// Get alert settings and setup some date variables
//
$alert_settings = ccp_get_alert_settings();
$end_month = date("Y-m", strtotime("-1 months") );
$__emo = date("n", strtotime("-1 months"));
$__eyr = date("Y", strtotime("-1 months"));

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

//-->> RESUME EDITS HERE, ONCE THERE IS DATA TO LOOK AT     <<--
//-->> Probably need an Outer loop on by-consortia (above?) <<--

// Test for statistics-related alerts. Check stored data for one month
// ago for out-of-tolerance variance.
//
$temp_tables = array();
foreach ( $alert_settings as $alert ) {

  if ( !($alert['active']) ) { continue; }	// skip if disabled
  if ( $alert['timespan']==0 ) { continue; }	// don't divide by-zero

  // Setup a temporary table of records we care about.
  //
  $start_month = date("Y-m", strtotime("-".$alert['timespan']." months") );
  $table = "stats_" . $start_month . "_" . $end_month;
  $table = preg_replace('/-/', '', $table);

  // If the temp table isn't built yet, create it and remember the name
  //
  if ( !in_array( $table, $temp_tables ) ) {
    $res = ccp_temp_statsmerge( $table, $start_month, $end_month );
    array_push($temp_tables, $table);
  }

  // Loop through all resources with stored stats for last month, and 
  // check whether they are out-of-range from allowed variance.
  //
  foreach ( $sources as $_source ) {

    // Get details on the resource
    //
    $_rsrc = $_source['rsrc_id'];
    $_vend = $_source['vend_id'];

    // Get averages and last-month counts for all measures from the temp table
    //
    $average = ccp_total_usage( 0, $_rsrc, $table, 'AVG' );

    // Get sum of counts for the resource for last month
    //
    $lastmo_table = date("Y", strtotime("-1 months")) . "_vendor_stats";
    $_lmo = date("n", strtotime("-1 months") );
    $last_mo = ccp_total_usage( 0, $_rsrc, $lastmo_table, 'SUM', $_lmo, $_lmo );

    // Alerts with db_field set use that field as single measurement 
    //
    if ( $alert['db_field'] != "" ) {
      if ( $average[$alert['db_field']] == 0 ) {
        $variance = 0;
      } else {
        $variance = abs( (1 - $last_mo[$alert['db_field']] / $average[$alert['db_field']]) * 100 );
      }

    } else {

      // Compute cost-per-use variance
      //
      $variance = 0;
      $current_cpu = ccp_resource_cost_per_use ($_rsrc, $last_mo, $_lmo, $_lmo);
      $average_cpu = ccp_resource_cost_per_use ($_rsrc, $average, $start_month, $end_month);
      if ( is_numeric($current_cpu) && is_numeric($average_cpu)) {
        if ( $current_cpu > 0 ) {
          $variance = (1 - $average_cpu / $current_cpu) * 100;
        }
      }

    } // end-if db-only field or computed

    // If variance is out-of-bounds, set the alert
    //
    if ( $variance > $alert['variance'] ) {
      $status = ccp_set_alert(1,'Active', $_vend, $_rsrc, $alert['ID']);
      if ( !$status ) {
        $msg  = "Failed to set alert! " . $alert['condition'] . " out of range for ";
        $msg .= "resource_ID: " . $_rsrc . "\n";
        print $msg;
      }
    }	// end-if variance out-of-bounds?
  }	// end-foreach resource ID
}	// end-foreach alert_settng

// ---------------------------------------------------------------
// Test for ingests that have failed max_retry times. Set an alert 
// for any that have hit the limit.
// ---------------------------------------------------------------
$failed = ccp_failed_ingests();
foreach ( $failed as $_attempt ) {
  if ( $_attempt['retry_count'] < MAX_INGEST_RETRIES ) { continue; }
  $status = ccp_set_alert(1,'Active', $_attempt['vend_id'], 0, 0, $_attempt['Failed_ID']);
}

// -------------------------------------------------------------------------------
// Perform tests for financial alerts
// If - in the future - there are more of these, more elegant code will be needed.
// For now (as of Jan-2017), there are only 2 - so brute force is simplest
// -------------------------------------------------------------------------------

// Loop through alerts (skip stats)
//
foreach ( $alert_settings as $alert ) {

  if ( ($alert['alert_type'] != CON_ALERT) && 
       ($alert['alert_type'] != INV_ALERT) ) { continue; }
  if ( !$alert['enabled'] ) { continue; }	// if disabled, skip it

  // Check for contracts past the renewal timespan. For any that need alerts,
  // also set the related resources to inactive.
  //
  if ( $alert['condition'] == "Contract Not Renewed" ) {

    // Check all (active) resources
    //
    $all_sources = ccp_get_resources();
    foreach ( $all_sources as $_src ) {

      if ( $_src['status'] != 'Active' ) { continue; }	// skip inactive sources

      // If contract expired more than 'timespan' days ago, set an alert and set the
      // resource to 'Inactive'
      //
      if ( ccp_contract_renewal_overdue ( 0, $_src['resource_id'], $alert['timespan'] ) ) {
        $status = ccp_set_alert(2,'Active', $_src['vend_id'], $_src['resource_id'], $alert['ID']);
        if ( !$status ) {
          $msg  = "Failed to set alert! Overdue : " . $alert['condition'] . " for ";
          $msg .= "resource_ID: " . $_src['resource_id'] . "\n";
          print $msg;
        }
        $status = ccp_set_resource_status($_src['resource_id'], 'Inactive');
        if ( !$status ) {
          $msg  = "Failed to mark resource_ID" . $_src['resource_id'] . " as inactive";
          $msg .= " (contract renewal is overdue).\n";
          print $msg;
        }
      }	// end-if contract is overdue?
    }	// end foreach resource
  }	// end-if alert condition is check contract overdue

  // Check for overdue invoices
  //
  if ( $alert['condition'] == "Invoice Overdue" ) {

    // Check all contracts with overdue invoices
    //
    $contracts = ccp_get_contracts_findash( 0, array('Overdue'), array('Paid','Unpaid') );

    // For every contract returned, set an alert
    // (If its already set, function does nothing and returns success)
    //
    foreach ( $contracts as $_con) {
      $status = ccp_set_alert(3,'Active', $_con['Vnd_id'], 0, $alert['ID'], 0, $_con['contract_id']);
      if ( !$status ) {
        $msg  = "Failed to set alert! " . $alert['condition'] . " for: ";
        $msg .= $_con['vend_name'] . " contract_ID: " . $_con['contract_id'] . "\n";
        print $msg;
      }
    }	// end foreach contracts with overdue invoices
  }	// end-if alert condition is test for overdue invoices
}	// foreach alert
?>
