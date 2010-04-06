<script type="text/javascript">
    gp.status.system.clickHandler = function() {
        gp.status.system.load();
    };

    gp.status.system.load = function() {
        $('#status_system_download').hide();
        //Handle XML loading
        gp.doAction({
            url: 'testxml/status_system.xml',
            module: 'System',
            page: 'getstatus',
            error_element: $('#status_system_error'),
            successFn: function(json) {
                json = json.system;
                $('#status_system_name').html(json.name);
                $('#status_system_currentversion').html(json.version.current);
                $('#status_system_latestversion').html(json.version.latest);
                if (json.version.current < json.version.latest) {
                    $('#status_system_download').slideDown(500);
                }
                $('#status_system_uptime').html(json.uptime);
                $('#status_system_cpu').html(json.cpu);
                $('#status_system_memory').html(json.memory);
                $('#status_system_hdd').html(json.harddisk);
            }
        });
    };
</script>
