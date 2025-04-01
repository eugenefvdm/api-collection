<?php

namespace Eugenefvdm\Api;

use Eugenefvdm\Api\Contracts\WhmInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Whm implements WhmInterface
{
    private ?PendingRequest $client = null;

    private string $username;

    private string $password;

    private string $server;

    /**
     * Constructor
     *
     * @param  string  $username  Whm username
     * @param  string  $password  Whm password
     * @param  string  $server  Whm server URL (e.g. https://server.example.com:2087)
     */
    public function __construct(string $username, string $password, string $server)
    {
        $this->username = $username;
        $this->password = $password;
        $this->server = rtrim($server, '/');
    }

    /**
     * Get the HTTP client instance
     */
    private function client(): PendingRequest
    {
        if (! $this->client) {
            $this->client = Http::baseUrl($this->server)
                ->withHeaders([
                    'Authorization' => 'WHM '.$this->username.':'.$this->password,
                ])
                ->withoutVerifying();
        }

        return $this->client;
    }

    /**
     * Get bandwidth information for all domains
     * @link https://api.docs.cpanel.net/openapi/whm/operation/showbw/ WHM API Documentation for showbw
     * @return array Bandwidth information
     */
    public function bandwidth(): array
    {
        return $this->client()->get('/json-api/showbw')->json();
    }

    /**
     * Suspend an email account's login ability
     * @link https://api.docs.cpanel.net/openapi/cpanel/operation/suspend_login/
     * @param string $email The email address to suspend
     * @param string $cpanelUsername The cPanel username that owns the email account
     * @return array Response from the API with HTTP status code
     */
    public function suspendEmail(string $cpanelUsername, string $email): array
    {
        $response = $this->client()->get('/json-api/cpanel', [
            'cpanel_jsonapi_apiversion' => 3,
            'cpanel_jsonapi_user' => $cpanelUsername,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'suspend_login',            
            'email' => $email,
        ])->json();

        // Check for email not found error message
        if (isset($response['result']['errors'])) {
            foreach ($response['result']['errors'] as $error) {
                if (str_contains($error, 'You do not have an email account named')) {
                    return [
                        'status' => 'error',
                        'code' => 404,
                        'output' => "Email address '$email' not found"
                    ];
                }
            }
        }

        // Check for other messages (e.g. already suspended)
        if (!empty($response['result']['messages'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'output' => $response['result']['messages'][0]
            ];
        }

        // Success case
        return [
            'status' => 'success',
            'code' => 200,
            'output' => [],
        ];
    }

    /**
     * Unsuspend an email account's login ability
     * @link https://api.docs.cpanel.net/openapi/cpanel/operation/suspend_login/
     * @param string $email The email address to unsuspend
     * @param string $cpanelUsername The cPanel username that owns the email account
     * @return array Response from the API with HTTP status code
     */
    public function unsuspendEmail(string $cpanelUsername, string $email): array
    {
        $response = $this->client()->get('/json-api/cpanel', [
            'cpanel_jsonapi_apiversion' => 3,
            'cpanel_jsonapi_user' => $cpanelUsername,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'unsuspend_login',
            'email' => $email,
        ])->json();

        // Check for email not found error message
        if (isset($response['result']['errors'])) {
            foreach ($response['result']['errors'] as $error) {
                if (str_contains($error, 'You do not have an email account named')) {
                    return [
                        'status' => 'error',
                        'code' => 404,
                        'output' => "Email address '$email' not found"
                    ];
                }
            }
        }

        // Check for already unsuspended message
        if (!empty($response['result']['messages'])) {
            return [
                'status' => 'error',
                'code' => 400,
                'output' => $response['result']['messages'][0]
            ];
        }

        // Success case
        return [
            'status' => 'success',
            'code' => 200,
            'output' => [],
        ];
    }

    /**
     * Set the HTTP client (used for testing)
     *
     * @param  PendingRequest  $client  The HTTP client to use
     */
    public function setClient(PendingRequest $client): void
    {
        $this->client = $client;
    }
}
