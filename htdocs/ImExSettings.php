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
// ImExSettings.php
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

// Check rights, set flag if user has management-site access
//
$Manager = FALSE;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] <= MANAGER_ROLE ) { $Manager = TRUE; }
}

// Setup page based on View
//
$_title = "";
switch ($VIEW) {
  case "user":
    $_title = "User Settings";
    $_JS = CCPLUSROOTURL . "include/ImEx_user.js";
    break;
  case "prov":
    $_title = "Provider Settings";
    $_JS = CCPLUSROOTURL . "include/ImEx_prov.js";
    break;
  case "inst":
    $_title = "Institution Settings";
    $_JS = CCPLUSROOTURL . "include/ImEx_inst.js";
    break;
  case "name":
    $_title = "Name Alias";
    $_JS = CCPLUSROOTURL . "include/ImEx_name.js";
}

// Build breadcrumbs, pass to page header builder
//
print_page_header("CC-Plus " . $_title . " Import-Export",TRUE);
print "<script type=\"text/javascript\" src=\"" . CCPLUSROOTURL . "include/validators.js\"></script>\n";

// Add jQuery/Ajax scripts
//
if ( $VIEW != "" ) {
?>
<script type="text/javascript">
  //// Function to validate form fields
  function ImpFormValidate(theForm) {
    var reason = "";
    var radio=0;
    for (var i=0; i<theForm.Itype.length; i++) {
      if (theForm.Itype[i].checked==true) { radio = 1; }
    }
    if ( !radio ) {
      reason += "A selection is required for Import Type.\n"
    }
    reason += validateFile(theForm.Ifile);
    if (reason != "") {
      alert("Some fields need correction:\n" + reason);
      return false;
    } else {
      if ( theForm.Itype.value == "Replace" ) {
        message  = "Full replacement import(s) can corrupt the CC-Plus database\n";
        message += "if existing ID values are overwritten or changed.\n\n";
        message += "Are you sure you want to continue?";
        if ( confirm(message)) {
          return true;
        } else {
          return false;
        }
      }
    }
    return true;
  }
</script>
<?php
  print "<script src=\"" . $_JS . "\"></script>\n";
}
?>
<table width="95%" class="centered">
<?php
if ( $VIEW == "" ) {
?>
  <tr>
    <td align="center" colspan=4>
      <form id='ImExView' method='get' action='<?php echo $_SERVER['PHP_SELF']; ?>'>
      Import or Export : &nbsp;
      <select name="View" id="View" onchange="this.form.submit()">
        <option value="" selected>-- Choose a dataset --</option>
        <option value="inst">Institution Settings</option>
        <option value="prov">Provider Settings</option>
        <option value="user">User Settings</option>
        <option value="name">Name Aliases/option>
      </select>
    </td>
  </tr>
<?php
} else {
?>
  <tr>
    <td width="10%">&nbsp;</td><td width="40%">&nbsp;</td>
    <td width="10%">&nbsp;</td><td width="40%">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td class="section">Import <?php echo $_title; ?> data</td>
    <td>&nbsp;</td>
    <td class="section">Export <?php echo $_title; ?> data</td>
  </tr>
  <tr><td colspan=4>&nbsp;</td></tr>

<?php
  //
  // Build page/form elements based on View, beginning w/ User Settings
  //
  switch ($VIEW) {
    case "user":
      $all_insts = ccp_get_institutions_ui();
      $all_roles = ccp_get_roles_ui();
      $all_users = ccp_get_users(0,"ALL");
      $u_count = count($all_users);
      if ( $u_count == 0 ) { $u_count = "NO"; }
?>
  <tr>
    <td>&nbsp;</td>
    <td align="left">
      <form name="ImpForm" id="ImpForm" method="post" onsubmit="return ImpFormValidate(this)"
         enctype="multipart/form-data" action="ImportSettings.php">
        <input type='hidden' name='ImpDest' id='ImpDest' value='User'>
        <table width="100%">
          <tr>
            <td colspan="2">
              <strong>Settings being imported must match the current User-settings layout.</strong>
              <ul>
                <li>If you are adding records to your configuration, you can download the
                    <a href="templates/User_settings_template.xls">user import template here.</a></li>
                <li>If you are replacing records, be aware that replacing or changing user ID's
                    as part of the import could cause unpredictable results. The best practice
                    for a replacement import is to begin with, and modify, a current EXPORT
                    of the settings in order to preserve the ID values for existing users.</li>
              </ul>
            </td>
          </tr>
          <tr>
            <td width='30%' align='left'><label for="Itype">Import Type</label></td>
            <td width='70%' align='left'>
              <input type="radio" name="Itype" id="Itype" value="Add" />Add Users
              &nbsp; &nbsp; &nbsp;
              <input type="radio" name="Itype" id="Itype" value="Replace" />Replace All Users
            </td>
          </tr>
          <tr>
            <td width='30%' align='left'><label for="Ifile">Import File</label></td>
            <td width='70%' align='left'>
              <input type="file" name="Ifile" id="Ifile">
            </td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tr><td colspan="2" align="center">
            <input type="submit" name="Import" value="Begin Import">
          </td></tr>
        </table>
      </form>
    </td>
    <td>&nbsp;</td>
    <td align="left">
      <form name='ExpForm' id='ExpForm' method='post' action='ExportSettings.php'>
        <input type='hidden' name='ExpType' id='ExpType' value='User'>
        <table width="100%">
          <tr>
            <td width='30%'><label for="U_stat">Filter by Status</label></td>
            <td width='70%'>
              <select name="U_stat" id="U_stat">
                <option value="ALL" selected>ALL</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select> 
            </td>
          </tr>
          <tr>
            <td><label for="U_role">Filter by User Role</label></td>
            <td>
              <select name="U_role" id="U_role" />
                <option value="ALL" selected>ALL</option>
<?php
    // Populate dropdown with available roles
    //
    $_roles = ccp_get_roles_ui();
    foreach ( $_roles as $_r ) {
      print "                <option value=\"" . $_r['role_id'] . "\" />";
      print $_r['name'] . "</option>\n";
    }
?>
              </select>
            </td>
          </tr>
          <tr>
            <td><label for="U_inst">Filter by Institution</label></td>
            <td>
              <select name="U_inst" id="U_inst">
                <option value="ALL" selected>ALL</option>
<?php
// Populate the initial institution list
//
foreach ($all_insts as $inst) {
  print "                <option value=\"" . $inst['inst_id'] . "\">" . $inst['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tbody id="ExpCount">
          <tr><td colspan="2" align="left">
            <?php echo $u_count; ?> user record(s) will be exported based on current selections.
          </td></tr>
          </tbody>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tbody id="BeginEXP">
          <tr>
            <td>&nbsp;</td>
            <td align="left"><input type="submit" name="Export" value="Begin Export"></td>
          </tr>
          </tbody>
        </table>
      </form>
    </td>
  </tr>
<?php
      break;
//
// Build Import/Export Options for Provider Settings
//
    case "prov":
      $all_prov = ccp_get_providers_ui();
      $p_count = count($all_prov);
      if ( $p_count == 0 ) { $p_count = "NO"; }
?>
  <tr>
    <td>&nbsp;</td>
    <td align="left">
      <form name='ImpForm' id='ImpForm' method='post' onsubmit='return ImpFormValidate(this)'
         enctype='multipart/form-data' action='ImportSettings.php'>
        <input type='hidden' name='ImpDest' id='ImpDest' value='Prov'>
        <table width="100%">
          <tr>
            <td colspan="2">
              <strong>Settings being imported must match the current Provider-settings layout.</strong>
              <ul>
                <li>If you are adding records to your configuration, you can download the
                    <a href="templates/Prov_settings_template.xls">provider import template here.</a></li>
                <li>If you are replacing records, be aware that replacing or changing provider ID's
                    as part of the import could cause unpredictable results, <strong>especially if there
                    are already reports saved in the system with the ID's being overwritten</strong>.
                    The best practice for a replacement import is to begin with, and modify, a current
                    EXPORT of the settings in order to preserve the ID values for existing providers.</li>
              </ul>
            </td>
          </tr>
          <tr>
            <td width='30%' align='left'><label for="Itype">Import Type</label></td>
            <td width='70%' align='left'>
              <input type="radio" name="Itype" id="Itype" value="Add" />Add Providers
              &nbsp; &nbsp; &nbsp;
              <input type="radio" name="Itype" id="Itype" value="Replace" />Replace All Providers
            </td>
          </tr>
          <tr>
            <td width='30%' align='left'><label for="Ifile">Import File</label></td>
            <td width='70%' align='left'>
              <input type="file" name="Ifile" id="Ifile">
            </td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tr><td colspan="2" align="center">
            <input type="submit" name="Import" value="Begin Import">
          </td></tr>
        </table>
      </form>
    </td>
    <td>&nbsp;</td>
    <td align="left">
      <form name='ExpForm' id='ExpForm' method='post' action='ExportSettings.php'>
        <input type='hidden' name='ExpType' id='ExpType' value='Prov'>
        <table width="100%">
          <tr>
            <td width='30%'><label for="P_stat">Filter by Status</label></td>
            <td width='70%'>
              <select name="P_stat" id="P_stat">
                <option value="ALL" selected>ALL</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select> 
            </td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tbody id="ExpCount">
          <tr><td colspan="2" align="left">
            <?php echo $p_count; ?> provider record(s) will be exported based on current selections.
          </td></tr>
          </tbody>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tbody id="BeginEXP">
          <tr>
            <td>&nbsp;</td>
            <td align="left"><input type="submit" name="Export" value="Begin Export"></td>
          </tr>
          </tbody>
        </table>
      </form>
    </td>
  </tr>
<?php
      break;
//
// Build Import/Export Options for Institution Settings
//
    case "inst":
      $all_providers = ccp_get_providers_ui();
      $inst_settings = ccp_get_institution_settings();
      $i_count = count($inst_settings);
      if ( $i_count == 0 ) { $i_count = "NO"; }
?>
  <tr>
    <td>&nbsp;</td>
    <td align="left">
      <form name='ImpForm' id='ImpForm' method='post' onsubmit='return ImpFormValidate(this)'
         enctype='multipart/form-data' action='ImportSettings.php'>
        <input type='hidden' name='ImpDest' id='ImpDest' value='Inst'>
        <table width="100%">
          <tr>
            <td colspan="2">
              <strong>Settings being imported must match the current Institution-settings layout.</strong>
              <ul>
                <li>If you are adding records to your configuration, you can download the
                    <a href="templates/Inst_settings_template.xls">institution import template here.</a></li>
                <li>If you are replacing records, be aware that replacing or changing institution ID's
                    as part of the import could cause unpredictable results, <strong>especially if there
                    are already reports saved in the system with the ID's being overwritten</strong>.
                    The best practice for a replacement import is to begin with, and modify, a current
                    EXPORT of the settings in order to preserve the ID values for existing institutions.</li>
              </ul>
            </td>
          </tr>
          <tr>
            <td width='30%' align='left'><label for="Itype">Import Type</label></td>
            <td width='70%' align='left'>
              <input type="radio" name="Itype" id="Itype" value="Add" />Add Institutions
              &nbsp; &nbsp; &nbsp;
              <input type="radio" name="Itype" id="Itype" value="Replace" />Replace All Institutions
            </td>
          </tr>
          <tr>
            <td width='30%' align='left'><label for="Ifile">Import File</label></td>
            <td width='70%' align='left'>
              <input type="file" name="Ifile" id="Ifile">
            </td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tr><td colspan="2" align="center">
            <input type="submit" name="Import" value="Begin Import">
          </td></tr>
        </table>
      </form>
    </td>
    <td>&nbsp;</td>
    <td align="left">
      <form name='ExpForm' id='ExpForm' method='post' action='ExportSettings.php'>
        <input type='hidden' name='ExpType' id='ExpType' value='Inst'>
        <table width="100%">
          <tr>
            <td width='30%'><label for="I_stat">Filter by Status</label></td>
            <td width='70%'>
              <select name="I_stat" id="I_stat">
                <option value="ALL" selected>ALL</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select> 
            </td>
          </tr>
          <tr>
            <td align="left"><label for="I_prov">Filter by Provider</label></td>
            <td align="left">
              <select name="I_prov" id="I_prov">
                <option value="ALL" selected>ALL</option>
                <option value="None">Exclude Provider Data</option>
<?php
// Populate the initial Provider list
//
foreach ($all_providers as $p) {
  print "                <option value=" . $p['prov_id'] . ">" . $p['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tbody id="ExpCount">
          <tr><td colspan="2" align="left">
            <?php echo $i_count; ?> row(s) of settings will be exported based on current selections
          </td></tr>
          </tbody>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tbody id="BeginEXP">
          <tr>
            <td>&nbsp;</td>
            <td align="left"><input type="submit" name="Export" value="Begin Export"></td>
          </tr>
          </tbody>
        </table>
      </form>
    </td>
  </tr>
<?php
      break;
//
// Build Import/Export Options for Name Aliases
//
    case "name":
      $all_providers = ccp_get_providers_ui();
      $all_insts = ccp_get_institutions_ui();
      $all_aliases = ccp_get_aliases(0,0,"ALL","ALL");
      $n_count = count($all_aliases);
      if ( $n_count == 0 ) { $n_count = "NO"; }
?>
  <tr>
    <td>&nbsp;</td>
    <td align="left">
      <form name='ImpForm' id='ImpForm' method='post' onsubmit='return ImpFormValidate(this)'
         enctype='multipart/form-data' action='ImportSettings.php'>
        <input type='hidden' name='ImpDest' id='ImpDest' value='Name'>
        <table width="100%">
          <tr>
            <td colspan="2">
              <strong>Settings being imported must match the current Alias-name-settings layout.</strong>
              <ul>
                <li>If you are adding records to your configuration, you can download the
                    <a href="templates/Name_settings_template.xls">alias names import template here.</a></li>
                <li>The best practice for a replacement import is to begin with, and modify, a current
                    EXPORT of the settings.</li>
              </ul>
            </td>
          </tr>
          <tr>
            <td width='30%' align='left'><label for="Itype">Import Type</label></td>
            <td width='70%' align='left'>
              <input type="radio" name="Itype" id="Itype" value="Add" />Add Name Aliases 
              &nbsp; &nbsp; &nbsp;
              <input type="radio" name="Itype" id="Itype" value="Replace" />Replace All Aliases
            </td>
          </tr>
          <tr>
            <td width='30%' align='left'><label for="Ifile">Import File</label></td>
            <td width='70%' align='left'>
              <input type="file" name="Ifile" id="Ifile">
            </td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tr><td colspan="2" align="center">
            <input type="submit" name="Import" value="Begin Import">
          </td></tr>
        </table>
      </form>
    </td>
    <td>&nbsp;</td>
    <td align="left">
      <form name='ExpForm' id='ExpForm' method='post' action='ExportSettings.php'>
        <input type='hidden' name='ExpType' id='ExpType' value='Name'>
        <table width="100%">
          <tr>
            <td width='30%'><label for="N_pstat">Filter by Provider Status</label></td>
            <td width='70%'>
              <select name="N_pstat" id="N_pstat">
                <option value="ALL" selected>ALL</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select> 
            </td>
          </tr>
          <tr>
            <td align="left"><label for="N_prov">Filter by Provider</label></td>
            <td align="left">
              <select name="N_prov" id="N_prov">
                <option value="ALL" selected>ALL</option>
<?php
// Populate the initial Provider list
//
foreach ($all_providers as $p) {
  print "                <option value=" . $p['prov_id'] . ">" . $p['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
          <tr>
            <td width='30%'><label for="N_istat">Filter by Institution Status</label></td>
            <td width='70%'>
              <select name="N_istat" id="N_istat">
                <option value="ALL" selected>ALL</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select> 
            </td>
          </tr>
          <tr>
            <td><label for="N_inst">Filter by Institution</label></td>
            <td>
              <select name="N_inst" id="N_inst">
                <option value="ALL" selected>ALL</option>
<?php
// Populate the initial institution list
//
foreach ($all_insts as $inst) {
  print "                <option value=\"" . $inst['inst_id'] . "\">" . $inst['name'] . "</option>\n";
}
?>
              </select>
            </td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tbody id="ExpCount">
          <tr><td colspan="2" align="left">
            <?php echo $n_count; ?> alias record(s) will be exported based on current selections.
          </td></tr>
          </tbody>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tbody id="BeginEXP">
          <tr>
            <td>&nbsp;</td>
            <td align="left"><input type="submit" name="Export" value="Begin Export"></td>
          </tr>
          </tbody>
        </table>
      </form>
    </td>
  </tr>
<?php
  }
}
?>
</table>
<?php
// All done.
//
include 'ccplus/footer.inc.html.php';
?>
