<script type="text/javascript">
    gp.services.ssh.clickHandler = function() {
        gp.services.ssh.load();
    };

    //XML Module: Ssh
    gp.services.ssh.load = function() {
        gp.data.ssh = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/ssh.xml',
            module: 'Ssh',
            page: 'getconfig',
            error_element: $('#services_ssh_form_error'),
            successFn: function(json) {
                gp.data.ssh = json.ssh;

                gp.services.ssh.loadForm();
            }
        });
    };

    gp.services.ssh.loadForm = function() {
        var data = gp.data.ssh;
        gp.resetForm('services_ssh_form');
        $('#services_ssh_enabled').attr('checked', data.enable.toLowerCase() == 'true');
    };

    $(function(){
        //Handler for submitting the form
        $('#services_ssh_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/ssh.xml',
                form_id: 'services_ssh_form',
                error_element: $('#services_ssh_form_error'),
                successFn: function(json) {
                    gp.data.ssh = json.ssh;
                    gp.services.ssh.loadForm();
                }
            });
            return false;
        });
    });
</script>
