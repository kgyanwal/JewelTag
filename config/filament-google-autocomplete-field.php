<?php

return [
    // Change 'api-key' to 'api_key' (with an underscore) 
    // to match what the plugin expects
    'api_key' => env('GOOGLE_PLACES_API_KEY', ''),
    'verify-ssl' => true,
    'throw-on-errors' => false,
];