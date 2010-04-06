<dl class="form_sub">
    <?
    $this->port_id = $this->portrange_id.'_from';
    $this->port_label = 'From';
    include $this->template('forms/port.tpl.php');
    ?>

    <?
    $this->port_id = $this->portrange_id.'_to';
    $this->port_label = 'To';
    include $this->template('forms/port.tpl.php');
    ?>
</dl>