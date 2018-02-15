//<!--
// Member Institutions onchange action scripts                    ////////
// ---------------------------------------------------------------////////
//////// Institution type onchange action - regens inst list      ////////
//
$(document).ready(function() {
  $("#Itype").change(function(){ // change function of listbox
    $("#Inst").empty(); // Clear the institution box
    $.post("insttype_js.php", {"Itype":$('#Itype').val()}, function(return_data,status){
      $("#Inst").append("<option value=''>Choose an institution</option>");
      $.each(return_data.insts, function(key,value){
        $("#Inst").append("<option value=" + value.inst_id +">"+value.name+"</option>");
      });
    },"json");
  });
})
//-->
