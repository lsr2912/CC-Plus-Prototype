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
// ImportSettings.php
//
// CC-Plus Settings Import Script
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

$ERR = 0;

// Check rights, set flag if user has management-site access
//
$Manager = FALSE;
if ( isset($_SESSION['role']) ) {
// if ( $_SESSION['role'] != ADMIN_ROLE ) { $ERR = 1; }
  if ( $_SESSION['role'] <= MANAGER_ROLE ) { $Manager = TRUE; }
}


// Check input POST arguments
//
$_OP = "";
$DEST = "";
if ( isset($_POST['ImpDest']) && isset($_POST['Itype']) && isset($_POST['Import']) &&
     isset($_FILES["Ifile"]) ) {
  $DEST = $_POST['ImpDest'];
  if ( $DEST!="User" && $DEST!="Prov" && $DEST!="Inst" && $DEST!="Name" ) { $ERR = 2; }
  $_OP = $_POST['Itype'];
  if ( $_OP != "Replace" && $_OP != "Add" ) { $ERR = 2; }
} else {  // if something is missing, set error
  $ERR = 2;
}

// Make sure file arrived without errors and has .csv extension
//
if ( $_FILES["Ifile"]["error"] == 0) {

  $inp_file = $_FILES["Ifile"]["name"];
  $inp_size = $_FILES["Ifile"]["size"];

  // Verify file extension
  $ext = pathinfo($inp_file, PATHINFO_EXTENSION);
  if ( $ext != "csv" ) { $ERR = 3; }

  // Verify file size - 5MB maximum
  $maxsize = 5 * 1024 * 1024;
  if ($inp_size > $maxsize) { $ERR = 4; }
} else {
  $ERR = 2;
}

if ( $ERR == 0 ) {

  // Set destination database table
  //
  switch ($DEST) {
    case "User":
      $db_table = "users";
      $_title = "User Settings";
      break;
    case "Prov":
      $db_table = "provider";
      $_title = "Provider Settings";
      break;
    case "Inst":
      $db_table = "institution";
      $_title = "Institution Settings";
      break;
    case "Name":
      $db_table = "institution_aliases";
      $_title = "Name Alias";
  }
 
  // Open database as admin
  //
  global $ccp_adm_cnx;
  if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

  // If doing a full-replace, clear out existing records
  //
  if ( $_OP == "Replace" ) {

    // Clear the table
    //
    $_qry = "DELETE FROM " . $db_table;
    try {
      $deleted_count = $ccp_adm_cnx->exec($_qry);
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit;
    }
 
    // If we're processing Inst settings, clear records from sushi_settings, too
    //
    if ( $DEST == "Inst" ) {
      $_qry = "DELETE FROM sushi_settings";
      try {
        $deleted_count = $ccp_adm_cnx->exec($_qry);
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
      }
    }

    // Reset table auto-increment to 1 
    //
    $_qry = "ALTER TABLE " . $db_table . " AUTO_INCREMENT=1";
    try {
      $res = $ccp_adm_cnx->exec($_qry);
    } catch (PDOException $e) {
      echo $e->getMessage();
      exit;
    }
  }

  //
  //
  print_page_header("CC-Plus " . $_title . " Import - Confirmation");
?>
<p align="center" width="90%">
<?php

  $ins_count = 0;
  $rec_count = 0;

  // Open the input file
  //
  $file = $_FILES['Ifile']['tmp_name'];
  $handle = fopen($file,"r");
   
  // Loop through the csv file and insert into database
  //
  switch ($DEST) {
    case "User":
      $_qry  = "INSERT INTO " . $db_table;
      $_qry .= " (user_id,active,inst_id,email,password,first_name,last_name,phone,role,optin_alerts,password_change_required)";
      $_qry .= " VALUES (?,?,?,?,?,?,?,?,?,?,?)";
      while ($data = fgetcsv($handle,0,",","'")) {
        if ( $data[0] == "" ) { continue; }
        $rec_count++;
        if ( !is_numeric($data[0]) || $data[0]<=0 ) {
          if ( strtoupper($data[0]) != "ID" ) {	// not-a-header?
            print "Record: " . $rec_count . " has invalid or missing ID in column 1; skipping<br />\n";
          }
        } else {
          $active = ($data[1]=="N") ? 0 : 1;
          $inst_id=0;
          if ( is_numeric($data[2]) ) { $inst_id = $data[2]; }
          $role = ccp_role_value($data[8]);
          $alerts = ($data[9]=="Y") ? 1 : 0;
          $changepw = ($data[10]=="N") ? 0 : 1;
          $_args = array($data[0],$active,$inst_id,$data[3],md5($data[4]),$data[5],$data[6],$data[7],$role,$alerts,$changepw);

          // execute the insert
          //
          try {
            $sth = $ccp_adm_cnx->prepare($_qry);
            $sth->execute($_args);
            $ins_count++;
          } catch (PDOException $e) {
            print "Error inserting record " . $rec_count . ": ". $e->getMessage() . "<br />\n";
          }
        }
      }
      break;
    case "Prov":
      $_qry  = "INSERT INTO " . $db_table;
      $_qry .= " (prov_id,name,active,server_url,security,auth_username,auth_password,day_of_month)";
      $_qry .= " VALUES (?,?,?,?,?,?,?,?)";
      while ($data = fgetcsv($handle,0,",","'")) {
        if ( $data[0] == "" ) { continue; }
        $rec_count++;
        if ( !is_numeric($data[0]) || $data[0]<=0 ) {
          if ( strtoupper($data[0]) != "ID" ) {	// not-a-header?
            print "Record: " . $rec_count . " has invalid or missing ID in column 1; skipping<br />\n";
          }
        } else {
          $active = ($data[2]=="N") ? 0 : 1;
          $security = "None";
          if ( $data[4]=="HTTP" || $data[4]=="WSSE" ) { $security = $data[4]; }
          $day = 15;
          if ( is_numeric($data[7]) ) {
            if ( $data[7] >0 && $data[7]<=28 ) { $day = $data[7]; }
          }
          $_args = array($data[0],$data[1],$active,$data[3],$security,$data[5],$data[6],$day);

          // execute the insert
          //
          try {
            $sth = $ccp_adm_cnx->prepare($_qry);
            $sth->execute($_args);
            $ins_count++;
          } catch (PDOException $e) {
            print "Error inserting record " . $rec_count . ": ". $e->getMessage() . "<br />\n";
          }
        }
      }
      break;
    case "Inst":
      // Build 2 insert queries: one for institution and one (conditionally)
      // for sushi_settings when prov_id is non-zero; start with institution.
      //
      $_i_qry  = "INSERT INTO " . $db_table . " (inst_id,name,active,admin_userid,notes)";
      $_i_qry .= " VALUES (?,?,?,?,?)";
      $_s_qry  = "INSERT INTO sushi_settings (inst_id,prov_id,RequestorID,RequestorName,RequestorEmail,CustRefID,CustRefName)";
      $_s_qry .= " VALUES (?,?,?,?,?,?,?)";
      $_last_inst_id = 0;
      while ($data = fgetcsv($handle,0,",","'")) {
        if ( $data[0] == "" ) { continue; }
        $rec_count++;
        if ( !is_numeric($data[0]) || $data[0]<=0 ) {
          if ( strtoupper($data[0]) != "ID" ) {	// not-a-header?
            print "Record: " . $rec_count . " has invalid or missing ID in column 1; skipping<br />\n";
          }
        } else {

          // We may have multiple records for a given institution (to allow N-provider sushi settings).
          // Only insert the FIRST one into the institution table.
          //
          if ( $data[0] != $_last_inst_id ) {
            $active = ($data[2]=="N") ? 0 : 1;
            $user_id=0;
            if ( is_numeric($data[3]) ) { $user_id = $data[3]; }
            $_i_args = array($data[0],$data[1],$active,$user_id,$data[4]);

            // execute the institution table insert
            //
            try {
              $sth = $ccp_adm_cnx->prepare($_i_qry);
              $sth->execute($_i_args);
              $_last_inst_id = $data[0];
            } catch (PDOException $e) {
              print "Error inserting record " . $rec_count . ": ". $e->getMessage() . "<br />\n";
              continue;
            }
          }

          // If prov_id is non-zero, handle sushi settings
          //
          if ( is_numeric($data[5]) && $data[5]>0 ) {

            // Run the insert query for sushi_settings
            //
            $_s_args = array($data[0],$data[5],$data[6],$data[7],$data[8],$data[9],$data[10]);

            // execute the institution table insert
            //
            try {
              $sth = $ccp_adm_cnx->prepare($_s_qry);
              $sth->execute($_s_args);
              $ins_count++;
            } catch (PDOException $e) {
              print "Error inserting record " . $rec_count . ": ". $e->getMessage() . "<br />\n";
              continue;
            }
          } else {	// if we got here and prov_id=0, then institution insert worked
            $ins_count++;
          }
        }
      }
      break;
    case "Name":
      $_qry = "INSERT INTO " . $db_table . " (ID,inst_id,prov_id,alias) VALUES (?,?,?,?)";
      while ($data = fgetcsv($handle,0,",","'")) {
        if ( $data[0] == "" ) { continue; }
        $rec_count++;
        if ( !is_numeric($data[0]) || $data[0]<=0 ) {
          if ( strtoupper($data[0]) != "ID" ) {	// not-a-header?
            print "Record: " . $rec_count . " has invalid or missing ID in column 1; skipping<br />\n";
          }
        } else {
          $active = ($data[1]=="N") ? 0 : 1;
          $inst_id=0;
          if ( is_numeric($data[1]) && $data[1]>0 ) { $inst_id = $data[1]; }
          $prov_id=0;
          if ( is_numeric($data[2]) && $data[2]>0 ) { $prov_id = $data[2]; }
          $alias = $data[3];
          if ( $inst_id==0 || $prov_id==0 || $alias=="") {
            print "Record: " . $rec_count . " has invalid or missing value(s) in columns 2-4 ; skipping<br />\n";
          } else {
            $_args = array($data[0],$inst_id,$prov_id,$alias);

            // execute the insert
            //
            try {
              $sth = $ccp_adm_cnx->prepare($_qry);
              $sth->execute($_args);
              $ins_count++;
            } catch (PDOException $e) {
              print "Error inserting record " . $rec_count . ": ". $e->getMessage() . "<br />\n";
            }
          }	
        }	// End-if ID numeric and > 0
      }		// End while input records
  }		// End switch
?>
</p>
<p align="center" width="90%"><strong>Import Completed</strong></p>
<p align="center" width="90%">
  <strong>Processed <?php echo $rec_count; ?> records, <?php echo $ins_count; ?> imported successfully.</strong>
</p>
<p align="center" width="90%">
<?php
  $_url = "ImExSettings.php?View=" . strtolower($DEST);
  print "<br />  You can return to the <a href='" . $_url . "'>Import-Export Page Here</a>.\n</p>\n";

// If errors, signal and stop
//
} else {
  print_page_header("CC-Plus " . $_title . " Import - Error");
  print "<blockquote>\n";
  print "<p>&nbsp;</p>\n<h3>Error on form submission</h3>\n";
  print "<p><font size=\"+1\">Update failed because:</br />\n";
  switch ($ERR) {
    case 1:
      print "Your account is not authorized for such a request.";
      break;
    case 2:
      print "Input arguments were missing or invalid.";
      break;
    case 3:
      print "Import file has unsupported type;  .csv  is expected.";
      break;
    case 4:
      print "Import file too large - size limit is currently 5Mb.";
  }
  $_url = "ImExSettings.php?View=" . strtolower($DEST);
  print "<br /><br />You can return to the <a href='" . $_url . "'>Import-Export Page</a>,\n";
  print "<br />or the <a href='AdminHome.php'>CC-Plus Administration Home Page</a>\n";
  print "</font></p>\n";
  print "</blockquote>\n";

}	// End-if Errors

// All done.
//
include 'ccplus/footer.inc.html.php';
?>
