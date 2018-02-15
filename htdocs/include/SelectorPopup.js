//<!--
$(document).ready(function() {
  
  /*** custom css only button popup ***/
  $(".custom-popup").tablesorter({
    widgets: ['columnSelector'],
    widgetOptions : {
      // target the column selector markup
      columnSelector_container : $('#columnSelector'),
      // column status, true = display, false = hide
      // disable = do not display on list
      columnSelector_columns : {
        0: 'disable' /* set to disabled; not allowed to unselect it */
      },

      // container layout
      columnSelector_layout : '<label><input type="checkbox">{name}</label>',
      // layout customizer callback called for each column
      // function($cell, name, column){ return name || $cell.html(); }
      columnSelector_layoutCustomizer : null,
      // data attribute containing column name to use in the selector container
      columnSelector_name  : 'data-selector-name',

      /* Responsive Media Query settings */
      // enable/disable mediaquery breakpoints
      columnSelector_mediaquery: true,
      // toggle checkbox name
      columnSelector_mediaqueryName: 'Auto: ',
      // breakpoints checkbox initial setting
      columnSelector_mediaqueryState: true,
      // hide columnSelector false columns while in auto mode
      columnSelector_mediaqueryHidden: true,

      // set the maximum and/or minimum number of visible columns; use null to disable
      columnSelector_maxVisible: null,
      columnSelector_minVisible: null,
      // responsive table hides columns with priority 1-6 at these breakpoints
      // see http://view.jquerymobile.com/1.3.2/dist/demos/widgets/table-column-toggle/#Applyingapresetbreakpoint
      // *** set to false to disable ***
      columnSelector_breakpoints : [ '20em', '30em', '40em', '50em', '60em', '70em' ],
      // data attribute containing column priority
      // duplicates how jQuery mobile uses priorities:
      // http://view.jquerymobile.com/1.3.2/dist/demos/widgets/table-column-toggle/
      columnSelector_priority : 'data-priority',

      // class name added to checked checkboxes - this fixes an issue with Chrome not updating FontAwesome
      // applied icons; use this class name (input.checked) instead of input:checked
      columnSelector_cssChecked : 'checked'
    }
  });
})
//-->
