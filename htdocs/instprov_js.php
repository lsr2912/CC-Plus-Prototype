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
// javascript target script to return sushi settings and alias names 
// for a given institution and provider as a json array
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';
require_once('ccplus/statsutils.inc.php');

$_inst=0;
$_prov=0;
if ( isset($_REQUEST['inst_id']) ) { $_inst=$_REQUEST['inst_id']; }
if ( isset($_REQUEST['prov_id']) ) { $_prov=$_REQUEST['prov_id']; }
$settings = array();
$aliases = array();
if ( $_inst>=0 && $_prov>0 ) { 
  $settings = ccp_get_sushi_settings($_inst,$_prov);
  if ( count($settings) == 0 ) {
    $settings['server_url'] = "";
    $settings['security'] = "None";
    $settings['auth_username'] = "";
    $settings['auth_password'] = "";
    $settings['RequestorID'] = "";
    $settings['RequestorName'] = "";
    $settings['RequestorEmail'] = "";
    $settings['CustRefID'] = "";
    $settings['CustRefName'] = "";
  }
  $aliases = ccp_get_aliases($_inst,$_prov);
}
$main = array('sushi'=>$settings, 'names'=>$aliases);
echo json_encode($main); 
?>
