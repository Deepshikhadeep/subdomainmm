<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\{identifier};

use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Illuminate\Support\Facades\DB;

class {identifier}ExtensionController extends Controller
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
        // Load global Cloudflare configuration
        $cf_api_key    = $this->blueprint->dbGet('{identifier}', 'cf_api_key');
        $cf_zone_id    = $this->blueprint->dbGet('{identifier}', 'cf_zone_id');
        $cf_account_id = $this->blueprint->dbGet('{identifier}', 'cf_account_id');
        $base_domain   = $this->blueprint->dbGet('{identifier}', 'base_domain');

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
            ->join('servers', 'cf_server_access_points.server_id', '=', 'servers.id')
            ->select(
                'cf_server_access_points.*',
                'servers.name as server_name'
            )
            ->orderBy('cf_server_access_points.created_at', 'desc')
            ->get();

        return $this->view->make(
            'admin.extensions.{identifier}.index', [
                'root'         => '/admin/extensions/{identifier}',
                'blueprint'    => $this->blueprint,
                'cf_api_key'   => $cf_api_key,
                'cf_zone_id'   => $cf_zone_id,
                'cf_account_id'=> $cf_account_id,
                'base_domain'  => $base_domain,
                'nodes'        => $nodes,
                'accessPoints' => $accessPoints,
            ]
        );
    }

    /**
     * PATCH /admin/extensions/cfsubdomain
     * Save global Cloudflare configuration.
     */
    public function update(CfSubdomainSettingsFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->blueprint->dbSet('{identifier}', $key, $value);
        }

        return redirect()->route('admin.extensions.{identifier}.index')
            ->with('success', 'Cloudflare settings saved successfully.');
    }

    /**
     * POST /admin/extensions/cfsubdomain
     * Save per-node Cloudflare configuration.
     */
    public function post($request): RedirectResponse
    {
        $request->validate([
            'node_id'        => 'required|integer|exists:nodes,id',
            'mode'           => 'required|in:tunneled,dns_only',
            'tunnel_id'      => 'nullable|string|max:255',
            'default_domain' => 'nullable|string|max:255',
        ]);

        DB::table('cf_node_settings')->updateOrInsert(
            ['node_id' => $request->input('node_id')],
            [
                'mode'           => $request->input('mode'),
                'tunnel_id'      => $request->input('mode') === 'tunneled' ? $request->input('tunnel_id') : null,
                'default_domain' => $request->input('mode') === 'dns_only' ? $request->input('default_domain') : null,
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
        );

        return redirect()->route('admin.extensions.{identifier}.index')
            ->with('success', 'Node settings saved successfully.');
    }

    /**
     * DELETE /admin/extensions/cfsubdomain/{target}/{id}
     * Delete an access point (subdomain binding).
     */
    public function delete($request, $target, $id): RedirectResponse
    {
        if ($target === 'access-point') {
            $accessPoint = DB::table('cf_server_access_points')->where('id', $id)->first();

            if ($accessPoint && $accessPoint->cf_record_id) {
                // Call Cloudflare API to delete the record
                $this->deleteCloudflareRecord($accessPoint);
            }

            DB::table('cf_server_access_points')->where('id', $id)->delete();

            return redirect()->route('admin.extensions.{identifier}.index')
                ->with('success', 'Subdomain removed successfully.');
        }

        return redirect()->route('admin.extensions.{identifier}.index')
            ->with('error', 'Unknown target.');
    }

    /**
     * Delete a Cloudflare record (Tunnel rule or DNS record).
     */
    private function deleteCloudflareRecord($accessPoint): void
    {
        $apiKey    = $this->blueprint->dbGet('{identifier}', 'cf_api_key');
        $zoneId    = $this->blueprint->dbGet('{identifier}', 'cf_zone_id');

        if (empty($apiKey) || empty($zoneId)) {
            return;
        }

        if ($accessPoint->node_mode === 'dns_only') {
            // Delete DNS record
            $url = "https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records/{$accessPoint->cf_record_id}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
        // For tunneled mode, tunnel config update would be handled separately
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
