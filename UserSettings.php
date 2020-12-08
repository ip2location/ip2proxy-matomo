<?php
/**
 * Piwik - free/libre analytics platform.
 *
 * @see https://matomo.org
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\IP2Proxy;

use Piwik\Common;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Setting;

/**
 * Defines Settings for IP2Proxy.
 *
 * Usage like this:
 * $settings = new UserSettings();
 * $settings->autoRefresh->getValue();
 * $settings->color->getValue();
 */
class UserSettings extends \Piwik\Settings\Plugin\UserSettings
{
	public function getSubscribedToEmailReportValueForUser($userLogin)
	{
		// Sanitize the user login
		$userLogin = filter_var($userLogin, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

		try {
			$sql = 'SELECT * FROM ' . Common::prefixTable('plugin_setting') . "
					WHERE plugin_name = 'IP2Proxy'
					AND setting_name = 'subscribedToEmailReport'
					AND setting_value = '1'
					AND user_login = '" . $userLogin . "'";

			$result = Db::fetchAll($sql);
		} catch (Exception $e) {
			// Ignore error if table already exists
			if (!Db::get()->isErrNo($e, '1050')) {
				throw $e;
			}
		}

		return \count($result) == 1;
	}

	protected function init()
	{
		// User setting --> checkbox converted to bool
		$this->subscribedToEmailReport = $this->createSubscribedToEmailReportSetting();
	}

	private function createSubscribedToEmailReportSetting()
	{
		return $this->makeSetting('subscribedToEmailReport', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
			$field->title = Piwik::translate('IP2Proxy_SubscribeToEmailReport');
			$field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
			$field->description = Piwik::translate('IP2Proxy_WantToReceiveDailyReport');
		});
	}
}
