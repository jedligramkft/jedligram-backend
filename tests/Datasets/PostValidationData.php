<?php

dataset('valid_post_data', [
    'valid payload' => [
        [
            'content' => 'This is a valid post content',
        ]
    ]
]);

dataset('invalid_post_data', [
    'missing content entirely' => [
        [],
        'content'
    ],

    'content is an empty string' => [
        ['content' => ''],
        'content'
    ],

    'content is null' => [
        ['content' => null],
        'content'
    ],

    'content is an array instead of a string' => [
        ['content' => ['This', 'is', 'an', 'array']],
        'content'
    ],

    'content is an integer instead of a string' => [
        ['content' => 12345],
        'content'
    ],
]);
