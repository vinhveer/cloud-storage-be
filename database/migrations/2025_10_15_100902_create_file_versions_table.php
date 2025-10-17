<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('version_number');
            $table->uuid('uuid')->unique();
            $table->string('file_extension', 20)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->enum('action', ['upload', 'update', 'restore']);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['file_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_versions');
    }
};
