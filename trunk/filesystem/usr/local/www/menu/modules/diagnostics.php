<?php

addMenu('diagnostics', array(
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