<?php

namespace Engelsystem\Migrations;

use Engelsystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddBotChatID extends Migration
{
    use Reference;

    /**
     * Run the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable('users_settings')) {
            return;
        }

        $this->schema->table(
            'users_settings',
            function (Blueprint $table) {
                $table->string('bot_chatid');
            }
        );
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        if (!$this->schema->hasTable('users_settings')) {
            return;
        }

        $this->schema->table(
            'users_settings',
            function (Blueprint $table) {
                $table->dropColumn('bot_chatid');
            }
        );
    }
}
