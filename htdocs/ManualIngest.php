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
// ManualIngest.php
//
// CC-Plus manual ingest page
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';
include_once 'ccplus/statsutils.inc.php';
include_once 'ccplus/counter4_parsers.php';
include_once 'ccplus/counter4_processors.php';

// Admins and Editors can use this page
//
$ERR = 1;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] <= MANAGER_ROLE ) { $ERR = 0; }
}

// Set some defaults
//
$_PROV = 0;
$_INST = 0;
$_REPT = 0;
$ingest_settings = array();

// If submit is set, verify and collect arguments
//
if ( isset($_REQUEST['submit']) ) {

  $RunType = $_REQUEST['submit'];

  // Get inputs for building the request
  //
  if ( $RunType == "GetReport" ) {

    if ( isset($_POST['Prov']) && isset($_POST['Inst']) && isset($_POST['Rept']) && isset($_POST['Server']) && 
         isset($_POST['Sushi_Auth']) && isset($_POST['auth_user']) && isset($_POST['auth_pass']) &&
         isset($_POST['ReqID']) && isset($_POST['ReqName']) && isset($_POST['ReqEmail']) &&
         isset($_POST['CustID']) && isset($_POST['CustName']) && isset($_POST['IngestOP']) &&
         isset($_POST['ReportMo']) && isset($_POST['ReportYr']) ) {
      $_PROV = $_POST['Prov'];
      $_INST = $_POST['Inst'];
      $_REPT = $_POST['Rept'];
      $prov_info = ccp_get_providers( $_PROV );
      $ingest_settings[0]['Provider'] = $prov_info['name'];
      $ingest_settings[0]['server_url'] = $_POST['Server'];
      $ingest_settings[0]['security'] = $_POST['Sushi_Auth'];
      $ingest_settings[0]['auth_username'] = $_POST['auth_user'];
      $ingest_settings[0]['auth_password'] = $_POST['auth_pass'];
      $ingest_settings[0]['RequestorID'] = $_POST['ReqID'];
      $ingest_settings[0]['RequestorName'] = $_POST['ReqName'];
      $ingest_settings[0]['RequestorEmail'] = $_POST['ReqEmail'];
      $ingest_settings[0]['CustRefID'] = $_POST['CustID'];
      $ingest_settings[0]['CustRefName'] = $_POST['CustName'];

    } else {
      $ERR=2;
    }

  // If we're re-running a report, get the details
  //
  } else if ( $RunType == "ReRun" ) {

    if ( isset($_REQUEST['rept']) ) {
      $_rept_val = trim($_REQUEST['rept']);
      $_rept = ccp_get_ingest_record(0,0,0,0,"",0,$_rept_val);
      $_PROV = $_rept['prov_id'];
      $_INST = $_rept['inst_id'];
      $_REPT = $_rept['report_ID'];
      $ingest_settings = ccp_get_sushi_settings( $_INST, $_PROV );
    } else {
      $ERR = 2;
    }

  } else {
    $ERR = 2;
  }

// If not submitted, we'll be prompting for inputs
//
} else {
  $RunType = "Prompt";
  $ingest_settings[0] = array('Provider'=>"", 'server_url'=>"", 'security'=>"None", 'auth_username'=>"",
                              'auth_password'=>"", 'RequestorID'=>"",'RequestorName'=>"", 'RequestorEmail'=>"",
                              'CustRefID'=>"", 'CustRefName'=>"");
}

// OK, if inputs look good, proceed
//
if ( $ERR == 0 ) {

  // For Prompt and ReRun, make an HTML form for the inputs. ReRun will use
  // initial values from above, but user can alter them if they choose.
  //
  if ( $RunType == "Prompt" || $RunType=="ReRun" ) {

    $_auth_types = ccp_get_enum_values("provider","security");

    // Build breadcrumbs, pass to page header builder
    //
    print_page_header("CC-Plus Manual Ingest",TRUE);

    // Setup Javascript and form
    //
    print "  <script src=\"" . CCPLUSROOTURL . "include/validators.js\"></script>\n";
    print "  <script src=\"" . CCPLUSROOTURL . "include/Manual_Ingest.js\"></script>\n";
    $_form  = "<form name=\"IngestForm\" onsubmit=\"return validateFormSubmit(this)\" id=\"IngestForm\"";
    $_form .= " method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\">\n";
    print $_form;
    print "  <input type=\"hidden\" name=\"TYPE\" id=\"TYPE\" value=\"" . $RunType . "\">\n";
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
<?php
    if ( $RunType == "Prompt" ) {
?>
    <tr>
      <td colspan="7" align="center">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Prov">Provider</label></td>
      <td align="left">
        <select name="Prov" id="Prov">
          <option value="">Choose a Provider</option>
<?php
      // Populate the initial provider list
      //
      foreach ( ccp_get_providers_ui() as $_p) {
        print "          <option value=\"" . $_p['prov_id'] . "\">" . $_p['name'] . "</option>\n";
      }
?>
        </select>
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="Rept">Report</label></td>
      <td colspan="2" align="left">
        <select name="Rept" id="Rept">
          <option value="">Choose a Report</option>
<?php
      // Populate the initial report list
      //
      foreach (ccp_get_reports() as $_r) {
        $_name = $_r['Report_Name'] . " v(" . $_r['revision'] . ")";
        print "          <option value=\"" . $_r['ID'] . "\">" . $_name. "</option>\n";
      }
?>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Inst">Institution</label></td>
      <td align="left">
        <select name="Inst" id="Inst">
          <option value="">Choose an Institution</option>
<?php
      // Populate the initial institution list
      //
      foreach ( ccp_get_institutions_ui() as $_i) {
        print "          <option value=\"" . $_i['inst_id'] . "\">" . $_i['name'] . "</option>\n";
      }
?>
        </select>
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="ReportMo">Usage for</label></td>
      <td colspan="2" align="left">
        <select name="ReportMo" id="ReportMo">
<?php
      $last_month = date("Y-m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
      foreach ( $MONTHS as $_mo=>$_str ) {
         print "          <option value=\"" . $_mo . "\"";
         print ($_mo == substr($last_month,5,2)) ? " selected" : "";
         print ">" . $_str . "</option>\n";
      }
?>
        </select>
        <label for="ReportYr"><strong>&nbsp; - &nbsp;</strong></label>
        <select name="ReportYr" id="ReportYr">
<?php
      $_yearlim = substr($last_month,0,4);
      for ( $_yr=$_yearlim-9; $_yr<=$_yearlim; $_yr++ ) {
         print "          <option value=\"" . $_yr . "\"";
         print ($_yr == $_yearlim) ? " selected" : "";
         print ">" . $_yr . "</option>\n";
      }
?>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Server">Sushi Server URL</label></td>
      <td colspan="5" align="left">
        <input type="text" id="Server" name="Server" class="URLtext" value="<?php echo $ingest_settings[0]['server_url']; ?>" />
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="IngestOP">Ingest Operation</label></td>
      <td colspan="5" align="left">
        <select name="IngestOP" id="IngestOP" class="required">
          <option value="">Choose how to process the result</option>
          <option value="CSV">Download report as a CSV, do NOT save in CC-Plus</option>
          <option value="CCP">Ingest the report to CC-Plus, with no download</option>
          <option value="ALL">Ingest to CC-Plus AND download report as a CSV</option>
        </select>
      </td>
    </tr>
<?php
    // ($RunType=="ReRun")
    } else {
?>
    <tr>
      <td colspan="7" align="center">
        <h3>Re-Ingesting Existing Report</h3>
        <p>By submitting this form, you will be replacing the stored usage data for the report
          shown below.<br />The connection settings below are the ones used to pull the original
          report, but these may be changed for this request.<br /> Modified settings will be
          temporary and not retained in the CC-Pluse system.</p>
        <input type="hidden" name="IngestOP" id="IngestOP" value="CCP" />
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Prov">Provider:</label></td>
      <td align="left">
        <input type="hidden" name="Prov" id="Prov" value="<?php echo $_PROV; ?>">
        <font face='+1'><?php echo $ingest_settings[0]['Provider']; ?></font>
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="Rept">Report:</label></td>
      <td colspan="2" align="left">
        <input type="hidden" name="Rept" id="Rept" value="<?php echo $_REPT; ?>">
        <font face='+1'><?php echo $_rept['report_name']; ?></font>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Inst">Institution:</label></td>
      <td align="left">
        <input type="hidden" name="Inst" id="Inst" value="<?php echo $_INST; ?>">
        <font face='+1'><?php echo $_rept['inst_name']; ?></font>
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="YM">Year-Month:</label></td>
      <td colspan="2" align="left">
<?php
       print "        <input type=\"hidden\" name=\"ReportYr\" id=\"ReportYr\" value=\"";
       print substr($_rept['yearmon'],0,4) . "\">\n";
       print "        <input type=\"hidden\" name=\"ReportMo\" id=\"ReportMo\" value=\"";
       print substr($_rept['yearmon'],5,2) . "\">\n";
?>
        <font face='+1'><?php echo $_rept['yearmon']; ?></font>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="Server">Sushi Server URL:</label></td>
      <td align="left">
        <input type="hidden" name="Server" id="Server" value="<?php echo $ingest_settings[0]['server_url']; ?>">
        <font face='+1'><?php echo $ingest_settings[0]['server_url']; ?></font>
      </td>
<?php
    }		// end-if RunType=Re-Run

    // SUSHI definitions are populated via jQuery script once Provider and Inst are selected
    //
?>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td colspan="2" align="right"><label for="Sushi_Auth">Sushi Authentication Type</label></td>
      <td align="left">
        <select id="Sushi_Auth" name="Sushi_Auth">
<?php
  foreach ($_auth_types as $_typ) {
    print "          <option value=\"" . $_typ . "\"";
    if ( $_typ == $ingest_settings[0]['security'] ) { print " selected "; }
    print ">" . $_typ . "</option>\n";
  }
?>
        </select>
      </td>
      <td>&nbsp;</td>
      <td colspan="3" rowspan="2" align="left">
        <div id="AuthCreds">
          <label for="auth_user">Auth-Username: &nbsp;</label>
          <input type="text" id="auth_user" name="auth_user" value="<?php echo $ingest_settings[0]['auth_username']; ?>" /><br />
          <label for="auth_pass">Auth-Password: &nbsp;</label>
          <input type="text" id="auth_pass" name="auth_pass" value="<?php echo $ingest_settings[0]['auth_password']; ?>" />
        </div>
      </td>
    </tr>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td colspan="2" align="right"><label for="ReqID">RequestorID</label></td>
      <td align="left">
        <input type="text" id="ReqID" name="ReqID" value="<?php echo $ingest_settings[0]['RequestorID']; ?>" />
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="ReqName">Requestor Name</label></td>
      <td align="left">
        <input type="text" id="ReqName" name="ReqName" value="<?php echo $ingest_settings[0]['RequestorName']; ?>" />
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="CustID">Customer Reference ID</label></td>
      <td colspan="2" align="left">
        <input type="text" id="CustID" name="CustID" value="<?php echo $ingest_settings[0]['CustRefID']; ?>" />
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right"><label for="ReqEmail">Requestor Email</label></td>
      <td align="left">
        <input type="text" id="ReqEmail" name="ReqEmail" value="<?php echo $ingest_settings[0]['RequestorEmail']; ?>" />
      </td>
      <td>&nbsp;</td>
      <td align="right"><label for="CustName">Customer Reference Name</label></td>
      <td colspan="2" align="left">
        <input type="text" id="CustName" name="CustName" value="<?php echo $ingest_settings[0]['CustRefName']; ?>" />
      </td>
    </tr>
    <tr><td colspan="7">&nbsp;</td></tr>
    <tr>
      <td>&nbsp;</td>
      <td colspan="5" align="center">
        <button type="submit" name="submit" value="GetReport">Submit</button> &nbsp; &nbsp; &nbsp; &nbsp;
        <input type="button" value="Reset" onClick="this.form.reset(); window.location.reload()" />
        &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" name="Cancel" value="Cancel" />
      </td>
      <td>&nbsp;</td>
    </tr>
  </table>
  </form>
<?php

  // RunType is GetReport, execute and process the Request
  //
  } else {	// RunType is GetReport

    // Setup status screen
    //
    // Build breadcrumbs, pass to page header builder
    //
    $crumbs = array();
    array_push($crumbs,array("Manual ingest", CCPLUSROOTURL . "ManualIngest.php"));
    array_push($crumbs,array("Results", ""));
    print_page_header("CC-Plus Manual Ingest Processing : Results ",TRUE,$crumbs);
?>
 <p>&nbsp;</p>
<?php
    $_Agent = uniqid("CCplusSUSHI:", true);
    $_Rpt = ccp_get_reports($_REPT);
    $Begin = $_POST['ReportYr'] . "-" . $_POST['ReportMo'];
    $End = $Begin;
    $Begin .= '-01';
    $End .= '-'.date('t',strtotime($End.'-01'));
    $_settings = $ingest_settings[0];
    $TempURLPath = CCPLUSROOTURL . "raw_reports/temp/";
    $TempDIRPath = CCPLUSREPORTS . "temp/";
 
    // Connect to service, setup SOAP client
    //
    include ('ccplus/sushi_connect.inc');

    // Apply WSSE authentication if required
    //
    if (preg_match("/WSSE/i", $Security)) {
      include ('ccplus/sushi_wsse.inc');
    }

    // Request the report
    //
    $_res = include('ccplus/sushi_request.inc');
    if ( !$_res ) {
      fwrite(STDOUT,"Cannot include CC-Plus SUSHI request template!\n");
      exit;
    }

    // On-Success, parse the XML and then save/download/process the CSV
    // depending on IngestOP. If we're ingesting the report into the
    // system, we'll load into a temp-folder and THEN, if everything
    // completes without error(s), the cron-agent will finish the job.
    //
    if ( $_res == "Success" ) {

      // Save XML as a temp-file
      //
      $_name = tempnam($TempDIRPath, 'XML');
      $xml_file = $_name . ".xml";
      unlink($_name);
      file_put_contents($xml_file, $xml);

      // Display a link to the Raw XML
      //
      $_url = $TempURLPath . substr($xml_file,strpos($xml_file,'/XML')+1);
?>
 <p align="center"><font face='+1'>
   The Raw XML data has been downloaded to a temporary folder.<br />
   CC-Plus will move it to the appropriate folder within 10 minutes.<br />
   <a href="<?php echo $_url; ?>" target="_blank">Click here to view the RAW XML now</a>.
 </font></p>
<?php
      // Parse the XML and and save the report as a CSV in a temp-file
      //
      $parse_status = "";
      $_name = tempnam($TempDIRPath, 'CSV');
      $counter_file = $_name . ".csv";
      unlink($_name);
      file_put_contents($counter_file, $xml);

      // Parse the XML and save as a CSV file
      //
      if ( $Report == "JR1" && $Release = "4" ) {
        $parse_status = parse_counter_JR1v4 ( $xml_file, $counter_file, $Begin, $End );
      } else if ( $Report == "JR5" && $Release = "4" ) {
        $parse_status = parse_counter_JR5v4 ( $xml_file, $counter_file, $Begin, $End );
      }

      // If parse successful, download and/or save into the system
      //
      if ($parse_status == "Success") {

        // Display a link to the CSV
        //
        if ( $_POST['IngestOP']=="CSV" || $_POST['IngestOP']=="ALL" ) {

          $_url = $TempURLPath . substr($counter_file,strpos($counter_file,'/CSV')+1);
?>
 <p align="center"><font face='+1'>
   The report has been processed and formatted as a COUNTER CSV, and currently resides in a
   temporary folder.<br /> CC-Plus will move it to the appropriate folder within 10 minutes.<br />
   <a href="<?php echo $_url; ?>">Click here to download the report as a COUNTER CSV</a>.
 </font></p>
 <p align="center"><br /><font face='+1'><strong>
<?php
        }

        // Process the CSV data into a temporary table in the database
        //
        if ( $_POST['IngestOP']=="CCP" || $_POST['IngestOP']=="ALL" ) {
          $_CONS = $_SESSION['ccp_con_key'];
          $_db = "ccplus_" . $_CONS;
          $yearmon = $_POST['ReportYr'] . "-" . $_POST['ReportMo'];
          if ( $Report == "JR1" && $Release = "4" ) {
            $proc_status = process_counter_JR1v4 ( $counter_file, $_PROV, $_INST, $yearmon, $_db, "Temp_JR1" );
          } else if ( $Report == "JR5" && $Release = "4" ) {
            $proc_status = process_counter_JR5v4 ( $counter_file, $_PROV, $_INST, $yearmon, $_db, "Temp_JR5" );
          }

          // Update the "Manual Staging" table to signal the cron job, and print out end-status
          //
          if ( $proc_status == "Success" ) {
            if ( ccp_record_manual( $xml_file, $counter_file, $_Rpt['ID'], $yearmon, $_PROV, $_CONS, $_INST) ) {
              print "Report Fully Processed and Successfully Saved.<br />\n";
              print "Database and saved intermediate files will be updated within 10 minutes.<br />\n";
            } else {
              print "Failed recording the requested ingest, CC-Plus database not updated.<br />\n";
            }
          } else {
            print "Report Process Failed with Status: " . $proc_status . "<br />\n";
          }
        }

      } else {

        print "XML Response Parsing Failed!<br />\n";

      }

    // If report request failed...
    //
    } else {

      print "SUSHI Request Failed!<br />\n";

    }
?>
        </strong></font></p>
      </td>
    </tr>
  </table>
<?php

  } 	// RunType is GetReport

}  // End-if no errors

// If errors, signal and stop
//
if ($ERR == 1) {
   print_noaccess_error();
} else if ( $ERR > 1 ) {
   print_page_header("CC-Plus Manual Ingest - Error");
   print "<blockquote>\n";
   print "<p>&nbsp;</p>\n<h3>Error on request</h3>\n";
   switch ($ERR) {
     case 2:
       print "<p><font size=\"+1\">One or more arguments are missing or invalid.</br />\n";
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
