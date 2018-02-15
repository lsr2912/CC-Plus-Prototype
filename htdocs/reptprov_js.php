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

$_prov = "";
if ( isset($_REQUEST['prov_id']) ) { $_prov=$_REQUEST['prov_id']; }
if ( $_prov != "" ) { 
  $insts = ccp_report_insts_ui('Saved',$_prov);
} else {
  $insts = array();
}
// If nothing came back, signal an error
//
if ( count($insts) == 0 ) {
  $insts[] = array("error"=>1,
                   "message"=>"No reports exist for this provider");
}
$main = array('insts'=>$insts);
echo json_encode($main); 
?>
