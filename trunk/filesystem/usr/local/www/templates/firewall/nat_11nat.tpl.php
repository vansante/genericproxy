<h2>1:1 NAT rules</h2>

<p class="intro">1:1 NAT maps one public IP address to one private IP address by specifying a /32 subnet. This means having an otherwise local network computer accessible from the Internet through the WAN interface of your device.</p>

<div class="form-error" id="firewall_nat_11nat_table_error">
</div>

<table id="firewall_nat_11nat_table">
    <thead>
        <tr>
            <th>Interface</th>
            <th>External IP</th>
            <th>Internal IP</th>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="firewall_nat_11nat_tbody">

    </tbody>
</table>


<p>
    <a class="icon_add" href="#firewall_nat" id="firewall_nat_11nat_add_link">Add new rule</a>
    <a class="icon_add firewall_nat_apply_link" href="#firewall_nat" rel="11nat">Apply changes</a>
</p>

<form id="firewall_nat_11nat_form" action="ajaxserver.php" method="post" class="dialog" title="Add new rule">
    <div class="form-error" id="firewall_nat_11nat_form_error">
    </div>

    <input type="hidden" name="module" value="Nat"/>
    <input type="hidden" name="page" value="add_11nat_rule" id="firewall_nat_11nat_form_page"/>
    <input type="hidden" name="firewall_nat_11nat_id" value="" id="firewall_nat_11nat_id"/>

    <dl>
        <dt><label for="firewall_nat_11nat_interface">Interface</label></dt>
        <dd>
            <select name="firewall_nat_11nat_interface" id="firewall_nat_11nat_interface">
                <option value="wan">WAN</option>
                <option value="ext">EXT</option>
            </select>
        </dd>

        <dt><label for="firewall_nat_11nat_ext_address">External subnet</label></dt>
        <dd>
            <input name="firewall_nat_11nat_ext_address" type="text" size="12" id="firewall_nat_11nat_ext_address"/>
            /
            <select name="firewall_nat_11nat_ext_subnet" id="firewall_nat_11nat_ext_subnet">
            <? for ($i = 32; $i >= 0; $i--) : ?>
                <option value="<?=$i?>"><?=$i?></option>
            <? endfor; ?>
            </select>
        </dd>

        <dt><label for="firewall_nat_11nat_int_address">Internal subnet</label></dt>
        <dd>
            <input name="firewall_nat_11nat_int_address" type="text" size="12" id="firewall_nat_11nat_int_address"/>
        </dd>

        <dt><label for="firewall_nat_11nat_descr">Description</label></dt>
        <dd>
            <input name="firewall_nat_11nat_descr" type="text" size="40" id="firewall_nat_11nat_descr"/>
        </dd>

        <dt><input type="submit" value="Add rule" id="firewall_nat_11nat_submit" class="submitbutton"/></dt>
    </dl>
</form>

<div class="help_pool">
    <div class="help" id="help_firewall_nat_11nat_interface">Choose which interface this rule applies to.<br>Hint: in most cases, you'll want to use WAN here.</div>
    <div class="help" id="help_firewall_nat_11nat_ext_address">Enter the external (WAN) subnet for the 1:1 mapping. You may map single IP addresses by specifying a /32 subnet.</div>
    <div class="help" id="help_firewall_nat_11nat_int_address">Enter the internal (LAN) subnet for the 1:1 mapping. The subnet size specified for the external subnet also applies to the internal subnet (they have to be the same).</div>
    <div class="help" id="help_firewall_nat_11nat_descr">You may enter a description here for your reference</div>
</div>

<script type="text/javascript">
    gp.firewall.nat['11nat'].buildTable = function() {
        //Clear the current table data to (re)load it
        $('#firewall_nat_11nat_tbody').empty();
        $.each(gp.data.nat_11nat_rules, function(id, rule) {
            gp.firewall.nat['11nat'].addRule(rule);
        });
    };

    //Add a rule to the table
    gp.firewall.nat['11nat'].addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule['interface'].toUpperCase()+'</td>'+
            '<td>'+rule.external+'</td>'+
            '<td>'+rule.internal+' / '+rule.subnet+'</td>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#firewall_nat" rel="'+rule.id+'" class="edit_firewall_nat_11nat_rule" title="Edit rule"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#firewall_nat" rel="'+rule.id+'" class="delete_firewall_nat_11nat_rule" title="Delete rule"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#firewall_nat_11nat_tbody').append(tblstring);
    };

    gp.firewall.nat.outbound.resetForm = function() {
        gp.resetForm('firewall_nat_outbound_form');
    };

    //Load a rule into 11nat rules form
    gp.firewall.nat['11nat'].formLoadRule = function(rule) {
        gp.firewall.nat.outbound.resetForm();
        $('#firewall_nat_11nat_form_page').val('edit_11nat_rule');
        $('#firewall_nat_11nat_id').val(rule.id);
        $('#firewall_nat_11nat_submit').val('Edit rule');
        $('#firewall_nat_11nat_form').dialog('option', 'title', 'Edit rule');

        $('#firewall_nat_11nat_interface').val(rule['interface'].toLowerCase());
        $('#firewall_nat_11nat_ext_address').val(rule.external);
        $('#firewall_nat_11nat_ext_subnet').val(rule.subnet);
        $('#firewall_nat_11nat_int_address').val(rule.internal);
        $('#firewall_nat_11nat_descr').val(rule.description);
    };

    $(function() {
        $('#firewall_nat_11nat_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 630,
            modal: true
        });

        $('#firewall_nat_11nat_add_link').click(function() {
            gp.firewall.nat.outbound.resetForm();
            $('#firewall_nat_11nat_form_page').val('add_11nat_rule');
            $('#firewall_nat_11nat_id').val(false);
            $('#firewall_nat_11nat_submit').val('Add rule');
            $('#firewall_nat_11nat_form').dialog('option', 'title', 'Add new rule');
            $('#firewall_nat_11nat_form').dialog('open');
            return false;
        });

        //Handler for submitting the form
        $('#firewall_nat_11nat_form').submit(function() {
            gp.doFormAction({
                form_id: 'firewall_nat_11nat_form',
                error_element: $('#firewall_nat_11nat_form_error'),
                successFn: function(json) {
                    gp.data.nat_11nat_rules[json.nat.onetoone.rule.id] = json.nat.onetoone.rule;
                    gp.firewall.nat['11nat'].buildTable();
                    $('#firewall_nat_11nat_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_firewall_nat_11nat_rule').live('click', function() {
            var rule = gp.data.nat_11nat_rules[$(this).attr('rel')];
            gp.firewall.nat['11nat'].formLoadRule(rule);
            $('#firewall_nat_11nat_submit').val('Edit rule');
            $('#firewall_nat_11nat_form').dialog('option', 'title', 'Edit rule');
            $('#firewall_nat_11nat_form').dialog('open');
            return false;
        });

        //Click handler for deleting rule
        $('.delete_firewall_nat_11nat_rule').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this rule?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Nat',
                    page: 'delete_11nat_rule',
                    params: {
                        ruleid: id
                    },
                    error_element: $('#firewall_nat_11nat_table_error'),
                    content_id: 'cp_firewall_nat_11nat',
                    successFn: function(json) {
                        delete gp.data.nat_11nat_rules[id];
                        gp.firewall.nat['11nat'].buildTable();
                    }
                });
            });
            return false;
        });
    });
</script>