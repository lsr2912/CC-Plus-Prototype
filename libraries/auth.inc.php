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
// CC-Plus authentication test 
//
require_once ('ccplus/sessions.inc.php');

// Redirect to loginpage if not yet authenticated
//
if ( !ccp_is_authenticated() ) {
  $_SESSION['redirect_url'] = $_SERVER['PHP_SELF'];
  header("Location: " . CCPLUSROOTURL . "login.php");
  exit();
} else {
  if ( !isset($_SESSION['ccp_con_id']) ) {
    $output = "Your CC-Plus Session is damaged or invalid, please<br />";
    $output .= "<a href=\"" . CCPLUSROOTURL . "login.php\">try loggging in again</a>";
    include 'ccplus/err.html.php';
    exit();
  }
}
?>
