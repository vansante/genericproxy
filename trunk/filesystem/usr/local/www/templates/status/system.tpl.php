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
                $('#status_system_version').html(json.version);
                $('#status_system_uptime').html(json.uptime);
                $('#status_system_cpu_1').html(json.cpu.avg1);
                $('#status_system_cpu_5').html(json.cpu.avg5);
                $('#status_system_cpu_15').html(json.cpu.avg15);
                $('#status_system_memory_total').html(json.memory.total);
                $('#status_system_memory_inuse').html(json.memory.used);
            }
        });
    };
</script>
