<?php

dataset('valid_role_assignment_data', [
    'assigning admin (1)'     => [ ['role_id' => 1] ],
    'assigning moderator (2)' => [ ['role_id' => 2] ],
    'assigning user (3)'      => [ ['role_id' => 3] ],
]);

dataset('invalid_role_assignment_data', [
    'missing role_id entirely' => [
        [],
        'role_id',
        422
    ],

    'role_id is null' => [
        ['role_id' => null],
        'role_id',
        422
    ],

    'role_id is not an integer' => [
        ['role_id' => 'moderator'],
        'role_id',
        422
    ],

    'role_id is a random invalid number' => [
        ['role_id' => 99],
        'role_id',
        422
    ],

    'role_id is 4 (banning must use dedicated endpoint)' => [
        ['role_id' => 4],
        'message',
        400
    ],
]);
