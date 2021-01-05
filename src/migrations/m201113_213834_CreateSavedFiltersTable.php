<?php

namespace Masuga\CpFilters\migrations;

use Craft;
use craft\db\Migration;

/**
 * m201113_213834_CreateSavedFiltersTable migration.
 */
class m201113_213834_CreateSavedFiltersTable extends Migration
{
	public function safeUp()
	{
		if (!$this->db->tableExists('{{%cpfilters_savedfilters}}')) {
			$this->createTable('{{%cpfilters_savedfilters}}', [
				'id' => $this->primaryKey(),
				'userId' => $this->integer(),
				'title' => $this->string(255),
				'filterElementType' => $this->string(255),
				'filterGroupId' => $this->integer(),
				'filterCriteria' => $this->text(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'dateDeleted' => $this->dateTime()->null(),
				'uid' => $this->uid()
			]);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m201113_213834_CreateSavedFiltersTable cannot be reverted.\n";
		return false;
	}
}
