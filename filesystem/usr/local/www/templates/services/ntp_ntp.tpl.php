<h2 class="help_anchor"><a class="open_all_help" rel="cp_services_ntp_ntp"></a>NTP settings</h2>

<p class="intro">The NTP (Network Time Protocol) page allows you to setup all the settings for a NTP-server. You can find the right NTP server in your country here.</p>

<form id="services_ntp_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_ntp_form_error">
    </div>

    <input type="hidden" name="module" value="System"/>
    <input type="hidden" name="page" value="saventp" id="services_ntp_form_page"/>

    <dl>
        <dt><label for="services_ntp_timezone">Time zone</label></dt>
        <dd>
            <select name="services_ntp_timezone" id="services_ntp_timezone">
            </select>
        </dd>

        <dt><label for="services_ntp_interval">Time update interval</label></dt>
        <dd>
            <select name="services_ntp_interval" id="services_ntp_interval">
            <?php for ($i = 1; $i <= 7; $i++) : ?>
                <option value="<?= $i*60*60*24 ?>"><?=$i?> days</option>
            <?php endfor ?>
            </select>
        </dd>

        <dt><label for="services_ntp_server">NTP server</label></dt>
        <dd>
            <input name="services_ntp_server" type="text" id="services_ntp_server" />
        </dd>

        <dt><input type="submit" value="Save" id="services_ntp_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<div class="help_pool">
    <div class="help" id="help_services_ntp_timezone">Select your timezone here</div>
    <div class="help" id="help_services_ntp_interval">Select the time update interval</div>
    <div class="help" id="help_services_ntp_server">Select the timeserver to synchronize with</div>
</div>