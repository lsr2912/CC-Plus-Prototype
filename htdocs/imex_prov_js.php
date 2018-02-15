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
// javascript target script to return count for the import/export provider settings page
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

// Handle input fields
//
$_stat = "ALL";
if ( isset($_REQUEST['stat']) ) { $_stat = $_REQUEST['stat']; }

// Pull Records , all we care about is the number found
//
$records = ccp_get_providers_ui($_stat);

// Return output w/ JSON
//
$count = count($records);
$main = array('count'=>$count);
echo json_encode($main); 

?>
