<?php

namespace Masuga\CpFilters\services;

use Craft;
use Exception;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use Masuga\CpFilters\base\Service;
use Masuga\CpFilters\elements\SavedFilter;
use Masuga\CpFilters\elements\db\SavedFilterQuery;
use Masuga\CpFilters\records\SavedFilterRecord;

class SavedFilters extends Service
{
	/**
	 * This method creates/updates a CPFilters SavedFilter element based on whether or
	 * not an existing filter ID is supplied.
	 * @param array $input
	 * @param int $id
	 * @return SavedFilter|null
	 */
	public function saveFilter($input=[], $id)
	{
		$savedFilter = null;
		if ( $id ) {
			$savedFilter = SavedFilter::find()->id($id)->one();
		}
		if ( ! $savedFilter ) {
			$savedFilter = new SavedFilter;
		}
		$savedFilter->siteId = Craft::$app->getSites()->currentSite->id;
		$savedFilter->title = $input['title'];
		$savedFilter->filterUrl = $input['filterUrl'] ?? $savedFilter->filterUrl;
		// On a successful save, return the savedFilter element itself.
		return Craft::$app->getElements()->saveElement($savedFilter) ? $savedFilter : null;
	}

	/**
	 * This method returns an array of saved filters based on provided criteria.
	 * @param array $criteria
	 * @return SavedFilterQuery
	 */
	public function getSavedFilters($criteria=array()): SavedFilterQuery
	{
		$query = SavedFilter::find();
		if ($criteria) {
			Craft::configure($query, $criteria);
		}
		return $query;
	}


}