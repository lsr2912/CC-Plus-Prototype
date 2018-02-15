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
// javascript target script to return provider details 
// matching a given provider ID as a json array
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

// Get Provider fields
//
$_PID = 0;
if ( isset($_REQUEST['prov_id']) ) { $_PID=$_REQUEST['prov_id']; }
$provider = ccp_get_providers($_PID);

// Get reports and format as HTML drop-in for div's
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

// Build Formatted HTML for v4 and v5 checkbox rows
//
$v4_reports = "";
foreach ( $merged_reports['4'] as $_rpt ) {
  $v4_reports .= "          <label for='CB_" . $_rpt['ID'] . "'>" . $_rpt['Report_Name'] . "</label>";
  $v4_reports .= "<input type='checkbox' name='reports_v4[]' id='CB_" . $_rpt['ID'] . "' value='" . $_rpt['ID'] . "'";
  if ($_rpt['selected'] == "on") { $v4_reports .= " checked"; }
  $v4_reports .= " />&nbsp; &nbsp; \n";
}
$v5_reports = "";
foreach ( $merged_reports['5'] as $_rpt ) {
  $v5_reports .= "          <label for='CB_" . $_rpt['ID'] . "'>" . $_rpt['Report_Name'] . "</label>";
  $v5_reports .= "<input type='checkbox' name='reports_v5[]' id='CB_" . $_rpt['ID'] . "' value='" . $_rpt['ID'] . "'";
  if ($_rpt['selected'] == "on") { $v5_reports .= " checked"; }
  $v5_reports .= " />&nbsp; &nbsp; \n";
}

// Build and return JSON
//
$main = array('prov'=>$provider,'v4_reports'=>$v4_reports,'v5_reports'=>$v5_reports);
echo json_encode($main); 
?>
