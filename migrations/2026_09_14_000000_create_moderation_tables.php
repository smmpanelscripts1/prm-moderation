<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('moderation_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reporter_id');
            $table->unsignedInteger('target_user_id');
            $table->string('target_type', 16);
            $table->unsignedInteger('post_id')->nullable();
            $table->unsignedInteger('discussion_id')->nullable();
            $table->string('reason', 32);
            $table->text('reason_detail')->nullable();
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('handled_by_id')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('set null');
            $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('set null');
            $table->foreign('handled_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'created_at']);
            $table->index(['target_user_id', 'status']);
        });

        $schema->create('moderation_warnings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('actor_id');
            $table->unsignedInteger('points')->default(1);
            $table->string('reason', 255);
            $table->text('comment')->nullable();
            $table->unsignedInteger('post_id')->nullable();
            $table->unsignedInteger('discussion_id')->nullable();
            $table->unsignedInteger('report_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('set null');
            $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('set null');
            $table->foreign('report_id')->references('id')->on('moderation_reports')->onDelete('set null');
            $table->index(['user_id', 'created_at']);
        });

        $schema->table('users', function (Blueprint $table) {
            $table->unsignedInteger('warning_points')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('moderation_warnings');
        $schema->dropIfExists('moderation_reports');

        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn(['warning_points', 'warning_count']);
        });
    },
];
