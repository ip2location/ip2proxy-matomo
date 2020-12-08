<?php
/**
 * Piwik - free/libre analytics platform.
 *
 * @see https://matomo.org
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\IP2Proxy\Reports;

use Piwik\Piwik;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Widget\WidgetsList;

/**
 * This class defines a new report.
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class GetProxyDetails extends Base
{
	/**
	 * Here you can configure how your report should be displayed. For instance whether your report supports a search
	 * etc. You can also change the default request config. For instance change how many rows are displayed by default.
	 */
	public function configureView(ViewDataTable $view)
	{
		if (!empty($this->dimension)) {
			$view->config->addTranslations(['label' => $this->dimension->getName()]);
		}

		$view->config->show_search = true;
		$view->config->show_pagination_control = true;
		$view->config->show_limit_control = true;
		$view->config->show_periods = true;
		$view->config->show_bar_chart = false;
		$view->config->show_pie_chart = false;
		$view->config->show_tag_cloud = false;
		// $view->requestConfig->filter_sort_column = 'nb_visits';
		// $view->requestConfig->filter_limit = 10';

		$view->config->addTranslation('proxy', Piwik::translate('IP2Proxy_Proxy'));
		$view->config->addTranslation('proxy_type', Piwik::translate('IP2Proxy_ProxyType'));
		$view->config->addTranslation('country', Piwik::translate('IP2Proxy_Country'));
		$view->config->addTranslation('region', Piwik::translate('IP2Proxy_Region'));
		$view->config->addTranslation('city', Piwik::translate('IP2Proxy_City'));
		$view->config->addTranslation('isp', Piwik::translate('IP2Proxy_Isp'));
		$view->config->addTranslation('domain', Piwik::translate('IP2Proxy_Domain'));
		$view->config->addTranslation('usage_type', Piwik::translate('IP2Proxy_UsageType'));
		$view->config->addTranslation('asn', Piwik::translate('IP2Proxy_Asn'));
		$view->config->addTranslation('threat', Piwik::translate('IP2Proxy_Threat'));
		$view->config->addTranslation('last_visit_time', Piwik::translate('IP2Proxy_LastVisit'));
		$view->config->addTranslation('type', Piwik::translate('IP2Proxy_Type'));
		$view->config->addTranslation('nb_visits', Piwik::translate('IP2Proxy_NumberOfVisits'));
		$view->config->addTranslation('last_visit_duration', Piwik::translate('IP2Proxy_LastVisitDuration'));
		$view->config->addTranslation('referrer_type', Piwik::translate('IP2Proxy_ReferrerType'));
		$view->config->addTranslation('referrer_name', Piwik::translate('IP2Proxy_ReferrerName'));
		$view->config->addTranslation('device', Piwik::translate('IP2Proxy_Device'));

		$view->config->columns_to_display = $this->columns;
	}

	/**
	 * Here you can define related reports that will be shown below the reports. Just return an array of related
	 * report instances if there are any.
	 *
	 * @return \Piwik\Plugin\Report[]
	 */
	public function getRelatedReports()
	{
		return [];
	}

	/**
	 * Here we define a method to be able to create a widget with this report.
	 */
	public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
	{
		// we have to do it manually since it's only done automatically if a subcategoryId is specified,
		// we do not set a subcategoryId since this report is not supposed to be shown in the UI
		$widgetsList->addWidgetConfig($factory->createWidget());
	}

	protected function init()
	{
		parent::init();

		$this->name = Piwik::translate('IP2Proxy_ProxyDetails');
		$this->dimension = null;
		$this->documentation = Piwik::translate('');
		$this->subcategoryId = Piwik::translate('IP2Proxy_ProxyDetails');

		// This defines in which order your report appears in the mobile app, in the menu and in the list of widgets
		$this->order = 20;

		// By default standard metrics are defined but you can customize them by defining an array of metric names
		$this->metrics = [
			'nb_visits',
		];

		$this->columns = [
			'IP',
			'proxy',
			'proxy_type',
			'country',
			'region',
			'city',
			'isp',
			'domain',
			'usage_type',
			'asn',
			'threat',
			'last_visit_time',
			'type',
			'nb_visits',
			'last_visit_duration',
			'referrer_type',
			'referrer_name',
			'device',
		];
	}
}
