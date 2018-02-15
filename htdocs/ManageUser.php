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
// ManageUser.php
//
// CC-Plus user management page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Pull the consortia info based on session variable
//
$_CON = ccp_get_consortia($_SESSION['ccp_con_id']);

// Build breadcrumbs, pass to page header builder
//
print_page_header("CC-Plus User Management: " . $_CON['name'],TRUE);

// If "me=1" passed via GET, user gets to see/edit their own profile
//
$_ME = FALSE;
$_UID = 0;
if ( isset($_GET['me']) ) {
  if ( $_GET['me'] ) {
    $_UID = $_SESSION['ccp_uid'];
    $_ME = TRUE;
  }
}

// Set $_UID to form value
//
if ( isset($_REQUEST['User']) ) { $_UID = $_REQUEST['User']; }

// Check rights; proceed if Admin or $_ME
//
if ( ($_SESSION['role'] == ADMIN_ROLE) || $_ME ) {

// Define an array to hold initial/default form values
//
$_user = array();

// IF $_UID=0 , we're creating a user - set initial fields
// 
if ( $_UID == 0 ) {

   $_user['inst_id'] = 0;
   $_user['first_name'] = "";
   $_user['last_name'] = "";
   $_user['email'] = "";
   $_user['phone'] = "";
   $_user['password'] = "";
   $_user['role'] = 20;
   $_user['optin_alerts'] = 0;
   $_user['active'] = 1;
   $_user['last_login'] = "";
   $_user['password_change_required'] = 1;

// If viewing/editing an existing record, query the database
//
} else {
   $_user = ccp_get_users($_UID);
}

// Setup Javascript functions and form
//
?>
  <script type="text/javascript" src="/include/validators.js"></script>
  <script type="text/javascript">
    //// Javascript to hide/reveal contents of the password field
    ////
    $(function () {
      $("#chk_show_pass").bind("click", function () {
        var userpass = $("#userpass");
        if ($(this).is(":checked")) {
          userpass.after('<input onchange = "pass_changed(this);" id = "txt_' + userpass.attr("id") + '" type = "text" value = "' + userpass.val() + '" />');
          userpass.hide();
        } else {
          userpass.val(userpass.next().val());
          userpass.next().remove();
          userpass.show();
        }
      });
    });
    function pass_changed(txt) {
      $(txt).prev().val($(txt).val());
    }
    //// Function to test various form fields
    //// (uses subfunctions from /include/validators.js)
    function validateFormSubmit(theForm) {
      var reason = "";

      reason += validatePassword(theForm.userpass);
      reason += validateEmail(theForm.email);
      reason += EmailInUsers(theForm.email);
      
      if (reason != "") {
        alert("Some fields need correction:\n" + reason);
        return false;
      }
      return true;
    }
  </script>
<?php
  $_form  = "<form name=\"User_Form\" onsubmit=\"return validateFormSubmit(this)\" id=\"Userform\" method=\"post\"";
  $_form .= " action=\"" . CCPLUSROOTURL . "UpdateUser.php\">\n";
  print $_form;
  print "  <input type=\"hidden\" name=\"UID\" value=$_UID>\n";
?>
  <table width="80%" class="centered">
    <tr>
      <td width="15%">&nbsp;</td>
      <td width="30%">&nbsp;</td>
      <td width="10%">&nbsp;</td>
      <td width="15%">&nbsp;</td>
      <td width="30%">&nbsp;</td>
    <tr>
    <tr>
      <td align="right"><label for="first">First Name</label></td>
      <td><input type="text" id="first" name="first" value="<?php print $_user['first_name'] ?>" /></td>
      <td>&nbsp;</td>
      <td align="right"><label for="last">Last Name</label></td>
      <td><input type="text" id="last" name="last" value="<?php print $_user['last_name'] ?>" /></td>
    </tr>
    <tr>
      <td align="right"><label for="email">Email Address</label></td>
      <td>
<?php
  if ( $_user['email'] == "Administrator" ) {
    print "        <input type=\"hidden\" name=\"email\" value=\"Administrator\">\n";
    print "        <strong>Administrator</strong>";
  } else {
    print "        <input type=\"text\" id=\"email\" name=\"email\" value=\"" . $_user['email'] . "\" />";
    if ( $_UID == 0 ) {
      print "        &nbsp; &nbsp;<font color=\"red\"> (required)</font>\n";
    }
  }
?>
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="phone">Phone</label>
      <td><input type="text" id="phone" name="phone" value="<?php print $_user['phone'] ?>" /></td>
    </tr>
    <tr>
      <td align="right"><label for="userpass">Password</label></td>
<?php
  // If existing record, replace (encrypted) value from the database with
  // "encrypted". If user changes it, the update script will encrypt and
  // store the given value.
  print "      <td><input type=\"password\" name=\"userpass\" id=\"userpass\"";
  if ( $_UID > 0 ) {
    print " value=\"Encrypted!\" />\n";
  } else {
    print " />\n";
  }
  if ( $_UID == 0 ) {
    print "        &nbsp; &nbsp;<font color=\"red\"> (required)</font>\n";
  }
?>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td align="right"><label for="chk_show_pass">Display password</label>
      <td><input type="checkbox" name="chk_show_pass" id="chk_show_pass" />
      <td>&nbsp;</td>
<?php
  if ( $_user['last_login'] != "" ) {
    print "      <td align=\"right\">Last Login: </td>\n";
    print "<td>&nbsp;" . $_user['last_login'] . "</td>\n";
  } else {
    print "      <td colspan=\"2\">&nbsp;</td>\n";
  }
?>
    </tr>
    <tr>
      <td align="right"><label for="optin_alerts">Opt-in for Alerts</label>
<?php
        print "        <td><input type=\"checkbox\" name=\"optin_alerts\"";
        if ( $_user['optin_alerts'] == 1 ) { print " checked"; }
        print " /></td>\n";
?>
      <td colspan="5">&nbsp;</td>
    </tr>
<?php
  // Admin access displays a dropdown to define user's access
  // and a checkbox to force a password change on next login
  //
  if ( $_SESSION['role'] == ADMIN_ROLE ) {

    $_insts = ccp_get_institutions_ui();
?>
    <tr><td colspan="5">&nbsp;</td></tr>
    <tr>
      <td align="right"><label for="Inst">Institution</label>
      <td>
        <select name="Inst" id="Inst" />
<?php
    // Populate dropdown with available institutionss
    //
    array_unshift($_insts,array("inst_id"=>0,"name"=>"Staff"));
    foreach ( $_insts as $_inst ) {
      print "          <option value=\"" . $_inst['inst_id'] . "\"";
      if ( $_inst['inst_id'] == $_user['inst_id'] ) {
        print " selected />" . $_inst['name'] . "</option>\n";
      } else {
        print " />" . $_inst['name'] . "</option>\n";
      }
    }
?>
        </select>
      </td>
      <td>&nbsp;</td>
      <td colspan="2">
        <label for="IsActive">Account is Active</label>
<?php
        print "        <input type=\"checkbox\" name=\"IsActive\"";
        if ( $_user['active'] == 1 ) { print " checked"; }
        print " />\n";
?>

      </td>
    </tr>
    <tr>
      <td align="right"><label for="CCPRole">Role</label>
      <td>
        <select name="CCPRole" id="CCPRole" />
<?php
    // Populate dropdown with available roles
    //
    $_roles = ccp_get_roles_ui();
    foreach ( $_roles as $_r ) {
      print "          <option value=\"" . $_r['role_id'] . "\"";
      if ( $_r['role_id'] == $_user['role'] ) {
        print " selected />" . $_r['name'] . "</option>\n";
      } else {
        print " />" . $_r['name'] . "</option>\n";
      }
    }
?>
        </select>
      </td>
      <td>&nbsp;</td>
      <td colspan="2">
        <label for="force_pwchange">Force new password on next login</label>
<?php
        print "        <input type=\"checkbox\" name=\"force_pwchange\"";
        if ( $_user['password_change_required'] == 1 ) { print " checked"; }
        print " />\n";
?>
      </td>
    </tr>
    <tr><td colspan="5">&nbsp;</td></tr>
<?php

  }	// End-if Admin-Role

  // If non-admin or viewing "me", build submit button as "Update"
  //
  if ( $_ME || $_SESSION['role'] != ADMIN_ROLE ) {
?>
    <tr>
      <td align="right">
        <input type="submit" name="Update" value="Update">
      </td>
      <td colspan="4">&nbsp;</td>
    </tr>
<?php
  // Admins see either "Create" for a new entry or 2 buttons
  // (Update and Delete) if viewing an existing user
  //
  } else {
    if ( $_UID == 0 ) {		// Submit is Create for a new user
?>
    <tr>
      <td align="right">
        <input type="submit" name="Create" value="Create User">
      </td>
      <td colspan="4">&nbsp;</td>
    </tr>
<?php
    } else {		// Submit row of 2 buttons for Update or Delete
?>
    <tr>
      <td>&nbsp;</td>
      <td align="left">
        <input type="submit" name="Update" value="Update">
      </td>
      <td>&nbsp;</td>
<?php
    if ( $_user['email'] != "Administrator" ) {		// No delete option for "Administrator" username
       print "      <td align=\"right\">\n";
       print "        <input type=\"submit\" name=\"Delete\" value=\"Delete User\">\n";
       print "      </td>\n";
    } else {
       print "      <td>&nbsp;</td>\n";
    }
?>
      <td>&nbsp;</td>
    </tr>
<?php
    }
  }
?>
  </table>
  </form>
<?php

// User-privilege error - not seeking own profile and not admin
//
} else {
   print_noaccess_error();
}

include 'ccplus/footer.inc.html.php';
?>
