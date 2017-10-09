<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\Location;
use JobScooper\DataAccess\LocationQuery;
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
 * This class defines the structure of the 'location' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class LocationTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.LocationTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'location';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\Location';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.Location';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 16;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 16;

    /**
     * the column name for the location_id field
     */
    const COL_LOCATION_ID = 'location.location_id';

    /**
     * the column name for the latitude field
     */
    const COL_LATITUDE = 'location.latitude';

    /**
     * the column name for the longitude field
     */
    const COL_LONGITUDE = 'location.longitude';

    /**
     * the column name for the full_display_name field
     */
    const COL_FULL_DISPLAY_NAME = 'location.full_display_name';

    /**
     * the column name for the location_key field
     */
    const COL_LOCATION_KEY = 'location.location_key';

    /**
     * the column name for the primary_name field
     */
    const COL_PRIMARY_NAME = 'location.primary_name';

    /**
     * the column name for the place field
     */
    const COL_PLACE = 'location.place';

    /**
     * the column name for the county field
     */
    const COL_COUNTY = 'location.county';

    /**
     * the column name for the state field
     */
    const COL_STATE = 'location.state';

    /**
     * the column name for the statecode field
     */
    const COL_STATECODE = 'location.statecode';

    /**
     * the column name for the country field
     */
    const COL_COUNTRY = 'location.country';

    /**
     * the column name for the countrycode field
     */
    const COL_COUNTRYCODE = 'location.countrycode';

    /**
     * the column name for the alternate_names field
     */
    const COL_ALTERNATE_NAMES = 'location.alternate_names';

    /**
     * the column name for the openstreetmap_id field
     */
    const COL_OPENSTREETMAP_ID = 'location.openstreetmap_id';

    /**
     * the column name for the full_osm_data field
     */
    const COL_FULL_OSM_DATA = 'location.full_osm_data';

    /**
     * the column name for the extra_details_data field
     */
    const COL_EXTRA_DETAILS_DATA = 'location.extra_details_data';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    // geocodable behavior

    /**
     * Kilometers unit
     */
    const KILOMETERS_UNIT = 1.609344;

    /**
     * Miles unit
     */
    const MILES_UNIT = 1.1515;

    /**
     * Nautical miles unit
     */
    const NAUTICAL_MILES_UNIT = 0.8684;

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('LocationId', 'Latitude', 'Longitude', 'DisplayName', 'LocationKey', 'PrimaryName', 'Place', 'County', 'State', 'StateCode', 'Country', 'CountryCode', 'AlternateNames', 'OpenStreetMapId', 'FullOsmData', 'ExtraDetailsData', ),
        self::TYPE_CAMELNAME     => array('locationId', 'latitude', 'longitude', 'displayName', 'locationKey', 'primaryName', 'place', 'county', 'state', 'stateCode', 'country', 'countryCode', 'alternateNames', 'openStreetMapId', 'fullOsmData', 'extraDetailsData', ),
        self::TYPE_COLNAME       => array(LocationTableMap::COL_LOCATION_ID, LocationTableMap::COL_LATITUDE, LocationTableMap::COL_LONGITUDE, LocationTableMap::COL_FULL_DISPLAY_NAME, LocationTableMap::COL_LOCATION_KEY, LocationTableMap::COL_PRIMARY_NAME, LocationTableMap::COL_PLACE, LocationTableMap::COL_COUNTY, LocationTableMap::COL_STATE, LocationTableMap::COL_STATECODE, LocationTableMap::COL_COUNTRY, LocationTableMap::COL_COUNTRYCODE, LocationTableMap::COL_ALTERNATE_NAMES, LocationTableMap::COL_OPENSTREETMAP_ID, LocationTableMap::COL_FULL_OSM_DATA, LocationTableMap::COL_EXTRA_DETAILS_DATA, ),
        self::TYPE_FIELDNAME     => array('location_id', 'latitude', 'longitude', 'full_display_name', 'location_key', 'primary_name', 'place', 'county', 'state', 'statecode', 'country', 'countrycode', 'alternate_names', 'openstreetmap_id', 'full_osm_data', 'extra_details_data', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('LocationId' => 0, 'Latitude' => 1, 'Longitude' => 2, 'DisplayName' => 3, 'LocationKey' => 4, 'PrimaryName' => 5, 'Place' => 6, 'County' => 7, 'State' => 8, 'StateCode' => 9, 'Country' => 10, 'CountryCode' => 11, 'AlternateNames' => 12, 'OpenStreetMapId' => 13, 'FullOsmData' => 14, 'ExtraDetailsData' => 15, ),
        self::TYPE_CAMELNAME     => array('locationId' => 0, 'latitude' => 1, 'longitude' => 2, 'displayName' => 3, 'locationKey' => 4, 'primaryName' => 5, 'place' => 6, 'county' => 7, 'state' => 8, 'stateCode' => 9, 'country' => 10, 'countryCode' => 11, 'alternateNames' => 12, 'openStreetMapId' => 13, 'fullOsmData' => 14, 'extraDetailsData' => 15, ),
        self::TYPE_COLNAME       => array(LocationTableMap::COL_LOCATION_ID => 0, LocationTableMap::COL_LATITUDE => 1, LocationTableMap::COL_LONGITUDE => 2, LocationTableMap::COL_FULL_DISPLAY_NAME => 3, LocationTableMap::COL_LOCATION_KEY => 4, LocationTableMap::COL_PRIMARY_NAME => 5, LocationTableMap::COL_PLACE => 6, LocationTableMap::COL_COUNTY => 7, LocationTableMap::COL_STATE => 8, LocationTableMap::COL_STATECODE => 9, LocationTableMap::COL_COUNTRY => 10, LocationTableMap::COL_COUNTRYCODE => 11, LocationTableMap::COL_ALTERNATE_NAMES => 12, LocationTableMap::COL_OPENSTREETMAP_ID => 13, LocationTableMap::COL_FULL_OSM_DATA => 14, LocationTableMap::COL_EXTRA_DETAILS_DATA => 15, ),
        self::TYPE_FIELDNAME     => array('location_id' => 0, 'latitude' => 1, 'longitude' => 2, 'full_display_name' => 3, 'location_key' => 4, 'primary_name' => 5, 'place' => 6, 'county' => 7, 'state' => 8, 'statecode' => 9, 'country' => 10, 'countrycode' => 11, 'alternate_names' => 12, 'openstreetmap_id' => 13, 'full_osm_data' => 14, 'extra_details_data' => 15, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, )
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
        $this->setName('location');
        $this->setPhpName('Location');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\Location');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('location_id', 'LocationId', 'INTEGER', true, null, null);
        $this->addColumn('latitude', 'Latitude', 'FLOAT', false, null, null);
        $this->addColumn('longitude', 'Longitude', 'FLOAT', false, null, null);
        $this->addColumn('full_display_name', 'DisplayName', 'VARCHAR', false, 100, null);
        $this->getColumn('full_display_name')->setPrimaryString(true);
        $this->addColumn('location_key', 'LocationKey', 'VARCHAR', true, 100, null);
        $this->addColumn('primary_name', 'PrimaryName', 'VARCHAR', false, 100, null);
        $this->addColumn('place', 'Place', 'VARCHAR', false, 100, null);
        $this->addColumn('county', 'County', 'VARCHAR', false, 100, null);
        $this->addColumn('state', 'State', 'VARCHAR', false, 100, null);
        $this->addColumn('statecode', 'StateCode', 'VARCHAR', false, 2, null);
        $this->addColumn('country', 'Country', 'VARCHAR', false, 100, null);
        $this->addColumn('countrycode', 'CountryCode', 'VARCHAR', false, 2, null);
        $this->addColumn('alternate_names', 'AlternateNames', 'ARRAY', false, null, null);
        $this->addColumn('openstreetmap_id', 'OpenStreetMapId', 'INTEGER', false, null, null);
        $this->addColumn('full_osm_data', 'FullOsmData', 'LONGVARCHAR', false, null, null);
        $this->addColumn('extra_details_data', 'ExtraDetailsData', 'LONGVARCHAR', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('JobPosting', '\\JobScooper\\DataAccess\\JobPosting', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':location_id',
    1 => ':location_id',
  ),
), null, null, 'JobPostings', false);
        $this->addRelation('LocationNames', '\\JobScooper\\DataAccess\\LocationNames', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':location_id',
    1 => ':location_id',
  ),
), null, null, 'LocationNamess', false);
        $this->addRelation('UserSearchRun', '\\JobScooper\\DataAccess\\UserSearchRun', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':location_id',
    1 => ':location_id',
  ),
), null, null, 'UserSearchRuns', false);
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
            'geocodable' => array('auto_update' => 'false', 'latitude_column' => 'latitude', 'longitude_column' => 'longitude', 'type' => 'DOUBLE', 'size' => '11', 'scale' => '8', 'geocode_ip' => 'false', 'ip_column' => 'ip_address', 'geocode_address' => 'false', 'address_columns' => 'street,locality,region,postal_code,country', 'geocoder_provider' => '\Geocoder\Provider\OpenStreetMapProvider', 'geocoder_adapter' => '\Geocoder\HttpAdapter\CurlHttpAdapter', 'geocoder_api_key' => 'false', 'geocoder_api_key_provider' => 'false', ),
            'sluggable' => array('slug_column' => 'location_key', 'slug_pattern' => '{DisplayName}', 'replace_pattern' => '/[^\w\/]+/u', 'replacement' => '', 'separator' => '-', 'permanent' => 'true', 'scope_column' => '', 'unique_constraint' => 'true', ),
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
        return $withPrefix ? LocationTableMap::CLASS_DEFAULT : LocationTableMap::OM_CLASS;
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
     * @return array           (Location object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = LocationTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = LocationTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + LocationTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = LocationTableMap::OM_CLASS;
            /** @var Location $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            LocationTableMap::addInstanceToPool($obj, $key);
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
            $key = LocationTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = LocationTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var Location $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                LocationTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(LocationTableMap::COL_LOCATION_ID);
            $criteria->addSelectColumn(LocationTableMap::COL_LATITUDE);
            $criteria->addSelectColumn(LocationTableMap::COL_LONGITUDE);
            $criteria->addSelectColumn(LocationTableMap::COL_FULL_DISPLAY_NAME);
            $criteria->addSelectColumn(LocationTableMap::COL_LOCATION_KEY);
            $criteria->addSelectColumn(LocationTableMap::COL_PRIMARY_NAME);
            $criteria->addSelectColumn(LocationTableMap::COL_PLACE);
            $criteria->addSelectColumn(LocationTableMap::COL_COUNTY);
            $criteria->addSelectColumn(LocationTableMap::COL_STATE);
            $criteria->addSelectColumn(LocationTableMap::COL_STATECODE);
            $criteria->addSelectColumn(LocationTableMap::COL_COUNTRY);
            $criteria->addSelectColumn(LocationTableMap::COL_COUNTRYCODE);
            $criteria->addSelectColumn(LocationTableMap::COL_ALTERNATE_NAMES);
            $criteria->addSelectColumn(LocationTableMap::COL_OPENSTREETMAP_ID);
            $criteria->addSelectColumn(LocationTableMap::COL_FULL_OSM_DATA);
            $criteria->addSelectColumn(LocationTableMap::COL_EXTRA_DETAILS_DATA);
        } else {
            $criteria->addSelectColumn($alias . '.location_id');
            $criteria->addSelectColumn($alias . '.latitude');
            $criteria->addSelectColumn($alias . '.longitude');
            $criteria->addSelectColumn($alias . '.full_display_name');
            $criteria->addSelectColumn($alias . '.location_key');
            $criteria->addSelectColumn($alias . '.primary_name');
            $criteria->addSelectColumn($alias . '.place');
            $criteria->addSelectColumn($alias . '.county');
            $criteria->addSelectColumn($alias . '.state');
            $criteria->addSelectColumn($alias . '.statecode');
            $criteria->addSelectColumn($alias . '.country');
            $criteria->addSelectColumn($alias . '.countrycode');
            $criteria->addSelectColumn($alias . '.alternate_names');
            $criteria->addSelectColumn($alias . '.openstreetmap_id');
            $criteria->addSelectColumn($alias . '.full_osm_data');
            $criteria->addSelectColumn($alias . '.extra_details_data');
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
        return Propel::getServiceContainer()->getDatabaseMap(LocationTableMap::DATABASE_NAME)->getTable(LocationTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(LocationTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(LocationTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new LocationTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a Location or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or Location object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(LocationTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\Location) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(LocationTableMap::DATABASE_NAME);
            $criteria->add(LocationTableMap::COL_LOCATION_ID, (array) $values, Criteria::IN);
        }

        $query = LocationQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            LocationTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                LocationTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the location table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return LocationQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a Location or Criteria object.
     *
     * @param mixed               $criteria Criteria or Location object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(LocationTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from Location object
        }

        if ($criteria->containsKey(LocationTableMap::COL_LOCATION_ID) && $criteria->keyContainsValue(LocationTableMap::COL_LOCATION_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.LocationTableMap::COL_LOCATION_ID.')');
        }


        // Set the correct dbName
        $query = LocationQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // LocationTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
LocationTableMap::buildTableMap();
