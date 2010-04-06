<script type="text/javascript">
    $(function() {
        $('#system_backrest_restore_form').submit(function(){
            gp.confirm("Are you sure?", "Are you sure you to restore the configuration?", function() {
                gp.doFormAction({
                    url: 'testxml/reply.xml',
                    form_id: 'system_backrest_restore_form',
                    error_element: $('#system_backrest_restore_form_error'),
                    successFn: function(json) {

                    }
                });
            });
            return false;
        });
    });
</script>