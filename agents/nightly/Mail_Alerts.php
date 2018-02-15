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
//     * The message strings and combined into a an email and sent.
//
//---------------------------------------------------------------------------------------
// Load templates and helper functions
//
include_once('ccplus/constants.inc.php');
include_once('ccplus/dbutils.inc.php');
global $ccp_usr_cnx;

// If no active alerts, bail out
//
if ( count($active_alerts) == 0 ) { exit; }

// Get global consortia info
//
$consortia = ccp_get_consortia();

foreach ( $consortia as $_con ) {

  // Open database for the consortium
  //
  $_db = "ccplus_" . $consortia['ccp_key'];
  $ccp_usr_cnx = ccp_open_db($_db);

  // Pull all active alerts and setup array to hold messages
  //
  $active_alerts = ccp_get_alerts();
  $messages = array();

  foreach ( $active_alerts as $alert ) {

    // Setup institution fields and index
    //
    $_inst = $alert['inst_id'];
    if ( $alert['inst_name'] != "") {
      $messages[$_inst]['string'] .= "Alerts for : ";
      $messages[$_inst]['string'] .= trim($alert['inst_name']) . "\n";
    }

    // One of settings_id or failed_id should be non-zero.
    //
    $_condition = "Unknown";
    if ( $alert['settings_id'] != 0 ) {
      $_condition = $alert['legend'];
    } else if ( $alert['failed_id'] != 0 ) {
      $_condition = "Failed Stats Ingest";
    }

    // Format the alert message and add to the array
    //
    $messages[$_inst]['string'] .= "  Condition : " . $_condition . " :";
    if ( $alert['prov_name'] != "") {
      $messages[$_inst]['string'] .= " Provider: ";
      $messages[$_inst]['string'] .= trim($alert['prov_name']);
    }
    $messages[$_inst]['string'] .= "\n";
  }	// end foreach active alert

  // Get all ACTIVE user profiles and loop through them
  //
  foreach ( ccp_get_users(0,1) as $user ) {

    // Skip non-admins if opt-in is not set 
    //
    if ( !($user['optin_alerts']) && $user['role']!=ADMIN_ROLE ) {
      continue;
    }

    // Loop through $messages
    //
    $mail_text  = "\n\nThe CC-Plus System has active alert conditions\n";
    $mail_text .= "All active alerts are summarized here: ";
    $mail_text .= CCPLUSROOTURL . "AlertsDash.php?Astat=Active\n\n";
    foreach ( $messages as $_msg ) {

      // Users (managers or not), only get alerte for their own inst,
      // admins get everything.
      //
      if ( $user['inst_id']!=$_msg['inst_id'] && $user['role']!=ADMIN_ROLE ) {
         continue;
      }

      // Build the mail text
      //
      $mail_text .= $_msg["string"] . "\n";
    }
    $mail_text .= "\r\n";

    // Build and send the email message
    //
    $to   = $user['email'];
    $subj = "CC-Plus System Alerts";
    $from = "From: ccplus_system@ccplus.org\r\n";

    if ( !mail( $to, $subj, $mail_text, $from ) ) {
      print "Failed to send email to : " . $user['email'] . "\n";
    }
  }	// end for all users
}	// end foreach consortium
?>
