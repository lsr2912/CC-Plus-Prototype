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
// Change your own password dialog 
//
require_once('ccplus/sessions.inc.php');

// If we were forced here, remember that
//
$_force_pass = FALSE;
if ( isset($_SESSION["force_pass"]) ) {
  if (isset($_SESSION["force_pass"]) ==1 ) {
    $_force_pass = TRUE;
  }
}

// This page submits to itself - see if we're supposed to process
// input or display the form
//
$Error = 0;
if ( isset($_POST['submitted']) ) {

  // Redirect to loginpage if not yet authenticated
  // (script depends on SESSION variables)
  //
  if ( !ccp_is_authenticated() ) {
      header('Location: '.CCPLUSROOTURL.'login.php');
      exit;
  }

  // Check password fields
  //
  if ( !isset($_POST['new_pass1']) || !isset($_POST['new_pass2']) ) {
    $Error=1;
  } else if ( $_POST['new_pass1'] != $_POST['new_pass2'] ) {
    $Error=2;
  }

  if ( $Error == 0 ) {
    // Update the password
    //
    ccp_change_password($_SESSION['ccp_uid'] , $_POST['new_pass1']);

    // Print confirmation
    //
    print_page_header("CC-Plus Proof-of-Concept : Confirmation");
?>
  <iv id="maincontent">
    <div class="innertube" align="center">
      <table>
        <tr><td width="100"></td><td><h3>Password successfully changed.</h3></td></tr>
        <tr><td></td>
          <td><p><font size="+1">
<?php
          print "Confirmed!<br />\n";
          if ( !isset($_SESSION['role']) ) { $_SESSION['role'] = USER_ROLE; }
          if ( $_SESSION['role'] != 0  &&  $_SESSION['role'] <= MANAGER_ROLE ) {
             print "<br /><br />You can now return to the <a href='".CCPLUSROOTURL."AdminHome.php'>";
             print "CC-Plus Administration HomePage</a>,  or<br />\n";
             print "the <a href='".CCPLUSROOTURL."ReportHome.php'>CC-Plus Reports Page";
          } else {
             print "<br /><br />You can now return to the <a href='".CCPLUSROOTURL."index.php'>CC-Plus Home Page</a>\n";
             $redirect_url = CCPLUSROOTURL . "AdminHome.php";
          }
?>
          </font></p></td>
        </tr>
      </table>
    </div>
  </div>
<?php

    include 'ccplus/footer.inc.html.php';
    exit();

  } // end-if : no errors

} // end-if : form submitted

// If we get here, build the form
// any submission errors get signalled below
//
print_page_header("CC-Plus Proof-of-Concept : Change password");

// Add javascript form-handlers
//
print "<script type=\"text/javascript\" src=\"".CCPLUSROOTURL."include/validators.js\"></script>\n";
?>
<script type="text/javascript">
  //// Function to validate form fields
  //// (uses subfunctions from validators.js)
  function validateFormSubmit(theForm) {
    var reason = "";
    reason += validatePassword(theForm.new_pass1);
    reason += validatePassword(theForm.new_pass2);
    if ( theForm.new_pass1.value != theForm.new_pass2.value ) {
      reason += "Password confirmation mismatch";
    }
    if (reason != "") {
      alert("Some fields need correction:\n" + reason);
      return false;
    }
    return true;
  }
</script>
<div id="maincontent">
  <div class="innertube" align="center">
    <form name="PWchange" onsubmit="return validateFormSubmit(this)" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
      <input type="hidden" name="submitted" value="1">
      <table cellspacing="5" cellpadding="0" border="0" width="80%" align="center">
        <tr>
<?php
if ( $Error == 0 ) {
  print "          <td colspan=\"2\"><p>&nbsp;</p></td>\n";
} else {
  print "          <td colspan=\"2\" align=\"center\"><p><font color=\"red\" size=\"+1\">\n";
  if ( $Error == 1 ) { print "Missing arguments - cannot make changes"; }
  if ( $Error == 2 ) { print "Password confirmation mismatch - please try again"; }
  print "          </font></p></td>\n";
}
?>
    </tr>
<?php
  if ($_force_pass) {
    print "        <tr><td colspan=\"2\" align=\"center\">\n";
    print "          <p><strong><font size=\"+1\">";
    print "Your password needs to be changed from the current setting.</font></strong></p>\n";
    print "          <p><strong>While you should reset it at your earliest convenience, you may continue using the";
    print " <a href=\"".CCPLUSROOTURL."index.php\">CC-Plus site</a> without doing so.</strong></p>\n";
    print "          <p><strong>This dialog, however, will persist until it is changed.</strong></p></td>\n";
    print "        </tr>\n";
  }
?>
        <tr>
          <td align="right"><label for="new_pass1">Enter new password:</label></td>
          <td><input type="password" name="new_pass1" size="16" maxlength="16"></td>
       </tr>
        <tr>
          <td align="right"><label for="new_pass2">Repeat new password:</label></td>
          <td><input type="password" name="new_pass2" size="16" maxlength="16"></td>
       </tr>
       <tr><td colspan="2"><p>&nbsp;</p></td>
       <tr>
         <td>&nbsp;</td>
         <td>
           <div>
             <input type="Submit" name="ChangePass" value="Change password">
           </div>
         </td>
       </tr>
      </table>
    </form>
  </div>
</div>
<?php include 'ccplus/footer.inc.html.php'; ?>
