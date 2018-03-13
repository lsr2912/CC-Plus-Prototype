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
// javascript target script to return manual ingest settings 
// matching a given provider and institution as a json array
//
require_once('ccplus/statsutils.inc.php');
include_once 'ccplus/auth.inc.php';

// Check if admin or manager, set flags
//
$is_admin = 0;
$is_manag = 0;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] == ADMIN_ROLE ) { $is_admin = 1; }
  if ( $_SESSION['role'] == MANAGER_ROLE ) { $is_manag = 1; }
}

// Get Provider fields
//
$_PROV = 0;
if ( isset($_REQUEST['prov_id']) ) { $_PROV=$_REQUEST['prov_id']; }
$_INST = 0;
if ( $is_manag ) {
  $_INST = $_SESSION['user_inst'];
} else {
  if ( isset($_REQUEST['inst_id']) ) { $_INST=$_REQUEST['inst_id']; }
}

// Return empty array of settings if inst or prov is zero
//
$settings = array();
if ( $_PROV==0 || $_INST==0 ) { 
  echo json_encode(array('settings'=>$settings));
  exit;
}

// Pull settings (function returns a 2-dim array)
//
$ingest_settings = ccp_get_sushi_settings( $_INST, $_PROV );
if ( count($ingest_settings) > 0 ) { 
  $return = $ingest_settings[0];
} else {
  $return = array ("error"=>1 , "message"=>$ERROR);
}

// Build and return JSON
//
$main = array('settings'=>$return);
echo json_encode($main); 
?>
