<script type="text/javascript">
$(function(){
    $('#system_reboot_submit').click(function(){
        gp.confirm("Are you sure?", "Are you sure you want to reboot the device?", function() {
            gp.doFormAction({
                url: 'testxml/reply.xml',
                form_id: 'system_reboot_form',
                error_element: $('#system_reboot_form_error'),
                successFn: function(json) {
                    gp.rebootNotice();
                }
            });
        });
        return false;
    });
});
</script>