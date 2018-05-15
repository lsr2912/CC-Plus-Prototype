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
// logout.php
//
// CC-Plus site logout
//  Destroys session and cookies. Leaves the "remember me" cookie alone
//
require_once('ccplus/sessions.inc.php');

// Kill cookies (leave CCP_USER), including the session cookie`
// Note: This destroys the session, and not just the session data!
//
setcookie(session_name(), null, -1, '/');
unset($_COOKIE['CCP_SESS']);
setcookie("CCP_SESS", null, -1, '/');
unset($_COOKIE['PHPSESSID']);
setcookie("PHPSESSID", null, -1, '/');

// Zap session variables
//
ccp_zap_session();

// Destroy the session.
//
session_destroy();

// Print a little ditty to let user know we're done
//
print_page_header("CC-Plus Confirmation",FALSE,FALSE);
?>
<div id="maincontent">
  <p>&nbsp;</p>
  <h3 align="center">You are now logged out of the CC-Plus system.</h3>
  <p>&nbsp;</p>
  <p align="center"><font size="+1">
    You can return to
<?php
    print "the <a href=\"" . CCPLUSROOTURL . "login.php\">CC-Plus Login page here</a>.\n";
?>
  </font><p>
</div>
<?php include 'ccplus/footer.inc.html.php'; ?>
