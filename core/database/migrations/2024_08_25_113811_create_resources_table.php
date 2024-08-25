<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resources', static function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignIdFor(User::class)->constrained('users');
            $table->string('code')->unique();
            $table->boolean('hidden')->default(false);
            $table->string('target', 2048)->nullable();
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('downloads')->default(0);
            $table->string('password')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
