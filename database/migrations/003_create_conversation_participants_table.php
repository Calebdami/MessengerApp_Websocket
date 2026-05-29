<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('conversation_participants'); }
};
