<script type="text/javascript">
    gp.services.sharing.clickHandler = function() {
        gp.services.sharing.load();
    };

    //XML Module: Sharing
    gp.services.sharing.load = function() {
        gp.data.sharing = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/sharing.xml',
            module: 'Scheduler',
            page: 'getconfig',
            error_element: $('#services_sharing_form_error'),
            successFn: function(json) {
                gp.data.sharing = json.sharing;
                gp.services.sharing.loadForm();
            }
        });
    };

    gp.services.sharing.resetForm = function() {
        gp.resetForm('services_sharing_form');
    };

    gp.services.sharing.loadForm = function() {
        var data = gp.data.sharing;
        gp.resetForm('services_sharing_form');

        gp.services.sharing.resetForm();

        $('#services_sharing_download').val(data.maxdownspeed);
        $('#services_sharing_upload').val(data.maxupspeed);

        $('#services_sharing_standard_download_speed').val(data.standard.downspeed).trigger('change');
        $('#services_sharing_standard_upload_speed').val(data.standard.upspeed).trigger('change');
        $('#services_sharing_optional_download_speed').val(data.optional.downspeed).trigger('change');
        $('#services_sharing_optional_upload_speed').val(data.optional.upspeed).trigger('change');

        gp.services.sharing.loadDefinedSchedules();
        gp.services.sharing.loadSchedule(data.schedule);
    };

    gp.services.sharing.loadDefinedSchedules = function() {
        var data = gp.data.sharing;
        
        var options = '<option value=""> -- Select configuration -- </option>';

        options += '<optgroup label="Saved configurations">';
        if ($.isArray(data.userdefined)) {
            $.each(data.userdefined, function(i, usrdef){
                options += '<option value="usrdef_'+usrdef.name+'">'+usrdef.name+'</option>';
            });
        } else if (data.userdefined) {
            options += '<option value="usrdef_'+data.userdefined.name+'">'+data.userdefined.name+'</option>';
        }
        options += '</optgroup>';

        options += '<optgroup label="Predefined configurations">';
        if ($.isArray(data.predefined)) {
            $.each(data.predefined, function(i, predef){
                options += '<option value="predef_'+predef.name+'">'+predef.name+'</option>';
            });
        } else if (data.predefined) {
            options += '<option value="predef_'+data.predefined.name+'">'+data.predefined.name+'</option>';
        }
        options += '</optgroup>';

        $('#services_sharing_schedule_configs').html(options);
    };

    gp.services.sharing.resetSchedule = function(reset_classes) {
        if (reset_classes) {
            $('#services_sharing_schedule_table .services_sharing_td').attr('class', 'services_sharing_td services_sharing_off');
        }
        gp.data.sharing_schedule = [];

        for (var day = 0; day < 7; day++) {
            gp.data.sharing_schedule[day] = [];
            for (var hour = 0; hour < 24; hour++) {
                gp.data.sharing_schedule[day][hour] = 0;
            }
        }
    };

    gp.services.sharing.loadSchedule = function(sched) {
        gp.services.sharing.resetSchedule(true);
        $.each(sched.day, function(i, day) {
            for (var hour = 0; hour < 24; hour++) {
                if (day['h'+hour]) {
                    gp.data.sharing_schedule[day.id][hour] = parseInt(day['h'+hour]);
                    if (day['h'+hour] == 1) {
                        $('#services_sharing_schedule_table_'+day.id+'_'+hour).attr('class', 'services_sharing_td services_sharing_standard');
                    } else if (day['h'+hour] == 2) {
                        $('#services_sharing_schedule_table_'+day.id+'_'+hour).attr('class', 'services_sharing_td services_sharing_limited');
                    }
                }
            }
        });
    };

    gp.services.sharing.resetConfigForm = function() {
        gp.resetForm('services_sharing_config_form');
    };

    gp.services.sharing.loadConfigForm = function() {
        gp.services.sharing.resetConfigForm();

        var val = $('#services_sharing_schedule_configs').val();
        if (val) {
            $('#services_sharing_config_name').val(val.substr(7));
        }
    };

    $(function() {
        //Sliders
        $(".slider").each(function(i, select) {
            var select = $(select);
            select.css('float', 'right').css('width', '120px');

            var slider = $('<div id="'+select.attr('id')+'"_slider" style="float:left; margin: 3px 0 0 5px; width: 305px;"></div>').insertAfter(select).slider({
                min: 0,
                max: 10,
                range: 'min',
                value: select[0].selectedIndex,
                animate: true,
                slide: function(event, ui) {
                    select[0].selectedIndex = ui.value;
                }
            });
            select.change(function() {
                slider.slider("value", this.selectedIndex);
            });
        });

        gp.services.sharing.resetSchedule();

        $('#services_sharing_form').submit(function() {
            var days = [];
            $.each(gp.data.sharing_schedule, function(i, day){
                days.push(day.join(','));
            });
            $('#services_sharing_schedule').val(days.join(':'));
            gp.doFormAction({
                url: 'testxml/sharing.xml',
                form_id: 'services_sharing_form',
                error_element: $('#services_sharing_form_error'),
                successFn: function(json) {
                    gp.data.sharing = json.sharing;
                    gp.services.sharing.loadForm();
                }
            });
            return false;
        });

        $('#services_sharing_schedule_configs').change(function(){
            if (!this.value) {
                return;
            }
            var data = gp.data.sharing;
            var val = this.value.substr(7);
            var type = this.value.substr(0, 6);
            
            if (type == 'predef') {
                if ($.isArray(data.predefined)) {
                    $.each(data.predefined, function(i, predef){
                        if (predef.name == val) {
                            gp.services.sharing.loadSchedule(predef);
                        }
                    });
                } else {
                    gp.services.sharing.loadSchedule(data.predefined);
                }
            } else if (type == 'usrdef') {
                if ($.isArray(data.userdefined)) {
                    $.each(data.userdefined, function(i, usrdef){
                        if (usrdef.name == val) {
                            gp.services.sharing.loadSchedule(usrdef);
                        }
                    });
                } else {
                    gp.services.sharing.loadSchedule(data.userdefined);
                }
            }
        });

        $('#services_sharing_config_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 500,
            minHeight: 100,
            modal: true
        });

        $('#services_sharing_save_config_link').click(function(){
            gp.services.sharing.loadConfigForm();

            var days = [];
            $.each(gp.data.sharing_schedule, function(i, day){
                days.push(day.join(','));
            });
            days = days.join(':');
            $('#services_sharing_config_schedule').val(days);
            $('#services_sharing_config_form').dialog('open');
        });

        $('#services_sharing_delete_config_link').click(function(){
            var name = $('#services_sharing_schedule_configs').val().substr(7);
            var type = $('#services_sharing_schedule_configs').val().substr(0, 6);

            if (!type) {
                gp.alert("Exception", "Select a saved configuration first.");
                return false;
            }
            if (type == 'predef') {
                gp.alert("Exception", "Cannot delete a predefined configuration.");
                return false;
            }
            gp.confirm("Are you sure?", "Are you sure you want to delete this mask?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Scheduler',
                    page: 'deleteconfig',
                    params: {
                        name: name
                    },
                    error_element: $('#services_sharing_form_error'),
                    successFn: function(json) {
                        if ($.isArray(gp.data.sharing.userdefined)) {
                            var id;
                            $.each(gp.data.sharing.userdefined, function(i, usrdef){
                                if (usrdef.name == name) {
                                    id = i;
                                }
                            });
                            gp.data.sharing.userdefined.splice(id, 1);
                        } else {
                            gp.data.sharing.userdefined = null;
                        }
                        gp.services.sharing.loadForm();
                    }
                });
            });
        });

        $('#services_sharing_config_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/reply.xml',
                form_id: 'services_sharing_config_form',
                error_element: $('#services_sharing_config_form_error'),
                successFn: function(json) {
                    if ($.isArray(gp.data.sharing.userdefined)) {
                        gp.data.sharing.userdefined.push(json.sharing.userdefined);
                    } else if (gp.data.sharing.userdefined) {
                        gp.data.sharing.userdefined = [
                            json.sharing.userdefined,
                            gp.data.sharing.userdefined
                        ];
                    } else {
                        gp.data.sharing.userdefined = [json.sharing.userdefined];
                    }
                    gp.services.sharing.loadForm();
                }
            });
            return false;
        });

        //Make calendar head
        var thead = new Array(74);
        var i = 0;
        thead[i++] = '<tr><th>Hour:</th>';
        for (var hour = 0; hour < 24; hour++) {
            thead[i++] = '<td>';
            thead[i++] = hour;
            thead[i++] = '</td>';
        }
        thead[i++] = '</tr>'
        $('#services_sharing_schedule_table thead').html(thead.join(''));

        //Weekdays
        var days = ['Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.', 'Sun.'];

        //Make calendar body
        var tbody = new Array(868);
        i = 0;
        for (var day = 0; day < 7; day++) {
            tbody[i++] = '<tr><th>';
            tbody[i++] = days[day];
            tbody[i++] = '</th>';
            for (var hour = 0; hour < 24; hour++) {
                tbody[i++] = '<td id="services_sharing_schedule_table_';
                tbody[i++] = day;
                tbody[i++] = '_';
                tbody[i++] = hour;
                tbody[i++] = '" class="services_sharing_td"></td>';
            }
            tbody[i++] = '</tr>';
        }

        //Is muisje beneden?
        var mousedown = false;
        var draggingType = 0;
        document.body.onmousedown = function() {
            mousedown = true;
        }
        document.body.onmouseup = function() {
            mousedown = false;
        }

        //Calendar click handler
        $('#services_sharing_schedule_table tbody').html(tbody.join(''));

        $('#services_sharing_schedule_table tbody td').mousedown(function() {
            var arr =  $(this).attr('id').split('_');
            var day = arr[arr.length - 2];
            var hour = arr[arr.length - 1];

            $('#services_sharing_schedule_configs').val('');

            //Update data en add klass
            if (gp.data.sharing_schedule[day][hour] == 0) {
                $(this).attr('class', 'services_sharing_td services_sharing_standard');
                gp.data.sharing_schedule[day][hour] = 1;
                draggingType = 0;
            } else if (gp.data.sharing_schedule[day][hour] == 1) {
                $(this).attr('class', 'services_sharing_td services_sharing_limited');
                gp.data.sharing_schedule[day][hour] = 2;
                draggingType = 1;
            } else if (gp.data.sharing_schedule[day][hour] == 2) {
                $(this).attr('class', 'services_sharing_td services_sharing_off');
                gp.data.sharing_schedule[day][hour] = 0;
                draggingType = 2;
            }
        }).mouseover(function() {
            if (mousedown) {
                var arr = $(this).attr('id').split('_');
                var day = arr[arr.length - 2];
                var hour = arr[arr.length - 1];

                if (draggingType == 0) {
                    $(this).attr('class', 'services_sharing_td services_sharing_standard');
                    gp.data.sharing_schedule[day][hour] = 1;
                } else if (draggingType == 1) {
                    $(this).attr('class', 'services_sharing_td services_sharing_limited');
                    gp.data.sharing_schedule[day][hour] = 2;
                } else if (draggingType == 2) {
                    $(this).attr('class', 'services_sharing_td services_sharing_off');
                    gp.data.sharing_schedule[day][hour] = 0;
                }
            }
        }).mouseup(function(){
            // Remove current selection.
            if (document.selection && document.selection.empty) {
                document.selection.empty() ;
            } else if(window.getSelection) {
                var sel = window.getSelection();
                if (sel && sel.removeAllRanges) {
                    sel.removeAllRanges();
                }
            }
        });
    });
</script>