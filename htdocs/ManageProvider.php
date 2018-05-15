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
// ManageProvider.php
//
// CC-Plus institution management page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Build breadcrumbs, pass to page header builder
//
print_page_header("CC-Plus Provider Management",TRUE);

// Set $_PID to form value
//
$_PID = 0;
if ( isset($_REQUEST['Prov']) ) { $_PID = $_REQUEST['Prov']; }

// if ( $_PID != 0 ) {
  // form value for 'Ptype' only matters when not creating
  //
//   $_TYP = "ALL";
//   if ( isset($_REQUEST['Ptype']) ) { $_TYP = $_REQUEST['Ptype']; }
// }

// Only Admins use this page
//
$_role = USER_ROLE;
if ( isset($_SESSION['role']) ) { $_role = $_SESSION['role']; }
if ( $_role == ADMIN_ROLE ) {

  // Setup arrays to hold initial/default form values
  //
  $_provider = array();
  $_auth_types = ccp_get_enum_values("provider","security");

  // Setup the reports options
  //
  $all_reports = ccp_get_counter_reports_ui();
  $enabled_reports = ccp_get_counter_reports($_PID);

  // Combine the 2 arrays into one, including a "selected" value for each row
  //
  $merged_reports = array();
  $merged_reports['4'] = array();
  $merged_reports['5'] = array();
  foreach ($all_reports as $a_rpt) {
    $a_rpt['selected'] = "off";
    foreach ($enabled_reports as $e_rpt) {
      if ( $a_rpt['ID'] == $e_rpt['ID'] ) { $a_rpt['selected'] = "on"; }
    }
    array_push($merged_reports[$a_rpt['revision']], $a_rpt);
  }

  // IF $_PID=0 , we're creating a provider - set initial fields
  //
  if ( $_PID == 0 ) {
    $_provider['name'] = "";
    $_provider['active'] = 0;
    $_provider['server_url'] = "";
    $_provider['security'] = "None";
    $_provider['day_of_month'] = 15;
    $_provider['reports'] = array();
    $_provider['auth_username'] = "";
    $_provider['auth_password'] = "";

  // If viewing/editing an existing record, query the database
  // for current field values
  } else {
    $_all_providers = ccp_get_providers_ui();
    $_provider = ccp_get_providers($_PID);
    $_reports['4'] = ccp_get_counter_reports($_PID,4);
    $_reports['5'] = ccp_get_counter_reports($_PID,5);
  }

  // Setup Javascript functions and form
  //
  print "  <script src=\"" . CCPLUSROOTURL . "include/Manage_Prov.js\"></script>\n";
  print "<form name=\"Provider_Form\" id=\"Providerform\" method=\"post\" action=\"UpdateProvider.php\">\n";
  if ( $_PID == 0 ) {
    print " <input type=\"hidden\" name=\"Prov\" id=\"Prov\" value=0 />\n";
  }
?>
  <table width="80%" class="centered">
    <tr>
      <td width="15%">&nbsp;</td>
      <td width="30%">&nbsp;</td>
      <td width="5%">&nbsp;</td>
      <td width="15%">&nbsp;</td>
      <td width="35%">&nbsp;</td>
    </tr>
<?php
  // Build top section differently if creating new vendor
  //
  if ( $_PID == 0 ) {
?>
    <tr><td colspan="5" align="center"><h3>Creating New Provider</h3></td></tr>
    <tr>
      <td align="right"><label for="Pname">Provider Name</label></td>
      <td align="left">
        <input type="text" id="Pname" name="Pname" value="">
        <font color="red">  (*)</font>
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="Pstat">Provider Status</label></td>
      <td align="left">
        <select name="Pstat" id="Pstat">
          <option value=1 selected>Active</option>
          <option value=0>Inative</option>
        </select>
      </td>
    </tr>
<?php

  // For existing provders, selector with all providers (onchange will repaint using JS)
  //
  } else {
?>
    <tr><td colspan="5">&nbsp;</td></tr>
    <tr>
      <td align="right"><label for="Prov">Provider</label></td>
      <td align="left">
        <select name="Prov" id="Prov">
<?php
    // Populate the initial Provider list
    //
    foreach ($_all_providers as $p) {
      print "          <option value=" . $p['prov_id'];
      if ( $_provider['prov_id'] == $p['prov_id'] ) { print " selected "; }
      print ">" . $p['name'] . "</option>\n";
    }
?>
        </select>
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="Pid">Provider ID</label>:</td>
      <td align="left">
        <strong><div id="Pid"><?php print $_provider['prov_id'] ?></div></strong>
      </td>
    </tr>
    <tr><td colspan="5">&nbsp;</td></tr>
    <tr>
      <td align="right"><label for="Pname">Name</label></td>
      <td align="left">
        <input type="text" id="Pname" name="Pname" value="<?php print $_provider['name'] ?>">
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="Pstat">Provider Status</label></td>
      <td align="left">
        <select name="Pstat" id="Pstat">
<?php
    print "          <option value=1";
    print ($_provider['active'] == 1 ) ? " selected" : "";
    print ">Active</option>\n";
    print "          <option value=0";
    print ($_provider['active'] == 0 ) ? " selected" : "";
    print ">Inactive</option>\n";
?>
        </select>
      </td>
    </tr>
    <tr><td colspan="5">&nbsp;</td></tr>
<?php
  }
  //
  // Build remaining form fields regardless of new/existing
  //
?>

    <tr>
      <td align="right"><label for="Sushi_URL">Sushi Server URL</label></td>
      <td align="left">
<?php
  print "        <input type=\"text\" id=\"Sushi_URL\" name=\"Sushi_URL\" class=\"URLtext\"";
  print " value=\"" . $_provider['server_url'] . "\" />\n";
?>
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="Sushi_Day">Ingest reports on day</label></td>
      <td align="left">
<?php
  print "        <input type=\"text\" id=\"Sushi_Day\" name=\"Sushi_Day\" class=\"Mintext\"";
  print " value=\"" . $_provider['day_of_month'] . "\" />\n";
  print " &nbsp; <strong> of every month</strong>\n";
?>
      </td>
    </tr>
    <tr><td colspan="5">&nbsp;</td></tr>
    <tr>
      <td align="right"><label for="Sushi_Auth">Sushi Authentication Type</label></td>
      <td align="left">
        <select id="Sushi_Auth" name="Sushi_Auth">
<?php
  foreach ($_auth_types as $_typ) {
    print "          <option value=\"" . $_typ . "\"";
    if ( $_typ == $_provider['security'] ) { print " selected "; }
    print ">" . $_typ . "</option>\n";
  }
?>
        </select>
      </td>
      <td colspan="3" rowspan="2" align="left">
        <div id="AuthCreds">
          <label for="Sushi_User">Auth-Username: &nbsp;</label>
          <input type="text" id="Sushi_User" name="Sushi_User" value="<?php echo $_provider['auth_username']; ?>" /><br />
          <label for="Sushi_Pass">Auth-Password: &nbsp;</label>
          <input type="text" id="Sushi_Pass" name="Sushi_Pass" value="<?php echo $_provider['auth_password']; ?>" />
        </div>
      </td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
<?php
  // display checkboxes for supported reports and build a string to be
  // put in a hidden variable for use by JS on provider selector change
  //
  if ( count($merged_reports['4']) > 0 ) {
?>
    <tr>
      <td align="right"><label for="reports_v4">Ingest COUNTER-4 Reports:</label></td>
      <td colspan="4" align="left"> &nbsp;
        <div id="C4_Reports">
<?php
    foreach ( $merged_reports['4'] as $_rpt ) {
      print "          <label for=\"CB_" . $_rpt['ID'] . "\">" . $_rpt['Report_Name'] . "</label>";
      print "<input type=\"checkbox\" name=\"reports_v4[]\" id=\"CB_" . $_rpt['ID'] . "\" value=\"" . $_rpt['ID'] . "\"";
      print ($_rpt['selected'] == "on") ? " checked" : "";
      print " />&nbsp; &nbsp; \n";
    }
?>
        </div>
      </td>
    </tr>
<?php
  }
  if ( count($merged_reports['5']) > 0 ) {
?>
    <tr>
      <td align="right"><label for="reports_v5">Ingest COUNTER-5 Reports:</label></td>
      <td colspan="4" align="left"> &nbsp;
        <div id="C5_Reports">
<?php
    foreach ( $merged_reports['5'] as $_rpt ) {
      print "          <label for=\"CB_" . $_rpt['ID'] . "\">" . $_rpt['Report_Name'] . "</label>";
      print "<input type=\"checkbox\" name=\"reports_v5[]\" id=\"CB_" . $_rpt['ID'] . "\" value=\"" . $_rpt['ID'] . "\"";
      print ($_rpt['selected'] == "on") ? " checked" : "";
      print " />&nbsp; &nbsp; \n";
    }
?>
        </div>
      </td>
    </tr>
<?php
  }
?>
    <tr>
      <td>&nbsp;</td>
      <td align="right">
        <input type="submit" name="Save" value="Save">
      </td>
      <td>&nbsp;</td>
      <td align="right">
        <input type="button" name="Cancel" value="Cancel">
      </td>
      <td align="right"><font size="+1">
        <a href="ConfirmDelete.php?prov=<?php echo $_PID; ?>">Delete This Provider</a>
      </font></td>
    </tr>
  </table>
  </form>
<?php

// User-privilege error - not Admin or Editor...
//
} else {
   print_noaccess_error();
}
include 'ccplus/footer.inc.html.php';
?>
