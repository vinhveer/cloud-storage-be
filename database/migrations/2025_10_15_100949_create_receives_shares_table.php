<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receives_shares', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('share_id')->constrained('shares')->onDelete('cascade');
            $table->enum('permission', ['view', 'download', 'edit'])->nullable();
            $table->primary(['user_id', 'share_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receives_shares');
    }
};
