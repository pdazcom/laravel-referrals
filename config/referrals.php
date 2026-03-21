<?php

return [
    'programs' => [
        'example' => \Pdazcom\Referrals\Programs\ExampleProgram::class,
    ],
    'cookie_name' => 'ref',
    'code_generator' => \Pdazcom\Referrals\Generators\RandomStringCodeGenerator::class,
    'code_length' => 8,
    'code_generation_max_attempts' => 10,
];
