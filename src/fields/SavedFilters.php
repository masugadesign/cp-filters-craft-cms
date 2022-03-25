<?php

namespace Masuga\CpFilters\fields;

use Craft;
use craft\fields\BaseRelationField;
use Masuga\CpFilters\elements\SavedFilter;
use Masuga\CpFilters\elements\db\SavedFilterQuery;

class SavedFilters extends BaseRelationField
{

	/**
     * @inheritdoc
     */
    protected $settingsTemplate = 'cpfilters/_savedFiltersField/settings';

    /**
     * @inheritdoc
     */
    protected $inputTemplate = 'cpfilters/_savedFiltersField/input';

	/**
	 * @inheritdoc
	 */
	public static function displayName(): string
	{
		return Craft::t('cpfilters', 'Saved Filters (CP Filters)');
	}

	/**
	 * @inheritdoc
	 */
	protected static function elementType(): string
	{
		return SavedFilter::class;
	}

	/**
	 * @inheritdoc
	 */
	public static function defaultSelectionLabel(): string
	{
		return Craft::t('cpfilters', 'Add a saved filter');
	}

	/**
	 * @inheritdoc
	 */
	public static function valueType(): string
	{
		return SavedFilterQuery::class;
	}

	/**
	 * @inheritdoc
	 */
	public function getSourceOptions(): array
	{
		return [];
	}

}
