<?php

namespace Masuga\CpFilters\models;

use craft\base\Model;

class Settings extends Model
{

	/**
	 * An array of entry type IDs that should appear as filterable options.
	 * @var mixed
	 */
	public $filterableEntryTypeIds = null;

	/**
	 * An array of Asset volume IDs that should appear as filterable options.
	 * @var mixed
	 */
	public $filterableAssetVolumeIds = null;

	/**
	 * An array of Category group IDs that should appear as filterable options.
	 * @var mixed
	 */
	public $filterableCategoryGroupIds = null;

	/**
	 * An array of Tag group IDs that should appear as filterable options.
	 * @var mixed
	 */
	public $filterableTagGroupIds = null;

	/**
	 * An array of additional field types that may be used as filter criteria.
	 * @var array
	 */
	public $additionalFieldTypes = [];

	/**
	 * A boolean determining whether or not to include Craft Commerce-related
	 * filters in this site.
	 */
	public $includeCommerce = false;

}
