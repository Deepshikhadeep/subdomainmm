<?php

namespace Pterodactyl\BlueprintFramework\Extensions\cfsubdomain;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        try {
            $serverId = $this->getServerId($server);

            if (!$serverId) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Server not found.',
                ], 404);
            }

            $accessPoints = DB::table('cf_server_access_points')
                ->where('server_id', $serverId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $accessPoints,
            ]);
            } catch (\Exception $e) {
            Log::error('[CfSubdomain] Get subdomains error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Failed to load subdomains.',
            ], 500);
        }
    }

    /**
     * Get available allocations (ports) for a server that don't have subdomains yet.
     * Also returns node mode to determine if subdomain is required.
     */
    public function getAvailablePorts(Request $request, $server): JsonResponse
    {
        try {
            $serverId = $this->getServerId($server);

            if (!$serverId) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Server not found.',
                ], 404);
            }

            // Get server to find its node
            $serverModel = DB::table('servers')
                ->where('id', $serverId)
                ->first(['id', 'node_id']);

            if (!$serverModel) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Server not found.',
                ], 404);
            }

            // Get node mode
            $nodeSettings = DB::table('cf_node_settings')
                ->where('node_id', $serverModel->node_id)
                ->first(['mode', 'default_domain']);

            // Get all allocations for this server
            $allocations = DB::table('allocations')
                ->where('server_id', $serverId)
                ->select('id', 'port', 'ip')
                ->orderBy('port', 'asc')
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
                'nodeMode' => $nodeSettings ? $nodeSettings->mode : null,
                'defaultDomain' => $nodeSettings ? $nodeSettings->default_domain : null,
            ]);
        } catch (\Exception $e) {
            Log::error('[CfSubdomain] Get available ports error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Failed to load available ports.',
            ], 500);
        }
    }

    /**
     * Create a new subdomain binding.
     */
    public function createSubdomain(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'server_id'     => 'required',
                'allocation_id' => 'required|integer|exists:allocations,id',
                'subdomain'     => 'nullable|string|max:63|regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/',
            ]);

            $serverId = $this->getServerId($validated['server_id']);
            if (!$serverId) {
                return response()->json(['success' => false, 'error' => 'Server not found.'], 404);
            }

            $allocationId = $validated['allocation_id'];
            $subdomain    = $validated['subdomain'];

            // Get allocation details
            $allocation = DB::table('allocations')
                ->where('id', $allocationId)
                ->where('server_id', $serverId)
                ->first();
                
            if (!$allocation) {
                return response()->json(['success' => false, 'error' => 'Allocation not found for this server.'], 404);
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
                return response()->json(['success' => false, 'error' => 'Node not configured for Cloudflare. Please configure it in admin panel.'], 400);
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
            } else {
                // Validate subdomain format
                if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/i', $subdomain)) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Subdomain must contain only alphanumeric characters and hyphens.',
                    ], 400);
                }
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
                Log::info('[CfSubdomain] Subdomain created: ' . $subdomain);
                return response()->json($result, 201);
            }

            return response()->json($result, 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Validation failed: ' . implode(', ', $this->flattenErrors($e->errors())),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[CfSubdomain] Create subdomain error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'An error occurred while creating the subdomain.',
            ], 500);
        }
    }

    /**
     * Delete a subdomain binding.
     */
    public function deleteSubdomain(Request $request, string $id): JsonResponse
    {
        try {
            if (!is_numeric($id)) {
                return response()->json(['success' => false, 'error' => 'Invalid ID.'], 400);
            }

            $accessPoint = DB::table('cf_server_access_points')
                ->where('id', (int) $id)
                ->first();

            if (!$accessPoint) {
                return response()->json(['success' => false, 'error' => 'Subdomain binding not found.'], 404);
            }

            // Check ownership if client (non-admin) request
            if ($request->user() && !$request->user()->root_admin) {
                $server = DB::table('servers')
                    ->where('id', $accessPoint->server_id)
                    ->first();

                if (!$server || $server->owner_id !== $request->user()->id) {
                    return response()->json(['success' => false, 'error' => 'Unauthorized to delete this binding.'], 403);
                }
            }

            $result = $this->cloudflare->deleteSubdomain((int) $id);

            if ($result['success']) {
                Log::info('[CfSubdomain] Subdomain deleted: ' . $id);
                return response()->json(['success' => true]);
            }

            return response()->json($result, 500);
        } catch (\Exception $e) {
            Log::error('[CfSubdomain] Delete subdomain error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'An error occurred while deleting the subdomain.',
            ], 500);
        }
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

    /**
     * Flatten error array for display.
     */
    private function flattenErrors(array $errors): array
    {
        $result = [];
        foreach ($errors as $key => $messages) {
            if (is_array($messages)) {
                $result = array_merge($result, $messages);
            } else {
                $result[] = $messages;
            }
        }
        return $result;
    }
}
