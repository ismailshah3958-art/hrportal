<?php

return [
    'enabled' => filter_var(env('ZKTECO_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'host' => env('ZKTECO_HOST', ''),
    'port' => (int) env('ZKTECO_PORT', 4370),
    'password' => (int) env('ZKTECO_PASSWORD', 0),
    'timeout' => (int) env('ZKTECO_TIMEOUT', 25),
    'should_ping' => filter_var(env('ZKTECO_SHOULD_PING', false), FILTER_VALIDATE_BOOLEAN),
    'schedule' => filter_var(env('ZKTECO_SCHEDULE', false), FILTER_VALIDATE_BOOLEAN),
];
