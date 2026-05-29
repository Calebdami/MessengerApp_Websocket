<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');
            $table->unique(['message_id', 'user_id']);
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('emoji');
            $table->timestamps();
            $table->unique(['message_id', 'user_id', 'emoji']);
        });

        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('initiated_by')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['audio', 'video']);
            $table->enum('status', ['ringing','active','ended','missed','declined'])->default('ringing');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'contact_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('calls');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('message_reads');
    }
};
