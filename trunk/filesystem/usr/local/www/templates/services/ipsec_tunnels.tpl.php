<h2>IPSec tunnels</h2>

<div class="form-error" id="services_ipsec_tunnel_table_error">
</div>

<table id="services_ipsec_tunnel_table">
    <thead>
        <tr>
            <th width="16">&nbsp;</th>
            <th>Local subnet</th>
            <th>Remote subnet</th>
            <th>Local gateway</th>
            <th>Remote gateway</th>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="services_ipsec_tunnel_tbody">

    </tbody>
</table>

<p>
    <a class="icon_add" href="#services_ipsec" id="services_ipsec_tunnel_add_link">Add new tunnel</a>
</p>

<form id="services_ipsec_tunnel_form" action="ajaxserver.php" method="post" class="dialog" title="Add new tunnel">
    <div class="form-error" id="services_ipsec_tunnel_form_error">
    </div>

    <input type="hidden" name="module" value="Ipsec"/>
    <input type="hidden" name="page" value="addtunnel" id="services_ipsec_tunnel_form_page"/>
    <input type="hidden" name="services_ipsec_tunnel_id" value="" id="services_ipsec_tunnel_id"/>

    <dl>
        <dt>Local subnet</dt>
        <dd>
            <dl class="form_sub">
                <dt><label for="services_ipsec_tunnel_local_subnet_type">Type</label></dt>
                <dd>
                    <select name="services_ipsec_tunnel_local_subnet_type" id="services_ipsec_tunnel_local_subnet_type">
                        <option value="lan_subnet">LAN subnet</option>
                        <option value="ipaddr">IP address</option>
                        <option value="network">Network</option>
                    </select>
                </dd>

                <dt><label for="services_ipsec_tunnel_local_subnet_ipaddr">IP address</label></dt>
                <dd>
                    <input name="services_ipsec_tunnel_local_subnet_ipaddr" size="12" type="text" id="services_ipsec_tunnel_local_subnet_ipaddr"/>
                    /
                    <select name="services_ipsec_tunnel_local_subnet_subnet" id="services_ipsec_tunnel_local_subnet_subnet">
                    <? for ($i = 32; $i >= 0; $i--) : ?>
                        <option value="<?=$i?>"><?=$i?></option>
                    <? endfor; ?>
                    </select>
                </dd>
            </dl>
        </dd>

        <dt>Remote subnet</dt>
        <dd>
            <dl class="form_sub">
                <dt><label for="services_ipsec_tunnel_remote_subnet_type">Type</label></dt>
                <dd>
                    <select name="services_ipsec_tunnel_remote_subnet_type" id="services_ipsec_tunnel_remote_subnet_type">
                        <option value="ipaddr">IP address</option>
                        <option value="network">Network</option>
                    </select>
                </dd>

                <dt><label for="services_ipsec_tunnel_remote_subnet_ipaddr">IP address</label></dt>
                <dd>
                    <input name="services_ipsec_tunnel_remote_subnet_ipaddr" size="12" type="text" id="services_ipsec_tunnel_remote_subnet_ipaddr"/>
                    /
                    <select name="services_ipsec_tunnel_remote_subnet_subnet" id="services_ipsec_tunnel_remote_subnet_subnet">
                    <? for ($i = 32; $i >= 0; $i--) : ?>
                        <option value="<?=$i?>"><?=$i?></option>
                    <? endfor; ?>
                    </select>
                </dd>
            </dl>
        </dd>

        <dt><label for="services_ipsec_tunnel_local_gateway">Local gateway</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_local_gateway" type="text" size="12" id="services_ipsec_tunnel_local_gateway"/>
        </dd>

        <dt><label for="services_ipsec_tunnel_remote_gateway">Remote gateway</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_remote_gateway" type="text" size="12" id="services_ipsec_tunnel_remote_gateway"/>
        </dd>

        <dt><label for="services_ipsec_tunnel_send_keepalive">Send ping keepalives</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_send_keepalive" type="checkbox" id="services_ipsec_tunnel_send_keepalive" value="true"/>
        </dd>

        <dt><label for="services_ipsec_tunnel_keepalive_ipaddr">Keepalive target IP address</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_keepalive_ipaddr" type="text" size="12" id="services_ipsec_tunnel_keepalive_ipaddr" value="true"/>
        </dd>

        <dt><label for="services_ipsec_tunnel_descr">Description</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_descr" type="text" size="40" id="services_ipsec_tunnel_descr"/>
        </dd>
    </dl>

    <p style="clear: both;"></p>
    
    <h3>Phase 1: Proposal (authentication)</h3>

    <dl>
        <dt><label for="services_ipsec_tunnel_p1_negotiation_mode">Negotiation mode</label></dt>
        <dd>
            <select name="services_ipsec_tunnel_p1_negotiation_mode" id="services_ipsec_tunnel_p1_negotiation_mode">
                <option value="main">Main</option>
                <option value="agressive">Aggressive</option>
                <option value="base">Base</option>
            </select>
        </dd>

        <dt><label for="services_ipsec_tunnel_p1_id_type">Identifier type</label></dt>
        <dd>
            <select name="services_ipsec_tunnel_p1_id_type" id="services_ipsec_tunnel_p1_id_type">
                <option value="myipaddr">My IP address</option>
                <option value="ipaddr">IP address</option>
                <option value="fqdn">Domain name</option>
                <option value="user_fqdn">User FQDN</option>
                <option value="dyn_dns">Dynamic DNS</option>
            </select>
        </dd>

        <dt><label for="services_ipsec_tunnel_p1_id">Identifier</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_p1_id" type="text" size="20" id="services_ipsec_tunnel_p1_id"/>
        </dd>

        <dt><label for="services_ipsec_tunnel_p1_encryption_alg">Encryption algorithm</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_p1_encryption_alg_des" type="checkbox" id="services_ipsec_tunnel_p1_encryption_alg_des" value="true"/>
            <label for="services_ipsec_tunnel_p1_encryption_alg_des">DES</label>
            <input name="services_ipsec_tunnel_p1_encryption_alg_3des" type="checkbox" id="services_ipsec_tunnel_p1_encryption_alg_3des" value="true"/>
            <label for="services_ipsec_tunnel_p1_encryption_alg_3des">3DES</label>
            <input name="services_ipsec_tunnel_p1_encryption_alg_blowfish" type="checkbox" id="services_ipsec_tunnel_p1_encryption_alg_blowfish" value="true"/>
            <label for="services_ipsec_tunnel_p1_encryption_alg_blowfish">Blowfish</label>
            <input name="services_ipsec_tunnel_p1_encryption_alg_cast128" type="checkbox" id="services_ipsec_tunnel_p1_encryption_alg_cast128" value="true"/>
            <label for="services_ipsec_tunnel_p1_encryption_alg_cast128">CAST128</label>
            <input name="services_ipsec_tunnel_p1_encryption_alg_aes" type="checkbox" id="services_ipsec_tunnel_p1_encryption_alg_aes" value="true"/>
            <label for="services_ipsec_tunnel_p1_encryption_alg_aes">AES</label>
            <input name="services_ipsec_tunnel_p1_encryption_alg_aes256" type="checkbox" id="services_ipsec_tunnel_p1_encryption_alg_aes256" value="true"/>
            <label for="services_ipsec_tunnel_p1_encryption_alg_aes256">AES 256</label>
        </dd>

        <dt><label for="services_ipsec_tunnel_p1_hash_alg">Hashing algorithm</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_p1_hashing_alg_sha1" type="checkbox" id="services_ipsec_tunnel_p1_hashing_alg_sha1" value="true"/>
            <label for="services_ipsec_tunnel_p1_hashing_alg_sha1">SHA1</label>
            <input name="services_ipsec_tunnel_p1_hashing_alg_md5" type="checkbox" id="services_ipsec_tunnel_p1_hashing_alg_md5" value="true"/>
            <label for="services_ipsec_tunnel_p1_hashing_alg_md5">MD5</label>
        </dd>

        <dt><label for="services_ipsec_tunnel_p1_dh_keygroup">DH key group</label></dt>
        <dd>
            <select name="services_ipsec_tunnel_p1_dh_keygroup" id="services_ipsec_tunnel_p1_dh_keygroup">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="5">5</option>
            </select>
        </dd>

        <dt><label for="services_ipsec_tunnel_p1_lifetime">Lifetime</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_p1_lifetime" type="text" size="2" id="services_ipsec_tunnel_p1_lifetime"/> seconds
        </dd>

        <dt><label for="services_ipsec_tunnel_p1_auth_method">Authentication method</label></dt>
        <dd>
            <select name="services_ipsec_tunnel_p1_auth_method" id="services_ipsec_tunnel_p1_auth_method">
                <option value="psk">Pre-shared key</option>
                <option value="rsasig">RSA signature</option>
            </select>
        </dd>

        <dt class="services_ipsec_tunnel_p1_preshared_key"><label for="services_ipsec_tunnel_p1_preshared_key">Pre-shared key</label></dt>
        <dd class="services_ipsec_tunnel_p1_preshared_key">
            <select name="services_ipsec_tunnel_p1_preshared_key" id="services_ipsec_tunnel_p1_preshared_key">
            </select>
        </dd>

        <dt class="services_ipsec_tunnel_p1_rsa_sig"><label for="services_ipsec_tunnel_p1_rsa_sig">RSA signature</label></dt>
        <dd class="services_ipsec_tunnel_p1_rsa_sig">
            <select name="services_ipsec_tunnel_p1_rsa_sig" id="services_ipsec_tunnel_p1_rsa_sig">
            </select>
        </dd>
    </dl>

    <p style="clear: both;"></p>

    <h3>Phase 2: Proposal (SA / key exchange)</h3>

    <dl>
        <dt><label for="services_ipsec_tunnel_p2_protocol">Protocol</label></dt>
        <dd>
            <select name="services_ipsec_tunnel_p2_protocol" id="services_ipsec_tunnel_p2_protocol">
                <option value="esp">ESP</option>
                <option value="ah">AH</option>
                <option value="esp_ah">ESP &amp; AH</option>
            </select>
        </dd>

        <dt><label for="services_ipsec_tunnel_p2_encrypt_algs">Encryption algorithms</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_p2_encryption_alg_des" type="checkbox" id="services_ipsec_tunnel_p2_encryption_alg_des" value="true"/>
            <label for="services_ipsec_tunnel_p2_encryption_alg_des">DES</label>
            <input name="services_ipsec_tunnel_p2_encryption_alg_3des" type="checkbox" id="services_ipsec_tunnel_p2_encryption_alg_3des" value="true"/>
            <label for="services_ipsec_tunnel_p2_encryption_alg_3des">3DES</label>
            <input name="services_ipsec_tunnel_p2_encryption_alg_blowfish" type="checkbox" id="services_ipsec_tunnel_p2_encryption_alg_blowfish" value="true"/>
            <label for="services_ipsec_tunnel_p2_encryption_alg_blowfish">Blowfish</label>
            <input name="services_ipsec_tunnel_p2_encryption_alg_cast128" type="checkbox" id="services_ipsec_tunnel_p2_encryption_alg_cast128" value="true"/>
            <label for="services_ipsec_tunnel_p2_encryption_alg_cast128">CAST128</label>
            <input name="services_ipsec_tunnel_p2_encryption_alg_aes" type="checkbox" id="services_ipsec_tunnel_p2_encryption_alg_aes" value="true"/>
            <label for="services_ipsec_tunnel_p2_encryption_alg_aes">AES</label>
            <input name="services_ipsec_tunnel_p2_encryption_alg_aes256" type="checkbox" id="services_ipsec_tunnel_p2_encryption_alg_aes256" value="true"/>
            <label for="services_ipsec_tunnel_p2_encryption_alg_aes256">AES 256</label>
        </dd>

        <dt><label for="services_ipsec_tunnel_p2_pfs_keygroup">PFS key group</label></dt>
        <dd>
            <select name="services_ipsec_tunnel_p2_pfs_keygroup" id="services_ipsec_tunnel_p2_pfs_keygroup">
                <option value="off">Off</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="5">5</option>
            </select>
        </dd>

        <dt><label for="services_ipsec_tunnel_p2_lifetime">Lifetime</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_p2_lifetime" type="text" size="2" id="services_ipsec_tunnel_p2_lifetime"/> seconds
        </dd>

        <dt><label for="services_ipsec_tunnel_p2_auth_alg">Authentication algorithm</label></dt>
        <dd>
            <input name="services_ipsec_tunnel_p2_auth_alg_des" type="checkbox" id="services_ipsec_tunnel_p2_auth_alg_des" value="true"/>
            <label for="services_ipsec_tunnel_p2_auth_alg_des">DES</label>
            <input name="services_ipsec_tunnel_p2_auth_alg_3des" type="checkbox" id="services_ipsec_tunnel_p2_auth_alg_3des" value="true"/>
            <label for="services_ipsec_tunnel_p2_auth_alg_3des">3DES</label>
            <input name="services_ipsec_tunnel_p2_auth_alg_md5" type="checkbox" id="services_ipsec_tunnel_p2_auth_alg_md5" value="true"/>
            <label for="services_ipsec_tunnel_p2_auth_alg_md5">MD5</label>
            <input name="services_ipsec_tunnel_p2_auth_alg_sha1" type="checkbox" id="services_ipsec_tunnel_p2_auth_alg_sha1" value="true"/>
            <label for="services_ipsec_tunnel_p2_auth_alg_sha1">SHA1</label>
        </dd>
        
        <dt><input type="submit" value="Add tunnel" id="services_ipsec_tunnel_submit" class="submitbutton"/></dt>
    </dl>

    <p style="clear: both;"></p>
</form>

<script type="text/javascript">
    gp.services.ipsec.tunnels.buildTable = function() {
        //Clear the current table data to (re)load it
        $('#services_ipsec_tunnel_tbody').empty();
        $.each(gp.data.ipsec_tunnels, function(id, rule) {
            gp.services.ipsec.tunnels.addRule(rule);
        });
    };

    //Add a rule to the table
    gp.services.ipsec.tunnels.addRule = function(rule) {
        var tblstring = '<tr>'+
            '<td><img src="images/icons/'+(rule.enable.toLowerCase()=='true'?'en':'dis')+'abled.png" alt="'+(rule.enable.toLowerCase()=='true'?'en':'dis')+'abled" title="Tunnel '+(rule.enable.toLowerCase()=='true'?'en':'dis')+'abled"/></td>'+
            '<td>'+rule.local.private_ip+(rule.local.private_subnet?' / '+rule.local.private_subnet:'')+'</td>'+
            '<td>'+rule.remote.private_ip+(rule.remote.private_subnet?' / '+rule.remote.private_subnet:'')+'</td>'+
            '<td>'+rule.local.public_ip+'</td>'+
            '<td>'+rule.remote.public_ip+'</td>'+
            '<td>'+rule.description+'</td>'+
            '<td><a href="#services_ipsec" rel="'+rule.id+'" class="toggle_ipsec_tunnel" title="'+(rule.enable.toLowerCase()=='true'?'Enable':'Disable')+' tunnel"><img src="images/icons/rule_'+(rule.enable.toLowerCase()=='true'?'on':'off')+'.png" alt="delete"/></a></td>'+
            '<td><a href="#services_ipsec" rel="'+rule.id+'" class="edit_ipsec_tunnel" title="Edit tunnel"><img src="images/icons/edit.png" alt="edit"/></a></td>'+
            '<td><a href="#services_ipsec" rel="'+rule.id+'" class="delete_ipsec_tunnel" title="Delete tunnel"><img src="images/icons/delete.png" alt="delete"/></a></td>'+
            '</tr>';
        $('#services_ipsec_tunnel_tbody').append(tblstring);
    };

    gp.services.ipsec.tunnels.resetForm = function() {
        gp.services.ipsec.tunnels.loadKeys();
        gp.services.ipsec.tunnels.loadCertificates();
        gp.resetForm('services_ipsec_tunnel_form');
    };

    gp.services.ipsec.tunnels.loadKeys = function() {
        var opts = '';
        $.each(gp.data.ipsec_keys, function(i, key) {
            opts += '<option value="'+key.id+'">'+key.description+'</option>';
        });
        $('#services_ipsec_tunnel_p1_preshared_key, #services_ipsec_tunnel_p2_preshared_key').html(opts);
    };

    gp.services.ipsec.tunnels.loadCertificates = function() {
        var opts = '';
        $.each(gp.data.ipsec_certificates, function(i, certif) {
            opts += '<option value="'+certif.id+'">'+certif.description+'</option>';
        });
        $('#services_ipsec_tunnel_p1_rsa_sig, #services_ipsec_tunnel_p2_rsa_sig').html(opts);
    };

    //Load a rule into the tunnel form
    gp.services.ipsec.tunnels.formLoadRule = function(rule) {
        gp.services.ipsec.tunnels.resetForm();
        $('#services_ipsec_tunnel_form_page').val('edittunnel');
        $('#services_ipsec_tunnel_id').val(rule.id);
        $('#services_ipsec_tunnel_submit').val('Edit tunnel');
        $('#services_ipsec_tunnel_form').dialog('option', 'title', 'Edit tunnel');

        $('#services_ipsec_tunnel_local_subnet_type').val(rule.local.type.toLowerCase());
        $('#services_ipsec_tunnel_local_subnet_ipaddr').val(rule.local.private_ip);
        $('#services_ipsec_tunnel_local_subnet_subnet').val(rule.local.private_subnet);

        $('#services_ipsec_tunnel_remote_subnet_type').val(rule.remote.type.toLowerCase());
        $('#services_ipsec_tunnel_remote_subnet_ipaddr').val(rule.remote.private_ip);
        $('#services_ipsec_tunnel_remote_subnet_subnet').val(rule.remote.private_subnet);

        $('#services_ipsec_tunnel_local_gateway').val(rule.local.public_ip);
        $('#services_ipsec_tunnel_remote_gateway').val(rule.remote.public_ip);

        if (rule.keepalive_ip && rule.keepalive_ip.toLowerCase() == '') {
            $('#services_ipsec_tunnel_send_keepalive').attr('checked', 'checked');
            $('#services_ipsec_tunnel_keepalive_ipaddr').removeAttr('disabled');
            $('#services_ipsec_tunnel_keepalive_ipaddr').val(rule.keepalive_ip);
        }

        $('#services_ipsec_tunnel_descr').val(rule.description);

        $('#services_ipsec_tunnel_p1_negotiation_mode').val(rule.phase1.mode.toLowerCase());
        $('#services_ipsec_tunnel_p1_id_type').val(rule.phase1.identifier.type.toLowerCase());
        $('#services_ipsec_tunnel_p1_id').val(rule.phase1.identifier);
        
        if (rule.phase1.encryption_algorithm) {
            var enc = rule.phase1.encryption_algorithm.split('|');
            for (var i = 0; i < enc.length; i++) {
                enc[i] = enc[i].trim().toLowerCase();
            }
            if (enc.indexOf('des') >= 0) {
                $('#services_ipsec_tunnel_p1_encryption_alg_des').attr('checked', 'checked');
            }
            if (enc.indexOf('3des') >= 0) {
                $('#services_ipsec_tunnel_p1_encryption_alg_3des').attr('checked', 'checked');
            }
            if (enc.indexOf('blowfish') >= 0) {
                $('#services_ipsec_tunnel_p1_encryption_alg_blowfish').attr('checked', 'checked');
            }
            if (enc.indexOf('cast128') >= 0) {
                $('#services_ipsec_tunnel_p1_encryption_alg_cast128').attr('checked', 'checked');
            }
            if (enc.indexOf('aes') >= 0) {
                $('#services_ipsec_tunnel_p1_encryption_alg_aes').attr('checked', 'checked');
            }
            if (enc.indexOf('aes256') >= 0) {
                $('#services_ipsec_tunnel_p1_encryption_alg_aes256').attr('checked', 'checked');
            }
        }
        
        if (rule.phase1.hash_algorithm) {
            var hash = rule.phase1.hash_algorithm.split('|');
            for (var i = 0; i < hash.length; i++) {
                hash[i] = hash[i].trim().toLowerCase();
            }
            if (hash.indexOf('sha1') >= 0) {
                $('#services_ipsec_tunnel_p1_hashing_alg_sha1').attr('checked', 'checked');
            }
            if (hash.indexOf('md5') >= 0) {
                $('#services_ipsec_tunnel_p1_hashing_alg_md5').attr('checked', 'checked');
            }
        }

        $('#services_ipsec_tunnel_p1_dh_keygroup').val(rule.phase1.dhgroup);
        $('#services_ipsec_tunnel_p1_lifetime').val(rule.phase1.lifetime);
        $('#services_ipsec_tunnel_p1_auth_method').val(rule.phase1.authentication_method.type).trigger('change');
        if (rule.phase1.authentication_method.type == 'rsasig') {
            // .text is needed here because the xml2json class creates weird json here.
            $('#services_ipsec_tunnel_p1_rsa_sig').val(rule.phase1.authentication_method.text);
        } else {
            $('#services_ipsec_tunnel_p1_preshared_key').val(rule.phase1.authentication_method.text);
        }

        $('#services_ipsec_tunnel_p2_protocol').val(rule.phase2.protocol);
        if (rule.phase2.encryption_algorithm) {
            var enc = rule.phase2.encryption_algorithm.split('|');
            for (var i = 0; i < enc.length; i++) {
                enc[i] = enc[i].trim().toLowerCase();
            }
            if (enc.indexOf('des') >= 0) {
                $('#services_ipsec_tunnel_p2_encryption_alg_des').attr('checked', 'checked');
            }
            if (enc.indexOf('3des') >= 0) {
                $('#services_ipsec_tunnel_p2_encryption_alg_3des').attr('checked', 'checked');
            }
            if (enc.indexOf('blowfish') >= 0) {
                $('#services_ipsec_tunnel_p2_encryption_alg_blowfish').attr('checked', 'checked');
            }
            if (enc.indexOf('cast128') >= 0) {
                $('#services_ipsec_tunnel_p2_encryption_alg_cast128').attr('checked', 'checked');
            }
            if (enc.indexOf('aes') >= 0) {
                $('#services_ipsec_tunnel_p2_encryption_alg_aes').attr('checked', 'checked');
            }
            if (enc.indexOf('aes256') >= 0) {
                $('#services_ipsec_tunnel_p2_encryption_alg_aes256').attr('checked', 'checked');
            }
        }
        $('#services_ipsec_tunnel_p2_pfs_keygroup').val(rule.phase2.pfsgroup);
        $('#services_ipsec_tunnel_p2_lifetime').val(rule.phase2.lifetime);

        if (rule.phase2.authentication_algorithm) {
            var enc = rule.phase2.authentication_algorithm.split('|');
            for (var i = 0; i < enc.length; i++) {
                enc[i] = enc[i].trim().toLowerCase();
            }
            if (enc.indexOf('des') >= 0) {
                $('#services_ipsec_tunnel_p2_auth_alg_des').attr('checked', 'checked');
            }
            if (enc.indexOf('3des') >= 0) {
                $('#services_ipsec_tunnel_p2_auth_alg_3des').attr('checked', 'checked');
            }
            if (enc.indexOf('hmac_md5') >= 0) {
                $('#services_ipsec_tunnel_p2_auth_alg_md5').attr('checked', 'checked');
            }
            if (enc.indexOf('hmac_sha1') >= 0) {
                $('#services_ipsec_tunnel_p2_auth_alg_sha1').attr('checked', 'checked');
            }
        }
    };

    $(function() {
        $('#services_ipsec_tunnel_form').dialog({
            autoOpen: false,
            resizable: false,
            width: 800,
            modal: true
        });
        $('#services_ipsec_tunnel_add_link').click(function() {
            gp.services.ipsec.tunnels.resetForm();
            $('#services_ipsec_tunnel_form_page').val('addtunnel');
            $('#services_ipsec_tunnel_id').val(false);
            $('#services_ipsec_tunnel_submit').val('Add tunnel');
            $('#services_ipsec_tunnel_form').dialog('option', 'title', 'Add new tunnel');
            $('#services_ipsec_tunnel_form').dialog('open');
            return false;
        });

        //Handler for submitting the form
        $('#services_ipsec_tunnel_form').submit(function() {
            gp.doFormAction({
                form_id: 'services_ipsec_tunnel_form',
                error_element: $('#services_ipsec_tunnel_form_error'),
                successFn: function(json) {
                    gp.data.ipsec_tunnels[json.ipsec.tunnels.tunnel.id] = json.ipsec.tunnels.tunnel;
                    gp.services.ipsec.tunnels.buildTable();
                    $('#services_ipsec_tunnel_form').dialog('close');
                }
            });
            return false;
        });

        //Click handler(s) for editing
        //Live handler because edit button doesn't exist on document.ready
        $('.edit_ipsec_tunnel').live('click', function() {
            var rule = gp.data.ipsec_tunnels[$(this).attr('rel')];
            gp.services.ipsec.tunnels.formLoadRule(rule);
            $('#services_ipsec_tunnel_submit').val('Edit tunnel');
            $('#services_ipsec_tunnel_form').dialog('option', 'title', 'Edit tunnel');
            $('#services_ipsec_tunnel_form').dialog('open');
            return false;
        });

        //Click handler for toggling rule on/off
        $('.toggle_ipsec_tunnel').live('click', function() {
            var id = $(this).attr('rel');
            gp.doAction({
                url: 'testxml/reply.xml',
                module: 'Ipsec',
                page: 'toggletunnel',
                params: {
                    tunnelid: id
                },
                error_element: $('#services_ipsec_tunnel_table_error'),
                content_id: 'cp_services_ipsec_tunnels',
                successFn: function(json) {
                    var enabled = gp.data.ipsec_tunnels[id].enable;
                    gp.data.ipsec_tunnels[id].enable = (enabled == 'true' ? 'false' : 'true');
                    gp.services.ipsec.tunnels.buildTable();
                }
            });
            return false;
        });

        //Click handler for deleting rule
        $('.delete_ipsec_tunnel').live('click', function() {
            var id = $(this).attr('rel');
            gp.confirm("Are you sure?", "Are you sure you want to delete this tunnel?", function() {
                gp.doAction({
                    url: 'testxml/reply.xml',
                    module: 'Ipsec',
                    page: 'deletetunnel',
                    params: {
                        tunnelid: id
                    },
                    error_element: $('#services_ipsec_tunnel_table_error'),
                    content_id: 'cp_services_ipsec_tunnels',
                    successFn: function(json) {
                        delete gp.data.ipsec_tunnels[id];
                        gp.services.ipsec.tunnels.buildTable();
                    }
                });
            });
            return false;
        });

        $('#services_ipsec_tunnel_send_keepalive').click(function(){
            if ($(this).attr('checked')) {
                $('#services_ipsec_tunnel_keepalive_ipaddr').removeAttr('disabled');
            } else {
                $('#services_ipsec_tunnel_keepalive_ipaddr').attr('disabled', 'disabled');
            }
        });

        $('#services_ipsec_tunnel_local_subnet_type').change(function(){
            switch (this.value) {
                case 'lan_subnet':
                    $('#services_ipsec_tunnel_local_subnet_ipaddr').attr('disabled', 'disabled');
                    $('#services_ipsec_tunnel_local_subnet_subnet').attr('disabled', 'disabled');
                    break;
                case 'ipaddr':
                    $('#services_ipsec_tunnel_local_subnet_ipaddr').removeAttr('disabled');
                    $('#services_ipsec_tunnel_local_subnet_subnet').attr('disabled', 'disabled');
                    break;
                case 'network':
                    $('#services_ipsec_tunnel_local_subnet_ipaddr').removeAttr('disabled');
                    $('#services_ipsec_tunnel_local_subnet_subnet').removeAttr('disabled');
                    break;
            }
        });

        $('#services_ipsec_tunnel_remote_subnet_type').change(function(){
            switch (this.value) {
                case 'ipaddr':
                    $('#services_ipsec_tunnel_remote_subnet_ipaddr').removeAttr('disabled');
                    $('#services_ipsec_tunnel_remote_subnet_subnet').attr('disabled', 'disabled');
                    break;
                case 'network':
                    $('#services_ipsec_tunnel_remote_subnet_ipaddr').removeAttr('disabled');
                    $('#services_ipsec_tunnel_remote_subnet_subnet').removeAttr('disabled');
                    break;
            }
        });

        $('#services_ipsec_tunnel_p1_id_type').change(function(){
            switch (this.value) {
                case 'ipaddr':
                case 'user_fqdn':
                    $('#services_ipsec_tunnel_p1_id').removeAttr('disabled');
                    break;
                default:
                    $('#services_ipsec_tunnel_p1_id').attr('disabled', 'disabled');
                    break;
            }
        });

        $('#services_ipsec_tunnel_p1_auth_method').change(function(){
            switch (this.value) {
                case 'psk':
                    $('#services_ipsec_tunnel_p1_preshared_key').removeAttr('disabled');
                    $('#services_ipsec_tunnel_p1_rsa_sig').attr('disabled', 'disabled');
                    $('.services_ipsec_tunnel_p1_rsa_sig').hide();
                    $('.services_ipsec_tunnel_p1_preshared_key').fadeIn();
                    
                    break;
                case 'rsasig':
                    $('#services_ipsec_tunnel_p1_preshared_key').attr('disabled', 'disabled');
                    $('#services_ipsec_tunnel_p1_rsa_sig').removeAttr('disabled');
                    $('.services_ipsec_tunnel_p1_preshared_key').hide();
                    $('.services_ipsec_tunnel_p1_rsa_sig').fadeIn();
                    break;
            }
        });
    });
</script>