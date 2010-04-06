<h2>Manual firmware upgrade</h2>

<p class="intro">Choose the image file (net48xx-*.img) to be uploaded. Click "Upload" to start the upgrade process.</p>

<div class="warning">
    <h3>Warning:</h3>
    <p>Do NOT abort the firmware upgrade once it has started. The appliance will reboot automatically after storing the new firmware. The configuration will be maintained.</p>
</div>

<form id="system_upgrade_manual_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="system_upgrade_manual_form_error">
    </div>

    <input type="hidden" name="module" value="Update"/>
    <input type="hidden" name="page" value="updatefirmwaremanual" id="system_upgrade_manual_form_page"/>
    
    <dl>
        <dt><label for="system_upgrade_manual_image">Firmware image</label></dt>
        <dd>
            <input name="system_upgrade_manual_image" type="file" id="system_upgrade_manual_image"/>
        </dd>

        <dt><input type="submit" value="Upgrade firmware" id="system_upgrade_manual_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<script type="text/javascript">
    $(function() {
        $('#system_upgrade_manual__form').submit(function(){
            gp.confirm("Are you sure?", "Are you sure you want to upgrade the devices firmware?", function() {
                gp.doFormAction({
                    url: 'testxml/reply.xml',
                    form_id: 'system_upgrade_manual__form',
                    error_element: $('#system_upgrade_manual__form_error'),
                    successFn: function(json) {
                        gp.rebootNotice();
                    }
                });
            });
            return false;
        });
    });
</script>