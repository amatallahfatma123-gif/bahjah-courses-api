<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class TestEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_tests_run_on_in_memory_sqlite_not_the_development_database(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
    }

    public function test_migrations_create_the_application_tables(): void
    {
        foreach (['users', 'courses', 'personal_access_tokens'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_courses_table_has_the_owner_column(): void
    {
        $this->assertTrue(Schema::hasColumn('courses', 'user_id'));
    }
}
