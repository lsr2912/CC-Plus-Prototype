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
// UpdateProvider.php
//
// Receives form-data as input to be saved in the provider table
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Set $_PROV to form-POST value
//
$_PROV = 0;
if ( isset($_POST['Prov']) ) { $_PROV = $_POST['Prov']; }

// Check rights; only proceed if role is Admin
// 
$ERR = 0;
if ( $_SESSION['role'] != ADMIN_ROLE ) { $ERR = 1; }

// Check input elements
//
if ( !isset($_POST['Pname']) || !isset($_POST['Pstat']) || !isset($_POST['Sushi_URL']) ||
     !isset($_POST['Sushi_Auth']) || !isset($_POST['Sushi_Day']) ) { $ERR = 2; }
if ( !isset($_POST['Save']) && !isset($_POST['ADD']) ) { $ERR = 2; }

// Authentication credentials only matter if provider is set and AuthType != 'None'
//
$security="None";
$sushi_user = "";
$sushi_pass = "";
if ( isset($_POST['Sushi_Auth']) ) {
  if ( $_POST['Sushi_Auth'] != "" ) { $security = $_POST['Sushi_Auth']; }
  if ( isset($_POST['Sushi_User']) ) { $sushi_user = $_POST['Sushi_User']; }
  if ( isset($_POST['Sushi_Pass']) ) { $sushi_pass = $_POST['Sushi_Pass']; }
}

// Set the type of query to build
//
if ( $_PROV == 0 ) { 
  $QueryType = "Create";
} else  {
  $QueryType = "Update";
}

// If no errors, proceed
//
if ($ERR == 0) {

  // setup database connection
  //
  global $ccp_adm_cnx;
  if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

  // For create new provider, build separate insert queries for the institution table,
  // and - if PROV is set - the sushi_settings table
  //
  if ( $QueryType == "Create" ) {

    // Put POST values in an array
    //
    $prov_args = array($_POST['Pname'], $_POST['Pstat'], $_POST['Sushi_URL'], $security,
                       $sushi_user, $sushi_pass, $_POST['Sushi_Day']);
    $_qry  = "INSERT INTO provider";
    $_qry .= " (name,active,server_url,security,auth_username,auth_password,day_of_month)";
    $_qry .= " VALUES (?,?,?,?,?,?,?)";

    // execute the institution table insert
    //
    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute($prov_args);
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    // Set $_PROV to just-inserted ID for the queries to follow
    //
    $_PROV = $ccp_adm_cnx->lastInsertId();
    if ( $_PROV == 0 ) {
      $ERR = 3;
    }

  // End-if Create

  // For an existing provider, build an update query for the provider table.
  // Delete whatever currently exists in the counter_xref table and fully
  // replace them via insert.
  //
  } else {

    $prov_args = array($_POST['Pname'], $_POST['Pstat'], $_POST['Sushi_URL'], $security,
                       $sushi_user, $sushi_pass, $_POST['Sushi_Day'], $_PROV);
    $_qry  = "UPDATE provider SET name=?, active=?, server_url=?, security=?, auth_username=?,
                                  auth_password=?, day_of_month=? WHERE prov_id=?";

    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute($prov_args);
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    // Prepare and execute a delete query to remove any existing xref records
    // for this provider from counter_xref
    //
    $D_qry  = "DELETE FROM counter_xref WHERE prov_id=?";

    // execute the delete 
    //
    try {
      $sth = $ccp_adm_cnx->prepare($D_qry);
      $sth->execute(array($_PROV));
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

  }	// End-if Update

  // Setup insert query and query argument array for counter_xref
  //
  $counterXref_args = array();
  $counterXref_qry = "INSERT INTO counter_xref (prov_id, report_xref) VALUES";
  $_count = 0;
  $_first = TRUE;
  if ( isset($_POST['reports_v4']) ) {
    if ( count($_POST['reports_v4'])>0 ) {
      foreach ( $_POST['reports_v4'] as $rept_id ) {
        array_push($counterXref_args,$_PROV);
        array_push($counterXref_args,$rept_id);
        if ( $_first ) {
           $counterXref_qry .= " ";
           $_first = FALSE;
        } else {
           $counterXref_qry .= ",";
        }
        $counterXref_qry .= "(?,?)";
        $_count++;
      }
    }
  }
  if ( isset($_POST['reports_v5']) ) {
    if ( count($_POST['reports_v5'])>0 ) {
      foreach ( $_POST['reports_v5'] as $rept_id ) {
        array_push($counterXref_args,$_PROV);
        array_push($counterXref_args,$rept_id);
        if ( $_first ) {
           $counterXref_qry .= " ";
           $_first = FALSE;
        } else {
           $counterXref_qry .= ",";
        }
        $counterXref_qry .= "(?,?)";
        $_count++;
      }
    }
  }
  if ( $_count > 0 ) {
    // execute the insert
    //
    try {
      $sth = $ccp_adm_cnx->prepare($counterXref_qry);
      $sth->execute($counterXref_args);
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }
  }

  ccp_close_db();

}  // End-if no errors

// If errors, signal and stop
//
if ($ERR > 0) {
   print_page_header("CC-Plus Provider Management - Error");
   print "<blockquote>\n";
   print "<p>&nbsp;</p>\n<h3>Error on form submission</h3>\n";
   print "<p><font size=\"+1\">Update failed because:</br />\n";
   switch ($ERR) {
     case 1:
       print "Your account is not authorized for such a request.";
       break;
     case 2:
       print "Invalid or missing arguments.";
       break;
     case 3:
       print "Invalid ID returned for new database entry!";
   }
   print "<br /><br />You can return to the <a href='ManageProvider.php'>Provider Management Page</a>,\n";
   print "<br />or the <a href='AdminHome.php'>Administration Home Page</a>\n";
   print "</font></p>\n";
   print "</blockquote>\n";

// Otherwise, print confirmation message
//
} else {
  print_page_header("CC-Plus Provider Management - Confirmation");
  $redir_url = "ManageProvider.php?Prov=" . $_PROV; 
  $_place = "the manage provider page";
  header( "refresh:4;url=" . $redir_url );
?>
 <table cellpadding="3" cellspacing="3" border="0">
    <tr><td width="100"></td>
      <td><h3>Requested updates successfully completed.</h3></td>
    </tr>
    <tr><td></td>
      <td>
        <p><font size="+1">
          <br />You will be returned to
<?php     print " <a href=\"" . $redir_url . "\">" . $_place . "</a> , or "; ?>
          <br />you can follow this link for the <a href="AdminHome.php">Administration homepage</a>.
        </font></p>
      </td>
    </tr>
  </table>
<?php
}
include 'ccplus/footer.inc.html.php';
?>
