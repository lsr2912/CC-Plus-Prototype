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
// ReportHome.php  :  CC-Plus Report Viewer homepage
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';
include_once 'ccplus/statsutils.inc.php';

// Handle allowed input variables; default view is by-Journal for JR1,
// for all Institutions and all Providers across last month
//
$_FROMYM = "";
if ( isset($_REQUEST['FromYM'])) { $_FROMYM = $_REQUEST['FromYM']; }
$_TOYM = "";
if ( isset($_REQUEST['ToYM'])) { $_TOYM = $_REQUEST['ToYM']; }
$_VIEW = "Jrnl";
if ( isset($_REQUEST['View'])) { $_VIEW = $_REQUEST['View']; }
$_REPT = "JR1";
if ( isset($_REQUEST['Rept'])) { $_REPT = $_REQUEST['Rept']; }
$_JR1COL = "TTL";
if ( isset($_REQUEST['JR1Col'])) { $_JR1COL = $_REQUEST['JR1Col']; }
$_INST = 0;
if ( isset($_REQUEST['Inst'])) { $_INST = $_REQUEST['Inst']; }
$_PROV = 0;
if ( isset($_REQUEST['Prov'])) { $_PROV = $_REQUEST['Prov']; }
$_ZRECS = 'show';
if ( isset($_REQUEST['Zrecs'])) { $_ZRECS = $_REQUEST['Zrecs']; }
$_DEST = 0;
if ( isset($_REQUEST['Dest'])) { $_DEST = $_REQUEST['Dest']; }
$_RUNIT = FALSE;
if ( isset($_POST['Submit']) ) { $_RUNIT = TRUE; }

// Pull (active) alerts, set URL for linking to them
//
$alert_url = "";
$alerts = ccp_get_alerts("Active",$_PROV);
if ( count($alerts) > 0 ) {
  $alert_url  = "/AlertsDash.php?Astat=Active";
  $alert_url .= ($_PROV==0) ? "" : "&amp;prov_id=".$_PROV;
}

// Limit select boxes to those with matching records in the time-range
// based on all the filter-settings.
//
$range = ccp_stats_available($_REPT, $_PROV, 0, $_INST );
$filt_ym = createYMarray($range['from'], $range['to']);
if ( $_FROMYM == "" ) { $_FROMYM = $range['from']; }
if ( $_TOYM == "" ) { $_TOYM = $range['to']; }
$year_mons = createYMarray($_FROMYM, $_TOYM);
$filt_reports = ccp_repts_available($_FROMYM,$_TOYM,$_PROV,0,$_INST);
// $filt_providers = ccp_stats_ID_list("PROV",$_REPT,$_FROMYM,$_TOYM,0,0,$_INST);
$filt_providers = ccp_stats_ID_list("PROV",$_REPT,$_FROMYM,$_TOYM,0,0,$_INST);
if ( count($filt_providers) > 1 ) {
  array_unshift($filt_providers,array("prov_id"=>0,"name"=>"ALL"));
}
$filt_insts = ccp_stats_ID_list("INST",$_REPT,$_FROMYM,$_TOYM,$_PROV,0,0);
if ( count($filt_insts) > 1 ) {
  array_unshift($filt_insts,array("inst_id"=>0,"name"=>"ALL"));
}
$all_views = array("Jrnl"=>"By Journal","Inst"=>"By Institution",
                   "Both"=>"By Journal + Institution");

// Get usage counts
// (default to JR1 if $_REPT is screwy)
//
if ( $_RUNIT ) {
  if ( $_REPT == "JR5" ) {
    $stats_counts = ccp_jr5_usage( $_PROV, 0, $_INST, $_FROMYM, $_TOYM, $_VIEW );
  } else {
    $_ordby = "Total_" . $_JR1COL . " DESC";
    $stats_counts = ccp_jr1_usage( $_PROV, 0, $_INST, $_FROMYM, $_TOYM, $_VIEW, $_ordby );
  }

  // Get info / counts of stats alerts
  //
  $alert_status = ccp_get_enum_values("alerts","status");
  $alerts = ccp_get_alerts("ALL",0);
  $alert_counts = array();
  $total_count = 0;
  foreach ( $alert_status as $_status ) {
    $count = 0;
    foreach ( $alerts as $_alert ) {
      if ( $_alert['status'] == $_status ) { $count++; }
    }
    $alert_counts[$_status] = $count;
    $total_count += $count;
  }
  $alert_counts['Total'] = $total_count;
}

// Start building html page
//
$_CON = ccp_get_consortia($_SESSION['ccp_con_id']);
if ( $_DEST == "HTML" ) {

  // Check rights, set flag if user has management-site access
  //
  $Manager = FALSE;
  if ( isset($_SESSION['role']) ) {
    if ( $_SESSION['role'] <= MANAGER_ROLE ) { $Manager = TRUE; }
  }

  $crumbs = array();
  print_page_header("CC-Plus Reports Home: ". $_CON['name'],TRUE,$crumbs,TRUE,TRUE);
  print "  <link href=\"" . CCPLUSROOTURL . "include/tablesorter_theme.css\" rel=\"stylesheet\">\n";
  print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.js\"></script>\n";
  print "  <script src=\"" . CCPLUSROOTURL . "include/jquery.tablesorter.widgets.js\"></script>\n";
  print "  <script src=\"" . CCPLUSROOTURL . "include/widget-columnSelector.js\"></script>\n";
  print "  <link href=\"" . CCPLUSROOTURL . "include/SelectorPopup.css\" rel=\"stylesheet\">\n";
  print "  <script src=\"" . CCPLUSROOTURL . "include/SelectorPopup.js\" rel=\"stylesheet\"></script>\n";
  print "  <script src=\"" . CCPLUSROOTURL . "include/Report_Home.js\"></script>\n";

  // Build a simple summary of alerts with links to the counts for each
  // alert status (if session indicates user wants to see them).
  //
  if (isset($_SESSION['statdash_alerts'])) {
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
<?php
  // Setup Form and hidden variables
  //
  print "<form name='ReportFrm' id='ReportFrm' method='post' action='".$_SERVER['PHP_SELF']."'>\n";

  // Start the main table and create the form inputs 
  //
?>
  <table width="85%" class="centered">
    <tr>
      <td width="20%" align="left"><label for="FromYM"><h4>Report usage from:</h4></label></td>
      <td width="2%" >&nbsp;</td>
      <td width="28%" align="left">
        <select name="FromYM" id="FromYM">
<?php
  foreach ( $filt_ym as $_ym ) {
    print "          <option value=\"" . $_ym . "\"";
    print ($_ym == $_FROMYM) ? " selected" : "";
    print ">" . $_ym . "</option>\n";
  }
?>
        </select> &nbsp; &nbsp;
        <label for="ToYM"><strong> through:</strong></label> &nbsp; &nbsp;
        <select name="ToYM" id="ToYM">
<?php
  foreach ( $filt_ym as $_ym ) {
    print "          <option value=\"" . $_ym . "\"";
    print ($_ym == $_TOYM) ? " selected" : "";
    print ">" . $_ym . "</option>\n";
  }
?>
        </select>
      </td>
      <td width="20%" align="left"><label for="Inst"><h4>Filter by Institution</h4></label></td>
      <td width="2%" >&nbsp;</td>
      <td width="28%" align="left">
        <select name="Inst" id="Inst">
<?php
  foreach ( $filt_insts as $_inst ) {
    print "          <option value=\"" . $_inst['inst_id'] . "\"";
    print ( $_INST == $_inst['inst_id'] ) ? " selected" : "";
    print ">" . $_inst['name'] . "</option>\n";
  }
?>
        </select>
      </td>
    </tr>
    <tr>
      <td align="left"><label for="View"><h4>Group Records</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="View" id="View">
<?php
  foreach ( $all_views as $_key=>$_str ) {
    print "          <option value=\"" . $_key . "\"";
    print ( $_VIEW == $_key ) ? " selected" : "";
    print ">" . $_str . "</option>\n";
  }
?>
        </select>
      </td>
      <td align="left"><label for="Prov"><h4>Filter by Provider</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="Prov" id="Prov">
<?php
  foreach ( $filt_providers as $_prov ) {
    print "          <option value=\"" . $_prov['prov_id'] . "\"";
    print ( $_PROV == $_prov['prov_id'] ) ? " selected" : "";
    print ">" . $_prov['name'] . "</option>\n";
  }
?>
        </select>
      </td>
    </tr>
    <tr>
      <td align="left"><label for="Rept"><h4>Report</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="Rept" id="Rept">
<?php
  foreach ( $filt_reports as $_rept ) {
    print "          <option value=\"" . $_rept['Report_Name'] . "\"";
    print ( $_REPT == $_rept['Report_Name'] ) ? " selected" : "";
    print ">" . $_rept['Report_Name'] . "</option>\n";
  }
?>
        </select>
      </td>
      <td align="left"><label for="Zrecs"><h4>Filter Zero-Records</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="Zrecs" id="Zrecs">
          <option value="show" selected>Include zero-records</option>
          <option value="skip">Exclude zero-records</option>
        </select>
      </td>
    </tr>
    <tr>
      <td align="left"><label for="JR1Col" id="JR1ColLab"><h4>Display Counter Metric</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="JR1Col" id="JR1Col">
<?php
    $_opts = array("TTL"=>"Full Text Total", "PDF"=>"Full Text PDF", "HTML"=>"Full Text HTML");
    foreach ( $_opts as $key=>$str ) {
      print "          <option value=\"" . $key . "\"";
      print ($_JR1COL == $key) ? " selected>" : ">";
      print $str . "</option>\n";
    }
?>
        </select>
      </td>
      <td align="left"><label for="Dest"><h4>Send Output to</h4></label></td>
      <td>&nbsp;</td>
      <td align="left">
        <select name="Dest" id="Dest">
          <option value="HTML" selected>Screen as HTML</option>
          <option value="FILE">File as CSV</option>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
      <td align="center">
        <input type="submit" name="Submit" value="Submit" />
      </td>
      <td align="left">
        <input type="button" value="Reset Form" onClick="this.form.reset(); window.location.reload()" />
      </td>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr><td colspan="6">&nbsp;</td></tr>
<?php
  // Upper table and form ends
  //
?>
  </table>
</form>
<?php
}	// Setup HTML if DEST is HTML

if ( $_RUNIT ) {

  if ( $_DEST == "HTML" ) {

    // Setup lower (counts) section; JR1 and JR5 start out the same
    //
    if ( $_REPT == "JR1" || $_REPT == "JR5" ) {
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
<?php
      if ( $_VIEW == "Inst" ) {
        print "        <th data-priority='critical' id='Inst' data-selector-name='Inst' align='left'>Institution</th>\n";
      } else {
        print "        <th data-priority='critical' id='Journal' align='left'>Journal</th>\n";
      }
?>
        <th data-priority='1' id='Provider' align='left'>Provider</th>
        <th data-priority='3' id='Platform' class='columnSelector-false' align='left'>Platform</th>
<?php
      if ( $_VIEW == "Jrnl" || $_VIEW == "Both" ) {
?>
        <th data-priority='3' id='DOI' class='columnSelector-false' data-selector-name='DOI' align='left'>Journal DOI</th>
        <th data-priority='3' id='PropID' class='columnSelector-false' data-selector-name='Prop.ID' align='left'>Proprietary ID</th>
        <th data-priority='3' id='ISSN' class='columnSelector-false' data-selector-name='ISSN' align='left'>Print ISSN</th>
        <th data-priority='3' id='eISSN' class='columnSelector-false' data-selector-name='eISSN' align='left'>Online ISSN</th>
<?php
        if ( $_VIEW == "Both" ) {
          print "        <th data-priority='2' id='Inst' data-selector-name='Inst' align='left'>Institution</th>\n";
        }
      }
    }

    // The rest of the data table depends on the report
    //
    $_ttlstr = "Total";
    $_ttlstr .= ($_REPT=="JR1" && $_JR1COL=="TTL") ? "" : "_".$_JR1COL;
    if ( $_REPT == "JR1" ) {
      print "        <th data-priority='2' id='Total' data-selector-name='Total' align='right'>".$_ttlstr."</th>\n";
      $_col_count = 4;
      if ( $_VIEW == "Jrnl" ) { $_col_count += 4; }
      foreach ( $year_mons as $_ym ) {
        $_col = prettydate($_ym);
        $_hdr = "        <th data-priority='2' id='" . $_col . "' data-selector-name='" . $_col . "'";
        $_hdr .= " align='right'>" . $_col . "</th>\n";
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
      if ( count($stats_counts) > 0 ) {
        $_ttl_col = "Total" . "_" . $_JR1COL;
        foreach ( $stats_counts as $_data ) {
          if ( $_data['Total_TTL']==0 && $_ZRECS=="skip" ) { continue; }
          $row  = "      <tr>\n";
          $row .= "        <td align='left'>" . $_data['Title'] . "</td>\n";
          $row .= "        <td align='left'>" . $_data['provider'] . "</td>\n";
          $row .= "        <td align='left'>" . $_data['platform'] . "</td>\n";
          if ( $_VIEW == "Jrnl" || $_VIEW == "Both" ) {
            $row .= "        <td align='left'>" . $_data['DOI'] . "</td>\n";
            $row .= "        <td align='left'>" . $_data['PropID'] . "</td>\n";
            $row .= "        <td align='left'>" . $_data['ISSN'] . "</td>\n";
            $row .= "        <td align='left'>" . $_data['eISSN'] . "</td>\n";
          }
          if ( $_VIEW == "Both" ) {
            $row .= "        <td align='left'>" . $_data['inst_name'] . "</td>\n";
          }
          $row .= "        <td align='right'>" . $_data[$_ttl_col] . "</td>\n";
          foreach ( $year_mons as $_ym ) {
            $_col = prettydate($_ym) . "_" . $_JR1COL;
            $row .= "        <td align='right'>" . $_data[$_col] . "</td>\n";
          }
          $row .= "      </tr>\n";
          print $row;
        }
      } else {
        print "      <tr><td colspan=".$_col_count." align='left'><p><strong>";
        print "No matching records</strong></p></td></tr>\n";
      }

    // For JR5, we'll grab all the YOP_#### columns, but only display some.
    //
    } else if ( $_REPT == "JR5" ) {
      $last_yr = date('Y', strtotime("-1 year"));
      $yops = ccp_get_yop_columns();
?>
        <th data-priority='2' id='Total' data-selector-name='Total' align='right'>Total</th>
        <th data-priority='2' id='Inpress' data-selector-name='InPress' align='right'>Articles in Press</th>
<?php
      // Setup JR5 column headers
      //
      $_col_count = 9;
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
      // Print JR5 data records; form inputs will allow user to rebuild and/or sort it
      //
      if ( count($stats_counts) > 0 ) {
        foreach ( $stats_counts as $_data ) {
          if ( $_data['Total']==0 && $_ZRECS=="skip" ) { continue; }
          $row  = "      <tr>\n";
          $row .= "        <td align='left'>" . $_data['Title'] . "</td>\n";
          $row .= "        <td align='left'>" . $_data['provider'] . "</td>\n";
          $row .= "        <td align='left'>" . $_data['platform'] . "</td>\n";
          if ( $_VIEW == "Jrnl" || $_VIEW == "Both" ) {
            $row .= "        <td align='left'>" . $_data['DOI'] . "</td>\n";
            $row .= "        <td align='left'>" . $_data['PropID'] . "</td>\n";
            $row .= "        <td align='left'>" . $_data['ISSN'] . "</td>\n";
            $row .= "        <td align='left'>" . $_data['eISSN'] . "</td>\n";
          }
          if ( $_VIEW == "Both" ) {
            $row .= "        <td align='left'>" . $_data['inst_name'] . "</td>\n";
          }
          $row .= "        <td align='left'>" . $_data['Total'] . "</td>\n";
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
    }	// End-if Report-choice
    print "    </tbody>\n  </table>\n";

  } else {	// DEST is CSV File

    // Build some info to precede headers
    //
    $rpt_info  = "CC-Plus " . $_REPT . " Summary Report Created: ";
    $rpt_info .= date("d-M-Y G:i") . "\nConsortium: " . $_CON['name'] . "\n";
    $rpt_info .= "Date Range: " . $_FROMYM . " to " . $_TOYM . "\n\n";
    if ( $_INST!=0 || $_PROV!=0 ) {
      $limits = "";
      if ( $_INST!=0 ) {
        $_inst = ccp_get_institutions($_INST);
        $limits .= $_inst['name'];
      }
      if ( $_PROV!=0 ) {
        $_prov = ccp_get_providers($_PROV);
        $limits .= ($limits!="") ? "," : "";
        $limits .= $_prov['name'];
      }
      if ( $limits != "" ) { $rpt_info .= "Limited By: " . $limits . "\n"; }
    }

    // Setup header(s)
    //
    $header  = ( $_VIEW == "Inst" ) ? "Institution" : "Journal";
    $header .= ",Provider,Platform";
    if ( $_VIEW=="Jrnl" || $_VIEW=="Both" ) {
      $header .= ",Journal DOI,Proprietary ID,Print ISSN,Online ISSN";
      if ( $_VIEW=="Both" ) { $header .= ",Institution"; }
    }
    if ( $_REPT == "JR1" ) {
      $header .= ",Reporting Period Total,Reporting Period HTML,Reporting Period PDF";
      foreach ( $year_mons as $_ym ) {
        $_col = prettydate($_ym);
        $header .= "," . $_col . "_Total";
        $header .= "," . $_col . "_HTML";
        $header .= "," . $_col . "_PDF";
      }
    } else if ( $_REPT == "JR5" ) {
      $header .= ",Total,Articles in Press";
      $yops = ccp_get_yop_columns();
      foreach ( $yops as $_yop ) {
        if ( $_yop == "YOP_InPress" ) { continue; }
        $header .= "," . $_yop;
      }
    }
    $header .= "\n";

    // Open output file and send header row
    //
    $out_file = "CCPLUS_";
    if ( $_INST!=0 ) { $out_file .= "Inst".$_INST."_"; }
    if ( $_PROV!=0 ) { $out_file .= "Prov".$_PROV."_"; }
    $out_file = $_REPT."_".$_FROMYM."_".$_TOYM."_";
    $out_file .= ($_VIEW == "Both") ? "Combined.csv" : "by_".$_VIEW.".csv";
    header( 'Content-Encoding: UTF-8');
    header( 'Content-Type: application/csv;charset=UTF-8' );
    header( 'Content-Disposition: attachment;filename='.$out_file );
    echo "\xEF\xBB\xBF";
    $fp = fopen('php://output', 'w');
    fprintf($fp,$rpt_info);
    fprintf($fp,$header);

    // Print the rows
    //
    foreach ( $stats_counts as $_data ) {
      if ($_REPT == 'JR1') {
        if ($_data['Total_TTL']==0 && $_ZRECS=="skip" ) { continue; }
      } else if ($_REPT == 'JR5') {
        if ( $_data['Total']==0 && $_ZRECS=="skip" ) { continue; }
      }
      $output = array();
      $output[] = $_data['Title'];
      $output[] = $_data['provider'];
      $output[] = $_data['platform'];
      if ( $_VIEW=="Jrnl" || $_VIEW=="Both" ) {
        $output[] = $_data['DOI'];
        $output[] = $_data['PropID'];
        $output[] = $_data['ISSN'];
        $output[] = $_data['eISSN'];
        if ( $_VIEW=="Both" ) { $output[] = $_data['inst_name']; }
      }
      if ( $_REPT == "JR1") {
        $output[] = $_data['Total_TTL'];
        $output[] = $_data['Total_HTML'];
        $output[] = $_data['Total_PDF'];
        foreach ( $year_mons as $_ym ) {
          $_col = prettydate($_ym);
          $output[] = $_data[$_col."_TTL"];
          $output[] = $_data[$_col."_HTML"];
          $output[] = $_data[$_col."_PDF"];
        }
      } else if ( $_REPT == "JR5") {
        $output[] = $_data['Total'];
        foreach ( $yops as $_yop ) { $output[] = $_data[$_yop]; }
      }
      fputcsv($fp,$output);
    }
    fclose($fp);
  }	// End-if HTML.vs.CSV
}	// End-if Running report
?>
<?php
// All done.
//
if ( $_DEST == "HTML" ) {
  include 'ccplus/footer.inc.html.php';
}
?>
