<form id="firewall_rules_form" action="ajaxserver.php" method="post" class="dialog">
<div class="form-error" id="firewall_rules_form_error">
</div>

<input type="hidden" name="module" value="Firewall"/>
<input type="hidden" name="page" value="addrule" id="firewall_rules_form_page"/>
<input type="hidden" name="firewall_rules_id" value="" id="firewall_rules_id"/>

    <dl>
        <dt><label for="firewall_rules_interface">Interface</label></dt>
        <dd>
            <select name="firewall_rules_interface" id="firewall_rules_interface">
                <option value="wan">WAN</option>
                <option value="lan">LAN</option>
                <option value="ext">EXT</option>
            </select>
        </dd>

        <dt><label for="firewall_rules_action">Action</label></dt>
        <dd>
            <select name="firewall_rules_action" id="firewall_rules_action">
                <option value="pass">Pass</option>
                <option value="block">Block</option>
                <option value="reject">Reject</option>
            </select>
        </dd>

        <dt><label for="firewall_rules_protocol">Protocol</label></dt>
        <dd>
            <select name="firewall_rules_protocol" id="firewall_rules_protocol">
                <option value="tcp">TCP</option>
                <option value="udp">UDP</option>
                <option value="tcp/udp">TCP/UDP</option>
                <option value="icmp">ICMP</option>
                <option value="esp">ESP</option>
                <option value="ah">AH</option>
                <option value="gre">GRE</option>
                <option value="ipv6">IPv6</option>
                <option value="igmp">IGMP</option>
                <option value="any">any</option>
            </select>
        </dd>

        <dt><label for="firewall_rules_icmp_type">ICMP Type</label></dt>
        <dd>
            <select name="firewall_rules_icmp_type" id="firewall_rules_icmp_type">
                <option value="" selected="selected">any</option>
                <option value="unreach">Destination unreachable</option>
                <option value="echo">Echo</option>
                <option value="echorep">Echo reply</option>
                <option value="squench">Source quench</option>
                <option value="redir">Redirect</option>
                <option value="timex">Time exceeded</option>
                <option value="paramprob">Parameter problem</option>
                <option value="timest">Timestamp</option>
                <option value="timestrep">Timestamp reply</option>
                <option value="inforeq">Information request</option>
                <option value="inforep">Information reply</option>
                <option value="maskreq">Address mask request</option>
                <option value="maskrep" >Address mask reply</option>
            </select>
        </dd>

        <dt>Source</dt>
        <dd>
            <?
            $this->ip_id = 'firewall_rules_src';
            include $this->template('forms/ip.tpl.php');
            ?>
        </dd>

        <dt>Source port</dt>
        <dd>
            <?
            $this->portrange_id = 'firewall_rules_srcport';
            include $this->template('forms/portrange.tpl.php');
            ?>
        </dd>

        <dt>Destination</dt>
        <dd>
            <?
            $this->ip_id = 'firewall_rules_dest';
            include $this->template('forms/ip.tpl.php');
            ?>
        </dd>

        <dt>Destination port</dt>
        <dd>
            <?
            $this->portrange_id = 'firewall_rules_destport';
            include $this->template('forms/portrange.tpl.php');
            ?>
        </dd>

        <dt><label for="firewall_rules_fragments">Allow fragmented packets</label></dt>
        <dd><input name="firewall_rules_fragments" type="checkbox" id="firewall_rules_fragments" value="true"/></dd>

        <dt><label for="firewall_rules_log">Log packets handled by this rule</label></dt>
        <dd><input name="firewall_rules_log" type="checkbox" id="firewall_rules_log" value="true"/></dd>

        <dt><label for="firewall_rules_descr">Description</label></dt>
        <dd><input name="firewall_rules_descr" type="text" size="40" id="firewall_rules_descr"/></dd>

        <dt><input type="submit" value="Add rule" id="firewall_rules_submit" class="submitbutton"/></dt>
    </dl>
</form>

<div class="help_pool">
    <div class="help" id="help_firewall_rules_interface">Choose on which interface packets must come in to match this rule.</div>
    <div class="help" id="help_firewall_rules_action">Choose what to do with packets that match the criteria specified below. <br>Hint: the difference between block and reject is that with reject, a packet (TCP RST or ICMP port unreachable for UDP) is returned to the sender, whereas with block the packet is dropped silently. In either case, the original packet is discarded. Reject only works when the protocol is set to either TCP or UDP (but not "TCP/UDP") below. </div>
    <div class="help" id="help_firewall_rules_protocol">Choose which IP protocol this rule should match.<br>Hint: in most cases, you should specify TCP  here. </div>
    <div class="help" id="help_firewall_rules_icmp_type">If you selected ICMP for the protocol above, you may specify an ICMP type here</div>
    <div class="help" id="help_firewall_rules_src_not">Enable to invert the following two rules</div>
    <div class="help" id="help_firewall_rules_src_type">The source address type</div>
    <div class="help" id="help_firewall_rules_src_address">The source address</div>
    <div class="help" id="help_firewall_rules_srcport_from">Specify the port or port range for the source of the packet for this rule. This is usually not equal to the destination port range (and is often "any").<br>Hint: you can leave the 'to' field empty if you only want to filter a single port</div>
    <div class="help" id="help_firewall_rules_dest_not">Enable to invert the following two rules</div>
    <div class="help" id="help_firewall_rules_dest_type">The destination address type</div>
    <div class="help" id="help_firewall_rules_dest_address">The destination address</div>
    <div class="help" id="help_firewall_rules_destport_from">Specify the port or port range for the destination of the packet for this rule. This is usually not equal to the source port range (and is often "any").<br>Hint: you can leave the 'to' field empty if you only want to filter a single port </div>
    <div class="help" id="help_firewall_rules_fragments">Hint: this option puts additional load on the firewall and may make it vulnerable to DoS attacks. In most cases, it is not needed. Try enabling it if you have troubles connecting to certain sites. </div>
    <div class="help" id="help_firewall_rules_log">Hint: the firewall has limited local log space. Don't turn on logging for everything. If you want to do a lot of logging, consider using a remote syslog server.</div>
    <div class="help" id="help_firewall_rules_descr">You may enter a description here for your reference</div>    
</div>

<p style="clear: both;"></p>