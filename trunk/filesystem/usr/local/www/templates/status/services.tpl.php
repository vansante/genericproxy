<script type="text/javascript">
    gp.status.services.clickHandler = function() {
        gp.status.services.load();
    };

    //XML Module: Dhcpd
    gp.status.services.load = function() {
        gp.data.status_services = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/status_services.xml',
            module: 'System',
            page: 'getservicesstatus',
            error_element: $('#status_services_table_error'),
            content_id: 'cp_status_services_services',
            successFn: function(json) {
                if (json.services.service) {
                    var service = json.services.service;
                    if ($.isArray(service)) {
                        $.each(service, function(i, rule) {
                            gp.data.status_services[i] = rule;
                        });
                    } else {
                        //One rule
                        gp.data.status_services[0] = service;
                    }
                }
                gp.status.services.buildTable();
            }
        });
    };

    gp.status.services.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#status_services_tbody').empty();

        $.each(gp.data.status_services, function(id, rule) {
            gp.status.services.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.status.services.addRule = function(rule) {
        var stat_icon;
        switch(rule.status.toLowerCase()) {
            case 'started':
                stat_icon = 'enabled';
                break;
            case 'stopped':
                stat_icon = 'disabled';
                break;
            case 'error':
                stat_icon = 'error';
                break;
        }
        var tblstring = '<tr>'+
            '<td>'+rule.name+'</td>'+
            '<td><img src="images/icons/'+stat_icon+'.png" alt="'+rule.status+'" title="'+rule.status+'"/></td>'+
            '<td>'+rule.status+'</td>'+
            '</tr>';
        $('#status_services_tbody').append(tblstring);
    };
</script>