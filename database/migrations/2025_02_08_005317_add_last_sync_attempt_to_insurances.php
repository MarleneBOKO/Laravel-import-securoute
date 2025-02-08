<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->timestamp('last_sync_attempt')->nullable()->after('sync_message');
        });
    }

    public function down()
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn('last_sync_attempt');
        });
    }
};
