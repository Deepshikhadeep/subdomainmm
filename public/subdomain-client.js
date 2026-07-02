/**
 * CfSubdomain - Client-side subdomain management.
 * Loaded on every Pterodactyl client page via dashboard.wrapper.
 */
const CfSubdomain = {
    serverId: null,
    serverUuid: null,
    baseDomain: 'yourdomain.com',
    apiBase: '/api/client/extensions/cfsubdomain',

    init() {
        const match = window.location.pathname.match(/\/server\/([a-zA-Z0-9]+)/);
        if (!match) return;

        this.serverUuid = match[1];

        // Read base domain from a meta tag injected by the wrapper
        const meta = document.querySelector('meta[name="cf-base-domain"]');
        if (meta) this.baseDomain = meta.content;

        const suffix = document.getElementById('cf-domain-suffix');
        if (suffix) suffix.textContent = '.' + this.baseDomain;

        document.getElementById('cf-fab').style.display = 'flex';
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
            }
        } catch (e) {
            console.warn('CfSubdomain: banner load failed', e);
        }
    },

    openModal() {
        document.getElementById('cf-subdomain-modal').style.display = 'flex';
        this.showList();
    },

    closeModal() {
        document.getElementById('cf-subdomain-modal').style.display = 'none';
    },

    async showList() {
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
            if (data.success && data.data && data.data.length > 0) {
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
                tableEl.innerHTML = '<div class="cf-empty"><p>No subdomains yet.</p></div>';
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
            document.getElementById('cf-add-form').style.display = 'block';

            const select = document.getElementById('cf-port-select');
            select.innerHTML = '<option value="">Select port...</option>';
            if (data.success && data.data) {
                data.data.forEach(function(alloc) {
                    const opt = document.createElement('option');
                    opt.value = alloc.id;
                    opt.textContent = 'Port ' + alloc.port;
                    opt.dataset.port = alloc.port;
                    select.appendChild(opt);
                });
            }
            document.getElementById('cf-subdomain-input').value = '';
            document.getElementById('cf-connection-preview').style.display = 'none';
            select.onchange = function() { CfSubdomain.onPortSelected(); };
        } catch (e) {
            document.getElementById('cf-loading').style.display = 'none';
            this.showError('Failed to load ports: ' + e.message);
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
        if (!allocId) { alert('Select a port first.'); return; }

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
                this.showError(data.error || 'Unknown error');
            }
        } catch (e) {
            btn.disabled = false;
            btn.textContent = '🔗 Bind';
            this.showError('Network error: ' + e.message);
        }
    },

    async deleteSubdomain(id) {
        if (!confirm('Delete this subdomain?')) return;
        try {
            var resp = await fetch(this.apiBase + '/subdomains/' + id, {
                method: 'DELETE',
                headers: this.getHeaders()
            });
            var data = await resp.json();
            if (data.success) this.showList();
            else alert('Error: ' + (data.error || 'Failed'));
        } catch (e) { alert('Network error: ' + e.message); }
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
