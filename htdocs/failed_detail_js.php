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
// javascript target script to detail for a failed ingest
//
require_once 'ccplus/dbutils.inc.php';
include_once 'ccplus/auth.inc.php';
include_once 'ccplus/statsutils.inc.php';

$ERROR = "";
$records = array();

// Get input ID
//
$_ID = 0;
if ( isset($_REQUEST['ID']) ) { $_ID = $_REQUEST['ID']; }

// Connect to database as user
//
global $ccp_usr_cnx;
if ( !$ccp_usr_cnx ) { $ccp_usr_cnx = ccp_open_db(); }

// Query for the record
//
$found = false;
$_qry = "SELECT * FROM failed_ingest WHERE ID=" . $_ID;
try {
  $_result = $ccp_usr_cnx->query($_qry);
  $row = $_result->fetch(PDO::FETCH_ASSOC);
  if ( isset($row['ID']) ) { $found = true; }
} catch (PDOException $e) {
  $ERROR = $e->getMessage();
}


// Build message details from query result
//
if ( $found ) {
  $details  = "Ingest failed processing ".$row['process_step'].":<br />";
  $details .= $row['detail'] . "<br />";
  $details .= (MAX_INGEST_RETRIES-$row['retry_count']) . " retries remain.";
} else {
  $details = "No details found";
}

// Build return data
//
if ( $ERROR != "" ) {
  $output = array ("error"=>1 , "message"=>$ERROR);
} else {
  $output = array ("detail"=>$details);
}

// Return output w/ JSON
//
echo json_encode($output); 
?>
