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
//////// Manual Ingest Page JS/Jquery actions
//
$(document).ready(function() {

  // Set initial state of the AuthCreds div
  //
  if ($("#Sushi_Auth").val()=="" || $("#Sushi_Auth").val()=="None") {
     document.getElementById('AuthCreds').style.display = 'none'; 
  } else {
     document.getElementById('AuthCreds').style.display = 'block'; 
  }

  // Hide/reveal user/pass form fields when auth-type changes
  //
  $("#Sushi_Auth").change(function() {
    if ( $("#Sushi_Auth").val()=="" || $("#Sushi_Auth").val()=="None") {
      $('#AuthCreds').hide();
    } else {
      $('#AuthCreds').show();
    }
  });

  // Runs on provider dropdown select box change
  //
  $("#Prov").change(function(){ // change function of listbox
    if ( $("#Inst").val()!="" ) {
      $.post("maning_js.php", {"prov_id":$('#Prov').val(),"inst_id":$('#Inst').val()}, function(return_data,status){
        $("#Server").val(return_data.settings.server_url);
        $("#Sushi_Auth").val(return_data.settings.security);
        $("#Sushi_Auth").change();
        $("#auth_user").val(return_data.settings.auth_username);
        $("#auth_pass").val(return_data.settings.auth_password);
        $("#ReqID").val(return_data.settings.RequestorID);
        $("#ReqName").val(return_data.settings.RequestorName);
        $("#ReqEmail").val(return_data.settings.RequestorEmail);
        $("#CustID").val(return_data.settings.CustRefID);
        $("#CustName").val(return_data.settings.CustRefName);
      },"json");
    }
  })

  // Runs on institution dropdown select box change
  //
  $("#Inst").change(function(){ // change function of listbox
    if ( $("#Prov").val()!="" ) {
      $.post("maning_js.php", {"prov_id":$('#Prov').val(),"inst_id":$('#Inst').val()}, function(return_data,status){
        $("#Server").val(return_data.settings.server_url);
        $("#Sushi_Auth").val(return_data.settings.security);
        $("#Sushi_Auth").change();
        $("#auth_user").val(return_data.settings.auth_username);
        $("#auth_pass").val(return_data.settings.auth_password);
        $("#ReqID").val(return_data.settings.RequestorID);
        $("#ReqName").val(return_data.settings.RequestorName);
        $("#ReqEmail").val(return_data.settings.RequestorEmail);
        $("#CustID").val(return_data.settings.CustRefID);
        $("#CustName").val(return_data.settings.CustRefName);
      },"json");
    }
  })

})

// Cancel backs up one page
//
$(function() {
  $('input[value=Cancel]').click(function() {history.go(-1);});
})

// Function to test various form fields
// (uses subfunctions from include/validators.js)
function validateFormSubmit(theForm) {
  var reason = "";
  reason += validateEmpty(theForm.Prov);
  reason += validateEmpty(theForm.Inst);
  reason += validateEmpty(theForm.Rept);
  reason += validateEmpty(theForm.ReportMo);
  reason += validateEmpty(theForm.ReportYr);
  reason += validateEmpty(theForm.Server);
  reason += validateEmpty(theForm.IngestOP);
  if ( $("#Sushi_Auth").val()!="None" ) {
   reason += validateEmpty(theForm.auth_user);
   reason += validateEmpty(theForm.auth_pass);
  }
  if ( $("#ReqEmail").val()!="" ) {
    reason += validateEmail(theForm.ReqEmail);
  }
  if (reason != "") {
    alert("Some fields need correction:\n" + reason);
    return false;
  }
  return true;
}
//-->
