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
// Counter report processing functions
//
// Parse a JR1 counter report
//
// Arguments:
//  $in_xml  :  Input filename to be processed
//  $out_csv :  (Optional) file to hold XML data as CSV
//  $begin   :  Reporting period start date as YYYY-MM-DD
//  $end     :  Reporting period end date as YYYY-MM-DD
// Returns:
//  $status : a string containing an error, or the string "Success"
//--------------------------------------------------------------------------------------
if (!function_exists("parse_counter_JR1v4")) {
  function parse_counter_JR1v4 ($in_xml, $out_csv="", $begin, $end ) {

    // Setup error handling for opening the file
    //
    $use_errors = libxml_use_internal_errors(true);
    $raw = simplexml_load_file( $in_xml );
    if ( $raw === FALSE ) {
      return "Failed loading XML";
    }
    libxml_clear_errors();
    libxml_use_internal_errors($use_errors);

    // Setup month-looping range control
    //
    $start = new DateTime($begin);
    $interval = new DateInterval('P1M'); 
    $stop = new DateTime($end);
    $period = new DatePeriod($start, $interval, $stop);

    // If out_csv non-null, set flag
    //
    $OUTFIL = FALSE;
    if ( $out_csv != "" ) { $OUTFIL = TRUE; }

    // Find and register the sushi-counter namespace(s).
    // If not found, bail with an error.
    //
    $have_c = FALSE;
    $have_sc = FALSE;
    $customers = array();
    $namespaces = $raw->getNamespaces(true);
    foreach ( $namespaces as $_key=>$_value ) {
      if ( $_value == "http://www.niso.org/schemas/sushi/counter") {
        $raw->registerXPathNamespace('sc', $_value);
        $have_sc = TRUE;
      }
      if ( $_value == "http://www.niso.org/schemas/counter") {
        $raw->registerXPathNamespace('c', 'http://www.niso.org/schemas/counter');
        $have_c = TRUE;
      }
    }
    if ( !$have_c && !$have_sc ) {
      return "Missing SUSHI-Counter namespace in document!?";
    }

    // Map the Customer section of the report to an array
    //
    if ( $have_c ) { $customers = $raw->xpath('//c:Customer'); }
    if ( (count($customers) == 0) && $have_sc ) {
      $sc_repo = $raw->xpath('//sc:Report');
      $customers = $sc_repo[0]->Report->Customer;
    }
    if ( count($customers) == 0 ) {
      return "Cannot find any Customer data in the XML!";
    }

    // Run a first-pass through the report to sum totals
    //
    foreach ($customers as $cust) {
      $total_pdf = 0;
      $total_html = 0;
      $grand_ttl = 0;
      $monthly_total = array();
      foreach ($cust->ReportItems as $item) {
        foreach ($item->ItemPerformance as $perf) {

          foreach ( $period as $_dt ) {
            $_begin = $_dt->format('Y-m-01');
            $_end = $_dt->format('Y-m-t');
            $_col = $_dt->format('M-Y');
            if ( !isset($monthly_total[$_col]) ) { $monthly_total[$_col] = 0; }
            $found_TTL = FALSE;
            if ( $perf->Period->Begin == $_begin  &&  $perf->Period->End == $_end ) {
              foreach ($perf->Instance as $inst) {
                if ( $inst->MetricType == "ft_pdf" ) { $total_pdf += $inst->Count; }
                if ( $inst->MetricType == "ft_html" ) { $total_html += $inst->Count; }
                if ( $inst->MetricType == "ft_total" && !$found_TTL ) { 
                  $found_TTL = TRUE;
                  $grand_ttl += $inst->Count;
                  $monthly_total[$_col] += $inst->Count;
                }
              }
            }
          }
        }       // for all ItemPerformance
      }         // for all report-items

      // Put out report header and total rows
      //
      if ( $OUTFIL ) {
        $inst_ident = "";
        $csv_out = fopen ($out_csv, 'w');
        fwrite($csv_out, "Journal Report 1 (R4),Number of Successful Full-Text Article Requests by Month and Journal\n");
        fwrite($csv_out, (string) $cust->Name . "\n");
        foreach ( $cust->InstitutionalIdentifier as $_iid ) {
          $inst_ident = (string) $_iid->Value;
        }
        if ( $inst_ident != "") { fwrite($csv_out, $inst_ident . "\n"); }
        fwrite($csv_out, "Period covered by Report:\n");
        fwrite($csv_out, $begin . " to " . $end . "\n");
        fwrite($csv_out, "Date run:\n" . date("Y-m-d") . "\n");
        $colhdr  = "Journal,Publisher,Platform,Journal DOI,Proprietary Identifier,Print ISSN,Online ISSN,Reporting Period Total,";
        $colhdr .= "Reporting Period HTML,Reporting Period PDF";
        $total_row = "Total for all journals,,,,,,," . $grand_ttl . "," . $total_html . "," . $total_pdf;
        foreach ( $period as $_dt ) {
          $_str = $_dt->format('M-Y');
          $colhdr .= "," . $_str;
          $total_row .= "," . $monthly_total[$_str];
        }
        $colhdr .= "\n";
        $total_row .= "\n";
        fwrite($csv_out, $colhdr);
        fwrite($csv_out, $total_row);
      }
  
      // Parse out the document by sections to get down to the metrics
      //
      foreach ($cust->ReportItems as $item) {

        if ( $OUTFIL ) {

          // Set identifiers
          //
          $_prop_id = "";
          $_print_issn = "";
          $_online_issn = "";
          $_journal_doi = "";
          foreach ( $item->ItemIdentifier as $_id ) {
            if ( $_id->Type == "Proprietary" ) { $_prop_id = $_id->Value; }
            if ( $_id->Type == "Print_ISSN" ) { $_print_issn = $_id->Value; }
            if ( $_id->Type == "Online_ISSN" ) { $_online_issn = $_id->Value; }
            if ( $_id->Type == "DOI" ) { $_journal_doi = $_id->Value; }
          }

          // Other item-level columns
          //
          $_name = "";
          $_publisher = "";
          $_platform = "";
          if ( isset($item->ItemName) ) { $_name = $item->ItemName; }
          if ( isset($item->ItemPlatform) ) { $_platform = $item->ItemPlatform; }
          if ( isset($item->ItemPublisher) ) { $_publisher = $item->ItemPublisher; }

          // Setup the output file record up-to the counts;
          // enclose name, publisher and platform in quotes
          //
          $output_line = "\"" . $_name . "\",\"" . $_publisher . "\",\"" . $_platform . "\",";
          $output_line .= $_journal_doi . "," . $_prop_id . "," . $_print_issn . "," . $_online_issn;

        }	// end-if building output file

        // Loop by-month to be parsed and, for matching months, loop through
        // the count-metrics instances to get and sum counts
        //
        $_rp_pdf = 0;
        $_rp_html = 0;
        $_rp_total = 0;
        $_monthly_counts = "";
        foreach ( $period as $_dt ) {
          $_begin = $_dt->format('Y-m-01');
          $_end = $_dt->format('Y-m-t');
          $found_TTL = FALSE;
          foreach ($item->ItemPerformance as $perf) {
            if ( $perf->Period->Begin == $_begin  &&  $perf->Period->End == $_end ) {
              foreach ($perf->Instance as $inst) {
                if ( $inst->MetricType == "ft_pdf" ) { $_rp_pdf += $inst->Count; }
                if ( $inst->MetricType == "ft_html" ) { $_rp_html += $inst->Count; }
                if ( $inst->MetricType == "ft_total" && !$found_TTL ) { 
                  $_monthly_counts .= "," . $inst->Count;
                  $found_TTL = TRUE;
                  $_rp_total += $inst->Count;
                }
              }
            }
          }
          if ( !$found_TTL ) { $_monthly_counts .= ","; }
        }

        // Write the CSV output
        //
        if ( $OUTFIL ) {
          $output_line .= "," . $_rp_total . "," . $_rp_html . "," . $_rp_pdf;
          $output_line .= $_monthly_counts . "\n";
          fwrite ($csv_out, $output_line);
        }
      }		// End foreach ReportItem
    }		// End foreach Customer
    return "Success";
  }
}

// Parse a JR2 counter report
//
// Arguments:
//  $in_xml  :  Input filename to be processed
//  $out_csv :  (Optional) file to hold XML data as CSV
//  $begin   :  Reporting period start date as YYYY-MM-DD
//  $end     :  Reporting period end date as YYYY-MM-DD
// Returns:
//  $status : a string containing an error, or the string "Success"
//
if (!function_exists("parse_counter_JR2v4")) {
  function parse_counter_JR2v4 ($in_xml, $out_csv="", $begin, $end ) {

    // Setup error handling for opening the file
    //
    $use_errors = libxml_use_internal_errors(true);
    $raw = simplexml_load_file( $in_xml );
    if ( $raw === FALSE ) {
      return "Failed loading XML";
    }
    libxml_clear_errors();
    libxml_use_internal_errors($use_errors);

    // Setup month-looping range control
    //
    $start = new DateTime($begin);
    $interval = new DateInterval('P1M'); 
    $stop = new DateTime($end);
    $period = new DatePeriod($start, $interval, $stop);

    // If out_csv non-null, set flag
    //
    $OUTFIL = FALSE;
    if ( $out_csv != "" ) { $OUTFIL = TRUE; }

    // Find and register the sushi-counter namespace(s).
    // If not found, bail with an error.
    //
    $have_c = FALSE;
    $have_sc = FALSE;
    $customers = array();
    $namespaces = $raw->getNamespaces(true);
    foreach ( $namespaces as $_key=>$_value ) {
      if ( $_value == "http://www.niso.org/schemas/sushi/counter") {
        $raw->registerXPathNamespace('sc', $_value);
        $have_sc = TRUE;
      }
      if ( $_value == "http://www.niso.org/schemas/counter") {
        $raw->registerXPathNamespace('c', 'http://www.niso.org/schemas/counter');
        $have_c = TRUE;
      }
    }
    if ( !$have_c && !$have_sc ) {
      return "Missing SUSHI-Counter namespace in document!?";
    }

    // Map the Customer section of the report to an array
    //
    if ( $have_c ) { $customers = $raw->xpath('//c:Customer'); }
    if ( (count($customers) == 0) && $have_sc ) {
      $sc_repo = $raw->xpath('//sc:Report');
      $customers = $sc_repo[0]->Report->Customer;
    }
    if ( count($customers) == 0 ) {
      return "Cannot find any Customer data in the XML!";
    }

    // Loop over all customers in the file
    //
    foreach ($customers as $cust) {

      // Run a first-pass through the report to sum totals
      //
      $RP_Total_TA = 0;
      $RP_Total_NL = 0;
      $monthly_TA = array();
      $monthly_NL = array();
      foreach ($cust->ReportItems as $item) {
        foreach ( $period as $_dt ) {
          $_begin = $_dt->format('Y-m-01');
          $_end = $_dt->format('Y-m-t');
          $_col = $_dt->format('M-Y');
          if ( !isset($monthly_TA[$_col]) ) { $monthly_TA[$_col] = 0; }
          if ( !isset($monthly_NL[$_col]) ) { $monthly_NL[$_col] = 0; }
          $found_TA = FALSE;
          $found_NL = FALSE;
          foreach ($item->ItemPerformance as $perf) {
            if ( $perf->Period->Begin == $_begin  &&  $perf->Period->End == $_end ) {
              foreach ($perf->Instance as $inst) {
                if ( $inst->MetricType == "turnaway" && !$found_TA ) {
                  $monthly_TA[$_col] .= "," . $inst->Count;
                  $RP_Total_TA += $inst->Count;
                  $found_TA = TRUE;
                }
                if ( $inst->MetricType == "no_license" && !$found_NL ) {
                  $monthly_NL[$_col] .= "," . $inst->Count;
                  $RP_Total_NL += $inst->Count;
                  $found_NL = TRUE;
                }
              }
            }
          }     // for all ItemPerformance
        }       // for all time-periods 
      }         // for all report-items

      // Put out report header and total rows
      //
      if ( $OUTFIL ) {
        $inst_ident = "";
        $csv_out = fopen ($out_csv, 'w');
        fwrite($csv_out, "Journal Report 2 (R4),Access Denied to Full-Text Articles by Month Journal and Category\n");
        fwrite($csv_out, (string) $cust->Name . "\n");
        foreach ( $cust->InstitutionalIdentifier as $_iid ) {
          $inst_ident = (string) $_iid->Value;
        }
        if ( $inst_ident != "") { fwrite($csv_out, $inst_ident . "\n"); }
        fwrite($csv_out, "Period covered by Report:\n");
        fwrite($csv_out, $begin . " to " . $end . "\n");
        fwrite($csv_out, "Date run:\n" . date("Y-m-d") . "\n");

        $colhdr  = "Journal,Publisher,Platform,Journal DOI,Proprietary Identifier,Print ISSN,Online ISSN,";
        $colhdr .= "Access Denied Category,Reporting Period Total";
        $total_row_TA = ",,,,,,,," . $RP_Total_TA;
        $total_row_NL = ",,,,,,,," . $RP_Total_NL;

        foreach ( $period as $_dt ) {
          $_str = $_dt->format('M-Y');
          $colhdr .= "," . $_str;
          $total_row_TA .= "," . $monthly_TA[$_str];
          $total_row_NL .= "," . $monthly_NL[$_str];
        }
        fwrite($csv_out, $colhdr . "\n");
        fwrite($csv_out, $total_row_TA . "\n");
        fwrite($csv_out, $total_row_NL . "\n");
      }

      // Parse out the document by sections to get down to the metrics
      //
      foreach ($cust->ReportItems as $item) {

        if ( $OUTFIL ) {

          // Set identifiers
          //
          $_prop_id = "";
          $_print_issn = "";
          $_online_issn = "";
          $_journal_doi = "";
          foreach ( $item->ItemIdentifier as $_id ) {
            if ( $_id->Type == "Proprietary" ) { $_prop_id = $_id->Value; }
            if ( $_id->Type == "Print_ISSN" ) { $_print_issn = $_id->Value; }
            if ( $_id->Type == "Online_ISSN" ) { $_online_issn = $_id->Value; }
            if ( $_id->Type == "DOI" ) { $_journal_doi = $_id->Value; }
          }
 
          // Other item-level columns
          //
          $_name = "";
          $_publisher = "";
          $_platform = "";
          if ( isset($item->ItemName) ) { $_name = $item->ItemName; }
          if ( isset($item->ItemPlatform) ) { $_platform = $item->ItemPlatform; }
          if ( isset($item->ItemPublisher) ) { $_publisher = $item->ItemPublisher; }

          // Setup the 2 output lines, one for each turnaway type
          //
          $turnaway_line  = $_name . "," . $_publisher . "," . $_platform . "," . $_journal_doi . "," . $_prop_id . ",";
          $turnaway_line .= $_print_issn . "," . $_online_issn;
          $nolicense_line = $turnaway_line;
          $turnaway_line  .= ",Access denied: concurrent/simultaneous user license limit exceeded";
          $nolicense_line .= ",Access denied: content item not licensed";

        }	// end-if building output file

        // Loop by-month to be parsed and, for matching months, loop through
        // the count-metrics instances to get and sum counts
        //
        $RP_Total_TA = 0;
        $RP_Total_NL = 0;
        $_monthly_counts_TA = "";
        $_monthly_counts_NL = "";
        foreach ( $period as $_dt ) {
          $_begin = $_dt->format('Y-m-01');
          $_end = $_dt->format('Y-m-t');
          $found_TA = FALSE;
          $found_NL = FALSE;
          foreach ($item->ItemPerformance as $perf) {
            if ( $perf->Period->Begin == $_begin  &&  $perf->Period->End == $_end ) {
              foreach ($perf->Instance as $inst) {
                if ( $inst->MetricType == "turnaway" && !$found_TA ) {
                  $RP_Total_TA += $inst->Count;
                  $_monthly_counts_TA .= "," . $inst->Count;
                  $found_TA = TRUE;
                }
                if ( $inst->MetricType == "no_license" && !$found_NL ) {
                  $RP_Total_NL += $inst->Count;
                  $_monthly_counts_NL .= "," . $inst->Count;
                  $found_NL = TRUE;
                }
              }
            }
          }
          if ( !$found_TA ) { $_monthly_counts_TA .= ","; }
          if ( !$found_NL ) { $_monthly_counts_NL .= ","; }
        }

        // Write the CSV output
        //
        if ( $OUTFIL ) {
          $turnaway_line .= "," . $RP_Total_TA . $_monthly_counts_TA . "\n";
          $nolicense_line .= "," . $RP_Total_NL . $_monthly_counts_NL . "\n";
          fwrite ($csv_out, $turnaway_line);
          fwrite ($csv_out, $nolicense_line);
        }
      }		// End foreach ReportItem
    }		// End foreach Customer
    return "Success";
  }
}

//--------------------------------------------------------------------------------------
// Parse a JR5 counter report
//
// Arguments:
//  $in_xml  :  Input filename to be processed
//  $out_csv :  (Optional) file to hold XML data as CSV
//  $begin   :  Reporting period start date as YYYY-MM-DD
//  $end     :  Reporting period end date as YYYY-MM-DD
// Returns:
//  $status : a string containing an error, or the string "Success"
//--------------------------------------------------------------------------------------
if (!function_exists("parse_counter_JR5v4")) {
  function parse_counter_JR5v4 ($in_xml, $out_csv="", $begin, $end ) {

    // Setup error handling for opening the file
    //
    $use_errors = libxml_use_internal_errors(true);
    $raw = simplexml_load_file( $in_xml );
    if ( $raw === FALSE ) {
      return "Failed loading XML";
    }
    libxml_clear_errors();
    libxml_use_internal_errors($use_errors);

    // Setup month-looping range control
    //
    $start = new DateTime($begin);
    $interval = new DateInterval('P1M'); 
    $stop = new DateTime($end);
    $period = new DatePeriod($start, $interval, $stop);

    // If out_csv non-null, we're creating an output file too
    // set a flag and put out the headers
    //
    $OUTFIL = FALSE;
    if ( $out_csv != "" ) {
      $OUTFIL = TRUE;
      $csv_out = fopen ($out_csv, 'w');
    }

    // Find and register the sushi-counter namespace(s).
    // If not found, bail with an error.
    //
    $have_c = FALSE;
    $have_sc = FALSE;
    $customers = array();
    $namespaces = $raw->getNamespaces(true);
    foreach ( $namespaces as $_key=>$_value ) {
      if ( $_value == "http://www.niso.org/schemas/sushi/counter") {
        $raw->registerXPathNamespace('sc', $_value);
        $have_sc = TRUE;
      }
      if ( $_value == "http://www.niso.org/schemas/counter") {
        $raw->registerXPathNamespace('c', 'http://www.niso.org/schemas/counter');
        $have_c = TRUE;
      }
    }
    if ( !$have_c && !$have_sc ) {
      return "Missing SUSHI-Counter namespace in document!?";
    }
    
    // Map the Customer section of the report to an array
    //
    if ( $have_c ) { $customers = $raw->xpath('//c:Customer'); }
    if ( (count($customers) == 0) && $have_sc ) {
      $sc_repo = $raw->xpath('//sc:Report');
      $customers = $sc_repo[0]->Report->Customer;
    }
    if ( count($customers) == 0 ) {
      return "Cannot find any Customer data in the XML!";
    }

    // Run a first-pass through the report to build an array of Year-of-Publication
    // values and the counts for each one. Ranged (From-To or Pre-YYYY) go into a
    // separate array and added after the YOPS are sorted. YOP=9999 is treated as
    // "Articles in Press" and like other YOPs.
    //
    foreach ($customers as $cust) {
      $YOPS = array();
      $YOP_ranges = array();
      $yop_totals = array();
      foreach ($cust->ReportItems as $item) {
        foreach ($item->ItemPerformance as $perf) {

          // Get Pub-Year or range and add to its array if not already there.
          //
          $_yop = ccp_get_yop($perf);
          if ( preg_match('/-/', $_yop) ) {
            if ( !in_array($_yop, $YOP_ranges) ) { $YOP_ranges[] = $_yop; }
          } else if ( $_yop!="Unknown" && $_yop!="Articles in Press" ) {
            if ( !in_array($_yop, $YOPS) ) { $YOPS[] = $_yop; }
          }

          // Sum the Counts for use in the totals row
          //
          if ( !isset($yop_totals[$_yop]) ) { $yop_totals[$_yop] = 0; }
          foreach ( $period as $_dt ) {
            $_begin = $_dt->format('Y-m-01');
            $_end = $_dt->format('Y-m-t');
            if ( $perf->Period->Begin == $_begin && $perf->Period->End == $_end ) {
              foreach ($perf->Instance as $inst) {
                if ( $inst->MetricType == "ft_total" ) {
                  $yop_totals[$_yop] += (int) $inst->Count;
                }
              }
            }
          }
        }       // for all ItemPerformance
      }         // for all report-items
    
      // Sort YOPS, tack on ranges and "Unknown" to end if they're set
      //
      rsort($YOPS);
      if ( isset($yop_totals["Articles in Press"]) ) { array_unshift($YOPS,"Articles in Press"); }
      foreach ( $YOP_ranges as $_range ) { $YOPS[] = $_range; }
      if ( isset($yop_totals["Unknown"]) ) { $YOPS[] = "Unknown"; }

      // Print out report info
      //
      if ( $OUTFIL ) {
        $inst_ident = "";
        fwrite($csv_out, "Journal Report 5 (R4),Number of Successful Full-Text Article Requests by Year-of-Publication (YOP) and Journal\n");
        fwrite($csv_out, (string) $cust->Name . "\n");
        foreach ( $cust->InstitutionalIdentifier as $_iid ) {
          $inst_ident = (string) $_iid->Value;
        }
        if ( $inst_ident != "") { fwrite($csv_out, $inst_ident . "\n"); }
        fwrite($csv_out, "Period covered by Report:\n");
        fwrite($csv_out, $begin . " to " . $end . "\n");
        fwrite($csv_out, "Date run:\n" . date("Y-m-d") . "\n");

        //
        // Print column header and total rows using array of YOP's
        //
        $colhdr  = "Journal,Publisher,Platform,Journal DOI,Proprietary Identifier,Print ISSN,Online ISSN";
        $totals_row = "Total for all journals,,,,,,";
        foreach ( $YOPS as $_yop ) {
          $colhdr .= ($_yop!="Articles in Press") ? ",YOP ".$_yop : ",".$_yop;
          $totals_row .= "," . $yop_totals[$_yop];
        }
        $colhdr .= "\n";
        $totals_row .= "\n";
        fwrite($csv_out, $colhdr);
        fwrite($csv_out, $totals_row);
      }

      // Go through ReportItems (for real) and build the journal
      // report records into ouput CSV rows
      //
      $yop_counts = array();
      foreach ($cust->ReportItems as $item) {

        if ( $OUTFIL ) {
          // Set identifiers
          //
          $_prop_id = "";
          $_print_issn = "";
          $_online_issn = "";
          $_journal_doi = "";
          foreach ( $item->ItemIdentifier as $_id ) {
            if ( $_id->Type == "Proprietary" ) { $_prop_id = $_id->Value; }
            if ( $_id->Type == "Print_ISSN" ) { $_print_issn = $_id->Value; }
            if ( $_id->Type == "Online_ISSN" ) { $_online_issn = $_id->Value; }
            if ( $_id->Type == "DOI" ) { $_journal_doi = $_id->Value; }
          }

          // Other item-level columns
          //
          $_name = "";
          $_publisher = "";
          $_platform = "";
          if ( isset($item->ItemName) ) { $_name = $item->ItemName; }
          if ( isset($item->ItemPlatform) ) { $_platform = $item->ItemPlatform; }
          if ( isset($item->ItemPublisher) ) { $_publisher = $item->ItemPublisher; }

          // Setup the output CSV record up-to the counts;
          // enclose name, publisher and platform in quotes
          //
          $output_line = "\"" . $_name . "\",\"" . $_publisher . "\",\"" . $_platform . "\",";
          $output_line .= $_journal_doi . "," . $_prop_id . "," . $_print_issn . "," . $_online_issn;
        }

        // zero-out counts array
        //
        foreach ( $YOPS as $_yop ) { $yop_counts[$_yop] = 0; }

        // Loop through the ItemPerformance records (by-PubYr) and, for matching time-periods,
        // sum the counts for "ft_total" into an array.
        //
        foreach ( $period as $_dt ) {
          $_begin = $_dt->format('Y-m-01');
          $_end = $_dt->format('Y-m-t');
          foreach ($item->ItemPerformance as $perf) {
            $_yop = ccp_get_yop($perf);	// funcion in this file, below
            if ( $perf->Period->Begin == $_begin  &&  $perf->Period->End == $_end ) {
              foreach ($perf->Instance as $inst) {
                if ( $inst->MetricType == "ft_total" ) {
                  $yop_counts[$_yop] += (int) $inst->Count;
                }
              }
            }
          }
        }

        // Add the columns for each YOP onto the output record and write it out
        //
        if ( $OUTFIL ) {
          foreach ( $YOPS as $_yop ) { $output_line .= "," . $yop_counts[$_yop]; }
          $output_line .= "\n";
          fwrite ($csv_out, $output_line);
        }
      }		// End foreach ReportItem
    }		// End foreach Customer
    return "Success";
  }
}

//
// Function to return a Year-of-Publication string for an ItemPerformance record
// Pub-Year values that are <2000 or in the future (>current_year)
// will return "Unknown".
//
function ccp_get_yop($perf) {
  if ( isset($perf['PubYr']) ) {
    $_yop = (string) $perf['PubYr'];
    if ( $_yop == "9999" ) {
      return "Articles in Press";
    } else {
      if ( $_yop<2000 || $_yop>date("Y") ) { return "Unknown"; }
    }
  } else if ( isset($perf['PubYrTo']) ) {
    $_to = (string) $perf['PubYrTo'];
    $_from = "Pre";
    if ( isset($perf['PubYrFrom']) ) {
      $_val = (string) $perf['PubYrFrom'];
      if ( $_val>=2000 && $_val<=date("Y") ) { $_from = $_val; }
    }
    $_yop = $_from . "-" . ($_to +1);
  }
  return $_yop;
}
?>
