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
// javascript target script to return count for the import/export institution settings page
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

// Handle input fields
//
$_stat = "ALL";
if ( isset($_REQUEST['stat']) ) { $_stat = $_REQUEST['stat']; }
$_prov = "ALL";
if ( isset($_REQUEST['prov']) ) { $_prov = $_REQUEST['prov']; }

// Pull Records , all we care about is the number found
//
if ( $_prov == "None" ) {
  $records = ccp_get_institutions_ui($_stat);
} else {
  $records = ccp_get_institution_settings($_stat, $_prov);
}

// Return output w/ JSON
//
$count = count($records);
$main = array('count'=>$count);
echo json_encode($main); 

?>
