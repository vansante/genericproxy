<script type="text/javascript">
    gp.system.genset.clickHandler = function() {
        gp.system.genset.load();
    };

    //XML Module: System
    gp.system.genset.load = function() {
        gp.data.system = {};
        
        //Handle XML loading
        gp.doAction({
            url: 'testxml/system.xml',
            module: 'System',
            page: 'getconfig',
            error_element: $('#system_genset_form_error'),
            content_id: 'cp_system_genset_genset',
            successFn: function(json) {
                gp.data.system = json.system;

                gp.system.genset.loadForm();
            }
        });
    };

    gp.system.genset.loadForm = function() {
        var data = gp.data.system;
        gp.resetForm('system_genset_form');

        $('#system_genset_hostname').val(data.hostname);
        $('#system_genset_domain').val(data.domain);
        if (data.dnsservers.dnsserver) {
            if ($.isArray(data.dnsservers.dnsserver)) {
                $.each(data.dnsservers.dnsserver, function(i, dns) {
                    if (i <= 2) {
                        $('#system_genset_dns'+(i+1)).val(dns.ip);
                    }
                });
            } else {
                $('#system_genset_dns1').val(data.dnsservers.dnsserver.ip);
            }
        }
        $('#system_genset_dnsoverride').attr('checked', data.dnsoverride.toLowerCase() == 'allow');
    };

    $(function(){
        //Handler for submitting the form
        $('#system_genset_form').submit(function() {
            gp.doFormAction({
                form_id: 'system_genset_form',
                error_element: $('#system_genset_form_error'),
                successFn: function(json) {
                    gp.data.system = json.system;
                    gp.system.genset.loadForm();
                }
            });
            return false;
        });

        $('#system_genset_username').val('admin');
        $('#system_genset_username').attr('disabled', 'disabled');
        $('#system_genset_password1').attr('disabled', 'disabled');
        $('#system_genset_password2').attr('disabled', 'disabled');
        $('#system_genset_change_user').click(function(){
            if (this.checked) {
                $('#system_genset_username').removeAttr('disabled');
                $('#system_genset_password1').removeAttr('disabled');
                $('#system_genset_password2').removeAttr('disabled');
            } else {
                $('#system_genset_username').attr('disabled', 'disabled');
                $('#system_genset_password1').attr('disabled', 'disabled');
                $('#system_genset_password2').attr('disabled', 'disabled');
            }
        });
    });
</script>
