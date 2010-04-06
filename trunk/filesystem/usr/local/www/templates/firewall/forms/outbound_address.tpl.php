<dl class="form_sub">
    <dt><label for="<?=$this->out_addr_id?>_type">Type</label></dt>
    <dd>
        <select name="<?=$this->out_addr_id?>_type" id="<?=$this->out_addr_id?>_type">
            <option value="any">Any</option>
            <option value="interface">Interface</option>
            <option value="address">IP address</option>
        </select>
    </dd>

    <dt><label for="<?=$this->out_addr_id?>_interface">Interface</label></dt>
    <dd>
        <select name="<?=$this->out_addr_id?>_interface" id="<?=$this->out_addr_id?>_interface">
            <option value="wan">WAN</option>
            <option value="lan">LAN</option>
            <option value="ext">EXT</option>
        </select>
    </dd>

    <dt><label for="<?=$this->out_addr_id?>_ipaddr">IP address</label></dt>
    <dd>
        <input name="<?=$this->out_addr_id?>_ipaddr" size="12" type="text" id="<?=$this->out_addr_id?>_ipaddr"/>
    </dd>
</dl>

<script type="text/javascript">
    $(function() {
        $('#<?=$this->out_addr_id?>_type').change(function(){
            switch (this.value) {
                case 'any':
                    $('#<?=$this->out_addr_id?>_interface').attr('disabled', 'disabled');
                    $('#<?=$this->out_addr_id?>_ipaddr').attr('disabled', 'disabled');
                    break;
                case 'interface':
                    $('#<?=$this->out_addr_id?>_interface').removeAttr('disabled');
                    $('#<?=$this->out_addr_id?>_ipaddr').attr('disabled', 'disabled');
                    break;
                case 'address':
                    $('#<?=$this->out_addr_id?>_interface').attr('disabled', 'disabled');
                    $('#<?=$this->out_addr_id?>_ipaddr').removeAttr('disabled');
                    break;
                default:
                    $('#<?=$this->out_addr_id?>_interface').attr('disabled', 'disabled');
                    $('#<?=$this->out_addr_id?>_ipaddr').attr('disabled', 'disabled');
                    break;
            }
        });
    });
</script>