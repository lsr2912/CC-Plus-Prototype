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
//////// Import/Export User Settings JS/Jquery actions
//
$(document).ready(function() {

  // On-change action for filter dropdowns
  //
  $("[id=U_stat],[id=U_role],[id=U_inst]").change(function(){ // change function of select boxes
    $.post("imex_users_js.php", {"stat":$('#U_stat').val(),"inst":$('#U_inst').val(),"role":$('#U_role').val()},
           function(return_data,status){
      var count = return_data.count;
      if ( count > 0 ) {
        $('#BeginEXP').show();
      } else {
        count="NO";
        $('#BeginEXP').hide();
      }
      var message="<tr><td colspan='2' align='left'>"+count;
      message+=" user record(s) will be exported based on current selections.</td></tr>";
      $('#ExpCount').html(message);
    },"json");
  })

})

