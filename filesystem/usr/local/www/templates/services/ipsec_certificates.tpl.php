<h2>IPSec certificates</h2>

<p class="intro">This page allows you to read out your certificates and add new ones.</p>

<div class="form-error" id="services_ipsec_certif_table_error">
</div>

<table id="services_ipsec_certif_table">
    <thead>
        <tr>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="services_ipsec_certif_tbody">

    </tbody>
</table>


<p>
    <a class="icon_add" href="#services_ipsec" id="services_ipsec_certif_add_link">Add new certificate</a>
</p>

<form id="services_ipsec_certif_form" action="ajaxserver.php" method="post" class="dialog" title="Add new certificate">
    <div class="form-error" id="services_ipsec_certif_error">
    </div>

    <input type="hidden" name="module" value="Ipsec"/>
    <input type="hidden" name="page" value="addcertificate" id="services_ipsec_certif_form_page"/>
    <input type="hidden" name="services_ipsec_certif_id" value="" id="services_ipsec_certif_id"/>

    <dl>
        <dt><label for="services_ipsec_certif_descr">Description</label></dt>
        <dd>
            <input name="services_ipsec_certif_descr" type="text" size="40" id="services_ipsec_certif_descr"/>
        </dd>
        
        <dt><label for="services_ipsec_certif_private_certificate">Private key</label></dt>
        <dd>
            <input name="services_ipsec_certif_private_certificate" type="file" id="services_ipsec_certif_private_certificate"/>
        </dd>

        <dt><label for="services_ipsec_certif_public_certificate">Public certificate</label></dt>
        <dd>
            <input name="services_ipsec_certif_public_certificate" type="file" id="services_ipsec_certif_public_certificate"/>
        </dd>

        <dt><input type="submit" value="Add key" id="services_ipsec_certif_submit" class="submitbutton"/></dt>
    </dl>
</form>

<script type="text/javascript">
    gp.services.ipsec.certificates.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#services_ipsec_certif_tbody').empty();
        $.each(gp.data.ipsec_certificates, function(id, rule) {
            gp.services.ipsec.certificates.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.services.ipsec.certificates.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#services_ipsec" rel="'+rule.id+'" class="edit_ipsec_certificate" title="Edit certificate"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#services_ipsec" rel="'+rule.id+'" class="delete_ipsec_certificate" title="Delete certificate"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#services_ipsec_certif_tbody').append(tblstring);
    };

    gp.services.ipsec.certificates.resetForm = function() {
        gp.resetForm('services_ipsec_certif_form');
    };

    //Load a rule into outbound rules form
    gp.services.ipsec.certificates.formLoadRule = function(rule) {
        gp.services.ipsec.certificates.resetForm();
        $('#services_ipsec_certif_form_page').val('editcertificate');
        $('#services_ipsec_certif_id').val(rule.id);
        $('#services_ipsec_certif_submit').val('Edit certificate');
        $('#services_ipsec_certif_form').dialog('option', 'title', 'Edit certificate');

        $('#services_ipsec_certif_descr').val(rule.description);
    };

    $(function() {
        $('#services_ipsec_certif_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 600,
            modal: true
        });
        $('#services_ipsec_certif_add_link').click(function() {
            gp.services.ipsec.certificates.resetForm();
            $('#services_ipsec_certif_form_page').val('addcertificate');
            $('#services_ipsec_certif_id').val(false);
            $('#services_ipsec_certif_submit').val('Add certificate');
            $('#services_ipsec_certif_form').dialog('option', 'title', 'Add new certificate');
            $('#services_ipsec_certif_form').dialog('open');
        });

        //Handler for submitting the form
        $('#services_ipsec_certif_form').submit(function() {
            gp.doFormAction({
                form_id: 'services_ipsec_certif_form',
                error_element: $('#services_ipsec_certif_form_error'),
                successFn: function(json) {
                    gp.data.ipsec_certificates[json.ipsec.certificates.certificate.id] = json.certificates.certificate.key;
                    gp.services.ipsec.certificates.buildTable();
                    $('#services_ipsec_certif_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_ipsec_certificate').live('click', function() {
            var rule = gp.data.ipsec_certificates[$(this).attr('rel')];
            gp.services.ipsec.certificates.formLoadRule(rule);
            $('#services_ipsec_certif_submit').val('Edit certificate');
            $('#services_ipsec_certif_form').dialog('option', 'title', 'Edit certificate');
            $('#services_ipsec_certif_form').dialog('open');
        });

        //Click handler for deleting rule
        $('.delete_ipsec_certificate').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this certificate?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Ipsec',
                    page: 'deletecertificate',
                    params: {
                        keyid: id
                    },
                    error_element: $('#services_ipsec_certif_table_error'),
                    content_id: 'cp_services_proxy_certificates',
                    successFn: function(json) {
                        delete gp.data.ipsec_certificates[id];
                        gp.services.ipsec.certificates.buildTable();
                    }
                });
            });
        });
    });
</script>