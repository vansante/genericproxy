<script type="text/javascript">
    gp.system.upgrade.clickHandler = function() {
        gp.system.upgrade.load();
    };

    gp.system.upgrade.load = function() {
        if (!gp.data.new_release && !gp.data.no_release) {
            gp.doAction({
                url: 'testxml/update.xml',
                module: 'Update',
                page: 'check',
                content_id: 'cp_system_upgrade_auto',
                successFn: function(json) {
                    if (json.release) {
                        gp.data.new_release = json.release;
                    } else {
                        gp.data.no_release = true;
                    }
                    gp.system.upgrade.auto.loadForm();
                }
            });
        } else {
            gp.system.upgrade.auto.loadForm();
        }
    };
</script>