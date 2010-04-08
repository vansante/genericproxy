<h2>DNS forwarding masks</h2>

<div class="form-error" id="services_dnsf_mask_table_error">
</div>

<table id="services_dnsf_mask_table">
    <thead>
        <tr>
            <th>Host</th>
            <th>Domain</th>
            <th>IP address</th>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="services_dnsf_mask_tbody">

    </tbody>
</table>

<p>
    <a class="icon_add" href="#services_dnsf" id="services_dnsf_mask_add_link">Add new mask</a>
</p>

<form id="services_dnsf_mask_form" action="ajaxserver.php" method="post" class="dialog" title="Add new mask">
    <div class="form-error" id="services_dnsf_mask_form_error">
    </div>

    <input type="hidden" name="module" value="DnsForward"/>
    <input type="hidden" name="page" value="addmask" id="services_dnsf_mask_form_page"/>
    <input type="hidden" name="services_dnsf_mask_id" value="" id="services_dnsf_mask_id"/>

    <dl>
        <dt><label for="services_dnsf_mask_host">Host</label></dt>
        <dd>
            <input name="services_dnsf_mask_host" type="text" size="20" id="services_dnsf_mask_host"/>
        </dd>

        <dt><label for="services_dnsf_mask_domain">Domain</label></dt>
        <dd>
            <input name="services_dnsf_mask_domain" type="text" size="20" id="services_dnsf_mask_domain"/>
        </dd>

        <dt><label for="services_dnsf_mask_ipaddr">IP address</label></dt>
        <dd>
            <input name="services_dnsf_mask_ipaddr" type="text" size="12" id="services_dnsf_mask_ipaddr"/>
        </dd>

        <dt><label for="services_dnsf_mask_descr">Description</label></dt>
        <dd>
            <input name="services_dnsf_mask_descr" type="text" size="40" id="services_dnsf_mask_descr"/>
        </dd>

        <dt><input type="submit" value="Add mask" id="services_dnsf_mask_submit" class="submitbutton"/></dt>
    </dl>
</form>

<div class="help_pool">
    <div class="help" id="help_services_dnsf_mask_host">Name of the host, without domain part<br>e.g. myhost</div>
    <div class="help" id="help_services_dnsf_mask_domain">Domain of the host<br>e.g. blah.com</div>
    <div class="help" id="help_services_dnsf_mask_ipaddr">IP address of the host<br>e.g. 192.168.100.100</div>
    <div class="help" id="help_services_dnsf_mask_descr">You may enter a description here for your reference</div>
</div>

<script type="text/javascript">
    gp.services.dnsf.masks.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#services_dnsf_mask_tbody').empty();
        $.each(gp.data.dnsf_masks, function(id, rule) {
            gp.services.dnsf.masks.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.services.dnsf.masks.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule.name+'</td>'+
            '<td>'+rule.domain+'</td>'+
            '<td>'+rule.ip+'</td>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#services_dnsf" rel="'+rule.id+'" class="edit_services_dnsf_mask" title="Edit mask"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#services_dnsf" rel="'+rule.id+'" class="delete_services_dnsf_mask" title="Delete mask"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#services_dnsf_mask_tbody').append(tblstring);
    };

    gp.services.dnsf.masks.resetForm = function() {
        gp.resetForm('services_dnsf_mask_form');
    };

    //Load a rule into outbound rules form
    gp.services.dnsf.masks.formLoadRule = function(rule) {
        gp.services.dnsf.masks.resetForm();
        $('#services_dnsf_mask_form_page').val('editmask');
        $('#services_dnsf_mask_id').val(rule.id);
        $('#services_dnsf_mask_submit').val('Edit mask');
        $('#services_dnsf_mask_form').dialog('option', 'title', 'Edit mask');

        $('#services_dnsf_mask_host').val(rule.name);
        $('#services_dnsf_mask_domain').val(rule.domain);
        $('#services_dnsf_mask_ipaddr').val(rule.ip);
        $('#services_dnsf_mask_descr').val(rule.description);
    };

    $(function() {
        $('#services_dnsf_mask_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 600,
            modal: true
        });
        $('#services_dnsf_mask_add_link').click(function() {
            gp.services.dnsf.masks.resetForm();
            $('#services_dnsf_mask_form_page').val('addmask');
            $('#services_dnsf_mask_id').val(false);
            $('#services_dnsf_mask_submit').val('Add mask');
            $('#services_dnsf_mask_form').dialog('option', 'title', 'Add new mask');
            $('#services_dnsf_mask_form').dialog('open');
        });

        //Handler for submitting the form
        $('#services_dnsf_mask_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/dnsforward.xml',
                form_id: 'services_dnsf_mask_form',
                error_element: $('#services_dnsf_mask_form_error'),
                successFn: function(json) {
                    gp.data.dnsf_masks[json.dnsmasq.hosts.host.id] = json.dnsmasq.hosts.host;
                    gp.services.dnsf.masks.buildTable();
                    $('#services_dnsf_mask_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_services_dnsf_mask').live('click', function() {
            var rule = gp.data.dnsf_masks[$(this).attr('rel')];
            gp.services.dnsf.masks.formLoadRule(rule);
            $('#services_dnsf_mask_submit').val('Edit mask');
            $('#services_dnsf_mask_form').dialog('option', 'title', 'Edit mask');
            $('#services_dnsf_mask_form').dialog('open');
        });

        //Click handler for deleting rule
        $('.delete_services_dnsf_mask').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this mask?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'DnsForward',
                    page: 'deletemask',
                    params: {
                        maskid: id
                    },
                    error_element: $('#services_dnsf_mask_table_error'),
                    content_id: 'cp_services_dnsf_masks',
                    successFn: function(json) {
                        delete gp.data.dnsf_masks[id];
                        gp.services.dnsf.masks.buildTable();
                    }
                });
            });
        });
    });
</script>
