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
// ExportSettings.php
//
// CC-Plus Settings Export Script
//
include_once 'ccplus/sessions.inc.php';
include_once 'ccplus/auth.inc.php';

$ERR = 0;

// Check rights; only proceed if role is Admin ??
//
// if ( $_SESSION['role'] != ADMIN_ROLE ) { $ERR = 1; }

// Check input POST arguments
//
$TYPE = "";
if ( isset($_POST['ExpType']) && isset($_POST['Export']) ) {
  $TYPE = $_POST['ExpType'];

  // Check on type-specific arguments
  //
  switch ($TYPE) {
    case "User":
      if ( !isset($_POST['U_stat']) || !isset($_POST['U_role']) || !isset($_POST['U_inst']) ) { $ERR = 2; }
      break;
    case "Inst":
      if ( !isset($_POST['I_stat']) || !isset($_POST['I_prov']) ) { $ERR = 2; }
      break;
    case "Prov":
      if ( !isset($_POST['P_stat']) ) { $ERR = 2; }
      break;
    case "Name":
      if ( !isset($_POST['N_pstat']) || !isset($_POST['N_prov']) || !isset($_POST['N_istat']) ||
           !isset($_POST['N_inst']) ) { $ERR = 2; }
  }
} else {  // if ExpType or Export missing, set error
  $ERR = 2;
}

// Start building the export if no errors are set
//
if ( $ERR == 0 ) {

  $out_file  = "CCPlus_" . $TYPE . "_settings_" . date('Y_m_d') . ".csv";

  // Collect and export the records to be exported
  //
  switch ($TYPE) {
    case "User":
      $records = ccp_get_users(0, $_POST['U_stat'], $_POST['U_inst'], $_POST['U_role']);
      $header = array('ID','Active','Inst_ID','Email','Password','First','Last','Phone','Role','GetsAlerts','PWChangeReq');
     
      // Open output with UTF-8 encoding and send header row
      //
      header( 'Content-Encoding: UTF-8');
      header( 'Content-Type: text/csv; charset=UTF-8' );
      header( 'Content-Disposition: attachment;filename='.$out_file);
      echo "\xEF\xBB\xBF";
      $fp = fopen('php://output', 'w');
      fputcsv($fp, array("",""));	// Excel thinks the CSV is an SYLK file if cell A-1 holds "ID"..
      fputcsv($fp, $header);

      // Print the rows
      //
      foreach ( $records as $row ) {
        $output = array();
        $output[] = $row['user_id'];
        $output[] = ($row['active']==1) ? "Y" : "N";
        $output[] = $row['inst_id'];
        $output[] = $row['email'];
        $output[] = " ";
        $output[] = $row['first_name'];
        $output[] = $row['last_name'];
        $output[] = $row['phone'];
        $output[] = ccp_role_name($row['role']);
        $output[] = ($row['optin_alerts']==1) ? "Y" : "N";
        $output[] = ($row['password_change_required']==1) ? "Y" : "N";
        fputcsv($fp,$output);
      }
      break;
    case "Inst":
      $header = array('ID','Name','Active','Notes');
      // $header = array('ID','Name','Active','notes','Type','FTE','IP Range','Shib URL');
      if ( $_POST['I_prov'] == "None" ) {
        $records = ccp_get_institutions($_POST['I_stat']);
      } else {
        $records = ccp_get_institution_settings($_POST['I_stat'], $_POST['I_prov']);
        array_push($header,'Prov_ID','RequestorID','RequestorName','RequestorEmail','CustomerRefID','CustRefName');
      }

      // Open output with UTF-8 encoding and send header row
      // 
      header( 'Content-Encoding: UTF-8');
      header( 'Content-Type: text/csv; charset=UTF-8' );
      header( 'Content-Disposition: attachment;filename='.$out_file);
      echo "\xEF\xBB\xBF";
      $fp = fopen('php://output', 'w');
      fputcsv($fp, array("",""));	// Excel thinks the CSV is an SYLK file if cell A-1 holds "ID"..
      fputcsv($fp, $header);

      // Print the rows
      //
      foreach ( $records as $row ) {
        $output = array();
        $output[] = $row['ID'];
        $output[] = $row['name'];
        $output[] = ($row['active']==1) ? "Y" : "N";
        $output[] = $row['notes'];
        // $output[] = $row['Type'];
        // $output[] = $row['FTE'];
        // $output[] = $row['IP Range'];
        // $output[] = $row['Shib URL'];
        if ( $_POST['I_prov'] != "None" ) {
          $output[] = $row['prov_id'];
          $output[] = $row['RequestorID'];
          $output[] = $row['RequestorName'];
          $output[] = $row['RequestorEmail'];
          $output[] = $row['CustRefID'];
          $output[] = $row['CustRefName'];
        }
        fputcsv($fp,$output);
      }
      break;
    case "Prov":
      $records = ccp_get_providers($_POST['P_stat']);
      $header = array('ID','Name','Active','ServerURL','Security','Auth_Username','Auth_Password','Ingest_Day');
     
      // Open output with UTF-8 encoding and send header row
      // 
      header( 'Content-Encoding: UTF-8');
      header( 'Content-Type: text/csv; charset=UTF-8' );
      header( 'Content-Disposition: attachment;filename='.$out_file);
      echo "\xEF\xBB\xBF";
      $fp = fopen('php://output', 'w');
      fputcsv($fp, array("",""));	// Excel thinks the CSV is an SYLK file if cell A-1 holds "ID"..
      fputcsv($fp, $header);

      // Print the rows
      //
      foreach ( $records as $row ) {
        $output = array();
        $output[] = $row['prov_id'];
        $output[] = $row['name'];
        $output[] = ($row['active']==1) ? "Y" : "N";
        $output[] = $row['server_url'];
        $output[] = $row['security'];
        $output[] = $row['auth_username'];
        $output[] = $row['auth_password'];
        $output[] = $row['day_of_month'];
        fputcsv($fp,$output);
      }
      break;
    case "Name":
      $records = ccp_get_aliases($_POST['N_inst'], $_POST['N_prov'], $_POST['N_istat'], $_POST['N_pstat']);
      $header = array('ID','Inst_ID','Prov_ID','Alias');
     
      // Open output with UTF-8 encoding and send header row
      // 
      header( 'Content-Encoding: UTF-8');
      header( 'Content-Type: text/csv; charset=UTF-8' );
      header( 'Content-Disposition: attachment;filename='.$out_file);
      echo "\xEF\xBB\xBF";
      $fp = fopen('php://output', 'w');
      fputcsv($fp, array("",""));	// Excel thinks the CSV is an SYLK file if cell A-1 holds "ID"..
      fputcsv($fp, $header);

      // Print the rows
      //
      foreach ( $records as $row ) {
        $output = array();
        $output[] = $row['ID'];
        $output[] = $row['inst_id'];
        $output[] = $row['prov_id'];
        $output[] = $row['alias'];
        fputcsv($fp,$output);
      }
  }
  fclose($fp);
}
?>
