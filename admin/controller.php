<?php

namespace Pterodactyl\BlueprintFramework\Extensions\cfsubdomain;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CfSubdomainController extends Controller
{
    public function __construct(
        private ViewFactory $view,
        private BlueprintExtensionLibrary $blueprint,
    ) {}

    /**
     * GET /admin/extensions/cfsubdomain
     * Renders the admin settings page with all configuration + node settings.
     */
    public function index(): View
    {
        try {
            // Load global Cloudflare configuration
            $cf_api_key    = $this->blueprint->dbGet('cfsubdomain', 'cf_api_key');
            $cf_zone_id    = $this->blueprint->dbGet('cfsubdomain', 'cf_zone_id');
            $cf_account_id = $this->blueprint->dbGet('cfsubdomain', 'cf_account_id');
            $base_domain   = $this->blueprint->dbGet('cfsubdomain', 'base_domain');

            // Load all nodes with their CF settings
            $nodes = DB::table('nodes')
                ->leftJoin('cf_node_settings', 'nodes.id', '=', 'cf_node_settings.node_id')
                ->select(
                    'nodes.id',
                    'nodes.name',
                    'nodes.fqdn',
                    'cf_node_settings.mode',
                    'cf_node_settings.tunnel_id',
                    'cf_node_settings.default_domain'
                )
                ->get();

            // Load all access points with server info
            $accessPoints = DB::table('cf_server_access_points')
                ->leftJoin('servers', 'cf_server_access_points.server_id', '=', 'servers.id')
                ->select(
                    'cf_server_access_points.*',
                    'servers.name as server_name'
                )
                ->orderBy('cf_server_access_points.created_at', 'desc')
                ->get();

            return $this->view->make(
                'blueprint.admin.extensions.cfsubdomain.view', [
                    'root'         => '/admin/extensions/cfsubdomain',
                    'blueprint'    => $this->blueprint,
                    'cf_api_key'   => $cf_api_key,
                    'cf_zone_id'   => $cf_zone_id,
                    'cf_account_id'=> $cf_account_id,
                    'base_domain'  => $base_domain,
                    'nodes'        => $nodes,
                    'accessPoints' => $accessPoints,
                ]
            );
        } catch (\Exception $e) {
            Log::error('[CfSubdomain] Admin index error: ' . $e->getMessage());
            return $this->view->make('blueprint.admin.extensions.cfsubdomain.view', [
                'root'         => '/admin/extensions/cfsubdomain',
                'blueprint'    => $this->blueprint,
                'cf_api_key'   => '',
                'cf_zone_id'   => '',
                'cf_account_id'=> '',
                'base_domain'  => '',
                'nodes'        => [],
                'accessPoints' => [],
                'error'        => 'Failed to load extension data.',
            ]);
        }
    }

    /**
     * PATCH /admin/extensions/cfsubdomain
     * Save global Cloudflare configuration.
     */
    public function update(CfSubdomainSettingsFormRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            foreach ($data as $key => $value) {
                $this->blueprint->dbSet('cfsubdomain', $key, $value);
            }

            Log::info('[CfSubdomain] Admin updated global settings');
            return redirect()->route('admin.extensions.view', ['extension' => 'cfsubdomain'])
                ->with('success', 'Cloudflare settings saved successfully.');
        } catch (\Exception $e) {
            Log::error('[CfSubdomain] Settings update error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to save settings: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/extensions/cfsubdomain/nodes
     * Save per-node Cloudflare configuration.
     */
    public function storeNode(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'node_id'        => 'required|integer|exists:nodes,id',
                'mode'           => 'required|in:tunneled,dns_only',
                'tunnel_id'      => 'nullable|string|max:255',
                'default_domain' => 'nullable|string|max:255',
            ]);

            // Only store tunnel_id if mode is tunneled
            if ($validated['mode'] === 'dns_only') {
                $validated['tunnel_id'] = null;
            }
            // Only store default_domain if mode is dns_only
            if ($validated['mode'] === 'tunneled') {
                $validated['default_domain'] = null;
            }

            DB::table('cf_node_settings')->updateOrInsert(
                ['node_id' => $validated['node_id']],
                array_merge($validated, ['updated_at' => now()])
            );

            Log::info('[CfSubdomain] Node ' . $validated['node_id'] . ' settings updated');
            return redirect()->route('admin.extensions.view', ['extension' => 'cfsubdomain'])
                ->with('success', 'Node settings saved successfully.');
        } catch (\Exception $e) {
            Log::error('[CfSubdomain] Node settings error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to save node settings: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /admin/extensions/cfsubdomain/access-points/{id}
     * Delete an access point (subdomain binding).
     */
    public function deleteAccessPoint(Request $request, int $id): RedirectResponse
    {
        try {
            $accessPoint = DB::table('cf_server_access_points')->where('id', $id)->first();

            if (!$accessPoint) {
                return redirect()->back()
                    ->with('error', 'Subdomain binding not found.');
            }

            // Use CloudflareService to properly clean up
            $service = new CloudflareService();
            $result = $service->deleteSubdomain($id);

            if ($result['success']) {
                Log::info('[CfSubdomain] Admin deleted access point ' . $id);
                return redirect()->route('admin.extensions.view', ['extension' => 'cfsubdomain'])
                    ->with('success', 'Subdomain removed successfully.');
            }

            return redirect()->back()
                ->with('error', $result['error'] ?? 'Failed to delete subdomain.');
        } catch (\Exception $e) {
            Log::error('[CfSubdomain] Delete access point error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}

/**
 * Form validation for global Cloudflare settings.
 */
class CfSubdomainSettingsFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'cf_api_key'    => ['nullable', 'string', 'max:255'],
            'cf_zone_id'    => ['nullable', 'string', 'max:255'],
            'cf_account_id' => ['nullable', 'string', 'max:255'],
            'base_domain'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'cf_api_key'    => 'Cloudflare API Key',
            'cf_zone_id'    => 'Cloudflare Zone ID',
            'cf_account_id' => 'Cloudflare Account ID',
            'base_domain'   => 'Base Domain',
        ];
    }
}

/**
 * Form validation for global Cloudflare settings.
 */
class CfSubdomainSettingsFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'cf_api_key'    => ['nullable', 'string', 'max:255'],
            'cf_zone_id'    => ['nullable', 'string', 'max:255'],
            'cf_account_id' => ['nullable', 'string', 'max:255'],
            'base_domain'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cf_api_key'    => 'Cloudflare API Key',
            'cf_zone_id'    => 'Cloudflare Zone ID',
            'cf_account_id' => 'Cloudflare Account ID',
            'base_domain'   => 'Base Domain',
        ];
    }
}
