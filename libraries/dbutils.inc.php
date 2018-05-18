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
// CC-Plus database access functions
//

require_once ('ccplus/helpers.inc.php');

// This function opens a Mysql PDO connection to a given database
// and returns the value of the connection-handle. It won't re-open
// or reset an existing connection by default.
// Returns PDO connection-id , or 0 if not found
//
if (!function_exists("ccp_open_db")) {
  function ccp_open_db( $db_name="", $access="User", $Force=0 ) {

    global $ccp_usr_cnx;
    global $ccp_adm_cnx;

    // Check whether a db connection is already open. If so, just return the handle
    // value of the global variable, based on $access.
    //
    if ( $access == "Admin" ) {
       if ( $ccp_adm_cnx && !$Force) { return $ccp_adm_cnx; }
    } else {
       if ( $ccp_usr_cnx && !$Force) { return $ccp_usr_cnx; }
    }

    // If $db_name is null, try to generate a default from the SESSION
    //
    if ( $db_name == "" ) {
      if ( !isset($_SESSION['ccp_con_key']) ) {
        print "Database name required for db_open\n";
        return 0;
      }
      $db_name = "ccplus_" . $_SESSION['ccp_con_key'];
    }

    // Setup mysql connection arguments
    //
    // $host = "localhost";
    $host = "127.0.0.1";
    $user = "not-defined";
    $pass = "db-name-isbad";
    if ( $access == "Admin" ) {
      $user = "conso_admin";
      $pass = "S3t@ndF1x";
    } else {
      $user = "conso_user";
      $pass = "D@rkShad3s";
    }

    // Create connection & return
    //
    try {
      $_cnx = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $user, $pass);
      $_cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $_cnx->exec("set names utf8");
    } catch (PDOException $e) {
      $output = 'Cannot connect to the database: ' . $e->getMessage();
      include 'ccplus/err.html.php';
      $_cnx = 0;
    }
    return $_cnx;
  }
}

// Function to close active database connection(s)
// Accepts a known named database, or "*" to close all
//
if (!function_exists("ccp_close_db")) {
  function ccp_close_db( $type="User" ) {

    global $ccp_usr_cnx;
    global $ccp_adm_cnx;

    if ( $type=="User"  || $type=="*" ) { $ccp_usr_cnx = null; }
    if ( $type=="Admin" || $type=="*" ) { $ccp_adm_cnx = null; }
    return;
  }
}

// Function to return ccplus_global:Consortium fields in an array
// Argument: con_id : limits the return set to a single consortium
//                    (the default is all-consortia, all fields)
// Returns : $cons : an array of associative arrays with consortia info
//      OR : $con  : an associative array with one consortium's info
//
if (!function_exists("ccp_get_consortia")) {
  function ccp_get_consortia($con_id=0) {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("ccplus_global", "Admin"); }

    // Setup the query
    //
    $_qry  = "SELECT * FROM Consortia";
    if ( $con_id > 0 ) { $_qry .= " WHERE ID=$con_id"; }
    $_qry .= " ORDER BY name ASC";

    // Execute query, prepare results
    //
    $con = "";
    $cons = array();
    try {
      $_result = $ccp_adm_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        if ( $con_id == 0 ) {
          array_push($cons,$row);
        } else {
          $con = $row;
        }
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return single row, or an array of rows
    //
    if ( $con_id > 0 ) {
      return $con;
    } else {
      return $cons;
    }
  }
}

// Function to return consortia ID and name pairs in an array
// $status_filter : limits the list based on status (ALL is default)
//
if (!function_exists("ccp_get_consortia_ui")) {
  function ccp_get_consortia_ui( $status_filter="ALL" ) {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("ccplus_global", "Admin"); }

    // Build query
    //
    if ( $status_filter == "ALL" ) {
       $_qry = "SELECT ID, name FROM Consortia";
    } else {
       $_qry = "SELECT ID, name FROM Consortia WHERE status='" . $status_filter . "'";
    }
    $_qry .= " ORDER BY name ASC";

    // Execute query, store results in $cons array
    //
    $cons = array();
    try {
      $_result = $ccp_adm_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($cons,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    //
    return $cons;
  }
}

// Function to change password for a given user_id
//
if (!function_exists("ccp_change_password")) {
  function ccp_change_password($user_id, $password) {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Setup and run the query
    //
    $enc_pass = md5($password);        // Encrypt the password string

    $sth = $ccp_adm_cnx->prepare("UPDATE users SET password=?, password_change_required=0 WHERE user_id=?");

    try {
      $sth->execute(array($enc_pass, $user_id));
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }
}

// Function to set password for a given email address
// to a random value and return new password
//
if (!function_exists("ccp_reset_password")) {
  function ccp_reset_password($con_key, $email) {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    $_db = "ccplus_" . $con_key;

    $ccp_adm_cnx = ccp_open_db($_db,"Admin");

    // Get a random word
    //
    $new_password = ccp_get_random_word();

    // Append a number between 0 and 999
    //
    srand ((double) microtime() * 1000000);
    $rand_number = rand(0, 999);
    $new_password .= $rand_number;

    // Update the record
    //
    $_qry = "UPDATE " . $_db . ".users SET password=?, password_change_required=? WHERE email=?";
    try {
      $sth = $ccp_adm_cnx->prepare($_qry);
      $sth->execute(array( md5($new_password), 1, $email ));
    } catch (PDOException $e) {
      $output = 'Cannot update the database: ' . $e->getMessage();
      include 'ccplus/err.html.php';
      exit;
    }

    //
    // Check to see if anything was changed
    //
    if ( $sth->rowCount() < 1 ) {
      $output = "No changes made!<br />Check the consortium and email address<br />";
      $output .= "The likely cause is that " . $email . " is not a registered address for<br />\n";
      $output .= "the chosen consortium.\n";
      include 'ccplus/err.html.php';
      exit;
    }
    return $new_password;
  }
}

// Function to return a random word (Mix of a-z, A-Z) of
// specified length (default=10)
//
if (!function_exists("ccp_get_random_word")) {
  function ccp_get_random_word($len = 10) {
    $word = array_merge(range('a', 'z'), range('A', 'Z'));
    shuffle($word);
    return substr(implode($word), 0, $len);
  }
}

// Function to notify user by email of a new password
//
if (!function_exists("ccp_notify_new_password")) {
  function ccp_notify_new_password($con_email, $email, $password) {

    $from = "From: " . $con_email . "\r\n";
    $mesg = "The CC-Plus password for this email address has been reset to $password \r\n"
            ."Please change it next time you log in. \r\n";
    if (mail($email, "login information", $mesg, $from)) {
      return TRUE;
    } else {
      return FALSE;
    }
  }
}

// Function to return all user fields in an array
// Argument: $_uid  : limits the return set to a single user
//                     (the default is all-users, all fields)
//           $_stat : limit based on status (default is all)
//           $_inst : limit based on institution (default is all)
//           $_role : limit based on role (default is all)
// Returns : $users : an array of associative arrays with info for multiple users
//      OR : $user  : an associative array with one user's info
//
if (!function_exists("ccp_get_users")) {
  function ccp_get_users($_uid=0, $_stat="ALL", $_inst="ALL", $_role="ALL") {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT UU.*,II.name as inst_name FROM users as UU";
    $_qry .= " LEFT JOIN institution as II ON UU.inst_id=II.inst_id";
    $_where = "";
    if ( $_uid > 0 ) { $_where .= "user_id=$_uid"; }
    if ( $_stat != "ALL" ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "UU.active=$_stat";
    }
    if ( $_inst != "ALL" ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "UU.inst_id=$_inst";
    }
    if ( $_role != "ALL" ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "UU.role=$_role";
    }
    if ( $_where != "" ) { $_qry .= " WHERE " . $_where; }
    $_qry .= " ORDER BY last_name,first_name ASC";

    // Execute query, prepare results
    //
    $user = "";
    $users = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        if ( $_uid == 0 ) {
           array_push($users,$row);
        } else {
           $user = $row;
        }
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return single row for a single user, or an array of rows
    // for all users found
    //
    if ( $_uid == 0 ) {
      return $users;
    } else {
      return $user;
    }
  }
}

// Function to return all user ID, names, and emails sets in an array
// for use in dropdowns/JS
// NOTE: users (like 'Administrator') with inst_id=0 are "Staff"!
//
// Argument: inst_id : (optional) limits the return set to a specific institution
//                     (defaults to all-users, all fields) ,
// Returns : $users : an array of associative user-id's and names
//
if (!function_exists("ccp_get_users_ui")) {
  function ccp_get_users_ui( $inst_id=-1 ) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup query
    //
    $_qry = "SELECT user_id,email,first_name,last_name FROM users";
    if ( $inst_id >= 0 ) { $_qry .= " WHERE inst_id=$inst_id"; }
    $_qry .= " ORDER BY last_name,first_name ASC";

    // Execute query, store results in $users array
    //
    $users = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($users,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    //
    return $users;
  }
}

// Function to return roles and ID's from the roles table
//
if (!function_exists("ccp_get_roles_ui")) {
  function ccp_get_roles_ui() {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Execute query, store results in $users array
    //
    $roles = array();
    try {
      $_result = $ccp_usr_cnx->query("SELECT * from roles ORDER BY role_id ASC");
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($roles,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    //
    return $roles;
  }
}

// Function to return inst_id+names from ingest_record table based on provider
//
if (!function_exists("ccp_report_insts_ui")) {
  function ccp_report_insts_ui($status='ALL', $prov_id=0) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT DISTINCT IR.inst_id,II.name FROM ingest_record AS IR LEFT JOIN institution AS II";
    $_qry .= " ON IR.inst_id=II.inst_id";
    $_where = "";
    if ($status != 'ALL') { $_where .= "status='$status'"; }
    $_where .= ( $_where == "" ) ? "" : " AND ";
    if ( $prov_id>0 ) { $_where .= "prov_id=$prov_id"; }
    if ( $_where != "" ) $_qry .= " WHERE " . $_where;
    $_qry .= " ORDER BY II.name ASC";

    // Execute query, store results in $users array
    //
    $insts = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($insts,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    //
    return $insts;
  }
}

// Function to return ingest date-times from ingest_record table
//
if (!function_exists("ccp_report_timestamps_ui")) {
  function ccp_report_timestamps_ui($prov_id=0, $inst_id=0) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT DISTINCT yearmon FROM ingest_record";
    $_where = " WHERE status='Saved'";
    if ( $prov_id>0 ) { $_where .= " AND prov_id=$prov_id"; }
    if ( $inst_id>0 ) { $_where .= " AND inst_id=$inst_id"; }
    $_qry .= $_where . " ORDER BY yearmon ASC";

    // Execute query, store results in $stamps array
    //
    $stamps = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($stamps,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    //
    return $stamps;
  }
}

// Function to confirm existence of a statistics table
// and, if not, to create one from a template table.
//
if (!function_exists("ccp_confirm_statstable")) {
  function ccp_confirm_statstable( $table ) {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Setup the query
    //
    // $table = $year . "_vendor_stats";
    $_base = "ccplus_" . $_SESSION['ccp_con_key'];
    $_qry  = "SELECT count(*) as total FROM information_schema.TABLES WHERE";
    $_qry .= " (TABLE_SCHEMA = '$_base') AND (TABLE_NAME = '$table')";

    // Execute query and get the count
    //
    $count = 0;
    try {
      $_result = $ccp_adm_cnx->query($_qry);
      $row = $_result->fetch(PDO::FETCH_ASSOC);
      $count = $row['total'];
    } catch (PDOException $e) {
      echo $e->getMessage();
      return FALSE;
    }

    // If count=0, create the table as a copy of the template
    //
    if ( $count == 0 ) {

      $_qry2  = "CREATE TABLE $table LIKE vendor_stats_template";
      try {
        $_result = $ccp_adm_cnx->query($_qry2);
      } catch (PDOException $e) {
        echo $e->getMessage();
        return FALSE;
      }
    }
   
    // If we get this far, return TRUE
    // 
    return TRUE;
  }
}

// Function to return institutional aliases
// Arguments:
//   $_inst : limit the return-set based on institution ID (default:all)
//   $_prov : limit the return-set based on provider ID (default:all)
//   $_i_stat : limit the return-set based on institution status (default:all)
//   $_p_stat : limit the return-set based on provider status (default:all)
//
if (!function_exists("ccp_get_aliases")) {
  function ccp_get_aliases( $_inst=0, $_prov=0, $_istat="ALL", $_pstat="ALL" ) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT IA.* FROM institution_aliases AS IA";
    $_where = "";
    if ($_inst>0) { $_where .= "inst_id='$_inst'"; }
    if ($_prov>0) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "prov_id=$_prov";
    }
    if ($_istat != "ALL") {
      $_qry .= " LEFT JOIN institution as Inst ON IA.inst_id=Inst.inst_id";
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Inst.active=$_istat";
    }
    if ($_pstat != "ALL") {
      $_qry .= " LEFT JOIN provider as Prov ON IA.prov_id=Prov.prov_id";
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Prov.active=$_pstat";
    }
    if ( $_where != "" ) $_qry .= " WHERE " . $_where;

    // Execute query, prepare results
    //
    $aliases=array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($aliases,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the settings
    //
    return $aliases;
  }
}

// Function to return alert-settings
// Argument: $status : limit the return-set based on status (default is ALL)
//
if (!function_exists("ccp_get_alert_settings")) {
  function ccp_get_alert_settings( $_stat=0 ) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT ALS.*, Met.col_xref, Met.legend, Rpt.Report_Name, Rpt.revision";
    $_qry .= " FROM alert_settings AS ALS";
    $_qry .= " LEFT JOIN ccplus_global.Metrics AS Met ON ALS.metric_xref=Met.ID";
    $_qry .= " LEFT JOIN ccplus_global.Reports AS Rpt ON Met.rept_id=Rpt.ID";
    if ( $_stat != "ALL" ) { $_qry .= " WHERE status=$_stat"; }

    // Execute query, prepare results
    //
    $alerts=array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $alerts[$row['ID']] = $row;
        // array_push($alerts,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the rows
    //
    return $alerts;
  }
}

// Function to return alerts as an array
//
// Arguments:
//   $_stat : limit the return-set based on alert status (default:Active)
//            Can also be "ALL" for no limiter
//   $_prov : limit the return-set based on provider ID (default:all)
//   $_inst : limit the return-set based on institution ID (default:all)
//   $_rept : limit the return-set based on report-name (default:all)
//   $_from : Start of date range as YYYY-MM (0=last-month)
//   $_to   : End of date range as YYYY-MM (0=last-month)
//
if (!function_exists("ccp_get_alerts")) {
  function ccp_get_alerts( $_stat="Active", $_prov=0, $_inst=0, $_rept="", $_from="", $_to="" ) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT Al.*, Met.legend, Pr.name AS prov_name,";
    $_qry .= "CONCAT(Usr.first_name, ' ', Usr.last_name) AS user_name, FI.detail,";
    $_qry .= "(CASE Al.failed_id WHEN 0 THEN Rpt.Report_Name ELSE FI.report_name END) as Report_Name,";
    $_qry .= " DATE_FORMAT(Al.time_stamp,'%Y-%m-%d') as ts_date FROM alerts AS Al";
    $_qry .= " LEFT JOIN alert_settings AS Ast ON Al.settings_id=Ast.ID";
    $_qry .= " LEFT JOIN ccplus_global.Metrics AS Met ON Ast.metric_xref=Met.ID";
    $_qry .= " LEFT JOIN ccplus_global.Reports AS Rpt ON Met.rept_id=Rpt.ID";
    $_qry .= " LEFT JOIN provider AS Pr ON Al.prov_id=Pr.prov_id";
    $_qry .= " LEFT JOIN users AS Usr ON Al.modified_by=Usr.user_id";
    $_qry .= " LEFT JOIN failed_ingest as FI ON Al.failed_id=FI.ID";

    // Setup where clause
    //
    $_where = "";
    if ( $_stat!="ALL" ) { $_where .= "Al.status='" . $_stat . "'"; }
    if ( $_prov!=0 ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Al.prov_id=" . $_prov;
    }
    if ( $_inst!=0 ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Al.inst_id=" . $_inst;
    }
    if ( $_rept!="" ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Rpt.Report_Name='" . $_rept . "'";
    }
    if ($_from!="" && $_to!="") {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "STR_TO_DATE(Al.yearmon,'%Y-%m') BETWEEN ";
      $_where .= "STR_TO_DATE('" . $_from . "','%Y-%m') AND ";
      $_where .= "STR_TO_DATE('" . $_to . "','%Y-%m')";
    }

    // Execute query, prepare results
    //
    $_qry .= " WHERE Al.inst_id=0";
    if ( $_where != "" ) { $_qry .= " OR (" . $_where . ")"; }

    $alerts=array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($alerts,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the rows
    return $alerts;
  }
}

// Function to return an array of COUNTER report names
// Argument: $prov_id  : limit list to a given provider
//           $_rev     : limit list to a specific COUNTER release version 
// Returns : $reports : an array of matching counter report rows
//                      (id,name,release)
//
if (!function_exists("ccp_get_counter_reports")) {
  function ccp_get_counter_reports($prov_id=0, $_rev=0) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    // If prov=0 , returns everything in the table
    //
    $_qry = "SELECT Rpt.*,'on' as selected FROM ccplus_global.Reports as Rpt
                LEFT JOIN counter_xref as Cx ON Rpt.ID=Cx.report_xref
                LEFT JOIN provider as Prov ON Prov.prov_id=Cx.prov_id";

    // If vend_id set, limit on vend_id
    //
    $_where = "";
    if ($prov_id > 0 ) { $_where = "Cx.prov_id=$prov_id"; }
    if ($_rev != 0 ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "Rpt.revision=$_rev";
    }
    if ( $_where != "" ) { $_qry .= " WHERE " . $_where; }
    $_qry .= " ORDER BY Rpt.Report_Name ASC";

    // Execute query, store results in $reports array
    //
    $reports = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($reports,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    return $reports;
  }
}

// Function to return a list of COUNTER reports for UI
// Arguments: $prov_id : filter by provider
//            $_rev    : filter by COUNTER version
// Returns : $reports  : an array of report ID/name pairs
//
if (!function_exists("ccp_get_counter_reports_ui")) {
  function ccp_get_counter_reports_ui($prov_id=0, $_rev="") {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Build the database query
    //
    $_qry = "SELECT CR.* FROM ccplus_global.Reports AS CR";
    $_where = "";
    if ( $prov_id != 0 ) {
      $_qry .= " LEFT JOIN counter_xref AS CX ON CR.ID=CX.report_xref";
      $_qry .= " CX.prov_id=$prov_id AND";
    }
    if ( $_rev != "" ) {
      $_where .= " AND " ? ($_where != "") : "";
      $_where .= " revision='$_rev'";
    }
    $_qry .= " ORDER BY Report_Name,revision ASC";

    // Execute the query
    //
    $reports = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($reports, $row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
    return $reports;
  }
}

// Function to return a list of INGESTED ID+report_name pairs for the UI
// Arguments: $prov_id : filter based on provider
//            $inst_id : filter based on institution
//            $yearmon : filter based on timestamp
//   Returns: $reports : an array containing matching report ID+names
//
if (!function_exists("ccp_get_reports_ui")) {
  function ccp_get_reports_ui($prov_id=0, $inst_id=0, $yearmon="") {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT DISTINCT Ing.ID,Rep.Report_Name,Rep.revision FROM ingest_record AS Ing";
    $_qry .= " LEFT JOIN ccplus_global.Reports AS Rep ON Ing.report_xref=Rep.ID";
    $_where = " WHERE status='Saved'";
    if ($yearmon!="") { $_where .= " AND yearmon='$yearmon'"; }
    if ( $prov_id>0 ) { $_where .= " AND prov_id=$prov_id"; }
    if ( $inst_id>0 ) { $_where .= " AND inst_id=$inst_id"; }
    $_qry .= $_where . " ORDER BY Rep.Report_Name ASC";

    // Execute query, prepare results
    //
    $reports = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($reports,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the result
    //
    return $reports;
  }
}

// Function to return a list of COUNTER reports
// Arguments: $report_id   : filter on a single report ID (if non-zero, other args ignored!)
//            $report_name : filter based on a report_name
//            $report_rev  : filter based on a COUNTER revision (e.g. 4, 5)
// Returns  : $reports : an array containing all reports in the database 
//    OR    : $report  : an array with a single report row (when report_id!=0)
//
if (!function_exists("ccp_get_reports")) {
  function ccp_get_reports($report_id=0, $report_name="", $report_rev=0) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT * FROM ccplus_global.Reports";
    $_where = " ";
    if ( $report_id > 0 ) {
      $_where .= "WHERE ID=$report_id";
    } else {
      if ( $report_name != "" ) {
        $_where .= "WHERE Report_Name='$report_name'";
      }
      if ( $report_rev != 0 ) {
        $_where .= ( $_where == "" ) ? "WHERE " : " AND ";
        $_where .= "revision=$report_rev";
      }
    }
    if ( $_where != " " ) { $_qry .= $_where; }

    // Execute query, prepare results
    //
    $report = array();
    $reports = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        if ( $report_id == 0 ) {
           array_push($reports,$row);
        } else {
           $report = $row;
        }
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the result
    //
    if ( $report_id == 0) {
       return $reports;
    } else {
       return $report;
    }

  }
}

// Function to return information about a specific journal. If a match
// for the arguments is not found and $title argument is non-null, the
// Journal is ADDED to the database table and the new ID is returned.
//
// NOTE: Arguments are OR'd , so only the first match gets returned if
//       multiple titles have identical title/issn/e-issn
//
// Arguments: $title : Journal title to lookup
//            $issn  : Print ISSN to lookup 
//            $eissn : Online ISSN to lookup 
// Returns : $journal : array of looked-up/created info for the journal
//
if (!function_exists("ccp_find_journal")) {
  function ccp_find_journal($title="", $issn="", $eissn="") {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("ccplus_global", "Admin"); }

    // Setup return array - default to ID=0 and whatever was input
    //
    $journal = array();
    $journal['ID'] = 0;
    $journal['Title'] = $title;
    $journal['ISSN']  = $issn;
    $journal['eISSN'] = $eissn;
    if ( $title=="" && $issn=="" && $eissn=="" ) { return $journal; }

    // Build query
    //
    $_args = array();
    $_qry = "SELECT * FROM ccplus_global.Journal";
    $where = " WHERE ";
    if ( $title != "") { 
      $where .= "Title=? OR ";
      $_args[] = $title;
    }
    if ( $issn != "") {
      $where .= "ISSN=? OR ";
      $_args[] = $issn;
    }
    if ( $eissn != "") {
      $where .= "eISSN=?";
      $_args[] = $eissn;
    }
    $where = preg_replace('/ OR $/', '', $where);
    $_qry .= $where;

    // Prepare and run query
    //
    $sth = $ccp_adm_cnx->prepare($_qry);
    $match = FALSE;
    try {
      $sth->execute($_args);
      $_result = $sth->fetchAll();
      foreach ( $_result as $row ) {
        $journal = $row;
        $match = TRUE;
        break;
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // If no match and there's a title, add it
    //
    if ( !$match && $title!="" ) {
      $_data = array($title,$issn,$eissn);
      $update_qry = "INSERT INTO ccplus_global.Journal (Title,ISSN,eISSN) VALUES (?,?,?)";

      // Insert the record
      //
      try {
        $sth = $ccp_adm_cnx->prepare($update_qry);
        $sth->execute($_data);

        // Get the new ID
        //
        $journal['ID'] = $ccp_adm_cnx->lastInsertId();

      } catch (PDOException $e) {
        // echo $e->getMessage();  //-hush...
      }
    }

    // Return the array
    //
    return $journal;
  }
}

// Function to return information about a Platform. If a match
// for the arguments is not found and the $name argument is non-null,
// a new entry is ADDED to the global Platform database table and
// the new ID is returned.
//
// Arguments: $name : Platfom name to lookup
//            $_ID  : Platfom ID to lookup 
// Returns : $platform : array of looked-up/created info
//
if (!function_exists("ccp_find_platform")) {
  function ccp_find_platform($name="", $_ID=0) {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("ccplus_global", "Admin"); }

    // Setup return array - default to ID=0 and whatever was input
    //
    $platform = array();
    $platform['ID'] = 0;
    $platform['name'] = $name;

    // Build query
    //
    $_args = array();
    $_qry = "SELECT * FROM ccplus_global.Platform";
    $where = " WHERE ";
    if ( $name != "") { 
      $where .= "Name=? OR ";
      $_args[] = $name;
    }
    if ( $_ID != 0) {
      $where .= "ID=?";
      $_args[] = $_ID;
    }
    $where = preg_replace('/ OR $/', '', $where);
    $_qry .= $where;

    // Prepare and run query
    //
    $sth = $ccp_adm_cnx->prepare($_qry);
    $match = FALSE;
    try {
      $sth->execute($_args);
      $_result = $sth->fetchAll();
      foreach ( $_result as $row ) {
        $platform = $row;
        $match = TRUE;
        break;
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // If no match and there's a name, add it
    //
    if ( !$match && $name!="" ) {
      $_data = array($name);
      $update_qry = "INSERT INTO ccplus_global.Platform (name) VALUES (?)";

      // Insert the record
      //
      try {
        $sth = $ccp_adm_cnx->prepare($update_qry);
        $sth->execute($_data);

        // Get the new ID
        //
        $platform['ID'] = $ccp_adm_cnx->lastInsertId();

      } catch (PDOException $e) {
        // echo $e->getMessage();  //-hush...
      }
    }

    // Return the array
    //
    return $platform;
  }
}

// Function to retrieve stats compliance. 
// Either argument may be zero. The "inherited compliance" from 
// a resource's vendor-setting will be returned if the resource
// is set to "None" and the vendor is not "None".
// Arguments: $vend_id : vendor to test
//            $rsrc_id : resource to test 
// Returns : $compliance : string
//
if (!function_exists("ccp_stats_compliance")) {
  function ccp_stats_compliance($vend_id=0, $rsrc_id=0) {

    // Bail if both arguments are zero
    if ( $vend_id==0 && $rsrc_id==0 ) { return; }
    
    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    if ( $rsrc_id == 0 ) { 
      $_qry  = "SELECT stats_compliance AS compliance FROM vendors WHERE vend_id=$vend_id";
    } else {
      $_qry  = "SELECT IF ((Rsc.stats_compliance='None' OR Rsc.stats_compliance is NULL),";
      $_qry .= " Vnd.stats_compliance, Rsc.stats_compliance) AS compliance";
      $_qry .= " FROM resources as Rsc LEFT JOIN vendors AS Vnd ON Rsc.vend_id=Vnd.vend_id";
      $_qry .= " WHERE Rsc.resource_id=$rsrc_id";
    }

    // Execute query, prepare results
    //
    $compliance="";
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $compliance = $row['compliance'];
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the result
    //
    if ( $compliance == "") { $compliance = "None"; }
    return $compliance;

  }
}

// Function to retrieve stats-metrics ID/string pairs for UI 
// Argument: $_rept   : filter on a specific report
// Returns : $metrics : an array of metric ID/string pairs
//
if (!function_exists("ccp_get_metrics_ui")) {
  function ccp_get_metrics_ui($_rept=0) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the query
    //
    $_qry  = "SELECT ID,legend FROM ccplus_global.Metrics";
    if ( $_rept != 0 ) { $_qry .= " WHERE rept_id=$_rept"; }

    // Execute query, prepare results
    //
    $metrics = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($metrics,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the results
    //
    return $metrics;
  }
}

// Function to return institution ID and name pairs in an array.
// $_stat : limits the list based on status (ALL is default)
//
if (!function_exists("ccp_get_institutions_ui")) {
  function ccp_get_institutions_ui( $_stat="ALL" ) {

    // Setup database connections
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(""); }

    // Build query, setup receiving array
    //
    $_qry = "SELECT inst_id, name FROM institution";
    if ( $_stat != "ALL" ) { $_qry .= " WHERE active='" . $_stat . "'"; }
    $_qry .= " ORDER BY name ASC";
    $institutions = array();

    // Execute query
    //
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($institutions,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    //
    return $institutions;
  }
}

// Function to return institution data in an array.
//   $_inst : limits the result to a single institution (default=0 : ALL)
//   $_stat : limits the list based on status (ALL is default)
// Returns  : $insts : an array of associative arrays with institution info
//      OR  : $inst  : an associative array with one institution's info
//
if (!function_exists("ccp_get_institutions")) {
  function ccp_get_institutions( $_inst=0, $_stat="ALL" ) {

    // Setup database connections
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(""); }

    // Build query, setup receiving array
    //
    $_qry = "SELECT * FROM institution";
    $_where = "";
    if ( $_stat != "ALL" ) { $_where .= "active=" . $_stat; }
    if ( $_inst != 0 ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= " inst_id=" . $_inst;
    }
    if ( $_where != "" ) { $_qry .= " WHERE " . $_where; }

    // Execute query
    //
    $inst  = "";
    $insts = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        if ( $_inst == 0 ) {
           array_push($insts,$row);
        } else {
           $inst = $row;
        }
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return single row for one inst, or an array of rows
    // for all institutions found
    //
    if ( $_inst > 0 ) {
      return $inst;
    } else {
      return $insts;
    }
  }
}

// Function to return institution and sushi settings in an array.
//   $_stat : limits the list based on status (default is ALL)
//   $_prov : limits the result to a single institution (default is ALL)
// Returns  : $insts : an array of associative arrays with institution info
//
if (!function_exists("ccp_get_institution_settings")) {
  function ccp_get_institution_settings( $_stat="ALL", $_prov="ALL" ) {

    // Setup database connections
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(""); }

    // Build query, setup receiving array
    //
    $_qry = "SELECT *,Ins.inst_id as ID FROM institution AS Ins LEFT OUTER JOIN sushi_settings AS SuS";
    $_qry .= " ON Ins.inst_id=SuS.inst_id";
    $_where = "";
    if ( $_stat != "ALL" ) { $_where .= "active=" . $_stat; }
    if ( $_prov != "ALL" ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "prov_id=" . $_prov;
    }
    if ( $_where != "" ) { $_qry .= " WHERE " . $_where; }
   
    // Execute query
    //
    $insts = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($insts,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    //
    return $insts;
  }
}

// Function to return provider ID and name pairs in an array.
// $status_filter : limits the list based on status (ALL is default)
//
if (!function_exists("ccp_get_providers_ui")) {
  function ccp_get_providers_ui( $_stat="ALL" ) {

    // Setup database connections
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(""); }

    // Build query, setup receiving array
    //
    $_qry = "SELECT prov_id, name FROM provider";
    if ( $_stat != "ALL" ) { $_qry .= " WHERE active='" . $_stat . "'"; }
    $_qry .= " ORDER BY name ASC";
    $providers = array();

    // Execute query
    //
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        array_push($providers,$row);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the array
    //
    return $providers;
  }
}

// Function to return provider information.
// Arguments: $_prov : limits the return set to a single provider
//                     (the default is all providers, all fields)
//            $_stat : (0=Active, 1=Inactive, default=ALL)
// Returns : $providers : an array of associative arrays with provider info
//      OR : $provider  : an associative array with one provider's info
//
if (!function_exists("ccp_get_providers")) {
  function ccp_get_providers ($_prov=0, $_stat="ALL") {

    // Setup database connections
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(""); }

    // Build query, setup receiving array
    //
    $_qry = "SELECT * FROM provider";
    $_where = "";
    if ( $_stat!="ALL" ) { $_where .= "active='" . $_stat . "'"; }
    if ( $_prov!=0 ) {
      $_where .= ( $_where == "" ) ? "" : " AND ";
      $_where .= "prov_id=" . $_prov;
    }
    if ( $_where != "" ) { $_qry .= " WHERE " . $_where; }

    // Execute query
    //
    $provider  = "";
    $providers = array();
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        if ( $_prov == 0 ) {
          array_push($providers,$row);
        } else {
          $provider = $row;
        }
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Return the info 
    //
    if ( $_prov > 0 ) {
      return $provider;
    } else {
      return $providers;
    }
  }
}

// Function to return a fiscal YR ID when given a string
//  Argument : $FY_string : 
//  Returns  : integer : $ID
//    OR     : 0 if $FY_string format is invalid 
// If $FY_string is not in the table (and is valid),
// a record is added to the table and the new ID
// is returned.
//
if (!function_exists("ccp_get_FY_ID")) {
  function ccp_get_FY_ID( $FY_string ) {

    $retval = 0;

    // Clean up and verify the input string
    //
    // Code goes here...
    $FY_string = preg_replace('/\s/', '', $FY_string);	// kill all spaces in the string
    $years = preg_split('/-/', $FY_string);
    if ( ($years[0]+1) != $years[1] ) { return 0; }

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Define and perform the SQL SELECT query
    //
    $_qry = "SELECT ID FROM fiscal_years WHERE fiscal_year='" . $FY_string . "'";
    foreach ($ccp_adm_cnx->query($_qry) as $row) {
      $retval = $row['ID'];
    }

    // If not found, add it
    //
    if ( $retval == 0 ) {
      $_qry  = "INSERT INTO fiscal_years (fiscal_year) VALUES (?)";
      $_arg = array($FY_string);

      // execute the contract table insert
      //
      try {
        $sth = $ccp_adm_cnx->prepare($_qry);
        $sth->execute($_arg);
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }

      // Set return value to newly-inserted ID
      //
      $retval = $ccp_adm_cnx->lastInsertId();
    }

    // Return the result
    //
    return $retval;
  }
}

// Function to return a fiscal YR string when given an ID
// Argument : $FYid : 
// Returns  : $fiscal_year_string as YYYY-YYYY
//   OR     : "" if no match
//
if (!function_exists("ccp_get_FY_string")) {
  function ccp_get_FY_string( $FYid ) {

    $retval = "";

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Define and perform the SQL SELECT query
    //
    $_qry = "SELECT fiscal_year FROM fiscal_years WHERE ID=$FYid";
    foreach ($ccp_usr_cnx->query($_qry) as $row) {
      $retval = $row['fiscal_year'];
    }

    // Return the result
    //
    return $retval;
  }
}

// Function to return the possible enum values for a
// given table and column
//
if (!function_exists("ccp_get_enum_values")) {
  function ccp_get_enum_values( $table, $field ) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    try {
      $_result = $ccp_usr_cnx->query("SHOW COLUMNS FROM $table WHERE Field = '$field'");
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
        $enum = explode("','", $matches[1]);
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
    return $enum;
  }
}

// Function to return the alert_types as key=>value pairs
// where alert_type:ID is the key and alert_type:alert is the value
//
if (!function_exists("ccp_get_alert_types")) {
  function ccp_get_alert_types() {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Query database, store rows returned in $types
    //
    $types = array();
    try {
      $_result = $ccp_usr_cnx->query("SELECT * from alert_types");
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $types[$row['ID']] = $row['alert'];
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
    return $types;
  }
}

// Function to return the alerts set for a given user_id
//
if (!function_exists("ccp_get_user_alerts")) {
  function ccp_get_user_alerts($user_id) {

    // Setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Setup the return values array as FALSE for all alert types
    //
    $alerts = array();
    $alert_types = ccp_get_alert_types();
    foreach ( $alert_types as $key => $value ) {
      $alerts[$value] = FALSE; 
    }

    // Query database for users' alert settings
    //
    $_qry = "SELECT alert from user_alert_xref LEFT JOIN alert_types " .
                "ON user_alert_xref.alert_type=alert_types.ID " .
             "WHERE user_id=$user_id";
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $alerts[$row['alert']] = TRUE;
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
    return $alerts;
  }
}

// Function to set the alerts a given user_id
// Arguments:
//   $user_id - the ID to be changed/added
//   $alerts - array of INDEX VALUES specifying which should be "on"
//             (if empty, all will be turned off)
//
if (!function_exists("ccp_set_user_alerts")) {
  function ccp_set_user_alerts($user_id, $alerts) {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Delete anything already in the table
    //
    try {
      $_count = $ccp_adm_cnx->exec("DELETE FROM user_alert_xref WHERE user_id=$user_id");
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Insert new entries based on $alerts argument
    //
    $_qry = "INSERT INTO user_alert_xref (user_id,alert_type) VALUES (?,?)";
    $sth = $ccp_adm_cnx->prepare($_qry);
    foreach ( $alerts as $key => $value ) {
      try {
        $sth->execute(array($user_id, $value));
      } catch (PDOException $e) {
        echo $e->getMessage();
      }

    }
  }
}

// Function to return a record count for a given table and where clause
//
// Arguments:
//   $table : the table to be queried
//   $where : where clause to be applied
//
// Returns : count as an integer
//
if (!function_exists("ccp_count_records")) {
  function ccp_count_records($table, $where="") {

    // setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // build the query
    //
    $count = 0;
    $_qry = "SELECT count(*) FROM " . $table;
    if ( $where != "" ) { $_qry .= " WHERE " . $where; }

    // execute query
    //
    $count = $ccp_usr_cnx->query($_qry)->fetchColumn();

    return $count;
  }
}

// Function to return fiscal year ID's and strings implicated by a range of dates
// Operates on a July-1 through June-30 fiscal year boundary.
//
// Arguments:
//   $start_date : start of date range as a string
//   $end_date   : end of date range as a string
// Returns:
//   $years : an array of ID+name pairs for each fiscal year the date range "touches"
//
if (!function_exists("ccp_FY_byrange")) {
  function ccp_FY_byrange($start_date, $end_date) {

    $years = array();

    // Parse input dates
    //
    $_syr = date("Y", strtotime($start_date) );
    $_smo = date("n", strtotime($start_date) );
    $_eyr = date("Y", strtotime($end_date) );
    $_emo = date("n", strtotime($end_date) );
    if ( ($_syr > $_eyr) || ( ($_syr==$_eyr) && ($_smo>$_emo) ) ) { return $years; }

    // setup database connection
    //
    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

    // Build a list of strings for query where clause
    //
    $_st = "";
    $strings = array();

    // Handle first year
    //
    if ( $_smo < 7 ) {
      $_st = $_syr-1 . "%";
      array_push($strings,$_st);
    }

    // Handle years between first and last
    //
    for ( $_yr=$_syr; $_yr<=$_eyr-1; $_yr++) {
      $_st = $_yr . "%";
      if ( !in_array($_st, $strings) ) { array_push($strings,$_st); }
    }

    // Handle last year
    //
    if ( $_emo > 6 ) { $_st = $_eyr . "%"; }
    if ( !in_array($_st, $strings) ) { array_push($strings,$_st); }

    // Turn the list of strings into an actual where clause
    //
    $where = " WHERE ";
    foreach ( $strings as $_yy ) {
      $where .= "(fiscal_year LIKE '" . $_yy . "') OR ";
    }
    $where = preg_replace('/ OR $/', '', $where);

    // Build and run the query
    //
    $_qry = "SELECT * FROM fiscal_years" . $where . " ORDER BY fiscal_year DESC";
    try {
      $_result = $ccp_usr_cnx->query($_qry);
      while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
        $years[$row['ID']] = $row['fiscal_year'];
      }
    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    return $years;
  }
}

// Function to insert alert table records. Inserts only happen if the alert
// is not yet set.
//
// Arguments:
//   $_stat : 'Active', 'Silent', or 'Pending'
//   $_prov : Provider ID (required)
//   $_alrt : Alert Settings ID to signal (xRef to ::alert_settings:ID)
//   $_fail : Failed ingest setting ID (xRef to ::sushi_settings:ID) , default=0
//
// Returns:
//   $success  : 0=no , 1=yes
//
if (!function_exists("ccp_set_alert")) {
  function ccp_set_alert($_stat, $_yearmon, $_prov, $_alrt=0, $_fail=0) {

    // Setup database connection
    //
    global $ccp_adm_cnx;
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

    // Check arguments; provider required and one of either $_alrt or $_fail
    //
    if ( ( $_prov==0 ) || ( $_alrt==0 && $_fail==0 ) ) { return 0; }

    // Setup the query to check if alert already set
    //
    $_qry  = "SELECT count(*) as seen FROM alerts WHERE";
    $_qry .= " prov_id=" . $_prov;
    if ( $_alrt != 0 ) { $_qry .= " AND settings_id=" . $_alrt; }
    if ( $_fail != 0 ) { $_qry .= " AND failed_id=" . $_fail; }

    // Execute query and get the count
    //
    $count = 0;
    try {
      $_result = $ccp_adm_cnx->query($_qry);
      $row = $_result->fetch(PDO::FETCH_ASSOC);
      $count = $row['seen'];
    } catch (PDOException $e) {
      echo $e->getMessage();
      return 0;
    }

    // If not there, build the Insert query 
    //
    if ( $count == 0 ) {

      $_qry  = "INSERT INTO alerts";
      $_qry .= " (yearmon,settings_id,failed_id,status,prov_id)";
      $_qry .= " VALUES (?,?,?,?,?)";

      // Insert the record
      //
      try {
        $sth = $ccp_adm_cnx->prepare($_qry);
        $sth->execute(array($_yearmon,$_alrt,$_fail,$_stat,$_prov));
      } catch (PDOException $e) {
        echo $e->getMessage();
        return 0;
      }

    }
    return 1;
  }
}
?>
