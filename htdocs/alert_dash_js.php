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
// javascript target script to return rows for the alerts dashboard table
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

// Check if admin, set flag and pass to JS with records
//
$is_admin = 0;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] == ADMIN_ROLE ) { $is_admin = 1; }
}

// Handle possible input fields
//
$stat = 'Active';
if ( isset($_REQUEST['filter_stat']) ) { $stat = $_REQUEST['filter_stat']; }
$vend = 0;
if ( isset($_REQUEST['filter_prov']) ) { $prov = $_REQUEST['filter_prov']; }

// Pull Records
//
$records = ccp_get_alerts($stat, $prov);

// Return output w/ JSON
//
$main = array('records'=>$records, 'admin'=>$is_admin);
echo json_encode($main); 

?>
