<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string("description")->nullable();
            $table->boolean("enabled")->default(true);
            $table->foreignId("project_id")->constrained("projects");
            $table->string("url");
            $table->enum("type", ["https","http"]);
            $table->enum("method", ["get","post","put","patch","delete","head"]);
            $table->integer("timeout_ms");
            $table->integer("interval_seconds");
            $table->string("expected_status");
            $table->timestamp("last_checked_at")->nullable();
            $table->timestamp("next_check_at")->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
