    if (<?=$this->js_var?>.toLowerCase() == 'any') {
        $('#<?=$this->out_addr_id?>_type').val(<?=$this->js_var?>.toLowerCase());
    } else if (['ext','lan','wan'].indexOf(<?=$this->js_var?>.toLowerCase()) != -1) {
        console.log(2)
        $('#<?=$this->out_addr_id?>_type').val('interface');
        $('#<?=$this->out_addr_id?>_interface').removeAttr('disabled');
        $('#<?=$this->out_addr_id?>_interface').val(<?=$this->js_var?>.toLowerCase());
    } else {
        $('#<?=$this->out_addr_id?>_type').val('address');
        $('#<?=$this->out_addr_id?>_ipaddr').removeAttr('disabled');
        $('#<?=$this->out_addr_id?>_ipaddr').val(<?=$this->js_var?>.toLowerCase());
    }