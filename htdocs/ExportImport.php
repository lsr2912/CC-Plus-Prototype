<?php
//
// ManageExIm.php
//
// CC-Plus Administrative data Export-Import page
//
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

// Handle allowed input variables;
// View has no default, but must be one of: "user", "prov", "inst",  or "name"
//
$VIEW = "";
if ( isset($_REQUEST['View'])) {
  $VIEW = $_REQUEST['View'];
  if ( ($VIEW!="user") && ($VIEW!="prov") && ($VIEW!="inst") && ($VIEW!="name") ) {
    $VIEW = "";  // if unrecognized, act like nothing given
  }
}

// If view non-null, get initial data elements
//
switch ($VIEW) {
  case: "user":
    $all_insts = ccp_get_institutions_ui();
    $all_roles = ccp_get_roles_ui();
  case: "prov":
    $all_insts = ccp_get_institutions_ui();
  case: "inst":
    $all_providers = ccp_get_providers_ui();
  case: "name":
    $all_providers = ccp_get_providers_ui();
    $all_insts = ccp_get_institutions_ui();
}

// Build breadcrumbs, pass to page header builder
//
$crumbs = array();
array_push($crumbs,array("Home", CCPLUSROOTURL . "AdminHome.php"));
array_push($crumbs,array("ImportExport",""));
print_page_header("CC-Plus Export-Import",TRUE,$crumbs);

// Add jQuery/Ajax scripts and init tablesorter
//
// print "<script src=\"" . CCPLUSROOTURL . "include/Manage_ExIm.js\"></script>\n";
?>
<script type="text/javascript">
  $(document).ready(function() {
    // Initialize tablesorter
    //
    $('table').tablesorter();
    // Initial display will have date-range form inputs set for by-fiscalYr
    //
    $('#ControlByFY').show();
    $('#ControlByMO').hide();
    // Initial state of category dropdown depends on view
    //
    if ( $("#View").val()=="resource") {
      $('#FilterCat').show();
    } else {
      $('#FilterCat').hide();
    }
    // Download and Export button actions
    //
    $('#CustomEXP').click(function() {
      $('<input />').attr('type', 'hidden')
        .attr('name', "EXPTYPE")
        .attr('value', "Custom")
        .appendTo('#StatsDashFrm');
      $('#StatsDashFrm').submit();
    });
    $('#RawDownload').click(function() {
      $('<input />').attr('type', 'hidden')
        .attr('name', "EXPTYPE")
        .attr('value', "Raw")
        .appendTo('#StatsDashFrm');
      $('#StatsDashFrm').submit();
    });
  });
</script>
<center>
<table width="95%" cellspacing="0" cellpadding="0">
  <tr>
<?php
print "    <td width=\"50%\" align=\"left\">";
if ( $Manager ) {
  print "&nbsp; &nbsp;<strong><a href=\"/manage_home.php\">&nbsp;Management Home</a></strong>";
} else {
  print "&nbsp;";
}
print "</td>\n";
?>
    <td width="50%" align="right">
      <strong>View Dashboard : &nbsp; &nbsp;
      <a href="/financials_dash.php">Financials</a> &nbsp; | &nbsp;
      <a href="/alerts_dash.php">Alerts</a></strong>
    </td>
  </tr>
</table>
<?php
// Build a simple summary of alerts with links to the counts for each
// alert status (if session indicates user wants to see them).
//
if ($_SESSION['statdash_alerts']) {
  $message = "  <p align='left'>&nbsp; &nbsp;";
  if ( $alert_counts['Total'] == 0 ) {
    print "<div id=\"InfoAlerts\" style=\"width:80%;background-color:#00FF00\">\n";
    $message .= "No alerts are currently set</p>\n";
  } else {
    print "<div id=\"InfoAlerts\" style=\"width:80%;background-color:#FFFF00\">\n";
    $message .= ($alert_counts['Total'] == 1) ? "There is " : "There are ";
    $message .= "currently &nbsp; <a href='/alerts_dash.php?status=ALL'>" . $alert_counts['Total'];
    $message .= " statistics alerts set </a> &nbsp; : ";
    foreach ( $alert_status as $_stat ) {
      if ( $alert_counts[$_stat] == 0 ) { continue; }
      $message .= " &nbsp; &nbsp; <a href='/alerts_dash.php?status=" . $_stat . "'>";
      $message .= $alert_counts[$_stat];
      $message .= ($alert_counts[$_stat] == 1) ? " is " : " are " ;
      $message .= ($_stat == "Delete") ? "marked for Deletion" : $_stat;
      $message .= "</a> &nbsp; &nbsp; ,";
    }
    $message = preg_replace('/,$/',"",$message);                // zap trailing comma
    $message .= "    </p>\n";
  }
  print $message;
  print "</div>\n";
}
?>
</center>
<?php
// Setup Form and hidden variables
//
print "<form name='StatsDashFrm' id='StatsDashFrm' method='post' action='/stats_export.php'>\n";
print "  <input type='hidden' name='VID' id='VID' value='" . $_VID . "'>\n";
print "  <input type='hidden' name='View' id='View' value=" . $view . ">\n";

// Start the main table and create the form inputs 
//
?>
  <table width="85%" cellspacing="0" cellpadding="0" border="0" align="center">
    <tr>
      <td width="15%" align="left"><label for="Sum_Type"><h4>Summary Type</h4></label></td>
      <td width="2%">&nbsp;</td>
      <td width="50%" align="left">
        <select name="Sum_Type" id="Sum_Type"
                onchange="window.document.location.href=this.options[this.selectedIndex].value;" value="GO">
<?php
    if ( $view == "vendor" ) {
      print "          <option value=\"#\" selected>By-Vendor</option>\n";
      print "          <option value=\"/stats_dash.php?View=resource\">By-Resource</option>\n";
    } else {
      print "          <option value=\"/stats_dash.php?View=vendor\">By-Vendor</option>\n";
      print "          <option value=\"#\" selected>By-Resource</option>\n";
    }
?>
        </select>
<?php
if ( $_VID!=0 || $view=="resource" ) {
    print "&nbsp; &nbsp; &nbsp; <label for=\"filter_vendor\">for vendor: </label>\n";
    print "        <select name=\"filter_vendor\" id=\"filter_vendor\">\n";
    print "          <option value=\"0\" selected>ALL</option>\n";
    foreach ( $filt_vendors as $_vend ) {
      if ( !in_array($_vend['vend_id'],$vendors_with_stats) ) { continue; }
      print "          <option value=\"" . $_vend['vend_id'] . "\"";
      print ( $_VID == $_vend['vend_id'] ) ? " selected" : "";
      print ">" . $_vend['name'] . "</option>\n";
    }
    print "        </select>\n";
}
?>
      </td>
      <td width="33%" align="left">&nbsp;</td>
    </tr>
    <tr id="FilterCat">
      <td align="left"><label for="filter_category"><h4>Filter by Category</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="filter_category" id="filter_category">\n";
<?php
    foreach ( $filt_cats as $_cat ) {
      print "          <option value=\"" . $_cat['ID'] . "\"";
      print ( $_cat['ID'] == 0 ) ? " selected" : "";
      print ">" . $_cat['name'] . "</option>\n";
    }
?>
        </select>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td align="left"><label for="SumBy"><h4>Summarize usage by</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="SumBy" id="SumBy">
          <option value="FY" selected>Fiscal Year</option>
          <option value="MO">Month</option>
        </select>
      </td>
      <td align="right">
        <input type="button" id="CustomEXP" value="Export Custom Report" />
      </td>
    </tr>

    <!-- Inputs for summarizing usage by Fiscal-Yr -->

    <tr id="ControlByFY">
      <td align="left"><label for="fromFY"><h4>Filter usage by F-Y</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="fromFY" id="fromFY">
<?php
foreach ( $fiscal_years as $id=>$name ) {
  print "          <option value=\"" . $id . "\"";
  print ( $name == $curFY ) ? " selected" : "";
  print ">" . $name . "</option>\n";
}
?>
        </select>
        <label for="fromFY"> &nbsp; <strong>Through</strong> &nbsp; </label>
        <select name="toFY" id="toFY">
<?php
foreach ( $fiscal_years as $id=>$name ) {
  print "          <option value=\"" . $id . "\"";
  print ( $name == $curFY ) ? " selected" : "";
  print ">" . $name . "</option>\n";
}
?>
        </select> &nbsp; &nbsp;
        <input type="button" id="FilterDate" value="Apply" />
      </td>
      <td align="right">
        <input type="button" id="RawDownload" value="Download Raw Data" />
      </td>
    </tr>
 
    <!-- Inputs for summarizing usage by #-months -->

    <tr id="ControlByMO">
      <td align="left"><label for="from_month"><h4>Filter usage from:</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="from_month" id="from_month">
<?php
// From-month dropdown
//
for ($_m=1; $_m<=12; $_m++) {
  print "          <option value=\"" . sprintf("%02d",$_m) . "\"";
  if ( $_m == $from_mo ) { print " selected"; }
  print ">" . $months[$_m-1] . "</option>\n";
}
?>
        </select> &nbsp; &nbsp;
        <select name="from_year" id="from_year">
<?php
// From-Year dropdown
//
for ($_y=$from_yr; $_y<=$to_yr; $_y++) {
  print "          <option value=\"" .$_y. "\"";
  if ( $_y == $from_yr ) { print " selected"; }
  print ">" . $_y . "</option>\n";
}
?>
        </select> &nbsp; &nbsp;
        <label for="to_month"><strong> through:</strong></label> &nbsp; &nbsp;
        <select name="to_month" id="to_month">
<?php
// To-month dropdown
//
for ($_m=1; $_m<=12; $_m++) {
  print "            <option value=\"" . sprintf("%02d",$_m) . "\"";
  if ( $_m == $to_mo ) { print " selected"; }
  print ">" . $months[$_m-1] . "</option>\n";
}
?>
        </select> &nbsp; &nbsp;
        &nbsp; &nbsp;
        <select name="to_year" id="to_year">
<?php
// To-Year dropdown
//
for ($_y=$from_yr; $_y<=$to_yr; $_y++) {
  print "            <option value=\"" .$_y. "\"";
  if ( $_y == $to_yr ) { print " selected"; }
  print ">" . $_y . "</option>\n";
}
?>
        </select> &nbsp; &nbsp;
        <input type="button" id="FilterDate" value="Apply" />
      </td>
      <td align="right">
        <input type="button" id="RawDownload" value="Download Raw Data" />
      </td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>
    <tr>
      <td align="left" colspan="3">
        <input type="checkbox" name="filter_active" id="filter_active" value="Yes" checked="checked">
        &nbsp; &nbsp;
<?php
print "        <label for=\"filter_active\"><strong>Include only active";
print ( $view == "vendor" ) ? " vendors" : " resources";
print "</strong></label>\n";
?>
      </td>
      <td align="right">
        <strong><a href="/ingest_log.php">View Statistics Ingest Log</a></strong>
      </td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>
<?php
// Upper table and form ends
//
?>
  </table>
</form>
<?php
// Data table begins
//
?>
<div class="columnSelectorWrapper">
  <input id="colSelect1" type="checkbox" class="hidden">
  <label class="columnSelectorButton" for="colSelect1">Add or Drop Columns</label>
  <div id="columnSelector" class="columnSelector">
    <!-- this div is where the column selector is put -->
  </div>
</div>
<center>
<table class="tablesorter custom-popup" id="data_table">
  <thead>
    <tr>
<?php
// 'critical' keeps columns out of the popup list.
//
print "      <th data-priority=\"critical\">";
print ($view == "vendor") ? "Vendor</th>\n" : "Resource</th>\n";
//      <th data-priority="critical">Cost Per Use</th>
?>
      <th data-priority="1" data-selector-name="Cost">Cost Per Use</th>
      <th data-priority="2" data-selector-name="Clicks">Result Clicks</th>
      <th data-priority="3" data-selector-name="Searches">Regular Searches</th>
      <th data-priority="4" data-selector-name="FullText">Full-Text Requests</th>
      <th data-priority="5" data-selector-name="Titles">Title Requests</th>
      <th data-priority="critical" class="sorter-false">&nbsp;</th>
    </tr>
  </thead>
  <tbody>
<?php
if ( count($all_stats_counts) == 0 ) {
   print "<tr><td colspan=\"7\"><strong>No matching records found</strong></td></tr>\n";
} else {
  foreach ( $all_stats_counts as $id => $_rec ) {
    if ( !isset($_rec['alert']) ) { $_rec['alert'] = FALSE; }
    if ( $_rec['alert'] ) {
      print "    <tr class=\"alertBackground\">\n";
    } else {
      print "    <tr>\n";
    }
    if ( $view == "vendor" ) {
      print "      <td><a href=\"/stats_dash.php?Vid=" . $_rec['ID'] . "\">" . $_rec['name'] . "</a></td>\n";
    } else {
      print "      <td>" . $_rec['name'] . "</td>\n";
    }
    print "      <td>" . $_rec['cost_per_use'] . "</td>\n";
    print "      <td>" . $_rec['result_clicks'] . "</td>\n";
    print "      <td>" . $_rec['reg_searches'] . "</td>\n";
    print "      <td>" . $_rec['ft_requests'] . "</td>\n";
    print "      <td>" . $_rec['title_requests'] . "</td>\n";
    if ( $_rec['alert'] ) {
      $vendor_alert .= ($view == "vendor") ? $_rec['ID'] : "";
      print "      <td><a href=\"" . $vendor_alert . "\">Alert</a></td>\n";
    } else {
      print "      <td>&nbsp;</td>\n";
    }
    print "    </tr>\n";
  }
}
?>
  </tbody>
</table>
</center>
<?php
// All done.
//
print_page_footer();
?>
