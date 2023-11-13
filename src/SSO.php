<?php

namespace ubtashton\AAD;

class SSO
{
    /**
     * Default scopes to request
     */
    const SCOPE = 'openid email profile';

    /**
     * User agent reported on HTTP requests
     */
    const USER_AGENT = 'AADSSO/1.0';

    /**
     * HTTP request timeout
     */
    const TIMEOUT = 3.0;

    /**
     * OAuth2 base URL
     */
    const URL_LOGIN = 'https://login.microsoftonline.com';

    /**
     * Graph API base URL
     */
    const URL_GRAPH = 'https://graph.microsoft.com/v1.0';

    /**
     * Redirect Url for authorization
     * @var string
     */
    protected $redirectUrl;

    /**
     * 365 Tenant (foobar.com)
     * @var string
     */
    protected $tenant;

    /**
     * Application ID
     * @var string
     */
    protected $applicationId;

    /**
     * Application Secret (value)
     * @var string
     */
    protected $applicationSecret;

    /**
     * Access token for Graph API
     * @var string|null
     */
    protected $accessToken = null;

    /**
     * @param string $redirectUrl           Whitelisted redirect URL
     * @param string $tenant                Tenant (foobar.com)
     * @param string $applicationId         Application (client) ID
     * @param string $applicationSecret     Client secret value
     */
    public function __construct($redirectUrl, $tenant, $applicationId, $applicationSecret)
    {
        $this->redirectUrl = $redirectUrl;
        $this->tenant = $tenant;
        $this->applicationId = $applicationId;
        $this->applicationSecret = $applicationSecret;
    }

    /**
     * Get URL for login
     * 
     * @param string $state         CSRF token - if given, required in authorize()
     * @return string               URL
     */
    public function getUrl($state = '')
    {
        return sprintf(
            '%s/%s/oauth2/v2.0/authorize?response_type=code&redirect_uri=%s&client_id=%s&response_mode=query&scope=%s&state=%s',
            static::URL_LOGIN,
            $this->tenant,
            urlencode($this->redirectUrl),
            $this->applicationId,
            static::SCOPE,
            urlencode($state)
        );
    }

    /**
     * Get access token based on getUrl() result
     * 
     * @param string $state         CSRF token - provided during getUrl()
     * @return void 
     * 
     * @throws \RuntimeException    General error
     * @throws \ErrorException      PHP error (fopen on url)
     */
    public function authorize($state = '')
    {
        $code = isset($_REQUEST['code']) ? $_REQUEST['code'] : null;

        if (!$code)
            throw new \RuntimeException(
                'Expected "code" during token fetch in request parameters.'
            );

        $responseState = isset($_REQUEST['state']) ? $_REQUEST['state'] : null;

        if ($responseState !== $state)
            throw new \RuntimeException(
                'Invalid "state" (CSRF)'
            );

        $response = json_decode(
            $this->post(
                sprintf('%s/%s/oauth2/v2.0/token', static::URL_LOGIN, $this->tenant),
                [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->applicationId,
                    'client_secret' => $this->applicationSecret,
                    'scope' => static::SCOPE,
                    'code' => $code,
                    'redirect_uri' => $this->redirectUrl
                ]
            )
        );

        if (!$response)
            throw new \RuntimeException(
                sprintf(
                    'Invalid response: %s',
                    json_last_error_msg()
                )
            );

        if (!isset($response->token_type) || !isset($response->access_token))
            throw new \RuntimeException(
                'Invalid format on response.'
            );

        $this->accessToken = $response->access_token;
    }

    /**
     * Get current user profile
     * 
     * @return object               https://graph.microsoft.com/v1.0/$metadata#users/$entity
     * 
     * @throws \Exception           Programmer error
     * @throws \ErrorException      PHP error (fopen on url)
     * @throws \RuntimeException    General error
     */
    public function me()
    {
        if (!$this->accessToken)
            throw new \Exception(
                'No access token available.'
            );

        $response = json_decode(
            $this->get(
                sprintf('%s/me', static::URL_GRAPH),
                [],
                ["Authorization: Bearer {$this->accessToken}"]
            )
        );

        if (!$response)
            throw new \RuntimeException(
                sprintf(
                    'Invalid response: %s',
                    json_last_error_msg()
                )
            );

        return $response;
    }

    /**
     * Get user groups
     * NOTICE: This requires forced admin role on AAD application (Group.Read.All)
     * 
     * @return object 
     * 
     * @throws \Exception           Programmer error
     * @throws \ErrorException      PHP error (fopen on url)
     * @throws \RuntimeException    General error
     */
    public function groups()
    {
        $response = json_decode(
            $this->get(
                sprintf('%s/me/memberOf', static::URL_GRAPH),
                [],
                ["Authorization: Bearer {$this->accessToken}"]
            )
        );

        if (!$response)
            throw new \RuntimeException(
                sprintf(
                    'Invalid response: %s',
                    json_last_error_msg()
                )
            );

        return $response;
    }

    /**
     * HTTP POST helper
     * 
     * @param string $url           URL to post
     * @param array $payload        Payload (assoc)
     * @param array $headers        HTTP headers
     * @return string               Response content 
     * 
     * @throws \ErrorException      fopen permission issue/fail (allow_url_fopen)
     * @throws \RuntimeException    Timeout
     * @throws \Exception           HTTP Response code not 2xx
     */
    protected function post($url, array $payload = [], array $headers = [])
    {
        $payload = http_build_query($payload);

        //refactor the above using arrays
        $context_options = array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-Type: application/x-www-form-urlencoded\r\n' .
                    'Content-Length: ' . strlen($payload) . "\r\n"
                ),
                'content' => $payload
            )
        );

        $streamContext = stream_context_create($context_options);

        return $this->request(
            $url,
            $streamContext
        );
    }

    /**
     * HTTP GET helper
     * 
     * @param string $url           URL to get
     * @param array $query          Query (assoc)
     * @param array $headers        HTTP headers
     * @return string               Response content 
     * 
     * @throws \ErrorException      fopen permission issue/fail (allow_url_fopen)
     * @throws \RuntimeException    Timeout
     * @throws \Exception           HTTP Response code not 2xx
     */
    protected function get($url, array $query = [], array $headers = [])
    {
        $requestUrl = $url;

        if (count($query)) {

            $requestUrl = (strpos($requestUrl, '?') !== false) ?
                "{$requestUrl}&" :
                "{$requestUrl}?";

            $requestUrl .= http_build_query($query);

        }

        //refactor the above to use arrays
        $context_options = array(
            'http' => array(
                'method' => 'GET',
                'header' => $headers[0]
            )
        );
        $streamContext = stream_context_create($context_options);

        return $this->request(
            $requestUrl,
            $streamContext
        );
    }

    /**
     * HTTP Request handler
     * 
     * @param string $url               URL to request
     * @param resource $streamContext   Stream Context
     * @return string                   Raw response data
     * 
     * @throws \ErrorException      fopen permission issue/fail (allow_url_fopen)
     * @throws \RuntimeException    Timeout
     * @throws \Exception           HTTP Response code not 2xx
     */
    protected function request($url, $streamContext)
    {
        $stream = fopen(
            $url,
            'r',
            false,
            $streamContext
        );

        if ($stream === false) {
            $lastError = error_get_last();
            throw new \ErrorException(
                $lastError['message'],
                0,
                $lastError['type'],
                $lastError['file'],
                $lastError['line']
            );
        }

        $streamMeta = stream_get_meta_data($stream);
        $streamData = stream_get_contents($stream);

        if ($streamMeta['timed_out'])
            throw new \RuntimeException(
                sprintf(
                    'Request to "%s" timed out.',
                    $url
                )
            );

        list($httpVersion, $httpCode, $httpMessage) = explode(' ', $streamMeta['wrapper_data'][0], 3);

        if ($httpCode < 200)
            throw new \Exception(
                $httpMessage,
                intval($httpCode)
            );
        if ($httpCode >= 300)
            throw new \Exception(
                $httpMessage,
                intval($httpCode)
            );

        fclose($stream);

        return $streamData;
    }


}