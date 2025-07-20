<?php

namespace Indigoram89\NestedSet\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Indigoram89\NestedSet\NestedSetServiceProvider;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            NestedSetServiceProvider::class,
        ];
    }

    protected function setUpDatabase(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('nested_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->integer('lft')->default(0)->index();
            $table->integer('rgt')->default(0)->index();
            $table->unsignedInteger('depth')->default(0);
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->timestamps();
            
            $table->index(['lft', 'rgt']);
            $table->index(['parent_id', 'lft']);
        });
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        $app['config']->set('app.key', 'base64:' . base64_encode('32characterrandomstringforencryp'));
    }
}