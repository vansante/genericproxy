<script type="text/javascript">
    gp.services.dyndns.clickHandler = function() {
        gp.services.dyndns.load();
    };

    //XML Module: DnsForward
    gp.services.dyndns.load = function() {
        gp.data.dyndns = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/dyndns.xml',
            module: 'Dyndns',
            page: 'getconfig',
            error_element: $('#services_dyndns_form_error'),
            content_id: 'cp_services_dyndns_dyndns',
            successFn: function(json) {
                gp.data.dyndns = json.dyndns;
                gp.services.dyndns.loadForm();
            }
        });
    };

    gp.services.dyndns.loadForm = function() {
        var data = gp.data.dyndns;
        gp.resetForm('services_dyndns_form');

        if (data.enable.toLowerCase() == 'true') {
            $('#services_dyndns_subform_client input, #services_dyndns_subform_client select').removeAttr('disabled');

            $('#services_dyndns_enabled').attr('checked', 'checked');
            $('#services_dyndns_type').val(data.client.type.toLowerCase());
            $('#services_dyndns_hostname').val(data.client.host);
            $('#services_dyndns_server').val(data.client.server);
            $('#services_dyndns_port').val(data.client.port);
            $('#services_dyndns_mx').val(data.client.mx);
            $('#services_dyndns_wildcards').attr('checked', data.client.wildcards.toLowerCase() == 'enable');
            $('#services_dyndns_username').val(data.client.username);
            $('#services_dyndns_password').val(data.client.password);
        } else {
            $('#services_dyndns_subform_client input, #services_dyndns_subform_client select').attr('disabled', 'disabled');
        }
    };

    $(function() {
        //Handler for submitting the form
        $('#services_dyndns_form').submit(function() {
            gp.doFormAction({
                form_id: 'services_dyndns_form',
                error_element: $('#services_dyndns_form_error'),
                successFn: function(json) {
                    gp.data.dyndns = json.dyndns;
                    gp.services.dyndns.loadForm();
                }
            });
            return false;
        });

        if (!$('#services_dyndns_enabled').attr('checked')) {
            $('#services_dyndns_subform_client input, #services_dyndns_subform_client select').attr('disabled', 'disabled');
        }

        $('#services_dyndns_enabled').click(function() {
            if ($(this).attr('checked')) {
                $('#services_dyndns_subform_client input, #services_dyndns_subform_client select').removeAttr('disabled');
            } else {
                $('#services_dyndns_subform_client input, #services_dyndns_subform_client select').attr('disabled', 'disabled');
            }
        });
    });
</script>