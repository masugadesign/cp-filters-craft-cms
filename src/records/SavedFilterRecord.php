<?php

namespace Masuga\CpFilters\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\base\Element;
use craft\elements\User;
use yii\db\ActiveQueryInterface;

class SavedFilterRecord extends ActiveRecord
{
	use SoftDeleteTrait;

	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return '{{%cpfilters_savedfilters}}';
	}

	/**
	 * Returns the saved filter record's element.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getElement(): ActiveQueryInterface
	{
		return $this->hasOne(Element::class, ['id' => 'id']);
	}

	/**
	 * Returns the saved filter record's related User.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getUser(): ActiveQueryInterface
	{
		return $this->hasOne(User::class, ['id' => 'userId']);
	}

}
