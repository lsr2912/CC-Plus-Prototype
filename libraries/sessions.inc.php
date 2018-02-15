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
// CC-Plus session managemement functions
//
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once ('ccplus/dbutils.inc.php');

session_start();

if (!function_exists("ccp_role_name")) {
  function ccp_role_name($_role=0) {
    // This function translates role-id to text
    //
    $_text = "NoRole";
    if ( $_role == ADMIN_ROLE ) {
       $_text = "Admin";
    } else if ( $_role == MANAGER_ROLE ) {
       $_text = "Manager";
    } else if ( $_role == USER_ROLE ) {
       $_text = "User";
    }
    return $_text;
  }
}

if (!function_exists("ccp_role_value")) {
  function ccp_role_value($_role_str="") {
    // This function translates role-id to text
    //
    $_role = 0;
    if ( $_role_str == "Admin" ) {
       $_role = ADMIN_ROLE;
    } else if ( $_role_str == "Manager" ) {
       $_role = MANAGER_ROLE;
    } else if ( $_role_str == "User" ) {
       $_role = "User";
    }
    return $_role;
  }
}

if (!function_exists("ccp_is_authenticated")) {

  // This function examines the current $_SESSION globals
  // to decide whether a user is properly authenticated.
  // Returns TRUE or FALSE
  //
  function ccp_is_authenticated() {

    // If existing session holds a user value, return TRUE
    //
    if ( isset($_SESSION['ccp_uid']) ) { return TRUE; }

    // No session and no cookie means return FALSE
    //
    if ( !isset($_COOKIE["CCP_USER"]) ) {
       return FALSE;

    // If cookie exists, rebuild a new session based on the cookie value
    // to reset the session.
    } else {
       $_user = $_COOKIE["CCP_USER"];
       ccp_init_session();
       ccp_set_session_globals($_user);
       return TRUE;
    }
  }
}

if (!function_exists("ccp_init_session")) {

  // This function ensures that the $_SESSION and cookies are in sync.
  // As long as the session exists in Apache, the cookie value can be 
  // used to reconnect to it. The minimum time-period for re-establishing
  // the session depends on the lifetime of the CCP_SESS cookie.
  // Apache housekeeping can, however, delete the session, which means
  // a new one has to be created.

  function ccp_init_session() {

    // Connect to, or create the session
    //
    $need_new = TRUE;
    if ( isset($_COOKIE["CCP_SESS"]) ) {
       $a = session_id ( $_COOKIE["CCP_SESS"] );
       if ( $a != '' ) { $need_new = FALSE; }
    }

    if ( $need_new ) {

       if ( !isset($_SESSION) ) { session_start(); }
       // Store session ID in a cookie
       //
       $_sess_id = session_id();
       setcookie("CCP_SESS", $_sess_id, CCP_COOKIE_LIFE, "/", "", 0);
    }
  }
}

if (!function_exists("ccp_set_session_globals")) {
  //
  // Set user-level metadata in the SESSION for a given user_id
  // Input arguments:
  //    $user  : a STRING of the form <consortiaID>:<consortiaKey>:<user_id>
  //
  function ccp_set_session_globals($user) {

    //
    // Break up the user-arg to figure out the database we need
    // to connect to and the user_id for the user
    //
    $u_vals = preg_split('/:/', $user);
    $_con_id  = $u_vals[0];
    $_con_key = $u_vals[1];
    $_user_id = $u_vals[2];
    $_db = "ccplus_" . $_con_key;

    global $ccp_usr_cnx;
    if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db($_db); }

    try {
       $_result = $ccp_usr_cnx->query("SELECT * FROM users WHERE user_id=$_user_id");
       while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
         $_SESSION['email'] = $row['email'];
         $_SESSION['first_name'] = $row['first_name'];
         $_SESSION['last_name'] = $row['last_name'];
         $_SESSION['role'] = $row['role'];
         if ( $row['password_change_required'] == 1 ) { $_SESSION['force_pass'] = 1; }
         $_SESSION['user_inst'] = $row['inst_id'];
       }

       $_SESSION['ccp_uid'] = $_user_id;
       $_SESSION['ccp_con_id'] = $_con_id;
       $_SESSION['ccp_con_key'] = $_con_key;
       $_cookieval = "$_con_id:$_con_key:$_user_id";
       setcookie("CCP_USER", $_cookieval, CCP_COOKIE_LIFE, "/", "", 0);

     } catch (PDOException $e) {
       echo $e->getMessage();
     }
  }
}

if (!function_exists("ccp_zap_session")) {
  function ccp_zap_session() {
    if ( !isset($_SESSION) ) { return; }
    if ( isset($_SESSION['ccp_uid']) ) { unset ($_SESSION['ccp_uid']); }
    if ( isset($_SESSION['ccp_con_id']) ) { unset ($_SESSION['ccp_con_id']); } 
    if ( isset($_SESSION['ccp_con_key']) ) { unset ($_SESSION['ccp_con_key']); } 
    if ( isset($_SESSION['email']) ) { unset ($_SESSION['email']); }
    if ( isset($_SESSION['first_name']) ) { unset ($_SESSION['first_name']); }
    if ( isset($_SESSION['last_name']) ) { unset ($_SESSION['last_name']); }
    if ( isset($_SESSION['role']) ) { unset ($_SESSION['role']); }
    if ( isset($_SESSION['force_pass']) ) { unset ($_SESSION['force_pass']); }
    if ( isset($_SESSION['user_inst']) ) { unset($_SESSION['user_inst']); }
  }
}

if (!function_exists("ccp_valid_login")) {

  // Function to determine if a provided username/password
  // pair is a valid CC-Plus user
  //
  //  $_conID : the consortia ID for the user
  //  $_user  : the username (email) to test (case-insensitive)
  //  $_pass  : the password to test
  //
  // Returns: TRUE or FALSE

  function ccp_valid_login( $_conID, $_user, $_pass ) {

    global $ccp_adm_cnx;
    $pass_test = md5($_pass);
    $retval = FALSE;                    // Default to FALSE

    // Check database handle and open admin-access connection if needed
    //
    if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("ccplus_global", "Admin"); }

    // Lookup the chosen consortium key from the master table
    //
    $_Key = "";
    try {
       $_result = $ccp_adm_cnx->query("SELECT * FROM Consortia WHERE ID='$_conID'");
       while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) { $_Key = $row['ccp_key']; }
     } catch (PDOException $e) {
       echo $e->getMessage();
     }

    // Test the email+password against the designated consortium user table
    //
    $_user_id = 0;
    $_table = "ccplus_" . $_Key . ".users";

    try {
       $_qry = "SELECT user_id,email,password FROM $_table WHERE active=1 AND email='$_user'";
       $_result = $ccp_adm_cnx->query($_qry);
       while ( $row = $_result->fetch(PDO::FETCH_ASSOC) ) {
         if ( $pass_test == $row['password'] ) {
            $_user_id = $row['user_id'];
            ccp_init_session();
            $_user_arg = "$_conID:$_Key:$_user_id";
            ccp_set_session_globals($_user_arg);
            $retval = TRUE;
         }
       }

       // Update the last-login column for the user
       //
       if ( $_user_id > 0 ) {
         $_res = $ccp_adm_cnx->exec("UPDATE $_table SET last_login=NOW() WHERE user_id=$_user_id");
       }

    } catch (PDOException $e) {
      echo $e->getMessage();
    }

    // Drop log record
    //
    // ccp_auth_logger( $_user, $retval );

    return $retval;
  }
}
?>
