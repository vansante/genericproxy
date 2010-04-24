<h2>Automatic firmware upgrade</h2>

<p class="intro">If there is a firmware upgrade it will be shown here. You can check for upgrades by pressing the check button. If there is one you can then view the changelog and decide whether to upgrade or not.</p>

<div class="form-error" id="system_upgrade_auto_error">
</div>

<p>
    <a class="icon_add" id="system_upgrade_auto_check_link" href="#system_upgrade">Check for updates</a>
</p>

<div class="note" id="system_upgrade_auto_no_update">
    <h3>Note</h3>
    <p>There are no new releases.</p>
</div>

<div class="warning" id="system_upgrade_auto_warning">
    <h3>Warning:</h3>
    <p>Do NOT abort the firmware upgrade once it has started. The appliance will reboot automatically after storing the new firmware. The configuration will be maintained.</p>
</div>

<form id="system_upgrade_auto_form" action="ajaxserver.php" method="post">
    <input type="hidden" name="module" value="Update"/>
    <input type="hidden" name="page" value="updatefirmware" id="system_upgrade_auto_form_page"/>

    <div class="form-error" id="system_upgrade_auto_form_error">
    </div>

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
    gp.system.upgrade.auto.checkUpdates = function(auto_check) {
        gp.system.upgrade.auto.resetForm();
        gp.doAction({
            url: 'testxml/update.xml',
            module: 'Update',
            page: 'check',
            error_element: $('#system_upgrade_auto_error'),
            content_id: 'cp_system_upgrade_auto',
            successFn: function(json) {
                if (json.release) {
                    gp.data.new_release = json.release;
                    
                    if (auto_check && self.document.location.hash != '#system_upgrade') {
                        var txt = '<p>There is a new firmware with version <strong>'
                            +json.release.version+'</strong> issued on <strong>'+json.release.date+'</strong>.'
                            +'<br>Do you want to go to the firmware upgrade page to apply it?</p>';

                        gp.confirm('New firmware', txt, function() {
                            gp.update_alert_given = true;
                            // Go to the firmware upgrade page
                            $('.module').hide();
                            $('.page').hide();
                            $('a.active').removeClass('active');
                            $('#cp_system_upgrade').show().parent().show();
                            $('#system_upgrade').addClass('active');
                            $('#menu').accordion('activate' , '#system');
                        });
                    }
                }
                gp.system.upgrade.auto.loadForm();
            }
        });
    };

    gp.system.upgrade.auto.resetForm = function() {
        $('#system_upgrade_auto_warning, #system_upgrade_auto_form, #system_upgrade_auto_no_update').hide();
    };

    gp.system.upgrade.auto.loadForm = function() {
        if (gp.data.new_release) {
            var rls = gp.data.new_release;
            $('#system_upgrade_auto_version').html(rls.version);
            $('#system_upgrade_auto_date').html(rls.date);
            $('#system_upgrade_auto_filename').html(rls.filename);
            $('#system_upgrade_auto_changelog').html('<pre>'+rls.changelog+'</pre>');

            $('#system_upgrade_auto_warning, #system_upgrade_auto_form').slideDown(500);
        } else {
            $('#system_upgrade_auto_no_update').slideDown(500);
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

        $('#system_upgrade_auto_check_link').click(function() {
            gp.system.upgrade.auto.checkUpdates(false);
            return false;
        });
    });
</script>