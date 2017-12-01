<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\UserSearchVersion;
use JobScooper\DataAccess\UserSearchVersionQuery;
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
 * This class defines the structure of the 'user_search_version' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class UserSearchVersionTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.UserSearchVersionTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_search_version';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\UserSearchVersion';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.UserSearchVersion';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 11;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 11;

    /**
     * the column name for the user_search_id field
     */
    const COL_USER_SEARCH_ID = 'user_search_version.user_search_id';

    /**
     * the column name for the user_id field
     */
    const COL_USER_ID = 'user_search_version.user_id';

    /**
     * the column name for the geolocation_id field
     */
    const COL_GEOLOCATION_ID = 'user_search_version.geolocation_id';

    /**
     * the column name for the user_search_key field
     */
    const COL_USER_SEARCH_KEY = 'user_search_version.user_search_key';

    /**
     * the column name for the keywords field
     */
    const COL_KEYWORDS = 'user_search_version.keywords';

    /**
     * the column name for the keyword_tokens field
     */
    const COL_KEYWORD_TOKENS = 'user_search_version.keyword_tokens';

    /**
     * the column name for the search_key_from_config field
     */
    const COL_SEARCH_KEY_FROM_CONFIG = 'user_search_version.search_key_from_config';

    /**
     * the column name for the date_created field
     */
    const COL_DATE_CREATED = 'user_search_version.date_created';

    /**
     * the column name for the date_updated field
     */
    const COL_DATE_UPDATED = 'user_search_version.date_updated';

    /**
     * the column name for the date_last_completed field
     */
    const COL_DATE_LAST_COMPLETED = 'user_search_version.date_last_completed';

    /**
     * the column name for the version field
     */
    const COL_VERSION = 'user_search_version.version';

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
        self::TYPE_PHPNAME       => array('UserSearchId', 'UserId', 'GeoLocationId', 'UserSearchKey', 'Keywords', 'KeywordTokens', 'SearchKeyFromConfig', 'CreatedAt', 'UpdatedAt', 'LastCompletedAt', 'Version', ),
        self::TYPE_CAMELNAME     => array('userSearchId', 'userId', 'geoLocationId', 'userSearchKey', 'keywords', 'keywordTokens', 'searchKeyFromConfig', 'createdAt', 'updatedAt', 'lastCompletedAt', 'version', ),
        self::TYPE_COLNAME       => array(UserSearchVersionTableMap::COL_USER_SEARCH_ID, UserSearchVersionTableMap::COL_USER_ID, UserSearchVersionTableMap::COL_GEOLOCATION_ID, UserSearchVersionTableMap::COL_USER_SEARCH_KEY, UserSearchVersionTableMap::COL_KEYWORDS, UserSearchVersionTableMap::COL_KEYWORD_TOKENS, UserSearchVersionTableMap::COL_SEARCH_KEY_FROM_CONFIG, UserSearchVersionTableMap::COL_DATE_CREATED, UserSearchVersionTableMap::COL_DATE_UPDATED, UserSearchVersionTableMap::COL_DATE_LAST_COMPLETED, UserSearchVersionTableMap::COL_VERSION, ),
        self::TYPE_FIELDNAME     => array('user_search_id', 'user_id', 'geolocation_id', 'user_search_key', 'keywords', 'keyword_tokens', 'search_key_from_config', 'date_created', 'date_updated', 'date_last_completed', 'version', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserSearchId' => 0, 'UserId' => 1, 'GeoLocationId' => 2, 'UserSearchKey' => 3, 'Keywords' => 4, 'KeywordTokens' => 5, 'SearchKeyFromConfig' => 6, 'CreatedAt' => 7, 'UpdatedAt' => 8, 'LastCompletedAt' => 9, 'Version' => 10, ),
        self::TYPE_CAMELNAME     => array('userSearchId' => 0, 'userId' => 1, 'geoLocationId' => 2, 'userSearchKey' => 3, 'keywords' => 4, 'keywordTokens' => 5, 'searchKeyFromConfig' => 6, 'createdAt' => 7, 'updatedAt' => 8, 'lastCompletedAt' => 9, 'version' => 10, ),
        self::TYPE_COLNAME       => array(UserSearchVersionTableMap::COL_USER_SEARCH_ID => 0, UserSearchVersionTableMap::COL_USER_ID => 1, UserSearchVersionTableMap::COL_GEOLOCATION_ID => 2, UserSearchVersionTableMap::COL_USER_SEARCH_KEY => 3, UserSearchVersionTableMap::COL_KEYWORDS => 4, UserSearchVersionTableMap::COL_KEYWORD_TOKENS => 5, UserSearchVersionTableMap::COL_SEARCH_KEY_FROM_CONFIG => 6, UserSearchVersionTableMap::COL_DATE_CREATED => 7, UserSearchVersionTableMap::COL_DATE_UPDATED => 8, UserSearchVersionTableMap::COL_DATE_LAST_COMPLETED => 9, UserSearchVersionTableMap::COL_VERSION => 10, ),
        self::TYPE_FIELDNAME     => array('user_search_id' => 0, 'user_id' => 1, 'geolocation_id' => 2, 'user_search_key' => 3, 'keywords' => 4, 'keyword_tokens' => 5, 'search_key_from_config' => 6, 'date_created' => 7, 'date_updated' => 8, 'date_last_completed' => 9, 'version' => 10, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, )
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
        $this->setName('user_search_version');
        $this->setPhpName('UserSearchVersion');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\UserSearchVersion');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(false);
        // columns
        $this->addForeignPrimaryKey('user_search_id', 'UserSearchId', 'INTEGER' , 'user_search', 'user_search_id', true, null, null);
        $this->addColumn('user_id', 'UserId', 'INTEGER', true, null, null);
        $this->addColumn('geolocation_id', 'GeoLocationId', 'INTEGER', false, null, null);
        $this->addColumn('user_search_key', 'UserSearchKey', 'VARCHAR', true, 128, null);
        $this->getColumn('user_search_key')->setPrimaryString(true);
        $this->addColumn('keywords', 'Keywords', 'ARRAY', false, null, null);
        $this->addColumn('keyword_tokens', 'KeywordTokens', 'ARRAY', false, null, null);
        $this->addColumn('search_key_from_config', 'SearchKeyFromConfig', 'VARCHAR', false, 50, null);
        $this->addColumn('date_created', 'CreatedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_updated', 'UpdatedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_last_completed', 'LastCompletedAt', 'TIMESTAMP', false, null, null);
        $this->addPrimaryKey('version', 'Version', 'INTEGER', true, null, 0);
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
     * @param \JobScooper\DataAccess\UserSearchVersion $obj A \JobScooper\DataAccess\UserSearchVersion object.
     * @param string $key             (optional) key to use for instance map (for performance boost if key was already calculated externally).
     */
    public static function addInstanceToPool($obj, $key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (null === $key) {
                $key = serialize([(null === $obj->getUserSearchId() || is_scalar($obj->getUserSearchId()) || is_callable([$obj->getUserSearchId(), '__toString']) ? (string) $obj->getUserSearchId() : $obj->getUserSearchId()), (null === $obj->getVersion() || is_scalar($obj->getVersion()) || is_callable([$obj->getVersion(), '__toString']) ? (string) $obj->getVersion() : $obj->getVersion())]);
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
     * @param mixed $value A \JobScooper\DataAccess\UserSearchVersion object or a primary key value.
     */
    public static function removeInstanceFromPool($value)
    {
        if (Propel::isInstancePoolingEnabled() && null !== $value) {
            if (is_object($value) && $value instanceof \JobScooper\DataAccess\UserSearchVersion) {
                $key = serialize([(null === $value->getUserSearchId() || is_scalar($value->getUserSearchId()) || is_callable([$value->getUserSearchId(), '__toString']) ? (string) $value->getUserSearchId() : $value->getUserSearchId()), (null === $value->getVersion() || is_scalar($value->getVersion()) || is_callable([$value->getVersion(), '__toString']) ? (string) $value->getVersion() : $value->getVersion())]);

            } elseif (is_array($value) && count($value) === 2) {
                // assume we've been passed a primary key";
                $key = serialize([(null === $value[0] || is_scalar($value[0]) || is_callable([$value[0], '__toString']) ? (string) $value[0] : $value[0]), (null === $value[1] || is_scalar($value[1]) || is_callable([$value[1], '__toString']) ? (string) $value[1] : $value[1])]);
            } elseif ($value instanceof Criteria) {
                self::$instances = [];

                return;
            } else {
                $e = new PropelException("Invalid value passed to removeInstanceFromPool().  Expected primary key or \JobScooper\DataAccess\UserSearchVersion object; got " . (is_object($value) ? get_class($value) . ' object.' : var_export($value, true)));
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)] === null && $row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return serialize([(null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)]), (null === $row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 10 + $offset : static::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)])]);
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
                : self::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? UserSearchVersionTableMap::CLASS_DEFAULT : UserSearchVersionTableMap::OM_CLASS;
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
     * @return array           (UserSearchVersion object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserSearchVersionTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserSearchVersionTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserSearchVersionTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserSearchVersionTableMap::OM_CLASS;
            /** @var UserSearchVersion $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserSearchVersionTableMap::addInstanceToPool($obj, $key);
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
            $key = UserSearchVersionTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserSearchVersionTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserSearchVersion $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserSearchVersionTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_USER_SEARCH_ID);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_USER_ID);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_GEOLOCATION_ID);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_USER_SEARCH_KEY);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_KEYWORDS);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_KEYWORD_TOKENS);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_SEARCH_KEY_FROM_CONFIG);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_DATE_CREATED);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_DATE_UPDATED);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_DATE_LAST_COMPLETED);
            $criteria->addSelectColumn(UserSearchVersionTableMap::COL_VERSION);
        } else {
            $criteria->addSelectColumn($alias . '.user_search_id');
            $criteria->addSelectColumn($alias . '.user_id');
            $criteria->addSelectColumn($alias . '.geolocation_id');
            $criteria->addSelectColumn($alias . '.user_search_key');
            $criteria->addSelectColumn($alias . '.keywords');
            $criteria->addSelectColumn($alias . '.keyword_tokens');
            $criteria->addSelectColumn($alias . '.search_key_from_config');
            $criteria->addSelectColumn($alias . '.date_created');
            $criteria->addSelectColumn($alias . '.date_updated');
            $criteria->addSelectColumn($alias . '.date_last_completed');
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
        return Propel::getServiceContainer()->getDatabaseMap(UserSearchVersionTableMap::DATABASE_NAME)->getTable(UserSearchVersionTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserSearchVersionTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserSearchVersionTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserSearchVersionTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserSearchVersion or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserSearchVersion object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchVersionTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\UserSearchVersion) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserSearchVersionTableMap::DATABASE_NAME);
            // primary key is composite; we therefore, expect
            // the primary key passed to be an array of pkey values
            if (count($values) == count($values, COUNT_RECURSIVE)) {
                // array is not multi-dimensional
                $values = array($values);
            }
            foreach ($values as $value) {
                $criterion = $criteria->getNewCriterion(UserSearchVersionTableMap::COL_USER_SEARCH_ID, $value[0]);
                $criterion->addAnd($criteria->getNewCriterion(UserSearchVersionTableMap::COL_VERSION, $value[1]));
                $criteria->addOr($criterion);
            }
        }

        $query = UserSearchVersionQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserSearchVersionTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserSearchVersionTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_search_version table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserSearchVersionQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserSearchVersion or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserSearchVersion object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchVersionTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserSearchVersion object
        }


        // Set the correct dbName
        $query = UserSearchVersionQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserSearchVersionTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserSearchVersionTableMap::buildTableMap();
