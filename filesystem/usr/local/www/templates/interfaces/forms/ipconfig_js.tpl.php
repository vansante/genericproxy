<script type="text/javascript">
    gp.interfaces.<?=$this->ipconfig_iface?>.clickHandler = function() {
        gp.interfaces.<?=$this->ipconfig_iface?>.load();
    };

    gp.interfaces.<?=$this->ipconfig_iface?>.load = function() {
        gp.data.interface_<?=$this->ipconfig_iface?> = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/iface_<?=$this->ipconfig_iface?>.xml',
            module: '<?=$this->ipconfig_module?>',
            page: 'getconfig',
            error_element: $('#<?=$this->ipconfig_id?>_form_error'),
            content_id: 'cp_<?=$this->ipconfig_id?>',
            successFn: function(json) {
                gp.data.interface_<?=$this->ipconfig_iface?> = json['interface'];
                gp.interfaces.<?=$this->ipconfig_iface?>.loadForm();
            }
        });
    };

    gp.interfaces.<?=$this->ipconfig_iface?>.loadForm = function() {
        var data = gp.data.interface_<?=$this->ipconfig_iface?>;
        gp.resetForm('<?=$this->ipconfig_id?>_form');

        $('#<?=$this->ipconfig_id?>_mac').val(data.mac);
        $('#<?=$this->ipconfig_id?>_mtu').val(data.mtu);
        if (data.ipaddr.toLowerCase() == 'dhcp') {
            $('#<?=$this->ipconfig_id?>_type_dhcp').attr('checked', 'checked');
            $('#<?=$this->ipconfig_id?>_subform_dhcp input').removeAttr('disabled');
            $('#<?=$this->ipconfig_id?>_subform_static input').attr('disabled', 'disabled');
            $('#<?=$this->ipconfig_id?>_static_dhcp_hostname').val(data.dhcphostname);
        } else {
            $('#<?=$this->ipconfig_id?>_subform_dhcp input').attr('disabled', 'disabled');
            $('#<?=$this->ipconfig_id?>_subform_static input').removeAttr('disabled');
            $('#<?=$this->ipconfig_id?>_type_static').attr('checked', 'checked');
            $('#<?=$this->ipconfig_id?>_static_ipaddr').val(data.ipaddr);
            $('#<?=$this->ipconfig_id?>_static_subnetmask').val(data.subnet);
            $('#<?=$this->ipconfig_id?>_static_gateway').val(data.gateway);
        }
    };

    $(function() {
        //XML Module: AssignInterfaces
        $('#<?=$this->ipconfig_id?>_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/iface_<?=$this->ipconfig_iface?>.xml',
                form_id: '<?=$this->ipconfig_id?>_form',
                error_element: $('#<?=$this->ipconfig_id?>_form_error'),
                successFn: function(json) {
                    gp.data.interface_<?=$this->ipconfig_iface?> = json['interface'];
                    gp.interfaces.<?=$this->ipconfig_iface?>.loadForm();
                }
            });
            return false;
        });
    });
</script>