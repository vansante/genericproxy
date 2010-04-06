<h2 class="help_anchor"><a class="open_all_help" rel="cp_diagnostics_ping_ping"></a>Ping host</h2>

<form id="diagnostics_ping_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="diagnostics_ping_form_error">
    </div>

    <input type="hidden" name="module" value="Diagnostics"/>
    <input type="hidden" name="page" value="ping" id="diagnostics_ping_form_page"/>
    
    <dl>
        <dt><label for="diagnostics_ping_host">Address to ping</label></dt>
        <dd>
            <input name="diagnostics_ping_host" type="text" id="diagnostics_ping_host" />
        </dd>

        <dt><label for="diagnostics_ping_interface">Interface</label></dt>
        <dd>
            <select name="diagnostics_ping_interface" id="diagnostics_ping_interface">
                <option value="wan">WAN</option>
                <option value="lan">LAN</option>
                <option value="ext">EXT</option>
            </select>
        </dd>

        <dt><label for="diagnostics_ping_count">Ping count</label></dt>
        <dd>
            <input name="diagnostics_ping_count" size="1" type="text" id="diagnostics_ping_count" value="10"/>
        </dd>

        <dt><input type="submit" value="Ping" id="diagnostics_ping_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<div class="diagnostics-results" id="diagnostics_ping_results_div">
    <h3>Ping results</h3>
    <code id="diagnostics_ping_results"></code>
</div>

<div class="help_pool">
    <div class="help" id="help_diagnostics_ping_host">Enter a host name or IP address to ping.</div>
    <div class="help" id="help_diagnostics_ping_interface">Choose the interface you want to use for pinging.</div>
    <div class="help" id="help_diagnostics_ping_count">The amount of echo request packets you want o send.</div>
</div>