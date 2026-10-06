<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->unsignedInteger('price');
                $table->unsignedInteger('stock');
                $table->timestamps();
            });
        }
    }
}
