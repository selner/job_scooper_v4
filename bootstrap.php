<?php
//use Doctrine\ORM\Tools\Setup;
if (file_exists(dirname(__FILE__).'/vendor/autoload.php')) {
    require_once(dirname(__FILE__).'/vendor/autoload.php');
} else {
    trigger_error("Composer required to run this app.");
}


/*
// Create a simple "default" Doctrine ORM configuration for XML Mapping
$isDevMode = true;
// $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"), $isDevMode);
// or if you prefer yaml or annotations
$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/include/static/xml/JobPosting.dcm.xml"), $isDevMode);
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

// database configuration parameters
$conn = array(
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/db.sqlite',
);

// obtaining the entity manager
$entityManager = \Doctrine\ORM\EntityManager::create($conn, $config);
*/

require_once(dirname(__FILE__).'/lib/pharse.php');
require_once(dirname(__FILE__).'/lib/Linkify.php');
require_once(dirname(__FILE__).'/lib/AddressNormalization.php');

require_once(dirname(__FILE__).'/include/helpers.php');
require_once(dirname(__FILE__).'/include/JobListFilters.php');
require_once(dirname(__FILE__).'/include/ErrorManager.php');
require_once(dirname(__FILE__).'/include/SeleniumSession.php');
require_once(dirname(__FILE__).'/include/CSimpleHTMLHelper.php');
require_once(dirname(__FILE__).'/include/CmdLineOptions.php');
require_once(dirname(__FILE__).'/include/ClassJobsSiteCommon.php');
require_once(dirname(__FILE__).'/include/ClassMultiSiteSearch.php');
require_once(dirname(__FILE__).'/include/PluginsAbstractJobsBaseClass.php');
require_once(dirname(__FILE__).'/include/PluginClassTypes.php');
require_once(dirname(__FILE__).'/include/ClassConfig.php');
require_once(dirname(__FILE__).'/include/StageManager.php');
require_once(dirname(__FILE__).'/include/PluginSimpleJobClasses.php');
require_once(dirname(__FILE__).'/include/PluginJsonConfigSupport.php');
require_once(dirname(__FILE__).'/include/JobsAutoMarker.php');
require_once(dirname(__FILE__).'/include/ClassJobsNotifier.php');
require_once(dirname(__FILE__).'/include/PluginOptions.php');

