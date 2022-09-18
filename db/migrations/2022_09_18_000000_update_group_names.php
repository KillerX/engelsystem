<?php

namespace Engelsystem\Migrations;

use Engelsystem\Database\Migration\Migration;

class UpdateGroupNames extends Migration
{
    /**
     * Run the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable('Groups')) {
            return;
        }

        $connection = $this->schema->getConnection();

        $connection->update('UPDATE Groups set Name = \'1 - Guest\' WHERE UID = -10', []);
        $connection->update('UPDATE Groups set Name = \'2 - Worker\' WHERE UID = -20', []);
        $connection->update('UPDATE Groups set Name = \'3 - Shirt Manager\' WHERE UID = -30', []);
        $connection->update('UPDATE Groups set Name = \'4 - Shift Coordinator\' WHERE UID = -40', []);
        $connection->update('UPDATE Groups set Name = \'5 - Team Coordinator\' WHERE UID = -50', []);
        $connection->update('UPDATE Groups set Name = \'6 - Admin\' WHERE UID = -60', []);
        $connection->update('UPDATE Groups set Name = \'7 - Developer\' WHERE UID = -70', []);
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
            return;
    }
}
