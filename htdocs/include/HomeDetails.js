//<!--
// Report Details onchange action scripts                         ////////
// ---------------------------------------------------------------////////
$(document).ready(function() {
//
//////// Provider field onchange action regens inst-names         ////////
//
  $("#R_Prov").change(function(){ // change function of listbox
    $("#R_Inst").empty(); // Clear the institution options
    $("#R_yearmon").empty(); // Clear the timestamp options
    $("#R_report").empty(); // Clear the report options
    $.post("reptprov_js.php", {"prov_id":$('#R_Prov').val()}, function(return_data,status){
      $("#R_Inst").append("<option value=''>Choose an institution</option>");
      $.each(return_data.insts, function(key,value){
        $("#R_Inst").append("<option value=" + value.inst_id +">"+value.name+"</option>");
      });
    },"json");
  });
//
//////// Institution field onchange action regens timestamps    ////////
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
//
//////// Timestamp field onchange action regens report-names       ////////
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
//
})
//-->
