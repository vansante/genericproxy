<h2>DNS forwarding settings</h2>

<p class="intro">This service allows you to use the fixed IP address of your GenericProxy's LAN ethernet interface to resolve/proxy all DNS queries on your LAN network. When the DHCP server assigns IP addresses, it also assigns the LAN IP address as the DNS server to use.</p>

<div class="note">
    <h3>Note:</h3>
    <p>If the DNS forwarder is enabled, the DHCP service (if enabled) will automatically serve the LAN IP address as a DNS server to DHCP clients so they will use the forwarder. The DNS forwarder will use the DNS servers entered in System: General setup  or those obtained via DHCP or PPP on WAN if the "Allow DNS server list to be overridden by DHCP/PPP on WAN" is checked. If you don't use that option (or if you use a static IP address on WAN), you must manually specify at least one DNS server on the System: General setup page.</p>
</div>

<form id="services_dnsf_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_dnsf_form_error">
    </div>

    <input type="hidden" name="module" value="DnsForward"/>
    <input type="hidden" name="page" value="save" id="services_dnsf_form_page"/>

    <dl>
        <dt><label for="services_dnsf_enabled">Enable DNS forwarder</label></dt>
        <dd>
            <input name="services_dnsf_enabled" type="checkbox" id="services_dnsf_enabled" value="true" />
        </dd>

        <dt><input type="submit" value="Save" id="services_dnsf_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<div class="help_pool">
    <div class="help" id="help_services_dnsf_enabled">If this option is set, then machines that specify their hostname when requesting a DHCP lease will be registered in the DNS forwarder, so that their name can be resolved. You should also set the domain in System: General setup to the proper value.</div>
</div>

<script type="text/javascript">
    gp.services.dnsf.settings.loadForm = function() {
        var data = gp.data.dnsf_settings;
        gp.resetForm('services_dnsf_form');
        $('#services_dnsf_enabled').attr('checked', data.enable.toLowerCase() == 'true');
    };

    $(function(){
        //Handler for submitting the form
        $('#services_dnsf_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/dnsf.xml',
                form_id: 'services_dnsf_form',
                error_element: $('#services_dnsf_form_error'),
                successFn: function(json) {
//                    var enabled = gp.data.dnsforward.enable == 'true';
//                    gp.data.dnsforward.enable = enabled ? 'false' : 'true';
                    gp.data.dnsf_settings = json.dnsforward;
                    gp.services.dnsf.settings.loadForm();
                }
            });
            return false;
        });
    });
</script>
