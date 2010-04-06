<?php

addMenu('firewall', array(
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
            )
        )
    )
);
