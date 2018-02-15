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
// UpdateUser.php
//
// Receives form-data as input to be saved in the institution table
// AND: updates sushi_settings and institution_aliases if a provider is given
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Set $_INST to form-POST value
//
$_INST = 0;
if ( isset($_POST['INST']) ) { $_INST = $_POST['INST']; }

// Check rights; only proceed if role is Admin or manager modifying their own inst
// 
$ERR = 0;
if ( ($_SESSION['role'] != ADMIN_ROLE) ||
     ($_SESSION['role'] == MANAGER_ROLE) && ($_INST != $_SESSION['user_inst']) ) { $ERR = 1; }

if ( !isset($_POST['Iname']) || !isset($_POST['Istat']) || !isset($_POST['notes']) ||
     (!isset($_POST['Save']) && !isset($_POST['ADD'])) ) { $ERR = 2; }

// Provider may, or may not be given
//
$_PROV = 0;
if ( isset($_POST['Prov']) ) {

  $_PROV = $_POST['Prov'];

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
}

// Set the type of query to build
//
if ( $_INST == 0 ) { 
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

  // For create new inst, build separate insert queries for the institution table,
  // and - if PROV is set - the sushi_settings table
  //
  $_InstAdmin = 0;
  if ( isset($_POST['InstAdmin']) ) { $_InstAdmin = $_POST['InstAdmin']; }

  if ( $QueryType == "Create" ) {

    // Put POST values in an array
    //
    $inst_args = array($_POST['Iname'], $_POST['Istat'], $_InstAdmin, $_POST['notes']);
    $_qry  = "INSERT INTO institution";
    $_qry .= " (name,active,admin_userid,notes)";
    $_qry .= " VALUES (?,?,?,?)";

    // execute the institution table insert
    //
    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute($inst_args);
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    // Set $_INST to just-inserted ID for the queries to follow
    //
    $_INST = $ccp_adm_cnx->lastInsertId();
    if ( $_INST == 0 ) {
      $ERR = 3;
    }

    // Setup POST values for the sushi settings in an array
    //
    if ( $_PROV != 0 ) {
      $sushi_args = array($_INST, $_PROV, $_POST['Sushi_ReqID'], $_POST['Sushi_ReqName'],
                          $_POST['Sushi_ReqEmail'], $_POST['Sushi_CustID'], $_POST['Sushi_CustName']);
      $_qry  = "INSERT INTO sushi_settings";
      $_qry .= " (inst_id, prov_id, RequestorID, RequestorName, RequestorEmail, CustRefID, CustRefName)";
      $_qry .= " VALUES (?,?,?,?,?,?,?)";

      // execute the sushi_settings table insert
      //
      try {
        $sth = $ccp_adm_cnx->prepare($_qry);
        $sth->execute($sushi_args);
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }
    }

  // End-if Create

  // For an existing instition, build an update query for the institution table.
  // If "alias names" are included, delete whatever currently exists and fully
  // replace them via insert.
  //
  } else {

    $inst_args = array($_POST['Iname'], $_POST['Istat'], $_InstAdmin, $_POST['notes'], $_INST);

    $_qry  = "UPDATE institution SET name=?, active=?, admin_userid=?, notes=?";
    $_qry .= "WHERE inst_id=?";

    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute($inst_args);
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit();
    }

    // Handle sushi settings and aliases if a provider is set
    //
    if ( $_PROV != 0 ) {

      // Query sushi_settings for settings ID (to see if it exists or not)
      //
      $__ID = 0;
      $sel_qry  = "SELECT ID FROM sushi_settings WHERE inst_id=? AND prov_id=?";
      try {
        $sth = $ccp_adm_cnx->prepare($sel_qry);
        $sth->execute(array($_INST,$_PROV));
        $_result = $sth->fetchAll();
        foreach ( $_result as $row ) {
          $__ID = $row['ID'];
          $match = TRUE;
          break;
        }
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }
      
      //Setup the query for sushi_settings and put POST values in an array
      //
      if ( $__ID != 0 ) {
        $_qry  = "UPDATE sushi_settings SET RequestorID=?, RequestorName=?, RequestorEmail=?, CustRefID=?,";
        $_qry .= "  CustRefName=? WHERE ID=?";
        $sushi_args = array($_POST['Sushi_ReqID'], $_POST['Sushi_ReqName'], $_POST['Sushi_ReqEmail'],
                            $_POST['Sushi_CustID'], $_POST['Sushi_CustName'], $__ID);
      } else {
        $_qry  = "INSERT INTO sushi_settings";
        $_qry .= " (inst_id, prov_id, RequestorID, RequestorName, RequestorEmail, CustRefID, CustRefName)";
        $_qry .= " VALUES (?,?,?,?,?,?,?)";
        $sushi_args = array($_INST, $_PROV, $_POST['Sushi_ReqID'], $_POST['Sushi_ReqName'],
                            $_POST['Sushi_ReqEmail'],$_POST['Sushi_CustID'], $_POST['Sushi_CustName']);
      }

      // execute the sushi_settings table update
      //
      try {
        $sth = $ccp_adm_cnx->prepare($_qry);
        $sth->execute($sushi_args);
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }

      // Prepare and execute a delete query to remove any existing alias records for this inst
      //
      if ( isset($_POST['inst_alias']) ) {

        $D_qry  = "DELETE FROM institution_aliases WHERE inst_id=? AND prov_id=?";

        // execute the delete 
        //
        try {
          $sth = $ccp_adm_cnx->prepare($D_qry);
          $sth->execute(array($_INST, $_PROV));
        } catch (PDOException $e) {
          echo $e->getMessage();
          exit();
        }
      }
    }

  }	// End-if Update

  // Insert aliases if a provider given and aliases are set
  //
  if ( isset($_POST['inst_alias']) && $_PROV!=0 && $ERR==0 ) {

    // Build the institution_aliases query and the arguments array
    //
    $aliases_args = array();
    $aliases_qry = "INSERT INTO institution_aliases (inst_id,prov_id,alias) VALUES";
    $_first = TRUE;
    foreach ( $_POST['inst_alias'] as $_alias ) {
      array_push($aliases_args,$_INST);
      array_push($aliases_args,$_PROV);
      array_push($aliases_args,$_alias);
      if ( $_first ) {
         $aliases_qry .= " ";
         $_first = FALSE;
      } else {
         $aliases_qry .= ",";
      }
      $aliases_qry .= "(?,?,?)";
    }

    // execute the insert
    //
    try {
      $sth = $ccp_adm_cnx->prepare($aliases_qry);
      $sth->execute($aliases_args);
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
   print_page_header("CC-Plus Institution Management - Error");
   print "<blockquote>\n";
   print "<p>&nbsp;</p>\n<h3>Error on form submission</h3>\n";
   print "<p><font size=\"+1\">Update failed because:</br />\n";
   switch ($ERR) {
     case 1:
       print "Your account is not authorized for such a request.";
       break;
     case 2:
       print "Invalid or missing arguments.";
     case 3:
       print "Invalid ID returned for new database entry!";
   }
   print "<br /><br />You can return to the <a href='ManageInst.php'>Institution Management Page</a>,\n";
   print "<br />or the <a href='AdminHome.php'>Administration Home Page</a>\n";
   print "</font></p>\n";
   print "</blockquote>\n";

// Otherwise, print confirmation message
//
} else {
  print_page_header("CC-Plus Institution Management - Confirmation");
  $redir_url = "ManageInst.php?Inst=" . $_INST; 
  $_place = "the manage institution page";
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
