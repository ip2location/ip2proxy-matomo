<?php
/**
 * Piwik - free/libre analytics platform.
 *
 * @see https://matomo.org
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\IP2Proxy;

use Exception;
use Piwik\API\Request;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\Piwik;

const EMPTY_HOSTNAME = '-';

/**
 * API for plugin IP2Proxy.
 *
 * @method static \Piwik\Plugins\IP2Proxy\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
	public $database;
	private $staticContainer;

	public function __construct(StaticContainer $staticContainer)
	{
		// Get settings
		$systemSettings = new \Piwik\Plugins\IP2Proxy\SystemSettings();
		$this->database = $systemSettings->database->getValue();
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

		if (!file_exists($this->database)) {
			return $result;
		}

		require_once PIWIK_INCLUDE_PATH . '/plugins/IP2Proxy/lib/IP2Proxy.php';

		$db = new \IP2Proxy\Database();
		$db->open($this->database, \IP2Proxy\Database::FILE_IO);

		foreach ($response->getRows() as $visitRow) {
			$visitIp = $visitRow->getColumn('visitIp');

			// Get proxy details by the IP
			$records = $db->getAll($visitIp);

			foreach ($records as $key => $value) {
				if (preg_match('/NOT SUPPORTED/', $value)) {
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
				'asn'                 => $records['asn'],
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

	/**
	 * Another example method that returns a data table.
	 *
	 * @param string $ip
	 * @param mixed  $ipList
	 * @param mixed  $dbList
	 *
	 * @return array
	 */
	private function getIPDetails($ip, $ipList, $dbList)
	{
		$itemFound = false;
		$companyName = null;
		$hostname = filter_var($ip, FILTER_VALIDATE_IP) ? gethostbyaddr($ip) : EMPTY_HOSTNAME;

		if (!isset($ipList[$ip])) {
			$delay = new \Datetime();
			$delay->sub(new \DateInterval('P' . $this->cacheLifeTimeForResults . 'W'));

			// Check if the IP address exists in the DB and if the record is younger than the defined cache
			foreach ($dbList as $item) {
				$itemDate = new \Datetime($item['updated_at']);

				if (($item['ip'] == $ip) && ($delay <= $itemDate)) {
					$ipList[$ip] = $item['as_name'];
					$itemFound = true;
				} elseif (($item['ip'] == $ip) && ($itemDate < $delay)) {
					$companyDetails = $this->getCompanyDetails($ip);
					$companyName = $companyDetails['as_name'];
					$itemFound = $companyName ? true : false;
					$ipList[$ip] = $companyName ? $companyName : ($hostname === $ip ? EMPTY_HOSTNAME : $hostname);

					// We update the DB only if we got results from the getCompanyDetails method.
					if (($ipList[$ip] != $hostname) && $companyName) {
						$this->updateCompanyDetails($item, [
							'as_number' => $companyDetails['as_number'],
							'as_name'   => $companyDetails['as_name'],
						]);
					}
				}
			}
		}

		// If the IP doesn't exist in the DB, and if it is a valid IP, try to get the details
		if (!isset($ipList[$ip]) && !$itemFound && filter_var($ip, FILTER_VALIDATE_IP)) {
			$companyDetails = $this->getCompanyDetails($ip);
			$companyName = $companyDetails['as_name'];
			$itemFound = $companyName ? true : false;
			$ipList[$ip] = $companyName ? $companyName : ($hostname === $ip ? EMPTY_HOSTNAME : $hostname);

			// We insert the item in the DB only if we got results from the getCompanyDetails method.
			if (($ipList[$ip] != $hostname) && $companyName) {
				$this->insertCompanyDetails([
					'ip'        => $ip,
					'as_number' => $companyDetails['as_number'],
					'as_name'   => $companyDetails['as_name'],
				]);
			}
		}

		// If the IP is not valid, just return the empty hostname
		elseif (!filter_var($ip, FILTER_VALIDATE_IP)) {
			$ipList[$ip] = EMPTY_HOSTNAME;
		}

		return $ipList;
	}

	/**
	 * Another example method that returns a data table.
	 *
	 * @param string $ip
	 *
	 * @return array
	 */
	private function getCompanyDetails($ip)
	{
		return [
			'as_name'   => mt_rand(10000, 99999),
			'as_number' => mt_rand(1000, 9999),
		];

		$ipInfo = new IPInfo();

		// If no token has been set, stop here.
		if (!$ipInfo->accessToken) {
			return [
				'as_name'   => null,
				'as_number' => null,
			];
		}

		$details = $ipInfo->getDetails($ip);
		$details = json_decode($details);
		$companyName = null;
		$asNumber = null;

		if (isset($details->company) && isset($details->company->name)) {
			$companyName = $details->company->name;

			if ($details->company->domain) {
				$companyName .= ' (' . $details->company->domain . ')';
			}
		} elseif (isset($details->org) && !isset($details->org->name)) {
			$orgElements = explode(' ', $details->org);
			$asNumber = array_shift($orgElements);
			$asName = \count($orgElements) > 1 ? implode(' ', $orgElements) : $orgElements[0];
			$companyName = $asName;
		}

		return [
			'as_name'   => $companyName,
			'as_number' => $asNumber,
		];
	}

	/**
	 * A private methode to update the company details that we found in the DB.
	 *
	 * @param array $item
	 * @param array $data
	 *
	 * @return bool
	 */
	private function updateCompanyDetails($item, $data)
	{
		try {
			$query = 'UPDATE ' . Common::prefixTable('ip_to_company') . ' SET as_number = ?, as_name = ? WHERE id = ?';
			$bind = array($data['as_number'], $data['as_name'], $item['id']);

			Db::query($query, $bind);
		} catch (Exception $e) {
			throw $e;
		}

		return true;
	}

	/**
	 * A private methode to save the company details in the DB.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	private function insertCompanyDetails($data)
	{
		try {
			$query = 'INSERT INTO ' . Common::prefixTable('ip_to_company') . ' (ip, as_number, as_name) VALUES (?, ?, ?)';
			$bind = array($data['ip'], $data['as_number'], $data['as_name']);

			Db::query($query, $bind);
		} catch (Exception $e) {
			throw $e;
		}

		return true;
	}
}
