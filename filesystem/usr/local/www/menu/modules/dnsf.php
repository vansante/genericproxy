<?php

addMenu('services', array(
        'name' => 'Services',
        'key' => 'services',
        'pages' => array(
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
            )
        )
    )
);
