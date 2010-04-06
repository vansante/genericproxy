<script type="text/javascript">
$(function(){
    $('#system_reset_submit').click(function(){
        gp.confirm("Are you sure?", "Are you sure you want to reset the device?", function() {
            gp.doFormAction({
                form_id: 'system_reset_form',
                error_element: $('#system_reset_form_error'),
                successFn: function(json) {
                    gp.rebootNotice();
                }
            });
        });
        return false;
    });
});
</script>