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
// Dialog for dealing with forgotten passwords
//
require_once("ccplus/sessions.inc.php");

// This page submits to itself, see if we're supposed to process
// input or display the form
//
$Error = 0;
if ( isset($_POST['submitted']) ) {

  $consortium = ccp_get_consortia($_POST['conID']);

  // Generate and assign a new password for the user
  //
  $new_pass = ccp_reset_password($consortium['ccp_key'], $_POST['email']);

  // Send an email with the new password
  //
  ccp_notify_new_password($consortium['email'], $_POST['email'], $new_pass);

  // Print confirmation
  //
  print_page_header("CC-Plus Proof-of-Concept : Password Updated");
?>
  <div id="maincontent">
    <div class="innertube" align="center">
      <table>
        <tr><td width="100"></td><td><h3>Your password has been reset.</h3></td></tr>
        <tr><td></td>
          <td><p><font size="+1">
<?php
      print "<br />New-pass=" . $new_pass . "<br /><br />\n";
      print "An email containing the new password value has been sent.<br />\n";
      print "<a href='".CCPLUSROOTURL."login.php'>Click here for the login page</a>.\n";
?>
          </font></p></td>
        </tr>
      </table>
    </div>
  </div>
<?php
//
} else {

// Form not submitted, get the list of active consortia
//
$consortia = ccp_get_consortia_ui();

// Build and display form fields
//
print_page_header("CC-Plus Proof-of-Concept : Reset Password");

// Add javascript form-handlers
//
print "<script type=\"text/javascript\" src=\"".CCPLUSROOTURL."include/validators.js\"></script>\n";
?>
<script type="text/javascript">
  //// Function to validate form fields
  //// (uses subfunctions from /include/validators.js)
  function validateFormSubmit(theForm) {
    var reason = "";
    reason += validateEmail(theForm.email);
    if (reason != "") {
      alert("Some fields need correction:\n" + reason);
      return false;
    }
    return true;
  }
</script>
<div id="maincontent">
  <div class="innertube" align="center">
    <form name="PWReset" onsubmit="return validateFormSubmit(this)" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
      <input type="hidden" name="submitted" value="1">
      <table>
        <tr><td colspan="2"><p align="center">
         <font size="+1">Your password will be reset to a randomized value and sent to the email address
         you provide here.<br /> You will be prompted to reset your password on your next login.</font>
        </p></td></tr>
        <tr>
          <td align="right"><label for="conID">Select your Consortium:</label></td>
          <td>
            <select name="conID" id="conID">
<?php
  foreach ($consortia as $con) {
    print "            <option value=" . $con['ID'] . ">" . $con['name'] . "</option>\n";
  }
?>
            </select>
          </td>
        </tr>
        <tr>
          <td align="right"><label for="email">Your email address:</label></td>
          <td>
            <input type="text" name="email" size="16" maxlength="60"><br />
          </td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="Submit" name="ResetPass" value="Reset Password">
          </td>
        </tr>
      </table>
    </form>
  </div>
<div>
<?php
}
include 'ccplus/footer.inc.html.php';
?>
