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

// Check if manager, set flag and pass to JS with records
//
$Manager = 0;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] <= MANAGER_ROLE ) { $Manager = 1; }
}

// Handle input field
//
$_stat = 1;
if ( isset($_REQUEST['filter_stat']) ) { $_stat = $_REQUEST['filter_stat']; }

// Pull Records
//
$records = ccp_get_users(0, $_stat);

// Return output w/ JSON
//
$main = array('records'=>$records, 'manager'=>$Manager);
echo json_encode($main); 

?>