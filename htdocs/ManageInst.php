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
// ManageInst.php
//
// CC-Plus institution management page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Admins and Editors can use this page
//
$ERR = 1;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] <= MANAGER_ROLE ) { $ERR = 0; }
}
// Set $_INST depending on input.
//
$_INST=0;
if ( isset($_REQUEST['Inst']) ) { $_INST = $_REQUEST['Inst']; }

if ( $ERR == 0 ) {

  // IF $_INST=0 , we're creating an institution - set initial fields
  //
  if ( $_INST == 0 ) {

     $Inst['inst_id'] = 0;
     $Inst['name'] = "";
     $Inst['active'] = 1;
     $Inst['admin_userid'] = 0;
     $Inst['notes'] = "";
     $Inst['type'] = "";
     $Inst['sushiIPRange'] = "";
     $Inst['shibURL'] = "";
     $Inst['fte'] = 0;
     $users = array();
     $admin_user = array();

  // If viewing/editing an existing record, query the database for
  // current settings and existing users. Aliases get initial values
  // only AFTER a provider is chosen, so no need to initialize them.
  //
  } else {
     $Inst = ccp_get_institutions($_INST);
     $users = ccp_get_users_ui( $_INST );
  }

  // Get/set admin-user info
  //
  if ( $Inst['admin_userid'] > 0 ) {
     $admin_user = ccp_get_users( $Inst['admin_userid'] );
  } else {
     $admin_user['user_id'] = 0;
  }

  //
  // Get active provider names and Id's
  //
  $providers = ccp_get_providers_ui( 1 );

  // Build breadcrumbs, pass to page header builder
  //
  print_page_header("CC-Plus Institution Management",TRUE);

  print "<script src=\"" . CCPLUSROOTURL . "include/Manage_Inst.js\"></script>\n";
  // $_form  = "<form name=\"Inst_Form\" onsubmit=\"return validateFormSubmit(this)\" id=\"Instform\" method=\"post\"";
  $_form  = "<form name=\"Inst_Form\" id=\"Instform\" method=\"post\"";
  $_form .= " action=\"" . CCPLUSROOTURL . "UpdateInst.php\">\n";
  print $_form;
  print "<input type=\"hidden\" name=\"INST\" id=\"INST\" value=" . $_INST . ">\n";
?>
  <table width="80%" class="centered">
    <tr>
      <td width="5%">&nbsp;</td>
      <td width="10%">&nbsp;</td>
      <td width="20%">&nbsp;</td>
      <td width="5%">&nbsp;</td>
      <td width="10%">&nbsp;</td>
      <td width="20%">&nbsp;</td>
      <td width="5%">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="3" align="right">
        <label for="Iname">Institution Name</label>
        <input type="text" id="Iname" name="Iname" value="<?php print $Inst['name'] ?>" />
      </td>
      <td>&nbsp;</td>
      <td colspan="3" align="left">
        <label for="Istat">Status</label>
        <select id="Istat" name="Istat">
<?php
  print "        <option value=1";
  print ($Inst['active']==1) ? " selected>" : ">";
  print "Active</option>\n";
  print "        <option value=0";
  print ($Inst['active']==0) ? " selected>" : ">";
  print "Inactive</option>\n";
?>
        </select>
      </td>
    </tr>
<?php
// If no users (other than admin) exist, or creating an inst...
// suppress the admin-assign options since it would be empty anyway
//
if ( $_INST!=0 && count($users)>0 ) {
?>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td colspan="3" align="right">
        <label for="InstAdmin">Institutional Administrator</label>
        <select name="InstAdmin" id="InstAdmin">
         <option value="0">Assign an Adminstrator</option>
<?php
foreach ($users as $u) {
  print "         <option value=" . $u['user_id'];
  print " selected" ? ($u['user_id'] == $Inst['admin_userid']) : "";
  print ">" . $u['first_name'] . ", " . $u['last_name'] . "</option>\n";
}
?>
        </select>
      </td>
      <td>&nbsp;</td>
      <td colspan="3" align="left">
        <div id="AdminInfo">
          <strong>
          <a href="ManageUser.php?User=<?php echo $admin_user['user_id']; ?>">View User Data</a>
          </strong>
        </div>
      </td>
    </tr>
<?php
}
?>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td colspan="2" align="right"><label for="notes" vertical-align="top">Notes</label>
      <td align="left" colspan="5">
        <textarea class="wide" name="notes" id="notes"><?php echo $Inst['notes']; ?></textarea>
      </td>
    </tr>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr><td colspan="7" class="section">Provider Settings for this Institution</td></tr>
    <tr>
      <td colspan="2" align="right"><label for="Prov">Provider</label></td>
      <td align="left">
        <select id="Prov" name="Prov">
          <option value="">Choose a Provider</option>
<?php
// Populate the initial Provider list
//
foreach ($providers as $p) {
  print "          <option value=" . $p['prov_id'] . ">" . $p['name'] . "</option>\n";
}
?>
        </select>
      </td>
      <td colspan="4"><strong>
<?php
print "        <a href=\"" . CCPLUSROOTURL . "ManageProvider.php\">Add a Provider</a><br /><br />\n";
?>
      </strong></td>
    </tr>
<?php
  // SUSHI definitions are populated by Provider-select on-change jquery script
  //
?>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td colspan="3" class="section">SUSHI Definitions</td>
      <td colspan="4"><strong>
<?php
print "        <a href=\"" . CCPLUSROOTURL . "ImExSettings.php?View=prov\">Import/Export Provider Settings</a>\n";
?>
      </strong></td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Sushi_ReqID">RequestorID</label></td>
      <td align="left"><input type="text" id="Sushi_ReqID" name="Sushi_ReqID" /></td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Sushi_ReqName">Requestor Name</label></td>
      <td align="left"><input type="text" id="Sushi_ReqName" name="Sushi_ReqName" /></td>
      <td>&nbsp;</td>
      <td align="right"><label for="Sushi_CustID">Customer Reference ID</label></td>
      <td colspan="2" align="left"><input type="text" id="Sushi_CustID" name="Sushi_CustID" /></td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Sushi_ReqEmail">Requestor Email</label></td>
      <td align="left"><input type="text" id="Sushi_ReqEmail" name="Sushi_ReqEmail" /></td>
      <td>&nbsp;</td>
      <td align="right"><label for="Sushi_CustName">Customer Reference Name</label></td>
      <td colspan="2" align="left"><input type="text" id="Sushi_CustName" name="Sushi_CustName" /></td>
    </tr>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td colspan="3" class="section">Alias Name Definitions</td>
      <td colspan="4"><strong>
<?php
print "        <a href=\"" . CCPLUSROOTURL . "ImExSettings.php?View=name\">Import/Export Name Definitions</a>\n";
?>
      </strong></td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
      <td colspan="2" align="left"><input type="text" id="add_alias" name="add_alias"></td>
      <td><input type="button" id="AddRow" value="Add an alias" /></td>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tbody id="AliasNames">
    </tbody>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td>&nbsp;</td>
      <td colspan="5" align="center">
        <input type="submit" name="Save" value="Save" /> &nbsp; &nbsp; &nbsp; &nbsp;
        <input type="button" value="Reset" onClick="this.form.reset(); window.location.reload()" />
        &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" name="Cancel" value="Cancel" />
      </td>
      <td>&nbsp;</td>
    </tr>
  </table>
  </form>
<?php
}  // End-if no errors

// If errors, signal and stop
//
if ($ERR == 1) {
   print_noaccess_error();
} else if ( $ERR > 1 ) {
   print_page_header("CC-Plus Institution settings - Error");
   print "<blockquote>\n";
   print "<p>&nbsp;</p>\n<h3>Error on request</h3>\n";
   switch ($ERR) {
     case 2:
       print "<p><font size=\"+1\">A valid resource or vendor ID is required as input.</br />\n";
       break;
     default:
       print "Invalid arguments were provided.";
   }
   print "<br /><br /><a href='AdminHome.php'>Click here to return to the Administration Home Page.</a>\n";
   print "</font></p>\n";
   print "</blockquote>\n";
}

include 'ccplus/footer.inc.html.php';
?>
