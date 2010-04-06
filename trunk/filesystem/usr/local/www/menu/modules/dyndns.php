<?php

addMenu('services', array(
        'name' => 'Services',
        'key' => 'services',
        'pages' => array(
            'dyndns' => array(
                'name' => 'Dynamic DNS',
                'key' => 'dyndns',
                'tabs' => array(
                    'dyndns' => array(
                        'name' => 'Dynamic DNS',
                        'key' => 'dyndns'
                    )
                )
            )
        )
    )
);