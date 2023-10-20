<?php
/**
 * Plugin Name: IP2Proxy (Matomo Plugin)
 * Plugin URI: http://plugins.matomo.org/ip2proxy
 * Description: Get the proxy details of visitors to your website.
 * Author: IP2Location
 * Author URI: https://github.com/ip2location/ip2proxy-matomo
 * Version: 0.1.6.
 */
?><?php
/**
 * Piwik - free/libre analytics platform.
 *
 * @see https://matomo.org
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\IP2Proxy;

use Piwik\SettingsPiwik;
use Piwik\Widget\WidgetsList;

if (\defined('ABSPATH')
&& \function_exists('add_action')) {
	$path = '/matomo/app/core/Plugin.php';
	if (\defined('WP_PLUGIN_DIR') && WP_PLUGIN_DIR && file_exists(WP_PLUGIN_DIR . $path)) {
		require_once WP_PLUGIN_DIR . $path;
	} elseif (\defined('WPMU_PLUGIN_DIR') && WPMU_PLUGIN_DIR && file_exists(WPMU_PLUGIN_DIR . $path)) {
		require_once WPMU_PLUGIN_DIR . $path;
	} else {
		return;
	}
	add_action('plugins_loaded', function () {
		if (\function_exists('matomo_add_plugin')) {
			matomo_add_plugin(__DIR__, __FILE__, true);
		}
	});
}

class IP2Proxy extends \Piwik\Plugin
{
	/**
	 * @see https://developer.matomo.org/guides/extending-database
	 */
	public function install()
	{
	}

	/**
	 * @see https://developer.matomo.org/guides/extending-database
	 */
	public function activate()
	{
	}

	/**
	 * @see https://developer.matomo.org/guides/extending-database
	 */
	public function uninstall()
	{
	}

	/**
	 * @see \Piwik\Plugin::registerEvents
	 */
	public function registerEvents()
	{
		return [
			'Widget.filterWidgets' => 'filterWidgets',
		];
	}

	/**
	 * @param WidgetsList $list
	 */
	public function filterWidgets($list)
	{
		if (!SettingsPiwik::isInternetEnabled()) {
			$list->remove('IP2Proxy_ProxyDetails');
		}
	}
}
