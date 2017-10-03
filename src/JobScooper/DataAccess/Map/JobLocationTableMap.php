<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\JobLocation;
use JobScooper\DataAccess\JobLocationQuery;
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
 * This class defines the structure of the 'job_location' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class JobLocationTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.JobLocationTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'job_location';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\JobLocation';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.JobLocation';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 13;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 13;

    /**
     * the column name for the location_id field
     */
    const COL_LOCATION_ID = 'job_location.location_id';

    /**
     * the column name for the lat field
     */
    const COL_LAT = 'job_location.lat';

    /**
     * the column name for the lon field
     */
    const COL_LON = 'job_location.lon';

    /**
     * the column name for the full_display_name field
     */
    const COL_FULL_DISPLAY_NAME = 'job_location.full_display_name';

    /**
     * the column name for the primary_name field
     */
    const COL_PRIMARY_NAME = 'job_location.primary_name';

    /**
     * the column name for the place field
     */
    const COL_PLACE = 'job_location.place';

    /**
     * the column name for the county field
     */
    const COL_COUNTY = 'job_location.county';

    /**
     * the column name for the state field
     */
    const COL_STATE = 'job_location.state';

    /**
     * the column name for the statecode field
     */
    const COL_STATECODE = 'job_location.statecode';

    /**
     * the column name for the country field
     */
    const COL_COUNTRY = 'job_location.country';

    /**
     * the column name for the countrycode field
     */
    const COL_COUNTRYCODE = 'job_location.countrycode';

    /**
     * the column name for the alternate_names field
     */
    const COL_ALTERNATE_NAMES = 'job_location.alternate_names';

    /**
     * the column name for the openstreetmap_id field
     */
    const COL_OPENSTREETMAP_ID = 'job_location.openstreetmap_id';

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
        self::TYPE_PHPNAME       => array('LocationId', 'Latitude', 'Logitude', 'DisplayName', 'PrimaryName', 'Place', 'County', 'State', 'StateCode', 'Country', 'CountryCode', 'AlternateNames', 'OpenStreetMapId', ),
        self::TYPE_CAMELNAME     => array('locationId', 'latitude', 'logitude', 'displayName', 'primaryName', 'place', 'county', 'state', 'stateCode', 'country', 'countryCode', 'alternateNames', 'openStreetMapId', ),
        self::TYPE_COLNAME       => array(JobLocationTableMap::COL_LOCATION_ID, JobLocationTableMap::COL_LAT, JobLocationTableMap::COL_LON, JobLocationTableMap::COL_FULL_DISPLAY_NAME, JobLocationTableMap::COL_PRIMARY_NAME, JobLocationTableMap::COL_PLACE, JobLocationTableMap::COL_COUNTY, JobLocationTableMap::COL_STATE, JobLocationTableMap::COL_STATECODE, JobLocationTableMap::COL_COUNTRY, JobLocationTableMap::COL_COUNTRYCODE, JobLocationTableMap::COL_ALTERNATE_NAMES, JobLocationTableMap::COL_OPENSTREETMAP_ID, ),
        self::TYPE_FIELDNAME     => array('location_id', 'lat', 'lon', 'full_display_name', 'primary_name', 'place', 'county', 'state', 'statecode', 'country', 'countrycode', 'alternate_names', 'openstreetmap_id', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('LocationId' => 0, 'Latitude' => 1, 'Logitude' => 2, 'DisplayName' => 3, 'PrimaryName' => 4, 'Place' => 5, 'County' => 6, 'State' => 7, 'StateCode' => 8, 'Country' => 9, 'CountryCode' => 10, 'AlternateNames' => 11, 'OpenStreetMapId' => 12, ),
        self::TYPE_CAMELNAME     => array('locationId' => 0, 'latitude' => 1, 'logitude' => 2, 'displayName' => 3, 'primaryName' => 4, 'place' => 5, 'county' => 6, 'state' => 7, 'stateCode' => 8, 'country' => 9, 'countryCode' => 10, 'alternateNames' => 11, 'openStreetMapId' => 12, ),
        self::TYPE_COLNAME       => array(JobLocationTableMap::COL_LOCATION_ID => 0, JobLocationTableMap::COL_LAT => 1, JobLocationTableMap::COL_LON => 2, JobLocationTableMap::COL_FULL_DISPLAY_NAME => 3, JobLocationTableMap::COL_PRIMARY_NAME => 4, JobLocationTableMap::COL_PLACE => 5, JobLocationTableMap::COL_COUNTY => 6, JobLocationTableMap::COL_STATE => 7, JobLocationTableMap::COL_STATECODE => 8, JobLocationTableMap::COL_COUNTRY => 9, JobLocationTableMap::COL_COUNTRYCODE => 10, JobLocationTableMap::COL_ALTERNATE_NAMES => 11, JobLocationTableMap::COL_OPENSTREETMAP_ID => 12, ),
        self::TYPE_FIELDNAME     => array('location_id' => 0, 'lat' => 1, 'lon' => 2, 'full_display_name' => 3, 'primary_name' => 4, 'place' => 5, 'county' => 6, 'state' => 7, 'statecode' => 8, 'country' => 9, 'countrycode' => 10, 'alternate_names' => 11, 'openstreetmap_id' => 12, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, )
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
        $this->setName('job_location');
        $this->setPhpName('JobLocation');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\JobLocation');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('location_id', 'LocationId', 'INTEGER', true, null, null);
        $this->addColumn('lat', 'Latitude', 'FLOAT', false, null, null);
        $this->addColumn('lon', 'Logitude', 'FLOAT', false, null, null);
        $this->addColumn('full_display_name', 'DisplayName', 'VARCHAR', false, 100, null);
        $this->addColumn('primary_name', 'PrimaryName', 'VARCHAR', false, 100, null);
        $this->addColumn('place', 'Place', 'VARCHAR', false, 100, null);
        $this->addColumn('county', 'County', 'VARCHAR', false, 100, null);
        $this->addColumn('state', 'State', 'VARCHAR', false, 100, null);
        $this->addColumn('statecode', 'StateCode', 'VARCHAR', false, 2, null);
        $this->addColumn('country', 'Country', 'VARCHAR', false, 100, null);
        $this->addColumn('countrycode', 'CountryCode', 'VARCHAR', false, 2, null);
        $this->addColumn('alternate_names', 'AlternateNames', 'ARRAY', false, null, null);
        $this->addColumn('openstreetmap_id', 'OpenStreetMapId', 'INTEGER', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('JobPosting', '\\JobScooper\\DataAccess\\JobPosting', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':job_location_id',
    1 => ':location_id',
  ),
), null, null, 'JobPostings', false);
        $this->addRelation('JobPlaceLookup', '\\JobScooper\\DataAccess\\JobPlaceLookup', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':job_location_id',
    1 => ':location_id',
  ),
), null, null, 'JobPlaceLookups', false);
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('LocationId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('LocationId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('LocationId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('LocationId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('LocationId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('LocationId', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('LocationId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? JobLocationTableMap::CLASS_DEFAULT : JobLocationTableMap::OM_CLASS;
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
     * @return array           (JobLocation object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = JobLocationTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = JobLocationTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + JobLocationTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = JobLocationTableMap::OM_CLASS;
            /** @var JobLocation $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            JobLocationTableMap::addInstanceToPool($obj, $key);
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
            $key = JobLocationTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = JobLocationTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var JobLocation $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                JobLocationTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(JobLocationTableMap::COL_LOCATION_ID);
            $criteria->addSelectColumn(JobLocationTableMap::COL_LAT);
            $criteria->addSelectColumn(JobLocationTableMap::COL_LON);
            $criteria->addSelectColumn(JobLocationTableMap::COL_FULL_DISPLAY_NAME);
            $criteria->addSelectColumn(JobLocationTableMap::COL_PRIMARY_NAME);
            $criteria->addSelectColumn(JobLocationTableMap::COL_PLACE);
            $criteria->addSelectColumn(JobLocationTableMap::COL_COUNTY);
            $criteria->addSelectColumn(JobLocationTableMap::COL_STATE);
            $criteria->addSelectColumn(JobLocationTableMap::COL_STATECODE);
            $criteria->addSelectColumn(JobLocationTableMap::COL_COUNTRY);
            $criteria->addSelectColumn(JobLocationTableMap::COL_COUNTRYCODE);
            $criteria->addSelectColumn(JobLocationTableMap::COL_ALTERNATE_NAMES);
            $criteria->addSelectColumn(JobLocationTableMap::COL_OPENSTREETMAP_ID);
        } else {
            $criteria->addSelectColumn($alias . '.location_id');
            $criteria->addSelectColumn($alias . '.lat');
            $criteria->addSelectColumn($alias . '.lon');
            $criteria->addSelectColumn($alias . '.full_display_name');
            $criteria->addSelectColumn($alias . '.primary_name');
            $criteria->addSelectColumn($alias . '.place');
            $criteria->addSelectColumn($alias . '.county');
            $criteria->addSelectColumn($alias . '.state');
            $criteria->addSelectColumn($alias . '.statecode');
            $criteria->addSelectColumn($alias . '.country');
            $criteria->addSelectColumn($alias . '.countrycode');
            $criteria->addSelectColumn($alias . '.alternate_names');
            $criteria->addSelectColumn($alias . '.openstreetmap_id');
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
        return Propel::getServiceContainer()->getDatabaseMap(JobLocationTableMap::DATABASE_NAME)->getTable(JobLocationTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(JobLocationTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(JobLocationTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new JobLocationTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a JobLocation or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or JobLocation object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(JobLocationTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\JobLocation) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(JobLocationTableMap::DATABASE_NAME);
            $criteria->add(JobLocationTableMap::COL_LOCATION_ID, (array) $values, Criteria::IN);
        }

        $query = JobLocationQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            JobLocationTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                JobLocationTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the job_location table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return JobLocationQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a JobLocation or Criteria object.
     *
     * @param mixed               $criteria Criteria or JobLocation object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobLocationTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from JobLocation object
        }

        if ($criteria->containsKey(JobLocationTableMap::COL_LOCATION_ID) && $criteria->keyContainsValue(JobLocationTableMap::COL_LOCATION_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.JobLocationTableMap::COL_LOCATION_ID.')');
        }


        // Set the correct dbName
        $query = JobLocationQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // JobLocationTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
JobLocationTableMap::buildTableMap();
