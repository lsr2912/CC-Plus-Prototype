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
// AdminHome.php
//
// CC-Plus Administration homepage
//
ini_set('display_errors', 'On');
error_reporting(E_ALL);
include_once 'ccplus/sessions.inc.php';

include_once 'ccplus/auth.inc.php';

// Pull the consortia info based on session variable
//
$_CON = ccp_get_consortia($_SESSION['ccp_con_id']);

print_page_header("CC-Plus Administration Home: " . $_CON['name'], TRUE);
?>
  <table width="80%" class="centered">
<?php
// -----------------------------------------------------------------
// If not Admin, display an error screen with instructions and links
//
if ( $_SESSION['role'] != ADMIN_ROLE) {

   print_noaccess_error();

} else {

   // Get name/ID/email for all users in an array
   //
   $users = ccp_get_users_ui();
?>
    <tr>
      <td width="25%" class="section">Manage Users</td>
      <td width="50%">
<?php
print "        <form name=\"UserFrm\" id=\"U_form\" method=\"post\" action=\"" . CCPLUSROOTURL . "ManageUser.php\">\n";
print "        <script src=\"" . CCPLUSROOTURL . "include/Admin_Home.js\"></script>\n";
?>
        <table width="100%" class="centered">
          <tr>
            <td width="30%" align="left"><label for="User">Modify a User</label></td>
            <td align="left">
              <select name="User" id="User" onchange="this.form.submit()">
                <option value="">Choose a User</option>
<?php
foreach ($users as $u) {
  print "                <option value=" . $u['user_id'] . ">";
  print $u['last_name'] . ", " . $u['first_name'] . " (" . $u['email'] . ")</option>\n";
}
?>
              </select>
            </td>
          </tr>
        </table>
        </form>
      </td>
      <td width="25%" class="data"><strong>
<?php
print "        <a href=\"" . CCPLUSROOTURL . "ManageUser.php\">Add a User</a><br /><br />\n";
print "        <a href=\"" . CCPLUSROOTURL . "ListUsers.php\">Display all Users</a><br /><br />\n";
print "        <a href=\"" . CCPLUSROOTURL . "ImExSettings.php?View=user\">Import/Export User Settings</a>\n";
?>
      </strong></td>
    </tr>
    <tr><td colspan="3" align="center"><br /><hr><br /></td></tr>
<?php
   // Build HTML for "Institutions" section
   // --------------------------------------

   // Get all institution names to fill-out default view
   //
   $institution = ccp_get_institutions_ui();
?>
    <tr>
      <td width="25%" class="section">Member Institutions</td>
      <td align="center">
<?php
print "        <form name=\"InstFrm\" id=\"I_form\" method=\"get\" action=\"" . CCPLUSROOTURL . "ManageInst.php\">\n";
?>
        <table width="100%" cellspacing="0" cellpadding="2" border="0" align="center">
          <tr>
            <td align="left" width="30%"><label for="Itype">Institution Type</label></td>
            <td align="left">
              <select name="Itype" id="Itype">
                <option value="ALL" selected>ALL</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </td>
          </tr>
          <tr>
            <td align="left"><label for="Inst">Institutions</label></td>
            <td align="left">
              <select name="Inst" id="Inst" onchange="this.form.submit()">
                <option value="">Choose an Institution</option>
<?php
// Populate the initial institution list
//
foreach ($institution as $inst) {
  print "                <option value=\"" . $inst['inst_id'] . "\">" . $inst['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
        </table>
        </form>
      </td>
      <td width="25%" class="data"><strong>
<?php
print "        <a href=\"" . CCPLUSROOTURL . "ManageInst.php\">Add an Institution</a><br /><br />\n";
print "        <a href=\"" . CCPLUSROOTURL . "ImExSettings.php?View=inst\">Import/Export Institution Settings</a>\n";
?>
      </strong></td>
    </tr>
    <tr><td colspan="3" align="center"><br /><hr><br /></td></tr>
<?php
   // Get all provider names to fill-out default view
   //
   $providers = ccp_get_providers_ui();

   // Build HTML for "Providers" section
   // ----------------------------------
?>
    <tr>
      <td width="25%" class="section">Report Providers</td>
      <td align="center">
<?php
print "        <form name=\"ProvFrm\" id=\"P_form\" method=\"get\" action=\"" . CCPLUSROOTURL . "ManageProvider.php\">\n";
?>
        <table width="100%" class="centered">
          <tr>
            <td align="left" width="30%"><label for="Ptype">Provider Type</label></td>
            <td align="left">
              <select name="Ptype" id="Ptype">
                <option value="ALL" selected>ALL</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </td>
          </tr>
          <tr>
            <td align="left"><label for="Prov">Provider</label></td>
            <td align="left">
              <select name="Prov" id="Prov" onchange="this.form.submit()">
                <option value="">Choose a Provider</option>
<?php
// Populate the initial Provider list 
//
foreach ($providers as $p) {
  print "                <option value=" . $p['prov_id'] . ">" . $p['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
        </table>
        </form>
      </td>
      <td width="25%" class="data"><strong>
<?php
print "        <a href=\"" . CCPLUSROOTURL . "ManageProvider.php\">Add a Provider</a><br /><br />\n";
print "        <a href=\"" . CCPLUSROOTURL . "ImExSettings.php?View=prov\">Import/Export Provider Settings</a>\n";
?>
      </strong></td>
    </tr>
    <tr><td colspan="3" align="center"><br /><hr><br /></td></tr>
<?php
   // Build HTML for "Report Details" section
   // ---------------------------------------
?>
    <tr>
      <td width="25%" class="section">Report Details</td>
      <td align="center">
<?php
print "        <form name=\"ReptFrm\" id=\"R_form\" method=\"get\" action=\"" . CCPLUSROOTURL . "ReportDetail.php\">\n";
?>
        <table width="100%" class="centered">
          <tr>
            <td align="left" width="30%"><label for="R_Prov">Provider</label></td>
            <td align="left">
              <select name="R_Prov" id="R_Prov">
                <option value="">Choose a Provider</option>
<?php
// Populate the initial Provider list 
//
foreach ($providers as $p) {
  print "                <option value=" . $p['prov_id'] . ">" . $p['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
<?php
// Institutions are populated after provider is chosen
?>
          <tr>
            <td align="left"><label for="R_Inst">Institution</label></td>
            <td align="left">
              <select name="R_Inst" id="R_Inst">
                <option value=""></option>
              </select>
            </td>
          </tr>
<?php
// Timestamps are populated after institution is chosen
?>
          <tr>
            <td align="left"><label for="R_yearmon">Timestamp</label></td>
            <td align="left">
              <select name="R_yearmon" id="R_yearmon">
                <option value=""></option>
              </select>
            </td>
          </tr>
<?php
// Report names are populated after timestamp is chosen
?>
          <tr>
            <td align="left"><label for="R_report">Report</label></td>
            <td align="left">
              <select name="R_report" id="R_report" onchange="this.form.submit()">
                <option value=""></option>
              </select>
            </td>
          </tr>
        </table>
        </form>
      </td>
      <td width="25%" class="data"><strong>
<?php
print "        <a href=\"" . CCPLUSROOTURL . "ManualIngest.php\">Manual Report Ingest</a>\n";
?>
      </strong></td>
    </tr>
    <tr><td colspan="3" align="center"><br /><hr><br /></td></tr>
<?php
   // Build HTML for "Error Handling" section
   // ----------------------------------------
?>
    <tr>
      <td width="25%" class="section">Error Handling</td>
      <td align="center">
<?php
print "        <form name=\"AlrtFrm\" id=\"A_form\" method=\"get\" action=\"" . CCPLUSROOTURL . "AlertsDash.php\">\n";
?>
          <table width="100%" class="centered">
            <tr>
              <td align="left" width="30%"><label for="Atype">View Alerts</label></td>
              <td align="left">
                <select name="Astat" id="Astat" onchange="this.form.submit()">
                  <option value="--" selected>by status</option>
                  <option value="ALL">ALL</option>
<?php
  foreach ( ccp_get_enum_values("alerts","status") as $_stat ) {
    print "                  <option value=\"" . $_stat . "\">" . $_stat . "</option>\n";
  }
?>
                </select>
              </td>
            </tr>
          </table>
        </form>
      </td>
      <td width="25%" class="data"><strong>
<?php
print "        <a href=\"" . CCPLUSROOTURL . "AlertSettings.php\">Alert Definitions</a>\n";
?>
      </strong></td>
    </tr>
    <tr>
      <td align="left">&nbsp;</td>
      <td align="center">
<?php
print "        <form name=\"LogFrm\" id=\"Logform\" method=\"get\" action=\"" . CCPLUSROOTURL . "IngestLog.php\">\n";
?>
          <table width="100%" class="centered">
            <tr>
              <td align="left" width="30%"><label for="Istat">View Ingest Log</label></td>
              <td align="left">
                <select name="Istat" id="Istat" onchange="this.form.submit()">
                  <option value="--" selected>by status</option>
                  <option value="ALL">ALL</option>
<?php
  foreach ( ccp_get_enum_values("ingest_record","status") as $_stat ) {
    print "                  <option value=\"" . $_stat . "\">" . $_stat . "</option>\n";
  }
?>
                </select>
              </td>
            </tr>
          </table>
        </form>
      </td>
      <td>&nbsp;</td>
    </tr>
  </table>
<?php

}	// end-if ADMIN role
//
// All done, footer time
//
include 'ccplus/footer.inc.html.php';
?>
