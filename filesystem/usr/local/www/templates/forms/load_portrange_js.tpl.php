        var ports = <?=$this->load_portrange_jsvar?>.split(':');
        <?
        $this->load_port_id = $this->load_portrange_id.'_from';
        $this->load_port_jsvar = 'ports[0]';
        include $this->template('forms/load_port_js.tpl.php');
        ?>
        if (ports.length > 1) {
            <?
            $this->load_port_id = $this->load_portrange_id.'_to';
            $this->load_port_jsvar = 'ports[1]';
            include $this->template('forms/load_port_js.tpl.php');
            ?>
        }
        