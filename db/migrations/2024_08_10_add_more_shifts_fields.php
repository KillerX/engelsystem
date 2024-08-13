<?php

namespace Engelsystem\Migrations;

use Engelsystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddMoreShiftsFields extends Migration
{
    use Reference;

    /**
     * Run the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable('Shifts')) {
            return;
        }

        $this->schema->table(
            'Shifts',
            function (Blueprint $table) {
                $table->text('responsible_name')->nullable()->after('description');
                $table->text('responsible_phone')->nullable()->after('responsible_name');
                $table->text('address')->nullable()->after('responsible_phone');
                $table->text('requirements')->nullable()->after('address');
            }
        );
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        if (!$this->schema->hasTable('Shifts')) {
            return;
        }

        $this->schema->table(
            'Shifts',
            function (Blueprint $table) {
                $table->dropColumn('responsible_name');
                $table->dropColumn('responsible_phone');
                $table->dropColumn('address');
                $table->dropColumn('requirements');
            }
        );
    }
}
