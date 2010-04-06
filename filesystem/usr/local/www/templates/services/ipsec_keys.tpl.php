<h2>IPSec keys</h2>

<p class="intro">This page allows you to read out your pre-shared keys and add new ones.</p>

<div class="form-error" id="services_ipsec_key_table_error">
</div>

<table id="services_ipsec_key_table">
    <thead>
        <tr>
            <th>Description</th>
            <th>Pre-shared key</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="services_ipsec_key_tbody">

    </tbody>
</table>


<p>
    <a class="icon_add" href="#services_ipsec" id="services_ipsec_key_add_link">Add new key</a>
</p>

<form id="services_ipsec_key_form" action="ajaxserver.php" method="post" class="dialog" title="Add new key">
    <div class="form-error" id="services_ipsec_key_error">
    </div>

    <input type="hidden" name="module" value="Ipsec"/>
    <input type="hidden" name="page" value="addkey" id="services_ipsec_key_form_page"/>
    <input type="hidden" name="services_ipsec_key_id" value="" id="services_ipsec_key_id"/>

    <dl>
        <dt><label for="services_ipsec_key_descr">Description</label></dt>
        <dd>
            <input name="services_ipsec_key_descr" type="text" size="40" id="services_ipsec_key_descr"/>
        </dd>

        <dt><label for="services_ipsec_key_pskey">Pre-shared key</label></dt>
        <dd>
            <input name="services_ipsec_key_pskey" type="text" size="70" id="services_ipsec_key_pskey" value="true"/>
        </dd>

        <dt><input type="submit" value="Add key" id="services_ipsec_key_submit" class="submitbutton"/></dt>
    </dl>
</form>

<script type="text/javascript">
    gp.services.ipsec.keys.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#services_ipsec_key_tbody').empty();
        $.each(gp.data.ipsec_keys, function(id, rule) {
            gp.services.ipsec.keys.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.services.ipsec.keys.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule.description+'</td>'+
            '<td>'+rule.content+'</td>'+
            '<td><a href="#services_ipsec" rel="'+rule.id+'" class="edit_ipsec_key" title="Edit key"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#services_ipsec" rel="'+rule.id+'" class="delete_ipsec_key" title="Delete key"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#services_ipsec_key_tbody').append(tblstring);
    };

    gp.services.ipsec.keys.resetForm = function() {
        gp.resetForm('services_ipsec_key_form');
    };

    //Load a rule into outbound rules form
    gp.services.ipsec.keys.formLoadRule = function(rule) {
        gp.services.ipsec.keys.resetForm();
        $('#services_ipsec_key_form_page').val('editkey');
        $('#services_ipsec_key_id').val(rule.id);
        $('#services_ipsec_key_submit').val('Edit key');
        $('#services_ipsec_key_form').dialog('option', 'title', 'Edit key');

        $('#services_ipsec_key_descr').val(rule.description);
        $('#services_ipsec_key_pskey').val(rule.content);
    };

    $(function() {
        $('#services_ipsec_key_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 860,
            modal: true
        });
        $('#services_ipsec_key_add_link').click(function() {
            gp.services.ipsec.keys.resetForm();
            $('#services_ipsec_key_form_page').val('addkey');
            $('#services_ipsec_key_id').val(false);
            $('#services_ipsec_key_submit').val('Add key');
            $('#services_ipsec_key_form').dialog('option', 'title', 'Add new key');
            $('#services_ipsec_key_form').dialog('open');
        });

        //Handler for submitting the form
        $('#services_ipsec_key_form').submit(function() {
            gp.doFormAction({
                form_id: 'services_ipsec_key_form',
                error_element: $('#services_ipsec_key_form_error'),
                successFn: function(json) {
                    gp.data.ipsec_keys[json.ipsec.keys.key.id] = json.ipsec.keys.key;
                    gp.services.ipsec.keys.buildTable();
                    $('#services_ipsec_key_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_ipsec_key').live('click', function() {
            var rule = gp.data.ipsec_keys[$(this).attr('rel')];
            gp.services.ipsec.keys.formLoadRule(rule);
            $('#services_ipsec_key_submit').val('Edit key');
            $('#services_ipsec_key_form').dialog('option', 'title', 'Edit key');
            $('#services_ipsec_key_form').dialog('open');
        });

        //Click handler for deleting rule
        $('.delete_ipsec_key').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this key?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Ipsec',
                    page: 'deletekey',
                    params: {
                        keyid: id
                    },
                    error_element: $('#services_ipsec_key_table_error'),
                    successFn: function(json) {
                        delete gp.data.ipsec_keys[id];
                        gp.services.ipsec.keys.buildTable();
                    }
                });
            });
        });
    });
</script>