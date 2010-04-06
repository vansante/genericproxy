<?php

addMenu('services', array(
        'name' => 'Services',
        'key' => 'services',
        'pages' => array(
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
            )
        )
    )
);
