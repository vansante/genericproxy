<script type="text/javascript">
    gp.interfaces.assign.clickHandler = function() {
        gp.interfaces.assign.load();
    };

    gp.interfaces.assign.load = function() {
        gp.data.interface_list = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/getinterfaces.xml',
            module: 'AssignInterfaces',
            page: 'getinterfaces',
            error_element: $('#interfaces_assign_form_error'),
            content_id: 'cp_interfaces_assign_assign',
            successFn: function(json) {
                gp.data.interface_list = json['interface'];
                gp.interfaces.assign.loadForm();
            }
        });
    };
    
    gp.interfaces.assign.loadForm = function() {
        var data = gp.data.interface_list;
        gp.interfaces.assign.isResetting = true;
        gp.resetForm('interfaces_assign_form');
        gp.interfaces.assign.isResetting = false;

        var options = {};
        $.each(data, function(i, iface){
            options[iface.name] = iface.name;
        });

        var selects = $('#interfaces_assign_lan, #interfaces_assign_wan, #interfaces_assign_ext');
        selects.empty();
        
        $.each(selects, function(i, select) {
            $.each(data, function(i, iface){
                var option = $('<option></option>').val(iface.name).html(iface.name);
                select = $(select);
                if (iface.current && select.attr('id') == ('interfaces_assign_'+iface.current.toLowerCase())) {
                    option.attr('selected', 'selected');
                }
                select.append(option);
            });
        });
    };
    
    $(function() {
        //XML Module: AssignInterfaces
        $('#interfaces_assign_form').submit(function() {
            gp.doFormAction({
                form_id: 'interfaces_assign_form',
                error_element: $('#interfaces_assign_form_error'),
                successFn: function(json) {
                    gp.data.interface_list = json['interface'];
                    gp.interfaces.assign.loadForm();
                }
            });
            return false;
        });

        $('#interfaces_assign_form select').change(function() {
            if (gp.interfaces.assign.isResetting) {
                return;
            }
            var assigned = {};
            var lan = $('#interfaces_assign_lan').val();
            var wan = $('#interfaces_assign_wan').val();
            var ext = $('#interfaces_assign_ext').val();
            var error = false;

            assigned[lan] = true;
            if (wan && assigned[wan]) {
                error = "The device '"+wan+"' is assigned to more than one interface."
            } else if (wan) {
                assigned[wan] = true;
            }

            if (ext && assigned[ext]) {
                error = "The device '"+ext+"' is assigned to more than one interface."
            } else if (ext) {
                assigned[ext] = true;
            }
            
            if (error) {
                $('#interfaces_assign_submit').attr('disabled', 'disabled');
                gp.displayError(error, 'Exception', $('#interfaces_assign_form_error'));
            } else {
                $('#interfaces_assign_form_error').fadeOut(500);
                $('#interfaces_assign_submit').removeAttr('disabled');
            }
        });
    });
</script>