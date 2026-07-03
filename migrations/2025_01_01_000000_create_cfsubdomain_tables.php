<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;

return new class extends Migration
{
    /**
     * Run the migrations - set up default config values and create custom tables.
     */
    public function up(): void
    {
        // Set default extension configuration values
        $blueprint = app(BlueprintExtensionLibrary::class);
        $blueprint->dbSetMany("cfsubdomain", [
            'cf_api_key'       => '',
            'cf_zone_id'       => '',
            'cf_account_id'    => '',
            'base_domain'      => '',
        ]);

        // Create per-node Cloudflare settings table
        Schema::create('cf_node_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('node_id')->unique();
            $table->enum('mode', ['tunneled', 'dns_only'])->default('dns_only');
            $table->string('tunnel_id', 255)->nullable();
            $table->string('default_domain', 255)->nullable();
            $table->timestamps();

            $table->foreign('node_id')
                  ->references('id')
                  ->on('nodes')
                  ->onDelete('cascade');

            $table->index('mode');
        });

        // Create server access points (port-to-subdomain mappings) table
        Schema::create('cf_server_access_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('allocation_id');
            $table->unsignedInteger('port');
            $table->string('subdomain', 63)->nullable();
            $table->string('full_domain', 255);
            $table->string('connection_string', 255);
            $table->string('cf_record_id', 255)->nullable();
            $table->enum('node_mode', ['tunneled', 'dns_only']);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('server_id')
                  ->references('id')
                  ->on('servers')
                  ->onDelete('cascade');

            $table->unique('subdomain');
            $table->unique('allocation_id');
            $table->index('server_id');
            $table->index('full_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cf_server_access_points');
        Schema::dropIfExists('cf_node_settings');

        // Clean up Blueprint settings
        DB::table('settings')->where('key', 'like', 'cfsubdomain::%')->delete();
    }
};
