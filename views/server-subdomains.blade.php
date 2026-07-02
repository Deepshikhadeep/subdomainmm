{{-- 
    Additional view: Server subdomain info partial.
    Can be included in custom server detail views.
--}}

@if(isset($accessPoints) && count($accessPoints) > 0)
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-link"></i> Server Subdomains</h3>
    </div>
    <div class="box-body no-padding">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Connection String</th>
                    <th>Port</th>
                    <th>Mode</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accessPoints as $ap)
                <tr>
                    <td><code class="cf-connection-string">{{ $ap->connection_string }}</code></td>
                    <td><span class="label label-default">{{ $ap->port }}</span></td>
                    <td>
                        @if($ap->node_mode === 'tunneled')
                            <span class="label label-success"><i class="fa fa-shield"></i> Tunnel</span>
                        @else
                            <span class="label label-info"><i class="fa fa-globe"></i> DNS</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
