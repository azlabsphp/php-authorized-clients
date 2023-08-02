<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Oauth\Clients;

use Closure;
use Drewlabs\Oauth\Clients\Contracts\ClientInterface;
use Drewlabs\Oauth\Clients\Contracts\ClientQueryInterface;
use Drewlabs\Oauth\Clients\Contracts\CredentialsIdentityInterface;
use Drewlabs\Oauth\Clients\Contracts\CredentialsIdentityValidator;
use Drewlabs\Oauth\Clients\Contracts\ScopeInterface;
use Drewlabs\Oauth\Clients\Exceptions\AuthorizationException;
use Drewlabs\Oauth\Clients\Exceptions\MissingScopesException;

final class CredentialsValidator implements CredentialsIdentityValidator
{
    /**
     * @var \Closure|ClientQueryInterface
     */
    private $selector;

    /**
     * @param \Closure|ClientQueryInterface $selectorFunc This function will be used to select client from datasource
     *                                                    using the clientId and clientSecret as parameters. This means that the selector function
     *                                                    must accept the clientId and clientSecret as arguments
     */
    public function __construct($selectorFunc)
    {
        $this->selector = $selectorFunc;
    }

    public function validate(CredentialsIdentityInterface $identity, $scopes = [], $ip = null)
    {

        // Find the client based on the provided token and id
        /**
         * @var ClientInterface
         */
        $client = ($this->selector)($identity);

        // Case the client is null we throw an authorization exception
        if (null === $client) {
            throw new AuthorizationException('client not found');
        }

        // Case the client is revoked, we throw an authorization exception
        if ($client->isRevoked()) {
            throw new AuthorizationException('client has been revoked');
        }

        // Case client does not have the required scopes we throw a Missing scope exception
        if (!$client->hasScope($scopes)) {
            $scopes = $scopes instanceof ScopeInterface ? (string) $scopes : $scopes;
            $scopes = \is_string($scopes) ? [$scopes] : $scopes;
            throw new MissingScopesException($client->getKey(), array_diff($client->getScopes(), $scopes));
        }

        // ! Provide the client request headers in the proxy request headers definition
        // Get Client IP Addresses
        $ips = null !== ($ips = $client->getIpAddressesAttribute()) ? $ips : [];

        // Check whether * exists in the list of client ips
        if (!\in_array('*', $ips, true) && (null !== $ip)) {
            // // Return the closure handler for the next middleware
            // Get the request IP address
            if (!\in_array($ip, $ips, true)) {
                throw new AuthorizationException(sprintf('unauthorized request origin %s', \is_array($ip) ? implode(',', $ip) : $ip));
            }
        }

        return $client;
    }
}
