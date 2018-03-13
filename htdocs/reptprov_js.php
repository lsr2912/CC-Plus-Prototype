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
// javascript target script to return available institutions 
// with reports matching a given provider as a json array
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

// Check if admin, set flag and pass to JS with records
//
$is_admin = 0;
$is_manag = 0;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] == ADMIN_ROLE ) { $is_admin = 1; }
  if ( $_SESSION['role'] == MANAGER_ROLE ) { $is_manag = 1; }
} 

// Get inputs and create empty return array
//
$_prov = "";
if ( isset($_REQUEST['prov_id']) ) { $_prov=$_REQUEST['prov_id']; }
$_inst = "";
if ( isset($_SESSION['user_inst']) ) { $_inst = $_SESSION['user_inst']; }
$records = array();

// Pull records to be returned, store in $records
//     Admin: send back institution ID's and names
//   Manager: send back timestamps
//
if ( $is_admin ) {
  if ( isset($_REQUEST['prov_id']) ) { $_prov=$_REQUEST['prov_id']; }
  if ( $_prov != "" ) { 
    $records = ccp_report_insts_ui('Saved',$_prov);
  }
} else if ( $is_manag ) {
  if ( $_prov != "" && $_inst != "") {
    $records = ccp_report_timestamps_ui($_prov,$_inst);
  }
}

// If nothing came back, signal an error
//
if ( count($records) == 0 ) {
  $records[] = array("error"=>1,
                   "message"=>"No reports exist for this provider");
}
$main = array('records'=>$records, 'admin'=>$is_admin);
echo json_encode($main); 
?>
