<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\UserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery;
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
 * This class defines the structure of the 'user_search_run' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class UserSearchRunTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.UserSearchRunTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_search_run';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\UserSearchRun';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.UserSearchRun';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 15;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 15;

    /**
     * the column name for the user_search_run_id field
     */
    const COL_USER_SEARCH_RUN_ID = 'user_search_run.user_search_run_id';

    /**
     * the column name for the search_key field
     */
    const COL_SEARCH_KEY = 'user_search_run.search_key';

    /**
     * the column name for the user_slug field
     */
    const COL_USER_SLUG = 'user_search_run.user_slug';

    /**
     * the column name for the jobsite_key field
     */
    const COL_JOBSITE_KEY = 'user_search_run.jobsite_key';

    /**
     * the column name for the location_key field
     */
    const COL_LOCATION_KEY = 'user_search_run.location_key';

    /**
     * the column name for the location_id field
     */
    const COL_LOCATION_ID = 'user_search_run.location_id';

    /**
     * the column name for the user_search_run_key field
     */
    const COL_USER_SEARCH_RUN_KEY = 'user_search_run.user_search_run_key';

    /**
     * the column name for the search_settings field
     */
    const COL_SEARCH_SETTINGS = 'user_search_run.search_settings';

    /**
     * the column name for the last_app_run_id field
     */
    const COL_LAST_APP_RUN_ID = 'user_search_run.last_app_run_id';

    /**
     * the column name for the run_result field
     */
    const COL_RUN_RESULT = 'user_search_run.run_result';

    /**
     * the column name for the run_error_details field
     */
    const COL_RUN_ERROR_DETAILS = 'user_search_run.run_error_details';

    /**
     * the column name for the date_last_run field
     */
    const COL_DATE_LAST_RUN = 'user_search_run.date_last_run';

    /**
     * the column name for the date_next_run field
     */
    const COL_DATE_NEXT_RUN = 'user_search_run.date_next_run';

    /**
     * the column name for the date_last_failed field
     */
    const COL_DATE_LAST_FAILED = 'user_search_run.date_last_failed';

    /**
     * the column name for the updated_at field
     */
    const COL_UPDATED_AT = 'user_search_run.updated_at';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /** The enumerated values for the run_result field */
    const COL_RUN_RESULT_NOT_RUN = 'not-run';
    const COL_RUN_RESULT_FAILED = 'failed';
    const COL_RUN_RESULT_EXCLUDED = 'excluded';
    const COL_RUN_RESULT_SKIPPED = 'skipped';
    const COL_RUN_RESULT_SUCCESSFUL = 'successful';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('UserSearchRunId', 'SearchKey', 'UserSlug', 'JobSiteKey', 'LocationKey', 'LocationId', 'UserSearchRunKey', 'SearchSettings', 'AppRunId', 'RunResultCode', 'RunErrorDetails', 'LastRunAt', 'StartNextRunAfter', 'LastFailedAt', 'UpdatedAt', ),
        self::TYPE_CAMELNAME     => array('userSearchRunId', 'searchKey', 'userSlug', 'jobSiteKey', 'locationKey', 'locationId', 'userSearchRunKey', 'searchSettings', 'appRunId', 'runResultCode', 'runErrorDetails', 'lastRunAt', 'startNextRunAfter', 'lastFailedAt', 'updatedAt', ),
        self::TYPE_COLNAME       => array(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, UserSearchRunTableMap::COL_SEARCH_KEY, UserSearchRunTableMap::COL_USER_SLUG, UserSearchRunTableMap::COL_JOBSITE_KEY, UserSearchRunTableMap::COL_LOCATION_KEY, UserSearchRunTableMap::COL_LOCATION_ID, UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY, UserSearchRunTableMap::COL_SEARCH_SETTINGS, UserSearchRunTableMap::COL_LAST_APP_RUN_ID, UserSearchRunTableMap::COL_RUN_RESULT, UserSearchRunTableMap::COL_RUN_ERROR_DETAILS, UserSearchRunTableMap::COL_DATE_LAST_RUN, UserSearchRunTableMap::COL_DATE_NEXT_RUN, UserSearchRunTableMap::COL_DATE_LAST_FAILED, UserSearchRunTableMap::COL_UPDATED_AT, ),
        self::TYPE_FIELDNAME     => array('user_search_run_id', 'search_key', 'user_slug', 'jobsite_key', 'location_key', 'location_id', 'user_search_run_key', 'search_settings', 'last_app_run_id', 'run_result', 'run_error_details', 'date_last_run', 'date_next_run', 'date_last_failed', 'updated_at', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserSearchRunId' => 0, 'SearchKey' => 1, 'UserSlug' => 2, 'JobSiteKey' => 3, 'LocationKey' => 4, 'LocationId' => 5, 'UserSearchRunKey' => 6, 'SearchSettings' => 7, 'AppRunId' => 8, 'RunResultCode' => 9, 'RunErrorDetails' => 10, 'LastRunAt' => 11, 'StartNextRunAfter' => 12, 'LastFailedAt' => 13, 'UpdatedAt' => 14, ),
        self::TYPE_CAMELNAME     => array('userSearchRunId' => 0, 'searchKey' => 1, 'userSlug' => 2, 'jobSiteKey' => 3, 'locationKey' => 4, 'locationId' => 5, 'userSearchRunKey' => 6, 'searchSettings' => 7, 'appRunId' => 8, 'runResultCode' => 9, 'runErrorDetails' => 10, 'lastRunAt' => 11, 'startNextRunAfter' => 12, 'lastFailedAt' => 13, 'updatedAt' => 14, ),
        self::TYPE_COLNAME       => array(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID => 0, UserSearchRunTableMap::COL_SEARCH_KEY => 1, UserSearchRunTableMap::COL_USER_SLUG => 2, UserSearchRunTableMap::COL_JOBSITE_KEY => 3, UserSearchRunTableMap::COL_LOCATION_KEY => 4, UserSearchRunTableMap::COL_LOCATION_ID => 5, UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY => 6, UserSearchRunTableMap::COL_SEARCH_SETTINGS => 7, UserSearchRunTableMap::COL_LAST_APP_RUN_ID => 8, UserSearchRunTableMap::COL_RUN_RESULT => 9, UserSearchRunTableMap::COL_RUN_ERROR_DETAILS => 10, UserSearchRunTableMap::COL_DATE_LAST_RUN => 11, UserSearchRunTableMap::COL_DATE_NEXT_RUN => 12, UserSearchRunTableMap::COL_DATE_LAST_FAILED => 13, UserSearchRunTableMap::COL_UPDATED_AT => 14, ),
        self::TYPE_FIELDNAME     => array('user_search_run_id' => 0, 'search_key' => 1, 'user_slug' => 2, 'jobsite_key' => 3, 'location_key' => 4, 'location_id' => 5, 'user_search_run_key' => 6, 'search_settings' => 7, 'last_app_run_id' => 8, 'run_result' => 9, 'run_error_details' => 10, 'date_last_run' => 11, 'date_next_run' => 12, 'date_last_failed' => 13, 'updated_at' => 14, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, )
    );

    /** The enumerated values for this table */
    protected static $enumValueSets = array(
                UserSearchRunTableMap::COL_RUN_RESULT => array(
                            self::COL_RUN_RESULT_NOT_RUN,
            self::COL_RUN_RESULT_FAILED,
            self::COL_RUN_RESULT_EXCLUDED,
            self::COL_RUN_RESULT_SKIPPED,
            self::COL_RUN_RESULT_SUCCESSFUL,
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
        $this->setName('user_search_run');
        $this->setPhpName('UserSearchRun');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\UserSearchRun');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('user_search_run_id', 'UserSearchRunId', 'INTEGER', true, null, null);
        $this->addColumn('search_key', 'SearchKey', 'VARCHAR', true, 128, null);
        $this->addForeignKey('user_slug', 'UserSlug', 'VARCHAR', 'user', 'user_slug', true, 128, null);
        $this->addColumn('jobsite_key', 'JobSiteKey', 'VARCHAR', true, 100, null);
        $this->addColumn('location_key', 'LocationKey', 'VARCHAR', true, 100, null);
        $this->addForeignKey('location_id', 'LocationId', 'INTEGER', 'location', 'location_id', false, null, null);
        $this->addColumn('user_search_run_key', 'UserSearchRunKey', 'VARCHAR', true, 100, null);
        $this->addColumn('search_settings', 'SearchSettings', 'OBJECT', false, null, null);
        $this->addColumn('last_app_run_id', 'AppRunId', 'VARCHAR', false, 75, null);
        $this->addColumn('run_result', 'RunResultCode', 'ENUM', false, null, 'not-run');
        $this->getColumn('run_result')->setValueSet(array (
  0 => 'not-run',
  1 => 'failed',
  2 => 'excluded',
  3 => 'skipped',
  4 => 'successful',
));
        $this->addColumn('run_error_details', 'RunErrorDetails', 'ARRAY', false, null, null);
        $this->addColumn('date_last_run', 'LastRunAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_next_run', 'StartNextRunAfter', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_last_failed', 'LastFailedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('updated_at', 'UpdatedAt', 'TIMESTAMP', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('Location', '\\JobScooper\\DataAccess\\Location', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':location_id',
    1 => ':location_id',
  ),
), null, null, null, false);
        $this->addRelation('User', '\\JobScooper\\DataAccess\\User', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_slug',
    1 => ':user_slug',
  ),
), null, null, null, false);
        $this->addRelation('JobSitePlugin', '\\JobScooper\\DataAccess\\JobSitePlugin', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':last_user_search_run_id',
    1 => ':user_search_run_id',
  ),
), null, null, 'JobSitePlugins', false);
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
            'sluggable' => array('slug_column' => 'user_search_run_key', 'slug_pattern' => '{JobSiteKey}-{UserSlug}-{SearchKey}-{LocationKey}', 'replace_pattern' => '/[^\w\/]+/u', 'replacement' => '', 'separator' => '-', 'permanent' => 'true', 'scope_column' => '', 'unique_constraint' => 'true', ),
            'timestampable' => array('create_column' => 'date_last_run', 'update_column' => 'updated_at', 'disable_created_at' => 'false', 'disable_updated_at' => 'false', ),
        );
    } // getBehaviors()

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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)];
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
        return (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 0 + $offset
                : self::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)
        ];
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
        return $withPrefix ? UserSearchRunTableMap::CLASS_DEFAULT : UserSearchRunTableMap::OM_CLASS;
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
     * @return array           (UserSearchRun object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserSearchRunTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserSearchRunTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserSearchRunTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserSearchRunTableMap::OM_CLASS;
            /** @var UserSearchRun $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserSearchRunTableMap::addInstanceToPool($obj, $key);
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
            $key = UserSearchRunTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserSearchRunTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserSearchRun $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserSearchRunTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_SEARCH_KEY);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_USER_SLUG);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_JOBSITE_KEY);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_LOCATION_KEY);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_LOCATION_ID);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_SEARCH_SETTINGS);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_LAST_APP_RUN_ID);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_RUN_RESULT);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_DATE_LAST_RUN);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_DATE_NEXT_RUN);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_DATE_LAST_FAILED);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_UPDATED_AT);
        } else {
            $criteria->addSelectColumn($alias . '.user_search_run_id');
            $criteria->addSelectColumn($alias . '.search_key');
            $criteria->addSelectColumn($alias . '.user_slug');
            $criteria->addSelectColumn($alias . '.jobsite_key');
            $criteria->addSelectColumn($alias . '.location_key');
            $criteria->addSelectColumn($alias . '.location_id');
            $criteria->addSelectColumn($alias . '.user_search_run_key');
            $criteria->addSelectColumn($alias . '.search_settings');
            $criteria->addSelectColumn($alias . '.last_app_run_id');
            $criteria->addSelectColumn($alias . '.run_result');
            $criteria->addSelectColumn($alias . '.run_error_details');
            $criteria->addSelectColumn($alias . '.date_last_run');
            $criteria->addSelectColumn($alias . '.date_next_run');
            $criteria->addSelectColumn($alias . '.date_last_failed');
            $criteria->addSelectColumn($alias . '.updated_at');
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
        return Propel::getServiceContainer()->getDatabaseMap(UserSearchRunTableMap::DATABASE_NAME)->getTable(UserSearchRunTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserSearchRunTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserSearchRunTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserSearchRunTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserSearchRun or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserSearchRun object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\UserSearchRun) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserSearchRunTableMap::DATABASE_NAME);
            $criteria->add(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, (array) $values, Criteria::IN);
        }

        $query = UserSearchRunQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserSearchRunTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserSearchRunTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_search_run table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserSearchRunQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserSearchRun or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserSearchRun object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserSearchRun object
        }

        if ($criteria->containsKey(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID) && $criteria->keyContainsValue(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID.')');
        }


        // Set the correct dbName
        $query = UserSearchRunQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserSearchRunTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserSearchRunTableMap::buildTableMap();
