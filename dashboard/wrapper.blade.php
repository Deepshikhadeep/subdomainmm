@if(isset($server))
{{-- Dashboard Wrapper - Subdomain management popup for client dashboard --}}
<link rel="stylesheet" href="/extensions/cfsubdomain/dashboard.css">
<meta name="cf-base-domain" content="{{ app('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary')->dbGet('cfsubdomain', 'base_domain') }}">
<meta name="cf-server-id" content="{{ $server->id }}">
<meta name="cf-server-uuid" content="{{ $server->uuidShort }}">

{{-- Modal Overlay --}}
<div id="cf-subdomain-modal" class="cf-modal-overlay" style="display:none;">
    <div class="cf-modal">
        <div class="cf-modal-header">
            <div class="cf-modal-icon">🌐</div>
            <h3 class="cf-modal-title">Manage Subdomains</h3>
            <button class="cf-modal-close" onclick="CfSubdomain.closeModal()">&times;</button>
        </div>
        <div class="cf-modal-body">
            <div id="cf-loading" class="cf-loading">
                <div class="cf-spinner"></div>
                <p>Loading...</p>
            </div>
            <div id="cf-subdomains-list" style="display:none;">
                <h4 class="cf-section-title">Active Subdomains</h4>
                <div id="cf-subdomains-table"></div>
            </div>
            <div id="cf-add-form" style="display:none;">
                <h4 class="cf-section-title">Bind New Subdomain</h4>
                <div class="cf-form-group">
                    <label class="cf-label">Port</label>
                    <select id="cf-port-select" class="cf-input"><option value="">Select port...</option></select>
                </div>
                <div class="cf-form-group">
                    <label class="cf-label">Subdomain</label>
                    <div class="cf-input-group">
                        <input type="text" id="cf-subdomain-input" class="cf-input" placeholder="myserver" maxlength="63" />
                        <span class="cf-input-suffix" id="cf-domain-suffix">.domain.com</span>
                    </div>
                </div>
                <div class="cf-preview" id="cf-connection-preview" style="display:none;">
                    <label class="cf-label">Preview</label>
                    <code id="cf-preview-string">—</code>
                </div>
                <div class="cf-form-actions">
                    <button class="cf-btn cf-btn-secondary" onclick="CfSubdomain.showList()">Cancel</button>
                    <button class="cf-btn cf-btn-primary" id="cf-bind-btn" onclick="CfSubdomain.bindSubdomain()">🔗 Bind</button>
                </div>
            </div>
            <div id="cf-success" style="display:none;">
                <div class="cf-success-icon">✅</div>
                <h4>Subdomain Bound!</h4>
                <code id="cf-success-connection">—</code>
                <button class="cf-btn cf-btn-primary" onclick="CfSubdomain.showList()" style="margin-top:16px;">← Back</button>
            </div>
            <div id="cf-error" style="display:none;">
                <div class="cf-error-icon">❌</div>
                <p id="cf-error-message"></p>
                <button class="cf-btn cf-btn-secondary" onclick="CfSubdomain.showAddForm()">Retry</button>
            </div>
        </div>
    </div>
</div>

{{-- FAB Button --}}
<div id="cf-fab" class="cf-fab" style="display:none;" onclick="CfSubdomain.openModal()">
    <span>🌐</span><span class="cf-fab-text">Subdomains</span>
</div>

{{-- Connection Banner --}}
<div id="cf-connection-banner" class="cf-banner" style="display:none;">
    <span>🔗</span>
    <code id="cf-banner-connection">—</code>
    <button class="cf-btn cf-btn-sm" onclick="CfSubdomain.copyBanner(this)">📋</button>
</div>

<script src="/extensions/cfsubdomain/subdomain-client.js"></script>
@endif
