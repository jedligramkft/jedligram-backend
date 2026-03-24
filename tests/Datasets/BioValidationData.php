<?php

dataset('invalid_bio_data', [
    'bio is an array' => [
        ['bio' => ['Hello', 'World']],
        'bio',
        422
    ],

    'bio is an integer' => [
        ['bio' => 12345],
        'bio',
        422
    ],

    'bio exceeds max length' => [
        ['bio' => str_repeat('a', 201)],
        'bio',
        422
    ],
]);


dataset('valid_bio_data', [
    'standard short introduction' => [
        ['bio' => 'Hi, I am a software developer from Hungary!']
    ],

    'bio with special characters and emojis' => [
        ['bio' => 'Coding 💻 | Coffee ☕ | Laravel 🚀']
    ],

    'clearing the bio with null' => [
        ['bio' => null]
    ],

    'clearing the bio with empty string' => [
        ['bio' => '']
    ],

    'exactly the maximum allowed length' => [
        ['bio' => str_repeat('b', 200)]
    ],
]);
