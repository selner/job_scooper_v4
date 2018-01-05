<?php
/**
 * Copyright 2014-17 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

use http\Url;


/**
 * Class AbstractAdicio
 */
abstract class AbstractAdicio extends \JobScooper\BasePlugin\Classes\AjaxHtmlSimplePlugin
{
	protected $PaginationType = C__PAGINATION_PAGE_VIA_URL;
	protected $JobListingsPerPage = 50;
	protected $LastKnownSiteLayout = null;
	protected $CountryCodes = array("US");

	// BUGBUG: setting "search job title only" seems to not find jobs with just one word in the title.  "Pharmacy Intern" does not come back for "intern" like it should.  Therefore not setting the kwsJobTitleOnly=true flag.
	//
	protected $strBaseURLPathSection = "/jobs/results/keyword/***KEYWORDS***?view=List_Detail&SearchNetworks=US&networkView=national&location=***LOCATION***&radius=50&sort=PostDate+desc%2C+Priority+desc%2C+score+desc&rows=50";
	protected $additionalLoadDelaySeconds = 2;
	protected $strBaseURLPathSuffix = "";
	protected $SearchUrlFormat = null;
	protected $LocationType = 'location-city-comma-statecode';

	protected $arrBaseListingTagSetupNationalSearch = array(
		'TotalPostCount' => array('selector' => 'span#retUSCountNumber'),  # BUGBUG:  need this empty array so that the parent class doesn't auto-set to C__JOB_ITEMCOUNT_NOTAPPLICABLE__
		'JobPostItem' => array('selector' => 'div.aiResultsWrapper'),
		'Title' => array('selector' => 'div.aiResultTitle h3 a'),
		'Url' => array('selector' => 'div.aiResultTitle h3 a', 'return_attribute' => 'href'),
		'JobSitePostId' => array('selector' => 'div.aiResultsMainDiv', 'return_attribute' => 'id', 'return_value_regex' =>  '/aiResultsMainDiv(.*)/'),
		'Company' => array('selector' => 'li.aiResultsCompanyName'),
		'Location' => array('selector' => 'span.aiResultsLocationSpan'),
		'PostedAt' => array('selector' => 'div.aiDescriptionPod ul li', 'index' => 2),
		'Category' => array('selector' => 'div.aiDescriptionPod ul li', 'index' => 3)
	);

	protected $arrBaseListingTagSetupJobsResponsive = array(
		'TotalPostCount' => array('selector' => 'h1#search-title-holder', 'return_value_regex' => '/(.*) [Jj]obs/'),
		'JobPostItem' => array('selector' => 'div.arJobPodWrap'),
		'Title' => array('selector' => 'div.arJobTitle h3 a'),
		'Url' => array('selector' => 'div.arJobTitle h3 a', 'return_attribute' => 'href'),
		'JobSitePostId' => array('selector' => 'div.arSaveJob a', 'return_attribute' => 'data-jobid'),
		'Company' => array('selector' => 'div.arJobCoLink'),
		'Location' => array('selector' => 'div.arJobCoLoc'),
		'PostedAt' => array('selector' => 'div.arJobPostDate span')
	);

	protected $_layout = null;

	/**
	 * AbstractAdicio constructor.
	 * @throws \Exception
	 */
	function __construct()
	{
		$this->additionalBitFlags[] = C__JOB_KEYWORD_PARAMETER_SPACES_AS_DASHES;

		$this->JobPostingBaseUrl = $this->childSiteURLBase;
		$this->SearchUrlFormat = $this->childSiteURLBase . $this->strBaseURLPathSection . $this->strBaseURLPathSuffix;

		parent::__construct();
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	function downloadLatestJobsForAllSearches()
	{
		if(!empty($this->LastKnownSiteLayout))
		{
			$this->setAdicioPageLayout($this->LastKnownSiteLayout);
		}
		else {
			$template = $this->_determinePageLayout();

			$this->setAdicioPageLayout($template);
			LogDebug("Adicio Template for " . get_class($this) . " with url '{$url}: " . PHP_EOL . "$template = {$template}," . PHP_EOL . "layout = {$this->_layout},  " . PHP_EOL . "template = {$template},  " . PHP_EOL . "id = {$id}, " . PHP_EOL . "matches = " . getArrayDebugOutput($matches));

		}

		return parent::downloadLatestJobsForAllSearches();
	}

	/**
	 * @throws \Exception
	 */
	private function _determinePageLayout()
	{
		$urlParts = parse_url($this->SearchUrlFormat);
		$urlParts['query'] = "";
		$urlParts['fragment'] = "";
		$urlParts['path'] = "/jobs/search/results";
		$baseUrl = new http\Url($urlParts, $urlParts, Url::REPLACE);
		$url = $baseUrl->toString();
		if (is_null($this->selenium)) {
			try {
				$this->selenium = new \JobScooper\Manager\SeleniumManager();
			} catch (Exception $ex) {
				handleException($ex, "Unable to start Selenium to get jobs for plugin '" . $this->JobSiteName . "'", true);
			}
		}

		$baseHTML = $this->getSimpleHtmlDomFromSeleniumPage($url);
		$this->_layout = "careersdefault";

		if (!empty($baseHTML)) {
			try {
				$head = $baseHTML->find("head");
				if (!empty($head) && count($head) >= 1) {
					foreach ($head[0]->children() as $child) {
						if ($child->isCommentNode()) {
							$template = "unknown";
							$id = 0;
							$matches = array();
							$matched = preg_match("/Template Type Requested:\s*([^\(]+)\s*([^,]+)*,?\s?(\d+)?/", $child->text(), $matches);
							if ($matched !== false) {
								if (count($matches) > 2) {
									$template = $matches[2];
									$id = $matches[3];
								} elseif (count($matches) == 2) {
									$template = $matches[1];
								}
								break;
							}
						}
					}
				}


			} catch (Exception $ex) {
			} finally {
				$this->selenium->done();
				$this->selenium = null;
			}
		}
	}

	/**
	 * @param $layout
	 */
	protected function setAdicioPageLayout($layout)
	{

		$switchVal = cleanupSlugPart($layout, "");
		$this->_layout = $switchVal;
		switch($switchVal) {
			case 'jobsresponsivedefault':
				$this->arrBaseListingTagSetup = $this->arrBaseListingTagSetupJobsResponsive;
				break;

			case "careersdefault":
				$this->arrBaseListingTagSetup = $this->arrBaseListingTagSetupNationalSearch;
				break;

			case "jobsearchresults":
				$this->arrBaseListingTagSetup = $this->arrBaseListingTagSetupNationalSearch;
				$this->arrBaseListingTagSetup['TotalPostCount']['selector'] = "span#retCountNumber";
				break;

			default:
				LogWarning("UNKNOWN ADICIO LAYOUT");
				$this->_layout = "default";
				$this->arrBaseListingTagSetup = $this->arrBaseListingTagSetupNationalSearch;
				break;
		}
	}
}


/**
 * Class PluginMashable
 */
class PluginMashable extends AbstractAdicio
{
	protected $JobSiteName = 'Mashable';
	protected $childSiteURLBase = 'http://jobs.mashable.com';
	// Note:  Mashable has a short list of jobs (< 500-1000 total) so we exclude keyword search here as an optimization.  We may download more jobs overall, but through fewer round trips to the servers
	protected $strBaseURLPathSection = "/jobs/search/results?location=***LOCATION***&radius=50&view=List_Detail&sort=PostType+asc%2C+PostDate+desc%2C+IsFeatured+desc&rows=50&";
	protected $LocationType = 'location-city-comma-state';
	protected $LastKnownSiteLayout = "jobsresponsivedefault";

}


/**
 * Class PluginJacksonville
 */
class PluginJacksonville extends AbstractAdicio
{
	protected $JobSiteName = 'Jacksonville';
	protected $childSiteURLBase = 'http://jobs.jacksonville.com';
	protected $LastKnownSiteLayout = "jobsresponsivedefault";
}


/**
 * Class PluginPolitico
 */
class PluginPolitico extends AbstractAdicio
{
	protected $JobSiteName = 'Politico';
	protected $childSiteURLBase = 'http://jobs.powerjobs.com';
	protected $LastKnownSiteLayout = "jobsearchresults";
}

/**
 * Class PluginIEEE
 */
class PluginIEEE extends AbstractAdicio
{
	protected $JobSiteName = 'IEEE';
	protected $childSiteURLBase = 'http://jobs.ieee.org';
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginVariety
 */
class PluginVariety extends AbstractAdicio
{
	protected $JobSiteName = 'Variety';
	protected $childSiteURLBase = 'http://jobs.variety.com';
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCellCom
 */
class PluginCellCom extends AbstractAdicio
{
	protected $JobSiteName = 'CellCom';
	protected $childSiteURLBase = 'http://jobs.cell.com';
	protected $LastKnownSiteLayout = "careersdefault";
}

// NO LONGER ADICIO-BASED
///**
// * Class PluginCareerJet
// */
//class PluginCareerJet extends AbstractAdicio
//{
//	protected $JobSiteName = 'CareerJet';
//	protected $childSiteURLBase = 'http://www.careerjet.co.uk';
//}

/**
 * Class PluginHamptonRoads
 */
class PluginHamptonRoads extends AbstractAdicio
{
	protected $JobSiteName = 'HamptonRoads';
	protected $childSiteURLBase = 'http://careers.hamptonroads.com';
	protected $LastKnownSiteLayout = "jobsresponsivedefault";
}

/**
 * Class PluginAnalyticTalent
 */
class PluginAnalyticTalent extends AbstractAdicio
{
	protected $JobSiteName = 'AnalyticTalent';
	protected $childSiteURLBase = 'http://careers.analytictalent.com';
	protected $LastKnownSiteLayout = "jobsresponsivedefault";
}

/**
 * Class PluginKenoshaNews
 */
class PluginKenoshaNews extends AbstractAdicio
{
	protected $JobSiteName = 'KenoshaNews';
	protected $childSiteURLBase = 'http://kenosha.careers.adicio.com';
	protected $LastKnownSiteLayout = "jobsresponsivedefault";
}

/**
 * Class PluginTopekaCapitalJournal
 */
class PluginTopekaCapitalJournal extends AbstractAdicio
{
	protected $JobSiteName = 'TopekaCapitalJournal';
	protected $childSiteURLBase = 'http://jobs.cjonline.com';
	protected $LastKnownSiteLayout = "jobsearchresults";
}

/**
 * Class PluginRetailCareersNow
 */
class PluginRetailCareersNow extends AbstractAdicio
{
	protected $JobSiteName = 'RetailCareersNow';
	protected $childSiteURLBase = 'http://retail.careers.adicio.com';
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginHealthJobs
 */
class PluginHealthJobs extends AbstractAdicio
{
	protected $JobSiteName = 'HealthJobs';
	protected $childSiteURLBase = 'http://healthjobs.careers.adicio.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginPharmacyJobCenter
 */
class PluginPharmacyJobCenter extends AbstractAdicio
{
	protected $JobSiteName = 'PharmacyJobCenter';
	protected $childSiteURLBase = 'http://pharmacy.careers.adicio.com';
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginKCBD
 */
class PluginKCBD extends AbstractAdicio
{
	protected $JobSiteName = 'KCBD';
	protected $childSiteURLBase = 'http://kcbd.careers.adicio.com';
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginAfro
 */
class PluginAfro extends AbstractAdicio
{
	protected $JobSiteName = 'Afro';
	protected $childSiteURLBase = 'http://afro.careers.adicio.com';
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginJamaCareerCenter
 */
class PluginJamaCareerCenter extends AbstractAdicio
{
	protected $JobSiteName = 'JamaCareerCenter';
	protected $childSiteURLBase = 'http://jama.careers.adicio.com';

	protected $SearchUrlFormat = "http://jama.careers.adicio.com/jobs/search/results?view=List_Detail&sort=PostDate+desc&radius=25&rows=50";

}

/**
 * Class PluginSeacoastOnline
 */
class PluginSeacoastOnline extends AbstractAdicio
{
	protected $JobSiteName = 'SeacoastOnline';
	protected $childSiteURLBase = 'http://seacoast.careers.adicio.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginAlbuquerqueJournal
 */
class PluginAlbuquerqueJournal extends AbstractAdicio
{
	protected $JobSiteName = 'AlbuquerqueJournal';
	protected $childSiteURLBase = 'http://abqcareers.careers.adicio.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginWestHawaiiToday
 */
class PluginWestHawaiiToday extends AbstractAdicio
{
	protected $JobSiteName = 'WestHawaiiToday';
	protected $childSiteURLBase = 'http://careers.westhawaiitoday.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginDeadline
 */
class PluginDeadline extends AbstractAdicio
{
	protected $JobSiteName = 'Deadline';
	protected $childSiteURLBase = 'http://jobsearch.deadline.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginLogCabinDemocrat
 */
class PluginLogCabinDemocrat extends AbstractAdicio
{
	protected $JobSiteName = 'LogCabinDemocrat';
	protected $childSiteURLBase = 'http://jobs.thecabin.net';
}

/**
 * Class PluginPennEnergy
 */
class PluginPennEnergy extends AbstractAdicio
{
	protected $JobSiteName = 'PennEnergy';
	protected $childSiteURLBase = 'http://careers.pennenergyjobs.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginBig4Firms
 */
class PluginBig4Firms extends AbstractAdicio
{
	protected $JobSiteName = 'Big4Firms';
	protected $childSiteURLBase = 'http://careers.big4.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginVindy
 */
class PluginVindy extends AbstractAdicio
{
	protected $JobSiteName = 'Vindy';
	protected $childSiteURLBase = 'http://careers.vindy.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCast
 */
class PluginCareerCast extends AbstractAdicio
{
	protected $JobSiteName = 'CareerCast';
	protected $childSiteURLBase = 'http://www.careercast.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginTheLancet
 */
class PluginTheLancet extends AbstractAdicio
{
	protected $JobSiteName = 'TheLancet';
	protected $childSiteURLBase = 'http://careers.thelancet.com';
protected $LastKnownSiteLayout = "careersdefault";
}

class PluginVictoriaTXAdvocate extends AbstractAdicio
{
	protected $JobSiteName = 'VictoriaTXAdvocate';
	protected $childSiteURLBase = 'http://jobs.crossroadsfinder.com';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginTVB
 */
class PluginTVB extends AbstractAdicio
{
	protected $JobSiteName = 'TVB';
	protected $childSiteURLBase = 'http://postjobs.tvb.org';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginSHRM
 */
class PluginSHRM extends AbstractAdicio
{
	protected $JobSiteName = 'SHRM';
	protected $childSiteURLBase = 'http://hrjobs.shrm.org';
protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastIT
 */
class PluginCareerCastIT extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastIT";
	protected $childSiteURLBase = "http://it.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastHealthcare
 */
class PluginCareerCastHealthcare extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastHealthcare";
	protected $childSiteURLBase = "http://healthcare.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastNursing
 */
class PluginCareerCastNursing extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastNursing";
	protected $childSiteURLBase = "http://nursing.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastTempJobs
 */
class PluginCareerCastTempJobs extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastTempJobs";
	protected $childSiteURLBase = "http://tempjobs.careercast.com";
	protected $LastKnownSiteLayout = "jobsresponsivedefault";

	protected $arrListingTagSetup = array(
		'NoPostsFound' =>  array('selector' => 'h5', 'index' => 0, 'return_attribute' => 'node', 'return_value_callback' => "matchesNoResultsPattern", 'callback_parameter' => 'Oops! Nothing')
	);
}

/**
 * Class PluginCareerCastMarketing
 */
class PluginCareerCastMarketing extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastMarketing";
	protected $childSiteURLBase = "http://marketing.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastRetail
 */
class PluginCareerCastRetail extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastRetail";
	protected $childSiteURLBase = "http://retail.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastGreenNetwork
 */
class PluginCareerCastGreenNetwork extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastGreenNetwork";
	protected $childSiteURLBase = "http://green.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastDiversity
 */
class PluginCareerCastDiversity extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastDiversity";
	protected $childSiteURLBase = "http://diversity.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastConstruction
 */
class PluginCareerCastConstruction extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastConstruction";
	protected $childSiteURLBase = "http://construction.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastEnergy
 */
class PluginCareerCastEnergy extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastEnergy";
	protected $childSiteURLBase = "http://energy.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastTrucking
 */
class PluginCareerCastTrucking extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastTrucking";
	protected $childSiteURLBase = "http://trucking.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastDisability
 */
class PluginCareerCastDisability extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastDisability";
	protected $childSiteURLBase = "http://disability.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastHR
 */
class PluginCareerCastHR extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastHR";
	protected $childSiteURLBase = "http://hr.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastVeteran
 */
class PluginCareerCastVeteran extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastVeteran";
	protected $childSiteURLBase = "http://veteran.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastHospitality
 */
class PluginCareerCastHospitality extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastHospitality";
	protected $childSiteURLBase = "http://hospitality.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

/**
 * Class PluginCareerCastFinance
 */
class PluginCareerCastFinance extends AbstractAdicio
{
	protected $JobSiteName = "CareerCastFinance";
	protected $childSiteURLBase = "http://finance.careercast.com";
	protected $LastKnownSiteLayout = "careersdefault";
}

