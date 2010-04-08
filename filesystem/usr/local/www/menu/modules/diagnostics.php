<?php

addMenu('diagnostics', array(
        'name' => 'Diagnostics',
        'key' => 'diagnostics',
        'pages' => array(
            'log' => array(
                'name' => 'Logs',
                'key' => 'log',
                'tabs' => array(
                    'boot' => array(
                        'name' => 'Boot',
                        'key' => 'boot'
                    ),
                    'httpd' => array(
                        'name' => 'HTTPD',
                        'key' => 'httpd'
                    ),
                    'browser' => array(
                        'name' => 'Browser',
                        'key' => 'browser'
                    )
                )
            ),
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
            ),
            'nmap' => array(
                'name' => 'Nmap',
                'key' => 'nmap',
                'tabs' => array(
                    'nmap' => array(
                        'name' => 'Nmap',
                        'key' => 'nmap'
                    )
                )
            )
        )
    )
);