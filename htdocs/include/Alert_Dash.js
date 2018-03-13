//<!--
// -------------------------------------------------------------------------------------- 
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
// --------------------------------------------------------------------------------------
//////// Alerts Dashboard: table refresh           ////////
//////// This fires on changes to the form inputs  ////////
//////// and queries for the data rows in the page ////////
$(document).ready(function() {

   // Init table sorter
   ///
   $('table').tablesorter();

   // Handy set-all button actions
   //
   $('#SilenceALL').click(function() {
     $("[id^=stat_]").each(function() { this.value = "Silent" });
   });
   $('#ActiveALL').click(function() {
     $("[id^=stat_]").each(function() { this.value = "Active" });
   });
   $('#DeleteALL').click(function() {
     $("[id^=stat_]").each(function() { this.value = "Delete" });
   });
 
   $("[id=filter_stat],[id=filter_prov]").change(function () {
      var $table = $('#data_table');
      var enum_stat = $('#enum_stat');
      var status_vals = enum_stat.val().split(":");
      $.tablesorter.clearTableBody( $table );
      var form_data = $(this).closest('form').serialize();
      form_data['ajax'] = 1;
      $.ajax({
         url: "alert_dash_js.php",
         type: 'POST',
         data: form_data,
         dataType: 'json',
         success: function(return_data) {
           var adm = return_data.admin;
           $.each(return_data.records, function(key,value){
           //
           // Build new table rows from function output 
           //
             var row="<tr>";
             //
             // Admins get a dropdown, otherwise just print the text
             //
             row += "<td align='left'>";
             if ( adm ) {
               row += "<select name='stat_" + value.ID + "' id='stat_" + value.ID + "'>";
               for (var i=0, sm=status_vals.length; sm>i; i++) {
                 row += "<option value='" + status_vals[i] + "'";
                 if ( status_vals[i] == value.status ) { row += " selected "; }
                 row += ">" + status_vals[i] + "</option>";
               }
               row += "</select>";
             } else {
               row += value.status+"</td>";
             }
             // Yearmon
             //
             row += "<td align='center'>";
             if ( value.yearmon != "" ) {
               row += value.yearmon + "</td>";
             } else {
               row += " -- </td>";
             }
             // Condition
             //
             row += "<td align='center'>";
             if ( value.legend != "" ) {
               row += value.legend + "</td>";
             } else {
               row += " -- </td>";
             }
             //
             // Provider as link for admins, otherwise just the name
             //
             row += "<td align='left'>";
             if ( value.prov_id == 0 ) {
               row += "--</td>";
             } else {
               if ( adm ) {
                 row += "<a href='ManageProvider.php?Prov=" + value.prov_id + "'>";
                 row += value.prov_name + "</a></td>";
               } else {
                 row += value.prov_name + "</td>";
               }
             }
             //
             // Timestamp date and modified-by
             // 
             row += "<td align='center'>"+value.ts_date+"</td>";
             row += "<td align='center'>";
             if ( value.modified_by == null ) {
               row += "--</td>";
             } else {
               if ( value.modified_by == 0 ) {
                 row += "CC-Plus System";
               } else {
                 row += value.user_name + "</td>";
               }
             }

             $table
               .append(row)
               .trigger('update');
           })
         }
      });
   })
})
//-->
