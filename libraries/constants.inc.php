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
// CC-Plus constants
//
// Negate 'magic_quotes' if on
// ---------------------------
if (get_magic_quotes_gpc()) {
  if (!function_exists("stripslashes_deep")) {
    function stripslashes_deep($value) {
      $value = is_array($value) ?
                      array_map('stripslashes_deep', $value) :
                      stripslashes($value);
      return $value;
    }
    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
  }
}

//
// Directories / Paths
//
define ("CCPLUSAGENTS", "__CCPLUSAGENTS__");
define ("CCPLUSROOTURL", "__CCPLUSROOTURL__");
//
// This path not (yet) set by the Installer. It *ALSO* needs
// to be Aliased in the Apache config file as "/raw_reports",
// for example, if ROOTURL is "/", then:
//   Alias "/raw_reports" "/usr/local/stats_reports"
//   <Directory /usr/local/stats_reports/>
//           AllowOverride All
//           Options All +Indexes
//           Require all granted
//           Order allow,deny
//           Allow from all
//   </Directory>
//
define ("CCPLUSREPORTS", "/usr/local/stats_reports/");

// Global variables
// ----------------
date_default_timezone_set('UTC');

// COOKIE_LIFE allows valid logins to persist (via cookie)
// and be sessions to be reconstructed for N-days
//
define ("CCP_COOKIE_LIFE", strtotime( '+__COOKIELIFE__ days' ));
$MONTHS = array(
    "01" => "Jan", "02" => "Feb", "03" => "Mar", "04" => "Apr", "05" => "May", "06" => "Jun",
    "07" => "Jul", "08" => "Aug", "09" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec");
for ($i = 2009; $i <= date('Y'); $i++) { $YEARS[] = $i; }
//
// Set constants for roles
// NOTE: If values in ccplus_con_template:roles table are changed, the
//       same updates need to be applied here to match the new settings
//
define('ADMIN_ROLE', 1);
define('MANAGER_ROLE', 10);
define('USER_ROLE', 20);

// User authorization
// ------------------
$authorised = 'Yes';
$userRole = '';
$LoginID = 'dum';

// Ingests and Alerts 
// ------------------
define('SILENCE_DAYS', 10);
define('MAX_INGEST_RETRIES', 10);

// Logs and Logging 
// ----------------

//
// SUSHI-related constants
// -----------------------
define ("SUSHI_WSDL_3", "http://www.niso.org/schemas/sushi/counter_sushi3_0.wsdl");
define ("SUSHI_WSDL_4", "http://www.niso.org/schemas/sushi/counter_sushi4_1.wsdl");
// Time between retries and max # retry attempts
define('SUSHI_RETRY_SLEEP', 30);
define('SUSHI_RETRY_LIMIT', 20);

?>
