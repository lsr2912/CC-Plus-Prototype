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
//////// Manage Institutions Page JS/Jquery actions
//
$(document).ready(function() {

  $('#AdminInfo').hide();	// hide div when admin not set

  // Hide/reveal/update inst-admin information when inst-admin changes
  //
  $("#InstAdmin").change(function() {
    if ( $("#InstAdmin").val()!="0" ) { 
      $.post("instuser_js.php", {"user_id":$('#InstAdmin').val()}, function(return_data,status){
        if ( return_data.user.user_id > 0 ) {
          var content = '<strong><a href="ManageUser.php?User='+return_data.user.user_id+'">View User Data</a></strong>';
          $("#AdminInfo").html(content);
        }
      },"json");
      $('#AdminInfo').show();
    } else {
      $('#AdminInfo').hide();		// hide div when admin not set
    }
  });

  // Runs on provider dropdown select box change
  //
  $("#Prov").change(function(){ // change function of listbox
    if ( $('#Prov').val() == "" ) {
      $("[id^=Sushi_]").val("");
    } else {
      $.post("instprov_js.php", {"inst_id":$('#INST').val(), "prov_id":$('#Prov').val()}, function(return_data,status){
        $.each(return_data.sushi, function(key,value){
          $("#Sushi_ReqID").val(value.RequestorID);
          $("#Sushi_ReqName").val(value.RequestorName);
          $("#Sushi_ReqEmail").val(value.RequestorEmail);
          $("#Sushi_CustID").val(value.CustRefID);
          $("#Sushi_CustName").val(value.CustRefName);
        });
        $("#AliasNames").empty();
        $.each(return_data.names, function(key,value){
          var row = '<tr><td colspan="2">&nbsp;</td><td colspan="2" align="left">';
          row += '<input type="text" name="inst_alias[]" value="'+value.alias+'"></td>';
          row += '<td colspan="2">&nbsp;</td></tr>';
          $("#AliasNames").append(row);
        });
      },"json");
    }
  });

  // Runs on "add an alias" button click, to add a name
  //
  $("#AddRow").click(function(){ // click add row
     var row = '<tr><td colspan="2">&nbsp;</td><td colspan="2" align="left">';
     row += '<input type="text" name="inst_alias[]" value="'+$('#add_alias').val()+'"></td>';
     row += '<td colspan="2">&nbsp;</td></tr>';
     $("#AliasNames").append(row);
     $("#add_alias").val('');
  });
})

// Cancel backs up one page
//
$(function() {
  $('input[value=Cancel]').click(function() {history.go(-1);});
})
//-->
