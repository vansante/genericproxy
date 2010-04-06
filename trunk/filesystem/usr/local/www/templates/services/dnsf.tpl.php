<script type="text/javascript">
    gp.services.dnsf.clickHandler = function() {
        gp.services.dnsf.load();
    };

    //XML Module: DnsForward
    gp.services.dnsf.load = function() {
        gp.data.dnsf_settings = {};
        gp.data.dnsf_masks = {};
        gp.data.dnsf_overrides = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/dnsforward.xml',
            module: 'DnsForward',
            page: 'getconfig',
            error_element: [
                $('#services_dnsf_form_error'),
                $('#services_dnsf_mask_table_error'),
                $('#services_dnsf_override_table_error')
            ],
            successFn: function(json) {
                gp.data.dnsf_settings = json.dnsmasq;
                gp.services.dnsf.settings.loadForm();

                if (json.dnsmasq.hosts.host) {
                    var host = json.dnsmasq.hosts.host;
                    if ($.isArray(host)) {
                        //Multiple rules
                        $.each(host, function(i, rule) {
                            gp.data.dnsf_masks[rule.id] = rule;
                        });
                    } else {
                        //One rule
                        gp.data.dnsf_masks[host.id] = host;
                    }
                    gp.services.dnsf.masks.buildTable();
                }

                if (json.dnsmasq.overrides.override) {
                    var override = json.dnsmasq.overrides.override;
                    if ($.isArray(override)) {
                        //Multiple rules
                        $.each(override, function(i, rule) {
                            gp.data.dnsf_overrides[rule.id] = rule;
                        });
                    } else {
                        //One rule
                        gp.data.dnsf_overrides[override.id] = override;
                    }
                    gp.services.dnsf.overrides.buildTable();
                }
            }
        });
    };
</script>