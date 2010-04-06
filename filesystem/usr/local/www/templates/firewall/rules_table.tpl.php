<? $id = 'firewall_rules_'.$this->rules_template_id; ?>

<div class="form-error" id="<?=$id?>_table_error">
</div>

<table id="<?=$id?>_table">
    <thead>
        <tr>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
            <th>Protocol</th>
            <th>Source</th>
            <th>Port</th>
            <th>Destination</th>
            <th>Port</th>
            <th>Description</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
            <th width="16">&nbsp;</th>
        </tr>
    </thead>
    <tbody id="<?=$id?>_tbody">

    </tbody>
</table>

<p>
    <a class="icon_add firewall_rules_add_link" href="#firewall_rules" rel="<?=$this->rules_template_id?>">Add new rule</a>
</p>