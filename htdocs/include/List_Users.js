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
//////// List Users Page JS/Jquery actions
//
$(document).ready(function() {

  // Init table sorter
  //
  $('table').tablesorter();

  // On-change action for status dropdown
  //
  $("[id=filter_stat]").change(function() {
    var $table = $('#data_table');
    var enum_roles = $('#enum_roles');
    var role_vals = enum_roles.val().split(",");
    $.tablesorter.clearTableBody( $table );
    var form_data = $(this).closest('form').serialize();
    form_data['ajax'] = 1;
    $.ajax({
      url: "list_users_js.php",
      type: 'POST',
      data: form_data,
      dataType: 'json',
      success: function(return_data) {
        var adm = return_data.admin;
        $.each(return_data.records, function(key,value){
          if (typeof value.error === 'undefined' || !value.error) {

            //
            // Build new table rows from function output
            //
            var row="<tr>";
            //
            // First/Last name
            //
            row += "<td align='left'>" + value.first_name + "</td>";
            row += "<td align='left'>" + value.last_name + "</td>";
            //
            // Add the institution column for admins
            //
            if (adm) {
              row += "<td align='left'>";
              if ( value.inst_id == 0 ) {
                row += "Staff</td>";
              } else {
                row += "<a href='ManageInst.php?Inst=" +  value.inst_id + "'>";
                row += value.inst_name + "</a></td>";
              }
            }
            row += "<td align='left'>";
            row += "<a href='ManageUser.php?User=" +  value.user_id + "'>";
            row += value.email + "</a></td>";
            //
            // Phone, role, and last_login
            //
            var _login = value.last_login.substring(0,10);
            row += "<td align='center'>" + value.phone + "</td>";
            row += "<td align='center'>";
            for (var i=0, sm=role_vals.length; sm>i; i++) {
               var _role = role_vals[i].split(":");
               if ( _role[0] == value.role ) {
                 row += _role[1];
               }
            }
            row += "</td>";
            var _ts = value.last_login.substring(0,10);
            row += "<td align='center'>" + _ts + "</td>";
            //
            // complete and add the row
            //
            row += "</tr>";
          } else {
            var row="<tr><td colspan=5 align='center'><p><strong>";
            row += value.message +"</strong></p></td></tr>";
          }
          $table.append(row);
          $table.trigger('update');
        })
      }
    });
  })
})

