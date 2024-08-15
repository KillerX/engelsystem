<?php

namespace Engelsystem\Migrations;

use Engelsystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddMinMaxAge extends Migration
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
                $table->unsignedInteger('min_age')->default(0)->after('description');
                $table->unsignedInteger('max_age')->default(999)->after('min_age');
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
                $table->dropColumn('min_age');
                $table->dropColumn('max_age');
            }
        );
    }
}
