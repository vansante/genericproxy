<? $id = 'firewall_rules_'.$this->rules_template_id; ?>

<div class="note">
    <h3>Note:</h3>
    <p>Rules are evaluated on a first-match basis (i.e. the action of the first rule to match a packet will be executed). This means that if you use block rules, you'll have to pay attention to the rule order. Everything that isn't explicitly passed is blocked by default.</p>
</div>

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
    <a class="icon_add firewall_rules_apply_link" href="#firewall_rules" rel="<?=$this->rules_template_id?>">Apply changes</a>
</p>