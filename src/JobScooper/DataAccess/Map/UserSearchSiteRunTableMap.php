<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\UserSearchSiteRun;
use JobScooper\DataAccess\UserSearchSiteRunQuery;
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
 * This class defines the structure of the 'user_search_site_run' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class UserSearchSiteRunTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.UserSearchSiteRunTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_search_site_run';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\UserSearchSiteRun';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.UserSearchSiteRun';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 12;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 12;

    /**
     * the column name for the user_id field
     */
    const COL_USER_ID = 'user_search_site_run.user_id';

    /**
     * the column name for the user_keyword_set_key field
     */
    const COL_USER_KEYWORD_SET_KEY = 'user_search_site_run.user_keyword_set_key';

    /**
     * the column name for the geolocation_id field
     */
    const COL_GEOLOCATION_ID = 'user_search_site_run.geolocation_id';

    /**
     * the column name for the jobsite_key field
     */
    const COL_JOBSITE_KEY = 'user_search_site_run.jobsite_key';

    /**
     * the column name for the app_run_id field
     */
    const COL_APP_RUN_ID = 'user_search_site_run.app_run_id';

    /**
     * the column name for the user_search_key field
     */
    const COL_USER_SEARCH_KEY = 'user_search_site_run.user_search_key';

    /**
     * the column name for the user_search_site_run_key field
     */
    const COL_USER_SEARCH_SITE_RUN_KEY = 'user_search_site_run.user_search_site_run_key';

    /**
     * the column name for the search_start_url field
     */
    const COL_SEARCH_START_URL = 'user_search_site_run.search_start_url';

    /**
     * the column name for the run_result_code field
     */
    const COL_RUN_RESULT_CODE = 'user_search_site_run.run_result_code';

    /**
     * the column name for the run_error_details field
     */
    const COL_RUN_ERROR_DETAILS = 'user_search_site_run.run_error_details';

    /**
     * the column name for the date_started field
     */
    const COL_DATE_STARTED = 'user_search_site_run.date_started';

    /**
     * the column name for the date_ended field
     */
    const COL_DATE_ENDED = 'user_search_site_run.date_ended';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /** The enumerated values for the run_result_code field */
    const COL_RUN_RESULT_CODE_NOT_RUN = 'not-run';
    const COL_RUN_RESULT_CODE_FAILED = 'failed';
    const COL_RUN_RESULT_CODE_EXCLUDED = 'excluded';
    const COL_RUN_RESULT_CODE_SKIPPED = 'skipped';
    const COL_RUN_RESULT_CODE_SUCCESSFUL = 'successful';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('UserId', 'UserKeywordSetKey', 'GeoLocationId', 'JobSiteKey', 'AppRunId', 'UserSearchKey', 'UserSearchSiteRunKey', 'SearchStartUrl', 'RunResultCode', 'RunErrorDetails', 'StartedAt', 'EndedAt', ),
        self::TYPE_CAMELNAME     => array('userId', 'userKeywordSetKey', 'geoLocationId', 'jobSiteKey', 'appRunId', 'userSearchKey', 'userSearchSiteRunKey', 'searchStartUrl', 'runResultCode', 'runErrorDetails', 'startedAt', 'endedAt', ),
        self::TYPE_COLNAME       => array(UserSearchSiteRunTableMap::COL_USER_ID, UserSearchSiteRunTableMap::COL_USER_KEYWORD_SET_KEY, UserSearchSiteRunTableMap::COL_GEOLOCATION_ID, UserSearchSiteRunTableMap::COL_JOBSITE_KEY, UserSearchSiteRunTableMap::COL_APP_RUN_ID, UserSearchSiteRunTableMap::COL_USER_SEARCH_KEY, UserSearchSiteRunTableMap::COL_USER_SEARCH_SITE_RUN_KEY, UserSearchSiteRunTableMap::COL_SEARCH_START_URL, UserSearchSiteRunTableMap::COL_RUN_RESULT_CODE, UserSearchSiteRunTableMap::COL_RUN_ERROR_DETAILS, UserSearchSiteRunTableMap::COL_DATE_STARTED, UserSearchSiteRunTableMap::COL_DATE_ENDED, ),
        self::TYPE_FIELDNAME     => array('user_id', 'user_keyword_set_key', 'geolocation_id', 'jobsite_key', 'app_run_id', 'user_search_key', 'user_search_site_run_key', 'search_start_url', 'run_result_code', 'run_error_details', 'date_started', 'date_ended', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserId' => 0, 'UserKeywordSetKey' => 1, 'GeoLocationId' => 2, 'JobSiteKey' => 3, 'AppRunId' => 4, 'UserSearchKey' => 5, 'UserSearchSiteRunKey' => 6, 'SearchStartUrl' => 7, 'RunResultCode' => 8, 'RunErrorDetails' => 9, 'StartedAt' => 10, 'EndedAt' => 11, ),
        self::TYPE_CAMELNAME     => array('userId' => 0, 'userKeywordSetKey' => 1, 'geoLocationId' => 2, 'jobSiteKey' => 3, 'appRunId' => 4, 'userSearchKey' => 5, 'userSearchSiteRunKey' => 6, 'searchStartUrl' => 7, 'runResultCode' => 8, 'runErrorDetails' => 9, 'startedAt' => 10, 'endedAt' => 11, ),
        self::TYPE_COLNAME       => array(UserSearchSiteRunTableMap::COL_USER_ID => 0, UserSearchSiteRunTableMap::COL_USER_KEYWORD_SET_KEY => 1, UserSearchSiteRunTableMap::COL_GEOLOCATION_ID => 2, UserSearchSiteRunTableMap::COL_JOBSITE_KEY => 3, UserSearchSiteRunTableMap::COL_APP_RUN_ID => 4, UserSearchSiteRunTableMap::COL_USER_SEARCH_KEY => 5, UserSearchSiteRunTableMap::COL_USER_SEARCH_SITE_RUN_KEY => 6, UserSearchSiteRunTableMap::COL_SEARCH_START_URL => 7, UserSearchSiteRunTableMap::COL_RUN_RESULT_CODE => 8, UserSearchSiteRunTableMap::COL_RUN_ERROR_DETAILS => 9, UserSearchSiteRunTableMap::COL_DATE_STARTED => 10, UserSearchSiteRunTableMap::COL_DATE_ENDED => 11, ),
        self::TYPE_FIELDNAME     => array('user_id' => 0, 'user_keyword_set_key' => 1, 'geolocation_id' => 2, 'jobsite_key' => 3, 'app_run_id' => 4, 'user_search_key' => 5, 'user_search_site_run_key' => 6, 'search_start_url' => 7, 'run_result_code' => 8, 'run_error_details' => 9, 'date_started' => 10, 'date_ended' => 11, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, )
    );

    /** The enumerated values for this table */
    protected static $enumValueSets = array(
                UserSearchSiteRunTableMap::COL_RUN_RESULT_CODE => array(
                            self::COL_RUN_RESULT_CODE_NOT_RUN,
            self::COL_RUN_RESULT_CODE_FAILED,
            self::COL_RUN_RESULT_CODE_EXCLUDED,
            self::COL_RUN_RESULT_CODE_SKIPPED,
            self::COL_RUN_RESULT_CODE_SUCCESSFUL,
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
        $this->setName('user_search_site_run');
        $this->setPhpName('UserSearchSiteRun');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\UserSearchSiteRun');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(false);
        $this->setIsCrossRef(true);
        // columns
        $this->addForeignKey('user_id', 'UserId', 'INTEGER', 'user_search', 'user_id', true, null, null);
        $this->addForeignKey('user_id', 'UserId', 'INTEGER', 'user', 'user_id', true, null, null);
        $this->addForeignPrimaryKey('user_keyword_set_key', 'UserKeywordSetKey', 'VARCHAR' , 'user_search', 'user_keyword_set_key', true, 100, null);
        $this->addForeignPrimaryKey('geolocation_id', 'GeoLocationId', 'INTEGER' , 'user_search', 'geolocation_id', true, null, null);
        $this->addForeignPrimaryKey('geolocation_id', 'GeoLocationId', 'INTEGER' , 'geolocation', 'geolocation_id', true, null, null);
        $this->addForeignPrimaryKey('jobsite_key', 'JobSiteKey', 'VARCHAR' , 'job_site', 'jobsite_key', true, 100, null);
        $this->addPrimaryKey('app_run_id', 'AppRunId', 'VARCHAR', true, 75, null);
        $this->addForeignKey('user_search_key', 'UserSearchKey', 'VARCHAR', 'user_search', 'user_search_key', true, 100, null);
        $this->addColumn('user_search_site_run_key', 'UserSearchSiteRunKey', 'VARCHAR', true, 100, null);
        $this->getColumn('user_search_site_run_key')->setPrimaryString(true);
        $this->addColumn('search_start_url', 'SearchStartUrl', 'VARCHAR', false, 1024, null);
        $this->addColumn('run_result_code', 'RunResultCode', 'ENUM', false, null, 'not-run');
        $this->getColumn('run_result_code')->setValueSet(array (
  0 => 'not-run',
  1 => 'failed',
  2 => 'excluded',
  3 => 'skipped',
  4 => 'successful',
));
        $this->addColumn('run_error_details', 'RunErrorDetails', 'ARRAY', false, null, null);
        $this->addColumn('date_started', 'StartedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_ended', 'EndedAt', 'TIMESTAMP', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('JobSiteFromUSSR', '\\JobScooper\\DataAccess\\JobSiteRecord', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':jobsite_key',
    1 => ':jobsite_key',
  ),
), 'CASCADE', null, null, false);
        $this->addRelation('UserSearchFromUSSR', '\\JobScooper\\DataAccess\\UserSearch', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_id',
    1 => ':user_id',
  ),
  1 =>
  array (
    0 => ':user_keyword_set_key',
    1 => ':user_keyword_set_key',
  ),
  2 =>
  array (
    0 => ':geolocation_id',
    1 => ':geolocation_id',
  ),
  3 =>
  array (
    0 => ':user_search_key',
    1 => ':user_search_key',
  ),
), 'CASCADE', 'CASCADE', null, false);
        $this->addRelation('UserFromUSSR', '\\JobScooper\\DataAccess\\User', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_id',
    1 => ':user_id',
  ),
), 'CASCADE', null, null, false);
        $this->addRelation('GeoLocationFromUSSR', '\\JobScooper\\DataAccess\\GeoLocation', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':geolocation_id',
    1 => ':geolocation_id',
  ),
), 'CASCADE', 'CASCADE', null, false);
    } // buildRelations()

    /**
     *
     * Gets the list of behaviors registered for this table
     *
     * @return array Associative array (name => parameters) of behaviors
     */
    public function getBehaviors()
    {
        return array(
            'sluggable' => array('slug_column' => 'user_search_site_run_key', 'slug_pattern' => 'search{UserSearchKey}_{JobSiteKey}_{AppRunId}', 'replace_pattern' => '/[^\w\/]+/u', 'replacement' => '', 'separator' => '-', 'permanent' => 'true', 'scope_column' => '', 'unique_constraint' => 'true', ),
        );
    } // getBehaviors()

    /**
     * Adds an object to the instance pool.
     *
     * Propel keeps cached copies of objects in an instance pool when they are retrieved
     * from the database. In some cases you may need to explicitly add objects
     * to the cache in order to ensure that the same objects are always returned by find*()
     * and findPk*() calls.
     *
     * @param \JobScooper\DataAccess\UserSearchSiteRun $obj A \JobScooper\DataAccess\UserSearchSiteRun object.
     * @param string $key             (optional) key to use for instance map (for performance boost if key was already calculated externally).
     */
    public static function addInstanceToPool($obj, $key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (null === $key) {
                $key = serialize([(null === $obj->getUserKeywordSetKey() || is_scalar($obj->getUserKeywordSetKey()) || is_callable([$obj->getUserKeywordSetKey(), '__toString']) ? (string) $obj->getUserKeywordSetKey() : $obj->getUserKeywordSetKey()), (null === $obj->getGeoLocationId() || is_scalar($obj->getGeoLocationId()) || is_callable([$obj->getGeoLocationId(), '__toString']) ? (string) $obj->getGeoLocationId() : $obj->getGeoLocationId()), (null === $obj->getJobSiteKey() || is_scalar($obj->getJobSiteKey()) || is_callable([$obj->getJobSiteKey(), '__toString']) ? (string) $obj->getJobSiteKey() : $obj->getJobSiteKey()), (null === $obj->getAppRunId() || is_scalar($obj->getAppRunId()) || is_callable([$obj->getAppRunId(), '__toString']) ? (string) $obj->getAppRunId() : $obj->getAppRunId())]);
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
     * @param mixed $value A \JobScooper\DataAccess\UserSearchSiteRun object or a primary key value.
     */
    public static function removeInstanceFromPool($value)
    {
        if (Propel::isInstancePoolingEnabled() && null !== $value) {
            if (is_object($value) && $value instanceof \JobScooper\DataAccess\UserSearchSiteRun) {
                $key = serialize([(null === $value->getUserKeywordSetKey() || is_scalar($value->getUserKeywordSetKey()) || is_callable([$value->getUserKeywordSetKey(), '__toString']) ? (string) $value->getUserKeywordSetKey() : $value->getUserKeywordSetKey()), (null === $value->getGeoLocationId() || is_scalar($value->getGeoLocationId()) || is_callable([$value->getGeoLocationId(), '__toString']) ? (string) $value->getGeoLocationId() : $value->getGeoLocationId()), (null === $value->getJobSiteKey() || is_scalar($value->getJobSiteKey()) || is_callable([$value->getJobSiteKey(), '__toString']) ? (string) $value->getJobSiteKey() : $value->getJobSiteKey()), (null === $value->getAppRunId() || is_scalar($value->getAppRunId()) || is_callable([$value->getAppRunId(), '__toString']) ? (string) $value->getAppRunId() : $value->getAppRunId())]);

            } elseif (is_array($value) && count($value) === 4) {
                // assume we've been passed a primary key";
                $key = serialize([(null === $value[0] || is_scalar($value[0]) || is_callable([$value[0], '__toString']) ? (string) $value[0] : $value[0]), (null === $value[1] || is_scalar($value[1]) || is_callable([$value[1], '__toString']) ? (string) $value[1] : $value[1]), (null === $value[2] || is_scalar($value[2]) || is_callable([$value[2], '__toString']) ? (string) $value[2] : $value[2]), (null === $value[3] || is_scalar($value[3]) || is_callable([$value[3], '__toString']) ? (string) $value[3] : $value[3])]);
            } elseif ($value instanceof Criteria) {
                self::$instances = [];

                return;
            } else {
                $e = new PropelException("Invalid value passed to removeInstanceFromPool().  Expected primary key or \JobScooper\DataAccess\UserSearchSiteRun object; got " . (is_object($value) ? get_class($value) . ' object.' : var_export($value, true)));
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('UserKeywordSetKey', TableMap::TYPE_PHPNAME, $indexType)] === null && $row[TableMap::TYPE_NUM == $indexType ? 2 + $offset : static::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)] === null && $row[TableMap::TYPE_NUM == $indexType ? 3 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] === null && $row[TableMap::TYPE_NUM == $indexType ? 4 + $offset : static::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return serialize([(null === $row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('UserKeywordSetKey', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('UserKeywordSetKey', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('UserKeywordSetKey', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('UserKeywordSetKey', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('UserKeywordSetKey', TableMap::TYPE_PHPNAME, $indexType)]), (null === $row[TableMap::TYPE_NUM == $indexType ? 2 + $offset : static::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 2 + $offset : static::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 2 + $offset : static::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 2 + $offset : static::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 2 + $offset : static::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)]), (null === $row[TableMap::TYPE_NUM == $indexType ? 3 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 3 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 3 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 3 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 3 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)]), (null === $row[TableMap::TYPE_NUM == $indexType ? 4 + $offset : static::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 4 + $offset : static::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 4 + $offset : static::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 4 + $offset : static::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 4 + $offset : static::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)])]);
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
                ? 1 + $offset
                : self::translateFieldName('UserKeywordSetKey', TableMap::TYPE_PHPNAME, $indexType)
        ];
        $pks[] = (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 2 + $offset
                : self::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)
        ];
        $pks[] = (string) $row[
            $indexType == TableMap::TYPE_NUM
                ? 3 + $offset
                : self::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)
        ];
        $pks[] = (string) $row[
            $indexType == TableMap::TYPE_NUM
                ? 4 + $offset
                : self::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? UserSearchSiteRunTableMap::CLASS_DEFAULT : UserSearchSiteRunTableMap::OM_CLASS;
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
     * @return array           (UserSearchSiteRun object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserSearchSiteRunTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserSearchSiteRunTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserSearchSiteRunTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserSearchSiteRunTableMap::OM_CLASS;
            /** @var UserSearchSiteRun $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserSearchSiteRunTableMap::addInstanceToPool($obj, $key);
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
            $key = UserSearchSiteRunTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserSearchSiteRunTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserSearchSiteRun $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserSearchSiteRunTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_USER_ID);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_USER_KEYWORD_SET_KEY);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_GEOLOCATION_ID);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_JOBSITE_KEY);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_APP_RUN_ID);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_USER_SEARCH_KEY);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_USER_SEARCH_SITE_RUN_KEY);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_SEARCH_START_URL);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_RUN_RESULT_CODE);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_RUN_ERROR_DETAILS);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_DATE_STARTED);
            $criteria->addSelectColumn(UserSearchSiteRunTableMap::COL_DATE_ENDED);
        } else {
            $criteria->addSelectColumn($alias . '.user_id');
            $criteria->addSelectColumn($alias . '.user_keyword_set_key');
            $criteria->addSelectColumn($alias . '.geolocation_id');
            $criteria->addSelectColumn($alias . '.jobsite_key');
            $criteria->addSelectColumn($alias . '.app_run_id');
            $criteria->addSelectColumn($alias . '.user_search_key');
            $criteria->addSelectColumn($alias . '.user_search_site_run_key');
            $criteria->addSelectColumn($alias . '.search_start_url');
            $criteria->addSelectColumn($alias . '.run_result_code');
            $criteria->addSelectColumn($alias . '.run_error_details');
            $criteria->addSelectColumn($alias . '.date_started');
            $criteria->addSelectColumn($alias . '.date_ended');
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
        return Propel::getServiceContainer()->getDatabaseMap(UserSearchSiteRunTableMap::DATABASE_NAME)->getTable(UserSearchSiteRunTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserSearchSiteRunTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserSearchSiteRunTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserSearchSiteRunTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserSearchSiteRun or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserSearchSiteRun object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchSiteRunTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\UserSearchSiteRun) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserSearchSiteRunTableMap::DATABASE_NAME);
            // primary key is composite; we therefore, expect
            // the primary key passed to be an array of pkey values
            if (count($values) == count($values, COUNT_RECURSIVE)) {
                // array is not multi-dimensional
                $values = array($values);
            }
            foreach ($values as $value) {
                $criterion = $criteria->getNewCriterion(UserSearchSiteRunTableMap::COL_USER_KEYWORD_SET_KEY, $value[0]);
                $criterion->addAnd($criteria->getNewCriterion(UserSearchSiteRunTableMap::COL_GEOLOCATION_ID, $value[1]));
                $criterion->addAnd($criteria->getNewCriterion(UserSearchSiteRunTableMap::COL_JOBSITE_KEY, $value[2]));
                $criterion->addAnd($criteria->getNewCriterion(UserSearchSiteRunTableMap::COL_APP_RUN_ID, $value[3]));
                $criteria->addOr($criterion);
            }
        }

        $query = UserSearchSiteRunQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserSearchSiteRunTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserSearchSiteRunTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_search_site_run table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserSearchSiteRunQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserSearchSiteRun or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserSearchSiteRun object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchSiteRunTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserSearchSiteRun object
        }


        // Set the correct dbName
        $query = UserSearchSiteRunQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserSearchSiteRunTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserSearchSiteRunTableMap::buildTableMap();
