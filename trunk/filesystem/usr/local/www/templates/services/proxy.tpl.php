<script type="text/javascript">
    gp.services.proxy.clickHandler = function() {
        gp.services.proxy.load();
    };

    //XML Module: Proxy
    gp.services.proxy.load = function() {
        gp.data.proxy_ports = {};
        gp.data.proxy_settings = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/proxy.xml',
            module: 'Proxy',
            page: 'getconfig',
            error_element: [
                $('#services_proxy_settings_form_error'),
                $('#services_proxy_port_table_error')
            ],
            content_id: ['cp_services_proxy_settings', 'cp_services_proxy_ports'],
            successFn: function(json) {
                json = json.proxy;

                gp.data.proxy_settings = json;
                gp.services.proxy.settings.loadForm();

                if (json.ports.port) {
                    var port = json.ports.port;
                    if ($.isArray(port)) {
                        $.each(port, function(i, rule) {
                            gp.data.proxy_ports[rule.id] = rule;
                        });
                    } else {
                        gp.data.proxy_ports[port.id] = port;
                    }
                }
                gp.services.proxy.ports.buildTable();
            }
        });
    };
</script>