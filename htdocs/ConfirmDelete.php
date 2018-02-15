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
// ConfirmDelete.php
//
// Prompts for confirmation of a delete-record request
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';
include_once 'ccplus/statsutils.inc.php';

// Check rights; only Admins allowed to delete things...
// 
$_ERR = 0;
if ( $_SESSION['role'] != ADMIN_ROLE ) { $_ERR = 1; }

// Right now, only providers and reports are deleted by this script
//
$_RecType = "";
if ( isset($_REQUEST['prov']) ) {
  $_ID = $_REQUEST['prov'];
  $_RecType = "provider";

// for RecType='report', 'rept' points to an ingest-ID
//
} else if ( isset($_REQUEST['rept']) ) {
  $_ID = $_REQUEST['rept'];
  $_RecType = "report";
  $_Rept = ccp_get_ingest_record(0,0,0,0,"",0,$_ID);

} else {
  if ( !isset($_POST['Delete']) ) { $_ERR = 2; }
}

if ($_ERR == 0) {


  // If user wants to stop the delete, redirect to home page
  //
  if ( isset($_POST['No']) ) {
     header("Location: AdminHome.php", true, 303);
     exit;
  }

  // If User confirmed it, then proceed
  //
  $_count = 0;
  if ( isset($_POST['Delete']) && isset($_POST['Yes']) ) {

    if ( ($_POST['Delete'] == "Delete") && ($_POST['Yes'] == "Yes, I am Sure") &&
         (isset($_POST['RecType'])) && (isset($_POST['ID'])) ) {

      // Setup database connection
      //
      global $ccp_adm_cnx;
      if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

      //
      // Build an array of delete queries depending on RecortType to
      // clear implicated records from the database.
      //
      $_queries = array();

      if ( $_POST['RecType'] == "provider" ) {
        $_q = "DELETE FROM alerts WHERE prov_id = " . $_POST['ID'];
        array_push($_queries, $_q);
        $_q = "DELETE FROM counter_xref WHERE prov_id = " . $_POST['ID'];
        array_push($_queries, $_q);
        $_q = "DELETE FROM institution_aliases WHERE prov_id = " . $_POST['ID'];
        array_push($_queries, $_q);
        $_q = "DELETE FROM sushi_settings WHERE prov_id = " . $_POST['ID'];
        array_push($_queries, $_q);
        $_q = "DELETE FROM provider WHERE prov_id = " . $_POST['ID'];
        array_push($_queries, $_q);
      } else if ( $_POST['RecType'] == "report" ) {
        $_Rept = ccp_get_ingest_record(0,0,0,0,"",0,$_POST['ID']);
        $_Table = $_Rept['Report_Name'] . "_Report_Data";
        $_q = "UPDATE ingest_record SET status='Deleted' WHERE ID = " . $_POST['ID'];
        array_push($_queries, $_q);
        $_q  = "DELETE FROM " . $_Table . " WHERE prov_id=" . $_Rept['prov_id'];
        $_q .= " AND yearmon='" . $_Rept['yearmon'] . "' AND inst_id=" . $_Rept['inst_id'];
        array_push($_queries, $_q);

      } else {
        $_ERR = 2;
      }

      // Loop through the queries build above and execute each one
      // 
      foreach ( $_queries as $_qry) {
        try {
          $_count += $ccp_adm_cnx->exec($_qry);
        } catch (PDOException $e) {
          echo $e->getMessage();
        }
      }
      ccp_close_db();

    } else {
      $_ERR = 2;
    }
  }

  // If $_count is still zero, the request is unconfirmed
  // Build a quick form to prompt for confirmation
  //
  if ( ($_ERR == 0) && ($_count == 0) ) {

    // Get details on the thing we're deleting
    //
    $_details = array();
    $_deps = array();
    if ( $_RecType == "provider" ) {
      $_settings = ccp_get_providers( $_ID );
      $_details['Name'] = $_settings['name'];
      $_details['Status'] = ($_settings['active']==1) ? "Active" : "Inactive";
      $_details['Server URL'] = $_settings['server_url'];
      $_details['Tables'] = "alerts, aliases, and SUSHI settings";
      $_details['Related'] = ", AND any related settings for alerts, aliases, and SUSHI settings";
    } else if ( $_RecType == "report" ) {
      $_details['Report'] = $_Rept['report_name'];
      $_details['Provider'] = $_Rept['prov_name'];
      $_details['Institution'] = $_Rept['inst_name'];
      $_details['Year-Month'] = $_Rept['yearmon'];
      $_details['Related'] = ", AND mark this report in the Ingest Log as Deleted.";
    }

    print_page_header("CC-Plus : Confirm Deletion");

    // Display details on the target and prompt for confirmation
    //
?>
  <table cellpadding="3" cellspacing="3" border="0">
    <tr><td width="100"></td><td colspan="2"><h3>Warning: deletion cannot be reversed!</h3></td></tr>
    <tr><td></td><td colspan="2">
      <p>This operation will delete the following
<?php
    print $_RecType . " record(s)";
    print isset($_details['Related']) ? $_details['Related'] : ".";
?>
      <p><strong>Are you sure you want to delete this <?php echo $_RecType; ?>?</strong></p>
    </td></tr>
<?php
    foreach ($_details as $key=>$value) {
      if ( $key == "Related" ) { continue; }
      print "<tr><td></td><td>" . $key . ":</td><td>" . $value . "</td></tr>\n";
    }
?>
    <tr><td></td><td colspan="2">
      <form name="Confirm" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
        <input type="hidden" name="RecType" value='<?php echo $_RecType; ?>'>
        <input type="hidden" name="ID" value=<?php echo $_ID; ?>>
        <input type="hidden" name="Delete" value="Delete">
        <p>&nbsp;</p>
        <input type="submit" name="Yes" value="Yes, I am Sure"> &nbsp; &nbsp; &nbsp;
        <input type="submit" name="No" value="No! Stop!">
      </form>
    </td></tr>
  </table>
<?php
  include 'ccplus/footer.inc.html.php';
  exit;
  }
}

// If errors, signal and stop
//
if ($_ERR > 0) {
   print_page_header("CC-Plus - Error");
   print "<blockquote>\n";
   print "<p>&nbsp;</p>\n<h3>Error or Invalid Request</h3>\n";
   print "<p><font size=\"+1\">The requested update failed because:</br />\n";
   switch ($_ERR) {
     case 1:
       print "Your account is not authorized for such a request.";
       break;
     case 2:
       print "Invalid arguments were provided.";
       break;
   }
   print "<br /><br />You can use the back button, or return to the <a href='AdminHome.php'>";
   print "CC-Plus Administration Home Page</a>\n</font></p>\n";
   print "</blockquote>\n";
   

// Otherwise, print confirmation message
//
} else {
  print_page_header("CC-Plus Delete Request Confirmation");
?>
 <table cellpadding="3" cellspacing="3" border="0">
    <tr><td width="100"></td><td><h3>Requested deletion successfully completed.</h3></td></tr>
    <tr><td></td>
      <td><p><font size="+1">
<?php
   header( "refresh:4;url=AdminHome.php" );
   echo 'You\'ll be redirected to the administration homepage. If not, <a href="AdminHome.php">click here</a>.';
?>
      </font></p></td>
    </tr>
  </table>
<?php
}
include 'ccplus/footer.inc.html.php';
?>
