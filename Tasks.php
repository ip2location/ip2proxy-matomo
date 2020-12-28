<?php
/**
 * Piwik - free/libre analytics platform.
 *
 * @see https://matomo.org
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\IP2Proxy;

use Piwik\API\Request;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Site;

class Tasks extends \Piwik\Plugin\Tasks
{
	private $staticContainer;

	public function __construct(StaticContainer $staticContainer)
	{
		$this->staticContainer = $staticContainer;
	}

	public function schedule()
	{
		foreach (\Piwik\Site::getSites() as $site) {
			// Foreach website, send a report by email to each user allowed to get stats of the concerned website
			$this->daily('getListOfProxyDetailsThatVisitedWebsiteYesterday', $site['idsite']);
		}
	}

	public function getListOfProxyDetailsThatVisitedWebsiteYesterday($siteId)
	{
		$logger = $this->staticContainer->getContainer()->get('Psr\Log\LoggerInterface');
		$siteName = Site::getNameFor($siteId);
		$recipients = $this->getAllUsersEmailsForSite($siteId);
		$superUsers = $this->getSuperUsersEmails();

		$proxyDetails = \Piwik\API\Request::processRequest('IP2Proxy.getProxyDetails', [
			'idSite' => $siteId,
			'period' => 'day',
			'date'   => 'yesterday',
		]);

		// Generate the HTML
		$html = $this->convertProxyDetailsDataTableToHTML($proxyDetails);

		if (!empty($superUsers) && !empty($recipients)) {
			$mail = new \Piwik\Mail();
			$mail->setFrom($superUsers[0]);
			$mail->setReplyTo($superUsers[0]);
			$logger->info('IP2Proxy: Email sent from ' . $superUsers[0] . ' for ' . $siteName);

			foreach ($recipients as $recipient) {
				$mail->addTo($recipient);
				$logger->info('IP2Proxy: Email sent to ' . $recipient . ' for ' . $siteName);
			}

			$mail->setSubject(Piwik::translate('IP2Proxy_ProxyDetailsReportSubject', $siteName));

			try {
				$mail->setWrappedHtmlBody($html);
			} catch (Exception $e) {
				$logger->error('IP2Proxy: An error occured while sending the email: ' . $e->message());
				throw $e;
			}

			$mail->send();
		}
	}

	/**
	 * For the supplied website, get the emails of the users that have view access.
	 *
	 * @param string the site ID
	 * @param mixed $siteId
	 *
	 * @return array The returned array has the format
	 *               array(email1, email2, ...)
	 */
	private function getAllUsersEmailsForSite($siteId)
	{
		$result = [];
		$userSettings = new \Piwik\Plugins\IP2Proxy\UserSettings();

		// Get the users with a view access
		$response = Request::processRequest('UsersManager.getUsersWithSiteAccess', [
			'idSite' => $siteId,
			'access' => 'view',
		]);

		foreach ($response as $user) {
			$subscribedToEmailReport = $userSettings->getSubscribedToEmailReportValueForUser($user['login']);

			if ($subscribedToEmailReport) {
				$result[] = $user['email'];
			}
		}

		// Get the users with a write access
		$response = Request::processRequest('UsersManager.getUsersWithSiteAccess', [
			'idSite' => $siteId,
			'access' => 'write',
		]);

		foreach ($response as $user) {
			$subscribedToEmailReport = $userSettings->getSubscribedToEmailReportValueForUser($user['login']);

			if ($subscribedToEmailReport) {
				$result[] = $user['email'];
			}
		}

		// Get the users with admin access
		$response = Request::processRequest('UsersManager.getUsersWithSiteAccess', [
			'idSite' => $siteId,
			'access' => 'admin',
		]);

		foreach ($response as $user) {
			$subscribedToEmailReport = $userSettings->getSubscribedToEmailReportValueForUser($user['login']);

			if ($subscribedToEmailReport) {
				$result[] = $user['email'];
			}
		}

		// Get the users with superuser access
		$response = Request::processRequest('UsersManager.getUsersHavingSuperUserAccess', []);

		foreach ($response as $superUser) {
			$subscribedToEmailReport = $userSettings->getSubscribedToEmailReportValueForUser($superUser['login']);

			if ($subscribedToEmailReport) {
				$result[] = $superUser['email'];
			}
		}

		return $result;
	}

	/**
	 * Get the email address of the super user.
	 *
	 * @return array The returned array has the format
	 *               array(email1, email2, ...)
	 */
	private function getSuperUsersEmails()
	{
		$response = Request::processRequest('UsersManager.getUsersHavingSuperUserAccess', []);
		$result = [];

		foreach ($response as $superUser) {
			$result[] = $superUser['email'];
		}

		return $result;
	}

	/**
	 * Get the email address of the super user.
	 *
	 * @param array      The list of proxies
	 * @param mixed $proxyDetails
	 *
	 * @return string The generated HTML
	 */
	private function convertProxyDetailsDataTableToHTML($proxyDetails)
	{
		$html = '<p>' . Piwik::translate('IP2Proxy_Hi') . '</p>'
			. '<p>' . Piwik::translate('IP2Proxy_FindBelowProxyDetailsReport') . '</p>';

		$rows = $proxyDetails->getRows();

		if (!empty($rows)) {
			$html .= '<p>'
			. "<table style='border: solid 2px #000; width: 100%;'>"
			. '<thead>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>IP</td>"
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_Proxy') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_LastVisit') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_Type') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_NumberOfVisits') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_LastVisitDuration') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_ReferrerType') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_ReferrerName') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_Device') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_Country') . '</td>'
			. "<td style='border-right: solid 1px #000; border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_Region') . '</td>'
			. "<td style='border-bottom: solid 1px #000;'>" . Piwik::translate('IP2Proxy_City') . '</td>'
			. '</thead>';

			foreach ($rows as $row) {
				$columns = $row->getColumns();
				$counter = 0;
				$nbColumns = \count($columns);

				$html .= '<tr>';

				foreach ($columns as $key => $value) {
					++$counter;
					$styles = 'border-bottom: solid 1px #DEDEDE;';

					if ($counter < $nbColumns) {
						$styles .= ' border-right: solid 1px #DEDEDE;';
					}

					$html .= "<td style='" . $styles . "'>" . $value . '</td>';
				}

				$html .= '</tr>';
			}

			$html .= '</table></p>';
		} else {
			$html .= "<p style='text-align: center; margin-top: 40px; margin-bottom: 40px; font-weight: bold;'><em>" . Piwik::translate('IP2Proxy_NoOneVisitedWebsiteYesterday') . '</em></p>';
		}

		$html .= '<br />'
			. '<p>' . Piwik::translate('IP2Proxy_ThanksForUsing') . '</p>';

		return $html;
	}
}
