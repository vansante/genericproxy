<script type="text/javascript">
    gp.diagnostics.ping.clickHandler = function() {};

    $(function(){
        //Handler for submitting the form
        $('#diagnostics_ping_form').submit(function() {
            $('#diagnostics_ping_results_div').slideUp(350);
            gp.doFormAction({
                url: 'testxml/ping.xml',
                form_id: 'diagnostics_ping_form',
                error_element: $('#diagnostics_ping_form_error'),
                successFn: function(json) {
                    $('#diagnostics_ping_results').html('<pre>'+json.ping.result+'</pre>');
                    $('#diagnostics_ping_results_div').slideDown(500);
                }
            });
            return false;
        });
    });
</script>
