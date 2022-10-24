<?php

declare(strict_types=1);

namespace Engelsystem\Migrations;

use Engelsystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class ExpandFieldSizes extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
    }
}
