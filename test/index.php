<?php

    /** @var \nhujanen\AAD\SSO $t */

    // Bootstrap
    require_once 'init.php';
    
    // Generate CSRF token (terrible, dont do it like this)
    $_SESSION['sso-state'] = sha1(implode('/', [microtime(true), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]));

    // Get login link
    $url = $t->getUrl($_SESSION['sso-state']);

?>
<!DOCTYPE html>
<html>
    <body>
        
        <h1>Azure AD - SSO test</h1>

        <p>
            Login here:
            <a href="<?= htmlspecialchars($url); ?>"><?= htmlspecialchars($url); ?></a>
        </p>

    </body>
</html>
