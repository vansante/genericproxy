<h2>SSH</h2>

<form id="services_ssh_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_ssh_form_error">
    </div>

    <input type="hidden" name="module" value="Ssh"/>
    <input type="hidden" name="page" value="save" id="services_ssh_form_page"/>

    <dl>
        <dt><label for="services_ssh_enabled">Enable SSH</label></dt>
        <dd>
            <input name="services_ssh_enabled" type="checkbox" id="services_ssh_enabled" value="true" />
        </dd>

        <dt><input type="submit" value="Save" id="services_ssh_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<div class="help_pool">
    <div class="help" id="help_services_ssh_enabled">If enabled, the device can be accessed over the LAN interface through SSH.</div>
</div>