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
// AlertSettings.php
//
// CC-Plus : Alert Definitions page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Only access_level=Admin gets to use this page
//
if ( $_SESSION['role'] == ADMIN_ROLE ) {

  // Pull the consortia info based on session variable
  //
  $_CON = ccp_get_consortia($_SESSION['ccp_con_id']);

  // Get alert settings and all active reports
  //
  $all_alerts = ccp_get_alert_settings();
  $all_reports = ccp_get_reports();
  $all_metrics = ccp_get_metrics_ui();

  // Build strings for report-names and metrics for JS to use
  //
  $enum_reports = "";
  foreach ( $all_reports as $_rpt ) {
    $enum_reports .= $_rpt['ID'] . ":" . $_rpt['Report_Name'] . "(v" . $_rpt['revision'] . "),";
  }
  $enum_reports = preg_replace("/,$/","",$enum_reports);
  $enum_metrics = "";
  foreach ( $all_metrics as $_met ) {
    $enum_metrics .= $_met['ID'] . ":" . $_met['legend'] . ",";
  }
  $enum_metrics = preg_replace("/,$/","",$enum_metrics);

  // Build page header and breadcrumb
  //
  print_page_header("CC-Plus Alert Settings: " . $_CON['name'],TRUE);

  // Form Validation
  //
  print "  <script type=\"text/javascript\" src=\"" . CCPLUSROOTURL . "include/validators.js\"></script>\n";
?>
  <script type="text/javascript">
    //// Function to test/warn if variance/#-months are zero
    function validateFormSubmit(theForm) {
      var reason = "";
      var bounds = document.getElementsByClassName('Num3');
      for (var i=0, len=bounds.length; i<len; i++) {
        if ( bounds[i].value==0 ) {
          if ( bounds[i].id!="A_variance" && bounds[i].id!="A_timespan" ) {
            reason  = "Alerts with variance or #-months set to zero will be deleted.\n";
            reason += "\nProceed?\n";
            i = bounds.length+1;
          }
        }
      }
      if (reason != "") {
        if (confirm(reason))
          return true;
        else
          return false;
      }
      return true;
    }
  </script>
<?php
  print "  <script src=\"" . CCPLUSROOTURL . "include/Alert_Settings.js\"></script>\n";
  $_form  = "<form name=\"AlertDefsForm\" id=\"AlertDefsForm\" method=\"post\"";
  $_form .= " onsubmit=\"return validateFormSubmit(this)\"";
  $_form .= " action=\"" . CCPLUSROOTURL . "UpdateAlertSet.php\" />\n";
  print $_form;
  print "  <input type=\"hidden\" name=\"enum_reports\" id=\"enum_reports\" value=\"$enum_reports\">\n";
  print "  <input type=\"hidden\" name=\"enum_metrics\" id=\"enum_metrics\" value=\"$enum_metrics\">\n";
  print "  <input type=\"hidden\" name=\"measure\" id=\"measure\" value=\"\">\n";
?>
  <table width="80%" class="centered">
    <tr>
      <td width="20%">&nbsp;</td>
      <td width="5%">&nbsp;</td>
      <td width="25%">&nbsp;</td>
      <td width="5%">&nbsp;</td>
      <td width="20%">&nbsp;</td>
      <td width="5%">&nbsp;</td>
      <td width="20%">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="7" class="notice">
        <p>Alerts are automatically set if/when a scheduled ingest fails or returns an error during processing.<br />
        The settings below provide additional tests for create alerts based on boudaries or conditions related
        to specific data values and metrics.&nbsp; If a defined alert is enabled (checked), emails will be sent to 
        to affiliated users who have opted in to receive them. Reports including "alerted" datasets will be annotated
        in the display screen(s).
      </td>
    </tr>
    <tr><td colspan="7">&nbsp;</td></tr>
<?php
// Build "Add an Alert" Row based on reports and metrics currently defined
?>
    <tr>
      <td class="section" colspan="7">
        Create an Alert: &nbsp; &nbsp;
<?php
// List all active reports available (including revision)
?>
        <label for="A_report"> for report </label>
        <select name="A_report" id="A_report">
          <option value="--">Choose a Report</option>
<?php
  foreach ( $all_reports as $_rpt ) {
    print "          <option value='" . $_rpt['ID'] . "'>" . $_rpt['Report_Name'] . "(v" . $_rpt['revision'] . ")</option>\n";
  }
?>
        </select>
        &nbsp; &nbsp;
<?php
// Metric values are populated after a report is chosen
?>
        <label for="A_metric"> ON </label> &nbsp; &nbsp;
        <select name="A_metric" id="A_metric">
          <option value=""></option>
        </select>
        &nbsp; &nbsp;
<?php
// Variance and timespan inputs
?>
        <label for="A_variance"> varying by +/-</label> &nbsp; &nbsp;
        <input type="text" class="Num3" name="A_variance" id="A_variance" value=0> percent &nbsp; &nbsp;
        <label for="A_timespan"> versus the past</label> &nbsp; &nbsp;
        <input type="text" class="Num3" name="A_timespan" id="A_timespan" value=0> month(s)
<?php
// The Add button stays hidden until a metric is chosen
?>
        &nbsp; &nbsp; &nbsp; <input type="button" id="AddAlert" value="Add Alert" />
      </td>
    </tr>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td align="center"><strong>Enable Alerts</strong></td>
      <td>&nbsp;</td>
      <td><strong>Whenever</strong></td>
      <td>&nbsp;</td>
      <td align="left"><strong>Varies by +/-</strong></td>
      <td>&nbsp;</td>
      <td align="left"><strong>Versus the past</strong></td>
    </tr>
<?php
// Initial display will bold currently defined (active or not) alerts
?>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tbody id="AllAlerts">
<?php
  foreach ( $all_alerts as $_alert ) {
    print "    <tr>\n";
    print "      <td align=\"center\"><input type=\"checkbox\" name=\"cb_" . $_alert['ID'] . "\" id=\"cb_" . $_alert['ID'] . "\"";
    if ( $_alert['active'] ) { print " checked"; }
    print " /></td><td>&nbsp;</td>\n";
    print "      <td align=\"left\">";
    print $_alert['Report_Name'] . "(v" . $_alert['revision'] . ") :: " . $_alert['legend'] . "</td><td>&nbsp;</td>\n";
    print "      <td align=\"left\"><input type=\"text\" class=\"Num3\" name=\"var_" . $_alert['ID'] . "\" id=\"var_" . $_alert['ID'] . "\"";
    print " value=" . $_alert['variance'] . " />&nbsp; %</td><td>&nbsp;</td>\n";
    print "      <td align=\"left\"><input type=\"text\" class=\"Num3\" name=\"time_" . $_alert['ID'] . "\" id=\"time_" . $_alert['ID'] . "\"";
    print " value=" . $_alert['timespan'] . " />&nbsp; month(s)</td>\n";
    print "    </tr>\n";
  }
?>
    </tbody>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td colspan="2">
      <td><input type="submit" name="Save" value="Save" /></td>
      <td>&nbsp;</td>
      <td><input type="button" value="Reset" onClick="this.form.reset(); window.location.reload()" />
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr><td colspan="7">&nbsp;</td></tr>
  </table>
<?php

// ---------------------------------------------------------------------------
// If not Admin, display an error screen with instructions and links
//
} else {
   print_noaccess_error();
}

// All done, close the page
//
include 'ccplus/footer.inc.html.php';
?>
