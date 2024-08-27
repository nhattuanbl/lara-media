<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $_table;
    public function getConnection()
    {
        $model = new (config('lara-media.model'));
        $connection = $model->getConnectionName();
        $this->_table = $model->getTable();

        DB::purge($connection);
        return $connection;
    }

    public function up(): void
    {
//        $stored_workflow_model = new (config('workflows.stored_workflow_model'));
//        $connection = $stored_workflow_model->getConnectionName();
//        $table = $stored_workflow_model->getTable();
//        DB::purge($connection);
//
//        $schema = Schema::connection($connection);
//        if (!$schema->hasTable($table)) {
//            $schema->table($table, function (/** @var Blueprint $blueprint */ $blueprint) {
//                $blueprint->string('connection')->nullable();
//                $blueprint->string('queue')->nullable();
//            });
//        }


        if (!Schema::hasTable($this->_table)) {
            Schema::create($this->_table, function(Blueprint $table) {
                $table->id();
                $table->morphs('model');
                $table->string('album')->nullable();
                $table->string('disk');
                $table->string('path')->nullable();
                $table->string('name');
                $table->string('ext');
                $table->string('mime');
                $table->string('hash', 32)->nullable();
                $table->unsignedBigInteger('size');
                $table->json('properties')->default('{}');
                $table->json('conversions')->default('{}');
                $table->json('responsive')->default('{}');
                $table->boolean('is_removed')->default(false);
                $table->integer('total_size')->default(0);
                $table->integer('total_files')->default(0);
                $table->timestampsTz();
            });
        }

        Schema::table($this->_table, function (Blueprint $table) {
            $table->index('album');
            $table->index('hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->_table);
    }
};
