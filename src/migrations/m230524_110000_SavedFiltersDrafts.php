<?php

namespace Masuga\CpFilters\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230524_110000_SavedFiltersDrafts migration.
 */
class m230524_110000_SavedFiltersDrafts extends Migration
{
	public function safeUp()
	{
		$table = $this->db->schema->getTableSchema('{{%cpfilters_savedfilters}}');
		if ( ! isset($table->columns['includeDrafts']) ) {
			$this->addColumn('{{%cpfilters_savedfilters}}', 'includeDrafts', $this->string(10));
		}
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m230524_110000_SavedFiltersDrafts cannot be reverted.\n";
		return false;
	}
}
