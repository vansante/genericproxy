<?php

addMenu('status', array(
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
    )
);
