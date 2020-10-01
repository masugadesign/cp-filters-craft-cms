<?php

namespace Masuga\CpFilters\models;

use craft\base\Model;

class Settings extends Model
{

	/**
	 * An array of entry type IDs that should appear as filterable options.
	 * @var mixed
	 */
	public $filterableEntryTypeIds = '*';

	/**
	 * An array of Asset volume IDs that should appear as filterable options.
	 * @var mixed
	 */
	public $filterableAssetVolumeIds = '*';

	/**
	 * An array of Category group IDs that should appear as filterable options.
	 * @var mixed
	 */
	public $filterableCategoryGroupIds = '*';

	/**
	 * An array of Tag group IDs that should appear as filterable options.
	 * @var mixed
	 */
	public $filterableTagGroupIds = '*';

	/**
	 * An array of additional field types that may be used as filter criteria.
	 * @var array
	 */
	public $additionalFieldTypes = [];

}
