<?php

dataset('valid_thread_data', [
    'valid payload' => [
        [
            'name' => 'New test thread',
            'description' => 'New test description',
            'rules' => 'rules'
        ]
    ]
]);

dataset('invalid_thread_data', [
    'missing name' => [
        [
            'description' => 'Valid description',
            'rules' => 'Valid rules'
        ],
        'name'
    ],

    'name is empty string' => [
        [
            'name' => '',
            'description' => 'Valid description',
            'rules' => 'Valid rules'
        ],
        'name'
    ],

    'name exceeds 30 characters' => [
        [
            'name' => str_repeat('a', 31),
            'description' => 'Valid description',
            'rules' => 'Valid rules'
        ],
        'name'
    ],

    'description exceeds 200 characters' => [
        [
            'name' => 'Valid Name',
            'description' => str_repeat('a', 201),
            'rules' => 'Valid rules'
        ],
        'description'
    ],

    'rules is not a string' => [
        [
            'name' => 'Valid Name',
            'description' => 'Valid description',
            'rules' => ['this', 'is', 'an', 'array', 'not', 'a', 'string']
        ],
        'rules'
    ],
]);
