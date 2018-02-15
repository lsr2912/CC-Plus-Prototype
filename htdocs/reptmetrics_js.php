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
// javascript target script to return available metrics 
// for a given report as a json array
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

$metrics = array();
$_rept = "";
if ( isset($_REQUEST['rept_id']) ) { $_rept=$_REQUEST['rept_id']; }
if ( $_rept != "" ) { 
  $metrics = ccp_get_metrics_ui($_rept);
}
$main = array('metrics'=>$metrics);
echo json_encode($main); 
?>
