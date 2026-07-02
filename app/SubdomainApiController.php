<?php

namespace Pterodactyl\BlueprintFramework\Extensions\cfsubdomain;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;

/**
 * API Controller for subdomain management.
 * Handles AJAX requests from both admin and client dashboard.
 */
class SubdomainApiController extends Controller
{
    private CloudflareService $cloudflare;

    public function __construct()
    {
        $this->cloudflare = new CloudflareService();
    }

    /**
     * Resolve the server ID from numeric ID, string, or Server model instance.
     */
    private function getServerId($server): ?int
    {
        if ($server instanceof \Pterodactyl\Models\Server) {
            return $server->id;
        }

        if (is_numeric($server)) {
            return (int) $server;
        }

        // Look up by uuid or uuidShort
        $serverModel = DB::table('servers')
            ->where('uuid', $server)
            ->orWhere('uuidShort', $server)
            ->first();

        return $serverModel ? $serverModel->id : null;
    }

    /**
     * Get all access points for a server.
     */
    public function getServerSubdomains(Request $request, $server): JsonResponse
    {
        $serverId = $this->getServerId($server);

        $accessPoints = DB::table('cf_server_access_points')
            ->where('server_id', $serverId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $accessPoints,
        ]);
    }

    /**
     * Get available allocations (ports) for a server that don't have subdomains yet.
     */
    public function getAvailablePorts(Request $request, $server): JsonResponse
    {
        $serverId = $this->getServerId($server);

        // Get all allocations for this server
        $allocations = DB::table('allocations')
            ->where('server_id', $serverId)
            ->select('id', 'port', 'ip')
            ->get();

        // Get allocations that already have subdomain bindings
        $boundAllocationIds = DB::table('cf_server_access_points')
            ->where('server_id', $serverId)
            ->pluck('allocation_id')
            ->toArray();

        // Filter to only unbound allocations
        $available = $allocations->filter(function ($alloc) use ($boundAllocationIds) {
            return !in_array($alloc->id, $boundAllocationIds);
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $available,
        ]);
    }

    /**
     * Create a new subdomain binding.
     */
    public function createSubdomain(Request $request): JsonResponse
    {
        $request->validate([
            'server_id'     => 'required',
            'allocation_id' => 'required|integer|exists:allocations,id',
            'subdomain'     => 'nullable|string|max:63|regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
        ]);

        $serverId = $this->getServerId($request->input('server_id'));
        if (!$serverId) {
            return response()->json(['success' => false, 'error' => 'Server not found.'], 404);
        }

        $allocationId = $request->input('allocation_id');
        $subdomain    = $request->input('subdomain');

        // Get allocation details
        $allocation = DB::table('allocations')->where('id', $allocationId)->first();
        if (!$allocation) {
            return response()->json(['success' => false, 'error' => 'Allocation not found.'], 404);
        }

        // Get server's node
        $server = DB::table('servers')->where('id', $serverId)->first();
        if (!$server) {
            return response()->json(['success' => false, 'error' => 'Server not found.'], 404);
        }

        $nodeId = $server->node_id;
        $port   = $allocation->port;

        // Get node settings
        $nodeSettings = DB::table('cf_node_settings')
            ->where('node_id', $nodeId)
            ->first();

        if (!$nodeSettings) {
            return response()->json(['success' => false, 'error' => 'Node not configured for Cloudflare.'], 400);
        }

        // Handle blank subdomain
        if (empty($subdomain)) {
            if ($nodeSettings->mode === 'tunneled') {
                return response()->json([
                    'success' => false,
                    'error'   => 'Subdomain is required for tunneled nodes.',
                ], 400);
            }
            // DNS-Only: auto-generate
            $subdomain = $this->cloudflare->generateDefaultSubdomain($serverId, $port);
        }

        // Check for duplicate subdomain
        $existing = DB::table('cf_server_access_points')
            ->where('subdomain', $subdomain)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error'   => 'Subdomain "' . $subdomain . '" is already in use.',
            ], 409);
        }

        // Create via Cloudflare service
        $userId = $request->user() ? $request->user()->id : null;
        $result = $this->cloudflare->createSubdomain(
            $serverId,
            $allocationId,
            $port,
            $subdomain,
            $nodeId,
            $userId
        );

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 500);
    }

    /**
     * Delete a subdomain binding.
     */
    public function deleteSubdomain(Request $request, string $id): JsonResponse
    {
        $accessPoint = DB::table('cf_server_access_points')
            ->where('id', $id)
            ->first();

        if (!$accessPoint) {
            return response()->json(['success' => false, 'error' => 'Not found.'], 404);
        }

        // Check ownership if client (non-admin) request
        if ($request->user() && !$request->user()->root_admin) {
            $server = DB::table('servers')
                ->where('id', $accessPoint->server_id)
                ->first();

            if (!$server || $server->owner_id !== $request->user()->id) {
                return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
            }
        }

        $result = $this->cloudflare->deleteSubdomain((int) $id);

        if ($result['success']) {
            return response()->json(['success' => true]);
        }

        return response()->json($result, 500);
    }

    /**
     * Get node settings for a specific node.
     */
    public function getNodeSettings(Request $request, string $nodeId): JsonResponse
    {
        $settings = DB::table('cf_node_settings')
            ->where('node_id', $nodeId)
            ->first();

        return response()->json([
            'success'    => true,
            'configured' => $settings !== null,
            'data'       => $settings,
        ]);
    }
}
