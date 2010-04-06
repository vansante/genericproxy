<script type="text/javascript">
    gp.services.dhcpd.clickHandler = function() {
        gp.services.dhcpd.load();
    };

    //XML Module: Dhcpd
    gp.services.dhcpd.load = function() {
        gp.data.dhcpd_settings = {};
        gp.data.dhcpd_rules = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/dhcpd.xml',
            module: 'Dhcpd',
            page: 'getconfig',
            error_element: [
                $('#services_dhcpd_form_error'),
                $('#services_dhcpd_table_error')
            ],
            successFn: function(json) {
                gp.data.dhcpd_settings = json.dhcpd;
                gp.services.dhcpd.settings.loadForm();

                if (json.dhcpd.staticmaps.map) {
                    var map = json.dhcpd.staticmaps.map;
                    if ($.isArray(map)) {
                        //Multiple rules
                        $.each(map, function(i, rule) {
                            gp.data.dhcpd_rules[rule.id] = rule;
                        });
                    } else {
                        //One rule
                        gp.data.dhcpd_rules[map.id] = map;
                    }
                }
                gp.services.dhcpd.rules.buildTable();
            }
        });
    };
</script>