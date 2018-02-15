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
//////// Import/Export Name Aliases JS/Jquery actions
//
$(document).ready(function() {

  // On-change action for filter dropdowns
  //
  $("[id=N_istat],[id=N_pstat],[id=N_inst],[id=N_prov]").change(function(){ // change function of select boxes
    $.post("imex_name_js.php", {"istat":$('#N_istat').val(),"pstat":$('#N_pstat').val(),
                                 "inst":$('#N_inst').val(),  "prov":$('#N_prov').val()},
           function(return_data,status){
      var count = return_data.count;
      if ( count > 0 ) {
        $('#BeginEXP').show();
      } else {
        count="NO";
        $('#BeginEXP').hide();
      }
      var message="<tr><td colspan='2' align='left'>"+count;
      message+=" alias record(s) will be exported based on current selections.</td></tr>";
      $('#ExpCount').html(message);
    },"json");
  })

})

