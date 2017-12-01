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
    const NUM_COLUMNS = 10;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 10;

    /**
     * the column name for the user_search_run_id field
     */
    const COL_USER_SEARCH_RUN_ID = 'user_search_run.user_search_run_id';

    /**
     * the column name for the user_search_id field
     */
    const COL_USER_SEARCH_ID = 'user_search_run.user_search_id';

    /**
     * the column name for the app_run_id field
     */
    const COL_APP_RUN_ID = 'user_search_run.app_run_id';

    /**
     * the column name for the user_search_run_key field
     */
    const COL_USER_SEARCH_RUN_KEY = 'user_search_run.user_search_run_key';

    /**
     * the column name for the jobsite_key field
     */
    const COL_JOBSITE_KEY = 'user_search_run.jobsite_key';

    /**
     * the column name for the search_start_url field
     */
    const COL_SEARCH_START_URL = 'user_search_run.search_start_url';

    /**
     * the column name for the run_result_code field
     */
    const COL_RUN_RESULT_CODE = 'user_search_run.run_result_code';

    /**
     * the column name for the run_error_details field
     */
    const COL_RUN_ERROR_DETAILS = 'user_search_run.run_error_details';

    /**
     * the column name for the date_started field
     */
    const COL_DATE_STARTED = 'user_search_run.date_started';

    /**
     * the column name for the date_ended field
     */
    const COL_DATE_ENDED = 'user_search_run.date_ended';

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
        self::TYPE_PHPNAME       => array('UserSearchRunId', 'UserSearchId', 'AppRunId', 'UserSearchRunKey', 'JobSiteKey', 'SearchStartUrl', 'RunResultCode', 'RunErrorDetails', 'StartedAt', 'EndedAt', ),
        self::TYPE_CAMELNAME     => array('userSearchRunId', 'userSearchId', 'appRunId', 'userSearchRunKey', 'jobSiteKey', 'searchStartUrl', 'runResultCode', 'runErrorDetails', 'startedAt', 'endedAt', ),
        self::TYPE_COLNAME       => array(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, UserSearchRunTableMap::COL_USER_SEARCH_ID, UserSearchRunTableMap::COL_APP_RUN_ID, UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY, UserSearchRunTableMap::COL_JOBSITE_KEY, UserSearchRunTableMap::COL_SEARCH_START_URL, UserSearchRunTableMap::COL_RUN_RESULT_CODE, UserSearchRunTableMap::COL_RUN_ERROR_DETAILS, UserSearchRunTableMap::COL_DATE_STARTED, UserSearchRunTableMap::COL_DATE_ENDED, ),
        self::TYPE_FIELDNAME     => array('user_search_run_id', 'user_search_id', 'app_run_id', 'user_search_run_key', 'jobsite_key', 'search_start_url', 'run_result_code', 'run_error_details', 'date_started', 'date_ended', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserSearchRunId' => 0, 'UserSearchId' => 1, 'AppRunId' => 2, 'UserSearchRunKey' => 3, 'JobSiteKey' => 4, 'SearchStartUrl' => 5, 'RunResultCode' => 6, 'RunErrorDetails' => 7, 'StartedAt' => 8, 'EndedAt' => 9, ),
        self::TYPE_CAMELNAME     => array('userSearchRunId' => 0, 'userSearchId' => 1, 'appRunId' => 2, 'userSearchRunKey' => 3, 'jobSiteKey' => 4, 'searchStartUrl' => 5, 'runResultCode' => 6, 'runErrorDetails' => 7, 'startedAt' => 8, 'endedAt' => 9, ),
        self::TYPE_COLNAME       => array(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID => 0, UserSearchRunTableMap::COL_USER_SEARCH_ID => 1, UserSearchRunTableMap::COL_APP_RUN_ID => 2, UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY => 3, UserSearchRunTableMap::COL_JOBSITE_KEY => 4, UserSearchRunTableMap::COL_SEARCH_START_URL => 5, UserSearchRunTableMap::COL_RUN_RESULT_CODE => 6, UserSearchRunTableMap::COL_RUN_ERROR_DETAILS => 7, UserSearchRunTableMap::COL_DATE_STARTED => 8, UserSearchRunTableMap::COL_DATE_ENDED => 9, ),
        self::TYPE_FIELDNAME     => array('user_search_run_id' => 0, 'user_search_id' => 1, 'app_run_id' => 2, 'user_search_run_key' => 3, 'jobsite_key' => 4, 'search_start_url' => 5, 'run_result_code' => 6, 'run_error_details' => 7, 'date_started' => 8, 'date_ended' => 9, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, )
    );

    /** The enumerated values for this table */
    protected static $enumValueSets = array(
                UserSearchRunTableMap::COL_RUN_RESULT_CODE => array(
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
        $this->setName('user_search_run');
        $this->setPhpName('UserSearchRun');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\UserSearchRun');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('user_search_run_id', 'UserSearchRunId', 'INTEGER', true, null, null);
        $this->addForeignKey('user_search_id', 'UserSearchId', 'INTEGER', 'user_search', 'user_search_id', true, null, null);
        $this->addColumn('app_run_id', 'AppRunId', 'VARCHAR', false, 75, null);
        $this->addColumn('user_search_run_key', 'UserSearchRunKey', 'VARCHAR', true, 100, null);
        $this->addForeignKey('jobsite_key', 'JobSiteKey', 'VARCHAR', 'job_site', 'jobsite_key', true, 100, null);
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
        $this->addRelation('UserSearch', '\\JobScooper\\DataAccess\\UserSearch', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_search_id',
    1 => ':user_search_id',
  ),
), null, null, null, false);
        $this->addRelation('JobSiteRecordRelatedByJobSiteKey', '\\JobScooper\\DataAccess\\JobSiteRecord', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':jobsite_key',
    1 => ':jobsite_key',
  ),
), null, null, null, false);
        $this->addRelation('JobSiteRecordRelatedByLastUserSearchRunId', '\\JobScooper\\DataAccess\\JobSiteRecord', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':last_user_search_run_id',
    1 => ':user_search_run_id',
  ),
), null, null, 'JobSiteRecordsRelatedByLastUserSearchRunId', false);
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
            'sluggable' => array('slug_column' => 'user_search_run_key', 'slug_pattern' => '{UserSearchKey}_{JobSiteKey}_{AppRunId}', 'replace_pattern' => '/[^\w\/]+/u', 'replacement' => '', 'separator' => '-', 'permanent' => 'false', 'scope_column' => '', 'unique_constraint' => 'true', ),
            'delegate' => array('to' => 'user_search', ),
            'aggregate_column_relation_date_last_pulled' => array('foreign_table' => 'job_site', 'update_method' => 'updateLastPulledAt', 'aggregate_name' => 'LastPulledAt', ),
            'aggregate_column_relation_date_last_run' => array('foreign_table' => 'job_site', 'update_method' => 'updateLastRunAt', 'aggregate_name' => 'LastRunAt', ),
            'aggregate_column_relation_date_last_completed' => array('foreign_table' => 'job_site', 'update_method' => 'updateLastCompletedAt', 'aggregate_name' => 'LastCompletedAt', ),
            'aggregate_column_relation_date_last_failed' => array('foreign_table' => 'job_site', 'update_method' => 'updateLastFailedAt', 'aggregate_name' => 'LastFailedAt', ),
            'aggregate_column_relation_aggregate_column' => array('foreign_table' => 'job_site', 'update_method' => 'updateLastUserSearchRunId', 'aggregate_name' => 'LastUserSearchRunId', ),
            'aggregate_column_relation_us_date_last_completed' => array('foreign_table' => 'user_search', 'update_method' => 'updateLastCompletedAt', 'aggregate_name' => 'LastCompletedAt', ),
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
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_USER_SEARCH_ID);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_APP_RUN_ID);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_JOBSITE_KEY);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_SEARCH_START_URL);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_RUN_RESULT_CODE);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_DATE_STARTED);
            $criteria->addSelectColumn(UserSearchRunTableMap::COL_DATE_ENDED);
        } else {
            $criteria->addSelectColumn($alias . '.user_search_run_id');
            $criteria->addSelectColumn($alias . '.user_search_id');
            $criteria->addSelectColumn($alias . '.app_run_id');
            $criteria->addSelectColumn($alias . '.user_search_run_key');
            $criteria->addSelectColumn($alias . '.jobsite_key');
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
