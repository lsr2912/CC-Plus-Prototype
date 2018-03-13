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
//////// Administration Homepage JS/Jquery actions
//
$(document).ready(function() {

  // ---------------------------------------------------------------////////
  //////// Institution type onchange action - regens inst list      ////////
  //
  $("#Itype").change(function(){ // change function of listbox
    $("#Inst").empty(); // Clear the institution box
    $.post("insttype_js.php", {"Itype":$('#Itype').val()}, function(return_data,status){
      $("#Inst").append("<option value=''>Choose an institution</option>");
      $.each(return_data.insts, function(key,value){
        $("#Inst").append("<option value=" + value.inst_id +">"+value.name+"</option>");
      });
    },"json");
  });

  // ---------------------------------------------------------------////////
  //////// Provider type onchange action - regens provider list     ////////
  //
  $("#Ptype").change(function(){ // change function of listbox
    $("#Prov").empty(); // Clear the institution box
    $.post("provtype_js.php", {"Ptype":$('#Ptype').val()}, function(return_data,status){
      $("#Prov").append("<option value=''>Choose a provider</option>");
      $.each(return_data.provs, function(key,value){
        $("#Prov").append("<option value=" + value.prov_id +">"+value.name+"</option>");
      });
    },"json");
  });

  // ---------------------------------------------------------------////////
  //////// Details: Provider onchange action regens inst-names if   ////////
  ////////          run as admin, or timestamps if run as manager   ////////
  //
  $("#R_Prov").change(function(){ // change function of listbox
    $("#R_yearmon").empty(); // Clear the timestamp options
    $("#R_report").empty(); // Clear the report options
    $.post("reptprov_js.php", {"prov_id":$('#R_Prov').val()}, function(return_data,status){
      var adm = return_data.admin;
      if ( adm ) {	// Admin gets to choose inst, rebuild the options
        $("#R_Inst").empty();
        var content = "<option value=''>Choose an institution</option>\n";
        $.each(return_data.records, function(key,value){
          if (value.error === 'undefined' || !value.error) {
            content += "<option value=" + value.inst_id +">"+value.name+"</option>\n";
          } else {
            content = "<option value=''>"+value.message+"</option>\n";
          }
        });
        $("#R_Inst").append(content);
      } else {		// for manager, rebuild timestamps instead of insts
        var content = "<option value=''>Choose a Month-Year</option>\n";
        $.each(return_data.records, function(key,value){
          if (value.error === 'undefined' || !value.error) {
            content += "<option value=" + value.yearmon +">"+value.yearmon+"</option>\n";
          } else {
            content = "<option value=''>"+value.message+"</option>\n";
          }
        });
        $("#R_yearmon").append(content);
      }
    },"json");
  });

  // ----------------------------------------------------------------////////
  //////// Details: Institution onchange action regens timestamps    ////////
  //
  $("#R_Inst").change(function(){ // change function of listbox
    $("#R_yearmon").empty(); // Clear the timestamp dropdown
    $("#R_report").empty(); // Clear the report dropdown
    $.post("reptinst_js.php", {"inst_id":$('#R_Inst').val(),"prov_id":$('#R_Prov').val()}, function(return_data,status){
      $("#R_yearmon").append("<option value=''>Choose a Month-Year</option>");
      $.each(return_data.stamps, function(key,value){
        $("#R_yearmon").append("<option value=" + value.yearmon +">"+value.yearmon+"</option>");
      });
    },"json");
  });

  // ----------------------------------------------------------------////////
  //////// Details: Timestamp onchange action regens report-names    ////////
  //
  $("#R_yearmon").change(function(){ // change function of listbox
    $("#R_report").empty(); // Clear the timestamp options
    $.post("reptdate_js.php", {"stamp":$('#R_yearmon').val(),"inst_id":$('#R_Inst').val(),"prov_id":$('#R_Prov').val()},
                              function(return_data,status){
      $("#R_report").append("<option value=''>Choose a Report</option>");
      $.each(return_data.reports, function(key,value){
        $("#R_report").append("<option value=" + value.ID +">"+value.Report_Name+"</option>");
      });
    },"json");
  });

})
//-->
