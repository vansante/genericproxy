<h2 class="help_anchor"><a class="open_all_help" rel="cp_interfaces_assign_assign"></a>Assign interfaces</h2>
<p class="intro">The assign menu allows to map the symbolic reference LAN and WAN to the physical interfaces that are present on the system. Click on the Save button to apply changes, and remember that a change in this assignment will require a system reboot for the changes to take effect.</p>

<form id="interfaces_assign_form" action="ajaxserver.php" method="post">
    <div class="form-error" id="interfaces_assign_form_error">
    </div>

    <input type="hidden" name="module" value="AssignInterfaces"/>
    <input type="hidden" name="page" value="save" id="interfaces_assign_form_page"/>

    <dl>
        <dt><label for="interfaces_assign_lan">LAN</label></dt>
        <dd>
            <select name="interfaces_assign_lan" id="interfaces_assign_lan">
            </select>
        </dd>

        <dt><label for="interfaces_assign_wan">WAN</label></dt>
        <dd>
            <select name="interfaces_assign_wan" id="interfaces_assign_wan">
            </select>
        </dd>

        <dt><label for="interfaces_assign_ext">EXT</label></dt>
        <dd>
            <select name="interfaces_assign_ext" id="interfaces_assign_ext">
            </select>
        </dd>

        <dt><input type="submit" value="Save" id="interfaces_assign_submit" class="submitbutton"/></dt>
    </dl>

    <p style="clear: both;"></p>
</form>

<div class="help_pool">
    <div class="help" id="help_interfaces_assign_lan">Select the device to assign to the LAN interface here.</div>
    <div class="help" id="help_interfaces_assign_wan">Select the device to assign to the WAN interface here.</div>
    <div class="help" id="help_interfaces_assign_ext">Select the device to assign to the EXT interface here.</div>
</div>