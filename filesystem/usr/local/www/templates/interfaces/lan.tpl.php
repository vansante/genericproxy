<script type="text/javascript">
    gp.interfaces.lan.clickHandler = function() {
        gp.interfaces.lan.load();
    };

    gp.interfaces.lan.load = function() {
        gp.data.interface_lan = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/iface_lan.xml',
            module: 'Lan',
            page: 'getconfig',
            error_element: $('#interfaces_lan_form_error'),
            content_id: 'cp_interfaces_lan_lan',
            successFn: function(json) {
                gp.data.interface_lan = json['interface'];
                gp.interfaces.lan.loadForm();
            }
        });
    };

    gp.interfaces.lan.loadForm = function() {
        var data = gp.data.interface_lan;
        gp.resetForm('interfaces_lan_form');

        $('#interfaces_lan_ipaddr').val(data.ipaddr);
        $('#interfaces_lan_subnetmask').val(data.subnet);
        $('#interfaces_lan_mtu').val(data.mtu);
    };

    $(function() {
        //XML Module: AssignInterfaces
        $('#interfaces_lan_form').submit(function() {
            gp.doFormAction({
                form_id: 'interfaces_lan_form',
                error_element: $('#interfaces_lan_form_error'),
                successFn: function(json) {
                    gp.data.interface_lan = json['interface'];
                    gp.interfaces.lan.loadForm();
                }
            });
            return false;
        });
    });
</script>