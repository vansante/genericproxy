<h2>IPSec settings</h2>

<p class="intro">IPSec's tunnel mode is supported on the device. This mode allows secured communication between entire subnets. When the packet leaves the subnet it will be encrypted, when it gets to the remote IPSec device the packets are decrypted and routed/sent into the remote network.</p>

<form id="services_ipsec_settings_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_ipsec_settings_form_error">
    </div>

    <input type="hidden" name="module" value="Ipsec"/>
    <input type="hidden" name="page" value="save" id="services_ipsec_settings_form_page"/>

    <dl>
        <dt><label for="services_ipsec_settings_enabled">Enable IPSec</label></dt>
        <dd>
            <input name="services_ipsec_settings_enabled" type="checkbox" id="services_ipsec_settings_enabled" value="true"/>
        </dd>
        
        <dt><input type="submit" value="Save" id="services_ipsec_settings_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<script type="text/javascript">
    gp.services.ipsec.settings.loadForm = function() {
        var data = gp.data.ipsec_settings;
        gp.resetForm('services_ipsec_settings_form');

        $('#services_ipsec_settings_enabled').attr('checked', data.enable.toLowerCase() == 'true');
    };

    $(function(){
        //Handler for submitting the form
        $('#services_ipsec_settings_form').submit(function() {
            var checked = $('#services_ipsec_settings_enabled').attr('checked');
            if ((gp.data.ipsec_settings.enable.toLowerCase() == 'true' && checked)
                || (gp.data.ipsec_settings.enable.toLowerCase() == 'false' && !checked)) {
                return false;
            }
            gp.doFormAction({
                url: 'testxml/ipsec.xml',
                form_id: 'services_ipsec_settings_form',
                error_element: $('#services_ipsec_settings_form_error'),
                successFn: function(json) {
                    var enabled = gp.data.ipsec_settings.enable == 'true';
                    gp.data.ipsec_settings.enable = enabled ? 'false' : 'true';
                    gp.services.ipsec.settings.loadForm();
                }
            });
            return false;
        });
    });
</script>
