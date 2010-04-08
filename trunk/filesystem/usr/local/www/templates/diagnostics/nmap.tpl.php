<script type="text/javascript">
    gp.diagnostics.nmap.clickHandler = function() {};

    $(function(){
        //Handler for submitting the form
        $('#diagnostics_nmap_form').submit(function() {
            $('#diagnostics_nmap_results_div').slideUp(350);
            gp.doFormAction({
                url: 'testxml/nmap.xml',
                form_id: 'diagnostics_nmap_form',
                error_element: $('#diagnostics_nmap_form_error'),
                successFn: function(json) {
                    $('#diagnostics_nmap_results').html('<pre>'+json.nmap.result+'</pre>');
                    $('#diagnostics_nmap_results_div').slideDown(500);
                }
            });
            return false;
        });
    });
</script>
