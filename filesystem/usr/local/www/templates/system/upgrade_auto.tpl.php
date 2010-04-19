<h2>Automatic firmware upgrade</h2>

<div class="warning" id="system_upgrade_auto_warning">
    <h3>Warning:</h3>
    <p>Do NOT abort the firmware upgrade once it has started. The appliance will reboot automatically after storing the new firmware. The configuration will be maintained.</p>
</div>

<div class="note" id="system_upgrade_auto_no_update">
    <h3>Note</h3>
    <p>There are no new releases.</p>
</div>

<form id="system_upgrade_auto_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="system_upgrade_auto_form_error">
    </div>

    <input type="hidden" name="module" value="Update"/>
    <input type="hidden" name="page" value="updatefirmware" id="system_upgrade_auto_form_page"/>

    <dl>
        <dt>New version</dt>
        <dd>
            <span id="system_upgrade_auto_version"></span>
        </dd>

        <dt>Issue date</dt>
        <dd>
            <span id="system_upgrade_auto_date"></span>
        </dd>

        <dt>Filename</dt>
        <dd>
            <span id="system_upgrade_auto_filename"></span>
        </dd>
    </dl>
    
    <p style="clear: both;"></p>

    <h3>Changelog</h3>
    <div class="release-changelog" id="system_upgrade_auto_changelog"></div>

    <dl>
        <dt><input type="submit" value="Upgrade firmware" id="system_upgrade_auto_submit" class="submitbutton"/></dt>
    </dl>

    <p style="clear: both;"></p>
</form>

<script type="text/javascript">
    gp.system.upgrade.auto.loadForm = function() {
        if (gp.data.no_release) {
            $('#system_upgrade_auto_no_update').slideDown(500);
        } else {
            var rls = gp.data.new_release;
            $('#system_upgrade_auto_version').html(rls.version);
            $('#system_upgrade_auto_date').html(rls.date);
            $('#system_upgrade_auto_filename').html(rls.filename);
            $('#system_upgrade_auto_changelog').html('<pre>'+rls.changelog+'</pre>');

            $('#system_upgrade_auto_warning, #system_upgrade_auto_form').slideDown(500);
        }
    };

    $(function() {
        $('#system_upgrade_auto_form').submit(function(){
            gp.confirm("Are you sure?", "Are you sure you want to upgrade the devices firmware?", function() {
                gp.doFormAction({
                    url: 'testxml/reply.xml',
                    form_id: 'system_upgrade_auto_form',
                    error_element: $('#system_upgrade_auto_form_error'),
                    successFn: function(json) {
                        gp.rebootNotice(90);
                    }
                });
            });
            return false;
        });
    });
</script>