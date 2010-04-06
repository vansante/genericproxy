<dl class="form_sub">
    <dt><label for="<?=$this->ip_id?>_not">NOT</label></dt>
    <dd><input name="<?=$this->ip_id?>_not" type="checkbox" id="<?=$this->ip_id?>_not" value="true"/></dd>

    <dt><label for="<?=$this->ip_id?>_type">Type</label></dt>
    <dd>
        <select name="<?=$this->ip_id?>_type" id="<?=$this->ip_id?>_type">
            <option value="any" selected="selected">any</option>
            <option value="address">Single host or alias</option>
            <option value="network">Network</option>
            <option value="wan_address">WAN address</option>
            <option value="lan_subnet">LAN subnet</option>
            <option value="ext_subnet">EXT subnet</option>
            <option value="lan">LAN</option>
            <option value="wan">WAN</option>
        </select>
    </dd>

    <dt><label for="<?=$this->ip_id?>_address">IP address</label></dt>
    <dd>
        <input name="<?=$this->ip_id?>_address" type="text" size="12" id="<?=$this->ip_id?>_address"/>
        /
        <select name="<?=$this->ip_id?>_subnet" id="<?=$this->ip_id?>_subnet">
        <? for ($i = 32; $i >= 0; $i--) : ?>
            <option value="<?=$i?>"><?=$i?></option>
        <? endfor; ?>
        </select>
    </dd>
</dl>

<script type="text/javascript">
    $(function() {
        var enableFn = function(value) {
            switch ($('#<?=$this->ip_id?>_type').val()) {
                case 'address':
                    $('#<?=$this->ip_id?>_address').removeAttr('disabled');
                    $('#<?=$this->ip_id?>_subnet').attr('disabled', 'disabled');
                    break;
                case 'network':
                    $('#<?=$this->ip_id?>_address').removeAttr('disabled');
                    $('#<?=$this->ip_id?>_subnet').removeAttr('disabled');
                    break;
                default:
                    $('#<?=$this->ip_id?>_address').attr('disabled', 'disabled');
                    $('#<?=$this->ip_id?>_subnet').attr('disabled', 'disabled');
                    break;
            }
        }
        enableFn();
        $('#<?=$this->ip_id?>_type').change(enableFn);
    });
</script>
