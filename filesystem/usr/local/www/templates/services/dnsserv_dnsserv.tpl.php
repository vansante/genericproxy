<h2 class="help_anchor"><a class="open_all_help" rel="cp_services_dnsserv_dnsserv"></a>DNS server</h2>

<p class="intro">Here you can enter the settings for the MaraDNS server. You can also manually fetch the dns zones. Please note that this will take a while and completes without feedback.</p>

<form id="services_dnsserv_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_dnsserv_form_error">
    </div>

    <input type="hidden" name="module" value="MaraDNS"/>
    <input type="hidden" name="page" value="save" id="services_dnsserv_form_page"/>

    <dl>
        <dt><label for="services_dnsserv_enabled">Enable DNS server</label></dt>
        <dd>
            <input name="services_dnsserv_enabled" type="checkbox" id="services_dnsserv_enabled" value="true" />
        </dd>

        <dt>DNS server settings</dt>
        <dd>
            <dl class="form_sub" id="services_dnsserv_settings">
                <dt><label for="services_dnsserv_server">Zone server</label></dt>
                <dd>
                    <input name="services_dnsserv_server" type="text" size="20" id="services_dnsserv_server" />
                </dd>

                <dt><label for="services_dnsserv_zone">Zone</label></dt>
                <dd>
                    <input name="services_dnsserv_zone" type="text" size="20" id="services_dnsserv_zone" />
                </dd>

                <dt><label for="services_dnsserv_fetchzone">Fetch zone now</label></dt>
                <dd>
                    <a class="icon_add" href="#services_dnsserv" id="services_dnsserver_fetchzone">Fetch zone</a>
                </dd>
            </dl>
        </dd>

        <dt>
            <input type="submit" value="Save" id="services_dnsserv_submit" class="submitbutton"/>
        </dt>
    </dl>

    <p style="clear: both;"></p>
</form>

<div class="help_pool">
    
</div>