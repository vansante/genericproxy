<?php

addMenu('firewall', array(
        'name' => 'Firewall',
        'key' => 'firewall',
        'pages' => array(
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
    )
);