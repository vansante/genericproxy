<h2 class="help_anchor"><a class="open_all_help" rel="cp_services_dyndns_dyndns"></a>Dynamic DNS</h2>

<p class="intro">For links to providers of dynamic DNS services, visit the website of the dynamic DNS client, www.ez-ipupdate.com<br>After you have signed up with one of the dynamic DNS providers listed, you can continue.</p>

<form id="services_dyndns_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_dyndns_form_error">
    </div>

    <input type="hidden" name="module" value="Dyndns"/>
    <input type="hidden" name="page" value="save" id="services_dyndns_form_page"/>
    
    <dl>
        <dt><label for="services_dyndns_enabled">Enable dynamic DNS client</label></dt>
        <dd>
            <input name="services_dyndns_enabled" type="checkbox" id="services_dyndns_enabled" value="true" />
        </dd>

        <dt>DNS client options</dt>
        <dd>
            <dl class="form_sub" id="services_dyndns_subform_client">
                <dt><label for="services_dyndns_type">Service type</label></dt>
                <dd>
                    <select name="services_dyndns_type" id="services_dyndns_type">
                        <option value="dyndns" selected>DynDNS</option>
                        <option value="dhs">DHS</option>
                        <option value="ods">ODS</option>
                        <option value="dyns">DyNS</option>
                        <option value="hn">HN.ORG</option>
                        <option value="zoneedit">ZoneEdit</option>
                        <option value="gnudip">GNUDip</option>
                        <option value="dyndns-static">DynDNS (static)</option>
                        <option value="dyndns-custom">DynDNS (custom)</option>
                        <option value="easydns">easyDNS</option>
                        <option value="ezip">EZ-IP</option>
                        <option value="tzo">TZO</option>
                    </select>
                </dd>

                <dt><label for="services_dyndns_hostname">Hostname</label></dt>
                <dd>
                    <input name="services_dyndns_hostname" type="text" id="services_dyndns_hostname" />
                </dd>

                <dt><label for="services_dyndns_server">Server</label></dt>
                <dd>
                    <input name="services_dyndns_server" type="text" size="12" id="services_dyndns_server" />
                </dd>

                <dt><label for="services_dyndns_port">Port</label></dt>
                <dd>
                    <input name="services_dyndns_port" type="text" size="3" id="services_dyndns_port" />
                </dd>

                <dt><label for="services_dyndns_mx">MX</label></dt>
                <dd>
                    <input name="services_dyndns_mx" type="text" id="services_dyndns_mx" />
                </dd>

                <dt><label for="services_dyndns_wildcards">Enable wildcards</label></dt>
                <dd>
                    <input name="services_dyndns_wildcards" type="checkbox" id="services_dyndns_wildcards" value="true" />
                </dd>

                <dt><label for="services_dyndns_username">Username</label></dt>
                <dd>
                    <input name="services_dyndns_username" type="text" id="services_dyndns_username" />
                </dd>

                <dt><label for="services_dyndns_password">Password</label></dt>
                <dd>
                    <input name="services_dyndns_password" type="password" id="services_dyndns_password" />
                </dd>
            </dl>
        </dd>

        <dt><input type="submit" value="Save" id="services_dyndns_submit" class="submitbutton"/></dt>
    </dl>

    <p style="clear: both;"></p>
</form>

<div class="help_pool">
    <div class="help" id="help_services_dyndns_type">Select the type of dynamic dns update provider</div>
    <div class="help" id="help_services_dyndns_hostname">Name of the device host, without domain part.<br>e.g. "genericproxy"</div>
    <div class="help" id="help_services_dyndns_server">Special server to connect to. This can usually be left blank.</div>
    <div class="help" id="help_services_dyndns_port">Port number of the DNS.</div>
    <div class="help" id="help_services_dyndns_mx">Set this option only if you need a special MX record. Not all services support this.</div>
    <div class="help" id="help_services_dyndns_wildcards">Enable wildcards</div>
    <div class="help" id="help_services_dyndns_username">Your username</div>
    <div class="help" id="help_services_dyndns_password">Your password</div>
</div>