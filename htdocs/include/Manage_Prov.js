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
//////// Manage Provider Page JS/Jquery actions
//
$(document).ready(function() {

  // Set initial state of the AuthCreds div
  //
  if ($("#Sushi_Auth").val()=="" || $("#Sushi_Auth").val()=="None") {
     document.getElementById('AuthCreds').style.display = 'none'; 
  } else {
     document.getElementById('AuthCreds').style.display = 'block'; 
  }

//////// Hide/reveal user/pass form fields when auth-type changes ////////
  $("#Sushi_Auth").change(function() {
    if ( $("#Sushi_Auth").val()=="" || $("#Sushi_Auth").val()=="None") {
      $('#AuthCreds').hide();
    } else {
      $('#AuthCreds').show();
    }
  });

//////// Runs on provider dropdown select box change ////////
//
  $("#Prov").change(function(){ // change function of listbox
    $.post("provprov_js.php", {"prov_id":$('#Prov').val()}, function(return_data,status){
      $("#PID").val(return_data.prov.prov_id);
      $("#Pid").html(return_data.prov.prov_id);
      $("#Pname").val(return_data.prov.name);
      $("#Pstat").val(return_data.prov.active);
      $("#Sushi_URL").val(return_data.prov.server_url);
      $("#Sushi_Day").val(return_data.prov.day_of_month);
      $("#Sushi_Auth").val(return_data.prov.security);
      $("#Sushi_Auth").change();
      $("#Sushi_User").val(return_data.prov.auth_username);
      $("#Sushi_Pass").val(return_data.prov.auth_password);
      if ( return_data.v4_reports!="" ) {
        $('#C4_Reports').html(return_data.v4_reports);
      }
      if ( return_data.v5_reports!="" ) {
        $('#C5_Reports').html(return_data.v5_reports);
      }
    },"json");
  })

})

// Cancel backs up one page
//
$(function() {
  $('input[value=Cancel]').click(function() {history.go(-1);});
})

//-->
