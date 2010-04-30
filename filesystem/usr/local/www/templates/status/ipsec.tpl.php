<script type="text/javascript">
    gp.status.ipsec.clickHandler = function() {
        gp.status.ipsec.load();
    };

    //XML Module: ipsec
    gp.status.ipsec.load = function() {
        gp.data.status_ipsec_tunnels = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/status_ipsec.xml',
            module: 'ipsec',
            page: 'getstatus',
            error_element: $('#status_ipsec_table_error'),
            content_id: 'cp_status_ipsec_ipsec',
            successFn: function(json) {
                if (json.ipsec_status.tunnel) {
                    var tnl = json.ipsec_status.tunnel;
                    if ($.isArray(tnl)) {
                        $.each(tnl, function(i, rule) {
                            gp.data.status_ipsec_tunnels[i] = rule;
                        });
                    } else {
                        //One rule
                        gp.data.status_ipsec_tunnels[0] = tnl;
                    }
                }
                gp.status.ipsec.buildTable();
            }
        });
    };

    gp.status.ipsec.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#status_ipsec_tbody').empty();

        $.each(gp.data.status_ipsec_tunnels, function(id, rule) {
            gp.status.ipsec.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.status.ipsec.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule.name+'</td>'+
            '<td>'+rule.policy+'</td>'+
            '<td>'+rule.encryption+'</td>'+
            '<td><img src="images/icons/'+(rule.online=='true'?'en':'dis')+'abled.png" alt="'+(rule.online=='true'?'en':'dis')+'abled" title="'+(rule.online=='true'?'En':'Dis')+'abled"/></td>'+
            '<td>Tunnel '+(rule.online=='true'?'en':'dis')+'abled</td>'+
            '</tr>';
        $('#status_ipsec_tbody').append(tblstring);
    };

    gp.status.services.addRule = function(rule) {
        var stat_icon;

        var tblstring = '<tr>'+
            '<td>'+rule.name+'</td>'+
            '<td><img src="images/icons/'+stat_icon+'.png" alt="'+rule.status+'" title="'+rule.status+'"/></td>'+
            '<td>'+rule.status+'</td>'+
            '</tr>';
        $('#status_services_tbody').append(tblstring);
    };
</script>