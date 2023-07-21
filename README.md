# Stand-alone Azure AD SSO for PHP 5.6+

Simple stand-alone library to handle Azure AD SSO flow.

- Tested with `PHP 5.6 - 8.1`
- `allow_url_fopen` must be `true` in `php.ini` to use HTTP requests.
- Requires `ext-openssl`.

## License

MIT License

## Install

You can use Composer:
```
composer require nhujanen/aad-sso
```

## Example

See `test/` for code examples.

You can run example with PHP's built-in web server:
```
foo@bar test]$ AAD_TENANT=foobar.com AAD_ID=00000000-0000-0000-0000-000000000000 AAD_SECRET=foo php -S localhost:8080 -t .
```

**Remember: you need to add `http://localhost:8080/auth.php` as valid Redirect URI.**

## Create and configure AAD application

Refer to [Register an application with the Microsoft identity platform](https://learn.microsoft.com/en-us/graph/auth-register-app-v2?view=graph-rest-1.0).