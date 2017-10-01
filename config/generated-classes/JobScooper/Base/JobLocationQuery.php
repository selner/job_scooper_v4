<?php

namespace JobScooper\Base;

use \Exception;
use \PDO;
use JobScooper\JobLocation as ChildJobLocation;
use JobScooper\JobLocationQuery as ChildJobLocationQuery;
use JobScooper\Map\JobLocationTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'job_location' table.
 *
 *
 *
 * @method     ChildJobLocationQuery orderByLocationId($order = Criteria::ASC) Order by the location_id column
 * @method     ChildJobLocationQuery orderByLatitude($order = Criteria::ASC) Order by the lat column
 * @method     ChildJobLocationQuery orderByLogitude($order = Criteria::ASC) Order by the lon column
 * @method     ChildJobLocationQuery orderByDisplayName($order = Criteria::ASC) Order by the full_display_name column
 * @method     ChildJobLocationQuery orderByPrimaryName($order = Criteria::ASC) Order by the primary_name column
 * @method     ChildJobLocationQuery orderByPlace($order = Criteria::ASC) Order by the place column
 * @method     ChildJobLocationQuery orderByCounty($order = Criteria::ASC) Order by the county column
 * @method     ChildJobLocationQuery orderByState($order = Criteria::ASC) Order by the state column
 * @method     ChildJobLocationQuery orderByStateCode($order = Criteria::ASC) Order by the statecode column
 * @method     ChildJobLocationQuery orderByCountry($order = Criteria::ASC) Order by the country column
 * @method     ChildJobLocationQuery orderByCountryCode($order = Criteria::ASC) Order by the countrycode column
 * @method     ChildJobLocationQuery orderByAlternateNames($order = Criteria::ASC) Order by the alternate_names column
 * @method     ChildJobLocationQuery orderByOpenStreetMapId($order = Criteria::ASC) Order by the openstreetmap_id column
 *
 * @method     ChildJobLocationQuery groupByLocationId() Group by the location_id column
 * @method     ChildJobLocationQuery groupByLatitude() Group by the lat column
 * @method     ChildJobLocationQuery groupByLogitude() Group by the lon column
 * @method     ChildJobLocationQuery groupByDisplayName() Group by the full_display_name column
 * @method     ChildJobLocationQuery groupByPrimaryName() Group by the primary_name column
 * @method     ChildJobLocationQuery groupByPlace() Group by the place column
 * @method     ChildJobLocationQuery groupByCounty() Group by the county column
 * @method     ChildJobLocationQuery groupByState() Group by the state column
 * @method     ChildJobLocationQuery groupByStateCode() Group by the statecode column
 * @method     ChildJobLocationQuery groupByCountry() Group by the country column
 * @method     ChildJobLocationQuery groupByCountryCode() Group by the countrycode column
 * @method     ChildJobLocationQuery groupByAlternateNames() Group by the alternate_names column
 * @method     ChildJobLocationQuery groupByOpenStreetMapId() Group by the openstreetmap_id column
 *
 * @method     ChildJobLocationQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildJobLocationQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildJobLocationQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildJobLocationQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildJobLocationQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildJobLocationQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildJobLocationQuery leftJoinJobPosting($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobPosting relation
 * @method     ChildJobLocationQuery rightJoinJobPosting($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobPosting relation
 * @method     ChildJobLocationQuery innerJoinJobPosting($relationAlias = null) Adds a INNER JOIN clause to the query using the JobPosting relation
 *
 * @method     ChildJobLocationQuery joinWithJobPosting($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobPosting relation
 *
 * @method     ChildJobLocationQuery leftJoinWithJobPosting() Adds a LEFT JOIN clause and with to the query using the JobPosting relation
 * @method     ChildJobLocationQuery rightJoinWithJobPosting() Adds a RIGHT JOIN clause and with to the query using the JobPosting relation
 * @method     ChildJobLocationQuery innerJoinWithJobPosting() Adds a INNER JOIN clause and with to the query using the JobPosting relation
 *
 * @method     ChildJobLocationQuery leftJoinJobPlaceLookup($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobPlaceLookup relation
 * @method     ChildJobLocationQuery rightJoinJobPlaceLookup($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobPlaceLookup relation
 * @method     ChildJobLocationQuery innerJoinJobPlaceLookup($relationAlias = null) Adds a INNER JOIN clause to the query using the JobPlaceLookup relation
 *
 * @method     ChildJobLocationQuery joinWithJobPlaceLookup($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobPlaceLookup relation
 *
 * @method     ChildJobLocationQuery leftJoinWithJobPlaceLookup() Adds a LEFT JOIN clause and with to the query using the JobPlaceLookup relation
 * @method     ChildJobLocationQuery rightJoinWithJobPlaceLookup() Adds a RIGHT JOIN clause and with to the query using the JobPlaceLookup relation
 * @method     ChildJobLocationQuery innerJoinWithJobPlaceLookup() Adds a INNER JOIN clause and with to the query using the JobPlaceLookup relation
 *
 * @method     \JobScooper\JobPostingQuery|\JobScooper\JobPlaceLookupQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildJobLocation findOne(ConnectionInterface $con = null) Return the first ChildJobLocation matching the query
 * @method     ChildJobLocation findOneOrCreate(ConnectionInterface $con = null) Return the first ChildJobLocation matching the query, or a new ChildJobLocation object populated from the query conditions when no match is found
 *
 * @method     ChildJobLocation findOneByLocationId(int $location_id) Return the first ChildJobLocation filtered by the location_id column
 * @method     ChildJobLocation findOneByLatitude(double $lat) Return the first ChildJobLocation filtered by the lat column
 * @method     ChildJobLocation findOneByLogitude(double $lon) Return the first ChildJobLocation filtered by the lon column
 * @method     ChildJobLocation findOneByDisplayName(string $full_display_name) Return the first ChildJobLocation filtered by the full_display_name column
 * @method     ChildJobLocation findOneByPrimaryName(string $primary_name) Return the first ChildJobLocation filtered by the primary_name column
 * @method     ChildJobLocation findOneByPlace(string $place) Return the first ChildJobLocation filtered by the place column
 * @method     ChildJobLocation findOneByCounty(string $county) Return the first ChildJobLocation filtered by the county column
 * @method     ChildJobLocation findOneByState(string $state) Return the first ChildJobLocation filtered by the state column
 * @method     ChildJobLocation findOneByStateCode(string $statecode) Return the first ChildJobLocation filtered by the statecode column
 * @method     ChildJobLocation findOneByCountry(string $country) Return the first ChildJobLocation filtered by the country column
 * @method     ChildJobLocation findOneByCountryCode(string $countrycode) Return the first ChildJobLocation filtered by the countrycode column
 * @method     ChildJobLocation findOneByAlternateNames(array $alternate_names) Return the first ChildJobLocation filtered by the alternate_names column
 * @method     ChildJobLocation findOneByOpenStreetMapId(int $openstreetmap_id) Return the first ChildJobLocation filtered by the openstreetmap_id column *

 * @method     ChildJobLocation requirePk($key, ConnectionInterface $con = null) Return the ChildJobLocation by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOne(ConnectionInterface $con = null) Return the first ChildJobLocation matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobLocation requireOneByLocationId(int $location_id) Return the first ChildJobLocation filtered by the location_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByLatitude(double $lat) Return the first ChildJobLocation filtered by the lat column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByLogitude(double $lon) Return the first ChildJobLocation filtered by the lon column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByDisplayName(string $full_display_name) Return the first ChildJobLocation filtered by the full_display_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByPrimaryName(string $primary_name) Return the first ChildJobLocation filtered by the primary_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByPlace(string $place) Return the first ChildJobLocation filtered by the place column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByCounty(string $county) Return the first ChildJobLocation filtered by the county column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByState(string $state) Return the first ChildJobLocation filtered by the state column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByStateCode(string $statecode) Return the first ChildJobLocation filtered by the statecode column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByCountry(string $country) Return the first ChildJobLocation filtered by the country column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByCountryCode(string $countrycode) Return the first ChildJobLocation filtered by the countrycode column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByAlternateNames(array $alternate_names) Return the first ChildJobLocation filtered by the alternate_names column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobLocation requireOneByOpenStreetMapId(int $openstreetmap_id) Return the first ChildJobLocation filtered by the openstreetmap_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobLocation[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildJobLocation objects based on current ModelCriteria
 * @method     ChildJobLocation[]|ObjectCollection findByLocationId(int $location_id) Return ChildJobLocation objects filtered by the location_id column
 * @method     ChildJobLocation[]|ObjectCollection findByLatitude(double $lat) Return ChildJobLocation objects filtered by the lat column
 * @method     ChildJobLocation[]|ObjectCollection findByLogitude(double $lon) Return ChildJobLocation objects filtered by the lon column
 * @method     ChildJobLocation[]|ObjectCollection findByDisplayName(string $full_display_name) Return ChildJobLocation objects filtered by the full_display_name column
 * @method     ChildJobLocation[]|ObjectCollection findByPrimaryName(string $primary_name) Return ChildJobLocation objects filtered by the primary_name column
 * @method     ChildJobLocation[]|ObjectCollection findByPlace(string $place) Return ChildJobLocation objects filtered by the place column
 * @method     ChildJobLocation[]|ObjectCollection findByCounty(string $county) Return ChildJobLocation objects filtered by the county column
 * @method     ChildJobLocation[]|ObjectCollection findByState(string $state) Return ChildJobLocation objects filtered by the state column
 * @method     ChildJobLocation[]|ObjectCollection findByStateCode(string $statecode) Return ChildJobLocation objects filtered by the statecode column
 * @method     ChildJobLocation[]|ObjectCollection findByCountry(string $country) Return ChildJobLocation objects filtered by the country column
 * @method     ChildJobLocation[]|ObjectCollection findByCountryCode(string $countrycode) Return ChildJobLocation objects filtered by the countrycode column
 * @method     ChildJobLocation[]|ObjectCollection findByAlternateNames(array $alternate_names) Return ChildJobLocation objects filtered by the alternate_names column
 * @method     ChildJobLocation[]|ObjectCollection findByOpenStreetMapId(int $openstreetmap_id) Return ChildJobLocation objects filtered by the openstreetmap_id column
 * @method     ChildJobLocation[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class JobLocationQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\Base\JobLocationQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\JobLocation', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildJobLocationQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildJobLocationQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildJobLocationQuery) {
            return $criteria;
        }
        $query = new ChildJobLocationQuery();
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
     * @return ChildJobLocation|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(JobLocationTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = JobLocationTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
     * @return ChildJobLocation A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT location_id, lat, lon, full_display_name, primary_name, place, county, state, statecode, country, countrycode, alternate_names, openstreetmap_id FROM job_location WHERE location_id = :p0';
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
            /** @var ChildJobLocation $obj */
            $obj = new ChildJobLocation();
            $obj->hydrate($row);
            JobLocationTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return ChildJobLocation|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(JobLocationTableMap::COL_LOCATION_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(JobLocationTableMap::COL_LOCATION_ID, $keys, Criteria::IN);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByLocationId($locationId = null, $comparison = null)
    {
        if (is_array($locationId)) {
            $useMinMax = false;
            if (isset($locationId['min'])) {
                $this->addUsingAlias(JobLocationTableMap::COL_LOCATION_ID, $locationId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($locationId['max'])) {
                $this->addUsingAlias(JobLocationTableMap::COL_LOCATION_ID, $locationId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_LOCATION_ID, $locationId, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByLatitude($latitude = null, $comparison = null)
    {
        if (is_array($latitude)) {
            $useMinMax = false;
            if (isset($latitude['min'])) {
                $this->addUsingAlias(JobLocationTableMap::COL_LAT, $latitude['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($latitude['max'])) {
                $this->addUsingAlias(JobLocationTableMap::COL_LAT, $latitude['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_LAT, $latitude, $comparison);
    }

    /**
     * Filter the query on the lon column
     *
     * Example usage:
     * <code>
     * $query->filterByLogitude(1234); // WHERE lon = 1234
     * $query->filterByLogitude(array(12, 34)); // WHERE lon IN (12, 34)
     * $query->filterByLogitude(array('min' => 12)); // WHERE lon > 12
     * </code>
     *
     * @param     mixed $logitude The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByLogitude($logitude = null, $comparison = null)
    {
        if (is_array($logitude)) {
            $useMinMax = false;
            if (isset($logitude['min'])) {
                $this->addUsingAlias(JobLocationTableMap::COL_LON, $logitude['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($logitude['max'])) {
                $this->addUsingAlias(JobLocationTableMap::COL_LON, $logitude['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_LON, $logitude, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByDisplayName($displayName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($displayName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_FULL_DISPLAY_NAME, $displayName, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByPrimaryName($primaryName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($primaryName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_PRIMARY_NAME, $primaryName, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByPlace($place = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($place)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_PLACE, $place, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByCounty($county = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($county)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_COUNTY, $county, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByState($state = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($state)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_STATE, $state, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByStateCode($stateCode = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($stateCode)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_STATECODE, $stateCode, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByCountry($country = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($country)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_COUNTRY, $country, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByCountryCode($countryCode = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($countryCode)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_COUNTRYCODE, $countryCode, $comparison);
    }

    /**
     * Filter the query on the alternate_names column
     *
     * @param     array $alternateNames The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByAlternateNames($alternateNames = null, $comparison = null)
    {
        $key = $this->getAliasedColName(JobLocationTableMap::COL_ALTERNATE_NAMES);
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

        return $this->addUsingAlias(JobLocationTableMap::COL_ALTERNATE_NAMES, $alternateNames, $comparison);
    }

    /**
     * Filter the query on the alternate_names column
     * @param     mixed $alternateNames The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
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
            $key = $this->getAliasedColName(JobLocationTableMap::COL_ALTERNATE_NAMES);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $alternateNames, $comparison);
            } else {
                $this->addAnd($key, $alternateNames, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_ALTERNATE_NAMES, $alternateNames, $comparison);
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
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByOpenStreetMapId($openStreetMapId = null, $comparison = null)
    {
        if (is_array($openStreetMapId)) {
            $useMinMax = false;
            if (isset($openStreetMapId['min'])) {
                $this->addUsingAlias(JobLocationTableMap::COL_OPENSTREETMAP_ID, $openStreetMapId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($openStreetMapId['max'])) {
                $this->addUsingAlias(JobLocationTableMap::COL_OPENSTREETMAP_ID, $openStreetMapId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobLocationTableMap::COL_OPENSTREETMAP_ID, $openStreetMapId, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\JobPosting object
     *
     * @param \JobScooper\JobPosting|ObjectCollection $jobPosting the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByJobPosting($jobPosting, $comparison = null)
    {
        if ($jobPosting instanceof \JobScooper\JobPosting) {
            return $this
                ->addUsingAlias(JobLocationTableMap::COL_LOCATION_ID, $jobPosting->getJobLocationId(), $comparison);
        } elseif ($jobPosting instanceof ObjectCollection) {
            return $this
                ->useJobPostingQuery()
                ->filterByPrimaryKeys($jobPosting->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByJobPosting() only accepts arguments of type \JobScooper\JobPosting or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the JobPosting relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
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
     * @return \JobScooper\JobPostingQuery A secondary query class using the current class as primary query
     */
    public function useJobPostingQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinJobPosting($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobPosting', '\JobScooper\JobPostingQuery');
    }

    /**
     * Filter the query by a related \JobScooper\JobPlaceLookup object
     *
     * @param \JobScooper\JobPlaceLookup|ObjectCollection $jobPlaceLookup the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildJobLocationQuery The current query, for fluid interface
     */
    public function filterByJobPlaceLookup($jobPlaceLookup, $comparison = null)
    {
        if ($jobPlaceLookup instanceof \JobScooper\JobPlaceLookup) {
            return $this
                ->addUsingAlias(JobLocationTableMap::COL_LOCATION_ID, $jobPlaceLookup->getLocationId(), $comparison);
        } elseif ($jobPlaceLookup instanceof ObjectCollection) {
            return $this
                ->useJobPlaceLookupQuery()
                ->filterByPrimaryKeys($jobPlaceLookup->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByJobPlaceLookup() only accepts arguments of type \JobScooper\JobPlaceLookup or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the JobPlaceLookup relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function joinJobPlaceLookup($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('JobPlaceLookup');

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
            $this->addJoinObject($join, 'JobPlaceLookup');
        }

        return $this;
    }

    /**
     * Use the JobPlaceLookup relation JobPlaceLookup object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\JobPlaceLookupQuery A secondary query class using the current class as primary query
     */
    public function useJobPlaceLookupQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinJobPlaceLookup($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobPlaceLookup', '\JobScooper\JobPlaceLookupQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildJobLocation $jobLocation Object to remove from the list of results
     *
     * @return $this|ChildJobLocationQuery The current query, for fluid interface
     */
    public function prune($jobLocation = null)
    {
        if ($jobLocation) {
            $this->addUsingAlias(JobLocationTableMap::COL_LOCATION_ID, $jobLocation->getLocationId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the job_location table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobLocationTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            JobLocationTableMap::clearInstancePool();
            JobLocationTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(JobLocationTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(JobLocationTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            JobLocationTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            JobLocationTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // JobLocationQuery
