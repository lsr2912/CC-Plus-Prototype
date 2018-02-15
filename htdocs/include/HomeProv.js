//<!--
// Report Providers onchange action scripts                       ////////
// ---------------------------------------------------------------////////
//////// Provider type onchange action - regens provider list     ////////
//
$(document).ready(function() {
  $("#Ptype").change(function(){ // change function of listbox
    $("#Prov").empty(); // Clear the institution box
    $.post("provtype_js.php", {"Ptype":$('#Ptype').val()}, function(return_data,status){
      $("#Prov").append("<option value=''>Choose a provider</option>");
      $.each(return_data.provs, function(key,value){
        $("#Prov").append("<option value=" + value.prov_id +">"+value.name+"</option>");
      });
    },"json");
  });
})
//-->
