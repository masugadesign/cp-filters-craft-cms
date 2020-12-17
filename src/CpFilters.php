<?php

namespace Masuga\CpFilters;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\log\FileTarget;
use craft\services\Dashboard;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use Masuga\CpFilters\assetbundles\cp\CpAsset;
use Masuga\CpFilters\controllers\CpController;
use Masuga\CpFilters\models\Settings;
use Masuga\CpFilters\services\AssetVolumes;
use Masuga\CpFilters\services\EntryTypes;
use Masuga\CpFilters\services\FieldTypes;
use Masuga\CpFilters\services\CategoryGroups;
use Masuga\CpFilters\services\Filters;
use Masuga\CpFilters\services\SavedFilters;
use Masuga\CpFilters\services\TagGroups;
use Masuga\CpFilters\services\UserGroups;
use Masuga\CpFilters\variables\CpFiltersVariable;
use yii\base\Event;

class CpFilters extends Plugin
{

	/**
	 * Enables the CP sidebar nav link for this plugin. Craft loads the plugin's
	 * index template by default.
	 * @var boolean
	 */
	public $hasCpSection = true;

	/**
	 * Enables the plugin settings form.
	 * @var boolean
	 */
	public $hasCpSettings = true;

	/**
	 * The name of the plugin as it appears in the Craft control panel and
	 * plugin store.
	 * @return string
	 */
	public function getName()
	{
		 return Craft::t('cpfilters', 'CP Filters');
	}

	/**
	 * The brief description of the plugin that appears in the control panel
	 * on the plugin settings page.
	 * @return string
	 */
	public function getDescription(): string
	{
		return Craft::t('cpfilters', 'Advanced filtering for elements in the control panel.');
	}

	/**
	 * This method returns the plugin's Settings model instance.
	 * @return Settings
	 */
	protected function createSettingsModel(): Settings
	{
		return new Settings();
	}

	/**
	 * This method returns the settings form HTML content.
	 * @return string
	 */
	protected function settingsHtml(): string
	{
		return Craft::$app->getView()->renderTemplate('cpfilters/_settings', [
			'settings' => $this->getSettings()
		]);
	}

	/**
	 * The plugin's initialization function is responsible for registering event
	 * handlers, routes and other plugin components.
	 */
	public function init()
	{
		parent::init();
		// Initialize each of the services used by this plugin.
		$this->setComponents([
			'assetVolumes' => AssetVolumes::class,
			'categoryGroups' => CategoryGroups::class,
			'entryTypes' => EntryTypes::class,
			'fieldTypes' => FieldTypes::class,
			'filters' => Filters::class,
			'savedFilters' => SavedFilters::class,
			'tagGroups' => TagGroups::class,
			'userGroups' => UserGroups::class,
		]);
		// Register the CP Filters plugin log though we probably won't use this.
		$fileTarget = new FileTarget([
			'logFile' => Craft::$app->getPath()->getLogPath().'/cpfilters-craft.log',
			'categories' => ['cpfilters']
		]);
		// Load the template variables class.
		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
			$variable = $event->sender;
			$variable->set('cpfilters', CpFiltersVariable::class);
		});
		// Register CP routes.
		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
			$event->rules['cpfilters'] = 'cpfilters/cp/filters';
			$event->rules['cpfilters/entries'] = 'cpfilters/cp/filters';
			$event->rules['cpfilters/assets'] = 'cpfilters/cp/filters';
			$event->rules['cpfilters/categories'] = 'cpfilters/cp/filters';
			$event->rules['cpfilters/users'] = 'cpfilters/cp/filters';
			$event->rules['cpfilters/tags'] = 'cpfilters/cp/filters';
			$event->rules['cpfilters/field-filter-options'] = 'cpfilters/cp/field-filter-options';
			$event->rules['cpfilters/value-field'] = 'cpfilters/cp/value-field';
			$event->rules['cpfilters/saved-filters'] = 'cpfilters/cp/get-saved-filters';
			$event->rules['cpfilters/save-filter'] = 'cpfilters/cp/save-filter';
		});
	}

}
