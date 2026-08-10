<?php

namespace Engelsystem\Migrations;

use Engelsystem\Database\Migration\Migration;

class SetAllUsersToNorwegian extends Migration
{
    /**
     * Run the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable('users_settings')) {
            return;
        }

        $this->schema->getConnection()
            ->table('users_settings')
            ->update(['language' => 'nb_NO']);
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        // Down is not possible: the previous per-user locales are not recoverable.
    }
}
