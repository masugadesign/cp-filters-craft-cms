<?php

namespace Masuga\CpFilters\controllers;

use Craft;
use Exception;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use Masuga\CpFilters\CpFilters;
use craft\web\Controller;
use craft\web\Response;
use yii\web\NotFoundHttpException;

class CpController extends Controller
{
	/**
	 * The instance of the CpFilters plugin.
	 * @var CpFilters
	 */
	private $plugin = null;

	public function init()
	{
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
		if ( $export ) {
			$entryTypeId = $request->getParam('entryTypeId');
			$filterInput = $request->getParam('filters') ?: [];
			$criteria = $this->plugin->filters->formatCriteria($filterInput);
			$criteria['typeId'] = $entryTypeId;
			$entries = $this->plugin->filters->fetchEntriesByCriteria($criteria, true);
			$basename = 'entries-export-'.date('YmdHi');
			$csvPath = $this->plugin->filters->generateCsvFile($entries, $basename);
			$response = Craft::$app->getResponse();
			$response->sendFile($csvPath, $basename.'.csv', [
				'mimeType' => 'text/csv'
			]);
		} else {
			$response = $this->renderTemplate('cpfilters/_index');
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
			'index' => $request->getParam('index'),
		];
		return $view->renderTemplate('cpfilters/_partials/value-field', $templateParams);
	}

}
