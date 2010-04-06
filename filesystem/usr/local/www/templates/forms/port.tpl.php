<dt><label for="<?=$this->port_id?>"><?=$this->port_label?></label></dt>
<dd>
    <select name="<?=$this->port_id?>" id="<?=$this->port_id?>">
        <option value="">(other)</option>
        <option value="21">FTP</option>
        <option value="22">SSH</option>
        <option value="23">Telnet</option>
        <option value="25">SMTP</option>
        <option value="53">DNS</option>
        <option value="80">HTTP</option>
        <option value="110">POP3</option>
        <option value="143">IMAP</option>
        <option value="443">HTTPS</option>
    </select>
    <input name="<?=$this->port_id?>_custom" size="3" id="<?=$this->port_id?>_custom" type="text"/>
</dd>

<script type="text/javascript">
    $(function() {
        $('#<?=$this->port_id?>').change(function() {
            if (this.value == '') {
                $('#<?=$this->port_id?>_custom').removeAttr('readonly');
            } else {
                $('#<?=$this->port_id?>_custom').attr('readonly', 'readonly');
                $('#<?=$this->port_id?>_custom').val(this.value);
            }
        });
    });
</script>
