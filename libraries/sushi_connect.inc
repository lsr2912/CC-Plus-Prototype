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
//---------------------------------------------------------------
// Common code to establish a connection to a SUSHI service using
// the first row of an existing $ingest_settings() array.
//---------------------------------------------------------------

  $error = '';
  $WSDL = SUSHI_WSDL_4;
  if ( !isset($retryID) ) { $retryID = 0; }

  // Setup arguments for connecting to the SUSHI service
  //
  $Provider = $ingest_settings[0]['Provider'];
  $ServiceURL = $ingest_settings[0]['server_url'];
  $Security = $ingest_settings[0]['security'];
  $AuthUser = $ingest_settings[0]['auth_username'];
  $AuthPass = $ingest_settings[0]['auth_password'];

  // Connect the WSDL using HTTP authentication
  //
  if (preg_match("/HTTP/i", $Security)) {

    try
    {
      $client = new SoapClient($WSDL,array
        (
          'login'      => $AuthUser,
          'password'   => $AuthPass,
          'location'   => $ServiceURL,
          "trace"      => 1,
          "exceptions" => 1,
        )
      );
    }
    catch (Exception $e)
    {
      $error = $e->faultstring . "(" . $e->faultcode . ")";
      fwrite(STDERR,"Failed to connect to $Provider: $error\n");
    }

  // Otherwise, connect the WSDL without a username/password
  //
  } else {

    try
    {
      $client = new SoapClient($WSDL,array(
      'location' => $ServiceURL,
      "trace"      => 1,
      "exceptions" => 1));
    }
    catch (Exception $e)
    {
      $error = $e->faultstring . "(" . $e->faultcode . ")";
      fwrite(STDERR,"Failed to connect to $Provider: $error\n");
    }

  }

  // Check for errors
  //
  if ( $error != '' ) {
    ccp_log_failed_ingest($_PROV, 0, 0, 0, "ALL", $yearmon, "SERVER", $error, $retryID);
  }
?>
