<h2>Bandwidth sharing</h2>

<p class="intro">On this page you can choose how you want to share your bandwith.</p>

<form id="services_sharing_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_sharing_form_error">
    </div>

    <input type="hidden" name="module" value="Scheduler"/>
    <input type="hidden" name="page" value="save" id="services_sharing_form_page"/>

    <input type="hidden" name="services_sharing_schedule" value="" id="services_sharing_schedule"/>

    <h3 class="help_anchor"><label for="services_sharing">Your maximum connection speed:</label></h3>
    <dl>
        <dt><label for="services_sharing_download">Download</label></dt>
        <dd>
            <input name="services_sharing_download" size="3" type="text" id="services_sharing_download"/>&nbsp;KiB/s
        </dd>
        <dt><label for="services_sharing_upload">Upload</label></dt>
        <dd>
            <input name="services_sharing_upload" size="3" type="text" id="services_sharing_upload"/>&nbsp;KiB/s
        </dd>
    </dl>

    <p style="clear: both;"></p>

    <h3 class="help_anchor"><label for="services_sharing_standard_sharing">Standard sharing:</label></h3>
    <dl>
        <dt><label for="services_sharing_standard_download_speed">Download speed</label></dt>
        <dd>
            <select class="slider" name="services_sharing_standard_download_speed" id="services_sharing_standard_download_speed">
                <option value="0">Don't share</option>
                <option value="10">10%</option>
                <option value="20">20%</option>
                <option value="30">30%</option>
                <option value="40">40%</option>
                <option value="50">50%</option>
                <option value="60">60%</option>
                <option value="70">70%</option>
                <option value="80">80%</option>
                <option value="90">90%</option>
                <option value="100">100%</option>
            </select>
        </dd>
        <dt><label for="services_sharing_standard_upload_speed">Upload speed</label></dt>
        <dd>
            <select class="slider" name="services_sharing_standard_upload_speed" id="services_sharing_standard_upload_speed">
                <option value="0">Don't share</option>
                <option value="10">10%</option>
                <option value="20">20%</option>
                <option value="30">30%</option>
                <option value="40">40%</option>
                <option value="50">50%</option>
                <option value="60">60%</option>
                <option value="70">70%</option>
                <option value="80">80%</option>
                <option value="90">90%</option>
                <option value="100">100%</option>
            </select>
        </dd>
    </dl>

    <p style="clear: both;"></p>

    <h3 class="help_anchor"><label for="services_sharing_optional_sharing">Limited sharing:</label></h3>
    <dl>
        <dt><label for="services_sharing_optional_download_speed">Download speed</label></dt>
        <dd>
            <select class="slider" name="services_sharing_optional_download_speed" id="services_sharing_optional_download_speed">
                <option value="0">Don't share</option>
                <option value="10">10%</option>
                <option value="20">20%</option>
                <option value="30">30%</option>
                <option value="40">40%</option>
                <option value="50">50%</option>
                <option value="60">60%</option>
                <option value="70">70%</option>
                <option value="80">80%</option>
                <option value="90">90%</option>
                <option value="100">100%</option>
            </select>
        </dd>
        <dt><label for="services_sharing_optional_upload_speed">Upload speed</label></dt>
        <dd>
            <select class="slider" name="services_sharing_optional_upload_speed" id="services_sharing_optional_upload_speed">
                <option value="0">Don't share</option>
                <option value="10">10%</option>
                <option value="20">20%</option>
                <option value="30">30%</option>
                <option value="40">40%</option>
                <option value="50">50%</option>
                <option value="60">60%</option>
                <option value="70">70%</option>
                <option value="80">80%</option>
                <option value="90">90%</option>
                <option value="100">100%</option>
            </select>
        </dd>
    </dl>

    <p style="clear: both;"></p>

    <h3 class="help_anchor"><label for="services_sharing_schedule_table">Schedule:</label></h3>
    <dl>
        <dt><label for="services_sharing_schedule_configs">Predefined configurations</label></dt>
        <dd>
            <select id="services_sharing_schedule_configs">
            </select>
            <a class="icon_add" href="#services_sharing" id="services_sharing_save_config_link">Save</a>
            <a class="icon_add" href="#services_sharing" id="services_sharing_delete_config_link">Delete</a>
        </dd>
    </dl>
    <p style="clear: both;"></p>

    <table id="services_sharing_schedule_table" cellpadding="0" cellspacing="0">
        <thead></thead>
        <tbody></tbody>
    </table>

    <table id="services_sharing_schedule_legend" cellpadding="0" cellspacing="0">
        <tbody>
            <tr>
                <th class="services_sharing_standard"></th>
                <td>Standard</td>
            </tr>
            <tr>
                <th class="services_sharing_limited"></th>
                <td>Limited</td>
            </tr>
            <tr>
                <th class="services_sharing_off"></th>
                <td>Off</td>
            </tr>
        </tbody>
    </table>

    <input type="submit" value="Save" id="services_sharing_submit" class="submitbutton"/>

    <p style="clear: both;"></p>
</form>

<form id="services_sharing_config_form" action="ajaxserver.php" method="post" class="dialog" title="Add new config">
    <div class="form-error" id="services_sharing_config_form_error">
    </div>

    <input type="hidden" name="module" value="Scheduler"/>
    <input type="hidden" name="page" value="addconfig" id="services_sharing_config_form_page"/>

    <input type="hidden" name="services_sharing_config_schedule" value="" id="services_sharing_config_schedule"/>

    <dl>
        <dt><label for="services_sharing_config_name">Name</label></dt>
        <dd>
            <input name="services_sharing_config_name" type="text" size="20" id="services_sharing_config_name"/>
        </dd>

        <dt><input type="submit" value="Add config" id="services_sharing_config_submit" class="submitbutton"/></dt>
    </dl>
    
    <p style="clear: both;"></p>
</form>


<div class="help_pool">
    <div class="help" id="help_services_sharing">Specify your maximum connection speed here.<br />You can specify the sharing speed below in a percentage of the maximum connection speed.</div>
</div>