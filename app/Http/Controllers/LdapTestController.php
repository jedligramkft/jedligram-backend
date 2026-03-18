<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use LdapRecord\Container;
use LdapRecord\LdapRecordException;

class LdapTestController extends Controller
{
    /**
     * Test-only LDAP endpoint (example for future development).
     *
     * This performs a bind using the configured LDAPRecord connection and returns
     * a small, non-sensitive snapshot.
     */
    public function test(): JsonResponse
    {
        $hostsConfig = config('ldap.connections.default.hosts', []);
        $hostsConfigString = is_array($hostsConfig) ? implode(',', $hostsConfig) : (string) $hostsConfig;

        echo ($hostsConfigString . "\n");

        $configuredHosts = is_array($hostsConfig)
            ? array_values(array_filter($hostsConfig))
            : array_values(array_filter(explode(',', (string) $hostsConfig)));

        // Basic guard so this endpoint gives a clear response on unconfigured envs.
        if (empty($configuredHosts) || ! config('ldap.connections.default.base_dn')) {
            return response()->json([
                'ok' => false,
                'message' => 'LDAP is not configured. Set LDAP_HOSTS and LDAP_BASE_DN in your environment.',
            ], 422);
        }

        try {
            $connection = Container::getConnection(config('ldap.default', 'default'));

            // Bind (using read-only DN if configured). If username/password are empty,
            // many servers will reject anonymous bind; we surface that cleanly.
            $connection->connect();

            return response()->json([
                'ok' => true,
                'message' => 'LDAP connection succeeded.',
                'connection' => config('ldap.default', 'default'),
                'host_count' => count($configuredHosts),
                'base_dn' => config('ldap.connections.default.base_dn'),
            ]);
        } catch (LdapRecordException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'LDAP connection failed.',
                // Don’t leak passwords; LDAP errors are usually safe but may include hostnames.
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
