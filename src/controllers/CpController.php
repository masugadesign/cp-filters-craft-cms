<?php

namespace Masuga\CpFilters\controllers;

use Craft;
use Exception;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use Masuga\CpFilters\CpFilters;
use Masuga\CpFilters\elements\SavedFilter;
use yii\web\NotFoundHttpException;

class CpController extends Controller
{
	/**
	 * The instance of the CpFilters plugin.
	 * @var CpFilters
	 */
	private $plugin = null;

	public function init(): void
	{
		parent::init();
		$this->plugin = CpFilters::getInstance();
	}

	/**
	 * This method action returns the Cp Filters landing page in the control
	 * panel.
	 * @return Response
	 */
	public function actionFilters(): Response
	{
		$request = Craft::$app->getRequest();
		$export = $request->getParam('exportSubmit');
		$groupId = $request->getParam('groupId');
		if ( $export ) {
			$elementTypeKey = $request->getSegment(2) ?: 'entries';
			$elementType = $this->plugin->filters->getElementClass($elementTypeKey);
			$filterInput = $request->getParam('filters') ?: [];
			$criteria = $this->plugin->filters->formatCriteria($filterInput) +
				$this->plugin->filters->elementGroupCriteria($elementTypeKey, $groupId);
			$elements = $this->plugin->filters->fetchElementsByCriteria($elementTypeKey, $criteria, true);
			$basename = $elementTypeKey.'-export-'.date('YmdHi');
			$csvPath = $this->plugin->filters->generateCsvFile($elements, $basename);
			$response = Craft::$app->getResponse();
			$response->sendFile($csvPath, $basename.'.csv', [
				'mimeType' => 'text/csv'
			]);
		} else {
			$response = $this->renderTemplate('cpfilters/_layout');
		}
		return $response;
	}

	/**
	 * This method action accepts an AJAX request including a field handle parameter
	 * used to fetch the available filter for that field's type.
	 * @return string
	 */
	public function actionFieldFilterOptions(): string
	{
		// @todo : Is there no longer a way to require an AJAX request?
		$request = Craft::$app->getRequest();
		$view = Craft::$app->getView();
		$fieldHandle = $request->getParam('fieldHandle');
		$filterOptionsHtml = $fieldHandle ? $this->plugin->fieldTypes->getFilterOptionsByFieldHandle($fieldHandle) : '';
		return $view->renderString($filterOptionsHtml);
	}

	/**
	 * This method determines what the appropriate filter "value" field should be
	 * based on whether or not a particular field has options or if it just needs
	 * a plain text field.
	 * @return string
	 */
	public function actionValueField(): string
	{
		$request = Craft::$app->getRequest();
		$view = Craft::$app->getView();
		// We need these three request parameters for the view. ("value" optional)
		$templateParams = [
			'fieldHandle' => $request->getParam('fieldHandle'),
			'filterType' => $request->getParam('filterType'),
			'fieldValue' => $request->getParam('value'),
			'elementTypeKey' => $request->getParam('elementTypeKey'),
			'index' => $request->getParam('index'),
		];
		return $view->renderTemplate('cpfilters/_partials/value-field', $templateParams);
	}

	/**
	 * This method creates a Saved Filter record
	 * or updates an existing Saved Filter
	 * @return Response
	 */
	public function actionSaveFilter(): Response
	{
		$request = Craft::$app->getRequest();
		$groupId = $request->getParam('groupId');
		$id      = $request->getParam('filterId');
		$title   = $request->getParam('filterTitle');
		$userId  = $request->getParam('userId');
		$elementTypeKey = $request->getParam('elementTypeKey');
		$filterElementType = $request->getParam('elementType');
		$filterInput = $request->getParam('filters') ?: [];
		$includeDrafts = $request->getParam('includeDrafts') === 'y' ? 'y' : '';
		$criteria = json_encode($filterInput);

		$fields = [
			'title' => $title,
			'filterElementType' => $filterElementType,
			'filterGroupId' => $groupId,
			'filterCriteria' => $criteria,
			'userId' => $userId,
			'includeDrafts' => $includeDrafts
		];

		$savedFilter = $this->plugin->savedFilters->saveFilter($fields, $id);
		if ( $savedFilter ) {
			Craft::$app->getSession()->setNotice(Craft::t('cpfilters', 'CP Filters custom filter saved!'));
			$response = $this->asJson(['url' => $savedFilter->getUrl()]);
		} else {
			Craft::$app->getSession()->setError(Craft::t('cpfilters', 'Error saving the CP Filters custom filter.'));
			$response = $this->asJson(['error' => Craft::t('cpfilters', 'Unable to save filter')]);
		}

		return $response;
	}

	/**
	 * This method deletes a Saved Filter record
	 * @return bool
	 */
	public function actionDeleteFilter(): bool
	{
		$request = Craft::$app->getRequest();
		$elementId = $request->getParam('filterId');
		return $this->plugin->savedFilters->deleteSavedFilter($elementId);
	}

	/**
	 * This controller action loads the user-created saved filters.
	 * @return YiiResponse
	 */
	public function actionGetSavedFilters(): Response
	{
		$response = $this->renderTemplate('cpfilters/_layout');
		return $response;
	}

}
