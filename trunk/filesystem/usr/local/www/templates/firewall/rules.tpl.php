<?
include $this->template('firewall/forms/rules.tpl.php');
?>
<script type="text/javascript">
    gp.firewall.rules.clickHandler = function() {
        gp.firewall.rules.load();
    };

    //XML Module: Firewall
    gp.firewall.rules.load = function() {
        // We use an array here because the ordering matters.
        gp.data.firewall_rules = [];

        //Handle XML loading
        gp.doAction({
            url: 'testxml/firewall.xml',
            module: 'Firewall',
            page: 'getconfig',
            error_element: [
                $('#firewall_rules_wan_table_error'),
                $('#firewall_rules_lan_table_error'),
                $('#firewall_rules_ext_table_error')
            ],
            successFn: function(json) {
                json = json.firewall;
                if (json.rule) {
                    if ($.isArray(json.rule)) {
                        //Multiple rules
                        $.each(json.rule, function(i, rule) {
                            gp.data.firewall_rules[rule.order] = rule;
                        });
                    } else {
                        //One rule
                        gp.data.firewall_rules[json.rule.order] = json.rule;
                    }
                }
                gp.firewall.rules.buildTable();
            }
        });
    };

    //Build the rules table
    gp.firewall.rules.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#firewall_rules_lan_tbody, #firewall_rules_wan_tbody, #firewall_rules_ext_tbody').empty();
        $.each(gp.data.firewall_rules, function(id, rule) {
            if (rule) {
                gp.firewall.rules.addRule(rule);
            }
        });
    };

    //Add a rule to the table
    gp.firewall.rules.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td><img src="images/icons/'+(rule.enable.toLowerCase()=='true'?'en':'dis')+'abled.png" alt="'+(rule.enable.toLowerCase()=='true'?'en':'dis')+'abled" title="Rule '+(rule.enable.toLowerCase()=='true'?'en':'dis')+'abled"/></td>'+
            '<td><img src="images/icons/'+rule.action.toLowerCase()+'.png" alt="'+rule.action+'" title="'+rule.action+'"/></td>'+
            '<td>'+(rule.log.toLowerCase() == 'enabled'?'<img src="images/icons/log.png" alt="Log" title="Logging enabled"/>':'')+'</td>'+
            '<td>'+rule.protocol.toUpperCase()+'</td>'+
            '<td>'+rule.source.type.toUpperCase()+'</td>'+
            '<td>'+rule.source.port+'</td>'+
            '<td>'+rule.destination.type.toUpperCase()+'</td>'+
            '<td>'+rule.destination.port+'</td>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#firewall_rules" rel="'+rule.order+'" class="promote_firewall_rule" title="Promote rule"><img src="images/icons/arrow_up.png" alt="promote"/></a></td>'+
            '<td><a href="#firewall_rules" rel="'+rule.order+'" class="demote_firewall_rule" title="Demote rule"><img src="images/icons/arrow_down.png" alt="demote"/></a></td>'+
            '<td><a href="#firewall_rules" rel="'+rule.order+'" class="toggle_firewall_rule" title="'+(rule.enable.toLowerCase()=='true'?'Enable':'Disable')+' rule"><img src="images/icons/rule_'+(rule.enable.toLowerCase()=='true'?'on':'off')+'.png" alt="delete"/></a></td>'+
            '<td><a href="#firewall_rules" rel="'+rule.order+'" class="edit_firewall_rule" title="Edit rule"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#firewall_rules" rel="'+rule.order+'" class="delete_firewall_rule" title="Delete rule"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#firewall_rules_'+rule['interface'].toLowerCase()+'_tbody').append(tblstring);
    };

    gp.firewall.rules.resetForm = function() {
        gp.resetForm('firewall_rules_form');
    };

    //Load a rule into firewall rules form
    gp.firewall.rules.formLoadRule = function(rule) {
        gp.firewall.rules.resetForm();
        $('#firewall_rules_form_page').val('editrule');
        $('#firewall_rules_id').val(rule.order);
        $('#firewall_rules_submit').val('Edit rule');
        $('#firewall_rules_form').dialog('option', 'title', 'Edit rule');
        
        $('#firewall_rules_interface').val(rule['interface'].toLowerCase());
        $('#firewall_rules_action').val(rule.action.toLowerCase());
        $('#firewall_rules_protocol').val(rule.protocol.toLowerCase());
        if (rule.icmp_type) {
            $('#firewall_rules_icmp_type').val(rule.icmp_type.toLowerCase());
        }

        $('#firewall_rules_src_not').attr('checked', rule.source.type.invert.toLowerCase() == 'true');
        $('#firewall_rules_src_type').val(rule.source.type.toLowerCase());
        $('#firewall_rules_src_address').val(rule.source.action);
        $('#firewall_rules_src_subnet').val(rule.source.subnet);

        <?
        $this->load_portrange_id = 'firewall_rules_srcport';
        $this->load_portrange_jsvar = 'rule.source.port';
        include $this->template('forms/load_portrange_js.tpl.php');
        ?>
        
        $('#firewall_rules_dest_not').attr('checked', rule.destination.type.invert.toLowerCase() == 'true');
        $('#firewall_rules_dest_type').val(rule.destination.type.toLowerCase());
        $('#firewall_rules_dest_address').val(rule.destination.action);
        $('#firewall_rules_dest_subnet').val(rule.destination.subnet);

        <?
        $this->load_portrange_id = 'firewall_rules_destport';
        $this->load_portrange_jsvar = 'rule.destination.port';
        include $this->template('forms/load_portrange_js.tpl.php');
        ?>
        
        $('#firewall_rules_fragments').attr('checked', rule.fragments.toLowerCase() == 'enabled');
        $('#firewall_rules_log').attr('checked', rule.log.toLowerCase() == 'enabled');
        $('#firewall_rules_descr').val(rule.description);
    };

    gp.firewall.rules.swapRules = function(id1, id2) {
        gp.doAction({
            url: 'testxml/reply.xml',
            module: 'Firewall',
            page: 'swaprule',
            params: {
                ruleid1: id1,
                ruleid2: id2
            },
            error_element: $('#firewall_rules_'+gp.data.firewall_rules[id1]['interface'].toLowerCase()+'_table_error'),
            successFn: function(json) {
                gp.data.firewall_rules[id1].order = id2;
                gp.data.firewall_rules[id2].order = id1;
                var temp = gp.data.firewall_rules[id1];
                gp.data.firewall_rules[id1] = gp.data.firewall_rules[id2];
                gp.data.firewall_rules[id2] = temp;
                gp.firewall.rules.buildTable();
            }
        });
    };

    $(function() {
        //Build firewall rules form
        $('#firewall_rules_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 800,
            modal: true
        });

        //Click handler for adding
        $('.firewall_rules_add_link').click(function() {
            gp.firewall.rules.resetForm();
            $('#firewall_rules_form_page').val('addrule');
            $('#firewall_rules_id').val(false);
            $('#firewall_rules_interface').val($(this).attr('rel'));
            $('#firewall_rules_submit').val('Add rule');
            $('#firewall_rules_form').dialog('option', 'title', 'Add new rule');
            $('#firewall_rules_form').dialog('open');
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_firewall_rule').live('click', function() {
            var rule = gp.data.firewall_rules[$(this).attr('rel')];
            gp.firewall.rules.formLoadRule(rule);
            $('#firewall_rules_form').dialog('open');
        });

        //Click handler for toggling rule on/off
        $('.toggle_firewall_rule').live('click', function() {
            var id = $(this).attr('rel');
            gp.doAction({
                url: 'testxml/reply.xml',
                module: 'Firewall',
                page: 'togglerule',
                params: {
                    ruleid: id
                },
                error_element: $('#firewall_rules_'+gp.data.firewall_rules[id]['interface'].toLowerCase()+'_table_error'),
                successFn: function(json) {
                    var enabled = gp.data.firewall_rules[id].enable;
                    gp.data.firewall_rules[id].enable = (enabled == 'true' ? 'false' : 'true');
                    gp.firewall.rules.buildTable();
                }
            });
        });

        //Click handler for deleting rule
        $('.delete_firewall_rule').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this rule?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Firewall',
                    page: 'deleterule',
                    params: {
                        ruleid: id
                    },
                    error_element: $('#firewall_rules_'+gp.data.firewall_rules[id]['interface'].toLowerCase()+'_table_error'),
                    successFn: function(json) {
                        delete gp.data.firewall_rules[id];
                        gp.firewall.rules.buildTable();
                    }
                });
            });
        });

        //Click handler for promoting rule
        $('.promote_firewall_rule').live('click', function() {
            var id1 = $(this).attr('rel');
            var id2 = false;
            var cur_rule = gp.data.firewall_rules[id1];
            $.each(gp.data.firewall_rules, function(i, rule) {
                if (rule && cur_rule['interface'].toLowerCase() == rule['interface'].toLowerCase()) {
                    if (rule.order < id1 && (!id2 || rule.order > id2)) {
                        id2 = rule.order;
                    }
                }
            });
            if (!id2) {
                gp.displayError('Cannot move this rule any higher.', 'An exception occurred', $('#firewall_rules_'+cur_rule['interface'].toLowerCase()+'_table_error'));
                return;
            }
            gp.firewall.rules.swapRules(id1, id2);
        });

        //Click handler for demoting rule
        $('.demote_firewall_rule').live('click', function() {
            var id1 = $(this).attr('rel');
            var id2 = false;
            var cur_rule = gp.data.firewall_rules[id1];
            $.each(gp.data.firewall_rules, function(i, rule) {
                if (rule && cur_rule['interface'].toLowerCase() == rule['interface'].toLowerCase()) {
                    if (rule.order > id1 && (!id2 || rule.order < id2)) {
                        id2 = rule.order;
                    }
                }
            });
            if (!id2) {
                gp.displayError('Cannot move this rule any lower.', 'An exception occurred', $('#firewall_rules_'+cur_rule['interface'].toLowerCase()+'_table_error'));
                return;
            }
            gp.firewall.rules.swapRules(id1, id2);
        });

        //Handler for submitting the form
        $('#firewall_rules_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/formerror.xml',
                form_id: 'firewall_rules_form',
                error_element: $('#firewall_rules_form_error'),
                successFn: function(json) {
                    gp.data.firewall_rules[json.firewall.rule.order] = json.firewall.rule;
                    gp.firewall.rules.buildTable();
                    $('#firewall_rules_form').dialog('close');
                }
            });
            return false;
        });

        //Input in formulier disablen als ICMP niet is gekozen
        if ($('#firewall_rules_protocol').val() != 'icmp') {
            $('#firewall_rules_icmp_type').attr('disabled', 'disabled');
        }

        $('#firewall_rules_protocol').change(function() {
            if (this.value == 'icmp') {
                $('#firewall_rules_icmp_type').removeAttr('disabled');
            } else {
                $('#firewall_rules_icmp_type').attr('disabled', 'disabled');
            }
        });
    });
</script>
