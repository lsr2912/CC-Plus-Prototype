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
// UpdateAlertSet.php
//
// Receives form-data as input to be saved in the database
// (updates/inserts to : alert_settings)
//
// NOTE **:
//   For now, any alert with variance=0 or timeframe=0 will be ignored.
//   This gives a "sneaky" way to delete an existing alert.
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Check rights; only proceed if role is Admin
//
$ERR = 0;
if ( $_SESSION['role'] != ADMIN_ROLE ) { $ERR = 1; }

// Check on the basic $_POST array elements.
// We'll check on the "stats alerts" variables below.
//
if ( !isset($_POST["measure"]) || !isset($_POST["Save"]) ) { $ERR = 2; }

// If no errors, proceed
//
if ($ERR == 0) {

  // setup database connection
  //
  global $ccp_adm_cnx;
  if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

  // Pull current alerts
  //
  $all_alerts = ccp_get_alert_settings();

  // Loop through the alerts to find out if any current alerts are "missing"
  // from the UI form fields. Any that are missing or have a variance=0 or
  // timeframe=0 will be deleted from the alert_settings table
  //
  $_dc = 0;
  $_delete = "(";
  foreach ($all_alerts as $_alert) {
    $_tim_var = "time_" . $_alert['ID'];
    $_var_var  = "var_" . $_alert['ID'];
    if ( isset($_POST[$_tim_var]) && isset($_POST[$_var_var]) ) {
      if ( $_POST[$_tim_var] == 0 || $_POST[$_var_var] == 0 ) {
        $_delete .= $_alert['ID'] . ",";
        $_dc++;
      }
    } else {
      $_delete .= $_alert['ID'] . ",";
      $_dc++;
    }
  }

  // Delete settings that need to go...
  //
  if ( $_dc > 0 ) {
    $_delete = preg_replace("/,$/",")",$_delete);
    $D_qry  = "DELETE FROM alert_settings WHERE ID IN " . $_delete;

    // execute the delete
    //
    try {
      $sth = $ccp_adm_cnx->prepare($D_qry);
      $sth->execute();
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }
  }

  // Loop through the alerts and build an UPDATE query for the ones that
  // need updating. Any rows with variance=0 or timeframe=0 get skipped.
  //
  $U_qry = "UPDATE alert_settings SET variance=?, timespan=?, active=? WHERE ID=?"; 
  foreach ($all_alerts as $_alert) {
    $_cbx_var = "cb_" . $_alert['ID'];
    $_tim_var = "time_" . $_alert['ID'];
    $_var_var  = "var_" . $_alert['ID'];
    if ( isset($_POST[$_tim_var]) && isset($_POST[$_var_var]) ) {
      $cbx_value = isset($_POST[$_cbx_var]) ? 1 : 0;
      if ( $_POST[$_tim_var] > 0 && $_POST[$_var_var] > 0 &&
           ( $_POST[$_var_var] != $_alert['variance'] ||
             $_POST[$_tim_var] != $_alert['timespan'] ||
             $cbx_value != $_alert['active'] ) ) {
        // Update alert_settings for the current alert
        //
        try {
          $sth = $ccp_adm_cnx->prepare($U_qry);
          $sth->execute(array($_POST[$_var_var], $_POST[$_tim_var], $cbx_value, $_alert['ID']));
        } catch (PDOException $e) {
          echo $e->getMessage();
          exit();
        }
      }
    }
  }	// process existing alerts found in form rows

  // Build an insert query based on the "new" form fields, new alerts are "active"
  //
  if ( isset($_POST['newcb']) ) {

    $I_qry = "INSERT INTO alert_settings (metric_xref,variance,timespan) VALUES (?,?,?)";
    $_new_count = count($_POST['newcb']);
    $_metric = $_POST['newmet'];
    $_variance = $_POST['newvar'];
    $_timespan = $_POST['newts'];
    
    for ( $_set=0; $_set<$_new_count; $_set++ ) { 
      $cbx_value = isset($_POST['newcb'][$_set]) ? 1 : 0;
      if ( $_POST['newvar'][$_set]==0 || $_POST['newts'][$_set] == 0 ) { continue; }
      $ins_vals = array($_POST['newmet'][$_set], $_POST['newvar'][$_set], $_POST['newts'][$_set]);

      // Update alert_settings for the current alert
      //
      try {
        $sth = $ccp_adm_cnx->prepare($I_qry);
        $sth->execute($ins_vals);
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }
    }
  }
}  // End-if no errors

ccp_close_db();

// If errors, signal and stop
//
if ($ERR > 0) {
   print_page_header("CC-Plus Alert Definitions - Error");
   print "<blockquote>\n";
   print "<p>&nbsp;</p>\n<h3>Error on form submission</h3>\n";
   print "<p><font size=\"+1\">Update failed because:</br />\n";
   switch ($ERR) {
     case 1:
       print "Your account is not authorized for such a request.";
       break;
     case 2:
       print "Invalid or missing arguments.";
   }
   print "<br /><br />You can return to the <a href='AdminHome.php'>CC-Plus Administration HomePage</a>\n";
   print "</font></p>\n";
   print "</blockquote>\n";
   
// Otherwise, print confirmation message
//
} else {
  print_page_header("CC-Plus Alert Settings Updated");
  header( "refresh:3;url=AlertSettings.php" );
?>
 <table cellpadding="3" cellspacing="3" border="0">
    <tr><td width="100"></td>
      <td><h3>Requested updates successfully completed.</h3></td>
    </tr>
    <tr><td></td>
      <td>
        <p><font size="+1">
          <br />You will be redirected back to the <a href="AlertSettings.php">CC-Plus Alert Settings page</a>.
        </font></p>
      </td>
    </tr>
  </table>
<?php
}
include 'ccplus/footer.inc.html.php';
?>
