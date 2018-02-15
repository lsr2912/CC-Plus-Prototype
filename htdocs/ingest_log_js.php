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
// javascript target script to return rows for the ingest log viewer
//
require_once 'ccplus/dbutils.inc.php';
include_once 'ccplus/auth.inc.php';
include_once 'ccplus/statsutils.inc.php';

// Check if manager, set flag and pass to JS with records
//
$Manager = 0;
if ( isset($_SESSION['role']) ) {
  if ( $_SESSION['role'] <= MANAGER_ROLE ) { $Manager = 1; }
}

$records = array();

// Handle possible input fields
//
$prov = 0;
if ( isset($_REQUEST['filter_prov']) ) { $prov = $_REQUEST['filter_prov']; }
$inst = 0;
if ( isset($_REQUEST['filter_inst']) ) { $inst = $_REQUEST['filter_inst']; }
$from = 0;
if ( isset($_REQUEST['filter_from']) ) { $from = $_REQUEST['filter_from']; }
$to = 0;
if ( isset($_REQUEST['filter_to']) ) { $to = $_REQUEST['filter_to']; }
$stat = "ALL";
if ( isset($_REQUEST['filter_stat']) ) { $stat = $_REQUEST['filter_stat']; }
$rept = "";
if ( isset($_REQUEST['filter_rept']) ) { $rept = $_REQUEST['filter_rept']; }
if ( $rept=="ALL" ) { $rept = ""; }

$ERROR = "";
if ( $to < $from ) {
  $ERROR = "End Date (" . $to . ") is before Start Date (" . $from . ").";
}

// Pull Records
//
if ( $ERROR != "") {
  $records[] = array ("error"=>1 , "message"=>$ERROR);
} else {
  $records = ccp_get_ingest_record( $prov, $inst, $from, $to, $stat, $rept);
}

// Signal if no records matched
//
if ( count($records) == 0 ) {
  $records[] = array ("error"=>1 , "message"=>"No matching records for search criteria");
}

// Return output w/ JSON
//
$main = array('records'=>$records, 'manager'=>$Manager);
echo json_encode($main); 
?>
