/**
 * CfSubdomain - Client-side subdomain management.
 * Loaded on every Pterodactyl client page via dashboard.wrapper.
 */
const CfSubdomain = {
    serverId: null,
    serverUuid: null,
    baseDomain: 'yourdomain.com',
    apiBase: '/api/client/extensions/cfsubdomain',
    nodeMode: null,
    defaultDomain: null,
    selectedPort: null,
    isFirstVisit: false,

    init() {
        const idMeta = document.querySelector('meta[name="cf-server-id"]');
        const uuidMeta = document.querySelector('meta[name="cf-server-uuid"]');
        
        if (idMeta) this.serverId = parseInt(idMeta.content);
        if (uuidMeta) this.serverUuid = uuidMeta.content;

        if (!this.serverUuid) {
            const match = window.location.pathname.match(/\/server\/([a-zA-Z0-9]+)/);
            if (!match) return;
            this.serverUuid = match[1];
        }

        // Read base domain from a meta tag injected by the wrapper
        const meta = document.querySelector('meta[name="cf-base-domain"]');
        if (meta && meta.content) this.baseDomain = meta.content;

        const suffix = document.getElementById('cf-domain-suffix');
        if (suffix) suffix.textContent = '.' + this.baseDomain;

        // Ensure FAB button is visible and clickable
        const fab = document.getElementById('cf-fab');
        if (fab) {
            fab.style.display = 'flex';
            fab.style.opacity = '1';
            fab.style.pointerEvents = 'auto';
        }

        this.loadBanner();

        const input = document.getElementById('cf-subdomain-input');
        if (input) input.addEventListener('input', () => this.updatePreview());
    },

    async loadBanner() {
        try {
            const resp = await fetch(this.apiBase + '/servers/' + this.serverUuid + '/subdomains', {
                headers: this.getHeaders()
            });
            const data = await resp.json();
            if (data.success && data.data && data.data.length > 0) {
                const primary = data.data[0];
                document.getElementById('cf-banner-connection').textContent = primary.connection_string;
                document.getElementById('cf-connection-banner').style.display = 'flex';
                this.serverId = primary.server_id;
                this.isFirstVisit = false;
            } else if (data.success && (!data.data || data.data.length === 0)) {
                // First visit: no subdomains exist
                this.isFirstVisit = true;
                this.updateModalForFirstVisit();
                setTimeout(() => this.openModal(), 500);
            }
        } catch (e) {
            console.warn('CfSubdomain: banner load failed', e);
        }
    },

    updateModalForFirstVisit() {
        const modalTitle = document.querySelector('.cf-modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Set Up Your Subdomain';
        }
        const hint = document.getElementById('cf-first-visit-hint');
        if (hint) {
            hint.style.display = 'block';
        }
    },

    openModal() {
        const modal = document.getElementById('cf-subdomain-modal');
        if (!modal) return;
        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.style.zIndex = '9999';
        this.showList();
    },

    closeModal() {
        const modal = document.getElementById('cf-subdomain-modal');
        if (modal) modal.style.display = 'none';
    },

    async showList() {
        // Reset modal title if it was changed for first visit
        if (!this.isFirstVisit) {
            const modalTitle = document.querySelector('.cf-modal-title');
            if (modalTitle) {
                modalTitle.textContent = 'Manage Subdomains';
            }
            const hint = document.getElementById('cf-first-visit-hint');
            if (hint) {
                hint.style.display = 'none';
            }
        }
        
        this.hideAll();
        document.getElementById('cf-loading').style.display = 'flex';

        try {
            const resp = await fetch(this.apiBase + '/servers/' + this.serverUuid + '/subdomains', {
                headers: this.getHeaders()
            });
            const data = await resp.json();
            document.getElementById('cf-loading').style.display = 'none';
            document.getElementById('cf-subdomains-list').style.display = 'block';

            const tableEl = document.getElementById('cf-subdomains-table');
            
            if (!data.success) {
                this.showError('Failed to load subdomains: ' + (data.error || 'Unknown error'));
                return;
            }

            if (data.data && data.data.length > 0) {
                this.serverId = data.data[0].server_id;
                let html = '<div class="cf-table">';
                html += '<div class="cf-table-header"><span>Connection</span><span>Port</span><span>Mode</span><span></span></div>';
                data.data.forEach(function(ap) {
                    const badge = ap.node_mode === 'tunneled'
                        ? '<span class="cf-badge cf-badge-green">🔒 Tunnel</span>'
                        : '<span class="cf-badge cf-badge-blue">🌍 DNS</span>';
                    html += '<div class="cf-table-row">';
                    html += '<span><code>' + ap.connection_string + '</code></span>';
                    html += '<span>' + ap.port + '</span>';
                    html += '<span>' + badge + '</span>';
                    html += '<span><button class="cf-btn cf-btn-xs cf-btn-danger" onclick="CfSubdomain.deleteSubdomain(' + ap.id + ')">🗑️</button></span>';
                    html += '</div>';
                });
                html += '</div>';
                tableEl.innerHTML = html;
            } else {
                tableEl.innerHTML = '<div class="cf-empty"><p>No subdomains yet. Add one to get started!</p></div>';
            }
            tableEl.innerHTML += '<button class="cf-btn cf-btn-primary cf-btn-full" onclick="CfSubdomain.showAddForm()" style="margin-top:16px;">+ Add Subdomain</button>';
        } catch (e) {
            document.getElementById('cf-loading').style.display = 'none';
            this.showError('Failed to load: ' + e.message);
        }
    },

    async showAddForm() {
        this.hideAll();
        document.getElementById('cf-loading').style.display = 'flex';

        try {
            const resp = await fetch(this.apiBase + '/servers/' + this.serverUuid + '/ports', {
                headers: this.getHeaders()
            });
            const data = await resp.json();
            document.getElementById('cf-loading').style.display = 'none';

            // Check if ports are available
            if (!data.success) {
                this.showError('Failed to load ports: ' + (data.error || 'Unknown error'));
                return;
            }

            if (!data.data || data.data.length === 0) {
                this.showError('No available ports. All ports already have subdomains bound, or server has no ports configured.');
                return;
            }

            // Store node mode and default domain
            this.nodeMode = data.nodeMode;
            this.defaultDomain = data.defaultDomain;

            const select = document.getElementById('cf-port-select');
            select.innerHTML = '<option value="">Select port...</option>';

            // Populate ports
            data.data.forEach(function(alloc) {
                const opt = document.createElement('option');
                opt.value = alloc.id;
                opt.textContent = 'Port ' + alloc.port + ' (' + (alloc.ip || '0.0.0.0') + ')';
                opt.dataset.port = alloc.port;
                opt.dataset.ip = alloc.ip || '0.0.0.0';
                select.appendChild(opt);
            });

            // Display form with appropriate mode
            this.displayFormByMode();

            document.getElementById('cf-add-form').style.display = 'block';
            document.getElementById('cf-subdomain-input').value = '';
            document.getElementById('cf-connection-preview').style.display = 'none';
            select.onchange = function() { CfSubdomain.onPortSelected(); };
        } catch (e) {
            document.getElementById('cf-loading').style.display = 'none';
            this.showError('Failed to load ports: ' + e.message);
        }
    },

    displayFormByMode() {
        const subdomainLabel = document.getElementById('cf-subdomain-label');
        const subdomainInput = document.getElementById('cf-subdomain-input');
        const modeHint = document.getElementById('cf-mode-hint');

        if (this.nodeMode === 'tunneled') {
            // TUNNELED: Subdomain is REQUIRED
            if (subdomainLabel) {
                subdomainLabel.innerHTML = 'Subdomain <span style="color: red;">*</span>';
            }
            if (subdomainInput) {
                subdomainInput.required = true;
                subdomainInput.placeholder = 'e.g. gameserver (required)';
            }
            if (modeHint) {
                modeHint.innerHTML = '<strong>🔒 Tunneled Mode:</strong> Subdomain is required for tunnel routing.';
                modeHint.style.display = 'block';
            }
        } else {
            // DNS-ONLY: Subdomain is OPTIONAL
            if (subdomainLabel) {
                subdomainLabel.innerHTML = 'Subdomain <span style="color: #888;">(optional)</span>';
            }
            if (subdomainInput) {
                subdomainInput.required = false;
                subdomainInput.placeholder = 'e.g. gameserver (or leave blank for default)';
            }
            if (modeHint) {
                modeHint.innerHTML = '<strong>🌍 DNS-Only Mode:</strong> Subdomain is optional. Leave blank to use: <code>' + (this.defaultDomain || 'server.yourdomain.com') + '</code>';
                modeHint.style.display = 'block';
            }
        }
    },

    onPortSelected() {
        var select = document.getElementById('cf-port-select');
        if (select.value) {
            document.getElementById('cf-connection-preview').style.display = 'block';
            this.updatePreview();
        }
    },

    updatePreview() {
        var sub = document.getElementById('cf-subdomain-input').value.toLowerCase().trim();
        var select = document.getElementById('cf-port-select');
        var opt = select.options[select.selectedIndex];
        var port = opt ? (opt.dataset.port || '?') : '?';
        var preview = (sub || 'auto') + '.' + this.baseDomain + ':' + port;
        document.getElementById('cf-preview-string').textContent = preview;
    },

    async bindSubdomain() {
        var allocId = document.getElementById('cf-port-select').value;
        var subdomain = document.getElementById('cf-subdomain-input').value.toLowerCase().trim();
        
        if (!allocId) { 
            this.showError('Please select a port first.');
            return;
        }

        // TUNNELED MODE: Subdomain is REQUIRED
        if (this.nodeMode === 'tunneled') {
            if (!subdomain) {
                this.showError('Subdomain is required for tunneled mode.');
                return;
            }
        }

        // Validate subdomain format if provided
        if (subdomain && !/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/.test(subdomain)) {
            this.showError('Subdomain must contain only letters, numbers, and hyphens.');
            return;
        }

        if (subdomain && subdomain.length > 63) {
            this.showError('Subdomain must be 63 characters or less.');
            return;
        }

        var btn = document.getElementById('cf-bind-btn');
        btn.disabled = true;
        btn.textContent = '⏳ Binding...';

        try {
            var resp = await fetch(this.apiBase + '/subdomains', {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    server_id: this.serverId,
                    allocation_id: parseInt(allocId),
                    subdomain: subdomain || null
                })
            });
            var data = await resp.json();
            btn.disabled = false;
            btn.textContent = '🔗 Bind';

            if (data.success) {
                this.hideAll();
                document.getElementById('cf-success').style.display = 'block';
                document.getElementById('cf-success-connection').textContent = data.connection_string;
                document.getElementById('cf-banner-connection').textContent = data.connection_string;
                document.getElementById('cf-connection-banner').style.display = 'flex';
            } else {
                this.showError(data.error || 'Unknown error occurred.');
            }
        } catch (e) {
            btn.disabled = false;
            btn.textContent = '🔗 Bind';
            this.showError('Network error: ' + e.message);
        }
    },

    async deleteSubdomain(id) {
        if (!confirm('Delete this subdomain binding? The Cloudflare record will be removed.')) return;
        try {
            var resp = await fetch(this.apiBase + '/subdomains/' + id, {
                method: 'DELETE',
                headers: this.getHeaders()
            });
            var data = await resp.json();
            if (data.success) {
                this.showList();
            } else {
                this.showError('Failed to delete: ' + (data.error || 'Unknown error'));
            }
        } catch (e) { 
            console.error('[CfSubdomain]', e);
            this.showError('Network error: ' + e.message);
        }
    },

    showError(msg) {
        this.hideAll();
        document.getElementById('cf-error').style.display = 'block';
        document.getElementById('cf-error-message').textContent = msg;
    },

    hideAll() {
        ['cf-loading','cf-subdomains-list','cf-add-form','cf-success','cf-error'].forEach(function(id) {
            document.getElementById(id).style.display = 'none';
        });
    },

    copyBanner(btn) {
        var t = document.getElementById('cf-banner-connection').textContent;
        navigator.clipboard.writeText(t).then(function() {
            btn.textContent = '✅'; setTimeout(function() { btn.textContent = '📋'; }, 1500);
        });
    },

    getHeaders() {
        var csrf = document.querySelector('meta[name="csrf-token"]');
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf ? csrf.content : ''
        };
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { CfSubdomain.init(); });
} else {
    CfSubdomain.init();
}
