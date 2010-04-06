<h2>DHCPD static mappings</h2>

<div class="form-error" id="services_dhcpd_table_error">
</div>

<table id="services_dhcpd_table">
    <thead>
        <tr>
            <th>MAC address</th>
            <th>IP address</th>
            <th>Hostname</th>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="services_dhcpd_tbody">

    </tbody>
</table>


<p>
    <a class="icon_add" href="#services_dhcpd" id="services_dhcpd_rule_add_link">Add new mapping</a>
</p>

<form id="services_dhcpd_rule_form" action="ajaxserver.php" method="post" class="dialog" title="Add new rule">
    <div class="form-error" id="services_dhcpd_rule_form_error">
    </div>

    <input type="hidden" name="module" value="Dhcpd"/>
    <input type="hidden" name="page" value="addrule" id="services_dhcpd_rule_form_page"/>
    <input type="hidden" name="services_dhcpd_rule_id" value="" id="services_dhcpd_rule_id"/>

    <dl>
        <dt><label for="services_dhcpd_rule_macaddr">MAC address</label></dt>
        <dd>
            <input name="services_dhcpd_rule_macaddr" type="text" size="20" id="services_dhcpd_rule_macaddr"/>
        </dd>

        <dt><label for="services_dhcpd_rule_ipaddr">IP address</label></dt>
        <dd>
            <input name="services_dhcpd_rule_ipaddr" type="text" size="12" id="services_dhcpd_rule_ipaddr"/>
        </dd>

        <dt><label for="services_dhcpd_rule_hostname">Hostname</label></dt>
        <dd>
            <input name="services_dhcpd_rule_hostname" type="text" size="20" id="services_dhcpd_rule_hostname"/>
        </dd>

        <dt><label for="services_dhcpd_rule_descr">Description</label></dt>
        <dd>
            <input name="services_dhcpd_rule_descr" type="text" size="40" id="services_dhcpd_rule_descr"/>
        </dd>

        <dt><input type="submit" value="Add rule" id="services_dhcpd_rule_submit" class="submitbutton"/></dt>
    </dl>
</form>

<div class="help_pool">
    <div class="help" id="help_services_dhcpd_rule_macaddr">Enter a MAC address in the following format: xx:xx:xx:xx:xx:xx</div>
    <div class="help" id="help_services_dhcpd_rule_ipaddr">If no IP address is given, one will be dynamically allocated from the pool.</div>
    <div class="help" id="help_services_dhcpd_rule_hostname">Name of the host, without domain part.</div>
    <div class="help" id="help_services_dhcpd_rule_descr">You may enter a description here for your reference</div>
</div>

<script type="text/javascript">
    gp.services.dhcpd.rules.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#services_dhcpd_tbody').empty();
        
        $.each(gp.data.dhcpd_rules, function(id, rule) {
            gp.services.dhcpd.rules.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.services.dhcpd.rules.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule.mac+'</td>'+
            '<td>'+rule.ipaddr+'</td>'+
            '<td>'+rule.hostname+'</td>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#services_dhcpd" rel="'+rule.id+'" class="edit_services_dhcpd_rule" title="Edit mapping"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#services_dhcpd" rel="'+rule.id+'" class="delete_services_dhcpd_rule" title="Delete mapping"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#services_dhcpd_tbody').append(tblstring);
    };

    gp.services.dhcpd.rules.resetForm = function() {
        gp.resetForm('services_dhcpd_rule_form');
    };

    //Load a rule into dhcpd rules form
    gp.services.dhcpd.rules.formLoadRule = function(rule) {
        gp.services.dhcpd.rules.resetForm();
        $('#services_dhcpd_rule_form_page').val('editrule');
        $('#services_dhcpd_rule_id').val(rule.id);
        $('#services_dhcpd_rule_submit').val('Edit rule');
        $('#services_dhcpd_rule_form').dialog('option', 'title', 'Edit mapping');

        $('#services_dhcpd_rule_macaddr').val(rule.mac);
        $('#services_dhcpd_rule_ipaddr').val(rule.ipaddr);
        $('#services_dhcpd_rule_hostname').val(rule.hostname);
        $('#services_dhcpd_rule_descr').val(rule.description);
    };
    
    $(function() {
        $('#services_dhcpd_rule_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 630,
            modal: true
        });

        $('#services_dhcpd_rule_add_link').click(function() {
            gp.services.dhcpd.rules.resetForm();
            $('#services_dhcpd_rule_form_page').val('addrule');
            $('#services_dhcpd_rule_id').val(false);
            $('#services_dhcpd_rule_submit').val('Add rule');
            $('#services_dhcpd_rule_form').dialog('option', 'title', 'Add new mapping');
            $('#services_dhcpd_rule_form').dialog('open');
        });

        //Handler for submitting the form
        $('#services_dhcpd_rule_form').submit(function() {
            gp.doFormAction({
                form_id: 'services_dhcpd_rule_form',
                error_element: $('#services_dhcpd_rule_form_error'),
                successFn: function(json) {
                    gp.data.dhcpd_rules[json.staticmaps.map.id] = json.staticmaps.map;
                    gp.services.dhcpd.rules.buildTable();
                    $('#services_dhcpd_rule_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_services_dhcpd_rule').live('click', function() {
            var rule = gp.data.dhcpd_rules[$(this).attr('rel')];
            gp.services.dhcpd.rules.formLoadRule(rule);
            $('#services_dhcpd_rule_submit').val('Edit mapping');
            $('#services_dhcpd_rule_form').dialog('option', 'title', 'Edit mapping');
            $('#services_dhcpd_rule_form').dialog('open');
        });

        //Click handler for deleting rule
        $('.delete_services_dhcpd_rule').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this mapping?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Dhcpd',
                    page: 'deleterule',
                    params: {
                        ruleid: id
                    },
                    error_element: $('#services_dhcpd_table_error'),
                    successFn: function(json) {
                        delete gp.data.dhcpd_rules[id];
                        gp.services.dhcpd.rules.buildTable();
                    }
                });
            });
        });
    });
</script>