<h2 class="help_anchor"><a class="open_all_help" rel="cp_diagnostics_tracert_tracert"></a>Trace route to host</h2>

<div class="note">
    <h3>Note:</h3>
    <p>Traceroute may take a while to complete.</p>
</div>

<form id="diagnostics_tracert_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="diagnostics_tracert_form_error">
    </div>

    <input type="hidden" name="module" value="Diagnostics"/>
    <input type="hidden" name="page" value="traceroute" id="diagnostics_tracert_form_page"/>

    <dl>
        <dt><label for="diagnostics_tracert_host">Address to trace</label></dt>
        <dd>
            <input name="diagnostics_tracert_host" type="text" id="diagnostics_tracert_host" />
        </dd>

        <dt><label for="diagnostics_tracert_maxhops">Maximum number of hops</label></dt>
        <dd>
            <select name="diagnostics_tracert_maxhops" id="diagnostics_tracert_maxhops">
            <? for ($i = 1; $i <= 64; $i++) : ?>
                <option value="<?=$i?>"<?php if ($i == 32) echo ' selected="selected"'?>><?=$i?> hops</option>
            <? endfor ?>
            </select>
        </dd>

        <dt><label for="diagnostics_tracert_use_icmp">Use ICMP</label></dt>
        <dd>
            <input name="diagnostics_tracert_use_icmp" type="checkbox" id="diagnostics_tracert_use_icmp" value="true" />
        </dd>

        <dt><input type="submit" value="Trace route" id="diagnostics_tracert_submit" class="submitbutton"/></dt>
    </dl>
</form>

<p style="clear: both;"></p>

<div class="diagnostics-results" id="diagnostics_tracert_results_div">
    <h3>Traceroute results</h3>
    <pre><code id="diagnostics_tracert_results"></code></pre>
</div>

<div class="help_pool">
    <div class="help" id="help_diagnostics_tracert_host">Enter a host name or IP address to ping.</div>
    <div class="help" id="help_diagnostics_tracert_maxhops">Choose the interface you want to use for pinging.</div>
    <div class="help" id="help_diagnostics_tracert_use_icmp">The Internet Control Message Protocol (ICMP) is one of the core protocols of the Internet Protocol Suite. It is chiefly used by networked computers' operating systems to send error messages indicating, for instance, that a requested service is not available or that a host or router could not be reached.</div>
</div>