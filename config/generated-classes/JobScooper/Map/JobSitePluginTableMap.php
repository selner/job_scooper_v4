<?php

namespace JobScooper\Map;

use JobScooper\JobSitePlugin;
use JobScooper\JobSitePluginQuery;
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
 * This class defines the structure of the 'jobsite_plugin' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class JobSitePluginTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.Map.JobSitePluginTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'jobsite_plugin';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\JobSitePlugin';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.JobSitePlugin';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 7;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 7;

    /**
     * the column name for the jobsite field
     */
    const COL_JOBSITE = 'jobsite_plugin.jobsite';

    /**
     * the column name for the display_name field
     */
    const COL_DISPLAY_NAME = 'jobsite_plugin.display_name';

    /**
     * the column name for the date_last_run field
     */
    const COL_DATE_LAST_RUN = 'jobsite_plugin.date_last_run';

    /**
     * the column name for the last_user_search_run_id field
     */
    const COL_LAST_USER_SEARCH_RUN_ID = 'jobsite_plugin.last_user_search_run_id';

    /**
     * the column name for the was_successful field
     */
    const COL_WAS_SUCCESSFUL = 'jobsite_plugin.was_successful';

    /**
     * the column name for the date_next_run field
     */
    const COL_DATE_NEXT_RUN = 'jobsite_plugin.date_next_run';

    /**
     * the column name for the date_last_failed field
     */
    const COL_DATE_LAST_FAILED = 'jobsite_plugin.date_last_failed';

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
        self::TYPE_PHPNAME       => array('JobSite', 'DisplayName', 'LastRunAt', 'LastUserSearchRunId', 'LastRunWasSuccessful', 'StartNextRunAfter', 'LastFailedAt', ),
        self::TYPE_CAMELNAME     => array('jobSite', 'displayName', 'lastRunAt', 'lastUserSearchRunId', 'lastRunWasSuccessful', 'startNextRunAfter', 'lastFailedAt', ),
        self::TYPE_COLNAME       => array(JobSitePluginTableMap::COL_JOBSITE, JobSitePluginTableMap::COL_DISPLAY_NAME, JobSitePluginTableMap::COL_DATE_LAST_RUN, JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID, JobSitePluginTableMap::COL_WAS_SUCCESSFUL, JobSitePluginTableMap::COL_DATE_NEXT_RUN, JobSitePluginTableMap::COL_DATE_LAST_FAILED, ),
        self::TYPE_FIELDNAME     => array('jobsite', 'display_name', 'date_last_run', 'last_user_search_run_id', 'was_successful', 'date_next_run', 'date_last_failed', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('JobSite' => 0, 'DisplayName' => 1, 'LastRunAt' => 2, 'LastUserSearchRunId' => 3, 'LastRunWasSuccessful' => 4, 'StartNextRunAfter' => 5, 'LastFailedAt' => 6, ),
        self::TYPE_CAMELNAME     => array('jobSite' => 0, 'displayName' => 1, 'lastRunAt' => 2, 'lastUserSearchRunId' => 3, 'lastRunWasSuccessful' => 4, 'startNextRunAfter' => 5, 'lastFailedAt' => 6, ),
        self::TYPE_COLNAME       => array(JobSitePluginTableMap::COL_JOBSITE => 0, JobSitePluginTableMap::COL_DISPLAY_NAME => 1, JobSitePluginTableMap::COL_DATE_LAST_RUN => 2, JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID => 3, JobSitePluginTableMap::COL_WAS_SUCCESSFUL => 4, JobSitePluginTableMap::COL_DATE_NEXT_RUN => 5, JobSitePluginTableMap::COL_DATE_LAST_FAILED => 6, ),
        self::TYPE_FIELDNAME     => array('jobsite' => 0, 'display_name' => 1, 'date_last_run' => 2, 'last_user_search_run_id' => 3, 'was_successful' => 4, 'date_next_run' => 5, 'date_last_failed' => 6, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, )
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
        $this->setName('jobsite_plugin');
        $this->setPhpName('JobSitePlugin');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\JobSitePlugin');
        $this->setPackage('JobScooper');
        $this->setUseIdGenerator(false);
        // columns
        $this->addPrimaryKey('jobsite', 'JobSite', 'VARCHAR', true, 100, null);
        $this->getColumn('jobsite')->setPrimaryString(true);
        $this->addColumn('display_name', 'DisplayName', 'VARCHAR', false, 255, null);
        $this->addColumn('date_last_run', 'LastRunAt', 'TIMESTAMP', false, null, null);
        $this->addForeignKey('last_user_search_run_id', 'LastUserSearchRunId', 'INTEGER', 'user_search_run', 'user_search_run_id', false, null, null);
        $this->addColumn('was_successful', 'LastRunWasSuccessful', 'BOOLEAN', false, null, null);
        $this->addColumn('date_next_run', 'StartNextRunAfter', 'TIMESTAMP', false, null, null);
        $this->addColumn('date_last_failed', 'LastFailedAt', 'TIMESTAMP', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('UserSearchRun', '\\JobScooper\\UserSearchRun', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':last_user_search_run_id',
    1 => ':user_search_run_id',
  ),
), null, null, null, false);
    } // buildRelations()

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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)];
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
        return (string) $row[
            $indexType == TableMap::TYPE_NUM
                ? 0 + $offset
                : self::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? JobSitePluginTableMap::CLASS_DEFAULT : JobSitePluginTableMap::OM_CLASS;
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
     * @return array           (JobSitePlugin object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = JobSitePluginTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = JobSitePluginTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + JobSitePluginTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = JobSitePluginTableMap::OM_CLASS;
            /** @var JobSitePlugin $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            JobSitePluginTableMap::addInstanceToPool($obj, $key);
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
            $key = JobSitePluginTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = JobSitePluginTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var JobSitePlugin $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                JobSitePluginTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_JOBSITE);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_DISPLAY_NAME);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_DATE_LAST_RUN);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_WAS_SUCCESSFUL);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_DATE_NEXT_RUN);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_DATE_LAST_FAILED);
        } else {
            $criteria->addSelectColumn($alias . '.jobsite');
            $criteria->addSelectColumn($alias . '.display_name');
            $criteria->addSelectColumn($alias . '.date_last_run');
            $criteria->addSelectColumn($alias . '.last_user_search_run_id');
            $criteria->addSelectColumn($alias . '.was_successful');
            $criteria->addSelectColumn($alias . '.date_next_run');
            $criteria->addSelectColumn($alias . '.date_last_failed');
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
        return Propel::getServiceContainer()->getDatabaseMap(JobSitePluginTableMap::DATABASE_NAME)->getTable(JobSitePluginTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(JobSitePluginTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(JobSitePluginTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new JobSitePluginTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a JobSitePlugin or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or JobSitePlugin object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\JobSitePlugin) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(JobSitePluginTableMap::DATABASE_NAME);
            $criteria->add(JobSitePluginTableMap::COL_JOBSITE, (array) $values, Criteria::IN);
        }

        $query = JobSitePluginQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            JobSitePluginTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                JobSitePluginTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the jobsite_plugin table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return JobSitePluginQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a JobSitePlugin or Criteria object.
     *
     * @param mixed               $criteria Criteria or JobSitePlugin object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from JobSitePlugin object
        }


        // Set the correct dbName
        $query = JobSitePluginQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // JobSitePluginTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
JobSitePluginTableMap::buildTableMap();