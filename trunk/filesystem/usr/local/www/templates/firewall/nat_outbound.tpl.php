<h2>Outbound NAT rules</h2>

<p class="intro">GenericProxy automatically adds NAT rules to all interfaces to NAT your internal hosts to your WAN IP address for outbound traffic. The only exception is for any hosts for which you have configured 1:1 NAT entries.</p>

<div class="note">
    <h3>Note:</h3>
    <p>If advanced outbound NAT is enabled, no outbound NAT rules will be automatically generated anymore. Instead, only the mappings you specify below will be used. With advanced outbound NAT disabled, a mapping is automatically created for each interface's subnet (except WAN) and any mappings specified below will be ignored.</p>
</div>

<form id="firewall_nat_outbound_enable_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="firewall_nat_outbound_enable_form_error">
    </div>
    
    <input type="hidden" name="module" value="Nat"/>
    <input type="hidden" name="page" value="save_outbound" id="firewall_nat_outbound_enable_form_page"/>

    <dl>
        <dt><label for="firewall_nat_outbound_adv_enable">Advanced outbound NAT</label></dt>
        <dd><input type="checkbox" name="firewall_nat_outbound_adv_enable" id="firewall_nat_outbound_adv_enable" value="true"/></dd>

        <dt><input type="submit" value="Save" id="firewall_nat_outbound_enable_submit" class="submitbutton"/></dt>
    </dl>

    <p style="clear: both;"></p>
</form>

<div class="help_pool">
    <div class="help" id="help_firewall_nat_outbound_adv_enable">Enable this to specify your own advanced outbound rules.</div>
</div>

<script type="text/javascript">
    gp.firewall.nat.outbound.loadForm = function() {
        var data = gp.data.nat_outbound_settings;
        gp.resetForm('firewall_nat_outbound_enable_form');
        $('#firewall_nat_outbound_adv_enable').attr('checked', data.enable.toLowerCase() == 'true');
    };

    $(function(){
        //Handler for submitting the form
        $('#firewall_nat_outbound_enable_form').submit(function() {
            var checked = $('#firewall_nat_outbound_adv_enable').attr('checked');
            if ((gp.data.nat_outbound_settings.enable.toLowerCase() == 'true' && checked)
                || (gp.data.nat_outbound_settings.enable.toLowerCase() == 'false' && !checked)) {
                return false;
            }
            gp.doFormAction({
                url: 'testxml/reply.xml',
                form_id: 'firewall_nat_outbound_enable_form',
                error_element: $('#firewall_nat_outbound_enable_form_error'),
                successFn: function(json) {
                    var enabled = gp.data.nat_outbound_settings.enable.toLowerCase() == 'true';
                    gp.data.nat_outbound_settings.enable = enabled ? 'false' : 'true';
                    gp.firewall.nat.outbound.loadForm();
                }
            });
            return false;
        });
    });
</script>

<div class="form-error" id="firewall_nat_outbound_table_error">
</div>

<table id="firewall_nat_outbound_table">
    <thead>
        <tr>
            <th>Interface</th>
            <th>Source</th>
            <th>Source port</th>
            <th>Destination</th>
            <th>Dest. port</th>
            <th>Target</th>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="firewall_nat_outbound_tbody">

    </tbody>
</table>

<p>
    <a class="icon_add" href="#firewall_nat" id="firewall_nat_outbound_add_link">Add new rule</a>
</p>

<form id="firewall_nat_outbound_form" action="ajaxserver.php" method="post" class="dialog" title="Add new rule">
    <div class="form-error" id="firewall_nat_outbound_form_error">
    </div>
    
    <input type="hidden" name="module" value="Nat"/>
    <input type="hidden" name="page" value="add_outbound_rule" id="firewall_nat_outbound_form_page"/>
    <input type="hidden" name="firewall_nat_outbound_id" value="" id="firewall_nat_outbound_id"/>

    <dl>
        <dt><label for="firewall_nat_outbound_interface">Interface</label></dt>
        <dd>
            <select name="firewall_nat_outbound_interface" id="firewall_nat_outbound_interface">
                <option value="wan">WAN</option>
                <option value="ext">EXT</option>
            </select>
        </dd>

        <dt>Source</dt>
        <dd>
            <?
            $this->out_addr_id = 'firewall_nat_outbound_src';
            include $this->template('firewall/forms/outbound_address.tpl.php');
            ?>
        </dd>

        <?
        $this->port_id = 'firewall_nat_outbound_srcport';
        $this->port_label = 'Source port';
        include $this->template('forms/port.tpl.php');
        ?>

        <dt>Destination</dt>
        <dd>
            <?
            $this->out_addr_id = 'firewall_nat_outbound_dest';
            include $this->template('firewall/forms/outbound_address.tpl.php');
            ?>
        </dd>

        <?
        $this->port_id = 'firewall_nat_outbound_destport';
        $this->port_label = 'Destination port';
        include $this->template('forms/port.tpl.php');
        ?>

        <?
        $this->port_id = 'firewall_nat_outbound_natport';
        $this->port_label = 'NAT port';
        include $this->template('forms/port.tpl.php');
        ?>

        <?
        $this->port_id = 'firewall_nat_outbound_staticnatport';
        $this->port_label = 'Static NAT port';
        include $this->template('forms/port.tpl.php');
        ?>

        <dt><label for="firewall_nat_outbound_target">Target</label></dt>
        <dd>
            <input name="firewall_nat_outbound_target" type="text" size="12" id="firewall_nat_outbound_target"/>
        </dd>

        <dt><label for="firewall_nat_outbound_disable_portmapping">Disable port mapping</label></dt>
        <dd>
            <input name="firewall_nat_outbound_disable_portmapping" type="checkbox" id="firewall_nat_outbound_disable_portmapping" value="true"/>
        </dd>

        <dt><label for="firewall_nat_outbound_descr">Description</label></dt>
        <dd>
            <input name="firewall_nat_outbound_descr" type="text" size="40" id="firewall_nat_outbound_descr"/>
        </dd>

        <dt><input type="submit" value="Add rule" id="firewall_nat_outbound_submit"></dt>
    </dl>
</form>

<div class="help_pool">
    <div class="help" id="help_firewall_nat_outbound_interface">Choose which interface this rule applies to.<br>Hint: in most cases, you'll want to use WAN here.</div>
    <div class="help" id="help_firewall_nat_outbound_src_type">Select the source address type.<br>Any: packets from any source<br>Interface: packets from a specified interface<br>IP address: packets from a specified IP address</div>
    <div class="help" id="help_firewall_nat_outbound_src_interface">Select the source interface here</div>
    <div class="help" id="help_firewall_nat_outbound_src_ipaddr">Select the source IP address here</div>
    <div class="help" id="help_firewall_nat_outbound_srcport">The packets source port</div>
    <div class="help" id="help_firewall_nat_outbound_dest_type">Select the destinaton address type.<br>Any: packets to any destination<br>Interface: packets to a specified interface<br>IP address: packets to a specified IP address</div>
    <div class="help" id="help_firewall_nat_outbound_dest_interface">Select the destination interface here</div>
    <div class="help" id="help_firewall_nat_outbound_dest_ipaddr">Select the destination IP address here</div>
    <div class="help" id="help_firewall_nat_outbound_destport">The packets destination port</div>
    <div class="help" id="help_firewall_nat_outbound_natport">The NAT port</div>
    <div class="help" id="help_firewall_nat_outbound_staticnatport">The static NAT port</div>
    <div class="help" id="help_firewall_nat_outbound_target">Packets matching this rule will be mapped to the IP address given here. Leave blank to use the selected interface's IP address.</div>
    <div class="help" id="help_firewall_nat_outbound_disable_portmapping">This option disables remapping of the source port number for outbound packets. This may help with software that insists on the source ports being left unchanged when applying NAT (such as some IPsec VPN gateways). However, with this option enabled, two clients behind NAT cannot communicate with the same server at the same time using the same source ports.</div>
    <div class="help" id="help_firewall_nat_outbound_descr">You may enter a description here for your reference</div>
</div>


<script type="text/javascript">
    gp.firewall.nat.outbound.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#firewall_nat_outbound_tbody').empty();
        $.each(gp.data.nat_outbound_rules, function(id, rule) {
            gp.firewall.nat.outbound.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.firewall.nat.outbound.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule['interface'].toUpperCase()+'</td>'+
            '<td>'+rule.source.address+'</td>'+
            '<td>'+rule.source.port+'</td>'+
            '<td>'+rule.destination.address+'</td>'+
            '<td>'+rule.destination.port+'</td>'+
            '<td>'+rule.target+'</td>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#firewall_nat" rel="'+rule.id+'" class="edit_firewall_nat_outbound_rule" title="Edit rule"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#firewall_nat" rel="'+rule.id+'" class="delete_firewall_nat_outbound_rule" title="Delete rule"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#firewall_nat_outbound_tbody').append(tblstring);
    };

    gp.firewall.nat.outbound.resetForm = function() {
        gp.resetForm('firewall_nat_outbound_form');
    };

    //Load a rule into outbound rules form
    gp.firewall.nat.outbound.formLoadRule = function(rule) {
        gp.firewall.nat.outbound.resetForm();
        $('#firewall_nat_outbound_form_page').val('edit_outbound_rule');
        $('#firewall_nat_outbound_id').val(rule.id);
        $('#firewall_nat_outbound_submit').val('Edit rule');
        $('#firewall_nat_outbound_form').dialog('option', 'title', 'Edit rule');

        $('#firewall_nat_outbound_interface').val(rule['interface'].toLowerCase());

        <?
        $this->out_addr_id = 'firewall_nat_outbound_src';
        $this->js_var = 'rule.source.address';
        include $this->template('firewall/forms/load_outbound_address_js.tpl.php');
        ?>

        <?
        $this->load_port_id = 'firewall_nat_outbound_srcport';
        $this->load_port_jsvar = 'rule.source.port';
        include $this->template('forms/load_port_js.tpl.php');
        ?>

        <?
        $this->out_addr_id = 'firewall_nat_outbound_dest';
        $this->js_var = 'rule.destination.address';
        include $this->template('firewall/forms/load_outbound_address_js.tpl.php');
        ?>

        <?
        $this->load_port_id = 'firewall_nat_outbound_destport';
        $this->load_port_jsvar = 'rule.destination.port';
        include $this->template('forms/load_port_js.tpl.php');
        ?>

        <?
        $this->load_port_id = 'firewall_nat_outbound_natport';
        $this->load_port_jsvar = 'rule.natport';
        include $this->template('forms/load_port_js.tpl.php');
        ?>

        <?
        $this->load_port_id = 'firewall_nat_outbound_staticnatport';
        $this->load_port_jsvar = 'rule.staticnatport';
        include $this->template('forms/load_port_js.tpl.php');
        ?>

        $('#firewall_nat_outbound_target').val(rule.target);
        $('#firewall_nat_outbound_disable_portmapping').attr('checked', rule.nonat == 'true');
        $('#firewall_nat_outbound_descr').val(rule.description);
    };

    $(function() {
        $('#firewall_nat_outbound_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 800,
            modal: true
        });
        $('#firewall_nat_outbound_add_link').click(function() {
            gp.firewall.nat.outbound.resetForm();
            $('#firewall_nat_outbound_form_page').val('add_outbound_rule');
            $('#firewall_nat_outbound_id').val(false);
            $('#firewall_nat_outbound_submit').val('Add rule');
            $('#firewall_nat_outbound_form').dialog('option', 'title', 'Add new rule');
            $('#firewall_nat_outbound_form').dialog('open');
        });

        //Handler for submitting the form
        $('#firewall_nat_outbound_form').submit(function() {
            gp.doFormAction({
                form_id: 'firewall_nat_outbound_form',
                error_element: $('#firewall_nat_outbound_form_error'),
                successFn: function(json) {
                    gp.data.nat_outbound_rules[json.nat.advancedoutbound.rule.id] = json.nat.advancedoutbound.rule;
                    gp.firewall.nat.outbound.buildTable();
                    $('#firewall_nat_outbound_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_firewall_nat_outbound_rule').live('click', function() {
            var rule = gp.data.nat_outbound_rules[$(this).attr('rel')];
            gp.firewall.nat.outbound.formLoadRule(rule);
            $('#firewall_nat_outbound_submit').val('Edit rule');
            $('#firewall_nat_outbound_form').dialog('option', 'title', 'Edit rule');
            $('#firewall_nat_outbound_form').dialog('open');
        });

        //Click handler for deleting rule
        $('.delete_firewall_nat_outbound_rule').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this rule?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Nat',
                    page: 'delete_outbound_rule',
                    params: {
                        ruleid: id
                    },
                    error_element: $('#firewall_nat_outbound_table_error'),
                    content_id: 'cp_firewall_nat_outbound',
                    successFn: function(json) {
                        delete gp.data.nat_outbound_rules[id];
                        gp.firewall.nat.outbound.buildTable();
                    }
                });
            });
        });
    });
</script>