<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('display_name');
            $table->bigInteger('file_size');
            $table->string('mime_type', 100)->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->softDeletes();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'folder_id', 'file_extension']);
            $table->index('display_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
