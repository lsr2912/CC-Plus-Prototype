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
// AlertsDash.php
//
// CC-Plus Alerts dashboard page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Accept a status (default:Active) and provider_ID (default:0) as inputs
//
$_STAT='ALL';
if ( isset($_REQUEST['Astat']) ) { $_STAT = $_REQUEST['Astat']; }
$_PROV=0;
if ( isset($_REQUEST['prov_id']) ) { $_PROV = $_REQUEST['prov_id']; }

// Setup initial data for display 
//
$alerts = ccp_get_alerts($_STAT,$_PROV);
$providers = ccp_get_providers_ui();
$filt_prov = $providers;
array_unshift($filt_prov,array("name"=>"ALL","prov_id"=>0));
$alert_status = ccp_get_enum_values("alerts","status");
$filt_stat = $alert_status;
array_unshift($filt_stat,"ALL");
$enum_status = "";
foreach ( $alert_status as $_st ) {
  $enum_status .= $_st . ":";
  if ( strtoupper($_st) == $_STAT ) { $_STAT = $_st; }
 }
$enum_status = preg_replace("/:$/","",$enum_status);

// Check rights, set flag if user has management-site access
//
$is_admin = FALSE;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] == ADMIN_ROLE ) { $is_admin = TRUE; }
}

// Setup page and add view-dependent jQuery/Ajax refresh script
//
print_page_header("CC-Plus System Alerts",TRUE);
print "  <link href=\"" . CCPLUSROOTURL . "include/tablesorter_theme.css\" rel=\"stylesheet\">\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.widgets.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/widget-columnSelector.js\"></script>\n";
?>
  <style>
    /* override document styling */
    .popover.right { text-align: left; }
    .ui-widget-content a { color: #428bca; }
  </style>
<?php
print "  <link href=\"" . CCPLUSROOTURL . "include/SelectorPopup.css\" rel=\"stylesheet\">\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/SelectorPopup.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/Alert_Dash.js\"></script>\n";

// Setup Form and upper table
//
$action = "";
if ( $is_admin ) { $action = CCPLUSROOTURL . "UpdateAlerts.php"; }
print "<form name='AlertDash' id='AlertDash' method='post' action='$action'>\n";
print "  <input type=\"hidden\" name=\"enum_stat\" id=\"enum_stat\" value=\"$enum_status\">\n";

// Put out the form inputs
//
?>
  <table width="80%" class="centered">
    <tr>
      <td width="15%" align="left"><label for="filter_stat"><strong>Filter by Status</strong></label></td>
      <td width="20%" align="left">
        <select name="filter_stat" id="filter_stat">
<?php
foreach ( $filt_stat as $_st ) {
  print "          <option value=\"" . $_st . "\"";
  print ($_st == $_STAT) ? " selected" : "";
  print ">" . $_st . "</option>\n";
}
?>
        </select>
      </td>
      <td width="15%">&nbsp;</td>
<?php
if ( $is_admin ) {
  print "      <td width=\"50%\"><input type=\"button\" id=\"SilenceALL\" value=\"Silence ALL Alerts\" /></td>\n";
} else {
  print "      <td width=\"50%\">&nbsp;</td>\n";
}
print "    </tr>\n    <tr>\n";
if ( $is_admin ) {
  print "      <td colspan=\"3\">&nbsp;</td>\n";
  print "      <td><input type=\"button\" id=\"ActiveALL\" value=\"Activate ALL Alerts\" /></td>\n";
} else {
  print "      <td colspan=\"4\">&nbsp;</td>\n";
}
print "    </tr>\n";
?>
    <tr>
      <td align="left"><label for="filter_prov"><strong>Filter by Provider</strong></label></td>
      <td align="left">
        <select name="filter_prov" id="filter_prov">
<?php
foreach ( $filt_prov as $_prov ) {
   print "          <option value=\"" . $_prov['prov_id'] . "\"";
   print ($_prov['prov_id'] == $_PROV) ? " selected" : "";
   print ">" . $_prov['name'] . "</option>\n";
}
?>
        </select>
      </td>
      <td>&nbsp;</td>
<?php
if ( $is_admin ) {
  print "      <td><input type=\"button\" id=\"DeleteALL\" value=\"Mark ALL Alerts For Deletion\" /></td>\n";
} else {
  print "      <td>&nbsp;</td>\n";
}
print "    </tr>\n";
?>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>
  </table>
  <center>
<?php
// Build initial data table
//
print "  <table id=\"data_table\" class=\"tablesorter\" cellpadding=\"2\">\n";
?>
    <thead>
      <tr>
        <th id="status"      width='10%' align='left'>Status</th>
        <th id="yearmon"     width='10%' align='center'>Year-Month</th>
        <th id="condition"   width='25%' align='center'>Condition</th>
        <th id="report"      width='15%' align='center'>Report</th>
        <th id="provider"    width='15%' align='left'>Provider</th>
        <th id="last_update" width='15%' align='center'>Last Updated</th>
        <th id="modified_by" width='15%' align='right'>Modified By</th>
      </tr>
    </thead>
    <tbody id="Summary">
<?php
// Display initial data; form inputs will allow user to refresh and/or sort it 
//
foreach ( $alerts as $_alert ) {

  // Status is a dropdown for admins, text otherwise
  //
  print "      <tr>\n        <td align='left'>";
  if ( $is_admin ) {
    print "\n          <select name='stat_" . $_alert['ID'] . "' id='stat_" .  $_alert['ID'] . "'>\n";
    foreach ( $alert_status as $_stat ) {
      print "            <option value=\"" . $_stat . "\"";
      print ($_stat == $_alert['status']) ? " selected" : "";
      print ">" . $_stat . "</option>\n";
    }
    print "          </select>\n        </td>\n";
  } else {
    print $_alert['status'] . "</td>\n";
  }

  // YYYY-MM column
  //
  print "        <td align='center'>";
  if ( $_alert['yearmon'] == "" ) {
   print "--</td>\n";
  } else {
   print $_alert['yearmon'] . "</td>\n";
  }

  // Display Metrics.legend as the "condition" when settings_id is set,
  // otherwise display failed ingest details
  //
  if ( $_alert['settings_id'] != 0 ) {
    print "        <td align='center'>" . $_alert['legend'] . "</td>\n";
  } else {
    print "        <td align='center'>Ingest Failed: " . $_alert['detail'] . "</td>\n";
  }
  print "        <td align='center'>" . $_alert['Report_Name'] . "</td>\n";

  // Provider column holds links to management site if $is_admin
  //
  print "        <td align='center'>";
  if ( $_alert['prov_id'] == 0 ) {
   print "--</td>\n";
  } else {
   print ( $is_admin) ?
      "<a href='/ManageProvider.php?Prov=".$_alert['prov_id']."'>".$_alert['prov_name']."</a></td>\n" :
      $_alert['prov_name'] . "</td>\n";
  }
  print "        <td align='center'>" . $_alert['ts_date'] . "</td>\n";
  print "        <td align='center'>";
  $_modby = "--";
  if ( is_numeric($_alert['modified_by']) ) {
    if ( $_alert['modified_by'] == 0 ) {
      $_modby = "CC-Plus System";
    } else {
      $_modby = $_alert['user_name'];
    }
  }
  print $_modby . "</td>\n";
  print "      </tr>\n";
}
?>
    </tbody>
  </table>
<?php
if ( $is_admin ) {
?>
  <p>
    <input type="submit" name="Save" value="Save Changes" /> &nbsp; &nbsp; &nbsp; &nbsp;
    <input type="button" value="Reset Form" onClick="this.form.reset(); window.location.reload()" />
  </p>
  </center>
<?php
}
?>
  </form>
<?php
//
// All done, footer time
//
include 'ccplus/footer.inc.html.php';
?>
