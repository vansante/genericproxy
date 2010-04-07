<script type="text/javascript">
    gp.firewall.nat.clickHandler = function() {
        gp.firewall.nat.load();
    };

    //XML Module: Nat
    gp.firewall.nat.load = function() {
        gp.data.nat_inbound_rules = {};
        gp.data.nat_outbound_rules = {};
        gp.data.nat_outbound_settings = {};
        gp.data.nat_11nat_rules = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/test.xml',
            module: 'Nat',
            page: 'getconfig',
            error_element: [
                $('#firewall_nat_inbound_table_error'),
                $('#firewall_nat_outbound_enable_form_error'),
                $('#firewall_nat_11nat_table_error')
            ],
            successFn: function(json) {
                json = json.nat;

                gp.data.nat_outbound_settings = json.advancedoutbound;

                // Create an xml field to js field mapping.
                var fields = {
                    inbound: 'nat_inbound_rules',
                    advancedoutbound: 'nat_outbound_rules',
                    onetoone: 'nat_11nat_rules'
                };

                for (var xmlField in fields) {
                    var jsField = fields[xmlField];

                    //No rules
                    if (json[xmlField].rule) {
                        if ($.isArray(json[xmlField].rule)) {
                            //Multiple rules
                            $.each(json[xmlField].rule, function(i, rule) {
                                gp.data[jsField][rule.id] = rule;
                            });
                        } else {
                            //One rule
                            gp.data[jsField][json[xmlField].rule.id] = json[xmlField].rule;
                        }
                    }
                }
                gp.firewall.nat.inbound.buildTable();
                gp.firewall.nat.outbound.loadForm(json.advancedoutbound);
                gp.firewall.nat.outbound.buildTable();
                gp.firewall.nat['11nat'].buildTable();
            }
        });
    };
</script>