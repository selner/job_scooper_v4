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
    const NUM_COLUMNS = 4;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 4;

    /**
     * the column name for the jobsite_key field
     */
    const COL_JOBSITE_KEY = 'jobsite_plugin.jobsite_key';

    /**
     * the column name for the plugin_class_name field
     */
    const COL_PLUGIN_CLASS_NAME = 'jobsite_plugin.plugin_class_name';

    /**
     * the column name for the supported_country_codes field
     */
    const COL_SUPPORTED_COUNTRY_CODES = 'jobsite_plugin.supported_country_codes';

    /**
     * the column name for the results_filter_type field
     */
    const COL_RESULTS_FILTER_TYPE = 'jobsite_plugin.results_filter_type';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /** The enumerated values for the results_filter_type field */
    const COL_RESULTS_FILTER_TYPE_ALL_ONLY = 'all-only';
    const COL_RESULTS_FILTER_TYPE_ALL_BY_LOCATION = 'all-by-location';
    const COL_RESULTS_FILTER_TYPE_USER_FILTERED = 'user-filtered';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('JobSiteKey', 'PluginClassName', 'SupportedCountryCodes', 'ResultsFilterType', ),
        self::TYPE_CAMELNAME     => array('jobSiteKey', 'pluginClassName', 'supportedCountryCodes', 'resultsFilterType', ),
        self::TYPE_COLNAME       => array(JobSitePluginTableMap::COL_JOBSITE_KEY, JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME, JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES, JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE, ),
        self::TYPE_FIELDNAME     => array('jobsite_key', 'plugin_class_name', 'supported_country_codes', 'results_filter_type', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('JobSiteKey' => 0, 'PluginClassName' => 1, 'SupportedCountryCodes' => 2, 'ResultsFilterType' => 3, ),
        self::TYPE_CAMELNAME     => array('jobSiteKey' => 0, 'pluginClassName' => 1, 'supportedCountryCodes' => 2, 'resultsFilterType' => 3, ),
        self::TYPE_COLNAME       => array(JobSitePluginTableMap::COL_JOBSITE_KEY => 0, JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME => 1, JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES => 2, JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE => 3, ),
        self::TYPE_FIELDNAME     => array('jobsite_key' => 0, 'plugin_class_name' => 1, 'supported_country_codes' => 2, 'results_filter_type' => 3, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, )
    );

    /** The enumerated values for this table */
    protected static $enumValueSets = array(
                JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE => array(
                            self::COL_RESULTS_FILTER_TYPE_ALL_ONLY,
            self::COL_RESULTS_FILTER_TYPE_ALL_BY_LOCATION,
            self::COL_RESULTS_FILTER_TYPE_USER_FILTERED,
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
        $this->setName('jobsite_plugin');
        $this->setPhpName('JobSitePlugin');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\JobSitePlugin');
        $this->setPackage('JobScooper');
        $this->setUseIdGenerator(false);
        // columns
        $this->addPrimaryKey('jobsite_key', 'JobSiteKey', 'VARCHAR', true, 100, null);
        $this->getColumn('jobsite_key')->setPrimaryString(true);
        $this->addColumn('plugin_class_name', 'PluginClassName', 'VARCHAR', false, 100, null);
        $this->addColumn('supported_country_codes', 'SupportedCountryCodes', 'ARRAY', false, null, null);
        $this->addColumn('results_filter_type', 'ResultsFilterType', 'ENUM', false, null, null);
        $this->getColumn('results_filter_type')->setValueSet(array (
  0 => 'all-only',
  1 => 'all-by-location',
  2 => 'user-filtered',
));
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)
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
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_JOBSITE_KEY);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES);
            $criteria->addSelectColumn(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE);
        } else {
            $criteria->addSelectColumn($alias . '.jobsite_key');
            $criteria->addSelectColumn($alias . '.plugin_class_name');
            $criteria->addSelectColumn($alias . '.supported_country_codes');
            $criteria->addSelectColumn($alias . '.results_filter_type');
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
            $criteria->add(JobSitePluginTableMap::COL_JOBSITE_KEY, (array) $values, Criteria::IN);
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
