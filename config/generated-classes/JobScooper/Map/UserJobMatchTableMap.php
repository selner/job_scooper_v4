<?php

namespace JobScooper\Map;

use JobScooper\UserJobMatch;
use JobScooper\UserJobMatchQuery;
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
 * This class defines the structure of the 'user_job_match' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class UserJobMatchTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.Map.UserJobMatchTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_job_match';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\UserJobMatch';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.UserJobMatch';

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
     * the column name for the user_slug field
     */
    const COL_USER_SLUG = 'user_job_match.user_slug';

    /**
     * the column name for the jobposting_id field
     */
    const COL_JOBPOSTING_ID = 'user_job_match.jobposting_id';

    /**
     * the column name for the user_notification_state field
     */
    const COL_USER_NOTIFICATION_STATE = 'user_job_match.user_notification_state';

    /**
     * the column name for the user_match_status field
     */
    const COL_USER_MATCH_STATUS = 'user_job_match.user_match_status';

    /**
     * the column name for the user_match_exclude_reason field
     */
    const COL_USER_MATCH_EXCLUDE_REASON = 'user_job_match.user_match_exclude_reason';

    /**
     * the column name for the app_run_id field
     */
    const COL_APP_RUN_ID = 'user_job_match.app_run_id';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /** The enumerated values for the user_notification_state field */
    const COL_USER_NOTIFICATION_STATE_NONE = 'none';
    const COL_USER_NOTIFICATION_STATE_READY_TO_SEND = 'ready-to-send';
    const COL_USER_NOTIFICATION_STATE_SENT = 'sent';

    /** The enumerated values for the user_match_status field */
    const COL_USER_MATCH_STATUS_NONE = 'none';
    const COL_USER_MATCH_STATUS_INCLUDE_MATCH = 'include-match';
    const COL_USER_MATCH_STATUS_EXCLUDE_MATCH = 'exclude-match';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('UserSlug', 'JobPostingId', 'UserNotificationState', 'UserMatchStatus', 'UserMatchExcludeReason', 'AppRunId', ),
        self::TYPE_CAMELNAME     => array('userSlug', 'jobPostingId', 'userNotificationState', 'userMatchStatus', 'userMatchExcludeReason', 'appRunId', ),
        self::TYPE_COLNAME       => array(UserJobMatchTableMap::COL_USER_SLUG, UserJobMatchTableMap::COL_JOBPOSTING_ID, UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE, UserJobMatchTableMap::COL_USER_MATCH_STATUS, UserJobMatchTableMap::COL_USER_MATCH_EXCLUDE_REASON, UserJobMatchTableMap::COL_APP_RUN_ID, ),
        self::TYPE_FIELDNAME     => array('user_slug', 'jobposting_id', 'user_notification_state', 'user_match_status', 'user_match_exclude_reason', 'app_run_id', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserSlug' => 0, 'JobPostingId' => 1, 'UserNotificationState' => 2, 'UserMatchStatus' => 3, 'UserMatchExcludeReason' => 4, 'AppRunId' => 5, ),
        self::TYPE_CAMELNAME     => array('userSlug' => 0, 'jobPostingId' => 1, 'userNotificationState' => 2, 'userMatchStatus' => 3, 'userMatchExcludeReason' => 4, 'appRunId' => 5, ),
        self::TYPE_COLNAME       => array(UserJobMatchTableMap::COL_USER_SLUG => 0, UserJobMatchTableMap::COL_JOBPOSTING_ID => 1, UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE => 2, UserJobMatchTableMap::COL_USER_MATCH_STATUS => 3, UserJobMatchTableMap::COL_USER_MATCH_EXCLUDE_REASON => 4, UserJobMatchTableMap::COL_APP_RUN_ID => 5, ),
        self::TYPE_FIELDNAME     => array('user_slug' => 0, 'jobposting_id' => 1, 'user_notification_state' => 2, 'user_match_status' => 3, 'user_match_exclude_reason' => 4, 'app_run_id' => 5, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /** The enumerated values for this table */
    protected static $enumValueSets = array(
                UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE => array(
                            self::COL_USER_NOTIFICATION_STATE_NONE,
            self::COL_USER_NOTIFICATION_STATE_READY_TO_SEND,
            self::COL_USER_NOTIFICATION_STATE_SENT,
        ),
                UserJobMatchTableMap::COL_USER_MATCH_STATUS => array(
                            self::COL_USER_MATCH_STATUS_NONE,
            self::COL_USER_MATCH_STATUS_INCLUDE_MATCH,
            self::COL_USER_MATCH_STATUS_EXCLUDE_MATCH,
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
        $this->setName('user_job_match');
        $this->setPhpName('UserJobMatch');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\UserJobMatch');
        $this->setPackage('JobScooper');
        $this->setUseIdGenerator(false);
        $this->setIsCrossRef(true);
        // columns
        $this->addForeignPrimaryKey('user_slug', 'UserSlug', 'VARCHAR' , 'user', 'user_slug', true, 128, null);
        $this->addForeignPrimaryKey('jobposting_id', 'JobPostingId', 'INTEGER' , 'jobposting', 'jobposting_id', true, null, null);
        $this->addColumn('user_notification_state', 'UserNotificationState', 'ENUM', false, null, null);
        $this->getColumn('user_notification_state')->setValueSet(array (
  0 => 'none',
  1 => 'ready-to-send',
  2 => 'sent',
));
        $this->addColumn('user_match_status', 'UserMatchStatus', 'ENUM', false, null, null);
        $this->getColumn('user_match_status')->setValueSet(array (
  0 => 'none',
  1 => 'include-match',
  2 => 'exclude-match',
));
        $this->addColumn('user_match_exclude_reason', 'UserMatchExcludeReason', 'VARCHAR', false, 1024, null);
        $this->addColumn('app_run_id', 'AppRunId', 'VARCHAR', false, 75, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('User', '\\JobScooper\\User', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_slug',
    1 => ':user_slug',
  ),
), null, null, null, false);
        $this->addRelation('JobPosting', '\\JobScooper\\JobPosting', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':jobposting_id',
    1 => ':jobposting_id',
  ),
), null, null, null, false);
    } // buildRelations()

    /**
     * Adds an object to the instance pool.
     *
     * Propel keeps cached copies of objects in an instance pool when they are retrieved
     * from the database. In some cases you may need to explicitly add objects
     * to the cache in order to ensure that the same objects are always returned by find*()
     * and findPk*() calls.
     *
     * @param \JobScooper\UserJobMatch $obj A \JobScooper\UserJobMatch object.
     * @param string $key             (optional) key to use for instance map (for performance boost if key was already calculated externally).
     */
    public static function addInstanceToPool($obj, $key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (null === $key) {
                $key = serialize([(null === $obj->getUserSlug() || is_scalar($obj->getUserSlug()) || is_callable([$obj->getUserSlug(), '__toString']) ? (string) $obj->getUserSlug() : $obj->getUserSlug()), (null === $obj->getJobPostingId() || is_scalar($obj->getJobPostingId()) || is_callable([$obj->getJobPostingId(), '__toString']) ? (string) $obj->getJobPostingId() : $obj->getJobPostingId())]);
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
     * @param mixed $value A \JobScooper\UserJobMatch object or a primary key value.
     */
    public static function removeInstanceFromPool($value)
    {
        if (Propel::isInstancePoolingEnabled() && null !== $value) {
            if (is_object($value) && $value instanceof \JobScooper\UserJobMatch) {
                $key = serialize([(null === $value->getUserSlug() || is_scalar($value->getUserSlug()) || is_callable([$value->getUserSlug(), '__toString']) ? (string) $value->getUserSlug() : $value->getUserSlug()), (null === $value->getJobPostingId() || is_scalar($value->getJobPostingId()) || is_callable([$value->getJobPostingId(), '__toString']) ? (string) $value->getJobPostingId() : $value->getJobPostingId())]);

            } elseif (is_array($value) && count($value) === 2) {
                // assume we've been passed a primary key";
                $key = serialize([(null === $value[0] || is_scalar($value[0]) || is_callable([$value[0], '__toString']) ? (string) $value[0] : $value[0]), (null === $value[1] || is_scalar($value[1]) || is_callable([$value[1], '__toString']) ? (string) $value[1] : $value[1])]);
            } elseif ($value instanceof Criteria) {
                self::$instances = [];

                return;
            } else {
                $e = new PropelException("Invalid value passed to removeInstanceFromPool().  Expected primary key or \JobScooper\UserJobMatch object; got " . (is_object($value) ? get_class($value) . ' object.' : var_export($value, true)));
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)] === null && $row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return serialize([(null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)]), (null === $row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 1 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)])]);
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
                : self::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)
        ];
        $pks[] = (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 1 + $offset
                : self::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? UserJobMatchTableMap::CLASS_DEFAULT : UserJobMatchTableMap::OM_CLASS;
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
     * @return array           (UserJobMatch object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserJobMatchTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserJobMatchTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserJobMatchTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserJobMatchTableMap::OM_CLASS;
            /** @var UserJobMatch $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserJobMatchTableMap::addInstanceToPool($obj, $key);
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
            $key = UserJobMatchTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserJobMatchTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserJobMatch $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserJobMatchTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_USER_SLUG);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_JOBPOSTING_ID);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_USER_MATCH_STATUS);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_USER_MATCH_EXCLUDE_REASON);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_APP_RUN_ID);
        } else {
            $criteria->addSelectColumn($alias . '.user_slug');
            $criteria->addSelectColumn($alias . '.jobposting_id');
            $criteria->addSelectColumn($alias . '.user_notification_state');
            $criteria->addSelectColumn($alias . '.user_match_status');
            $criteria->addSelectColumn($alias . '.user_match_exclude_reason');
            $criteria->addSelectColumn($alias . '.app_run_id');
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
        return Propel::getServiceContainer()->getDatabaseMap(UserJobMatchTableMap::DATABASE_NAME)->getTable(UserJobMatchTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserJobMatchTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserJobMatchTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserJobMatchTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserJobMatch or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserJobMatch object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\UserJobMatch) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserJobMatchTableMap::DATABASE_NAME);
            // primary key is composite; we therefore, expect
            // the primary key passed to be an array of pkey values
            if (count($values) == count($values, COUNT_RECURSIVE)) {
                // array is not multi-dimensional
                $values = array($values);
            }
            foreach ($values as $value) {
                $criterion = $criteria->getNewCriterion(UserJobMatchTableMap::COL_USER_SLUG, $value[0]);
                $criterion->addAnd($criteria->getNewCriterion(UserJobMatchTableMap::COL_JOBPOSTING_ID, $value[1]));
                $criteria->addOr($criterion);
            }
        }

        $query = UserJobMatchQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserJobMatchTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserJobMatchTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_job_match table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserJobMatchQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserJobMatch or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserJobMatch object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserJobMatch object
        }


        // Set the correct dbName
        $query = UserJobMatchQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserJobMatchTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserJobMatchTableMap::buildTableMap();
