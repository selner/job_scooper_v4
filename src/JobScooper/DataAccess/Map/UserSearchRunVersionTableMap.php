<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\UserSearchRunVersion;
use JobScooper\DataAccess\UserSearchRunVersionQuery;
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
 * This class defines the structure of the 'user_search_run_version' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class UserSearchRunVersionTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.UserSearchRunVersionTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_search_run_version';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\UserSearchRunVersion';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.UserSearchRunVersion';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 6;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 6;

    /**
     * the column name for the user_search_run_id field
     */
    const COL_USER_SEARCH_RUN_ID = 'user_search_run_version.user_search_run_id';

    /**
     * the column name for the search_parameters_data field
     */
    const COL_SEARCH_PARAMETERS_DATA = 'user_search_run_version.search_parameters_data';

    /**
     * the column name for the last_app_run_id field
     */
    const COL_LAST_APP_RUN_ID = 'user_search_run_version.last_app_run_id';

    /**
     * the column name for the run_result field
     */
    const COL_RUN_RESULT = 'user_search_run_version.run_result';

    /**
     * the column name for the run_error_details field
     */
    const COL_RUN_ERROR_DETAILS = 'user_search_run_version.run_error_details';

    /**
     * the column name for the version field
     */
    const COL_VERSION = 'user_search_run_version.version';

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
        self::TYPE_PHPNAME       => array('UserSearchRunId', 'SearchParametersData', 'AppRunId', 'RunResultCode', 'RunErrorDetails', 'Version', ),
        self::TYPE_CAMELNAME     => array('userSearchRunId', 'searchParametersData', 'appRunId', 'runResultCode', 'runErrorDetails', 'version', ),
        self::TYPE_COLNAME       => array(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, UserSearchRunVersionTableMap::COL_SEARCH_PARAMETERS_DATA, UserSearchRunVersionTableMap::COL_LAST_APP_RUN_ID, UserSearchRunVersionTableMap::COL_RUN_RESULT, UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS, UserSearchRunVersionTableMap::COL_VERSION, ),
        self::TYPE_FIELDNAME     => array('user_search_run_id', 'search_parameters_data', 'last_app_run_id', 'run_result', 'run_error_details', 'version', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserSearchRunId' => 0, 'SearchParametersData' => 1, 'AppRunId' => 2, 'RunResultCode' => 3, 'RunErrorDetails' => 4, 'Version' => 5, ),
        self::TYPE_CAMELNAME     => array('userSearchRunId' => 0, 'searchParametersData' => 1, 'appRunId' => 2, 'runResultCode' => 3, 'runErrorDetails' => 4, 'version' => 5, ),
        self::TYPE_COLNAME       => array(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID => 0, UserSearchRunVersionTableMap::COL_SEARCH_PARAMETERS_DATA => 1, UserSearchRunVersionTableMap::COL_LAST_APP_RUN_ID => 2, UserSearchRunVersionTableMap::COL_RUN_RESULT => 3, UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS => 4, UserSearchRunVersionTableMap::COL_VERSION => 5, ),
        self::TYPE_FIELDNAME     => array('user_search_run_id' => 0, 'search_parameters_data' => 1, 'last_app_run_id' => 2, 'run_result' => 3, 'run_error_details' => 4, 'version' => 5, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /** The enumerated values for this table */
    protected static $enumValueSets = array(
                UserSearchRunVersionTableMap::COL_RUN_RESULT => array(
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
        $this->setName('user_search_run_version');
        $this->setPhpName('UserSearchRunVersion');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\UserSearchRunVersion');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(false);
        // columns
        $this->addForeignPrimaryKey('user_search_run_id', 'UserSearchRunId', 'INTEGER' , 'user_search_run', 'user_search_run_id', true, null, null);
        $this->addColumn('search_parameters_data', 'SearchParametersData', 'LONGVARCHAR', false, null, null);
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
        $this->addPrimaryKey('version', 'Version', 'INTEGER', true, null, 0);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('UserSearchRun', '\\JobScooper\\DataAccess\\UserSearchRun', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_search_run_id',
    1 => ':user_search_run_id',
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
     * @param \JobScooper\DataAccess\UserSearchRunVersion $obj A \JobScooper\DataAccess\UserSearchRunVersion object.
     * @param string $key             (optional) key to use for instance map (for performance boost if key was already calculated externally).
     */
    public static function addInstanceToPool($obj, $key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (null === $key) {
                $key = serialize([(null === $obj->getUserSearchRunId() || is_scalar($obj->getUserSearchRunId()) || is_callable([$obj->getUserSearchRunId(), '__toString']) ? (string) $obj->getUserSearchRunId() : $obj->getUserSearchRunId()), (null === $obj->getVersion() || is_scalar($obj->getVersion()) || is_callable([$obj->getVersion(), '__toString']) ? (string) $obj->getVersion() : $obj->getVersion())]);
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
     * @param mixed $value A \JobScooper\DataAccess\UserSearchRunVersion object or a primary key value.
     */
    public static function removeInstanceFromPool($value)
    {
        if (Propel::isInstancePoolingEnabled() && null !== $value) {
            if (is_object($value) && $value instanceof \JobScooper\DataAccess\UserSearchRunVersion) {
                $key = serialize([(null === $value->getUserSearchRunId() || is_scalar($value->getUserSearchRunId()) || is_callable([$value->getUserSearchRunId(), '__toString']) ? (string) $value->getUserSearchRunId() : $value->getUserSearchRunId()), (null === $value->getVersion() || is_scalar($value->getVersion()) || is_callable([$value->getVersion(), '__toString']) ? (string) $value->getVersion() : $value->getVersion())]);

            } elseif (is_array($value) && count($value) === 2) {
                // assume we've been passed a primary key";
                $key = serialize([(null === $value[0] || is_scalar($value[0]) || is_callable([$value[0], '__toString']) ? (string) $value[0] : $value[0]), (null === $value[1] || is_scalar($value[1]) || is_callable([$value[1], '__toString']) ? (string) $value[1] : $value[1])]);
            } elseif ($value instanceof Criteria) {
                self::$instances = [];

                return;
            } else {
                $e = new PropelException("Invalid value passed to removeInstanceFromPool().  Expected primary key or \JobScooper\DataAccess\UserSearchRunVersion object; got " . (is_object($value) ? get_class($value) . ' object.' : var_export($value, true)));
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)] === null && $row[TableMap::TYPE_NUM == $indexType ? 5 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return serialize([(null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)]), (null === $row[TableMap::TYPE_NUM == $indexType ? 5 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 5 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 5 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 5 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 5 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)])]);
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

        $pks[] = (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 0 + $offset
                : self::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)
        ];
        $pks[] = (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 5 + $offset
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
        return $withPrefix ? UserSearchRunVersionTableMap::CLASS_DEFAULT : UserSearchRunVersionTableMap::OM_CLASS;
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
     * @return array           (UserSearchRunVersion object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserSearchRunVersionTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserSearchRunVersionTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserSearchRunVersionTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserSearchRunVersionTableMap::OM_CLASS;
            /** @var UserSearchRunVersion $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserSearchRunVersionTableMap::addInstanceToPool($obj, $key);
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
            $key = UserSearchRunVersionTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserSearchRunVersionTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserSearchRunVersion $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserSearchRunVersionTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID);
            $criteria->addSelectColumn(UserSearchRunVersionTableMap::COL_SEARCH_PARAMETERS_DATA);
            $criteria->addSelectColumn(UserSearchRunVersionTableMap::COL_LAST_APP_RUN_ID);
            $criteria->addSelectColumn(UserSearchRunVersionTableMap::COL_RUN_RESULT);
            $criteria->addSelectColumn(UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS);
            $criteria->addSelectColumn(UserSearchRunVersionTableMap::COL_VERSION);
        } else {
            $criteria->addSelectColumn($alias . '.user_search_run_id');
            $criteria->addSelectColumn($alias . '.search_parameters_data');
            $criteria->addSelectColumn($alias . '.last_app_run_id');
            $criteria->addSelectColumn($alias . '.run_result');
            $criteria->addSelectColumn($alias . '.run_error_details');
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
        return Propel::getServiceContainer()->getDatabaseMap(UserSearchRunVersionTableMap::DATABASE_NAME)->getTable(UserSearchRunVersionTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserSearchRunVersionTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserSearchRunVersionTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserSearchRunVersionTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserSearchRunVersion or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserSearchRunVersion object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunVersionTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\UserSearchRunVersion) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserSearchRunVersionTableMap::DATABASE_NAME);
            // primary key is composite; we therefore, expect
            // the primary key passed to be an array of pkey values
            if (count($values) == count($values, COUNT_RECURSIVE)) {
                // array is not multi-dimensional
                $values = array($values);
            }
            foreach ($values as $value) {
                $criterion = $criteria->getNewCriterion(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $value[0]);
                $criterion->addAnd($criteria->getNewCriterion(UserSearchRunVersionTableMap::COL_VERSION, $value[1]));
                $criteria->addOr($criterion);
            }
        }

        $query = UserSearchRunVersionQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserSearchRunVersionTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserSearchRunVersionTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_search_run_version table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserSearchRunVersionQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserSearchRunVersion or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserSearchRunVersion object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunVersionTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserSearchRunVersion object
        }


        // Set the correct dbName
        $query = UserSearchRunVersionQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserSearchRunVersionTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserSearchRunVersionTableMap::buildTableMap();
