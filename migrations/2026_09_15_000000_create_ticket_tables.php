<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('moderation_tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('assigned_to_id')->nullable();
            $table->string('subject', 160);
            $table->string('category', 32);
            $table->string('priority', 16)->default('normal');
            $table->string('status', 16)->default('open');
            $table->timestamp('last_replied_at')->nullable();
            $table->unsignedInteger('closed_by_id')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'last_replied_at']);
            $table->index(['user_id', 'status']);
        });

        $schema->create('moderation_ticket_replies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('user_id');
            $table->text('content');
            $table->boolean('is_staff')->default(false);
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('moderation_tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['ticket_id', 'created_at']);
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('moderation_ticket_replies');
        $schema->dropIfExists('moderation_tickets');
    },
];
