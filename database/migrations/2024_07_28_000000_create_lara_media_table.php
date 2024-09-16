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

        Schema::create($this->_table, function(/** @var Blueprint $table */$table) {
            $table->id();
            $table->string('model_id')->index()->nullable();
            $table->string('model_type')->index()->nullable();
            $table->string('album')->nullable()->index();
            $table->string('disk')->index();
            $table->string('path')->nullable()->index();
            $table->string('name');
            $table->string('ext');
            $table->string('mime');
            $table->string('hash', 32)->nullable()->index();
            $table->unsignedBigInteger('size');
            $table->json('properties')->default('{}');
            $table->json('conversions')->default('{}');
            $table->json('responsive')->default('{}');
            $table->boolean('is_removed')->default(false)->index();
            $table->unsignedBigInteger('total_size')->default(0);
            $table->integer('total_files')->default(0);
            $table->text('description')->nullable();
            $table->timestampsTz();
        });

        $jobStatusModel = new (config('job-status.model'));
        $jobStatusConnection = $jobStatusModel->getConnectionName();
        $jobStatusTable = $jobStatusModel->getTable();
        DB::purge($jobStatusConnection);

        $schema = Schema::connection($jobStatusConnection);
        $schema->table($jobStatusTable, function (/** @var Blueprint $blueprint */ $table) {
            $table->string('model_id')->index()->nullable();
            $table->string('model_type')->index()->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->_table);
    }
};
