<?php

    use nhujanen\AAD\SSO;

    // Let's be verbose
    error_reporting(E_ALL);
    ini_set('display_errors', true);

    // For CSRF token
    session_start();

    // Autoloader
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    /**
     * Initialize SSO
     * 
     * Remember to provide Tenant, Appliaction ID and Application Secret.
     */
    $t = new SSO(
        'http://localhost:8080/auth.php', 
        getenv('AAD_TENANT'),
        getenv('AAD_ID'),
        getenv('AAD_SECRET')
    );
