<?php

namespace Pterodactyl\BlueprintFramework\Extensions\cfsubdomain;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;

/**
 * Cloudflare Service Layer
 *
 * Handles all Cloudflare API interactions for both Tunneled and DNS-Only modes.
 * This service creates and deletes subdomain records via Cloudflare's API.
 */
class CloudflareService
{
    private string $apiKey;
    private string $zoneId;
    private string $accountId;
    private string $baseDomain;

    public function __construct()
    {
        $blueprint = app(BlueprintExtensionLibrary::class);

        $this->apiKey    = $blueprint->dbGet('cfsubdomain', 'cf_api_key') ?? '';
        $this->zoneId    = $blueprint->dbGet('cfsubdomain', 'cf_zone_id') ?? '';
        $this->accountId = $blueprint->dbGet('cfsubdomain', 'cf_account_id') ?? '';
        $this->baseDomain = $blueprint->dbGet('cfsubdomain', 'base_domain') ?? '';
    }

    /**
     * Check if Cloudflare is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->zoneId) && !empty($this->baseDomain);
    }

    /**
     * Create a subdomain binding for a server port.
     *
     * @param int    $serverId      Pterodactyl server ID
     * @param int    $allocationId  Pterodactyl allocation ID
     * @param int    $port          Dynamic port number
     * @param string $subdomain     Desired subdomain (e.g. "myserver")
     * @param int    $nodeId        Node ID to look up mode
     * @param int|null $createdBy   User who created the binding
     * @return array Result with success status and data
     */
    public function createSubdomain(
        int $serverId,
        int $allocationId,
        int $port,
        string $subdomain,
        int $nodeId,
        ?int $createdBy = null
    ): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Cloudflare is not configured. Please set API key, Zone ID, and Base Domain in admin settings.'];
        }

        // Get node settings
        $nodeSettings = DB::table('cf_node_settings')
            ->where('node_id', $nodeId)
            ->first();

        if (!$nodeSettings) {
            return ['success' => false, 'error' => 'Node is not configured for Cloudflare. Please configure it in admin settings.'];
        }

        $mode = $nodeSettings->mode;
        $fullDomain = $subdomain . '.' . $this->baseDomain;
        $connectionString = $fullDomain . ':' . $port;

        // Call Cloudflare API based on mode
        if ($mode === 'tunneled') {
            $result = $this->createTunnelRule($nodeSettings->tunnel_id, $fullDomain, $port);
        } else {
            // DNS-Only: get node IP from Pterodactyl
            $node = DB::table('nodes')->where('id', $nodeId)->first();
            $nodeIp = $node ? $node->fqdn : '';
            $result = $this->createDnsRecord($fullDomain, $nodeIp);
        }

        if (!$result['success']) {
            return $result;
        }

        // Save to database
        DB::table('cf_server_access_points')->insert([
            'server_id'         => $serverId,
            'allocation_id'     => $allocationId,
            'port'              => $port,
            'subdomain'         => $subdomain,
            'full_domain'       => $fullDomain,
            'connection_string' => $connectionString,
            'cf_record_id'      => $result['cf_record_id'] ?? null,
            'node_mode'         => $mode,
            'created_by'        => $createdBy,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return [
            'success'           => true,
            'full_domain'       => $fullDomain,
            'connection_string' => $connectionString,
            'port'              => $port,
            'mode'              => $mode,
        ];
    }

    /**
     * Delete a subdomain binding.
     *
     * @param int $accessPointId
     * @return array
     */
    public function deleteSubdomain(int $accessPointId): array
    {
        $accessPoint = DB::table('cf_server_access_points')
            ->where('id', $accessPointId)
            ->first();

        if (!$accessPoint) {
            return ['success' => false, 'error' => 'Access point not found.'];
        }

        // Delete from Cloudflare
        if ($accessPoint->cf_record_id) {
            if ($accessPoint->node_mode === 'dns_only') {
                $this->deleteDnsRecord($accessPoint->cf_record_id);
            } else {
                // For tunneled mode, we'd update the tunnel configuration
                $this->deleteTunnelRule($accessPoint);
            }
        }

        // Delete from database
        DB::table('cf_server_access_points')
            ->where('id', $accessPointId)
            ->delete();

        return ['success' => true];
    }

    /**
     * Delete all subdomains for a server (called when server is deleted).
     *
     * @param int $serverId
     * @return void
     */
    public function deleteAllForServer(int $serverId): void
    {
        $accessPoints = DB::table('cf_server_access_points')
            ->where('server_id', $serverId)
            ->get();

        foreach ($accessPoints as $ap) {
            if ($ap->cf_record_id) {
                if ($ap->node_mode === 'dns_only') {
                    $this->deleteDnsRecord($ap->cf_record_id);
                } else {
                    $this->deleteTunnelRule($ap);
                }
            }
        }

        DB::table('cf_server_access_points')
            ->where('server_id', $serverId)
            ->delete();
    }

    /**
     * Get auto-generated subdomain for a server (used when user leaves field blank on DNS-Only).
     */
    public function generateDefaultSubdomain(int $serverId, int $port): string
    {
        return 'server-' . $serverId . '-' . $port;
    }

    // =====================================================
    //  CLOUDFLARE API METHODS
    // =====================================================

    /**
     * Create a DNS A record.
     */
    private function createDnsRecord(string $fullDomain, string $nodeIp): array
    {
        $url = "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records";

        $data = [
            'type'    => 'A',
            'name'    => $fullDomain,
            'content' => $nodeIp,
            'ttl'     => 1, // Auto TTL
            'proxied' => false,
        ];

        $response = $this->cfApiRequest('POST', $url, $data);

        if ($response && isset($response['success']) && $response['success']) {
            return [
                'success'      => true,
                'cf_record_id' => $response['result']['id'] ?? null,
            ];
        }

        $error = $response['errors'][0]['message'] ?? 'Unknown Cloudflare API error';
        return ['success' => false, 'error' => 'Cloudflare DNS error: ' . $error];
    }

    /**
     * Delete a DNS record.
     */
    private function deleteDnsRecord(string $recordId): bool
    {
        $url = "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records/{$recordId}";
        $response = $this->cfApiRequest('DELETE', $url);
        return $response && ($response['success'] ?? false);
    }

    /**
     * Create a Cloudflare Tunnel ingress rule.
     */
    private function createTunnelRule(string $tunnelId, string $fullDomain, int $port): array
    {
        // First, get current tunnel configuration
        $getUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/cfd_tunnel/{$tunnelId}/configurations";
        $currentConfig = $this->cfApiRequest('GET', $getUrl);

        if (!$currentConfig || !($currentConfig['success'] ?? false)) {
            return ['success' => false, 'error' => 'Failed to fetch tunnel configuration.'];
        }

        // Add new ingress rule
        $config = $currentConfig['result']['config'] ?? ['ingress' => [['service' => 'http_status:404']]];
        $ingress = $config['ingress'] ?? [['service' => 'http_status:404']];

        // Insert before the catch-all rule (last rule)
        $catchAll = array_pop($ingress);
        $ingress[] = [
            'hostname' => $fullDomain,
            'service'  => 'tcp://localhost:' . $port,
        ];
        $ingress[] = $catchAll;

        $config['ingress'] = $ingress;

        // Update tunnel configuration
        $putUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/cfd_tunnel/{$tunnelId}/configurations";
        $response = $this->cfApiRequest('PUT', $putUrl, ['config' => $config]);

        if ($response && ($response['success'] ?? false)) {
            return [
                'success'      => true,
                'cf_record_id' => 'tunnel_rule_' . md5($fullDomain),
            ];
        }

        $error = $response['errors'][0]['message'] ?? 'Unknown tunnel API error';
        return ['success' => false, 'error' => 'Cloudflare Tunnel error: ' . $error];
    }

    /**
     * Remove a Cloudflare Tunnel ingress rule.
     */
    private function deleteTunnelRule($accessPoint): bool
    {
        // Get node settings for tunnel ID
        $server = DB::table('servers')->where('id', $accessPoint->server_id)->first();
        if (!$server) return false;

        $nodeSettings = DB::table('cf_node_settings')
            ->where('node_id', $server->node_id)
            ->first();

        if (!$nodeSettings || !$nodeSettings->tunnel_id) return false;

        $tunnelId = $nodeSettings->tunnel_id;

        // Get current config
        $getUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/cfd_tunnel/{$tunnelId}/configurations";
        $currentConfig = $this->cfApiRequest('GET', $getUrl);

        if (!$currentConfig || !($currentConfig['success'] ?? false)) return false;

        $config = $currentConfig['result']['config'] ?? [];
        $ingress = $config['ingress'] ?? [];

        // Remove the rule matching this domain
        $config['ingress'] = array_values(array_filter($ingress, function ($rule) use ($accessPoint) {
            return ($rule['hostname'] ?? '') !== $accessPoint->full_domain;
        }));

        // Ensure catch-all rule exists
        $hasDefault = false;
        foreach ($config['ingress'] as $rule) {
            if (!isset($rule['hostname'])) {
                $hasDefault = true;
                break;
            }
        }
        if (!$hasDefault) {
            $config['ingress'][] = ['service' => 'http_status:404'];
        }

        // Update tunnel
        $putUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/cfd_tunnel/{$tunnelId}/configurations";
        $response = $this->cfApiRequest('PUT', $putUrl, ['config' => $config]);

        return $response && ($response['success'] ?? false);
    }

    /**
     * Make a Cloudflare API request.
     */
    private function cfApiRequest(string $method, string $url, ?array $data = null): ?array
    {
        try {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                Log::error('[CfSubdomain] CURL error: ' . $error);
                return null;
            }

            $decoded = json_decode($response, true);
            if ($decoded === null && $response !== '') {
                Log::error('[CfSubdomain] JSON decode error for: ' . substr($response, 0, 200));
                return null;
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error('[CfSubdomain] API request exception: ' . $e->getMessage());
            return null;
        }
    }
}
