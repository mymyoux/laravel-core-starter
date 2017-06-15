<?php
return [
    'google' => 
    [
        'client_id' => env('GOOGLE_KEY'),
        'client_secret' => env('GOOGLE_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),  
    ],
    'github' => 
    [
        'client_id' => env('GITHUB_KEY'),
        'client_secret' => env('GITHUB_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),  
    ],
    'slack' => [
        'client_id' => env('SLACK_KEY'),
        'client_secret' => env('SLACK_SECRET'),
        'redirect' => env('SLACK_REDIRECT_URI'),  
        'verification_token'=>env('SLACK_VERIFICATION_TOKEN'),
    ], 
    'gmap' => 
    [
        'api_key' => env('GMAP_API_KEY')
    ]
];