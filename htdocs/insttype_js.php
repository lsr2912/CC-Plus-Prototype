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
// javascript target script to return institution ID/name pairs
// matching a given type as a json array
//
require_once('ccplus/dbutils.inc.php');
include_once 'ccplus/auth.inc.php';

$_type = "ALL";
if ( isset($_REQUEST['Itype']) ) { $_type=$_REQUEST['Itype']; }

$insts = ccp_get_institutions_ui($_type);

$main = array('insts'=>$insts);
echo json_encode($main); 
?>
