<?php
//--------------------------------------------------------------------------------------
// Copyright 2017,2018 Scott Ross
// This file is part of CC-Plus.
//
// CC-Plus is free software: you can redistribute it and/or modify it under the tccps
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
// update_alerts.php
//
// Receives form-data as input to be saved in the database
// (updates the status field for the alerts table)
//
// NOTE: NO DELETIONS happen here - the ovenight alert update script
//       handles the actual record removal.
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Only Admins allowed to use this page
//
$ERR = 1;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] == ADMIN_ROLE ) { $ERR = 0; }
}
if ( !isset($_POST['Save'])) { $ERR = 2; };

// If no errors, proceed
//
if ($ERR == 0) {

  $alert_status = ccp_get_enum_values("alerts","status");

  // setup database connection
  //
  global $ccp_adm_cnx;
  if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

  // Build and execute an update query for each status, and
  // apply to all the ID's in $_POST that need that status.
  //
  foreach ( $alert_status as $_status ) {

    // Setup the query and initial arguments
    //
    $_args = array($_status,$_SESSION['ccp_uid']);
    $_qry  = "UPDATE alerts SET status=? , modified_by=? WHERE ID IN (";

    // Loop through all POST inputs. Any status dropdowns matching the current
    // status will have their ID added to the query.
    //
    foreach ( $_POST as $key => $value ) {
      if ( $value != $_status ) { continue; }
      if ( !preg_match("/^stat_/", $key) ) { continue; }
      array_push($_args,substr($key,5));
      $_qry .= "?,";
    }

    // If $_args still has only 2 values, no ID's in $_POST need this status
    //
    if ( count($_args) == 2 ) { continue; }

    // Finalize and execute the query
    //
    $_qry = preg_replace("/,$/",")",$_qry);	// trailing comma becomes a paren
    $_qry .= " AND status!=?";			// preserve time_stamp is status not changing
    array_push($_args,$_status);

    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute($_args);
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }
  }

  ccp_close_db();

}  // End-if no errors

// If errors, signal and stop
//
if ($ERR == 1) {
   print_noaccess_error();
} else if ($ERR > 1) {
   print_page_header("CC-Plus Alert Updates - Error");
   print "<blockquote>\n";
   print "<p>&nbsp;</p>\n<h3>Error on form submission</h3>\n";
   print "<p><font size=\"+1\">Database update failed because:</br />\n";
   print "Missing or invalid arguments were provided.";
   print "<br /><br />You can return to the <a href='AlertsDash.php'>Alerts Page here</a>.\n";
   print "</font></p>\n";
   print "</blockquote>\n";
   
// Otherwise, print confirmation message
//
} else {
  print_page_header("CC-Plus Alert Settings Updated");
  $redir_url = "AlertsDash.php";
  $_place = "the Alerts Dashboard";
  header( "refresh:3;url=" . $redir_url );
?>
 <table cellpadding="3" cellspacing="3" border="0">
    <tr><td width="100"></td>
      <td><h3>Requested updates successfully completed.</h3></td>
    </tr>
    <tr><td></td>
      <td>
        <p><font size="+1">
          <br />You will be returned to
<?php print " <a href=\"" . $redir_url . "\">" . $_place . "</a>"; ?>
        </font></p>
      </td>
    </tr>
  </table>
<?php
}
include 'ccplus/footer.inc.html.php';
?>
