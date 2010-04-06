<h2 class="help_anchor"><a class="open_all_help" rel="cp_interfaces_ext_ext"></a>EXT interface</h2>

<p class="intro">In the extern sub section, it is possible to set up all the parameters for the EXT interface. The EXT interface can be a static IP address or a DHCP address as detailed in the following. On the basis of the connection type selected, the related sub panel must be filled.</p>

<?
$this->ipconfig_id = 'interfaces_ext';
$this->ipconfig_module = 'Ext';
include $this->template('interfaces/forms/ipconfig.tpl.php');
?>