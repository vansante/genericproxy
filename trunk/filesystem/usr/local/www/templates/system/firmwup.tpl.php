<script type="text/javascript">
    $(function() {
        $('#system_firmwup_form').submit(function(){
            gp.confirm("Are you sure?", "Are you sure you want to upgrade the devices firmware?", function() {
                gp.doFormAction({
                    url: 'testxml/reply.xml',
                    form_id: 'system_firmwup_form',
                    error_element: $('#system_firmwup_form_error'),
                    successFn: function(json) {
                        gp.rebootNotice();
                    }
                });
            });
            return false;
        });
    });
</script>