<?php

use Illuminate\Http\UploadedFile;

dataset('invalid_profile_data', [
    'missing name entirely' => [
        ['email' => 'valid@example.com', 'bio' => 'Hello!'],
        'name',
        422
    ],
    'name is not a string' => [
        ['name' => 12345, 'email' => 'valid@example.com'],
        'name',
        422
    ],
    'name exceeds 40 chars' => [
        ['name' => str_repeat('a', 41), 'email' => 'valid@example.com'],
        'name',
        422
    ],
    'missing email entirely' => [
        ['name' => 'John Doe', 'bio' => 'Hello!'],
        'email',
        422
    ],
    'email is not a string' => [
        ['name' => 'John Doe', 'email' => 12345],
        'email',
        422
    ],
    'email is not a valid format' => [
        ['name' => 'John Doe', 'email' => 'not-an-email-address'],
        'email',
        422
    ],
    'email exceeds 60 chars' => [
        ['name' => 'John Doe', 'email' => str_repeat('a', 60) . '@gmail.com'],
        'email',
        422
    ],
    'bio is an array (wrong type)' => [
        ['name' => 'John Doe', 'email' => 'valid@example.com', 'bio' => ['Hello']],
        'bio',
        422
    ],
    'bio exceeds 100 chars' => [
        ['name' => 'John Doe', 'email' => 'valid@example.com', 'bio' => str_repeat('b', 101)],
        'bio',
        422
    ],
]);

dataset('valid_profile_data', [

    'standard payload with all fields' => [
        [
            'name'  => 'John Doe',
            'email' => 'john.updated@example.com',
            'bio'   => 'Hi, I am a software developer!'
        ]
    ],

    'payload clearing the bio with null' => [
        [
            'name'  => 'Jane Smith',
            'email' => 'jane.updated@example.com',
            'bio'   => null
        ]
    ],

    'payload entirely missing the bio key' => [
        [
            'name'  => 'Alice Wonderland',
            'email' => 'alice.updated@example.com',
        ]
    ],

    'payload hitting exact maximum lengths' => [
        [
            'name'  => str_repeat('N', 40),
            'email' => str_repeat('e', 48) . '@example.com',
            'bio'   => str_repeat('B', 100)
        ]
    ],
]);

dataset('valid_profile_picture_data', [
    'standard JPEG' => ['profile.jpeg', null],
    'standard PNG' => ['profile.png', null],
    'standard GIF' => ['animated.gif', null],
    'image exactly at the maximum size limit' => ['borderline.jpg', 2048],
]);

dataset('invalid_profile_picture_data', [
    'missing image entirely' => [null, null, null, 'image'],
    'file is not an image' => ['document.pdf', 100, 'application/pdf', 'image'],
    'image is the wrong mime type' => ['vector.svg', 100, 'image/svg+xml', 'image'],
    'image exceeds the 2MB limit' => ['massive.jpg', 2500, 'image/jpeg', 'image'],
]);
