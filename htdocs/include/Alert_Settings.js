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
//////// Alert Settings page JS/Jquery actions
//
$(document).ready(function() {

  $('#AddAlert').hide();       // hide "Add" button until a metric is set

  // -----------------------------------------------------////////
  //////// Report onchange action - regens inst list      ////////
  //
  $("#A_report").change(function(){ // change function of listbox
    $("#A_metric").empty(); // Clear the institution box
    if ( $('#A_report').val() != "--" ) {
      $.post("reptmetrics_js.php", {"rept_id":$('#A_report').val()}, function(return_data,status){
        $("#A_metric").append("<option value='--'>Choose a measurement</option>");
        $.each(return_data.metrics, function(key,value){
          $("#A_metric").append("<option value=" + value.ID+">"+value.legend+"</option>");
        });
      },"json");
    }
  });

  // -------------------------------------------------------////////
  //////// Metric onchange action - enable button, define   ////////
  //////// hidden string for measurement to be alerted      ////////
  //
  $("#A_metric").change(function(){ // change function of listbox
    if ( $('#A_metric').val() == "--" ) {
      $('#AddAlert').hide();       // hide "Add" button if metric is set to "Choose..."
      $('#measure').val('');       // clear measure if metric is set to "Choose..."
    } else {
      var _measure = "";
      var enum_reports = $('#enum_reports');
      var report_vals = enum_reports.val().split(",");
      for (var i=0, sm=report_vals.length; sm>i; i++) {
        var _rpt = report_vals[i].split(":");
        if ( _rpt[0] == $('#A_report').val() ) {
          _measure += _rpt[1];
        }
      }
      _measure += " :: ";
      var enum_metrics = $('#enum_metrics');
      var metric_vals = enum_metrics.val().split(",");
      for (var i=0, sm=metric_vals.length; sm>i; i++) {
        var _met = metric_vals[i].split(":");
        if ( _met[0] == $('#A_metric').val() ) {
          _measure += _met[1];
        }
      }
      $('#measure').val(_measure);
      $('#AddAlert').show();       // reveal "Add" button until a metric is set
    }
  });

  //////// Runs on "add an alert" button click ////////
  //
  $("#AddAlert").click(function(){ // click add row
    if ( $('#A_variance').val()=='0' || $('#A_timespan').val()=='0' ) {
      alert ('Variance and #-of-Months must be non-zero values');
    } else {
      var row = '<tr><td align="center"><input type="hidden" name="newcb[]" value="On"><strong>New</strong></td><td>&nbsp;</td>';
      row += '<td align="left">'+$('#measure').val()+'</td>';
      row += '<td><input type="hidden" name="newmet[]" value="'+$('#A_metric').val()+'"></td>';
      row += '<td align="left"><input type="text" class="Num3" name="newvar[]" value="'+$('#A_variance').val()+'" />';
      row += '&nbsp; %</td><td>&nbsp;</td>';
      row += '<td align="left"><input type="text" class="Num3" name="newts[]" value="'+$('#A_timespan').val()+'" />';
      row += '&nbsp; month(s)</td>';
      $("#AllAlerts").append(row);
      $('#A_report').val('--');
      $('#A_metric').val('--');
      $('#A_variance').val(0);
      $('#A_timespan').val(0);
      $('#measure').val('');
      $('#AddAlert').hide();
    }
  });


})
//-->
