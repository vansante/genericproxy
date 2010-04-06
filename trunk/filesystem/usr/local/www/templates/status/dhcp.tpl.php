<script type="text/javascript">
    gp.status.dhcp.clickHandler = function() {
        gp.status.dhcp.load();
    };

    //XML Module: Dhcpd
    gp.status.dhcp.load = function() {
        gp.data.status_dhcp_leases = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/status_dhcp.xml',
            module: 'Dhcpd',
            page: 'getstatus',
            error_element: $('#status_dhcp_table_error'),
            successFn: function(json) {
                if (json.dhcp_status.lease) {
                    var lease = json.dhcp_status.lease;
                    if ($.isArray(lease)) {
                        $.each(lease, function(i, rule) {
                            gp.data.status_dhcp_leases[i] = rule;
                        });
                    } else {
                        //One rule
                        gp.data.status_dhcp_leases[0] = lease;
                    }
                }
                gp.status.dhcp.buildTable();
            }
        });
    };

    gp.status.dhcp.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#status_dhcp_tbody').empty();
        
        $.each(gp.data.status_dhcp_leases, function(id, rule) {
            gp.status.dhcp.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.status.dhcp.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td><img src="images/icons/'+(rule.online.toLowerCase()=='true'?'en':'dis')+'abled.png" alt="'+(rule.online.toLowerCase()=='true'?'On':'Off')+'line" title="'+(rule.online.toLowerCase()=='true'?'On':'Off')+'line"/></td>'+
            '<td>'+rule.ip+'</td>'+
            '<td>'+rule.mac+'</td>'+
            '<td>'+rule.hostname+'</td>'+
            '<td>'+rule.start+'</td>'+
            '<td>'+rule.end+'</td>'+
            '</tr>';
        $('#status_dhcp_tbody').append(tblstring);
    };
</script>