<h2>DNS forwarding domain overrides</h2>

<div class="form-error" id="services_dnsf_override_table_error">
</div>

<table id="services_dnsf_override_table">
    <thead>
        <tr>
            <th>Domain</th>
            <th>IP address</th>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="services_dnsf_override_tbody">

    </tbody>
</table>

<p>
    <a class="icon_add" href="#services_dnsf" id="services_dnsf_override_add_link">Add new override</a>
</p>

<form id="services_dnsf_override_form" action="ajaxserver.php" method="post" class="dialog" title="Add new override">
    <div class="form-error" id="services_dnsf_override_form_error">
    </div>

    <input type="hidden" name="module" value="DnsForward"/>
    <input type="hidden" name="page" value="addoverride" id="services_dnsf_override_form_page"/>
    <input type="hidden" name="services_dnsf_override_id" value="" id="services_dnsf_override_id"/>

    <dl>
        <dt><label for="services_dnsf_override_domain">Domain</label></dt>
        <dd>
            <input name="services_dnsf_override_domain" type="text" size="20" id="services_dnsf_override_domain"/>
        </dd>

        <dt><label for="services_dnsf_override_ipaddr">IP address</label></dt>
        <dd>
            <input name="services_dnsf_override_ipaddr" type="text" size="12" id="services_dnsf_override_ipaddr"/>
        </dd>

        <dt><label for="services_dnsf_override_descr">Description</label></dt>
        <dd>
            <input name="services_dnsf_override_descr" type="text" size="40" id="services_dnsf_override_descr"/>
        </dd>

        <dt><input type="submit" value="Add mask" id="services_dnsf_override_submit" class="submitbutton"/></dt>
    </dl>
</form>

<div class="help_pool">
    <div class="help" id="help_services_dnsf_override_domain">Domain to override<br>e.g. test</div>
    <div class="help" id="help_services_dnsf_override_ipaddr">IP address of the authoritative DNS server for this domain<br>e.g. 192.168.100.100</div>
    <div class="help" id="help_services_dnsf_override_descr">You may enter a description here for your reference</div>
</div>

<script type="text/javascript">
    gp.services.dnsf.overrides.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#services_dnsf_override_tbody').empty();
        $.each(gp.data.dnsf_overrides, function(id, rule) {
            gp.services.dnsf.overrides.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.services.dnsf.overrides.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule.domain+'</td>'+
            '<td>'+rule.ip+'</td>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#services_dnsf" rel="'+rule.id+'" class="edit_services_dnsf_override" title="Edit override"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#services_dnsf" rel="'+rule.id+'" class="delete_services_dnsf_override" title="Delete override"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#services_dnsf_override_tbody').append(tblstring);
    };

    gp.services.dnsf.overrides.resetForm = function() {
        gp.resetForm('services_dnsf_override_form');
    };

    //Load a rule into outbound rules form
    gp.services.dnsf.overrides.formLoadRule = function(rule) {
        gp.services.dnsf.overrides.resetForm();
        $('#services_dnsf_override_form_page').val('editoverride');
        $('#services_dnsf_override_id').val(rule.id);
        $('#services_dnsf_override_submit').val('Edit override');
        $('#services_dnsf_override_form').dialog('option', 'title', 'Edit override');

        $('#services_dnsf_override_domain').val(rule.domain);
        $('#services_dnsf_override_ipaddr').val(rule.ip);
        $('#services_dnsf_override_descr').val(rule.description);
    };

    $(function() {
        $('#services_dnsf_override_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 600,
            modal: true
        });
        
        $('#services_dnsf_override_add_link').click(function() {
            gp.services.dnsf.overrides.resetForm();
            $('#services_dnsf_override_form_page').val('addoverride');
            $('#services_dnsf_override_id').val(false);
            $('#services_dnsf_override_submit').val('Add override');
            $('#services_dnsf_override_form').dialog('option', 'title', 'Add new override');
            $('#services_dnsf_override_form').dialog('open');
        });

        //Handler for submitting the form
        $('#services_dnsf_override_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/dnsforward.xml',
                form_id: 'services_dnsf_override_form',
                error_element: $('#services_dnsf_override_form_error'),
                successFn: function(json) {
                    gp.data.dnsf_overrides[json.dnsmasq.overrides.override.id] = json.dnsmasq.overrides.override;
                    gp.services.dnsf.overrides.buildTable();
                    $('#services_dnsf_override_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_services_dnsf_override').live('click', function() {
            var rule = gp.data.dnsf_overrides[$(this).attr('rel')];
            gp.services.dnsf.overrides.formLoadRule(rule);
            $('#services_dnsf_override_submit').val('Edit override');
            $('#services_dnsf_override_form').dialog('option', 'title', 'Edit override');
            $('#services_dnsf_override_form').dialog('open');
        });

        //Click handler for deleting rule
        $('.delete_services_dnsf_override').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this override?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'DnsForward',
                    page: 'deleteoverride',
                    params: {
                        overrideid: id
                    },
                    error_element: $('#services_dnsf_override_table_error'),
                    successFn: function(json) {
                        delete gp.data.dnsf_overrides[id];
                        gp.services.dnsf.overrides.buildTable();
                    }
                });
            });
        });
    });
</script>