<h2 class="help_anchor"><a class="open_all_help" rel="cp_services_dhcpd_settings"></a>DHCPD settings</h2>

<p class="intro">This screen allows you to enable and configure the DHCP server.</p>

<div class="note">
    <h3>Note:</h3>
    <p>The DNS servers entered in System: General setup (or the DNS forwarder, if enabled) will be assigned to clients by the DHCP server.<br/> The DHCP lease table can be viewed on the Diagnostics: DHCP leases page. </p>
</div>

<form id="services_dhcpd_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_dhcpd_form_error">
    </div>
    
    <input type="hidden" name="module" value="Dhcpd"/>
    <input type="hidden" name="page" value="save" id="services_dhcpd_form_page"/>

    <dl>
        <dt><label for="services_dhcpd_enabled">Enable DHCPD</label></dt>
        <dd>
            <input name="services_dhcpd_enabled" type="checkbox" id="services_dhcpd_enabled" value="true" />
        </dd>

        <dt>Settings</dt>
        <dd>
            <dl class="form_sub" id="services_dhcpd_subform">
                <dt><label for="services_dhcpd_deny_unknown">Deny unknown clients</label></dt>
                <dd>
                    <input name="services_dhcpd_deny_unknown" type="checkbox" id="services_dhcpd_deny_unknown" value="true" />
                </dd>
                
                <dt><label for="services_dhcpd_range_from">Range</label></dt>
                <dd>
                    <input name="services_dhcpd_range_from" type="text" size="12" id="services_dhcpd_range_from" /> to
                    <input name="services_dhcpd_range_to" type="text" size="12" id="services_dhcpd_range_to" />
                </dd>

                <dt><label for="services_dhcpd_netmask">Subnet mask</label></dt>
                <dd>
                    <input name="services_dhcpd_netmask" type="text" size="12" id="services_dhcpd_netmask" />
                </dd>


                <dt><label for="services_dhcpd_wins1">WINS servers</label></dt>
                <dd>
                    <input name="services_dhcpd_wins1" type="text" size="12" id="services_dhcpd_wins1" /><br/>
                    <input name="services_dhcpd_wins2" type="text" size="12" id="services_dhcpd_wins2" />
                </dd>

                <dt><label for="services_dhcpd_deflease">Default lease time</label></dt>
                <dd>
                    <input name="services_dhcpd_deflease" type="text" size="3" id="services_dhcpd_deflease" />
                </dd>

                <dt><label for="services_dhcpd_maxlease">Maximum lease time</label></dt>
                <dd>
                    <input name="services_dhcpd_maxlease" type="text" size="3" id="services_dhcpd_maxlease" />
                </dd>
            </dl>
        </dd>

        <dt><input type="submit" value="Save" id="services_dhcpd_submit" class="submitbutton"/></dt>
    </dl>

    <p style="clear: both;"></p>
</form>

<div class="help_pool">
    <div class="help" id="help_services_dhcpd_enabled">Enable DHCP server on the LAN interface</div>
    <div class="help" id="help_services_dhcpd_deny_unknown">If this is checked, only the clients defined in the mappings table will get DHCP leases from this server. </div>
    <div class="help" id="help_services_dhcpd_range_from">Specify which range of IP addresses to be handed out</div>
    <div class="help" id="help_services_dhcpd_netmask">Enter the networks subnet mask<br>Hint: Should generally be the same as the subnet mask specified for the LAN interface.</div>
    <div class="help" id="help_services_dhcpd_wins1">WINS Servers</div>
    <div class="help" id="help_services_dhcpd_deflease">This is used for clients that do not ask for a specific expiration time. The default is 7200 seconds.</div>
    <div class="help" id="help_services_dhcpd_maxlease">This is the maximum lease time for clients that ask for a specific expiration time. The default is 86400 seconds.</div>
</div>

<script type="text/javascript">
    gp.services.dhcpd.settings.loadForm = function() {
        var data = gp.data.dhcpd_settings;
        gp.resetForm('services_dhcpd_form');

        if (data.enable.toLowerCase() == 'true') {
            $('#services_dhcpd_enabled').attr('checked', 'checked');
            $('#services_dhcpd_subform input').removeAttr('disabled');
            $('#services_dhcpd_deny_unknown').attr('checked', data.deny_unknown.toLowerCase() == 'true');
            $('#services_dhcpd_range_from').val(data.range.from);
            $('#services_dhcpd_range_to').val(data.range.to);
            $('#services_dhcpd_netmask').val(data.netmask);

            if (data.winsservers.winsserver) {
                if ($.isArray(data.winsservers.winsserver)) {
                    $.each(data.winsservers.winsserver, function(i, wins) {
                        if (i <= 1) {
                            $('#services_dhcpd_wins'+(i+1)).val(wins.ip);
                        }
                    });
                } else {
                    $('#services_dhcpd_wins1').val(data.winsservers.winsserver.ip);
                }
            }
            $('#services_dhcpd_deflease').val(data.defaultleasetime);
            $('#services_dhcpd_maxlease').val(data.maxleasetime);
        } else {
            $('#services_dhcpd_subform input').attr('disabled', 'disabled');
        }
    };

    $(function() {
        //Handler for submitting the form
        $('#services_dhcpd_form').submit(function() {
            gp.doFormAction({
                form_id: 'services_dhcpd_form',
                error_element: $('#services_dhcpd_form_error'),
                successFn: function(json) {
                    gp.data.dhcpd_settings = json.dhcpd;
                    gp.services.dhcpd.settings.loadForm();
                }
            });
            return false;
        });

        $('#services_dhcpd_enabled').click(function() {
            if ($('#services_dhcpd_enabled').attr('checked')) {
                $('#services_dhcpd_subform input').removeAttr('disabled');
            } else {
                $('#services_dhcpd_subform input').attr('disabled', 'disabled');
            }
        });
    });
</script>