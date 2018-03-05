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
// Common code to setup WSSE authentication for a SOAP connection
//---------------------------------------------------------------
    if ( !isset($retryID) ) { $retryID = 0; }

    // Prepare SoapHeader parameters
    class clsWSSEAuth
    {
      private $Username;
      private $Password;
      function __construct($username, $password) {
        $this->Username=$username;
        $this->Password=$password;
      }
    }
    class clsWSSEToken
    {
      private $UsernameToken;
      function __construct ($innerVal) {
        $this->UsernameToken = $innerVal;
      }
    }

    $strWSSENS = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd";
    $objSoapVarUser = new SoapVar($AuthUser, XSD_STRING, NULL, $strWSSENS, NULL, $strWSSENS);
    $objSoapVarPass = new SoapVar($AuthPass, XSD_STRING, NULL, $strWSSENS, NULL, $strWSSENS);
    $objWSSEAuth = new clsWSSEAuth($objSoapVarUser, $objSoapVarPass);
    $objSoapVarWSSEAuth = new SoapVar($objWSSEAuth, SOAP_ENC_OBJECT, NULL, $strWSSENS, 'UsernameToken', $strWSSENS);
    $objWSSEToken = new clsWSSEToken($objSoapVarWSSEAuth);
    $objSoapVarWSSEToken = new SoapVar($objWSSEToken, SOAP_ENC_OBJECT, NULL, $strWSSENS, 'UsernameToken', $strWSSENS);
    $objSoapVarHeaderVal=new SoapVar($objSoapVarWSSEToken, SOAP_ENC_OBJECT, NULL, $strWSSENS, 'Security', $strWSSENS);
    $objSoapVarWSSEHeader = new SoapHeader($strWSSENS, 'Security', $objSoapVarHeaderVal,false);

    // Prepare Soap Client
    try
      {
        $client->__setSoapHeaders(array($objSoapVarWSSEHeader));
      }
    catch (Exception $e)
      {
        $error = $e->getMessage();
        print "Failed to set SoapHeaders for $Provider: $error\n";
        ccp_log_failed_ingest($_PROV, 0, 0, 0, "ALL", $yearmon, "SERVER", $error, $retryID);
      }
?>
