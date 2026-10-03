<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Microservices\Migrations\ShadowMigration;

return new class extends ShadowMigration
{
    protected function source(): string
    {
        return 'customers';
    }

    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
    }
};
