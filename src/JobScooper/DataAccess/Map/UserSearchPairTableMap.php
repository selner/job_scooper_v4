<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\UserSearchPair;
use JobScooper\DataAccess\UserSearchPairQuery;
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
 * This class defines the structure of the 'user_search_pair' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 */
class UserSearchPairTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.UserSearchPairTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_search_pair';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\UserSearchPair';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.UserSearchPair';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 5;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 5;

    /**
     * the column name for the user_search_pair_id field
     */
    const COL_USER_SEARCH_PAIR_ID = 'user_search_pair.user_search_pair_id';

    /**
     * the column name for the user_id field
     */
    const COL_USER_ID = 'user_search_pair.user_id';

    /**
     * the column name for the user_keyword field
     */
    const COL_USER_KEYWORD = 'user_search_pair.user_keyword';

    /**
     * the column name for the geolocation_id field
     */
    const COL_GEOLOCATION_ID = 'user_search_pair.geolocation_id';

    /**
     * the column name for the is_active field
     */
    const COL_IS_ACTIVE = 'user_search_pair.is_active';

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
        self::TYPE_PHPNAME       => array('UserSearchPairId', 'UserId', 'UserKeyword', 'GeoLocationId', 'IsActive', ),
        self::TYPE_CAMELNAME     => array('userSearchPairId', 'userId', 'userKeyword', 'geoLocationId', 'isActive', ),
        self::TYPE_COLNAME       => array(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, UserSearchPairTableMap::COL_USER_ID, UserSearchPairTableMap::COL_USER_KEYWORD, UserSearchPairTableMap::COL_GEOLOCATION_ID, UserSearchPairTableMap::COL_IS_ACTIVE, ),
        self::TYPE_FIELDNAME     => array('user_search_pair_id', 'user_id', 'user_keyword', 'geolocation_id', 'is_active', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserSearchPairId' => 0, 'UserId' => 1, 'UserKeyword' => 2, 'GeoLocationId' => 3, 'IsActive' => 4, ),
        self::TYPE_CAMELNAME     => array('userSearchPairId' => 0, 'userId' => 1, 'userKeyword' => 2, 'geoLocationId' => 3, 'isActive' => 4, ),
        self::TYPE_COLNAME       => array(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID => 0, UserSearchPairTableMap::COL_USER_ID => 1, UserSearchPairTableMap::COL_USER_KEYWORD => 2, UserSearchPairTableMap::COL_GEOLOCATION_ID => 3, UserSearchPairTableMap::COL_IS_ACTIVE => 4, ),
        self::TYPE_FIELDNAME     => array('user_search_pair_id' => 0, 'user_id' => 1, 'user_keyword' => 2, 'geolocation_id' => 3, 'is_active' => 4, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
    );

    /**
     * Holds a list of column names and their normalized version.
     *
     * @var string[]
     */
    protected $normalizedColumnNameMap = [
        'UserSearchPairId' => 'USER_SEARCH_PAIR_ID',
        'UserSearchPair.UserSearchPairId' => 'USER_SEARCH_PAIR_ID',
        'userSearchPairId' => 'USER_SEARCH_PAIR_ID',
        'userSearchPair.userSearchPairId' => 'USER_SEARCH_PAIR_ID',
        'UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID' => 'USER_SEARCH_PAIR_ID',
        'COL_USER_SEARCH_PAIR_ID' => 'USER_SEARCH_PAIR_ID',
        'user_search_pair_id' => 'USER_SEARCH_PAIR_ID',
        'user_search_pair.user_search_pair_id' => 'USER_SEARCH_PAIR_ID',
        'UserId' => 'USER_ID',
        'UserSearchPair.UserId' => 'USER_ID',
        'userId' => 'USER_ID',
        'userSearchPair.userId' => 'USER_ID',
        'UserSearchPairTableMap::COL_USER_ID' => 'USER_ID',
        'COL_USER_ID' => 'USER_ID',
        'user_id' => 'USER_ID',
        'user_search_pair.user_id' => 'USER_ID',
        'UserKeyword' => 'USER_KEYWORD',
        'UserSearchPair.UserKeyword' => 'USER_KEYWORD',
        'userKeyword' => 'USER_KEYWORD',
        'userSearchPair.userKeyword' => 'USER_KEYWORD',
        'UserSearchPairTableMap::COL_USER_KEYWORD' => 'USER_KEYWORD',
        'COL_USER_KEYWORD' => 'USER_KEYWORD',
        'user_keyword' => 'USER_KEYWORD',
        'user_search_pair.user_keyword' => 'USER_KEYWORD',
        'GeoLocationId' => 'GEOLOCATION_ID',
        'UserSearchPair.GeoLocationId' => 'GEOLOCATION_ID',
        'geoLocationId' => 'GEOLOCATION_ID',
        'userSearchPair.geoLocationId' => 'GEOLOCATION_ID',
        'UserSearchPairTableMap::COL_GEOLOCATION_ID' => 'GEOLOCATION_ID',
        'COL_GEOLOCATION_ID' => 'GEOLOCATION_ID',
        'geolocation_id' => 'GEOLOCATION_ID',
        'user_search_pair.geolocation_id' => 'GEOLOCATION_ID',
        'IsActive' => 'IS_ACTIVE',
        'UserSearchPair.IsActive' => 'IS_ACTIVE',
        'isActive' => 'IS_ACTIVE',
        'userSearchPair.isActive' => 'IS_ACTIVE',
        'UserSearchPairTableMap::COL_IS_ACTIVE' => 'IS_ACTIVE',
        'COL_IS_ACTIVE' => 'IS_ACTIVE',
        'is_active' => 'IS_ACTIVE',
        'user_search_pair.is_active' => 'IS_ACTIVE',
    ];

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
        $this->setName('user_search_pair');
        $this->setPhpName('UserSearchPair');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\UserSearchPair');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(true);
        $this->setIsCrossRef(true);
        // columns
        $this->addPrimaryKey('user_search_pair_id', 'UserSearchPairId', 'INTEGER', true, null, null);
        $this->addForeignKey('user_id', 'UserId', 'INTEGER', 'user', 'user_id', true, null, null);
        $this->addColumn('user_keyword', 'UserKeyword', 'VARCHAR', true, 50, null);
        $this->addForeignKey('geolocation_id', 'GeoLocationId', 'INTEGER', 'geolocation', 'geolocation_id', true, null, null);
        $this->addColumn('is_active', 'IsActive', 'BOOLEAN', true, 1, true);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('UserFromUS', '\\JobScooper\\DataAccess\\User', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_id',
    1 => ':user_id',
  ),
), 'CASCADE', 'CASCADE', null, false);
        $this->addRelation('GeoLocationFromUS', '\\JobScooper\\DataAccess\\GeoLocation', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':geolocation_id',
    1 => ':geolocation_id',
  ),
), null, null, null, false);
        $this->addRelation('UserSearchSiteRun', '\\JobScooper\\DataAccess\\UserSearchSiteRun', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':user_search_pair_id',
    1 => ':user_search_pair_id',
  ),
), 'CASCADE', 'CASCADE', 'UserSearchSiteRuns', false);
    } // buildRelations()
    /**
     * Method to invalidate the instance pool of all tables related to user_search_pair     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        UserSearchSiteRunTableMap::clearInstancePool();
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchPairId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchPairId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchPairId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchPairId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchPairId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchPairId', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('UserSearchPairId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? UserSearchPairTableMap::CLASS_DEFAULT : UserSearchPairTableMap::OM_CLASS;
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
     * @return array           (UserSearchPair object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserSearchPairTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserSearchPairTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserSearchPairTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserSearchPairTableMap::OM_CLASS;
            /** @var UserSearchPair $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserSearchPairTableMap::addInstanceToPool($obj, $key);
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
            $key = UserSearchPairTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserSearchPairTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserSearchPair $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserSearchPairTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID);
            $criteria->addSelectColumn(UserSearchPairTableMap::COL_USER_ID);
            $criteria->addSelectColumn(UserSearchPairTableMap::COL_USER_KEYWORD);
            $criteria->addSelectColumn(UserSearchPairTableMap::COL_GEOLOCATION_ID);
            $criteria->addSelectColumn(UserSearchPairTableMap::COL_IS_ACTIVE);
        } else {
            $criteria->addSelectColumn($alias . '.user_search_pair_id');
            $criteria->addSelectColumn($alias . '.user_id');
            $criteria->addSelectColumn($alias . '.user_keyword');
            $criteria->addSelectColumn($alias . '.geolocation_id');
            $criteria->addSelectColumn($alias . '.is_active');
        }
    }

    /**
     * Remove all the columns needed to create a new object.
     *
     * Note: any columns that were marked with lazyLoad="true" in the
     * XML schema will not be removed as they are only loaded on demand.
     *
     * @param Criteria $criteria object containing the columns to remove.
     * @param string   $alias    optional table alias
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function removeSelectColumns(Criteria $criteria, $alias = null)
    {
        if (null === $alias) {
            $criteria->removeSelectColumn(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID);
            $criteria->removeSelectColumn(UserSearchPairTableMap::COL_USER_ID);
            $criteria->removeSelectColumn(UserSearchPairTableMap::COL_USER_KEYWORD);
            $criteria->removeSelectColumn(UserSearchPairTableMap::COL_GEOLOCATION_ID);
            $criteria->removeSelectColumn(UserSearchPairTableMap::COL_IS_ACTIVE);
        } else {
            $criteria->removeSelectColumn($alias . '.user_search_pair_id');
            $criteria->removeSelectColumn($alias . '.user_id');
            $criteria->removeSelectColumn($alias . '.user_keyword');
            $criteria->removeSelectColumn($alias . '.geolocation_id');
            $criteria->removeSelectColumn($alias . '.is_active');
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
        return Propel::getServiceContainer()->getDatabaseMap(UserSearchPairTableMap::DATABASE_NAME)->getTable(UserSearchPairTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserSearchPairTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserSearchPairTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserSearchPairTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserSearchPair or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserSearchPair object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchPairTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\UserSearchPair) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserSearchPairTableMap::DATABASE_NAME);
            $criteria->add(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, (array) $values, Criteria::IN);
        }

        $query = UserSearchPairQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserSearchPairTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserSearchPairTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_search_pair table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserSearchPairQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserSearchPair or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserSearchPair object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchPairTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserSearchPair object
        }

        if ($criteria->containsKey(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID) && $criteria->keyContainsValue(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID.')');
        }


        // Set the correct dbName
        $query = UserSearchPairQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserSearchPairTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserSearchPairTableMap::buildTableMap();
