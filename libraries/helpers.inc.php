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
// CC-Plus helper functions
//
include_once 'ccplus/constants.inc.php';

if (!function_exists("array_sort_by_column")) {
  function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {

    $sort_col = array(); 
    foreach ($arr as $key=> $row) { 
      $sort_col[$key] = $row[$col]; 
    } 
    if($dir == "SORT_ASC") {
      array_multisort($sort_col, SORT_ASC, $arr); 
    } else {
      array_multisort($sort_col, SORT_DESC, $arr); 
    }
  }
}
 
if (!function_exists("clean_filename")) {
  function clean_filename($filename) {
    $search  = array(" ", "&",   "$", ",", "!", "@", "#", "^", "(", ")", "+", "=", "[", "]", "/");
    $replace = array("_", "and", "S", "_",  "",  "",  "",  "",  "",  "",  "",  "",  "",  "",  "");
     
    return str_replace($search,$replace,$filename); 
  }
}  

if (!function_exists("createYMarray")) {
  function createYMarray($from, $to) {

    $range = array();
    $start = 1;
    $end = 0;
    if (is_string($from) === true) $start = strtotime($from);
    if (is_string($to) === true ) $end = strtotime($to);
    if ($start > $end) { return $range; }

    while($start <= $end) {
      $range[] = date('Y-m', $start);
      $start = strtotime("+1 month", $start);
    }

    return $range;
  }
} 

if (!function_exists("MonthCount")) {
  function MonthCount($from, $to) {

    $from_ts = strtotime($from);
    $to_ts = strtotime($to);

    $from_yr = date('Y', $from_ts);
    $to_yr = date('Y', $to_ts);
    $from_mo = date('m', $from_ts);
    $to_mo = date('m', $to_ts);

    return (($to_yr-$from_yr)*12) + ($to_mo-$from_mo);
  }
} 

if (!function_exists("html")) {
  function html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists("htmlout")) {
  function htmlout($text) {
    echo html($text);
  }
}

if (!function_exists("prettydate")) {
  function prettydate($date) {
    list($yyyy, $mm) = explode("-", $date);
    return date("M-Y", mktime(0, 0, 0, $mm, 1, $yyyy));  
  }
}


if (!function_exists("print_page_header")) {
  function print_page_header() {
    //------------------------------------------------------------------------------------------
    //
    // Usage: print_page_header ( $title [,$userdata] [,$crumbs] [,$jqueryUI] )
    //
    // Arguments:
    //     $title : page title
    //  $userdata : boolean indicates whether to include user-data elements
    //    $crumbs : array of strings to build a breacrumb list from
    //  $jqueryUI : boolean flag to have Jquery UI elements loaded in the header
    //
    //------------------------------------------------------------------------------------------
    //
    // Check input arguments
    //
    $userdata = FALSE;
    $jqueryUI = FALSE;
    $tabsort = FALSE;
    $crumbs = array();

    $nargs = func_num_args();
    if ($nargs < 1) die ("Title is required by print_page_header()");
    $title = func_get_arg(0);
    if ($nargs >= 2) { $userdata = func_get_arg(1); }
    if ($nargs >= 3) { $crumbs = func_get_arg(2); }
    if ($nargs >= 4) { $jqueryUI = func_get_arg(3); }
    $n_crumbs = count($crumbs);

    //
    // Setup the initial HTML
    //
    print <<< EOT1
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <title>$title</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <!-- Latest compiled and minified jquery JavaScript       -->
    <!-- Load local, downloaded copies from http://jquery.com -->
    <script src="/include/jquery-3.1.1.min.js"></script>

EOT1;
    if ( $jqueryUI ) {
      print <<< JQ1

  <link href="/include/jquery-ui.css" rel="stylesheet">
  <script src="/include/jquery-ui.min.js"></script>
  <script>
    $(function() {
      $('.datepicker').datepicker({
        dateFormat: "yy-mm-dd"
      });
    });
  </script>

JQ1;
    }
    $_on_reports = FALSE;
    $_on_adminhome = FALSE;
    $_role = 0;
    if ( isset($_SESSION['role']) ) { $_role = $_SESSION['role']; }
    if ( preg_match('/ReportHome\.php/',$_SERVER['REQUEST_URI']) ) { $_on_reports = TRUE; }
    if ( preg_match('/AdminHome\.php/',$_SERVER['REQUEST_URI']) ) { $_on_adminhome = TRUE; }

    print "    <link rel=\"stylesheet\" href=\"" . CCPLUSROOTURL . "include/ccplus.css\" type=\"text/css\" />\n";
    print "    <style type=\"text/css\" media=\"all\"> @import \"" . CCPLUSROOTURL . "include/global.css\";</style>\n";
    print "  </head>\n  <body>\n";
    print "    <div>\n";
    print "      <p style=\"float:left;\">\n";
    $__role = 0;
    if ( isset($_SESSION['role']) ) { $__role = $_SESSION['role']; }
    if ( $__role<=MANAGER_ROLE && !$_on_adminhome ) {
      print "        <a href=\"" . CCPLUSROOTURL . "AdminHome.php\">Administration Home</a><br /><br />\n";
    }
    if ( !$_on_reports ) {
      print "        <a href=\"" . CCPLUSROOTURL . "ReportHome.php\">Reports Home</a>\n";
    }
    print "      </p>\n";
    print "      <p style=\"float:right;\">\n";
    print "        <a href=\"" . CCPLUSROOTURL . "Wiki\">Help & Documentation</a><br /><br />\n";
    if ( $userdata ) {
      $ident = "";
      if ( isset($_SESSION['first_name']) ) { $ident .= $_SESSION['first_name']." "; }
      if ( isset($_SESSION['last_name']) ) { $ident .= $_SESSION['last_name']." "; }
      if ( isset($_SESSION['role']) ) { $ident .= "(" . ccp_role_name($_SESSION['role']) . ")"; }
      print $ident;
      print "<br />\n        <a href=\"" . CCPLUSROOTURL . "logout.php\">Logout</a> &nbsp; | ";
      print "<a href=\"" . CCPLUSROOTURL . "ManageUser.php?me=1\">Profile</a>\n";
    }
    print "      </p>\n    </div>\n";
    print "    <div id=\"body-container\">\n";
    print "      <h1 style=\"text-align:center\">" . $title . "</h1>\n";

    if ( count($crumbs) > 0 ) {
      print "      <ol class=\"breadcrumb\">\n";
      $_cc=1;
      foreach ($crumbs as $c) {
         if ( $_cc < count($crumbs) ) {
            print "        <li><a href=\"" . $c[1] . "\">" . $c[0] . "</a></li>\n";
         } else {
            // print "        <li class=\"current\">" . $c[0] . "</li>\n";
            print "        <li>" . $c[0] . "</li>\n";
         }
         $_cc++;
       }
       print "      </ol>\n";
    }
    print "      <div style=\"clear: both;\"></div>\n";
  }
}

// Display no-access error message
// (builds and prints a table-row)
//
if (!function_exists("print_noaccess_error")) {
  function print_noaccess_error() {
print <<< ERR1
  <Blockquote>
  Your account does not have sufficient access to use the requested resource or page.<br />
  Contact your CC-Plus Consortium administrator or the CC-Plus site administrator to<br />
  gain greater access.
</Blockquote>
ERR1;
  }
}
?>
