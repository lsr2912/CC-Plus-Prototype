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
// login.php
//
// CCPlus re-entrant page to prompt for and validate user credentials
//
require_once('ccplus/helpers.inc.php');
require_once('ccplus/sessions.inc.php');

// credentials arriving via POST indicates a login attempt
//
$_failed = FALSE;
if ( isset($_POST['conID']) && isset($_POST['email']) && isset($_POST['password']) ) {

   // If credentials are good, then redirect to content
   //
   if ( ccp_valid_login($_POST['conID'], $_POST['email'], $_POST['password']) ) {

     // Remember the username (email address) by setting a 1-yr cookie
     // 
     if ( isset($_POST['remember']) ) {
       setcookie("CCP_UNAME", $_POST['email'], strtotime( '+365 days' ), "/", "", 0);
     }

     // If password change required, redirect to the change password page
     //
     if ( isset($_SESSION['force_pass']) ) {
       if ( $_SESSION['force_pass'] ==1 ) {
         header("Location: " . CCPLUSROOTURL . "change_passwd.php", true, 303);
         exit();
       }
     }

     // If there is no preserved URL in the session (i.e. no prior-page to return to),
     // then redirect to main index.
     //
     if ( !isset($_SESSION['redirect_url']) ) {

        $redirect_url .= CCPLUSROOTURL . "ReportHome.php";
        if ( isset($_SESSION['role']) ) {
          if ( $_SESSION['role'] != 0  &&  $_SESSION['role'] <= MANAGER_ROLE ) {
            $redirect_url = CCPLUSROOTURL . "AdminHome.php";
          }
        }

     // If redirect_url is set in session, use it
     //
     } else {
        $redirect_url = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
     }
     header("Location: $redirect_url", true, 303);
     exit();

   // Credentials failed - set flag for form below
   //
   } else {
      $_failed = TRUE;
   }
}

//
// Display login form
//
print_page_header("CC-Plus Proof-of-Concept: Login");
print "      <div class=\"innertube\" align=\"center\">\n";
print "        <form method=\"POST\" action=\"" . CCPLUSROOTURL . "login.php\">\n";
?>
          <input type="hidden" name="loginfrm" value="1">
          <table summary="Enter your account information">
<?php
  // Add error message or empty row depending on value of input flag
  //
  print "            <tr><td colspan=\"3\"><p>";
  if ($_failed) {
    print "<font color=\"RED\"><strong>Invalid login - retry or contact the CC-Plus Administrator</strong></font>";
  } else {
    print "&nbsp;";
  }
  print "</p></td></tr>\n";

  // Get the list of active consortia and display as dropdown
  //
  $consortia = ccp_get_consortia_ui();
?>
            <tr>
              <td><label for="conID">Consortium:</label></td>
              <td>
                <select name="conID" id="conID">
<?php
  foreach ($consortia as $con) {
    print "                <option value=" . $con['ID'] . ">" . $con['name'] . "</option>\n";
  }
?>
                </select>
              </td>
            </tr>

            <tr>
              <td><label for="email">Email Address:</label></td>
<?php
  print "              <td><input type=\"text\" name=\"email\"";
  if ( isset($_COOKIE['CCP_UNAME']) ) {
    print " value=\"" . $_COOKIE['CCP_UNAME'] . "\"";
  }
  print "></td>\n";
?>
              <td>&nbsp; &nbsp; &nbsp;<input type="checkbox" name="remember">
                <label for="remember">Remember me</label>
                </td>
            </tr>
            <tr>
              <td><label for="passwd">Password:</label></td>
              <td><input type="password" name="password"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td colspan=3 align="center"><br />
                <input type="Submit" name="LogIn" value="Log in" class="button">
              </td>
            </tr>
            <tr>
              <td colspan="3" align="center">
<?php
  print "                <br /><a href=\"" . CCPLUSROOTURL . "forgot_pass.php\">Forgot your password?</a>\n";
?>
              </td>
            </tr>
          </table>
        </form>
      </div>
    </div>
<?php include 'ccplus/footer.inc.html.php'; ?>
  </body>
</html>
