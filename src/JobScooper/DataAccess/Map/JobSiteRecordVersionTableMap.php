<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\JobSiteRecordVersion;
use JobScooper\DataAccess\JobSiteRecordVersionQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\InstancePoolTrait;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Map\TableMapTrait;


/**
 * This class defines the structure of the 'job_site_version' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class JobSiteRecordVersionTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.JobSiteRecordVersionTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'job_site_version';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\JobSiteRecordVersion';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.JobSiteRecordVersion';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 12;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 1;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 11;

    /**
     * the column name for the jobsite_key field
     */
    const COL_JOBSITE_KEY = 'job_site_version.jobsite_key';

    /**
     * the column name for the plugin_class_name field
     */
    const COL_PLUGIN_CLASS_NAME = 'job_site_version.plugin_class_name';

    /**
     * the column name for the display_name field
     */
    const COL_DISPLAY_NAME = 'job_site_version.display_name';

    /**
     * the column name for the is_disabled field
     */
    const COL_IS_DISABLED = 'job_site_version.is_disabled';

    /**
     * the column name for the date_last_pulled field
     */
    const COL_DATE_LAST_PULLED = 'job_site_version.date_last_pulled';

    /**
     * the column name for the date_last_run field
     */
    const COL_DATE_LAST_RUN = 'job_site_version.date_last_run';

    /**
     * the column name for the date_last_completed field
     */
    const COL_DATE_LAST_COMPLETED = 'job_site_version.date_last_completed';

    /**
     * the column name for the date_last_failed field
     */
    const COL_DATE_LAST_FAILED = 'job_site_version.date_last_failed';

    /**
     * the column name for the last_user_search_run_id field
     */
    const COL_LAST_USER_SEARCH_RUN_ID = 'job_site_version.last_user_search_run_id';

    /**
     * the column name for the supported_country_codes field
     */
    const COL_SUPPORTED_COUNTRY_CODES = 'job_site_version.supported_country_codes';

    /**
     * the column name for the results_filter_type field
     */
    const COL_RESULTS_FILTER_TYPE = 'job_site_version.results_filter_type';

    /**
     * the column name for the version field
     */
    const COL_VERSION = 'job_site_version.version';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /** The enumerated values for the results_filter_type field */
    const COL_RESULTS_FILTER_TYPE_ALL_ONLY = 'all-only';
    const COL_RESULTS_FILTER_TYPE_ALL_BY_LOCATION = 'all-by-location';
    const COL_RESULTS_FILTER_TYPE_USER_FILTERED = 'user-filtered';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('JobSiteKey', 'PluginClassName', 'DisplayName', 'isDisabled', 'LastPulledAt', 'LastRunAt', 'LastCompletedAt', 'LastFailedAt', 'LastUserSearchRunId', 'SupportedCountryCodes', 'ResultsFilterType', 'Version', ),
        self::TYPE_CAMELNAME     => array('jobSiteKey', 'pluginClassName', 'displayName', 'isDisabled', 'lastPulledAt', 'lastRunAt', 'lastCompletedAt', 'lastFailedAt', 'lastUserSearchRunId', 'supportedCountryCodes', 'resultsFilterType', 'version', ),
        self::TYPE_COLNAME       => array(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY, JobSiteRecordVersionTableMap::COL_PLUGIN_CLASS_NAME, JobSiteRecordVersionTableMap::COL_DISPLAY_NAME, JobSiteRecordVersionTableMap::COL_IS_DISABLED, JobSiteRecordVersionTableMap::COL_DATE_LAST_PULLED, JobSiteRecordVersionTableMap::COL_DATE_LAST_RUN, JobSiteRecordVersionTableMap::COL_DATE_LAST_COMPLETED, JobSiteRecordVersionTableMap::COL_DATE_LAST_FAILED, JobSiteRecordVersionTableMap::COL_LAST_USER_SEARCH_RUN_ID, JobSiteRecordVersionTableMap::COL_SUPPORTED_COUNTRY_CODES, JobSiteRecordVersionTableMap::COL_RESULTS_FILTER_TYPE, JobSiteRecordVersionTableMap::COL_VERSION, ),
        self::TYPE_FIELDNAME     => array('jobsite_key', 'plugin_class_name', 'display_name', 'is_disabled', 'date_last_pulled', 'date_last_run', 'date_last_completed', 'date_last_failed', 'last_user_search_run_id', 'supported_country_codes', 'results_filter_type', 'version', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('JobSiteKey' => 0, 'PluginClassName' => 1, 'DisplayName' => 2, 'isDisabled' => 3, 'LastPulledAt' => 4, 'LastRunAt' => 5, 'LastCompletedAt' => 6, 'LastFailedAt' => 7, 'LastUserSearchRunId' => 8, 'SupportedCountryCodes' => 9, 'ResultsFilterType' => 10, 'Version' => 11, ),
        self::TYPE_CAMELNAME     => array('jobSiteKey' => 0, 'pluginClassName' => 1, 'displayName' => 2, 'isDisabled' => 3, 'lastPulledAt' => 4, 'lastRunAt' => 5, 'lastCompletedAt' => 6, 'lastFailedAt' => 7, 'lastUserSearchRunId' => 8, 'supportedCountryCodes' => 9, 'resultsFilterType' => 10, 'version' => 11, ),
        self::TYPE_COLNAME       => array(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY => 0, JobSiteRecordVersionTableMap::COL_PLUGIN_CLASS_NAME => 1, JobSiteRecordVersionTableMap::COL_DISPLAY_NAME => 2, JobSiteRecordVersionTableMap::COL_IS_DISABLED => 3, JobSiteRecordVersionTableMap::COL_DATE_LAST_PULLED => 4, JobSiteRecordVersionTableMap::COL_DATE_LAST_RUN => 5, JobSiteRecordVersionTableMap::COL_DATE_LAST_COMPLETED => 6, JobSiteRecordVersionTableMap::COL_DATE_LAST_FAILED => 7, JobSiteRecordVersionTableMap::COL_LAST_USER_SEARCH_RUN_ID => 8, JobSiteRecordVersionTableMap::COL_SUPPORTED_COUNTRY_CODES => 9, JobSiteRecordVersionTableMap::COL_RESULTS_FILTER_TYPE => 10, JobSiteRecordVersionTableMap::COL_VERSION => 11, ),
        self::TYPE_FIELDNAME     => array('jobsite_key' => 0, 'plugin_class_name' => 1, 'display_name' => 2, 'is_disabled' => 3, 'date_last_pulled' => 4, 'date_last_run' => 5, 'date_last_completed' => 6, 'date_last_failed' => 7, 'last_user_search_run_id' => 8, 'supported_country_codes' => 9, 'results_filter_type' => 10, 'version' => 11, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, )
    );

    /** The enumerated values for this table */
    protected static $enumValueSets = array(
                JobSiteRecordVersionTableMap::COL_RESULTS_FILTER_TYPE => array(
                            self::COL_RESULTS_FILTER_TYPE_ALL_ONLY,
            self::COL_RESULTS_FILTER_TYPE_ALL_BY_LOCATION,
            self::COL_RESULTS_FILTER_TYPE_USER_FILTERED,
        ),
    );

    /**
     * Gets the list of values for all ENUM and SET columns
     * @return array
     */
    public static function getValueSets()
    {
      return static::$enumValueSets;
    }

    /**
     * Gets the list of values for an ENUM or SET column
     * @param string $colname
     * @return array list of possible values for the column
     */
    public static function getValueSet($colname)
    {
        $valueSets = self::getValueSets();

        return $valueSets[$colname];
    }

    /**
     * Initialize the table attributes and columns
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        $this->setName('job_site_version');
        $this->setPhpName('JobSiteRecordVersion');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\JobSiteRecordVersion');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(false);
        // columns
        $this->addForeignPrimaryKey('jobsite_key', 'JobSiteKey', 'VARCHAR' , 'job_site', 'jobsite_key', true, 100, null);
        $this->getColumn('jobsite_key')->setPrimaryString(true);
        $this->addColumn('plugin_class_name', 'PluginClassName', 'VARCHAR', false, 100, null);
        $this->addColumn('display_name', 'DisplayName', 'VARCHAR', false, 255, null);
        $this->addColumn('is_disabled', 'isDisabled', 'BOOLEAN', false, null, false);
        $this->addColumn('date_last_pulled', 'LastPulledAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_last_run', 'LastRunAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_last_completed', 'LastCompletedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_last_failed', 'LastFailedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('last_user_search_run_id', 'LastUserSearchRunId', 'INTEGER', false, null, null);
        $this->addColumn('supported_country_codes', 'SupportedCountryCodes', 'ARRAY', false, null, null);
        $this->addColumn('results_filter_type', 'ResultsFilterType', 'ENUM', false, null, null);
        $this->getColumn('results_filter_type')->setValueSet(array (
  0 => 'all-only',
  1 => 'all-by-location',
  2 => 'user-filtered',
));
        $this->addPrimaryKey('version', 'Version', 'INTEGER', true, null, 0);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('JobSiteRecord', '\\JobScooper\\DataAccess\\JobSiteRecord', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':jobsite_key',
    1 => ':jobsite_key',
  ),
), 'CASCADE', null, null, false);
    } // buildRelations()

    /**
     * Adds an object to the instance pool.
     *
     * Propel keeps cached copies of objects in an instance pool when they are retrieved
     * from the database. In some cases you may need to explicitly add objects
     * to the cache in order to ensure that the same objects are always returned by find*()
     * and findPk*() calls.
     *
     * @param \JobScooper\DataAccess\JobSiteRecordVersion $obj A \JobScooper\DataAccess\JobSiteRecordVersion object.
     * @param string $key             (optional) key to use for instance map (for performance boost if key was already calculated externally).
     */
    public static function addInstanceToPool($obj, $key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (null === $key) {
                $key = serialize([(null === $obj->getJobSiteKey() || is_scalar($obj->getJobSiteKey()) || is_callable([$obj->getJobSiteKey(), '__toString']) ? (string) $obj->getJobSiteKey() : $obj->getJobSiteKey()), (null === $obj->getVersion() || is_scalar($obj->getVersion()) || is_callable([$obj->getVersion(), '__toString']) ? (string) $obj->getVersion() : $obj->getVersion())]);
            } // if key === null
            self::$instances[$key] = $obj;
        }
    }

    /**
     * Removes an object from the instance pool.
     *
     * Propel keeps cached copies of objects in an instance pool when they are retrieved
     * from the database.  In some cases -- especially when you override doDelete
     * methods in your stub classes -- you may need to explicitly remove objects
     * from the cache in order to prevent returning objects that no longer exist.
     *
     * @param mixed $value A \JobScooper\DataAccess\JobSiteRecordVersion object or a primary key value.
     */
    public static function removeInstanceFromPool($value)
    {
        if (Propel::isInstancePoolingEnabled() && null !== $value) {
            if (is_object($value) && $value instanceof \JobScooper\DataAccess\JobSiteRecordVersion) {
                $key = serialize([(null === $value->getJobSiteKey() || is_scalar($value->getJobSiteKey()) || is_callable([$value->getJobSiteKey(), '__toString']) ? (string) $value->getJobSiteKey() : $value->getJobSiteKey()), (null === $value->getVersion() || is_scalar($value->getVersion()) || is_callable([$value->getVersion(), '__toString']) ? (string) $value->getVersion() : $value->getVersion())]);

            } elseif (is_array($value) && count($value) === 2) {
                // assume we've been passed a primary key";
                $key = serialize([(null === $value[0] || is_scalar($value[0]) || is_callable([$value[0], '__toString']) ? (string) $value[0] : $value[0]), (null === $value[1] || is_scalar($value[1]) || is_callable([$value[1], '__toString']) ? (string) $value[1] : $value[1])]);
            } elseif ($value instanceof Criteria) {
                self::$instances = [];

                return;
            } else {
                $e = new PropelException("Invalid value passed to removeInstanceFromPool().  Expected primary key or \JobScooper\DataAccess\JobSiteRecordVersion object; got " . (is_object($value) ? get_class($value) . ' object.' : var_export($value, true)));
                throw $e;
            }

            unset(self::$instances[$key]);
        }
    }

    /**
     * Retrieves a string version of the primary key from the DB resultset row that can be used to uniquely identify a row in this table.
     *
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, a serialize()d version of the primary key will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return string The primary key hash of the row
     */
    public static function getPrimaryKeyHashFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        // If the PK cannot be derived from the row, return NULL.
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] === null && $row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return serialize([(null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)]), (null === $row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)])]);
    }

    /**
     * Retrieves the primary key from the DB resultset row
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, an array of the primary key columns will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return mixed The primary key of the row
     */
    public static function getPrimaryKeyFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
            $pks = [];

        $pks[] = (string) $row[
            $indexType == TableMap::TYPE_NUM
                ? 0 + $offset
                : self::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)
        ];
        $pks[] = (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 10 + $offset
                : self::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)
        ];

        return $pks;
    }

    /**
     * The class that the tableMap will make instances of.
     *
     * If $withPrefix is true, the returned path
     * uses a dot-path notation which is translated into a path
     * relative to a location on the PHP include_path.
     * (e.g. path.to.MyClass -> 'path/to/MyClass.php')
     *
     * @param boolean $withPrefix Whether or not to return the path with the class name
     * @return string path.to.ClassName
     */
    public static function getOMClass($withPrefix = true)
    {
        return $withPrefix ? JobSiteRecordVersionTableMap::CLASS_DEFAULT : JobSiteRecordVersionTableMap::OM_CLASS;
    }

    /**
     * Populates an object of the default type or an object that inherit from the default.
     *
     * @param array  $row       row returned by DataFetcher->fetch().
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                 One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     * @return array           (JobSiteRecordVersion object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = JobSiteRecordVersionTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = JobSiteRecordVersionTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + JobSiteRecordVersionTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = JobSiteRecordVersionTableMap::OM_CLASS;
            /** @var JobSiteRecordVersion $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            JobSiteRecordVersionTableMap::addInstanceToPool($obj, $key);
        }

        return array($obj, $col);
    }

    /**
     * The returned array will contain objects of the default type or
     * objects that inherit from the default.
     *
     * @param DataFetcherInterface $dataFetcher
     * @return array
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function populateObjects(DataFetcherInterface $dataFetcher)
    {
        $results = array();

        // set the class once to avoid overhead in the loop
        $cls = static::getOMClass(false);
        // populate the object(s)
        while ($row = $dataFetcher->fetch()) {
            $key = JobSiteRecordVersionTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = JobSiteRecordVersionTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var JobSiteRecordVersion $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                JobSiteRecordVersionTableMap::addInstanceToPool($obj, $key);
            } // if key exists
        }

        return $results;
    }
    /**
     * Add all the columns needed to create a new object.
     *
     * Note: any columns that were marked with lazyLoad="true" in the
     * XML schema will not be added to the select list and only loaded
     * on demand.
     *
     * @param Criteria $criteria object containing the columns to add.
     * @param string   $alias    optional table alias
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function addSelectColumns(Criteria $criteria, $alias = null)
    {
        if (null === $alias) {
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_PLUGIN_CLASS_NAME);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_DISPLAY_NAME);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_IS_DISABLED);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_DATE_LAST_PULLED);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_DATE_LAST_RUN);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_DATE_LAST_COMPLETED);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_DATE_LAST_FAILED);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_SUPPORTED_COUNTRY_CODES);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_RESULTS_FILTER_TYPE);
            $criteria->addSelectColumn(JobSiteRecordVersionTableMap::COL_VERSION);
        } else {
            $criteria->addSelectColumn($alias . '.jobsite_key');
            $criteria->addSelectColumn($alias . '.plugin_class_name');
            $criteria->addSelectColumn($alias . '.display_name');
            $criteria->addSelectColumn($alias . '.is_disabled');
            $criteria->addSelectColumn($alias . '.date_last_pulled');
            $criteria->addSelectColumn($alias . '.date_last_run');
            $criteria->addSelectColumn($alias . '.date_last_completed');
            $criteria->addSelectColumn($alias . '.date_last_failed');
            $criteria->addSelectColumn($alias . '.supported_country_codes');
            $criteria->addSelectColumn($alias . '.results_filter_type');
            $criteria->addSelectColumn($alias . '.version');
        }
    }

    /**
     * Returns the TableMap related to this object.
     * This method is not needed for general use but a specific application could have a need.
     * @return TableMap
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function getTableMap()
    {
        return Propel::getServiceContainer()->getDatabaseMap(JobSiteRecordVersionTableMap::DATABASE_NAME)->getTable(JobSiteRecordVersionTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(JobSiteRecordVersionTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(JobSiteRecordVersionTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new JobSiteRecordVersionTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a JobSiteRecordVersion or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or JobSiteRecordVersion object or primary key or array of primary keys
     *              which is used to create the DELETE statement
     * @param  ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
     public static function doDelete($values, ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSiteRecordVersionTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\JobSiteRecordVersion) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(JobSiteRecordVersionTableMap::DATABASE_NAME);
            // primary key is composite; we therefore, expect
            // the primary key passed to be an array of pkey values
            if (count($values) == count($values, COUNT_RECURSIVE)) {
                // array is not multi-dimensional
                $values = array($values);
            }
            foreach ($values as $value) {
                $criterion = $criteria->getNewCriterion(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY, $value[0]);
                $criterion->addAnd($criteria->getNewCriterion(JobSiteRecordVersionTableMap::COL_VERSION, $value[1]));
                $criteria->addOr($criterion);
            }
        }

        $query = JobSiteRecordVersionQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            JobSiteRecordVersionTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                JobSiteRecordVersionTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the job_site_version table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return JobSiteRecordVersionQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a JobSiteRecordVersion or Criteria object.
     *
     * @param mixed               $criteria Criteria or JobSiteRecordVersion object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSiteRecordVersionTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from JobSiteRecordVersion object
        }


        // Set the correct dbName
        $query = JobSiteRecordVersionQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // JobSiteRecordVersionTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
JobSiteRecordVersionTableMap::buildTableMap();
