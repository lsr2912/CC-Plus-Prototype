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
// Mail_Alerts
//
// Usage:
//   # php Mail_Alerts.php
// Description:
//   This script pulls all active alerts from the CCPLUS <consortia>::alerts tables.
//   Processing iterates through all consortia defined, and within each:
//     * The alerts are formatted into strings
//     * The users table is then read for user info, including the user's alert
//       preferences. If the user wants emails for one or more alert types,
//       the message strings are combined into a an email and sent.
//
//---------------------------------------------------------------------------------------
// Load templates and helper functions
//
include_once('ccplus/constants.inc.php');
include_once('ccplus/dbutils.inc.php');
global $ccp_adm_cnx;
global $ccp_usr_cnx;

// Get global consortia info
//
$consortia = ccp_get_consortia();

foreach ( $consortia as $_Con ) {

  // Open database handles (force new ones)
  //
  $_db = "ccplus_" . $_Con['ccp_key'];
  $ccp_usr_cnx = ccp_open_db($_db, "User", 1);
  $ccp_adm_cnx = ccp_open_db($_db, "Admin", 1);

  // Get all active alerts and alert settings
  // If no active alerts, check next consortium
  //
  $active_alerts = ccp_get_alerts();
  if ( count($active_alerts) == 0 ) { continue; }
  $alert_settings = ccp_get_alert_settings();

  // Build an array to hold messages by looping through active alerts
  //
  $messages = array();
  foreach ( $active_alerts as $alert ) {

    // Setup institution fields and index
    //
    $_PROV = $alert['prov_id'];
    if ( !isset($messages[$_PROV]) ) {	// init provider first time
      $messages[$_PROV] = array();
      $messages[$_PROV]['string'] = "";
    }
    if ( $alert['prov_name'] != "") {
      $messages[$_PROV]['string'] .= "Alert On Provider: ";
      $messages[$_PROV]['string'] .= $alert['prov_name'] . "\n";
    }

    // One of settings_id or failed_id should be non-zero.
    //
    $_condition = "Unknown";
    if ( $alert['settings_id'] != 0 ) {
      $_condition = "  " . $alert['legend'] . " for " . $alert['yearmon'];
      $_condition .= " (" . $alert['Report_Name'] . ")";
      $_condition .= " varies by >" . $alert_settings[$alert['settings_id']]['variance'];
      $_condition .= "% compared to the last " . $alert_settings[$alert['settings_id']]['timespan'] . " months";
    } else if ( $alert['failed_id'] != 0 ) {
      $_condition  = "  Ingest Failed: " . $alert['Report_Name'] . " :: " . $alert['yearmon'] . "\n";
      $_condition .= "               : " . $alert['detail'];
    }

    // Format the alert message and add to the array
    //
    $messages[$_PROV]['string'] .= "  Condition : " . $_condition . "\n";

  }	// end foreach active alert

  // Get all ACTIVE user profiles and loop through them
  //
  foreach ( ccp_get_users(0,1) as $user ) {

    // If user not opting-in, skip 'em
    //
    if ( !($user['optin_alerts']) ) { continue; }

    // For now... only admins get alerted
    //
    if ( $user['role']!=ADMIN_ROLE ) { continue; }

    // Loop through $messages
    //
    $mail_text  = "\nThe CC-Plus System has detected active alert conditions";
    $mail_text .= " for the " . $_Con['name'] . " Consortium,\n";
    $mail_text .= " summarized here: " . CCPLUSROOTURL . "AlertsDash.php?Astat=Active\n\n";
    foreach ( $messages as $_msg ) {

      // If alerts get categorize by-inst, this is where we'll
      // filter what gets reported. Admins get everything
      //
      // if ( $user['inst_id']!=$_msg['inst_id'] && $user['role']!=ADMIN_ROLE ) { continue; }

      // Build the mail text
      //
      $mail_text .= $_msg["string"] . "\n";
    }
    $mail_text .= "\r\n";

    // Build and send the email message
    //
    if ( $user['email'] == "Administrator" ) {
      $to = $_Con['email'];
    } else {
      $to   = $user['email'];
    }
    $subj = "CC-Plus System Alerts";
    $from = "From: ccplus_system@ccplus.org\r\n";

    if ( !mail( $to, $subj, $mail_text, $from ) ) {
      print "Failed to send email for " . $_Con['ccp_key'] . " to " . $to . "\n";
    }
  }	// end for all users
}	// end foreach consortium
?>
