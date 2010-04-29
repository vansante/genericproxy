<h2>Network mapper</h2>

<p class="intro">You can use Nmap here. Nmap is a network security scanner.</p>

<div class="note">
    <h3>Note:</h3>
    <p>Nmap may take a while to complete.</p>
</div>

<form id="diagnostics_nmap_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="diagnostics_nmap_form_error">
    </div>

    <input type="hidden" name="module" value="Diagnostics"/>
    <input type="hidden" name="page" value="nmap" id="diagnostics_nmap_form_page"/>

    <dl>
        <dt><label for="diagnostics_nmap_options">Nmap options</label></dt>
        <dd>
            <input name="diagnostics_nmap_options" type="text" size="45" id="diagnostics_nmap_options" />
        </dd>

        <dt><input type="submit" value="Nmap" id="diagnostics_nmap_submit" class="submitbutton"/></dt>
    </dl>

    <p style="clear: both;"></p>
</form>

<div class="diagnostics-results" id="diagnostics_nmap_results_div">
    <h3>Nmap results</h3>
    <code id="diagnostics_nmap_results"></code>
</div>

<div class="help_pool">
    <div class="help" id="help_diagnostics_nmap_options">Enter commandline options for nmap.</div>
</div>