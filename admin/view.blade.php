@extends('layouts.admin')
@include('blueprint.admin.template')

@section('title')
    Cloudflare Subdomain Manager
@endsection

@section('content-header')
    <h1>Cloudflare Subdomain Manager<small>Manage subdomains for your server ports.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.extensions') }}">Extensions</a></li>
        <li class="active">Cloudflare Subdomain Manager</li>
    </ol>
@endsection

@section('content')
    {{-- Success/Error Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissable">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    {{-- ======================== --}}
    {{-- SECTION 1: Global Config --}}
    {{-- ======================== --}}
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-cloud"></i> Global Cloudflare Configuration</h3>
                </div>
                <form action="" method="POST">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cf_api_key">Cloudflare API Token</label>
                                    <input type="password" name="cf_api_key" id="cf_api_key"
                                           class="form-control"
                                           value="{{ $cf_api_key }}"
                                           placeholder="Enter your Cloudflare API token" />
                                    <p class="text-muted small">
                                        <i class="fa fa-lock"></i> Stored securely. Used for all Cloudflare API calls (server-side only).
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cf_zone_id">Cloudflare Zone ID</label>
                                    <input type="text" name="cf_zone_id" id="cf_zone_id"
                                           class="form-control"
                                           value="{{ $cf_zone_id }}"
                                           placeholder="e.g. a1b2c3d4e5f6..." />
                                    <p class="text-muted small">Found in your Cloudflare dashboard → Domain → Overview → API section.</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cf_account_id">Cloudflare Account ID</label>
                                    <input type="text" name="cf_account_id" id="cf_account_id"
                                           class="form-control"
                                           value="{{ $cf_account_id }}"
                                           placeholder="e.g. x9y8z7w6v5u4..." />
                                    <p class="text-muted small">Required for Tunnel mode. Found in Cloudflare dashboard → Account Home.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="base_domain">Base Domain</label>
                                    <input type="text" name="base_domain" id="base_domain"
                                           class="form-control"
                                           value="{{ $base_domain }}"
                                           placeholder="e.g. yourhost.com" />
                                    <p class="text-muted small">Subdomains will be created as <code>name.{{ $base_domain ?: 'yourhost.com' }}</code></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">
                            <i class="fa fa-save"></i> Save Global Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================== --}}
    {{-- SECTION 2: Per-Node Config --}}
    {{-- ========================== --}}
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-server"></i> Node Cloudflare Settings</h3>
                    <div class="box-tools">
                        <span class="label label-default">{{ count($nodes) }} node(s)</span>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Node</th>
                                <th>FQDN</th>
                                <th>Mode</th>
                                <th>Tunnel ID</th>
                                <th>Default Domain</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($nodes as $node)
                            <tr>
                                <td><strong>{{ $node->name }}</strong></td>
                                <td><code>{{ $node->fqdn }}</code></td>
                                <td>
                                    @if($node->mode === 'tunneled')
                                        <span class="label label-success"><i class="fa fa-shield"></i> Tunneled</span>
                                    @elseif($node->mode === 'dns_only')
                                        <span class="label label-info"><i class="fa fa-globe"></i> DNS-Only</span>
                                    @else
                                        <span class="label label-default"><i class="fa fa-question"></i> Not Configured</span>
                                    @endif
                                </td>
                                <td>
                                    @if($node->tunnel_id)
                                        <code>{{ $node->tunnel_id }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($node->default_domain)
                                        <code>{{ $node->default_domain }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-primary"
                                            data-toggle="modal"
                                            data-target="#nodeModal-{{ $node->id }}">
                                        <i class="fa fa-cog"></i> Configure
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Node Configuration Modals --}}
    @foreach($nodes as $node)
    <div class="modal fade" id="nodeModal-{{ $node->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">
                            <i class="fa fa-server"></i> Configure Node: {{ $node->name }}
                        </h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="node_id" value="{{ $node->id }}" />

                        <div class="form-group">
                            <label>Node Mode</label>
                            <select name="mode" class="form-control" id="nodeMode-{{ $node->id }}"
                                    onchange="toggleNodeFields({{ $node->id }})">
                                <option value="dns_only" {{ $node->mode === 'dns_only' ? 'selected' : '' }}>
                                    🌍 DNS-Only (Direct IP)
                                </option>
                                <option value="tunneled" {{ $node->mode === 'tunneled' ? 'selected' : '' }}>
                                    🔒 Tunneled (Cloudflare Tunnel)
                                </option>
                            </select>
                        </div>

                        <div class="form-group" id="tunnelIdGroup-{{ $node->id }}"
                             style="{{ $node->mode !== 'tunneled' ? 'display:none' : '' }}">
                            <label>Cloudflare Tunnel ID</label>
                            <input type="text" name="tunnel_id" class="form-control"
                                   value="{{ $node->tunnel_id }}"
                                   placeholder="e.g. abc123-def456-ghi789" />
                            <p class="text-muted small">Find this in Cloudflare → Zero Trust → Tunnels.</p>
                        </div>

                        <div class="form-group" id="defaultDomainGroup-{{ $node->id }}"
                             style="{{ $node->mode === 'tunneled' ? 'display:none' : '' }}">
                            <label>Default Domain (for auto-assign)</label>
                            <input type="text" name="default_domain" class="form-control"
                                   value="{{ $node->default_domain }}"
                                   placeholder="e.g. node1.yourhost.com" />
                            <p class="text-muted small">Used when user leaves subdomain blank on DNS-Only nodes.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        {{ csrf_field() }}
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Save Node Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    {{-- ================================ --}}
    {{-- SECTION 3: Active Access Points  --}}
    {{-- ================================ --}}
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-link"></i> Active Subdomain Bindings</h3>
                    <div class="box-tools">
                        <span class="label label-success">{{ count($accessPoints) }} binding(s)</span>
                    </div>
                </div>
                <div class="box-body no-padding">
                    @if(count($accessPoints) > 0)
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Server</th>
                                <th>Connection String</th>
                                <th>Port</th>
                                <th>Mode</th>
                                <th>Created</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accessPoints as $ap)
                            <tr>
                                <td><strong>{{ $ap->server_name }}</strong></td>
                                <td>
                                    <code class="cf-connection-string">{{ $ap->connection_string }}</code>
                                    <button class="btn btn-xs btn-default copy-btn"
                                            data-clipboard="{{ $ap->connection_string }}"
                                            title="Copy to clipboard">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </td>
                                <td><span class="label label-default">{{ $ap->port }}</span></td>
                                <td>
                                    @if($ap->node_mode === 'tunneled')
                                        <span class="label label-success"><i class="fa fa-shield"></i> Tunnel</span>
                                    @else
                                        <span class="label label-info"><i class="fa fa-globe"></i> DNS</span>
                                    @endif
                                </td>
                                <td>{{ $ap->created_at }}</td>
                                <td class="text-center">
                                    <form action="{{ $root }}/access-point/{{ $ap->id }}" method="POST" style="display:inline">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button type="submit" class="btn btn-xs btn-danger"
                                                onclick="return confirm('Delete this subdomain binding? The Cloudflare record will also be removed.')">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center" style="padding: 40px;">
                        <i class="fa fa-link fa-3x text-muted" style="margin-bottom: 15px;"></i>
                        <p class="text-muted">No subdomain bindings yet. They will appear here when ports are created and subdomains are assigned.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        // Toggle node configuration fields based on mode selection
        function toggleNodeFields(nodeId) {
            var mode = document.getElementById('nodeMode-' + nodeId).value;
            var tunnelGroup = document.getElementById('tunnelIdGroup-' + nodeId);
            var domainGroup = document.getElementById('defaultDomainGroup-' + nodeId);

            if (mode === 'tunneled') {
                tunnelGroup.style.display = 'block';
                domainGroup.style.display = 'none';
            } else {
                tunnelGroup.style.display = 'none';
                domainGroup.style.display = 'block';
            }
        }

        // Copy to clipboard
        document.querySelectorAll('.copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var text = this.getAttribute('data-clipboard');
                navigator.clipboard.writeText(text).then(function() {
                    btn.innerHTML = '<i class="fa fa-check"></i>';
                    setTimeout(function() {
                        btn.innerHTML = '<i class="fa fa-copy"></i>';
                    }, 1500);
                });
            });
        });
    </script>
@endsection
