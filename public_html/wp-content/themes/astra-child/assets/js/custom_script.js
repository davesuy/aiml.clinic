var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
    return false;
};

jQuery( document ).ready(function($) {
  
    $(".expertise-user-edit select").chosen();

    $("#repeater .btn").click(function(event)
    {
      event.preventDefault(); // cancel default behavior
  
      //... rest of add logic
    });

    /* Create Repeater */
    $("#repeater").createRepeater({
        showFirstItemToDefault: true,
    });

    $(".wrk_in").datepicker();

    //var active = $( "#tabs" ).tabs( "option", "active" );
   // $( "#tabs" ).tabs({collapsible: true, active: true});

    $( "#aiml_tabs"  ).tabs({
        collapsible: true,
        active: true,
        create: function( event, ui ) {

            setTimeout(delayLoad, 2000);
        }
      });
      
      //.addClass( "ui-tabs-vertical ui-helper-clearfix" );

    function delayLoad(){
        $( "#aiml_tabs" ).tabs( "option", "active", 0 );
    }

    $( "#aiml_tabs-other"  ).tabs();

   // $( "#tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
  



});