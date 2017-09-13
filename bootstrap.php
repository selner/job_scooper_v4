<?php

define('__APP_VERSION__', "Job Scooper v4.use-propel-orm");

//use Doctrine\ORM\Tools\Setup;
if (file_exists(dirname(__FILE__).'/vendor/autoload.php')) {
    require_once(dirname(__FILE__).'/vendor/autoload.php');
} else {
    trigger_error("Composer required to run this app.");
}


// setup Propel
require_once(dirname(__FILE__).'/config/generated-conf/config.php');

require_once(dirname(__FILE__).'/include/HelpersBase.php');

require_once(dirname(__FILE__).'/lib/pharse.php');
require_once(dirname(__FILE__).'/lib/Linkify.php');
require_once(dirname(__FILE__).'/lib/AddressNormalization.php');

require_once(dirname(__FILE__).'/include/Helpers.php');
require_once(dirname(__FILE__).'/include/HelpersFiles.php');
require_once(dirname(__FILE__).'/include/HelpersPropel.php');
require_once(dirname(__FILE__).'/include/JobListFilters.php');
require_once(dirname(__FILE__).'/include/ErrorManager.php');
require_once(dirname(__FILE__).'/include/SeleniumSession.php');
require_once(dirname(__FILE__).'/include/CSimpleHTMLHelper.php');
require_once(dirname(__FILE__).'/include/CmdLineOptions.php');
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

