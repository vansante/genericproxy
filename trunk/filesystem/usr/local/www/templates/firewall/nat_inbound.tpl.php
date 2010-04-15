<h2>Inbound NAT rules</h2>

<p class="intro">Inbound NAT allows you to open up TCP and/or UDP ports or port ranges to hosts on networks protected by Generic Proxy. You may need to open ports to allow certain NAT-unfriendly applications and protocols to function properly. Also if you run any services or applications that require inbound connections to a machine on your internal network, you will need inbound NAT.</p>

<div class="note">
    <h3>Note:</h3>
    <p>It is not possible to access NATed services using the WAN IP address from within LAN (or an optional network).</p>
</div>

<div class="form-error" id="firewall_nat_inbound_table_error">
</div>

<table id="firewall_nat_inbound_table">
    <thead>
        <tr>
            <th>Interface</th>
            <th>Protocol</th>
            <th>Ext. port range</th>
            <th>NAT IP</th>
            <th>Int. port range</th>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="firewall_nat_inbound_tbody">

    </tbody>
</table>

<p>
    <a class="icon_add" href="#firewall_nat" id="firewall_nat_inbound_add_link">Add new rule</a>
</p>

<form id="firewall_nat_inbound_form" action="ajaxserver.php" method="post" class="dialog" title="Add new rule">
    <div class="form-error" id="firewall_nat_inbound_form_error">
    </div>

    <input type="hidden" name="module" value="Nat"/>
    <input type="hidden" name="page" value="add_inbound_rule" id="firewall_nat_inbound_form_page"/>
    <input type="hidden" name="firewall_nat_inbound_id" value="" id="firewall_nat_inbound_id"/>

    <dl>
        <dt><label for="firewall_nat_inbound_interface">Interface</label></dt>
        <dd>
            <select name="firewall_nat_inbound_interface" id="firewall_nat_inbound_interface">
                <option value="wan">WAN</option>
                <option value="ext">EXT</option>
            </select>
        </dd>

        <dt><label for="firewall_nat_inbound_ext_ip">External IP address</label></dt>
        <dd><input name="firewall_nat_inbound_ext_ip" type="text" size="12" id="firewall_nat_inbound_ext_ip"/></dd>

        <dt><label for="firewall_nat_inbound_protocol">Protocol</label></dt>
        <dd>
            <select name="firewall_nat_inbound_protocol" id="firewall_nat_inbound_protocol">
                <option value="tcp">TCP</option>
                <option value="udp">UDP</option>
                <option value="tcp/udp">TCP/UDP</option>
            </select>
        </dd>

        <dt>External port range</dt>
        <dd>
            <?
            $this->portrange_id = 'firewall_nat_inbound_portrange';
            include $this->template('forms/portrange.tpl.php');
            ?>
        </dd>

        <dt><label for="firewall_nat_inbound_nat_ip">NAT IP address</label></dt>
        <dd><input name="firewall_nat_inbound_nat_ip" type="text" size="12" id="firewall_nat_inbound_nat_ip"/></dd>

        <?
        $this->port_id = 'firewall_nat_inbound_localport';
        $this->port_label = 'Local port';
        include $this->template('forms/port.tpl.php');
        ?>

        <dt class="firewall_nat_inbound_add_firewallrule"><label for="firewall_nat_inbound_add_firewallrule">Automaticly add firewall rule</label></dt>
        <dd class="firewall_nat_inbound_add_firewallrule">
            <input name="firewall_nat_inbound_add_firewallrule" type="checkbox" id="firewall_nat_inbound_add_firewallrule" value="true"/>
        </dd>

        <dt><label for="firewall_nat_inbound_descr">Description</label></dt>
        <dd><input name="firewall_nat_inbound_descr" type="text" size="40" id="firewall_nat_inbound_descr"/></dd>

        <dt><input type="submit" value="Add rule" id="firewall_nat_inbound_submit" class="submitbutton"/></dt>
    </dl>
</form>

<div class="help_pool">
    <div class="help" id="help_firewall_nat_inbound_interface">Choose which interface this rule applies to.<br>Hint: in most cases, you'll want to use WAN here.</div>
    <div class="help" id="help_firewall_nat_inbound_ext_ip">If you want this rule to apply to another IP address other than the IP address of the interface chosen above, select it here.</div>
    <div class="help" id="help_firewall_nat_inbound_protocol">Choose which IP protocol this rule should match.<br>Hint: in most cases you should specify TCP here.</div>
    <div class="help" id="help_firewall_nat_inbound_portrange_from">Specify the port or port range on the firewall's external address for this mapping.<br>Hint: you can leave the 'to' field empty if you only want to map a single port</div>
    <div class="help" id="help_firewall_nat_inbound_nat_ip">Enter the internal IP address of the server on which you want to map the ports.<br>e.g. 192.168.1.12</div>
    <div class="help" id="help_firewall_nat_inbound_localport">Specify the port on the machine with the IP address entered above. In case of a port range, specify the beginning port of the range (the end port will be calculated automatically).<br>Hint: this is usually identical to the 'from' port above</div>
    <div class="help" id="help_firewall_nat_inbound_add_firewallrule">Automaticly add a firewall rule to permit traffic through this NAT rule</div>
    <div class="help" id="help_firewall_nat_inbound_descr">You may enter a description here for your reference.</div>
</div>

<script type="text/javascript">
    gp.firewall.nat.inbound.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#firewall_nat_inbound_tbody').empty();
        $.each(gp.data.nat_inbound_rules, function(id, rule) {
            gp.firewall.nat.inbound.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.firewall.nat.inbound.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule['interface'].toUpperCase()+'</td>'+
            '<td>'+rule.protocol.toUpperCase()+'</td>'+
            '<td>'+rule.external_port+'</td>'+
            '<td>'+rule.external_address+'</td>'+
            '<td>'+rule.local_port+'</td>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#firewall_nat" rel="'+rule.id+'" class="edit_firewall_nat_inbound_rule" title="Edit rule"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#firewall_nat" rel="'+rule.id+'" class="delete_firewall_nat_inbound_rule" title="Delete rule"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#firewall_nat_inbound_tbody').append(tblstring);
    };

    gp.firewall.nat.inbound.resetForm = function() {
        $('.firewall_nat_inbound_add_firewallrule').show();
        gp.resetForm('firewall_nat_inbound_form');
    };

    //Load a rule into inbound rules form
    gp.firewall.nat.inbound.formLoadRule = function(rule) {
        gp.firewall.nat.inbound.resetForm();
        $('#firewall_nat_inbound_form_page').val('edit_inbound_rule');
        $('#firewall_nat_inbound_id').val(rule.id);
        $('#firewall_nat_inbound_submit').val('Edit rule');
        $('#firewall_nat_inbound_form').dialog('option', 'title', 'Edit rule');

        $('#firewall_nat_inbound_interface').val(rule['interface'].toLowerCase());
        $('#firewall_nat_inbound_ext_ip').val(rule.external_address);
        $('#firewall_nat_inbound_protocol').val(rule.protocol.toLowerCase());

        <?
        $this->load_portrange_id = 'firewall_nat_inbound_portrange';
        $this->load_portrange_jsvar = 'rule.external_port';
        include $this->template('forms/load_portrange_js.tpl.php');
        ?>
        
        $('#firewall_nat_inbound_nat_ip').val(rule.target);

        <?
        $this->load_port_id = 'firewall_nat_inbound_localport';
        $this->load_port_jsvar = 'rule.local_port';
        include $this->template('forms/load_port_js.tpl.php');
        ?>

        $('.firewall_nat_inbound_add_firewallrule').hide();

        $('#firewall_nat_inbound_descr').val(rule.description);
    };

    $(function() {
        $('#firewall_nat_inbound_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 800,
            modal: true
        });
        $('#firewall_nat_inbound_add_link').click(function() {
            gp.firewall.nat.inbound.resetForm();
            $('#firewall_nat_inbound_form_page').val('add_inbound_rule');
            $('#firewall_nat_inbound_id').val(false);
            $('#firewall_nat_inbound_submit').val('Add rule');
            $('#firewall_nat_inbound_form').dialog('option', 'title', 'Add new rule');
            $('#firewall_nat_inbound_form').dialog('open');
        });

        //Handler for submitting the form
        $('#firewall_nat_inbound_form').submit(function() {
            gp.doFormAction({
                form_id: 'firewall_nat_inbound_form',
                error_element: $('#firewall_nat_inbound_form_error'),
                successFn: function(json) {
                    gp.data.nat_inbound_rules[json.nat.inbound.rule.id] = json.nat.inbound.rule;
                    gp.firewall.nat.inbound.buildTable();
                    $('#firewall_nat_inbound_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_firewall_nat_inbound_rule').live('click', function() {
            var rule = gp.data.nat_inbound_rules[$(this).attr('rel')];
            gp.firewall.nat.inbound.formLoadRule(rule);
            $('#firewall_nat_inbound_submit').val('Edit rule');
            $('#firewall_nat_inbound_form').dialog('option', 'title', 'Edit rule');
            $('#firewall_nat_inbound_form').dialog('open');
        });

        //Click handler for deleting rule
        $('.delete_firewall_nat_inbound_rule').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this rule?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Nat',
                    page: 'delete_inbound_rule',
                    params: {
                        ruleid: id
                    },
                    error_element: $('#firewall_nat_inbound_table_error'),
                    content_id: 'cp_firewall_nat_inbound',
                    successFn: function(json) {
                        delete gp.data.nat_inbound_rules[id];
                        gp.firewall.nat.inbound.buildTable();
                    }
                });
            });
        });
    });
</script>