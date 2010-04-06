    var portFound = false;
    $('#<?=$this->load_port_id?> option').each(function() {
        if (<?=$this->load_port_jsvar?> == this.value) {
            $('#<?=$this->load_port_id?>').val(<?=$this->load_port_jsvar?>);
            portFound = true;
        }
    });
    if (portFound) {
        $('#<?=$this->load_port_id?>_custom').attr('readonly', 'readonly');
    } else {
        $('#<?=$this->load_port_id?>_custom').removeAttr('readonly');
    }
    $('#<?=$this->load_port_id?>_custom').val(<?=$this->load_port_jsvar?>);
