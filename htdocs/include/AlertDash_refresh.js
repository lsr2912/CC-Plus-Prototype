//<!--
//////// Alerts Dashboard: table refresh           ////////
//////// This fires on changes to the form inputs  ////////
//////// and queries for the data rows in the page ////////
$(document).ready(function() {
   $("[id=filter_stat],[id=filter_prov]").change(function () {
      var $table = $('#data_table');
      var enum_stat = $('#enum_stat');
      var status_vals = enum_stat.val().split(":");
      $.tablesorter.clearTableBody( $table );
      var form_data = $(this).closest('form').serialize();
      form_data['ajax'] = 1;
      var _my_url = window.location.pathname.substring(window.location.pathname.lastIndexOf("/")+1);
      $.ajax({
         url: "alerts_dash_ck.php",
         type: 'POST',
         data: form_data,
         dataType: 'json',
         success: function(return_data) {
           var mgr = return_data.manager;
           $.each(return_data.records, function(key,value){
           //
           // Build new table rows from function output 
           //
             var row="<tr>";
             //
             // Manager gets a dropdown, otherwise just print the text
             //
             row += "<td align='left'>";
             if ( mgr ) {
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
             //
             // Condition : one of failed_xref or alert_xref should be non-zero.
             // If not, display an error message.
             //
             row += "<td align='left'>";
             if ( value.alert_xref != 0 ) {
               row += value.condition + "</td>";
             } else if ( value.failed_xref != 0 ) {
               row += "Failed Stats Ingest</td>";
             } else {
               row += "Error - Unknown</td>";
             }
             //
             // Vendor as link for manager, otherwise just the name
             //
             row += "<td align='left'>";
             if ( value.vend_id == 0 ) {
               row += "--</td>";
             } else {
               if ( mgr ) {
                 row += "<a href='/ManageProvider.php?prov=" + value.prov_id + "'>";
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
