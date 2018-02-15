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
// javascript target script to return filtering options based on current values
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';
require_once('ccplus/statsutils.inc.php');

// Handle input arguments
//
$_from="";
$_to="";
$_rept=0;
$_prov=0;
$_plat=0;
$_inst=0;
if ( isset($_REQUEST['from']) ) { $_from=$_REQUEST['from']; }
if (   isset($_REQUEST['to']) ) { $_to=$_REQUEST['to']; }
if ( isset($_REQUEST['rept']) ) { $_rept=$_REQUEST['rept']; }
if ( isset($_REQUEST['prov']) ) { $_prov=$_REQUEST['prov']; }
if ( isset($_REQUEST['plat']) ) { $_plat=$_REQUEST['plat']; }
if ( isset($_REQUEST['inst']) ) { $_inst=$_REQUEST['inst']; }

// Get range of available dates for input filters
//
$range = ccp_stats_available($_rept, $_prov, $_plat, $_inst );
$yearmons = createYMarray($range['from'], $range['to']);
if ( $_from == "" ) { $_from = $range['from']; }
if ( $_to == "" ) { $_to = $range['to']; }

// Get available options for each of the filters, store in separate arrays
//
$reports = ccp_repts_available($_from,$_to,$_prov,$_plat,$_inst);
// $providers = ccp_stats_ID_list("PROV",$_rept,$_from,$_to,0,$_plat,$_inst);
$providers = ccp_stats_ID_list("PROV",$_rept,$_from,$_to,0,0,$_inst);
if ( count($providers) > 1 ) {
  array_unshift($providers,array("prov_id"=>0,"name"=>"ALL"));
}
$platforms = ccp_stats_ID_list("PLAT",$_rept,$_from,$_to,$_prov,0,$_inst);
if ( count($platforms) > 1 ) {
  array_unshift($platforms,array("plat_id"=>0,"name"=>"ALL"));
}
$institutions = ccp_stats_ID_list("INST",$_rept,$_from,$_to,$_prov,$_plat,0);
if ( count($institutions) > 1 ) {
  array_unshift($institutions,array("inst_id"=>0,"name"=>"ALL"));
}

// Pull the arrays together and return via JSON
//
$main = array('range'=>$yearmons,
              'repts'=>$reports,
              'provs'=>$providers,
              'plats'=>$platforms,
              'insts'=>$institutions,
             );
echo json_encode($main); 
?>
