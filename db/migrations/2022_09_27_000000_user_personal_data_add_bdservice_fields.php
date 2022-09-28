<?php

declare(strict_types=1);

namespace Engelsystem\Migrations;

use Engelsystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class UserPersonalDataAddBdserviceFields extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->table('users_personal_data', function (Blueprint $table) {
            $table->dateTime('birthday')
                ->nullable()
                ->default(null)
                ->after('last_name');

            $table->string('employee_number')
                ->nullable()
                ->default(null)
                ->after('birthday');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->schema->table('users_personal_data', function (Blueprint $table) {
            $table->dropColumn('birthday');
            $table->dropColumn('employee_number');
        });
    }
}
