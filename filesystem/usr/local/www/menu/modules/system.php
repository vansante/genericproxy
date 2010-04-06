<?php

addMenu('system', array(
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
            'upgrade' => array(
                'name' => 'Firmware upgrade',
                'key' => 'upgrade',
                'tabs' => array(
                    'auto' => array(
                        'name' => 'Automatic',
                        'key' => 'auto',
                    ),
                    'manual' => array(
                        'name' => 'Manual',
                        'key' => 'manual',
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
    )
);