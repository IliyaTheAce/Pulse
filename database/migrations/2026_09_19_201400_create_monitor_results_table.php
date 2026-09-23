<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitor_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained('monitors')->cascadeOnDelete();
            $table->string('execution_id')->unique();
            $table->foreignId('team_id')->constrained('teams');
//          TODO:Add incident ID when incident table is available
            $table->timestamp("checked_at");
            $table->enum("status", ["success", "failure", "error"]);
            $table->string("error_type")->nullable();
            $table->string("http_status")->nullable();
            $table->integer("duration_ms");
            $table->integer("response_bytes")->nullable();
            $table->json("assertion_failures")->nullable();
            $table->timestamps();
            $table->index(["monitor_id", "checked_at"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_results');
    }
};
