<?php

namespace Mrstebo\LaravelSocialiteXero;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use Lcobucci\JWT\Parser;

class XeroSocialiteProvider extends AbstractProvider
{
    protected $scopeSeparator = ' ';
    protected $tokens;

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => $this->getTokenFields($code),
        ]);
        $this->tokens = json_decode($response->getBody(), true);

        return $this->tokens;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param  string $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://login.xero.com/identity/connect/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return 'https://identity.xero.com/connect/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $parsedToken = (new Parser())->parse($token);
        $idToken = Arr::get($this->tokens, 'id_token');
        $tenant = $this->getAuthorizedTenantByToken($token);

        $user = [
            'sub' => $parsedToken->getClaim('sub'),
            'xero_userid' => $parsedToken->getClaim('xero_userid'),
            'email' => $this->getEmailByToken($idToken),
            'nickname' => $this->getNicknameByToken($idToken),
            'name' => $this->getNameByToken($idToken),
            'tenant_id' => $tenant['tenant_id'],
            'tenant_type' => $tenant['tenant_type'],
            'tenant_name' => $tenant['tenant_name'],
        ];

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['sub'],
            'xeroUserId' => $user['xero_userid'],
            'email' => $user['email'],
            'nickname' => $user['nickname'],
            'name' => $user['name'],
            'tenantId' => $user['tenant_id'],
            'tenantType' => $user['tenant_type'],
            'tenantName' => $user['tenant_name'],
        ]);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Get the email for the given id token.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function getEmailByToken($token)
    {
        $parsedToken = (new Parser())->parse($token);

        return $parsedToken->getClaim('email');
    }

    /**
     * Get the nickname for the given id token.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function getNicknameByToken($token)
    {
        $parsedToken = (new Parser())->parse($token);

        return $parsedToken->getClaim('preferred_username');
    }

    /**
     * Get the name for the given id token.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function getNameByToken($token)
    {
        $parsedToken = (new Parser())->parse($token);

        return implode(' ', [
            $parsedToken->getClaim('given_name'),
            $parsedToken->getClaim('family_name'),
        ]);
    }

    /**
     * Get the authorized tenant for the given token.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function getAuthorizedTenantByToken($token)
    {
        $parsedToken = (new Parser())->parse($token);

        $connectionsUrl = 'https://api.xero.com/connections';

        $response = $this->getHttpClient()->get($connectionsUrl, $this->getRequestOptions($token));

        foreach (json_decode($response->getBody(), true) as $tenant) {
            if ($tenant['authEventId'] === $parsedToken->authentication_event_id && $tenant['tenantType'] === 'ORGANISATION') {
                return [
                    'tenant_id' => $tenant['tenantId'],
                    'tenant_type' => $tenant['tenantType'],
                    'tenant_name' => $tenant['tenantName'],
                ];
            }
        }

        return [
            'tenant_id' => null,
            'tenant_type' => null,
            'tenant_name' => null,
        ];
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @param string $token
     * @return array
     */
    protected function getRequestOptions($token)
    {
        return [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'token '.$token,
            ],
        ];
    }
}
