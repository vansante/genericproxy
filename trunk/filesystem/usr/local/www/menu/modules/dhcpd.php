<?php

addMenu('services', array(
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
            )
        )
    )
);
