<?php
    
    /** @var \nhujanen\AAD\SSO $t */
    
    // Bootstrap
    require_once 'init.php';

    try {

        // Get access_token (authorize) and provide CSRF token generated before login started.
        $t->authorize($_SESSION['sso-state']);

        // Fetch current user's profile
        $me = $t->me();

    } catch (Exception $e) {

        printf('<h2>%d %s</h2>', $e->getCode(), $e->getMessage());
        echo "<pre>{$e}</pre>";
        echo '<a href="/">Try again</a>';
        exit;

    }
?>
<!DOCTYPE html>
<html>
    <body>

        <h1>Azure AD - SSO test</h1>

        <pre><?php print_r( $me ); ?></pre>
        
    </body>
</html>
