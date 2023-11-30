<?php
/**
 * Piwik - free/libre analytics platform.
 *
 * @see https://matomo.org
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\IP2Proxy;

use Piwik\Piwik;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Setting;
use Piwik\Validators\NotEmpty;

/**
 * Defines Settings for IP2Proxy.
 *
 * Usage like this:
 * $settings = new SystemSettings();
 * $settings->metric->getValue();
 * $settings->description->getValue();
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
	/** @var Setting */
	public $database;
	public $ioApiKey;

	protected function init()
	{
		// Create a setting to store the database path
		$this->database = $this->createDatabaseSetting();

		// Create a settings to store ip2location.io API key
		$this->ioApiKey = $this->createIP2LocationIoSetting();
	}

	private function createDatabaseSetting()
	{
		return $this->makeSetting('database', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
			$field->title = Piwik::translate('IP2Proxy_IP2ProxyBinDatabase');
			$field->uiControl = FieldConfig::UI_CONTROL_TEXT;
			$field->inlineHelp = Piwik::translate('IP2Proxy_DownloadYourBinDatabase', [
				'<a target="_blank" rel="noreferrer noopener" href="https://lite.ip2location.com/ip2proxy-lite">', '</a>',
				'<a target="_blank" rel="noreferrer noopener" href="https://www.ip2location.com/database/ip2proxy">', '</a>',
			]);
			// $field->validators[] = new NotEmpty();
		});
	}

	private function createIP2LocationIoSetting()
	{
		return $this->makeSetting('ioApiKey', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
			$field->title = Piwik::translate('IP2Proxy_IoApiKey');
			$field->uiControl = FieldConfig::UI_CONTROL_TEXT;
			$field->inlineHelp = Piwik::translate('IP2Proxy_SignUpFreeIP2LocationIo', [
				'<a target="_blank" rel="noreferrer noopener" href="https://www.ip2location.io/">', '</a>',
			]);
		});
	}
}
