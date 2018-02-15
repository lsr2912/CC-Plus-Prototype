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
// javascript target script to return available timestamps 
// matching a given provider and institution as a json array
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

$stamps = array();
$_inst = "";
if ( isset($_REQUEST['inst_id']) ) { $_inst=$_REQUEST['inst_id']; }
$_prov = "";
if ( isset($_REQUEST['prov_id']) ) { $_prov=$_REQUEST['prov_id']; }
if ( $_prov != "" && $_inst != "") { 
  $stamps = ccp_report_timestamps_ui($_prov,$_inst);
}
$main = array('stamps'=>$stamps);
echo json_encode($main); 
?>
