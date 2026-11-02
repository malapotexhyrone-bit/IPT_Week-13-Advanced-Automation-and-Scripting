<?php

return [
    'enabled' => (bool) env('DEMO_MODE_ENABLED', env('APP_ENV') === 'local'),
];
