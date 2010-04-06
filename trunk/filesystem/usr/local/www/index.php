<?php
/**
 * @author Paul van Santen, Douwe Kasemier
 */

session_start();

require_once('json_encode.php');

require_once('Savant3.php');
$tpl = new Savant3();
$tpl->setPath('template', 'templates');
//$tpl->addFilters(array('Savant3_Filter_trimwhitespace', 'filter'));

// Login tonen
if (empty($_SESSION['uid'])) {
    $tpl->display('login.tpl.php');
    /*
     * DEBUG, REMOVE LATER !!!!!!!!!
     * IK HEB HET HIER GESTOPT ZODAT JE EERSTE KEER ALTIJD LOGIN KRIJGT.
     * EN NA REFRESHEN REST.
     */
    $_SESSION['uid'] = 1;
} else {
    $tpl->modules = array (
        'status' => array(
            'name' => 'Status',
            'key' => 'status',
            'pages' => array(
                'system' => array(
                    'name' => 'System',
                    'key' => 'system',
                    'tabs' => array(
                        'system' => array(
                            'name' => 'System',
                            'key' => 'system'
                        )
                    )
                ),
                'services' => array(
                    'name' => 'Services',
                    'key' => 'services',
                    'tabs' => array(
                        'services' => array(
                            'name' => 'Services',
                            'key' => 'services'
                        )
                    )
                ),

                'ifaces' => array(
                    'name' => 'Interfaces',
                    'key' => 'ifaces',
                    'tabs' => array(
                        'wan' => array(
                            'name' => 'WAN',
                            'key' => 'wan'
                        ),
                        'lan' => array(
                            'name' => 'LAN',
                            'key' => 'lan'
                        ),
                        'ext' => array(
                            'name' => 'EXT',
                            'key' => 'ext'
                        )
                    )
                ),
                'dhcp' => array(
                    'name' => 'DHCP',
                    'key' => 'dhcp',
                    'tabs' => array(
                        'dhcp' => array(
                            'name' => 'DHCP',
                            'key' => 'dhcp'
                        )
                    )
                ),
                'traffic' => array(
                    'name' => 'Traffic',
                    'key' => 'traffic',
                    'tabs' => array(
                        'wan' => array(
                            'name' => 'WAN',
                            'key' => 'wan'
                        ),
                        'lan' => array(
                            'name' => 'LAN',
                            'key' => 'lan'
                        ),
                        'ext' => array(
                            'name' => 'EXT',
                            'key' => 'ext'
                        )
                    )
                ),
                'ipsec' => array(
                    'name' => 'IPSec',
                    'key' => 'ipsec',
                    'tabs' => array(
                        'ipsec' => array(
                            'name' => 'IPSec',
                            'key' => 'ipsec'
                        )
                    )
                )
            )
        ),
        'system' => array(
            'name' => 'System',
            'key' => 'system',
            'pages' => array(
                'genset' => array(
                    'name' => 'General settings',
                    'key' => 'genset',
                    'tabs' => array(
                        'genset' => array(
                            'name' => 'General settings',
                            'key' => 'genset',
                        )
                    )
                ),
                'firmwup' => array(
                    'name' => 'Firmware upgrade',
                    'key' => 'firmwup',
                    'tabs' => array(
                        'firmwup' => array(
                            'name' => 'Firmware upgrade',
                            'key' => 'firmwup',
                        )
                    )
                ),
                'reboot' => array(
                    'name' => 'Reboot',
                    'key' => 'reboot',
                    'tabs' => array(
                        'reboot' => array(
                            'name' => 'Reboot',
                            'key' => 'reboot',
                        )
                    )
                ),
                'reset' => array(
                    'name' => 'Reset',
                    'key' => 'reset',
                    'tabs' => array(
                        'reset' => array(
                            'name' => 'Reset',
                            'key' => 'reset',
                        )
                    )
                ),
                'backrest' => array(
                    'name' => 'Backup / restore',
                    'key' => 'backrest',
                    'tabs' => array(
                        'backrest' => array(
                            'name' => 'Backup / restore',
                            'key' => 'backrest',
                        )
                    )
                ),
            )
        ),
        'interfaces' => array(
            'name' => 'Interfaces',
            'key' => 'interfaces',
            'pages' => array(
                'assign' => array(
                    'name' => 'Assign',
                    'key' => 'assign',
                    'tabs' => array(
                        'assign' => array(
                            'name' => 'Assign',
                            'key' => 'assign',
                        )
                    )
                ),
                'lan' => array(
                    'name' => 'LAN',
                    'key' => 'lan',
                    'tabs' => array(
                        'lan' => array(
                            'name' => 'LAN',
                            'key' => 'lan',
                        )
                    )
                ),
                'wan' => array(
                    'name' => 'WAN',
                    'key' => 'wan',
                    'tabs' => array(
                        'wan' => array(
                            'name' => 'WAN',
                            'key' => 'wan',
                        )
                    )
                ),
                'ext' => array(
                    'name' => 'EXT',
                    'key' => 'ext',
                    'tabs' => array(
                        'ext' => array(
                            'name' => 'EXT',
                            'key' => 'ext',
                        )
                    )
                ),
            )
        ),
        'firewall' => array(
            'name' => 'Firewall',
            'key' => 'firewall',
            'pages' => array(
                'nat' => array(
                    'name' => 'NAT',
                    'key' => 'nat',
                    'tabs' => array(
                        'inbound' => array(
                            'name' => 'Inbound',
                            'key' => 'inbound'
                        ),
                        'outbound' => array(
                            'name' => 'Outbound',
                            'key' => 'outbound'
                        ),
                        '11nat' => array(
                            'name' => '1:1 NAT',
                            'key' => '11nat'
                        )
                    )
                ),
                'rules' => array(
                    'name' => 'Rules',
                    'key' => 'rules',
                    'tabs' => array(
                        'wan' => array(
                            'name' => 'WAN',
                            'key' => 'wan'
                        ),
                        'lan' => array(
                            'name' => 'LAN',
                            'key' => 'lan'
                        ),
                        'ext' => array(
                            'name' => 'EXT',
                            'key' => 'ext'
                        )
                    )
                )
            )
        ),
        'services' => array(
            'name' => 'Services',
            'key' => 'services',
            'pages' => array(
                'dhcpd' => array(
                    'name' => 'DHCPD',
                    'key' => 'dhcpd',
                    'tabs' => array(
                        'settings' => array(
                            'name' => 'Settings',
                            'key' => 'settings'
                        ),
                        'rules' => array(
                            'name' => 'Mappings',
                            'key' => 'rules'
                        )
                    )
                ),
                'dnsf' => array(
                    'name' => 'DNS forwarding',
                    'key' => 'dnsf',
                    'tabs' => array(
                        'settings' => array(
                            'name' => 'Settings',
                            'key' => 'settings'
                        ),
                        'masks' => array(
                            'name' => 'Masks',
                            'key' => 'masks'
                        ),
                        'overrides' => array(
                            'name' => 'Overrides',
                            'key' => 'overrides'
                        )
                    )
                ),
                'dyndns' => array(
                    'name' => 'Dynamic DNS',
                    'key' => 'dyndns',
                    'tabs' => array(
                        'dyndns' => array(
                            'name' => 'Dynamic DNS',
                            'key' => 'dyndns'
                        )
                    )
                ),
                'httpd' => array(
                    'name' => 'HTTPD',
                    'key' => 'httpd',
                    'tabs' => array(
                        'httpd' => array(
                            'name' => 'HTTPD',
                            'key' => 'httpd'
                        )
                    )
                ),
                'ipsec' => array(
                    'name' => 'IPSec',
                    'key' => 'ipsec',
                    'tabs' => array(
                        'settings' => array(
                            'name' => 'Settings',
                            'key' => 'settings'
                        ),
                        'tunnels' => array(
                            'name' => 'Tunnels',
                            'key' => 'tunnels'
                        ),
                        'keys' => array(
                            'name' => 'Keys',
                            'key' => 'keys'
                        ),
                        'certificates' => array(
                            'name' => 'Certificates',
                            'key' => 'certificates'
                        ),
                    )
                ),
                'ntp' => array(
                    'name' => 'NTP',
                    'key' => 'ntp',
                    'tabs' => array(
                        'ntp' => array(
                            'name' => 'NTP',
                            'key' => 'ntp'
                        )
                    )
                ),
                'proxy' => array(
                    'name' => 'Proxy',
                    'key' => 'proxy',
                    'tabs' => array(
                        'settings' => array(
                            'name' => 'Settings',
                            'key' => 'settings'
                        ),
                        'ports' => array(
                            'name' => 'Ports',
                            'key' => 'ports'
                        )
                    )
                ),
                'ssh' => array(
                    'name' => 'SSH',
                    'key' => 'ssh',
                    'tabs' => array(
                        'ssh' => array(
                            'name' => 'SSH',
                            'key' => 'ssh'
                        )
                    )
                ),
                'sharing' => array(
                    'name' => 'Sharing',
                    'key' => 'sharing',
                    'tabs' => array(
                        'sharing' => array(
                            'name' => 'Sharing',
                            'key' => 'sharing'
                        )
                    )
                ),
            )
        ),
        'diagnostics' => array(
            'name' => 'Diagnostics',
            'key' => 'diagnostics',
            'pages' => array(
                'ping' => array(
                    'name' => 'Ping',
                    'key' => 'ping',
                    'tabs' => array(
                        'ping' => array(
                            'name' => 'Ping',
                            'key' => 'ping'
                        )
                    )
                ),
                'tracert' => array(
                    'name' => 'Traceroute',
                    'key' => 'tracert',
                    'tabs' => array(
                        'tracert' => array(
                            'name' => 'Traceroute',
                            'key' => 'tracert'
                        )
                    )
                )
            )
        )
    );

    //Build the javascript namespace based on menu structure
    $data = array();
    foreach ($tpl->modules as $mod) {
        $data[$mod['key']] = array();
        foreach ($mod['pages'] as $page) {
            $data[$mod['key']][$page['key']] = array();
            foreach ($page['tabs'] as $tab) {
                $data[$mod['key']][$page['key']][$tab['key']] = array();
            }
        }
    }
    //Space for xml objects
    $data['data'] = array();
    $tpl->namespace = json_encode_custom($data, true);

    $tpl->display('layout.tpl.php');
}
