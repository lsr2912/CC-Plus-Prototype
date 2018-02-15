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
// ReportDetail.php
//
// CC-Plus Report Detail page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';
include_once 'ccplus/statsutils.inc.php';

// Handle input arguments
//
$_ERR = 0;
$_PROV=0;
if ( isset($_REQUEST['R_Prov']) ) { $_PROV = $_REQUEST['R_Prov']; }
$_INST=0;
if ( isset($_REQUEST['R_Inst']) ) { $_INST = $_REQUEST['R_Inst']; }
$_DATE=0;
if ( isset($_REQUEST['R_yearmon']) ) { $_DATE = trim($_REQUEST['R_yearmon']); }
$_REPT=0;
if ( isset($_REQUEST['R_report']) ) { $_REPT = trim($_REQUEST['R_report']); }
if ( $_PROV==0 || $_INST==0 || $_DATE==0 || $_REPT==0 ) { $_ERR = 1; }

// If there are missing argument(s), put out error page
//
if ( $_ERR != 0 ) {
  print_page_header("CC-Plus Report Details - Error");
  print "<blockquote>\n";
  print "<p>&nbsp;</p>\n<h3>Error on request</h3>\n";
  print "<p><font size=\"+1\">One or more input arguments are missing!</br />\n";
  print "<br /><br /><a href='AdminHome.php'>Click here to return to the Administration Home Page.</a>\n";
  print "</font></p>\n";
  print "</blockquote>\n";
  include 'ccplus/footer.inc.html.php';
  exit;
}

// Setup arrays for filter dropdowns
//
$providers = ccp_get_providers_ui();
$institutions = ccp_report_insts_ui('Saved',$_PROV);
$stamps = ccp_report_timestamps_ui($_PROV,$_INST);
$reports = ccp_get_reports_ui($_PROV, $_INST, $_DATE);

// Setup links to the raw data. This needs to match the way
// that Sushi_ingest.php stores the raw data.
//
$from = date("Y-m", strtotime($_DATE));
$to = $from . '-' . date('t',strtotime($from.'-01'));
$from .= '-01';
$_cons = ccp_get_consortia($_SESSION['ccp_con_id']);
$_rept = ccp_get_ingest_record(0,0,0,0,"",0,$_REPT);
$_rept_name = preg_replace('/ \(|\)/', '', $_rept['report_name']);
$base_path = CCPLUSROOTURL."raw_reports/".$_cons['ccp_key']."/".$_rept['inst_name']."/".$_rept['prov_name'];
$_raw_xml = $base_path."/XML/".$_rept_name."_".$from."_".$to.".xml";
$_raw_csv = $base_path."/COUNTER/".$_rept_name."_".$from."_".$to.".csv";

// Check rights, set flag if user has management-site access
//
$Manager = FALSE;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] <= MANAGER_ROLE ) { $Manager = TRUE; }
}

// Setup page and add jQuery/Ajax refresh script
//
$crumbs = array();	// no crumbs this page
print_page_header("CC-Plus Report Details",TRUE,$crumbs);
print "  <link href=\"" . CCPLUSROOTURL . "include/tablesorter_theme.css\" rel=\"stylesheet\">\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.widgets.js\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/widget-columnSelector.js\"></script>\n";
print "  <link href=\"" . CCPLUSROOTURL . "include/SelectorPopup.css\" rel=\"stylesheet\">\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/SelectorPopup.js\" rel=\"stylesheet\"></script>\n";
print "  <script src=\"" . CCPLUSROOTURL . "include/Report_Details.js\"></script>\n";

?>
  <script type="text/javascript">
    $(document).ready(function() {
      // Init table sorter
      ///
      $('table').tablesorter();
    });
  </script>

<?php
// Setup upper table with form inputs and links
//
?>
  <table width="80%" class="centered">
    <tr>
      <td width="50%" align="left">
        <form name="DetailFrm" id="RptDetail" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <table width="100%" class="centered">
          <tr>
            <td width="40%" align="left"><label for="R_Prov"><h4>Choose a Provider</h4></label></td>
            <td width="5%">&nbsp;</td>
            <td width="55%" align="left">
              <select name="R_Prov" id="R_Prov">
<?php
foreach ( $providers as $_prov ) {
   print "                <option value=\"" . $_prov['prov_id'] . "\"";
   print ($_prov['prov_id'] == $_PROV) ? " selected" : "";
   print ">" . $_prov['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
          <tr>
            <td align="left"><label for="R_Inst"><h4>Choose an Institution</h4></label></td>
            <td>&nbsp;</td>
            <td align="left">
              <select name="R_Inst" id="R_Inst">
<?php
foreach ( $institutions as $_inst ) {
   print "                <option value=\"" . $_inst['inst_id'] . "\"";
   print ($_inst['inst_id'] == $_INST) ? " selected" : "";
   print ">" . $_inst['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
          <tr>
            <td align="left"><label for="R_yearmon"><h4>Choose a Date</h4></label></td>
            <td>&nbsp;</td>
            <td align="left">
              <select name="R_yearmon" id="R_yearmon">
<?php
  foreach ( $stamps as $_ts ) {
    print "                <option value=\"" . $_ts['yearmon'] . "\"";
    print ($_ts['yearmon'] == $_DATE) ? " selected" : "";
    print ">" . $_ts['yearmon'] . "</option>\n";
  }
?>
              </select>
            </td>
          </tr>
          <tr>
            <td align="left"><label for="R_report"><h4>Choose a Report Name</h4></label></td>
            <td>&nbsp;</td>
            <td align="left">
              <select name="R_report" id="R_report" onchange="this.form.submit()">
<?php
  foreach ( $reports as $_r ) {
    $_name = $_r['Report_Name'] . " (v" . $_r['revision'] . ")";
    print "                <option value=\"" . $_r['ID'] . "\"";
    print ($_name == $_rept['report_name']) ? " selected" : "";
    print ">" . $_name . "</option>\n";
  }
?>
              </select>
            </td>
          </tr>
        </table>
        </form>
      </td>
      <td width="50%" align="left" valign="top">
        <table width="100%" class="centered">
          <tr>
            <td class="data">
              <p><a href="<?php echo $_raw_csv; ?>">Download Raw CSV</a></p>
            </td>
          </tr>
          <tr>
            <td class="data">
              <p><a href="<?php echo $_raw_xml; ?>" target="_blank">Download Raw XML</a></p>
            </td>
          </tr>
          <tr>
            <td class="data">
              <form name="DelFrm" id="DelFrm" method="get" action="<?php echo CCPLUSROOTURL; ?>ConfirmDelete.php">
                <input type="hidden" name="rept" id="rept" value="<?php echo $_REPT; ?>">
                <p><button type="submit" name="DeleteRpt" value="Delete">Delete this Report</button></p>
              </form>
            </td>
          </tr>
          <tr>
            <td class="data">
              <form name="ReRunFrm" id="RerunFrm" method="get" action="<?php echo CCPLUSROOTURL; ?>ManualIngest.php">
                <input type="hidden" name="rept" id="rept" value="<?php echo $_REPT; ?>">
                <p><button type="submit" name="submit" value="ReRun">Re-Ingest this Report</button></p>
              </form>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
  </table>

<?php
// Pull records from the database
//
$records = ccp_get_report_records($_rept['Report_Name'], $_INST, $_PROV, 0, $_DATE, $_DATE);

// Setup column selector and commontable headers, but don't initially display all.
// The user will be able to turn columns on/off using the selector.
//
?>
  <div class="columnSelectorWrapper">
    <input id="colSelect1" type="checkbox" class="hidden">
    <label class="columnSelectorButton" for="colSelect1">Columns</label>
    <div id="columnSelector" class="columnSelector">
      <!-- div to hold the column selector -->
    </div>
  </div>
  <table id="data_table" class="tablesorter custom-popup">
    <thead>
      <tr>
        <th data-priority='critical' id="Journal" align='left'>Journal</th>
        <th data-priority='1' id='Platform' class='columnSelector-false' align='left'>Platform</th>
        <th data-priority='3' id='DOI' class='columnSelector-false' data-selector-name='DOI' align='left'>Journal DOI</th>
        <th data-priority='3' id='PropID' class='columnSelector-false' data-selector-name='Prop.ID' align='left'>Proprietary ID</th>
        <th data-priority='3' id='ISSN' class='columnSelector-false' data-selector-name='ISSN' align='left'>Print ISSN</th>
        <th data-priority='3' id='eISSN' class='columnSelector-false' data-selector-name='eISSN' align='left'>Online ISSN</th>
<?php

// Build data table based on report name
//
if ( $_rept['report_name'] == "JR1 (v4)" ) {
?>
        <th data-priority='2' id="HTML" data-selector-name='HTML' align='right'>Reporting Period HTML</th>
        <th data-priority='2' id="PDF" data-selector-name='PDF' align='right'>Reporting Period PDF</th>
        <th data-priority='1' id="Requests" data-selector-name='Total' align='right'>Total Full-Text Article Requests</th>
      </tr>
    </thead>
    <tbody id="Summary">
<?php
  // Print data records; form inputs will allow user to rebuild and/or sort it 
  //
  if ( count($records) > 0 ) {
    foreach ( $records as $_data ) {
      $row  = "      <tr>\n";
      $row .= "        <td align='left'>" . $_data['journal_title'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['platform_name'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['DOI'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['PropID'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['ISSN'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['eISSN'] . "</td>\n";
      $row .= "        <td align='right'>" . $_data['RP_HTML'] . "</td>\n";
      $row .= "        <td align='right'>" . $_data['RP_PDF'] . "</td>\n";
      $row .= "        <td align='right'>" . $_data['RP_TTL'] . "</td>\n";
      $row .= "      </tr>\n";
      print $row;
    }
  } else {
    print "      <tr><td colspan=3 align='left'><p><strong>";
    print "No matching records</strong></p></td></tr>\n";
  }

// For JR5, we'll grab all the YOP_#### columns, but only display some.
//
} else if ( $_rept['report_name'] == "JR5 (v4)" ) {
  $last_yr = date('Y', strtotime("-1 year"));  
  $yops = ccp_get_yop_columns();
?>
        <th data-priority='2' id='Inpress' data-selector-name='InPress' align='right'>Articles in Press</th>
<?php
  $_col_count = 3;
  foreach ( $yops as $_yop ) {
    if ( $_yop == "YOP_InPress" ) { continue; }
    $_yr = substr($_yop,4);
    $_hdr = "        <th data-priority='2' id='" . $_yop . "' data-selector-name='" . $_yr . "'";
    if ( !is_numeric($_yr) ) {
      if ( $_yop == "YOP_InPress" ) { continue; }
      $_hdr .= " align='right' class='columnSelector-false'>" . $_yop . "</th>\n";
    } else {
      if ( $_yr >= $last_yr ) {
        $_hdr .= " align='right'>" . $_yop . "</th>\n";
      } else {
        $_hdr .= " align='right' class='columnSelector-false'>" . $_yop . "</th>\n";
      }
    }
    print $_hdr;
    $_col_count++;
  }
?>
      </tr>
    </thead>
    <tbody id="Summary">
<?php
  // Print data records; form inputs will allow user to rebuild and/or sort it 
  //
  if ( count($records) > 0 ) {
    foreach ( $records as $_data ) {
      $row  = "      <tr>\n";
      $row .= "        <td align='left'>" . $_data['journal_title'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['platform_name'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['DOI'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['PropID'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['ISSN'] . "</td>\n";
      $row .= "        <td align='left'>" . $_data['eISSN'] . "</td>\n";
      foreach ( $yops as $_yop ) {
        $row .= "        <td align='right'>" . $_data[$_yop] . "</td>\n";
      }
      $row .= "      </tr>\n";
      print $row;
    }
  } else {
    print "      <tr><td colspan=" . $_col_count . " align='left'><p><strong>";
    print "No matching records</strong></p></td></tr>\n";
  }

// Missing/unknown Report_Name!?
//
} else {
?>
    <tbody id="Summary">
      <tr>
        <td align='center'><p><strong>Unrecognized Report_Name!?</p></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
<?php
include 'ccplus/footer.inc.html.php';
?>
