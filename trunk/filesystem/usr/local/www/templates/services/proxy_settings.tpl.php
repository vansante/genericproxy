<h2 class="help_anchor"><a class="open_all_help" rel="cp_services_proxy_settings"></a>Proxy settings</h2>

<form id="services_proxy_settings_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_proxy_settings_form_error">
    </div>

    <input type="hidden" name="module" value="Proxy"/>
    <input type="hidden" name="page" value="save" id="services_proxy_settings_form_page"/>

    <dl>
        <dt><label for="services_proxy_settings_port">Port</label></dt>
        <dd>
            <input name="services_proxy_settings_port" type="text" size="3" id="services_proxy_settings_port" />
        </dd>

        <dt><label for="services_proxy_settings_allow_ipaddr">Allowed IP address(es)</label></dt>
        <dd>
            <input name="services_proxy_settings_allow_ipaddr" type="text" size="12" id="services_proxy_settings_allow_ipaddr"/>
            /
            <select name="services_proxy_settings_allow_subnet" id="services_proxy_settings_allow_subnet">
            <? for ($i = 32; $i >= 0; $i--) : ?>
                <option value="<?=$i?>"><?=$i?></option>
            <? endfor; ?>
            </select>
        </dd>

        <dt><label for="services_proxy_settings_proxyname">Proxy name</label></dt>
        <dd>
            <input name="services_proxy_settings_proxyname" type="text" size="20" id="services_proxy_settings_proxyname" />
        </dd>

        <dt><label for="services_proxy_settings_maxclients">Max clients</label></dt>
        <dd>
            <input name="services_proxy_settings_maxclients" type="text" size="3" id="services_proxy_settings_maxclients" />
        </dd>

        <dt><label for="services_proxy_settings_timeout">Time out (seconds)</label></dt>
        <dd>
            <input name="services_proxy_settings_timeout" type="text" size="3" id="services_proxy_settings_timeout" />
        </dd>

        <dt><input type="submit" value="Save" id="services_proxy_settings_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<div class="help_pool">
    <div class="help" id="help_services_proxy_settings_port">The port the proxy will listen on</div>
    <div class="help" id="help_services_proxy_settings_allow_ipaddr">The subnet the proxy will service</div>
    <div class="help" id="help_services_proxy_settings_proxyname">The name of the proxy, appears in serviced http headers</div>
    <div class="help" id="help_services_proxy_settings_maxclients">The maximum number of clients the proxyserver will service</div>
    <div class="help" id="help_services_proxy_settings_timeout"> The number of seconds of inactivity a connection is allowed to have before it closed by the proxy.</div>
</div>

<script type="text/javascript">
    gp.services.proxy.settings.loadForm = function() {
        var data = gp.data.proxy_settings;
        gp.resetForm('services_proxy_settings_form');

        $('#services_proxy_settings_port').val(data.port);
        $('#services_proxy_settings_allow_ipaddr').val(data.allow_from.ip);
        $('#services_proxy_settings_allow_subnet').val(data.allow_from.subnet);
        $('#services_proxy_settings_proxyname').val(data.proxyname);
        $('#services_proxy_settings_maxclients').val(data.maxclients);
        $('#services_proxy_settings_timeout').val(data.timeout);
    };

    $(function(){
        //Handler for submitting the form
        $('#services_proxy_settings_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/proxy.xml',
                form_id: 'services_proxy_settings_form',
                error_element: $('#services_proxy_settings_form_error'),
                successFn: function(json) {
                    gp.data.proxy_settings = json.proxy;
                    gp.services.proxy.settings.loadForm();
                }
            });
            return false;
        });
    });
</script>