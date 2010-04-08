<h2>Proxy ports</h2>

<div class="form-error" id="services_proxy_port_table_error">
</div>

<table id="services_proxy_port_table">
    <thead>
        <tr>
            <th>Port</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="services_proxy_port_tbody">

    </tbody>
</table>

<p>
    <a class="icon_add" href="#services_proxy" id="services_proxy_port_add_link">Add new port</a>
</p>

<form id="services_proxy_port_form" action="ajaxserver.php" method="post" class="dialog" title="Add new port">
    <div class="form-error" id="services_proxy_port_form_error">
    </div>

    <input type="hidden" name="module" value="Proxy"/>
    <input type="hidden" name="page" value="addport" id="services_proxy_port_form_page"/>
    <input type="hidden" name="services_proxy_port_id" value="" id="services_proxy_port_id"/>

    <dl>
        <?
        $this->port_id = 'services_proxy_port_port';
        $this->port_label = 'Port';
        include $this->template('forms/port.tpl.php');
        ?>
        
        <dt><input type="submit" value="Add port" id="services_proxy_port_submit" class="submitbutton"/></dt>
    </dl>
</form>

<div class="help_pool">
    <div class="help" id="help_services_proxy_port_port">The port to listen on</div>
</div>

<script type="text/javascript">
    //Build the rules table
    gp.services.proxy.ports.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#services_proxy_port_tbody').empty();
        $.each(gp.data.proxy_ports, function(id, rule) {
            gp.services.proxy.ports.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.services.proxy.ports.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td>'+rule.id+'</td>'+
            '<td><a href="#services_proxy" rel="'+rule.id+'" class="edit_proxy_port" title="Edit port"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#services_proxy" rel="'+rule.id+'" class="delete_proxy_port" title="Delete port"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#services_proxy_port_tbody').append(tblstring);
    };

    gp.services.proxy.ports.resetForm = function() {
        gp.resetForm('services_proxy_port_form');
    };

    //Load a rule into firewall rules form
    gp.services.proxy.ports.formLoadRule = function(rule) {
        gp.services.proxy.ports.resetForm();
        $('#services_proxy_port_form_page').val('editport');
        $('#services_proxy_port_id').val(rule.id);
        $('#services_proxy_port_submit').val('Edit port');
        $('#services_proxy_port_form').dialog('option', 'title', 'Edit port');

        <?
        $this->load_port_id = 'services_proxy_port_port';
        $this->load_port_jsvar = 'rule.port';
        include $this->template('forms/load_port_js.tpl.php');
        ?>
    };

    $(function() {
        $('#services_proxy_port_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 400,
            minHeight: 100,
            modal: true
        });

        //Click handler for adding
        $('#services_proxy_port_add_link').click(function() {
            gp.services.proxy.ports.resetForm();
            $('#services_proxy_port_form_page').val('addport');
            $('#services_proxy_port_id').val(false);
            $('#services_proxy_port_submit').val('Add port');
            $('#services_proxy_port_form').dialog('option', 'title', 'Add new port');
            $('#services_proxy_port_form').dialog('open');
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_proxy_port').live('click', function() {
            var rule = gp.data.proxy_ports[$(this).attr('rel')];
            gp.services.proxy.ports.formLoadRule(rule);
            $('#services_proxy_port_form').dialog('open');
        });

        //Click handler for deleting rule
        $('.delete_proxy_port').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this port?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Proxy',
                    page: 'deleteport',
                    params: {
                        portid: id
                    },
                    error_element: $('#services_proxy_port_table_error'),
                    content_id: 'cp_services_proxy_ports',
                    successFn: function(json) {
                        delete gp.data.proxy_ports[id];
                        gp.services.proxy.ports.buildTable();
                    }
                });
            });
        });

        //Handler for submitting the form
        $('#services_proxy_port_form').submit(function() {
            gp.doFormAction({
//                url: 'testxml/',
                form_id: 'services_proxy_port_form',
                error_element: $('#services_proxy_port_form_error'),
                successFn: function(json) {
                    gp.data.proxy_ports[json.proxy.ports.port.id] = json.proxy.ports.port;
                    gp.services.proxy.ports.buildTable();
                    $('#services_proxy_port_form').dialog('close');
                }
            });
            return false;
        });
    });
</script>