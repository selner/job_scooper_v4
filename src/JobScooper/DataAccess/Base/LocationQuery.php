<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\Location as ChildLocation;
use JobScooper\DataAccess\LocationQuery as ChildLocationQuery;
use JobScooper\DataAccess\Map\LocationTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'location' table.
 *
 *
 *
 * @method     ChildLocationQuery orderByLocationId($order = Criteria::ASC) Order by the location_id column
 * @method     ChildLocationQuery orderByLatitude($order = Criteria::ASC) Order by the lat column
 * @method     ChildLocationQuery orderByLongitude($order = Criteria::ASC) Order by the lon column
 * @method     ChildLocationQuery orderByDisplayName($order = Criteria::ASC) Order by the full_display_name column
 * @method     ChildLocationQuery orderByPrimaryName($order = Criteria::ASC) Order by the primary_name column
 * @method     ChildLocationQuery orderByPlace($order = Criteria::ASC) Order by the place column
 * @method     ChildLocationQuery orderByCounty($order = Criteria::ASC) Order by the county column
 * @method     ChildLocationQuery orderByState($order = Criteria::ASC) Order by the state column
 * @method     ChildLocationQuery orderByStateCode($order = Criteria::ASC) Order by the statecode column
 * @method     ChildLocationQuery orderByCountry($order = Criteria::ASC) Order by the country column
 * @method     ChildLocationQuery orderByCountryCode($order = Criteria::ASC) Order by the countrycode column
 * @method     ChildLocationQuery orderByAlternateNames($order = Criteria::ASC) Order by the alternate_names column
 * @method     ChildLocationQuery orderByOpenStreetMapId($order = Criteria::ASC) Order by the openstreetmap_id column
 * @method     ChildLocationQuery orderByFullOsmData($order = Criteria::ASC) Order by the full_osm_data column
 * @method     ChildLocationQuery orderByExtraDetailsData($order = Criteria::ASC) Order by the extra_details_data column
 *
 * @method     ChildLocationQuery groupByLocationId() Group by the location_id column
 * @method     ChildLocationQuery groupByLatitude() Group by the lat column
 * @method     ChildLocationQuery groupByLongitude() Group by the lon column
 * @method     ChildLocationQuery groupByDisplayName() Group by the full_display_name column
 * @method     ChildLocationQuery groupByPrimaryName() Group by the primary_name column
 * @method     ChildLocationQuery groupByPlace() Group by the place column
 * @method     ChildLocationQuery groupByCounty() Group by the county column
 * @method     ChildLocationQuery groupByState() Group by the state column
 * @method     ChildLocationQuery groupByStateCode() Group by the statecode column
 * @method     ChildLocationQuery groupByCountry() Group by the country column
 * @method     ChildLocationQuery groupByCountryCode() Group by the countrycode column
 * @method     ChildLocationQuery groupByAlternateNames() Group by the alternate_names column
 * @method     ChildLocationQuery groupByOpenStreetMapId() Group by the openstreetmap_id column
 * @method     ChildLocationQuery groupByFullOsmData() Group by the full_osm_data column
 * @method     ChildLocationQuery groupByExtraDetailsData() Group by the extra_details_data column
 *
 * @method     ChildLocationQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildLocationQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildLocationQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildLocationQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildLocationQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildLocationQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildLocationQuery leftJoinJobPosting($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobPosting relation
 * @method     ChildLocationQuery rightJoinJobPosting($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobPosting relation
 * @method     ChildLocationQuery innerJoinJobPosting($relationAlias = null) Adds a INNER JOIN clause to the query using the JobPosting relation
 *
 * @method     ChildLocationQuery joinWithJobPosting($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobPosting relation
 *
 * @method     ChildLocationQuery leftJoinWithJobPosting() Adds a LEFT JOIN clause and with to the query using the JobPosting relation
 * @method     ChildLocationQuery rightJoinWithJobPosting() Adds a RIGHT JOIN clause and with to the query using the JobPosting relation
 * @method     ChildLocationQuery innerJoinWithJobPosting() Adds a INNER JOIN clause and with to the query using the JobPosting relation
 *
 * @method     ChildLocationQuery leftJoinLocationNames($relationAlias = null) Adds a LEFT JOIN clause to the query using the LocationNames relation
 * @method     ChildLocationQuery rightJoinLocationNames($relationAlias = null) Adds a RIGHT JOIN clause to the query using the LocationNames relation
 * @method     ChildLocationQuery innerJoinLocationNames($relationAlias = null) Adds a INNER JOIN clause to the query using the LocationNames relation
 *
 * @method     ChildLocationQuery joinWithLocationNames($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the LocationNames relation
 *
 * @method     ChildLocationQuery leftJoinWithLocationNames() Adds a LEFT JOIN clause and with to the query using the LocationNames relation
 * @method     ChildLocationQuery rightJoinWithLocationNames() Adds a RIGHT JOIN clause and with to the query using the LocationNames relation
 * @method     ChildLocationQuery innerJoinWithLocationNames() Adds a INNER JOIN clause and with to the query using the LocationNames relation
 *
 * @method     ChildLocationQuery leftJoinUserSearchRun($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchRun relation
 * @method     ChildLocationQuery rightJoinUserSearchRun($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchRun relation
 * @method     ChildLocationQuery innerJoinUserSearchRun($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchRun relation
 *
 * @method     ChildLocationQuery joinWithUserSearchRun($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchRun relation
 *
 * @method     ChildLocationQuery leftJoinWithUserSearchRun() Adds a LEFT JOIN clause and with to the query using the UserSearchRun relation
 * @method     ChildLocationQuery rightJoinWithUserSearchRun() Adds a RIGHT JOIN clause and with to the query using the UserSearchRun relation
 * @method     ChildLocationQuery innerJoinWithUserSearchRun() Adds a INNER JOIN clause and with to the query using the UserSearchRun relation
 *
 * @method     \JobScooper\DataAccess\JobPostingQuery|\JobScooper\DataAccess\LocationNamesQuery|\JobScooper\DataAccess\UserSearchRunQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildLocation findOne(ConnectionInterface $con = null) Return the first ChildLocation matching the query
 * @method     ChildLocation findOneOrCreate(ConnectionInterface $con = null) Return the first ChildLocation matching the query, or a new ChildLocation object populated from the query conditions when no match is found
 *
 * @method     ChildLocation findOneByLocationId(int $location_id) Return the first ChildLocation filtered by the location_id column
 * @method     ChildLocation findOneByLatitude(double $lat) Return the first ChildLocation filtered by the lat column
 * @method     ChildLocation findOneByLongitude(double $lon) Return the first ChildLocation filtered by the lon column
 * @method     ChildLocation findOneByDisplayName(string $full_display_name) Return the first ChildLocation filtered by the full_display_name column
 * @method     ChildLocation findOneByPrimaryName(string $primary_name) Return the first ChildLocation filtered by the primary_name column
 * @method     ChildLocation findOneByPlace(string $place) Return the first ChildLocation filtered by the place column
 * @method     ChildLocation findOneByCounty(string $county) Return the first ChildLocation filtered by the county column
 * @method     ChildLocation findOneByState(string $state) Return the first ChildLocation filtered by the state column
 * @method     ChildLocation findOneByStateCode(string $statecode) Return the first ChildLocation filtered by the statecode column
 * @method     ChildLocation findOneByCountry(string $country) Return the first ChildLocation filtered by the country column
 * @method     ChildLocation findOneByCountryCode(string $countrycode) Return the first ChildLocation filtered by the countrycode column
 * @method     ChildLocation findOneByAlternateNames(array $alternate_names) Return the first ChildLocation filtered by the alternate_names column
 * @method     ChildLocation findOneByOpenStreetMapId(int $openstreetmap_id) Return the first ChildLocation filtered by the openstreetmap_id column
 * @method     ChildLocation findOneByFullOsmData(string $full_osm_data) Return the first ChildLocation filtered by the full_osm_data column
 * @method     ChildLocation findOneByExtraDetailsData(string $extra_details_data) Return the first ChildLocation filtered by the extra_details_data column *

 * @method     ChildLocation requirePk($key, ConnectionInterface $con = null) Return the ChildLocation by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOne(ConnectionInterface $con = null) Return the first ChildLocation matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildLocation requireOneByLocationId(int $location_id) Return the first ChildLocation filtered by the location_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByLatitude(double $lat) Return the first ChildLocation filtered by the lat column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByLongitude(double $lon) Return the first ChildLocation filtered by the lon column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByDisplayName(string $full_display_name) Return the first ChildLocation filtered by the full_display_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByPrimaryName(string $primary_name) Return the first ChildLocation filtered by the primary_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByPlace(string $place) Return the first ChildLocation filtered by the place column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByCounty(string $county) Return the first ChildLocation filtered by the county column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByState(string $state) Return the first ChildLocation filtered by the state column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByStateCode(string $statecode) Return the first ChildLocation filtered by the statecode column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByCountry(string $country) Return the first ChildLocation filtered by the country column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByCountryCode(string $countrycode) Return the first ChildLocation filtered by the countrycode column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByAlternateNames(array $alternate_names) Return the first ChildLocation filtered by the alternate_names column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByOpenStreetMapId(int $openstreetmap_id) Return the first ChildLocation filtered by the openstreetmap_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByFullOsmData(string $full_osm_data) Return the first ChildLocation filtered by the full_osm_data column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildLocation requireOneByExtraDetailsData(string $extra_details_data) Return the first ChildLocation filtered by the extra_details_data column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildLocation[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildLocation objects based on current ModelCriteria
 * @method     ChildLocation[]|ObjectCollection findByLocationId(int $location_id) Return ChildLocation objects filtered by the location_id column
 * @method     ChildLocation[]|ObjectCollection findByLatitude(double $lat) Return ChildLocation objects filtered by the lat column
 * @method     ChildLocation[]|ObjectCollection findByLongitude(double $lon) Return ChildLocation objects filtered by the lon column
 * @method     ChildLocation[]|ObjectCollection findByDisplayName(string $full_display_name) Return ChildLocation objects filtered by the full_display_name column
 * @method     ChildLocation[]|ObjectCollection findByPrimaryName(string $primary_name) Return ChildLocation objects filtered by the primary_name column
 * @method     ChildLocation[]|ObjectCollection findByPlace(string $place) Return ChildLocation objects filtered by the place column
 * @method     ChildLocation[]|ObjectCollection findByCounty(string $county) Return ChildLocation objects filtered by the county column
 * @method     ChildLocation[]|ObjectCollection findByState(string $state) Return ChildLocation objects filtered by the state column
 * @method     ChildLocation[]|ObjectCollection findByStateCode(string $statecode) Return ChildLocation objects filtered by the statecode column
 * @method     ChildLocation[]|ObjectCollection findByCountry(string $country) Return ChildLocation objects filtered by the country column
 * @method     ChildLocation[]|ObjectCollection findByCountryCode(string $countrycode) Return ChildLocation objects filtered by the countrycode column
 * @method     ChildLocation[]|ObjectCollection findByAlternateNames(array $alternate_names) Return ChildLocation objects filtered by the alternate_names column
 * @method     ChildLocation[]|ObjectCollection findByOpenStreetMapId(int $openstreetmap_id) Return ChildLocation objects filtered by the openstreetmap_id column
 * @method     ChildLocation[]|ObjectCollection findByFullOsmData(string $full_osm_data) Return ChildLocation objects filtered by the full_osm_data column
 * @method     ChildLocation[]|ObjectCollection findByExtraDetailsData(string $extra_details_data) Return ChildLocation objects filtered by the extra_details_data column
 * @method     ChildLocation[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class LocationQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\DataAccess\Base\LocationQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\DataAccess\\Location', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildLocationQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildLocationQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildLocationQuery) {
            return $criteria;
        }
        $query = new ChildLocationQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildLocation|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(LocationTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = LocationTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
            // the object is already in the instance pool
            return $obj;
        }

        return $this->findPkSimple($key, $con);
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildLocation A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT location_id, lat, lon, full_display_name, primary_name, place, county, state, statecode, country, countrycode, alternate_names, openstreetmap_id, full_osm_data, extra_details_data FROM location WHERE location_id = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildLocation $obj */
            $obj = new ChildLocation();
            $obj->hydrate($row);
            LocationTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildLocation|array|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, ConnectionInterface $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param     array $keys Primary keys to use for the query
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ObjectCollection|array|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param     mixed $key Primary key to use for the query
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the location_id column
     *
     * Example usage:
     * <code>
     * $query->filterByLocationId(1234); // WHERE location_id = 1234
     * $query->filterByLocationId(array(12, 34)); // WHERE location_id IN (12, 34)
     * $query->filterByLocationId(array('min' => 12)); // WHERE location_id > 12
     * </code>
     *
     * @param     mixed $locationId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByLocationId($locationId = null, $comparison = null)
    {
        if (is_array($locationId)) {
            $useMinMax = false;
            if (isset($locationId['min'])) {
                $this->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $locationId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($locationId['max'])) {
                $this->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $locationId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $locationId, $comparison);
    }

    /**
     * Filter the query on the lat column
     *
     * Example usage:
     * <code>
     * $query->filterByLatitude(1234); // WHERE lat = 1234
     * $query->filterByLatitude(array(12, 34)); // WHERE lat IN (12, 34)
     * $query->filterByLatitude(array('min' => 12)); // WHERE lat > 12
     * </code>
     *
     * @param     mixed $latitude The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByLatitude($latitude = null, $comparison = null)
    {
        if (is_array($latitude)) {
            $useMinMax = false;
            if (isset($latitude['min'])) {
                $this->addUsingAlias(LocationTableMap::COL_LAT, $latitude['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($latitude['max'])) {
                $this->addUsingAlias(LocationTableMap::COL_LAT, $latitude['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_LAT, $latitude, $comparison);
    }

    /**
     * Filter the query on the lon column
     *
     * Example usage:
     * <code>
     * $query->filterByLongitude(1234); // WHERE lon = 1234
     * $query->filterByLongitude(array(12, 34)); // WHERE lon IN (12, 34)
     * $query->filterByLongitude(array('min' => 12)); // WHERE lon > 12
     * </code>
     *
     * @param     mixed $longitude The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByLongitude($longitude = null, $comparison = null)
    {
        if (is_array($longitude)) {
            $useMinMax = false;
            if (isset($longitude['min'])) {
                $this->addUsingAlias(LocationTableMap::COL_LON, $longitude['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($longitude['max'])) {
                $this->addUsingAlias(LocationTableMap::COL_LON, $longitude['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_LON, $longitude, $comparison);
    }

    /**
     * Filter the query on the full_display_name column
     *
     * Example usage:
     * <code>
     * $query->filterByDisplayName('fooValue');   // WHERE full_display_name = 'fooValue'
     * $query->filterByDisplayName('%fooValue%', Criteria::LIKE); // WHERE full_display_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $displayName The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByDisplayName($displayName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($displayName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_FULL_DISPLAY_NAME, $displayName, $comparison);
    }

    /**
     * Filter the query on the primary_name column
     *
     * Example usage:
     * <code>
     * $query->filterByPrimaryName('fooValue');   // WHERE primary_name = 'fooValue'
     * $query->filterByPrimaryName('%fooValue%', Criteria::LIKE); // WHERE primary_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $primaryName The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByPrimaryName($primaryName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($primaryName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_PRIMARY_NAME, $primaryName, $comparison);
    }

    /**
     * Filter the query on the place column
     *
     * Example usage:
     * <code>
     * $query->filterByPlace('fooValue');   // WHERE place = 'fooValue'
     * $query->filterByPlace('%fooValue%', Criteria::LIKE); // WHERE place LIKE '%fooValue%'
     * </code>
     *
     * @param     string $place The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByPlace($place = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($place)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_PLACE, $place, $comparison);
    }

    /**
     * Filter the query on the county column
     *
     * Example usage:
     * <code>
     * $query->filterByCounty('fooValue');   // WHERE county = 'fooValue'
     * $query->filterByCounty('%fooValue%', Criteria::LIKE); // WHERE county LIKE '%fooValue%'
     * </code>
     *
     * @param     string $county The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByCounty($county = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($county)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_COUNTY, $county, $comparison);
    }

    /**
     * Filter the query on the state column
     *
     * Example usage:
     * <code>
     * $query->filterByState('fooValue');   // WHERE state = 'fooValue'
     * $query->filterByState('%fooValue%', Criteria::LIKE); // WHERE state LIKE '%fooValue%'
     * </code>
     *
     * @param     string $state The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByState($state = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($state)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_STATE, $state, $comparison);
    }

    /**
     * Filter the query on the statecode column
     *
     * Example usage:
     * <code>
     * $query->filterByStateCode('fooValue');   // WHERE statecode = 'fooValue'
     * $query->filterByStateCode('%fooValue%', Criteria::LIKE); // WHERE statecode LIKE '%fooValue%'
     * </code>
     *
     * @param     string $stateCode The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByStateCode($stateCode = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($stateCode)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_STATECODE, $stateCode, $comparison);
    }

    /**
     * Filter the query on the country column
     *
     * Example usage:
     * <code>
     * $query->filterByCountry('fooValue');   // WHERE country = 'fooValue'
     * $query->filterByCountry('%fooValue%', Criteria::LIKE); // WHERE country LIKE '%fooValue%'
     * </code>
     *
     * @param     string $country The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByCountry($country = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($country)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_COUNTRY, $country, $comparison);
    }

    /**
     * Filter the query on the countrycode column
     *
     * Example usage:
     * <code>
     * $query->filterByCountryCode('fooValue');   // WHERE countrycode = 'fooValue'
     * $query->filterByCountryCode('%fooValue%', Criteria::LIKE); // WHERE countrycode LIKE '%fooValue%'
     * </code>
     *
     * @param     string $countryCode The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByCountryCode($countryCode = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($countryCode)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_COUNTRYCODE, $countryCode, $comparison);
    }

    /**
     * Filter the query on the alternate_names column
     *
     * @param     array $alternateNames The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByAlternateNames($alternateNames = null, $comparison = null)
    {
        $key = $this->getAliasedColName(LocationTableMap::COL_ALTERNATE_NAMES);
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            foreach ($alternateNames as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_SOME) {
            foreach ($alternateNames as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addOr($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            foreach ($alternateNames as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::NOT_LIKE);
                } else {
                    $this->add($key, $value, Criteria::NOT_LIKE);
                }
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(LocationTableMap::COL_ALTERNATE_NAMES, $alternateNames, $comparison);
    }

    /**
     * Filter the query on the alternate_names column
     * @param     mixed $alternateNames The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByAlternateName($alternateNames = null, $comparison = null)
    {
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            if (is_scalar($alternateNames)) {
                $alternateNames = '%| ' . $alternateNames . ' |%';
                $comparison = Criteria::LIKE;
            }
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            $alternateNames = '%| ' . $alternateNames . ' |%';
            $comparison = Criteria::NOT_LIKE;
            $key = $this->getAliasedColName(LocationTableMap::COL_ALTERNATE_NAMES);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $alternateNames, $comparison);
            } else {
                $this->addAnd($key, $alternateNames, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(LocationTableMap::COL_ALTERNATE_NAMES, $alternateNames, $comparison);
    }

    /**
     * Filter the query on the openstreetmap_id column
     *
     * Example usage:
     * <code>
     * $query->filterByOpenStreetMapId(1234); // WHERE openstreetmap_id = 1234
     * $query->filterByOpenStreetMapId(array(12, 34)); // WHERE openstreetmap_id IN (12, 34)
     * $query->filterByOpenStreetMapId(array('min' => 12)); // WHERE openstreetmap_id > 12
     * </code>
     *
     * @param     mixed $openStreetMapId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByOpenStreetMapId($openStreetMapId = null, $comparison = null)
    {
        if (is_array($openStreetMapId)) {
            $useMinMax = false;
            if (isset($openStreetMapId['min'])) {
                $this->addUsingAlias(LocationTableMap::COL_OPENSTREETMAP_ID, $openStreetMapId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($openStreetMapId['max'])) {
                $this->addUsingAlias(LocationTableMap::COL_OPENSTREETMAP_ID, $openStreetMapId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_OPENSTREETMAP_ID, $openStreetMapId, $comparison);
    }

    /**
     * Filter the query on the full_osm_data column
     *
     * Example usage:
     * <code>
     * $query->filterByFullOsmData('fooValue');   // WHERE full_osm_data = 'fooValue'
     * $query->filterByFullOsmData('%fooValue%', Criteria::LIKE); // WHERE full_osm_data LIKE '%fooValue%'
     * </code>
     *
     * @param     string $fullOsmData The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByFullOsmData($fullOsmData = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($fullOsmData)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_FULL_OSM_DATA, $fullOsmData, $comparison);
    }

    /**
     * Filter the query on the extra_details_data column
     *
     * Example usage:
     * <code>
     * $query->filterByExtraDetailsData('fooValue');   // WHERE extra_details_data = 'fooValue'
     * $query->filterByExtraDetailsData('%fooValue%', Criteria::LIKE); // WHERE extra_details_data LIKE '%fooValue%'
     * </code>
     *
     * @param     string $extraDetailsData The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function filterByExtraDetailsData($extraDetailsData = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($extraDetailsData)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(LocationTableMap::COL_EXTRA_DETAILS_DATA, $extraDetailsData, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\JobPosting object
     *
     * @param \JobScooper\DataAccess\JobPosting|ObjectCollection $jobPosting the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildLocationQuery The current query, for fluid interface
     */
    public function filterByJobPosting($jobPosting, $comparison = null)
    {
        if ($jobPosting instanceof \JobScooper\DataAccess\JobPosting) {
            return $this
                ->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $jobPosting->getLocationId(), $comparison);
        } elseif ($jobPosting instanceof ObjectCollection) {
            return $this
                ->useJobPostingQuery()
                ->filterByPrimaryKeys($jobPosting->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByJobPosting() only accepts arguments of type \JobScooper\DataAccess\JobPosting or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the JobPosting relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function joinJobPosting($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('JobPosting');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'JobPosting');
        }

        return $this;
    }

    /**
     * Use the JobPosting relation JobPosting object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\JobPostingQuery A secondary query class using the current class as primary query
     */
    public function useJobPostingQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinJobPosting($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobPosting', '\JobScooper\DataAccess\JobPostingQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\LocationNames object
     *
     * @param \JobScooper\DataAccess\LocationNames|ObjectCollection $locationNames the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildLocationQuery The current query, for fluid interface
     */
    public function filterByLocationNames($locationNames, $comparison = null)
    {
        if ($locationNames instanceof \JobScooper\DataAccess\LocationNames) {
            return $this
                ->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $locationNames->getLocationId(), $comparison);
        } elseif ($locationNames instanceof ObjectCollection) {
            return $this
                ->useLocationNamesQuery()
                ->filterByPrimaryKeys($locationNames->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByLocationNames() only accepts arguments of type \JobScooper\DataAccess\LocationNames or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the LocationNames relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function joinLocationNames($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('LocationNames');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'LocationNames');
        }

        return $this;
    }

    /**
     * Use the LocationNames relation LocationNames object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\LocationNamesQuery A secondary query class using the current class as primary query
     */
    public function useLocationNamesQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinLocationNames($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'LocationNames', '\JobScooper\DataAccess\LocationNamesQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\UserSearchRun object
     *
     * @param \JobScooper\DataAccess\UserSearchRun|ObjectCollection $userSearchRun the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildLocationQuery The current query, for fluid interface
     */
    public function filterByUserSearchRun($userSearchRun, $comparison = null)
    {
        if ($userSearchRun instanceof \JobScooper\DataAccess\UserSearchRun) {
            return $this
                ->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $userSearchRun->getLocationId(), $comparison);
        } elseif ($userSearchRun instanceof ObjectCollection) {
            return $this
                ->useUserSearchRunQuery()
                ->filterByPrimaryKeys($userSearchRun->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByUserSearchRun() only accepts arguments of type \JobScooper\DataAccess\UserSearchRun or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearchRun relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function joinUserSearchRun($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserSearchRun');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'UserSearchRun');
        }

        return $this;
    }

    /**
     * Use the UserSearchRun relation UserSearchRun object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserSearchRunQuery A secondary query class using the current class as primary query
     */
    public function useUserSearchRunQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinUserSearchRun($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearchRun', '\JobScooper\DataAccess\UserSearchRunQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildLocation $location Object to remove from the list of results
     *
     * @return $this|ChildLocationQuery The current query, for fluid interface
     */
    public function prune($location = null)
    {
        if ($location) {
            $this->addUsingAlias(LocationTableMap::COL_LOCATION_ID, $location->getLocationId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the location table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(LocationTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            LocationTableMap::clearInstancePool();
            LocationTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    /**
     * Performs a DELETE on the database based on the current ModelCriteria
     *
     * @param ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public function delete(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(LocationTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(LocationTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            LocationTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            LocationTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // LocationQuery
