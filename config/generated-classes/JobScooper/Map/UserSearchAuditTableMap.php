<?php

namespace JobScooper\Map;

use JobScooper\UserSearchAudit;
use JobScooper\UserSearchAuditQuery;
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
 * This class defines the structure of the 'user_search_audit' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class UserSearchAuditTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.Map.UserSearchAuditTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_search_audit';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\UserSearchAudit';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.UserSearchAudit';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 9;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 9;

    /**
     * the column name for the user_search_run_id field
     */
    const COL_USER_SEARCH_RUN_ID = 'user_search_audit.user_search_run_id';

    /**
     * the column name for the key field
     */
    const COL_KEY = 'user_search_audit.key';

    /**
     * the column name for the app_run_id field
     */
    const COL_APP_RUN_ID = 'user_search_audit.app_run_id';

    /**
     * the column name for the user_slug field
     */
    const COL_USER_SLUG = 'user_search_audit.user_slug';

    /**
     * the column name for the date_search_run field
     */
    const COL_DATE_SEARCH_RUN = 'user_search_audit.date_search_run';

    /**
     * the column name for the jobsite field
     */
    const COL_JOBSITE = 'user_search_audit.jobsite';

    /**
     * the column name for the search_settings field
     */
    const COL_SEARCH_SETTINGS = 'user_search_audit.search_settings';

    /**
     * the column name for the search_run_result field
     */
    const COL_SEARCH_RUN_RESULT = 'user_search_audit.search_run_result';

    /**
     * the column name for the updated_at field
     */
    const COL_UPDATED_AT = 'user_search_audit.updated_at';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('UserSearchId', 'Key', 'AppRunId', 'UserSlug', 'DateSearchRun', 'JobSite', 'SearchSettings', 'SearchRunResult', 'UpdatedAt', ),
        self::TYPE_CAMELNAME     => array('userSearchId', 'key', 'appRunId', 'userSlug', 'dateSearchRun', 'jobSite', 'searchSettings', 'searchRunResult', 'updatedAt', ),
        self::TYPE_COLNAME       => array(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID, UserSearchAuditTableMap::COL_KEY, UserSearchAuditTableMap::COL_APP_RUN_ID, UserSearchAuditTableMap::COL_USER_SLUG, UserSearchAuditTableMap::COL_DATE_SEARCH_RUN, UserSearchAuditTableMap::COL_JOBSITE, UserSearchAuditTableMap::COL_SEARCH_SETTINGS, UserSearchAuditTableMap::COL_SEARCH_RUN_RESULT, UserSearchAuditTableMap::COL_UPDATED_AT, ),
        self::TYPE_FIELDNAME     => array('user_search_run_id', 'key', 'app_run_id', 'user_slug', 'date_search_run', 'jobsite', 'search_settings', 'search_run_result', 'updated_at', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserSearchId' => 0, 'Key' => 1, 'AppRunId' => 2, 'UserSlug' => 3, 'DateSearchRun' => 4, 'JobSite' => 5, 'SearchSettings' => 6, 'SearchRunResult' => 7, 'UpdatedAt' => 8, ),
        self::TYPE_CAMELNAME     => array('userSearchId' => 0, 'key' => 1, 'appRunId' => 2, 'userSlug' => 3, 'dateSearchRun' => 4, 'jobSite' => 5, 'searchSettings' => 6, 'searchRunResult' => 7, 'updatedAt' => 8, ),
        self::TYPE_COLNAME       => array(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID => 0, UserSearchAuditTableMap::COL_KEY => 1, UserSearchAuditTableMap::COL_APP_RUN_ID => 2, UserSearchAuditTableMap::COL_USER_SLUG => 3, UserSearchAuditTableMap::COL_DATE_SEARCH_RUN => 4, UserSearchAuditTableMap::COL_JOBSITE => 5, UserSearchAuditTableMap::COL_SEARCH_SETTINGS => 6, UserSearchAuditTableMap::COL_SEARCH_RUN_RESULT => 7, UserSearchAuditTableMap::COL_UPDATED_AT => 8, ),
        self::TYPE_FIELDNAME     => array('user_search_run_id' => 0, 'key' => 1, 'app_run_id' => 2, 'user_slug' => 3, 'date_search_run' => 4, 'jobsite' => 5, 'search_settings' => 6, 'search_run_result' => 7, 'updated_at' => 8, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, )
    );

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
        $this->setName('user_search_audit');
        $this->setPhpName('UserSearchAudit');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\UserSearchAudit');
        $this->setPackage('JobScooper');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('user_search_run_id', 'UserSearchId', 'INTEGER', true, null, null);
        $this->addColumn('key', 'Key', 'VARCHAR', false, 128, null);
        $this->addColumn('app_run_id', 'AppRunId', 'VARCHAR', false, 75, null);
        $this->addForeignKey('user_slug', 'UserSlug', 'VARCHAR', 'user', 'user_slug', false, 128, null);
        $this->addColumn('date_search_run', 'DateSearchRun', 'TIMESTAMP', false, null, null);
        $this->addColumn('jobsite', 'JobSite', 'VARCHAR', false, 255, null);
        $this->addColumn('search_settings', 'SearchSettings', 'ARRAY', false, null, null);
        $this->addColumn('search_run_result', 'SearchRunResult', 'OBJECT', false, null, null);
        $this->addColumn('updated_at', 'UpdatedAt', 'TIMESTAMP', false, null, null);
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
            'timestampable' => array('create_column' => 'date_search_run', 'update_column' => 'updated_at', 'disable_created_at' => 'false', 'disable_updated_at' => 'false', ),
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? UserSearchAuditTableMap::CLASS_DEFAULT : UserSearchAuditTableMap::OM_CLASS;
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
     * @return array           (UserSearchAudit object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserSearchAuditTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserSearchAuditTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserSearchAuditTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserSearchAuditTableMap::OM_CLASS;
            /** @var UserSearchAudit $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserSearchAuditTableMap::addInstanceToPool($obj, $key);
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
            $key = UserSearchAuditTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserSearchAuditTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserSearchAudit $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserSearchAuditTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID);
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_KEY);
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_APP_RUN_ID);
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_USER_SLUG);
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_DATE_SEARCH_RUN);
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_JOBSITE);
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_SEARCH_SETTINGS);
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_SEARCH_RUN_RESULT);
            $criteria->addSelectColumn(UserSearchAuditTableMap::COL_UPDATED_AT);
        } else {
            $criteria->addSelectColumn($alias . '.user_search_run_id');
            $criteria->addSelectColumn($alias . '.key');
            $criteria->addSelectColumn($alias . '.app_run_id');
            $criteria->addSelectColumn($alias . '.user_slug');
            $criteria->addSelectColumn($alias . '.date_search_run');
            $criteria->addSelectColumn($alias . '.jobsite');
            $criteria->addSelectColumn($alias . '.search_settings');
            $criteria->addSelectColumn($alias . '.search_run_result');
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
        return Propel::getServiceContainer()->getDatabaseMap(UserSearchAuditTableMap::DATABASE_NAME)->getTable(UserSearchAuditTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserSearchAuditTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserSearchAuditTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserSearchAuditTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserSearchAudit or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserSearchAudit object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchAuditTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\UserSearchAudit) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserSearchAuditTableMap::DATABASE_NAME);
            $criteria->add(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID, (array) $values, Criteria::IN);
        }

        $query = UserSearchAuditQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserSearchAuditTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserSearchAuditTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_search_audit table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserSearchAuditQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserSearchAudit or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserSearchAudit object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchAuditTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserSearchAudit object
        }

        if ($criteria->containsKey(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID) && $criteria->keyContainsValue(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID.')');
        }


        // Set the correct dbName
        $query = UserSearchAuditQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserSearchAuditTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserSearchAuditTableMap::buildTableMap();
