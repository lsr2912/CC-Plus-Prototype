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
// Receives form-data as input to be saved in the user table
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Set $_UID to form-POST value
//
$_UID = 0;
if ( isset($_POST['UID']) ) { $_UID = $_POST['UID']; }

// Check rights; proceed if Admin/Manager or if UID matches current user 
// Check for errors in arguments or access
// 
$ERR = 0;
if ( $_UID != $_SESSION['ccp_uid'] ) {	// account to update not my own?
  if ( $_SESSION['role'] > MANAGER_ROLE ) {	// if not admin/manager, then error
    $ERR = 1;
  } else if ( $_SESSION['role'] == MANAGER_ROLE ) {	// manager only updates their inst
    if ( $_SESSION['user_inst'] != $_POST['Inst'] ) { $ERR = 1; }
  }
}
if ( !isset($_POST['Create'] ) && ($_UID == 0) ) { $ERR = 2; }

// Check for duplicate email address, disallow creating a duplicate,
// and disallow changing existing record to an existing value.
//
$where = "email='" . $_POST['email'] . "'";
$existing_count = ccp_count_records("users", $where);
if ( isset($_POST['Create']) ) {	// On Create, ensure no duplicate email addresses
  if ( $existing_count > 0 ) { $ERR = 3; }
}
if ( isset($_POST['Update']) ) {
  $_user = ccp_get_users($_UID);
  if ( ($_POST['email'] != $_user['email']) && $existing_count > 0 ) { $ERR = 3; }

  // Managers cannot set role w/ more priv than they have
  // (not a show-stopper, just an enforced reset)
  //
  if ( isset($_POST['CCPRole']) ) {
    $_ROLE = $_POST['CCPRole'];
    if ( ($_SESSION['role'] == MANAGER_ROLE) && ($_ROLE < $_SESSION['role']) ) {
      $_ROLE = $_SESSION['role'];
    }
  } else {
    $ERR = 2;
  }
}

// If access and UID good, proceed
//
$confirm = FALSE;
if ($ERR == 0) {

  // setup database connection
  //
  global $ccp_adm_cnx;
  if ( !$ccp_adm_cnx ) { $ccp_adm_cnx = ccp_open_db("","Admin"); }

  if ( isset($_POST['Delete'] ) ) {

    // If user wants to stop the delete, redirect to home page
    //
    if ( isset($_POST['No']) ) {
       header("Location: " . CCPLUSROOTURL . "AdminHome.php", true, 303);
       exit;
    }

    // If User confirmed it, then proceed
    //
    $_count = 0;
    if ( isset($_POST['Yes']) ) {
      if ( $_POST['Yes'] == "Yes, I am Sure" ) {
        try {
          $_count = $ccp_adm_cnx->exec("DELETE FROM users WHERE user_id=$_UID");
        } catch (PDOException $e) {
          echo $e->getMessage();
        }
      }
    }

    if ( $_count == 0 ) {
       // If we get here, the request is unconfirmed
       // Build a quick form to prompt for confirmation
       //
       $user = ccp_get_users($_UID);
       print_page_header("CC-Plus User Management");
?>
  <table cellpadding="3" cellspacing="3" border="0">
    <tr><td width="100"></td><td colspan="2"><h3>Warning: deletion cannot be reversed!</h3></td></tr>
    <tr><td></td><td colspan="2">
      <p>&nbsp;</p>
      <p>Are you sure you want to delete this user?</p>
    </td></tr>
    <tr><td></td><td>Name</td><td><?php echo $user['first_name'] . " " . $user['last_name']; ?></td></tr>
    <tr><td></td><td>Email</td><td><?php echo $user['email']; ?></td></tr>
    <tr><td></td><td>Role</td><td><?php echo ccp_role_name($user['role']); ?></td></tr>
    <tr><td></td><td>Last_Login</td><td><?php echo $user['last_login']; ?></td></tr>
    <tr><td></td><td colspan="2">
      <form name="Confirm" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
        <input type="hidden" name="UID" value='$_UID'>
        <input type="hidden" name="Delete" value="Delete">
        <p>&nbsp;</p>
        <input type="submit" name="Yes" value="Yes, I am Sure"> &nbsp; &nbsp; &nbsp;
        <input type="submit" name="No" value="No! Stop!">
      </form>
    </td></tr>
  </table>
<?php
      $confirm = TRUE;
    }

  // Action is Update or Create
  //
  } else if ( (isset($_POST['Create'])) || (isset($_POST['Update'])) ) {

    // Deal with checkboxes for alerts and such
    //
    $new_pass = 0;
    if ( isset($_POST['force_pwchange']) ) { $new_pass = 1; }
    $optin_alerts = 0;
    if ( isset($_POST['optin_alerts']) ) { $optin_alerts = 1; }
    $is_active = 0;
    if ( isset($_POST['IsActive']) ) { $is_active = 1; }

    // Build Create query for users table
    //
    if ( isset($_POST['Create'] ) ) {
      $__Args = array($_POST['Inst'], $_POST['email'], md5($_POST['userpass']), $_POST['first'], $_POST['last'],
                      $_POST['phone'],  $_ROLE, $optin_alerts, $is_active, $new_pass);

      $_qry  = "INSERT INTO users";
      $_qry .= " (inst_id,email,password,first_name,last_name,phone,role,optin_alerts,active,password_change_required)";
      $_qry .= " VALUES (?,?,?,?,?,?,?,?,?,?)";

      // Run the Insert
      //
      try {
        $sth = $ccp_adm_cnx->prepare($_qry);
        $sth->execute($__Args);
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }

      // Send email to the new account holder
      //
      $to = $_POST['email'];
      $subj = "New CC-Plus User Account";
      $from = "From: admin@ccplus.org\r\n";
      $message  = "Hello " . $_POST['first'] . $_POST['last'] . ",\n\n";
      $message .= "An account has just been created for you in the CC-Plus Service.\n";
      $message .= "Follow this URL: " . CCPLUSROOTURL . " to login the first time.\n";
      $message .= "\nOnce there, you will need to enter your:\n";
      $message .= "  Email Address: " . $_POST['email'] . " , and \n";
      $message .= "  Password:" . $_POST['userpass'] . "\n";
      $message .= "\nYou will be prompted to change this password until you reset it.\n";
      mail( $to, $subj, $message, $from );

    // Build Update query (leave out password)
    //
    } else {

      $__Args = array($_POST['Inst'], $_POST['email'], $_POST['first'], $_POST['last'], $_POST['phone'],
            $_ROLE, $optin_alerts, $is_active, $new_pass, $_UID);

      $_qry1  = "UPDATE users SET inst_id=?, email=? , first_name=? , last_name=? , phone=? , role=? , ";
      $_qry1 .= "optin_alerts=?, active=? , password_change_required=? ";
      $_qry1 .= "WHERE user_id=?";

      try {
        $sth = $ccp_adm_cnx->prepare($_qry1);
        $sth->execute($__Args);
      } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
      }

      if ( $_POST['userpass'] != "Encrypted!" ) {
         ccp_change_password($_UID, $_POST['userpass']);
      }

    }

  } else {
    $ERR=4;
  }
  ccp_close_db("Admin");
}

// If errors, signal and stop
//
if ($ERR > 0) {
   print_page_header("CC-Plus User Management - Error");
   print "<blockquote>\n";
   print "<p>&nbsp;</p>\n<h3>Error on form submission</h3>\n";
   print "<p><font size=\"+1\">Update failed because:</br />\n";
   switch ($ERR) {
     case 1:
       print "Your account is not authorized for such a request.";
       break;
     case 2:
       print "Invalid arguments were provided.";
       break;
     case 3:
       print "The email address is already defined for another user.";
       break;
     case 4:
       print "The form submission type is unknown or not supported.";
   }
   print "<br /><br />You can return to the <a href='" . CCPLUSROOTURL . "ManageUser.php'>User Management Page</a>,\n";
   print "<br />or the <a href='" . CCPLUSROOTURL . "AdminHome.php'>Administration Home Page</a>\n";
   print "</font></p>\n";
   print "</blockquote>\n";
   

// Otherwise, print confirmation message
//
} else {
  print_page_header("CC-Plus User Management Confirmation");
?>
 <table cellpadding="3" cellspacing="3" border="0">
    <tr><td width="100"></td><td><h3>Requested updates successfully completed.</h3></td></tr>
    <tr><td></td>
      <td><p><font size="+1">
<?php
   header( "refresh:4;url=" . CCPLUSROOTURL . "AdminHome.php" );
   print "You'll be redirected to the Administration homepage. If not, <a href='" . CCPLUSROOTURL . "AdminHome.php'>click here</a>.\n";
?>
      </font></p></td>
    </tr>
  </table>
<?php
}
include 'ccplus/footer.inc.html.php';
?>
