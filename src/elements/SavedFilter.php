<?php

namespace Masuga\CpFilters\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Delete;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\elements\db\SavedFilterQuery;
use Masuga\CpFilters\records\SavedFilterRecord;

class SavedFilter extends Element
{
	/**
	 * Element contents
	 */
	public $elementId = null;
	public $userId = null;
	public $title = null;
	public $filterUrl = null;

	/**
	 * Returns the element type name.
	 * @return string
	 */
	public static function displayName(): string
	{
		return Craft::t('cpfilters', 'Saved Filter');
	}

	/**
	 * Returns whether this element type has content.
	 * @return bool
	 */
	public static function hasContent(): bool
	{
		return true;
	}

	/**
	 * Returns whether this element type has titles.
	 * @return bool
	 */
	public static function hasTitles(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function isLocalized(): bool
	{
		return true;
	}

	/**
	 * Returns this element type's sources.
	 * @param string|null $context
	 * @return array|false
	 */
	protected static function defineSources(string $context = null): array
	{
		$sources = [
			[
				'key'      => '*',
				'label'    => Craft::t('cpfilters', 'Saved Filters'),
				'defaultSort' => ['elements.dateCreated', 'desc']
			],
		];
	}

	/**
	 * @inheritdoc
	 * @return SavedFilterQuery The newly created [[SavedFilterQuery]] instance.
	 */
	public static function find(): ElementQueryInterface
	{
		return new SavedFilterQuery(static::class);
	}

	/**
	 * @inheritDoc
	 */
	protected static function defineDefaultTableAttributes(string $source): array
	{
		return ['id', 'dateCreated', 'title', 'filterUrl', 'userId'];
	}

	/**
	* @inheritdoc
	*/
	protected static function defineSortOptions(): array
	{
		return [
			'elements.dateCreated' => Craft::t('app', 'Date Created'),
			'title' => Craft::t('CpFilters', 'Title')
		];
	}

	/**
	 * @inheritDoc IElementType::defineSearchableAttributes()
	 * @return array
	 */
	protected static function defineSearchableAttributes(): array
	{
		return [];
	}

	/**
	 * @inheritdoc
	 */
	protected function tableAttributeHtml(string $attribute): string
	{
		$displayValue = '';
		switch ($attribute) {
			case 'dateCreated': {
				$date = $this->$attribute;
				if ($date) {
					$displayValue = DateTimeHelper::toDateTime($date)->format('F j, Y H:i');
				}
			}
			case 'title':
				$displayValue = $this->$attribute;
			case 'userId':
				$user = $this->user;
				$displayValue = isset($user->username) ? '<a href="'.UrlHelper::cpUrl('/user', ['userId' => $user->id]).'" >'.$user->username.'</a>' : '--';
			case 'elementId':
				$element = $this->relatedElement;
				if ( $element ) {
					$title = (string) $element ?: '--';
					$url = $this->getCpEditUrl();
					$output = $url ? '<a href="'.$url.'" >'.$title.'</a>' : $title;
				} else {
					$output = '--';
				}
				$displayValue = $output;
			case 'id':
				$displayValue = $this->$attribute;
			default:
				$displayValue = parent::tableAttributeHtml($attribute);
		}
		return (string) $displayValue;
	}

	/**
	 * Returns the HTML for an editor HUD for the given element.
	 * @param BaseElementModel $element
	 * @return string
	 */
	public function getEditorHtml(): string
	{
		$html .= parent::getEditorHtml();
		return $html;
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 * @param string|null $source
	 * @return array|null
	 */
	protected static function defineActions(string $source = null): array
	{
		return [
			Delete::class
		];
	}
	/**
	 * @inheritdoc
	 * @throws Exception if existing record is not found.
	 */
	public function afterSave(bool $isNew)
	{
		if ( $isNew ) {
			$record = new SavedFilterRecord;
			$record->id = $this->id;
		} else {
			$record = SavedFilterRecord::findOne($this->id);
			if (!$record) {
				throw new Exception('Invalid download ID: '.$this->id);
			}
		}
		$record->elementId = $this->elementId;
		$record->userId = $this->userId;
		$record->title = $this->title;
		$record->filterUrl = $this->filterUrl;

		$status = $record->save();
		parent::afterSave($isNew);
	}

	/**
	 * This method sets the related _user property.
	 * @param User $user
	 * @return $this
	 */
	public function setUser($user)
	{
		$this->_user = $user;
		return $this;
	}

	/**
	 * This method returns the User element associated with this record.
	 * @return User
	 */
	public function getUser()
	{
		$user = null;
		if ( $this->_user !== null ) {
			$user = $this->_user;
		} elseif ( $this->userId ) {
			$user = Craft::$app->users->getUserById($this->userId);
		}
		return $user;
	}

	/**
	 * @inheritdoc
	 */
	public static function eagerLoadingMap(array $sourceElements, string $handle)
	{
		if ($handle === 'user') {
			$sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');
			$map = (new Query())
				->select(['id as source', 'userId as target'])
				->from(['{{%cpfilters_savedfilters}}'])
				->where(['and', ['id' => $sourceElementIds], ['not', ['userId' => null]]])
				->all();
			return [
				'elementType' => User::class,
				'map' => $map
			];
		}
		return parent::eagerLoadingMap($sourceElements, $handle);
	}

	/**
	 * @inheritdoc
	 */
	public function setEagerLoadedElements(string $handle, array $elements)
	{
		if ($handle === 'user') {
			$user = $elements[0] ?? null;
			$this->setUser($user);
		} elseif ($handle === 'asset') {
			$asset = $elements[0] ?? null;
			$this->setAsset($asset);
		} else {
			parent::setEagerLoadedElements($handle, $elements);
		}
	}
}
