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

      // This include file works together with "sushi_connect", and expects to be
      // included within a loop structure like this (array names matter!):
      //   $ingest_settings = ccp_get_sushi_settings( $inst_id, $_PROV, $day );
      //   foreach ( $ingest_settings as $_settings ) {
      //     $_reports = ccp_get_counter_reports( $_PROV );
      //     foreach ( $_reports as $_Rpt ) {
      //       << Include it here >>
      //     }
      //     << Handle XML >>
      //   }
      // ALSO:
      //   $_PROV (int ID), $_INST (int ID), $Begin (YYYY-mm-dd), and $End (YYYY-mm-dd)
      // need to be defined before including/using this file.

        // Test+set these, just-in-case... should not be necessary
        //
        if ( !isset($retryID) ) { $retryID = 0; }
        if ( !isset($ReportArg) ) { $ReportArg = "ALL"; }
        if ( !isset($yearmon) ) { $yearmon = substr(0,7,$Begin); }
        if ( !isset($_Agent) ) { $_Agent = uniqid("CCplusSUSHI:", true); }
        if ( !isset($Created) ) { $Created = date("Y-m-d\TH:i:s.0\Z"); }

        // Get name for this report, if we're just pulling one, test it
        //
        $Report = $_Rpt['Report_Name'];
        if ( ($ReportArg != "ALL") && ($Report != $ReportArg) ) { return 1; }
        $Release = $_Rpt['revision'];

        // Keep asking as long as the provider queues the request
        //
        $req_state = "queued";
        $queue_retries = 0;
        while ( $queue_retries<=SUSHI_RETRY_LIMIT && $req_state=="queued" ) {

          // Make the request
          //
          try {
            $result = $client->GetReport(array
                      (
                       'Requestor' => array
                        (
                         'ID' => $_settings['RequestorID'],
                         'Name' => $_settings['RequestorName'],
                         'Email' => $_settings['RequestorEmail']
                        ),
                       'CustomerReference' => array
                        (
                         'ID' => $_settings['CustRefID'],
                         'Name' => $_settings['CustRefName']
                        ),
                       'ReportDefinition'  => array
                        (
                         'Filters' => array
                          (
                           'UsageDateRange' => array
                            (
                             'Begin' => $Begin,
                             'End' => $End
                            )
                          ),
                         'Name' => $Report,
                         'Release' => $Release
                        ),
                       'Created' => $Created,
                       'ID' => $_Agent
                      )
                    );

          } catch(Exception $e) {

            $error = $e->faultstring . "(" . $e->faultcode . ")";
            print "Failed to GetReport from $Provider: $error\n";
            if ( isset($_settings['ID']) ) {
              ccp_log_failed_ingest($_PROV,$_INST,$_settings['ID'],$_Rpt['ID'],$Report,$yearmon,"SUSHI",$error,$retryID);
            }
            return 1;
          }

          $xml = $client->__getLastResponse();
          $req_state = "done";
 
          // Check for errors
          //
          libxml_use_internal_errors(true);
          $report = simplexml_load_string($xml);
          $output = 'Unknown, generic SUSHI error';
          if ( $report === FALSE ) {
            $errors = libxml_get_errors();
            print "ERROR loading $Report from $Provider for $Begin to $End\n";
            foreach ($errors as $error) {
              $output = "XML Error (" . $error->code . ") : " . $error->message . "\n";
              print $output;
              if ( isset($_settings['ID']) ) {
                ccp_log_failed_ingest($_PROV,$_INST,$_settings['ID'],$_Rpt['ID'],$Report,$yearmon,"XML",$output,$retryID);
              }
            }
            $errors = libxml_clear_errors();
            return 1;
          } else {
            // Check if an Exception is set as the ReportResponse.
            // If so, extract the number and message and print it out.
            //
            $e_num = "";
            $e_msg = "";
            $retry = false;

            if ( preg_match("/<(\w+:)?Exception.*<\/(\w+:)?Exception>/i", $report->asXML(), $matches ) ) {
              $exception = $matches[0];
              if ( preg_match("/<(\w+:)?Number>(.*)<\/(\w+:)?Number>/i", $exception, $matches) ) {
                $e_num = $matches[2];
              }
              if ( preg_match("/<(\w+:)?Message>(.*)<\/(\w+:)?Message>/i", $exception, $matches) ) {
                $e_msg = $matches[2];
              }
              // if message matches "queued" conditions, sleep and retry
              //
              if ( preg_match('/Queue/i', $e_msg) ) { $retry = true; }
              if ( $e_num==4001 || $e_num==4002 )   { $retry = true; }	// Wiley
              if ( $retry && $queue_retries<SUSHI_RETRY_LIMIT ) {
                 $queue_retries++;
                 print "Queued ... sleeping: " . $e_msg . "(" . $e_num . ") ...";
                 sleep(SUSHI_RETRY_SLEEP);
                 print "Retrying\n";
                 $req_state = "queued";
                 continue;
              } else {
                 if ( $e_msg!="" || $e_num!="" ) {
                   $output = "SUSHI Error (" . $e_num . ") : " . $e_msg;
                 }
                 if ( $retry && $queue_retries>=SUSHI_RETRY_LIMIT ) {
                   $output .= " (retry limit: " . SUSHI_RETRY_LIMIT . " reached).";
                 }
                 $req_state = "done";
              }
              print $output."\n";
              if ( $req_state != "queued" ) {
                if ( isset($_settings['ID']) ) {
                  ccp_log_failed_ingest($_PROV,$_INST,$_settings['ID'],$_Rpt['ID'],$Report,$yearmon,"XML",$output,$retryID);
                }
                return 1;
              }
            }
          }
        }       // Loop until not-queued

        return 'Success';
?>
