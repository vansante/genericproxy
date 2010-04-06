<h2>Firmware upgrade</h2>

<p class="intro">Choose the image file (net48xx-*.img) to be uploaded. Click "Upload" to start the upgrade process.</p>

<div class="warning">
    <h3>Warning:</h3>
    <p>Do NOT abort the firmware upgrade once it has started. The appliance will reboot automatically after storing the new firmware. The configuration will be maintained.</p>
</div>

<form id="system_firmwup_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="system_firmwup_form_error">
    </div>

    <input type="hidden" name="module" value="System"/>
    <input type="hidden" name="page" value="upgradefirmware" id="system_firmwup_form_page"/>
    
    <dl>
        <dt><label for="system_firmwup_image">Firmware image</label></dt>
        <dd>
            <input name="system_firmwup_image" type="file" id="system_firmwup_image"/>
        </dd>

        <dt><input type="submit" value="Upgrade firmware" id="system_firmwup_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>