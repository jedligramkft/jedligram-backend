<?php

use Illuminate\Http\UploadedFile;

dataset('valid_post_data', [
    'valid payload' => [
        [
            'content' => 'This is a valid post content',
        ]
    ],

    'valid payload with image' => [
        fn () => [
            'content' => 'This is a valid post content with image',
            'image' => UploadedFile::fake()->image('post.jpg', 300, 300),
        ]
    ],
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

    'image is a plain string instead of a file' => [
        [
            'content' => 'Valid content',
            'image' => 'not-a-file',
        ],
        'image'
    ],

    'image is an array instead of a file' => [
        [
            'content' => 'Valid content',
            'image' => ['invalid'],
        ],
        'image'
    ],

    'image is an integer instead of a file' => [
        [
            'content' => 'Valid content',
            'image' => 123,
        ],
        'image'
    ],

    'image exceeds max file size' => [
        fn () => [
            [
                'content' => 'Valid content',
                'image' => UploadedFile::fake()->image('huge.jpg')->size(5000),
            ],
            'image',
        ]
    ],

    'image has invalid mime type' => [
        fn () => [
            [
                'content' => 'Valid content',
                'image' => UploadedFile::fake()->create('document.pdf', 200, 'application/pdf'),
            ],
            'image',
        ]
    ],
]);
