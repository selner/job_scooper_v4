<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\UserSearch;
use JobScooper\DataAccess\UserSearchQuery;
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
 * This class defines the structure of the 'user_search' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class UserSearchTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.UserSearchTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_search';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\UserSearch';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.UserSearch';

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
    const COL_USER_SEARCH_ID = 'user_search.user_search_id';

    /**
     * the column name for the user_id field
     */
    const COL_USER_ID = 'user_search.user_id';

    /**
     * the column name for the geolocation_id field
     */
    const COL_GEOLOCATION_ID = 'user_search.geolocation_id';

    /**
     * the column name for the user_search_key field
     */
    const COL_USER_SEARCH_KEY = 'user_search.user_search_key';

    /**
     * the column name for the keywords field
     */
    const COL_KEYWORDS = 'user_search.keywords';

    /**
     * the column name for the keyword_tokens field
     */
    const COL_KEYWORD_TOKENS = 'user_search.keyword_tokens';

    /**
     * the column name for the search_key_from_config field
     */
    const COL_SEARCH_KEY_FROM_CONFIG = 'user_search.search_key_from_config';

    /**
     * the column name for the date_created field
     */
    const COL_DATE_CREATED = 'user_search.date_created';

    /**
     * the column name for the date_updated field
     */
    const COL_DATE_UPDATED = 'user_search.date_updated';

    /**
     * the column name for the date_last_completed field
     */
    const COL_DATE_LAST_COMPLETED = 'user_search.date_last_completed';

    /**
     * the column name for the version field
     */
    const COL_VERSION = 'user_search.version';

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
        self::TYPE_COLNAME       => array(UserSearchTableMap::COL_USER_SEARCH_ID, UserSearchTableMap::COL_USER_ID, UserSearchTableMap::COL_GEOLOCATION_ID, UserSearchTableMap::COL_USER_SEARCH_KEY, UserSearchTableMap::COL_KEYWORDS, UserSearchTableMap::COL_KEYWORD_TOKENS, UserSearchTableMap::COL_SEARCH_KEY_FROM_CONFIG, UserSearchTableMap::COL_DATE_CREATED, UserSearchTableMap::COL_DATE_UPDATED, UserSearchTableMap::COL_DATE_LAST_COMPLETED, UserSearchTableMap::COL_VERSION, ),
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
        self::TYPE_COLNAME       => array(UserSearchTableMap::COL_USER_SEARCH_ID => 0, UserSearchTableMap::COL_USER_ID => 1, UserSearchTableMap::COL_GEOLOCATION_ID => 2, UserSearchTableMap::COL_USER_SEARCH_KEY => 3, UserSearchTableMap::COL_KEYWORDS => 4, UserSearchTableMap::COL_KEYWORD_TOKENS => 5, UserSearchTableMap::COL_SEARCH_KEY_FROM_CONFIG => 6, UserSearchTableMap::COL_DATE_CREATED => 7, UserSearchTableMap::COL_DATE_UPDATED => 8, UserSearchTableMap::COL_DATE_LAST_COMPLETED => 9, UserSearchTableMap::COL_VERSION => 10, ),
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
        $this->setName('user_search');
        $this->setPhpName('UserSearch');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\UserSearch');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('user_search_id', 'UserSearchId', 'INTEGER', true, null, null);
        $this->addForeignKey('user_id', 'UserId', 'INTEGER', 'user', 'user_id', true, null, null);
        $this->addForeignKey('geolocation_id', 'GeoLocationId', 'INTEGER', 'geolocation', 'geolocation_id', false, null, null);
        $this->addColumn('user_search_key', 'UserSearchKey', 'VARCHAR', true, 128, null);
        $this->getColumn('user_search_key')->setPrimaryString(true);
        $this->addColumn('keywords', 'Keywords', 'ARRAY', false, null, null);
        $this->addColumn('keyword_tokens', 'KeywordTokens', 'ARRAY', false, null, null);
        $this->addColumn('search_key_from_config', 'SearchKeyFromConfig', 'VARCHAR', false, 50, null);
        $this->addColumn('date_created', 'CreatedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_updated', 'UpdatedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_last_completed', 'LastCompletedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('version', 'Version', 'INTEGER', false, null, 0);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('User', '\\JobScooper\\DataAccess\\User', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_id',
    1 => ':user_id',
  ),
), null, null, null, false);
        $this->addRelation('GeoLocation', '\\JobScooper\\DataAccess\\GeoLocation', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':geolocation_id',
    1 => ':geolocation_id',
  ),
), null, null, null, false);
        $this->addRelation('UserSearchRun', '\\JobScooper\\DataAccess\\UserSearchRun', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':user_search_id',
    1 => ':user_search_id',
  ),
), null, null, 'UserSearchRuns', false);
        $this->addRelation('UserSearchVersion', '\\JobScooper\\DataAccess\\UserSearchVersion', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':user_search_id',
    1 => ':user_search_id',
  ),
), 'CASCADE', null, 'UserSearchVersions', false);
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
            'sluggable' => array('slug_column' => 'user_search_key', 'slug_pattern' => '{UserId}_{SearchKeyFromConfig}_{GeoLocationId}', 'replace_pattern' => '/[^\w\/]+/u', 'replacement' => '', 'separator' => '_', 'permanent' => 'false', 'scope_column' => '', 'unique_constraint' => 'true', ),
            'timestampable' => array('create_column' => 'date_created', 'update_column' => 'date_updated', 'disable_created_at' => 'false', 'disable_updated_at' => 'false', ),
            'us_date_last_completed' => array('name' => 'date_last_completed', 'expression' => 'MAX(date_ended)', 'condition' => 'run_result_code = 4', 'foreign_table' => 'user_search_run', 'foreign_schema' => '', ),
            'versionable' => array('version_column' => 'version', 'version_table' => '', 'log_created_at' => 'false', 'log_created_by' => 'false', 'log_comment' => 'false', 'version_created_at_column' => 'version_created_at', 'version_created_by_column' => 'version_created_by', 'version_comment_column' => 'version_comment', 'indices' => 'false', ),
        );
    } // getBehaviors()
    /**
     * Method to invalidate the instance pool of all tables related to user_search     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        UserSearchVersionTableMap::clearInstancePool();
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
        return $withPrefix ? UserSearchTableMap::CLASS_DEFAULT : UserSearchTableMap::OM_CLASS;
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
     * @return array           (UserSearch object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserSearchTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserSearchTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserSearchTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserSearchTableMap::OM_CLASS;
            /** @var UserSearch $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserSearchTableMap::addInstanceToPool($obj, $key);
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
            $key = UserSearchTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserSearchTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserSearch $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserSearchTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserSearchTableMap::COL_USER_SEARCH_ID);
            $criteria->addSelectColumn(UserSearchTableMap::COL_USER_ID);
            $criteria->addSelectColumn(UserSearchTableMap::COL_GEOLOCATION_ID);
            $criteria->addSelectColumn(UserSearchTableMap::COL_USER_SEARCH_KEY);
            $criteria->addSelectColumn(UserSearchTableMap::COL_KEYWORDS);
            $criteria->addSelectColumn(UserSearchTableMap::COL_KEYWORD_TOKENS);
            $criteria->addSelectColumn(UserSearchTableMap::COL_SEARCH_KEY_FROM_CONFIG);
            $criteria->addSelectColumn(UserSearchTableMap::COL_DATE_CREATED);
            $criteria->addSelectColumn(UserSearchTableMap::COL_DATE_UPDATED);
            $criteria->addSelectColumn(UserSearchTableMap::COL_DATE_LAST_COMPLETED);
            $criteria->addSelectColumn(UserSearchTableMap::COL_VERSION);
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
        return Propel::getServiceContainer()->getDatabaseMap(UserSearchTableMap::DATABASE_NAME)->getTable(UserSearchTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserSearchTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserSearchTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserSearchTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserSearch or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserSearch object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\UserSearch) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserSearchTableMap::DATABASE_NAME);
            $criteria->add(UserSearchTableMap::COL_USER_SEARCH_ID, (array) $values, Criteria::IN);
        }

        $query = UserSearchQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserSearchTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserSearchTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_search table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserSearchQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserSearch or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserSearch object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserSearch object
        }

        if ($criteria->containsKey(UserSearchTableMap::COL_USER_SEARCH_ID) && $criteria->keyContainsValue(UserSearchTableMap::COL_USER_SEARCH_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.UserSearchTableMap::COL_USER_SEARCH_ID.')');
        }


        // Set the correct dbName
        $query = UserSearchQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserSearchTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserSearchTableMap::buildTableMap();
