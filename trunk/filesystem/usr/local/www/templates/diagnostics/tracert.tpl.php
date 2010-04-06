<script type="text/javascript">
    gp.diagnostics.tracert.clickHandler = function() {};

    $(function(){
        //Handler for submitting the form
        $('#diagnostics_tracert_form').submit(function() {
            $('#diagnostics_tracert_results_div').slideUp(350);
            gp.doFormAction({
                url: 'testxml/traceroute.xml',
                form_id: 'diagnostics_tracert_form',
                error_element: $('#diagnostics_tracert_form_error'),
                successFn: function(json) {
                    $('#diagnostics_tracert_results').html('<pre>'+json.traceroute.result+'</pre>');
                    $('#diagnostics_tracert_results_div').slideDown(500);
                }
            });
            return false;
        });
    });
</script>
