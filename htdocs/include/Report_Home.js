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
//////// ReportHome Page JS/Jquery actions
//
$(document).ready(function() {

  // Set initial state of the Jr1Col selector
  //
  if ($("#Dest").val()=="HTML" && $("#Rept").val()=="JR1") {
    $('#JR1Col').removeClass('hidden');
    $('#JR1ColLab').removeClass('hidden');
  } else {
    $('#JR1Col').addClass('hidden');
    $('#JR1ColLab').addClass('hidden');
  }

  // Initialize tablesorter
  //
  $('table').tablesorter();

  // Hide/reveal JR1Col div when Report+Output form fields change
  //
  $("[id=Dest],[id=Rept]").change(function () {
    if ( $("#Dest").val()=="HTML" && $("#Rept").val()=="JR1") {
      $('#JR1Col').removeClass('hidden');
      $('#JR1ColLab').removeClass('hidden');
    } else {
      $('#JR1Col').addClass('hidden');
      $('#JR1ColLab').addClass('hidden');
    }
  });

  // Runs when any filter, date (from/to), or report select boxes change
  //
  $("[id=FromYM],[id=ToYM],[id=Inst],[id=Prov],[id=Plat],[id=Rept]").change(function() {

    // If provider changes, reset platform before pulling new filter values
    //
    var curFilt = this.id;
    if ( curFilt=="Prov" ) { $('#Plat').val(0); }
    $.post("reportfilt_js.php", {"from":$('#FromYM').val(), "to":$('#ToYM').val(), "rept":$('#Rept').val(),
                                 "prov":$('#Prov').val(), "plat":$('#Plat').val(), "inst":$('#Inst').val()
                                }, function(return_data,status){

      // Rebuild From-To options
      //
      if ( curFilt!="FromYM" && curFilt!="ToYM" ) {
        var selFrom = "";
        var   selTo = "";
        var curFrom = $('#FromYM').val();
        var   curTo = $('#ToYM').val();
        $('#FromYM').empty();
        $('#ToYM').empty();
        $.each(return_data.range, function(key,value){
          $('#FromYM').append("<option value="+value+">"+value+"</option>");
          $('#ToYM').append("<option value="+value+">"+value+"</option>");
          if ( curFrom==value ) { selFrom = value; }
          if (   curTo==value ) {   selTo = value; }
        });
        if ( selFrom=="" ) { selFrom = return_data.range[0]; }
        if (   selTo=="" ) {   selTo = return_data.range[return_data.range.length-1]; }
        $('#FromYM').val(selFrom);
        $('#ToYM').val(selTo);
      }

      // Rebuild Report options
      //
      if ( curFilt!="Rept" ) {
        var selRept = "";
        var curRept = $('#Rept').val();
        num_rows = return_data.repts.length;
        $('#Rept').empty();
        $.each(return_data.repts, function(key,value){
          $('#Rept').append("<option value="+value.Report_Name+">"+value.Report_Name+"</option>");
          if ( curRept==value.Report_Name ) { selRept = curRept; }
        });
        if ( selRept=="" ) { selRept = return_data.repts[0].Report_Name; }
        $('#Rept').val(selRept);
      }

      // Rebuild Provider options
      //
      var num_rows;
      if ( curFilt!="Prov" ) {
        var selProv = 0;
        var curProv = $('#Prov').val();
        num_rows = return_data.provs.length;
        $('#Prov').empty();
        $.each(return_data.provs, function(key,value){
          $('#Prov').append("<option value="+value.prov_id+">"+value.name+"</option>");
          if ( num_rows==1 ) {
            selProv = value.prov_id;
          } else {
            if ( curProv==value.prov_id ) { selProv = curProv; }
          }
        });
        $('#Prov').val(selProv);
      }

      // Rebuild Platform options
      //
      if ( curFilt!="Plat" ) {
        var selPlat = 0;
        var curPlat = $('#Plat').val();
        num_rows = return_data.plats.length;
        $('#Plat').empty();
        $.each(return_data.plats, function(key,value){
          $('#Plat').append("<option value="+value.plat_id+">"+value.name+"</option>");
          if ( num_rows==1 ) {
            selPlat = value.plat_id;
          } else {
            if ( curPlat==value.plat_id ) { selPlat = curPlat; }
          }
        });
        $('#Plat').val(selPlat);
      }

      // Rebuild Institution options
      //
      if ( curFilt!="Inst" ) {
        var selInst = 0;
        var curInst = $('#Inst').val();
        num_rows = return_data.insts.length;
        $('#Inst').empty();
        $.each(return_data.insts, function(key,value){
          $('#Inst').append("<option value="+value.inst_id+">"+value.name+"</option>");
          if ( num_rows==1 ) {
            selInst = value.inst_id;
          } else {
            if ( curInst==value.inst_id ) { selInst = curInst; }
          }
        });
        $('#Inst').val(selInst);
      }

    },"json");
  });

})
//-->
