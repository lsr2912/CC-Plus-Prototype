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
// javascript target script to return count for the import/export name aliases page
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

// Handle input fields
//
$_istat = "ALL";
if ( isset($_REQUEST['istat']) ) { $_istat = $_REQUEST['istat']; }
$_inst = 0;
if ( isset($_REQUEST['inst']) ) { $_inst = $_REQUEST['inst']; }
$_pstat = "ALL";
if ( isset($_REQUEST['pstat']) ) { $_pstat = $_REQUEST['pstat']; }
$_prov = 0;
if ( isset($_REQUEST['prov']) ) { $_prov = $_REQUEST['prov']; }

// Pull Records , all we care about is the number found
//
$records = ccp_get_aliases($_inst, $_prov, $_istat, $_pstat);

// Return output w/ JSON
//
$count = count($records);
$main = array('count'=>$count);
echo json_encode($main); 

?>
