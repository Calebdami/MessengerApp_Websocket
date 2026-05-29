<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->onDelete('set null');
            $table->enum('type', ['text','image','video','audio','file','sticker','emoji','call','system'])->default('text');
            $table->text('content')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('file_mime')->nullable();
            $table->integer('duration')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('messages'); }
};
