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
// javascript target script to return report ID/name pairs
// matching a given provider and timestamp as a json array
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

// Handle input arguments
//
$_stamp = "";
if ( isset($_REQUEST['stamp']) ) { $_stamp=$_REQUEST['stamp']; }
$_prov = "";
if ( isset($_REQUEST['prov_id']) ) { $_prov=$_REQUEST['prov_id']; }

// Set inst based on SESSION if user is manager
//
$_inst = "";
if ( isset($_REQUEST['inst_id']) ) { $_inst=$_REQUEST['inst_id']; }
if ( isset($_SESSION['role']) && isset($_SESSION['user_inst']) ) {
  if ( $_inst == "" && $_SESSION['role'] == MANAGER_ROLE ) {
    $_inst = $_SESSION['user_inst'];
  }
}

// Pull list of available reports
//
if ( $_prov != "" && $_stamp != "" && $_inst != "" ) { 
  $reports = ccp_get_reports_ui($_prov, $_inst, $_stamp);
} else {
  $reports = array();
}
$main = array('reports'=>$reports);
echo json_encode($main); 
?>
