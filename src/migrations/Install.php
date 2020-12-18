<?php
namespace Masuga\CpFilters\migrations;

use craft\db\Migration;

class Install extends Migration
{
	public function safeUp()
	{
		if (!$this->db->tableExists('{{%cpfilters_savedfilters}}')) {
			$this->createTable('{{%cpfilters_savedfilters}}', [
				'id' => $this->primaryKey(),
				'userId' => $this->integer(),
				'title' => $this->string(255),
				'filterUrl' => $this->string(255),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid()
			]);
		}
	}

	public function safeDown()
	{
		if ( $this->db->tableExists('{{%cpfilters_savedfilters}}') ) {
			$this->dropTable('{{%cpfilters_savedfilters}}');
		}
	}
}