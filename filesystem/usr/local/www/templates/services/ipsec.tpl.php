<script type="text/javascript">
    gp.services.ipsec.clickHandler = function() {
        gp.services.ipsec.load();
    };

    //XML Module: Ipsec
    gp.services.ipsec.load = function() {
        gp.data.ipsec_settings = {};
        gp.data.ipsec_tunnels = {};
        gp.data.ipsec_keys = {};
        gp.data.ipsec_certificates = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/ipsec.xml',
            module: 'Ipsec',
            page: 'getconfig',
            error_element: [
                $('#services_ipsec_settings_form_error'),
                $('#services_ipsec_tunnel_table_error'),
                $('#services_ipsec_key_table_error'),
                $('#services_ipsec_certif_table_error')
            ],
            content_id: ['cp_services_ipsec_settings', 'cp_services_ipsec_tunnels', 'cp_services_ipsec_keys', 'cp_services_ipsec_certificates'],
            successFn: function(json) {
                gp.data.ipsec_settings = json.ipsec;

                gp.services.ipsec.settings.loadForm();

                if (json.ipsec.tunnels.tunnel) {
                    var tnls = json.ipsec.tunnels.tunnel;
                    if ($.isArray(tnls)) {
                        $.each(tnls, function(i, tnl) {
                            gp.data.ipsec_tunnels[tnl.id] = tnl;
                        });
                    } else {
                        gp.data.ipsec_tunnels[tnls.id] = tnls;
                    }
                }
                gp.services.ipsec.tunnels.buildTable();

                if (json.ipsec.keys.key) {
                    var keys = json.ipsec.keys.key;
                    if ($.isArray(keys)) {
                        $.each(keys, function(i, key) {
                            gp.data.ipsec_keys[key.id] = key;
                        });
                    } else {
                        gp.data.ipsec_keys[keys.id] = keys;
                    }
                }
                gp.services.ipsec.keys.buildTable();

                if (json.ipsec.certificates.certificate) {
                    var certifs = json.ipsec.certificates.certificate;
                    if ($.isArray(certifs)) {
                        $.each(certifs, function(i, certif) {
                            gp.data.ipsec_certificates[certif.id] = certif;
                        });
                    } else {
                        gp.data.ipsec_certificates[certifs.id] = certifs;
                    }
                }
                gp.services.ipsec.certificates.buildTable();
            }
        });
    };
</script>
