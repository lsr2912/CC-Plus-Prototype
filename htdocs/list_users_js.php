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

// Check role, set flag if admin and limit INST if manager
//
$is_admin = 0;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] == ADMIN_ROLE ) {
    $_INST = 0;
    $is_admin = 1;
  }
  if ( $_SESSION['role'] > ADMIN_ROLE && $_SESSION['role'] <= MANAGER_ROLE) {
    $_INST = $_SESSION['user_inst'];
  }
}

// Handle input field
//
$_stat = 1;
if ( isset($_REQUEST['filter_stat']) ) { $_stat = $_REQUEST['filter_stat']; }

// Pull Records
//
$records = ccp_get_users(0, $_stat, $_INST);

// Signal if no records matched
//
if ( count($records) == 0 ) {
  $records[] = array ("error"=>1 , "message"=>"No matching records for search criteria");
}

// Return output w/ JSON
//
$main = array('records'=>$records, 'admin'=>$is_admin);
echo json_encode($main); 

?>
