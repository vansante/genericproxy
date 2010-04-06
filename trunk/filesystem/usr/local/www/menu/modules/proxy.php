<?php

addMenu('services', array(
        'name' => 'Services',
        'key' => 'services',
        'pages' => array(
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
            )
        )
    )
);

