<?php

return [
    'menu_data_endpoint' => env('ALUMNI_MENU_DATA_ENDPOINT', 'http://127.0.0.1:8001/api/integration/alumni/menu-data'),
    'api_key' => env('ALUMNI_MENU_DATA_API_KEY', 'alumni_WsoQxOsjaifVg49uzJNbTdAmWCuIgF11okjNDfF8bnUCWdHSQLykHN9Q1fv9La5K'),
    'status_cache_key' => 'integration.menu_data.connection_status',
];
