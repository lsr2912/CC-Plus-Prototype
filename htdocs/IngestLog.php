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
// IngestLog.php
//
// CC-Plus Ingest Log display page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';
include_once 'ccplus/statsutils.inc.php';

// Accept a status (default:ALL) as input
//
$_STAT="ALL";
if ( isset($_REQUEST['Istat']) ) { $_STAT = trim($_REQUEST['Istat']); }

// Setup initial data for display 
// hardcoded to start@Jan_2017... if we start purging records, this
// should connect to the expected minimum start date in the log... or
// make a function to go get date of the oldest record in the log
//
$filt_date = array();
$cur_yr = date("Y");
$cur_mo = date("n");
for ( $_yr=2017; $_yr<=$cur_yr; $_yr++ ) {
  for ( $_mo=1; $_mo<=12; $_mo++ ) {
    if ( $_yr==$cur_yr && $_mo>$cur_mo) { break; }
    $str = "$_yr-" . sprintf("%02d",$_mo);
    array_push($filt_date,$str);
  }
}
$from = date("Y-m", strtotime("-2 months"));
$to = date("Y-m", strtotime("-1 months"));
$records = ccp_get_ingest_record( 0, 0, $from, $to, $_STAT);
$providers = ccp_get_providers_ui();
$institutions = ccp_get_institutions_ui();
$reports = ccp_get_counter_reports_ui(0,0);

// Check rights, set flag if user has management-site access
//
$Manager = FALSE;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] <= MANAGER_ROLE ) { $Manager = TRUE; }
}

// Setup page header differently if Admin.vs.Manager
//
$_title = "CC-Plus Ingest Log : ";
if ( $_SESSION['role'] == ADMIN_ROLE) {
  $_CON = ccp_get_consortia($_SESSION['ccp_con_id']);
  $_title .= $_CON['name'];
} else {
  $_INST = ccp_get_institutions($_SESSION['user_inst']);
  $_title .= $_INST['name'];
}

// Setup page and add jQuery/Ajax refresh script
//
// $crumbs = array();	// no crumbs this page
print_page_header($_title,TRUE);
print "  <link href=\"" . CCPLUSROOTURL . "include/tablesorter_theme.css\" rel=\"stylesheet\">\n";
print "  <link href=\"" . CCPLUSROOTURL . "include/jquery.qtip.min.css\" rel=\"stylesheet\">\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.widgets.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.qtip.min.js\"></script>\n";
print "<script src=\"include/Ingest_Log.js\"></script>\n";

// Setup Form and upper table
//
print "<form name='IngestLogFrm' id='IngestLog'>\n";

// Put out the form inputs
//
?>
  <table width="80%" class="centered">
    <tr>
      <td width="20%" align="left"><label for="filter_prov"><h4>Filter by Provider</h4></label></td>
      <td width="2%">&nbsp;</td>
      <td width="28%" align="left">
        <select name="filter_prov" id="filter_prov">
          <option value="ALL" selected>ALL</option>
<?php
foreach ( $providers as $_prov ) {
   print "          <option value=\"" . $_prov['prov_id'] . "\"";
   print ">" . $_prov['name'] . "</option>\n";
}
?>
        </select>
      </td>
<?php
  if ( $_SESSION['role'] == ADMIN_ROLE) {
?>
      <td width="20%" align="left"><label for="filter_inst"><h4>Filter by Institution</h4></label></td>
      <td width="2%">&nbsp;</td>
      <td width="28%" align="left">
        <select name="filter_inst" id="filter_inst">
          <option value="ALL" selected>ALL</option>
<?php
    foreach ( $institutions as $_inst ) {
      print "          <option value=\"" . $_inst['inst_id'] . "\"";
      print ($_inst['inst_id'] == 0) ? " selected" : "";
      print ">" . $_inst['name'] . "</option>\n";
    }
?>
        </select>
      </td>
<?php
   } else {
     print "      <td colspan=\"3\">";
     print "<input type='hidden' name='filter_inst' value='".$_SESSION['user_inst']."'></td>\n";
   }
?>
    </tr>
    <tr>
      <td align="left"><label for="filter_stat"><h4>Completion Status</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="filter_stat" id="filter_stat">
          <option value="ALL" selected>ALL</option>
<?php
  foreach ( ccp_get_enum_values("ingest_record","status") as $_stat ) {
    print "          <option value=\"" . $_stat . "\"";
    print ($_STAT == $_stat) ? " selected" : "";
    print ">" . $_stat . "</option>\n";
  }
?>
        </select>
      </td>
      <td align="left"><label for="filter_rept"><h4>Filter by Report Name</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="filter_rept" id="filter_rept">
          <option value="ALL" selected>ALL</option>
<?php
foreach ( $reports as $_rept ) {
   print "          <option value=\"" . $_rept['ID'] . "\">";
   print $_rept['Report_Name'] . " (v" . $_rept['revision'] . ")</option>\n";
}
?>
        </select>
      </td>
    </tr>
    <tr>
      <td align="left"><label for="filter_from"><h4>Filter Usage Dates</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="filter_from" id="filter_from">
<?php
foreach ( $filt_date as $_ym ) {
   print "          <option value=\"" . $_ym . "\"";
   print ($_ym == $from) ? " selected" : "";
   print ">" . $_ym . "</option>\n";
}
?>
        </select>
        <label for="filter_to"><strong>&nbsp; To &nbsp;</strong></label>
        <select name="filter_to" id="filter_to">
<?php
foreach ( $filt_date as $_ym ) {
   print "          <option value=\"" . $_ym . "\"";
   print ($_ym == $to) ? " selected" : "";
   print ">" . $_ym . "</option>\n";
}
?>
        </select>
      </td>
    </tr>
    <tr><td colspan="6">&nbsp;</td></tr>
  </table>
  <center>
<?php
// Build initial data table
//
print "  <table id=\"data_table\" class=\"tablesorter\" cellpadding=\"2\">\n";
?>
    <thead>
      <tr>
        <th id="provider" align='left'>Provider</th>
        <th id="institution" align='left'>Institution</th>
        <th id="report" align='center'>Report</th>
        <th id="yearmon" align='center'>Usage Date</th>
        <th id="status" align='center'>Status</th>
        <th id="status" align='center'>Run Date</th>
      </tr>
    </thead>
    <tbody id="Summary">
<?php
// Display initial data; form inputs will allow user to rebuild and/or sort it 
//
if ( count($records) > 0 ) {

  $__inst_id = 0;
  if ( $_SESSION['role'] != ADMIN_ROLE) { $__inst_id = $_INST['inst_id']; }
  $records = ccp_get_ingest_record( 0, $__inst_id, $from, $to, $_STAT);

  foreach ( $records as $_rec ) {
    print "      <tr>\n";
    print "        <td align='left'>" . $_rec['prov_name'] . "</td>\n";
    print "        <td align='left'>" . $_rec['inst_name'] . "</td>\n";
    $_name = $_rec['report_name'];
    if ( $Manager && $_rec['status'] == 'Saved' ) {
      $_name  = "<a href=\"ReportDetail.php?R_Prov=" . $_rec['prov_id'];
      $_name .= "&R_Inst=" . $_rec['inst_id'] . "&R_yearmon=" . $_rec['yearmon'];
      $_name .= "&R_report=" . $_rec['ID'] . "\">" . $_rec['report_name'] . "</a>";
    }
    print "        <td align='center'>" . $_name . "</td>\n";
    print "        <td align='center'>" . $_rec['yearmon'] . "</td>\n";
    print "        <td align='center'";
    if ($_rec['status']=='Saved') {
      print " class=\"ing_succ\">Saved</td>\n";
    } else if ($_rec['status']=='Failed') {
      print " class=\"ing_fail\"";
      if ( $_rec['failed_ID'] != "" ) { print " FID=\"" . $_rec['failed_ID'] . "\""; }
      print ">Failed</td>\n";
    } else if ($_rec['status']=='Deleted') {
      print " class=\"ing_dele\">Deleted</td>\n";
    } else {
      print ">&nbsp;</td>\n";
    }
    $_TS = date("Y-m-d", strtotime($_rec['timestamp']) );
    print "        <td align='center'>" . $_TS . "</td>\n";
    print "      </tr>\n";
  }

} else {
  print "      <tr><td colspan=5 align='center'><p><strong>";
  print "No matching records for search criteria</strong></p></td></tr>\n";
}
?>
    </tbody>
  </table>
  </center>
</form>
<?php
include 'ccplus/footer.inc.html.php';
?>
