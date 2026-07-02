<?php

namespace Pterodactyl\BlueprintFramework\Extensions\cfsubdomain;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Event Listener Service
 * 
 * This class provides static methods that can be called when
 * Pterodactyl server/allocation events occur. Since Blueprint
 * doesn't have a native event hook system, these are triggered
 * via the admin controller or API routes when actions happen.
 * 
 * For automatic interception, you would need to register these
 * in a custom ServiceProvider or use Pterodactyl's observer pattern.
 */
class EventListener
{
    /**
     * Called when a server is deleted.
     * Cleans up all Cloudflare records associated with the server.
     */
    public static function onServerDeleted(int $serverId): void
    {
        try {
            $service = new CloudflareService();
            $service->deleteAllForServer($serverId);
            Log::info("[CfSubdomain] Cleaned up all subdomains for deleted server #{$serverId}");
        } catch (\Exception $e) {
            Log::error("[CfSubdomain] Failed to clean up server #{$serverId}: " . $e->getMessage());
        }
    }

    /**
     * Called when an allocation (port) is removed from a server.
     * Cleans up the associated Cloudflare record.
     */
    public static function onAllocationRemoved(int $serverId, int $allocationId): void
    {
        try {
            $accessPoint = DB::table('cf_server_access_points')
                ->where('server_id', $serverId)
                ->where('allocation_id', $allocationId)
                ->first();

            if ($accessPoint) {
                $service = new CloudflareService();
                $service->deleteSubdomain((int) $accessPoint->id);
                Log::info("[CfSubdomain] Removed subdomain for allocation #{$allocationId} on server #{$serverId}");
            }
        } catch (\Exception $e) {
            Log::error("[CfSubdomain] Failed to clean up allocation #{$allocationId}: " . $e->getMessage());
        }
    }

    /**
     * Get all access points for a server.
     * Useful for displaying connection info on server pages.
     */
    public static function getServerAccessPoints(int $serverId): array
    {
        return DB::table('cf_server_access_points')
            ->where('server_id', $serverId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get the primary (first) connection string for a server.
     */
    public static function getPrimaryConnectionString(int $serverId): ?string
    {
        $primary = DB::table('cf_server_access_points')
            ->where('server_id', $serverId)
            ->orderBy('created_at', 'asc')
            ->first();

        return $primary ? $primary->connection_string : null;
    }
}
