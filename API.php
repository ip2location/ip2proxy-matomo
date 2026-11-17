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
use Piwik\DataTable;
use Piwik\Http;
use Piwik\Piwik;

/**
 * API for plugin IP2Proxy.
 *
 * @method static \Piwik\Plugins\IP2Proxy\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
	public $database;
	public $ioApiKey;
	private $staticContainer;

	public function __construct(StaticContainer $staticContainer)
	{
		// Get settings
		$systemSettings = new \Piwik\Plugins\IP2Proxy\SystemSettings();
		$this->database = $systemSettings->database->getValue();
		$this->ioApiKey = $systemSettings->ioApiKey->getValue();
		$this->staticContainer = $staticContainer;
	}

	/**
	 * Returns a data table with the visits.
	 *
	 * @param int         $idSite
	 * @param string      $period
	 * @param string      $date
	 * @param bool|string $segment
	 * @param int         $filterLimit
	 *
	 * @return DataTable
	 */
	public function getProxyDetails($idSite, $period, $date, $segment = false, $filterLimit = 200)
	{
		Piwik::checkUserHasViewAccess($idSite);

		$logger = $this->staticContainer->getContainer()->get('Psr\Log\LoggerInterface');

		$response = Request::processRequest('Live.getLastVisitsDetails', [
			'idSite'               => $idSite,
			'period'               => $period,
			'date'                 => $date,
			'segment'              => $segment,
			'flat'                 => false,
			'doNotFetchActions'    => false,
			'countVisitorsToFetch' => $filterLimit,
		]);

		$response->applyQueuedFilters();

		$result = $response->getEmptyClone($keepFilters = false);

		if (empty($this->ioApiKey)) {
			if (!file_exists($this->database)) {
				return $result;
			}

			require_once PIWIK_INCLUDE_PATH . '/plugins/IP2Proxy/lib/IP2Proxy.php';

			$db = new \IP2Proxy\Database($this->database, \IP2Proxy\Database::FILE_IO);
		}

		foreach ($response->getRows() as $visitRow) {
			$visitIp = $visitRow->getColumn('visitIp');

			// Defaults used when the lookup returns nothing.
			$records = [
				'isProxy'     => 0,
				'proxyType'   => 'N/A',
				'countryName' => 'N/A',
				'regionName'  => 'N/A',
				'cityName'    => 'N/A',
				'isp'         => 'N/A',
				'domain'      => 'N/A',
				'usageType'   => 'N/A',
				'asn'         => '-',
				'threat'      => 'N/A',
			];

			if (empty($this->ioApiKey)) {
				// Get proxy details by the IP
				$records = $db->lookup($visitIp, \IP2PROXY\Database::ALL);
			} else {
				if (($json = json_decode(Http::sendHttpRequest('https://api.ip2location.io/?key=' . $this->ioApiKey . '&ip=' . $visitIp, 30))) !== null) {
					$records['isProxy'] = ($json->is_proxy) ? 1 : 0;
					$records['proxyType'] = $json->proxy_type ?? 'N/A';
					$records['countryName'] = $json->country_name ?? 'N/A';
					$records['regionName'] = $json->region_name ?? 'N/A';
					$records['cityName'] = $json->city_name ?? 'N/A';
					$records['isp'] = $json->isp ?? 'N/A';
					$records['domain'] = $json->domain ?? 'N/A';
					$records['usageType'] = $json->usage_type ?? 'N/A';
					$records['asn'] = $json->asn ?? 'N/A';
					$records['threat'] = $json->threat ?? 'N/A';
				}
			}

			foreach ($records as $key => $value) {
				if (preg_match('/NOT SUPPORTED/', (string) $value)) {
					$records[$key] = 'N/A';
				}
			}

			$result->addRowFromSimpleArray([
				'IP'                  => $visitIp,
				'proxy'               => ($records['isProxy'] == 1) ? 'Yes' : 'No',
				'proxy_type'          => $records['proxyType'],
				'country'             => $records['countryName'],
				'region'              => $records['regionName'],
				'city'                => $records['cityName'],
				'isp'                 => $records['isp'],
				'domain'              => $records['domain'],
				'usage_type'          => $records['usageType'],
				'asn'                 => ($records['asn'] != '-') ? ('AS' . $records['asn']) : '',
				'threat'              => $records['threat'],
				'last_visit_time'     => $visitRow->getColumn('lastActionDateTime'),
				'type'                => $visitRow->getColumn('visitorType'),
				'nb_visits'           => $visitRow->getColumn('visitCount'),
				'last_visit_duration' => $visitRow->getColumn('visitDurationPretty'),
				'referrer_type'       => $visitRow->getColumn('referrerType'),
				'referrer_name'       => $visitRow->getColumn('referrerName'),
				'device'              => $visitRow->getColumn('deviceType'),
			]);
		}

		return $result;
	}
}
