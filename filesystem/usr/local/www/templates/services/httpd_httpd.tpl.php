<h2 class="help_anchor"><a class="open_all_help" rel="cp_services_httpd_httpd"></a>HTTPD</h2>

<p class="intro">If you select HTTPS, you will need to securely access your web interface using a URL that starts with "https:" and to enter a signed certificate and key.</p>

<form id="services_httpd_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="services_httpd_form_error">
    </div>

    <input type="hidden" name="module" value="Httpd"/>
    <input type="hidden" name="page" value="save" id="services_httpd_form_page"/>

    <dl>
        <dt><label for="services_httpd_protocol">Web interface protocol</label></dt>
        <dd>
            <input name="services_httpd_protocol" type="radio" id="services_httpd_protocol_http" value="http" />
            <label for="services_httpd_protocol_http">HTTP</label>
            <input name="services_httpd_protocol" type="radio" id="services_httpd_protocol_https" value="https" />
            <label for="services_httpd_protocol_https">HTTPS</label>
        </dd>

        <dt><label for="services_httpd_port">Port</label></dt>
        <dd>
            <input name="services_httpd_port" type="text" size="3" id="services_httpd_port" />
        </dd>

        <dt><label for="services_httpd_certificate">Certificate</label></dt>
        <dd>
            <input name="services_httpd_certificate" type="file" id="services_httpd_certificate" />
        </dd>

        <dt><label for="services_httpd_privatekey">Private key</label></dt>
        <dd>
            <input name="services_httpd_privatekey" type="file" id="services_httpd_privatekey" />
        </dd>

        <dt><input type="submit" value="Save" id="services_httpd_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<div class="help_pool">
    <div class="help" id="help_services_httpd_protocol">Select the protocol you want to use</div>
    <div class="help" id="help_services_httpd_port">Select the port number the webserver should run on</div>
    <div class="help" id="help_services_httpd_certificate">Enter the certificate file for https here</div>
    <div class="help" id="help_services_httpd_privatekey">Enter the private key file for https here</div>
</div>