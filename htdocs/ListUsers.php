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
// ListUsers.php
//
// CC-Plus User Listing page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Build roles into a string for JS functions
//
$enum_roles = "0:NoRole,";
$_all_roles = ccp_get_roles_ui();
foreach ( $_all_roles as $_r ) {
  $enum_roles .= $_r['role_id'] . ":" . $_r['name'] . ",";
}
$enum_roles = preg_replace("/,$/","",$enum_roles);

// Admins and Editors can use this page
//
$ERR = 0;
$_INST = 0;
$is_admin = false;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] == ADMIN_ROLE ) {
    $is_admin = true;
  } else if ( $_SESSION['role'] == MANAGER_ROLE ) {
    $_INST = $_SESSION['user_inst'];
  } else {
    $ERR = 1;
  }
} else {
  $ERR = 1;
}

// Setup page header differently if Admin.vs.Manager, and
// add view-dependent jQuery/Ajax refresh scripts
//
$_title = "CC-Plus User Accounts : ";
if ( $_SESSION['role'] == ADMIN_ROLE ) {

  // Pull the consortia info based on session variable
  //
  $_CON = ccp_get_consortia($_SESSION['ccp_con_id']);
  $_title .= $_CON['name'];

} else {

  $_u_inst = ccp_get_institutions($_SESSION['user_inst']);
  $_title .= $_u_inst['name'];
}
print_page_header($_title,TRUE);
if ( $ERR != 0 ) {
  print_noaccess_error();
  include 'ccplus/footer.inc.html.php';
  exit;
}
print "  <link href=\"" . CCPLUSROOTURL . "include/tablesorter_theme.css\" rel=\"stylesheet\">\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.widgets.js\"></script>\n";
?>
  <style>
    /* override document styling */
    .popover.right { text-align: left; }
    .ui-widget-content a { color: #428bca; }
  </style>
<?php
print "  <link href=\"" . CCPLUSROOTURL . "include/SelectorPopup.css\" rel=\"stylesheet\">\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/SelectorPopup.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/List_Users.js\"></script>\n";

print "<form name='UserList' id='UserList'>\n";
print "  <input type=\"hidden\" name=\"enum_roles\" id=\"enum_roles\" value=\"$enum_roles\">\n";
?>
  <table width="80%" class="centered">
    <tr>
      <td width="15%" align="left"><label for="filter_stat"><strong>Filter by Status</strong></label></td>
      <td width="85%" align="left">
        <select name="filter_stat" id="filter_stat">
          <option value="ALL" selected>ALL</option>
          <option value=1>Active</option>
          <option value=0>Inactive</option>
        </select>
      </td>
    </tr>
  </table>
  <center>
<?php
// Build initial data table
//
print "  <table id=\"data_table\" class=\"tablesorter\" cellpadding=\"2\">\n";
?>
    <thead>
      <tr>
        <th id="first"      width='10%' align='left'>First</th>
        <th id="last"       width='10%' align='left'>Last</th>
<?php
if ( $_SESSION['role'] == ADMIN_ROLE ) {	// Inst is suppressed for non-admin
  print "        <th id=\"inst\" width='30%' align='left'>Institution</th>\n";
}
?>
        <th id="email"      width='20%' align='left'>Email</th>
        <th id="phone"      width='10%' align='center'>Phone</th>
        <th id="role"       width='10%' align='center'>Role</th>
        <th id="last_login" width='10%' align='left'>Last Login</th>
      </tr>
    </thead>
    <tbody id="Summary">
<?php
// Get initial data for display 
//
$users = ccp_get_users(0,"ALL",$_INST);

// Display initial data; form inputs will allow user to refresh and/or sort it 
//
foreach ( $users as $_user ) {

  print "      <tr>\n";
  print "        <td align='left'>" . $_user['first_name'] . "</td>\n";
  print "        <td align='left'>" . $_user['last_name'] . "</td>\n";
          
  if ( $_SESSION['role'] == ADMIN_ROLE ) {	// Inst is suppressed for non-admin
    print "        <td align='left'>";
    if ( $_user['inst_id'] == 0 ) {
      print "Staff</td>\n";
    } else {
      print "<a href=\"ManageInst.php?Inst=" . $_user['inst_id'] . "\">";
      print $_user['inst_name'] . "</a></td>\n";
    }
  }

  print "        <td align='left'>";
  if ( $_SESSION['role'] <= $_user['role'] ) {
    print "<a href=\"ManageUser.php?User=" . $_user['user_id'] . "\">";
    print $_user['email'] . "</a></td>\n";
  } else {
    print $_user['email'] . "</td>\n";
  }
  print "        <td align='center'>" . $_user['phone'] . "</td>\n";
  print "        <td align='center'>" . ccp_role_name($_user['role']) . "</td>\n";
  print "        <td align='left'>" . substr($_user['last_login'],0,10) . "</td>\n";
  print "      </tr>\n";
}
?>
    </tbody>
  </table>
  </center>
  </form>
<?php
//
// All done, footer time
//
include 'ccplus/footer.inc.html.php';
?>
