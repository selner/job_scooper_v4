<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\UserSearchRun as ChildUserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\DataAccess\Map\UserSearchRunTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'user_search_run' table.
 *
 *
 *
 * @method     ChildUserSearchRunQuery orderByUserSearchRunId($order = Criteria::ASC) Order by the user_search_run_id column
 * @method     ChildUserSearchRunQuery orderBySearchKey($order = Criteria::ASC) Order by the search_key column
 * @method     ChildUserSearchRunQuery orderByUserSlug($order = Criteria::ASC) Order by the user_slug column
 * @method     ChildUserSearchRunQuery orderByGeoLocationId($order = Criteria::ASC) Order by the geolocation_id column
 * @method     ChildUserSearchRunQuery orderByJobSiteKey($order = Criteria::ASC) Order by the jobsite_key column
 * @method     ChildUserSearchRunQuery orderByUserSearchRunKey($order = Criteria::ASC) Order by the user_search_run_key column
 * @method     ChildUserSearchRunQuery orderBySearchParametersData($order = Criteria::ASC) Order by the search_parameters_data column
 * @method     ChildUserSearchRunQuery orderByAppRunId($order = Criteria::ASC) Order by the last_app_run_id column
 * @method     ChildUserSearchRunQuery orderByRunResultCode($order = Criteria::ASC) Order by the run_result column
 * @method     ChildUserSearchRunQuery orderByRunErrorDetails($order = Criteria::ASC) Order by the run_error_details column
 * @method     ChildUserSearchRunQuery orderByCreatedAt($order = Criteria::ASC) Order by the date_created column
 * @method     ChildUserSearchRunQuery orderByUpdatedAt($order = Criteria::ASC) Order by the date_updated column
 * @method     ChildUserSearchRunQuery orderByLastRunAt($order = Criteria::ASC) Order by the date_last_run column
 * @method     ChildUserSearchRunQuery orderByStartNextRunAfter($order = Criteria::ASC) Order by the date_next_run column
 * @method     ChildUserSearchRunQuery orderByLastFailedAt($order = Criteria::ASC) Order by the date_last_failed column
 *
 * @method     ChildUserSearchRunQuery groupByUserSearchRunId() Group by the user_search_run_id column
 * @method     ChildUserSearchRunQuery groupBySearchKey() Group by the search_key column
 * @method     ChildUserSearchRunQuery groupByUserSlug() Group by the user_slug column
 * @method     ChildUserSearchRunQuery groupByGeoLocationId() Group by the geolocation_id column
 * @method     ChildUserSearchRunQuery groupByJobSiteKey() Group by the jobsite_key column
 * @method     ChildUserSearchRunQuery groupByUserSearchRunKey() Group by the user_search_run_key column
 * @method     ChildUserSearchRunQuery groupBySearchParametersData() Group by the search_parameters_data column
 * @method     ChildUserSearchRunQuery groupByAppRunId() Group by the last_app_run_id column
 * @method     ChildUserSearchRunQuery groupByRunResultCode() Group by the run_result column
 * @method     ChildUserSearchRunQuery groupByRunErrorDetails() Group by the run_error_details column
 * @method     ChildUserSearchRunQuery groupByCreatedAt() Group by the date_created column
 * @method     ChildUserSearchRunQuery groupByUpdatedAt() Group by the date_updated column
 * @method     ChildUserSearchRunQuery groupByLastRunAt() Group by the date_last_run column
 * @method     ChildUserSearchRunQuery groupByStartNextRunAfter() Group by the date_next_run column
 * @method     ChildUserSearchRunQuery groupByLastFailedAt() Group by the date_last_failed column
 *
 * @method     ChildUserSearchRunQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchRunQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchRunQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchRunQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchRunQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchRunQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserSearchRunQuery leftJoinGeoLocation($relationAlias = null) Adds a LEFT JOIN clause to the query using the GeoLocation relation
 * @method     ChildUserSearchRunQuery rightJoinGeoLocation($relationAlias = null) Adds a RIGHT JOIN clause to the query using the GeoLocation relation
 * @method     ChildUserSearchRunQuery innerJoinGeoLocation($relationAlias = null) Adds a INNER JOIN clause to the query using the GeoLocation relation
 *
 * @method     ChildUserSearchRunQuery joinWithGeoLocation($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the GeoLocation relation
 *
 * @method     ChildUserSearchRunQuery leftJoinWithGeoLocation() Adds a LEFT JOIN clause and with to the query using the GeoLocation relation
 * @method     ChildUserSearchRunQuery rightJoinWithGeoLocation() Adds a RIGHT JOIN clause and with to the query using the GeoLocation relation
 * @method     ChildUserSearchRunQuery innerJoinWithGeoLocation() Adds a INNER JOIN clause and with to the query using the GeoLocation relation
 *
 * @method     ChildUserSearchRunQuery leftJoinJobSitePlugin($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobSitePlugin relation
 * @method     ChildUserSearchRunQuery rightJoinJobSitePlugin($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobSitePlugin relation
 * @method     ChildUserSearchRunQuery innerJoinJobSitePlugin($relationAlias = null) Adds a INNER JOIN clause to the query using the JobSitePlugin relation
 *
 * @method     ChildUserSearchRunQuery joinWithJobSitePlugin($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobSitePlugin relation
 *
 * @method     ChildUserSearchRunQuery leftJoinWithJobSitePlugin() Adds a LEFT JOIN clause and with to the query using the JobSitePlugin relation
 * @method     ChildUserSearchRunQuery rightJoinWithJobSitePlugin() Adds a RIGHT JOIN clause and with to the query using the JobSitePlugin relation
 * @method     ChildUserSearchRunQuery innerJoinWithJobSitePlugin() Adds a INNER JOIN clause and with to the query using the JobSitePlugin relation
 *
 * @method     ChildUserSearchRunQuery leftJoinUser($relationAlias = null) Adds a LEFT JOIN clause to the query using the User relation
 * @method     ChildUserSearchRunQuery rightJoinUser($relationAlias = null) Adds a RIGHT JOIN clause to the query using the User relation
 * @method     ChildUserSearchRunQuery innerJoinUser($relationAlias = null) Adds a INNER JOIN clause to the query using the User relation
 *
 * @method     ChildUserSearchRunQuery joinWithUser($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the User relation
 *
 * @method     ChildUserSearchRunQuery leftJoinWithUser() Adds a LEFT JOIN clause and with to the query using the User relation
 * @method     ChildUserSearchRunQuery rightJoinWithUser() Adds a RIGHT JOIN clause and with to the query using the User relation
 * @method     ChildUserSearchRunQuery innerJoinWithUser() Adds a INNER JOIN clause and with to the query using the User relation
 *
 * @method     \JobScooper\DataAccess\GeoLocationQuery|\JobScooper\DataAccess\JobSitePluginQuery|\JobScooper\DataAccess\UserQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearchRun findOne(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query
 * @method     ChildUserSearchRun findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query, or a new ChildUserSearchRun object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearchRun findOneByUserSearchRunId(int $user_search_run_id) Return the first ChildUserSearchRun filtered by the user_search_run_id column
 * @method     ChildUserSearchRun findOneBySearchKey(string $search_key) Return the first ChildUserSearchRun filtered by the search_key column
 * @method     ChildUserSearchRun findOneByUserSlug(string $user_slug) Return the first ChildUserSearchRun filtered by the user_slug column
 * @method     ChildUserSearchRun findOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearchRun filtered by the geolocation_id column
 * @method     ChildUserSearchRun findOneByJobSiteKey(string $jobsite_key) Return the first ChildUserSearchRun filtered by the jobsite_key column
 * @method     ChildUserSearchRun findOneByUserSearchRunKey(string $user_search_run_key) Return the first ChildUserSearchRun filtered by the user_search_run_key column
 * @method     ChildUserSearchRun findOneBySearchParametersData(string $search_parameters_data) Return the first ChildUserSearchRun filtered by the search_parameters_data column
 * @method     ChildUserSearchRun findOneByAppRunId(string $last_app_run_id) Return the first ChildUserSearchRun filtered by the last_app_run_id column
 * @method     ChildUserSearchRun findOneByRunResultCode(int $run_result) Return the first ChildUserSearchRun filtered by the run_result column
 * @method     ChildUserSearchRun findOneByRunErrorDetails(array $run_error_details) Return the first ChildUserSearchRun filtered by the run_error_details column
 * @method     ChildUserSearchRun findOneByCreatedAt(string $date_created) Return the first ChildUserSearchRun filtered by the date_created column
 * @method     ChildUserSearchRun findOneByUpdatedAt(string $date_updated) Return the first ChildUserSearchRun filtered by the date_updated column
 * @method     ChildUserSearchRun findOneByLastRunAt(string $date_last_run) Return the first ChildUserSearchRun filtered by the date_last_run column
 * @method     ChildUserSearchRun findOneByStartNextRunAfter(string $date_next_run) Return the first ChildUserSearchRun filtered by the date_next_run column
 * @method     ChildUserSearchRun findOneByLastFailedAt(string $date_last_failed) Return the first ChildUserSearchRun filtered by the date_last_failed column *

 * @method     ChildUserSearchRun requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearchRun by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOne(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchRun requireOneByUserSearchRunId(int $user_search_run_id) Return the first ChildUserSearchRun filtered by the user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneBySearchKey(string $search_key) Return the first ChildUserSearchRun filtered by the search_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByUserSlug(string $user_slug) Return the first ChildUserSearchRun filtered by the user_slug column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearchRun filtered by the geolocation_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByJobSiteKey(string $jobsite_key) Return the first ChildUserSearchRun filtered by the jobsite_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByUserSearchRunKey(string $user_search_run_key) Return the first ChildUserSearchRun filtered by the user_search_run_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneBySearchParametersData(string $search_parameters_data) Return the first ChildUserSearchRun filtered by the search_parameters_data column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByAppRunId(string $last_app_run_id) Return the first ChildUserSearchRun filtered by the last_app_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByRunResultCode(int $run_result) Return the first ChildUserSearchRun filtered by the run_result column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByRunErrorDetails(array $run_error_details) Return the first ChildUserSearchRun filtered by the run_error_details column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByCreatedAt(string $date_created) Return the first ChildUserSearchRun filtered by the date_created column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByUpdatedAt(string $date_updated) Return the first ChildUserSearchRun filtered by the date_updated column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByLastRunAt(string $date_last_run) Return the first ChildUserSearchRun filtered by the date_last_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByStartNextRunAfter(string $date_next_run) Return the first ChildUserSearchRun filtered by the date_next_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByLastFailedAt(string $date_last_failed) Return the first ChildUserSearchRun filtered by the date_last_failed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchRun[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearchRun objects based on current ModelCriteria
 * @method     ChildUserSearchRun[]|ObjectCollection findByUserSearchRunId(int $user_search_run_id) Return ChildUserSearchRun objects filtered by the user_search_run_id column
 * @method     ChildUserSearchRun[]|ObjectCollection findBySearchKey(string $search_key) Return ChildUserSearchRun objects filtered by the search_key column
 * @method     ChildUserSearchRun[]|ObjectCollection findByUserSlug(string $user_slug) Return ChildUserSearchRun objects filtered by the user_slug column
 * @method     ChildUserSearchRun[]|ObjectCollection findByGeoLocationId(int $geolocation_id) Return ChildUserSearchRun objects filtered by the geolocation_id column
 * @method     ChildUserSearchRun[]|ObjectCollection findByJobSiteKey(string $jobsite_key) Return ChildUserSearchRun objects filtered by the jobsite_key column
 * @method     ChildUserSearchRun[]|ObjectCollection findByUserSearchRunKey(string $user_search_run_key) Return ChildUserSearchRun objects filtered by the user_search_run_key column
 * @method     ChildUserSearchRun[]|ObjectCollection findBySearchParametersData(string $search_parameters_data) Return ChildUserSearchRun objects filtered by the search_parameters_data column
 * @method     ChildUserSearchRun[]|ObjectCollection findByAppRunId(string $last_app_run_id) Return ChildUserSearchRun objects filtered by the last_app_run_id column
 * @method     ChildUserSearchRun[]|ObjectCollection findByRunResultCode(int $run_result) Return ChildUserSearchRun objects filtered by the run_result column
 * @method     ChildUserSearchRun[]|ObjectCollection findByRunErrorDetails(array $run_error_details) Return ChildUserSearchRun objects filtered by the run_error_details column
 * @method     ChildUserSearchRun[]|ObjectCollection findByCreatedAt(string $date_created) Return ChildUserSearchRun objects filtered by the date_created column
 * @method     ChildUserSearchRun[]|ObjectCollection findByUpdatedAt(string $date_updated) Return ChildUserSearchRun objects filtered by the date_updated column
 * @method     ChildUserSearchRun[]|ObjectCollection findByLastRunAt(string $date_last_run) Return ChildUserSearchRun objects filtered by the date_last_run column
 * @method     ChildUserSearchRun[]|ObjectCollection findByStartNextRunAfter(string $date_next_run) Return ChildUserSearchRun objects filtered by the date_next_run column
 * @method     ChildUserSearchRun[]|ObjectCollection findByLastFailedAt(string $date_last_failed) Return ChildUserSearchRun objects filtered by the date_last_failed column
 * @method     ChildUserSearchRun[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchRunQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\DataAccess\Base\UserSearchRunQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\DataAccess\\UserSearchRun', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUserSearchRunQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildUserSearchRunQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildUserSearchRunQuery) {
            return $criteria;
        }
        $query = new ChildUserSearchRunQuery();
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
     * @return ChildUserSearchRun|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchRunTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = UserSearchRunTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
     * @return ChildUserSearchRun A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT user_search_run_id, search_key, user_slug, geolocation_id, jobsite_key, user_search_run_key, search_parameters_data, last_app_run_id, run_result, run_error_details, date_created, date_updated, date_last_run, date_next_run, date_last_failed FROM user_search_run WHERE user_search_run_id = :p0';
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
            /** @var ChildUserSearchRun $obj */
            $obj = new ChildUserSearchRun();
            $obj->hydrate($row);
            UserSearchRunTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return ChildUserSearchRun|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the user_search_run_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUserSearchRunId(1234); // WHERE user_search_run_id = 1234
     * $query->filterByUserSearchRunId(array(12, 34)); // WHERE user_search_run_id IN (12, 34)
     * $query->filterByUserSearchRunId(array('min' => 12)); // WHERE user_search_run_id > 12
     * </code>
     *
     * @param     mixed $userSearchRunId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUserSearchRunId($userSearchRunId = null, $comparison = null)
    {
        if (is_array($userSearchRunId)) {
            $useMinMax = false;
            if (isset($userSearchRunId['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRunId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userSearchRunId['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRunId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRunId, $comparison);
    }

    /**
     * Filter the query on the search_key column
     *
     * Example usage:
     * <code>
     * $query->filterBySearchKey('fooValue');   // WHERE search_key = 'fooValue'
     * $query->filterBySearchKey('%fooValue%', Criteria::LIKE); // WHERE search_key LIKE '%fooValue%'
     * </code>
     *
     * @param     string $searchKey The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterBySearchKey($searchKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($searchKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_SEARCH_KEY, $searchKey, $comparison);
    }

    /**
     * Filter the query on the user_slug column
     *
     * Example usage:
     * <code>
     * $query->filterByUserSlug('fooValue');   // WHERE user_slug = 'fooValue'
     * $query->filterByUserSlug('%fooValue%', Criteria::LIKE); // WHERE user_slug LIKE '%fooValue%'
     * </code>
     *
     * @param     string $userSlug The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUserSlug($userSlug = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userSlug)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SLUG, $userSlug, $comparison);
    }

    /**
     * Filter the query on the geolocation_id column
     *
     * Example usage:
     * <code>
     * $query->filterByGeoLocationId(1234); // WHERE geolocation_id = 1234
     * $query->filterByGeoLocationId(array(12, 34)); // WHERE geolocation_id IN (12, 34)
     * $query->filterByGeoLocationId(array('min' => 12)); // WHERE geolocation_id > 12
     * </code>
     *
     * @see       filterByGeoLocation()
     *
     * @param     mixed $geoLocationId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByGeoLocationId($geoLocationId = null, $comparison = null)
    {
        if (is_array($geoLocationId)) {
            $useMinMax = false;
            if (isset($geoLocationId['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_GEOLOCATION_ID, $geoLocationId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($geoLocationId['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_GEOLOCATION_ID, $geoLocationId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_GEOLOCATION_ID, $geoLocationId, $comparison);
    }

    /**
     * Filter the query on the jobsite_key column
     *
     * Example usage:
     * <code>
     * $query->filterByJobSiteKey('fooValue');   // WHERE jobsite_key = 'fooValue'
     * $query->filterByJobSiteKey('%fooValue%', Criteria::LIKE); // WHERE jobsite_key LIKE '%fooValue%'
     * </code>
     *
     * @param     string $jobSiteKey The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByJobSiteKey($jobSiteKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobSiteKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_JOBSITE_KEY, $jobSiteKey, $comparison);
    }

    /**
     * Filter the query on the user_search_run_key column
     *
     * Example usage:
     * <code>
     * $query->filterByUserSearchRunKey('fooValue');   // WHERE user_search_run_key = 'fooValue'
     * $query->filterByUserSearchRunKey('%fooValue%', Criteria::LIKE); // WHERE user_search_run_key LIKE '%fooValue%'
     * </code>
     *
     * @param     string $userSearchRunKey The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUserSearchRunKey($userSearchRunKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userSearchRunKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY, $userSearchRunKey, $comparison);
    }

    /**
     * Filter the query on the search_parameters_data column
     *
     * Example usage:
     * <code>
     * $query->filterBySearchParametersData('fooValue');   // WHERE search_parameters_data = 'fooValue'
     * $query->filterBySearchParametersData('%fooValue%', Criteria::LIKE); // WHERE search_parameters_data LIKE '%fooValue%'
     * </code>
     *
     * @param     string $searchParametersData The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterBySearchParametersData($searchParametersData = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($searchParametersData)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_SEARCH_PARAMETERS_DATA, $searchParametersData, $comparison);
    }

    /**
     * Filter the query on the last_app_run_id column
     *
     * Example usage:
     * <code>
     * $query->filterByAppRunId('fooValue');   // WHERE last_app_run_id = 'fooValue'
     * $query->filterByAppRunId('%fooValue%', Criteria::LIKE); // WHERE last_app_run_id LIKE '%fooValue%'
     * </code>
     *
     * @param     string $appRunId The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByAppRunId($appRunId = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($appRunId)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_LAST_APP_RUN_ID, $appRunId, $comparison);
    }

    /**
     * Filter the query on the run_result column
     *
     * @param     mixed $runResultCode The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByRunResultCode($runResultCode = null, $comparison = null)
    {
        $valueSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT);
        if (is_scalar($runResultCode)) {
            if (!in_array($runResultCode, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $runResultCode));
            }
            $runResultCode = array_search($runResultCode, $valueSet);
        } elseif (is_array($runResultCode)) {
            $convertedValues = array();
            foreach ($runResultCode as $value) {
                if (!in_array($value, $valueSet)) {
                    throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $value));
                }
                $convertedValues []= array_search($value, $valueSet);
            }
            $runResultCode = $convertedValues;
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_RUN_RESULT, $runResultCode, $comparison);
    }

    /**
     * Filter the query on the run_error_details column
     *
     * @param     array $runErrorDetails The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByRunErrorDetails($runErrorDetails = null, $comparison = null)
    {
        $key = $this->getAliasedColName(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS);
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            foreach ($runErrorDetails as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_SOME) {
            foreach ($runErrorDetails as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addOr($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            foreach ($runErrorDetails as $value) {
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

        return $this->addUsingAlias(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS, $runErrorDetails, $comparison);
    }

    /**
     * Filter the query on the run_error_details column
     * @param     mixed $runErrorDetails The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByRunErrorDetail($runErrorDetails = null, $comparison = null)
    {
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            if (is_scalar($runErrorDetails)) {
                $runErrorDetails = '%| ' . $runErrorDetails . ' |%';
                $comparison = Criteria::LIKE;
            }
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            $runErrorDetails = '%| ' . $runErrorDetails . ' |%';
            $comparison = Criteria::NOT_LIKE;
            $key = $this->getAliasedColName(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $runErrorDetails, $comparison);
            } else {
                $this->addAnd($key, $runErrorDetails, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS, $runErrorDetails, $comparison);
    }

    /**
     * Filter the query on the date_created column
     *
     * Example usage:
     * <code>
     * $query->filterByCreatedAt('2011-03-14'); // WHERE date_created = '2011-03-14'
     * $query->filterByCreatedAt('now'); // WHERE date_created = '2011-03-14'
     * $query->filterByCreatedAt(array('max' => 'yesterday')); // WHERE date_created > '2011-03-13'
     * </code>
     *
     * @param     mixed $createdAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByCreatedAt($createdAt = null, $comparison = null)
    {
        if (is_array($createdAt)) {
            $useMinMax = false;
            if (isset($createdAt['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_CREATED, $createdAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($createdAt['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_CREATED, $createdAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_CREATED, $createdAt, $comparison);
    }

    /**
     * Filter the query on the date_updated column
     *
     * Example usage:
     * <code>
     * $query->filterByUpdatedAt('2011-03-14'); // WHERE date_updated = '2011-03-14'
     * $query->filterByUpdatedAt('now'); // WHERE date_updated = '2011-03-14'
     * $query->filterByUpdatedAt(array('max' => 'yesterday')); // WHERE date_updated > '2011-03-13'
     * </code>
     *
     * @param     mixed $updatedAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUpdatedAt($updatedAt = null, $comparison = null)
    {
        if (is_array($updatedAt)) {
            $useMinMax = false;
            if (isset($updatedAt['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_UPDATED, $updatedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($updatedAt['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_UPDATED, $updatedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_UPDATED, $updatedAt, $comparison);
    }

    /**
     * Filter the query on the date_last_run column
     *
     * Example usage:
     * <code>
     * $query->filterByLastRunAt('2011-03-14'); // WHERE date_last_run = '2011-03-14'
     * $query->filterByLastRunAt('now'); // WHERE date_last_run = '2011-03-14'
     * $query->filterByLastRunAt(array('max' => 'yesterday')); // WHERE date_last_run > '2011-03-13'
     * </code>
     *
     * @param     mixed $lastRunAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByLastRunAt($lastRunAt = null, $comparison = null)
    {
        if (is_array($lastRunAt)) {
            $useMinMax = false;
            if (isset($lastRunAt['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_LAST_RUN, $lastRunAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastRunAt['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_LAST_RUN, $lastRunAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_LAST_RUN, $lastRunAt, $comparison);
    }

    /**
     * Filter the query on the date_next_run column
     *
     * Example usage:
     * <code>
     * $query->filterByStartNextRunAfter('2011-03-14'); // WHERE date_next_run = '2011-03-14'
     * $query->filterByStartNextRunAfter('now'); // WHERE date_next_run = '2011-03-14'
     * $query->filterByStartNextRunAfter(array('max' => 'yesterday')); // WHERE date_next_run > '2011-03-13'
     * </code>
     *
     * @param     mixed $startNextRunAfter The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByStartNextRunAfter($startNextRunAfter = null, $comparison = null)
    {
        if (is_array($startNextRunAfter)) {
            $useMinMax = false;
            if (isset($startNextRunAfter['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_NEXT_RUN, $startNextRunAfter['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($startNextRunAfter['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_NEXT_RUN, $startNextRunAfter['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_NEXT_RUN, $startNextRunAfter, $comparison);
    }

    /**
     * Filter the query on the date_last_failed column
     *
     * Example usage:
     * <code>
     * $query->filterByLastFailedAt('2011-03-14'); // WHERE date_last_failed = '2011-03-14'
     * $query->filterByLastFailedAt('now'); // WHERE date_last_failed = '2011-03-14'
     * $query->filterByLastFailedAt(array('max' => 'yesterday')); // WHERE date_last_failed > '2011-03-13'
     * </code>
     *
     * @param     mixed $lastFailedAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByLastFailedAt($lastFailedAt = null, $comparison = null)
    {
        if (is_array($lastFailedAt)) {
            $useMinMax = false;
            if (isset($lastFailedAt['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastFailedAt['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_LAST_FAILED, $lastFailedAt, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\GeoLocation object
     *
     * @param \JobScooper\DataAccess\GeoLocation|ObjectCollection $geoLocation The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByGeoLocation($geoLocation, $comparison = null)
    {
        if ($geoLocation instanceof \JobScooper\DataAccess\GeoLocation) {
            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_GEOLOCATION_ID, $geoLocation->getGeoLocationId(), $comparison);
        } elseif ($geoLocation instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_GEOLOCATION_ID, $geoLocation->toKeyValue('PrimaryKey', 'GeoLocationId'), $comparison);
        } else {
            throw new PropelException('filterByGeoLocation() only accepts arguments of type \JobScooper\DataAccess\GeoLocation or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the GeoLocation relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function joinGeoLocation($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('GeoLocation');

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
            $this->addJoinObject($join, 'GeoLocation');
        }

        return $this;
    }

    /**
     * Use the GeoLocation relation GeoLocation object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\GeoLocationQuery A secondary query class using the current class as primary query
     */
    public function useGeoLocationQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinGeoLocation($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'GeoLocation', '\JobScooper\DataAccess\GeoLocationQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\JobSitePlugin object
     *
     * @param \JobScooper\DataAccess\JobSitePlugin|ObjectCollection $jobSitePlugin The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByJobSitePlugin($jobSitePlugin, $comparison = null)
    {
        if ($jobSitePlugin instanceof \JobScooper\DataAccess\JobSitePlugin) {
            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_JOBSITE_KEY, $jobSitePlugin->getJobSiteKey(), $comparison);
        } elseif ($jobSitePlugin instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_JOBSITE_KEY, $jobSitePlugin->toKeyValue('PrimaryKey', 'JobSiteKey'), $comparison);
        } else {
            throw new PropelException('filterByJobSitePlugin() only accepts arguments of type \JobScooper\DataAccess\JobSitePlugin or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the JobSitePlugin relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function joinJobSitePlugin($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('JobSitePlugin');

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
            $this->addJoinObject($join, 'JobSitePlugin');
        }

        return $this;
    }

    /**
     * Use the JobSitePlugin relation JobSitePlugin object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\JobSitePluginQuery A secondary query class using the current class as primary query
     */
    public function useJobSitePluginQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinJobSitePlugin($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobSitePlugin', '\JobScooper\DataAccess\JobSitePluginQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\User object
     *
     * @param \JobScooper\DataAccess\User|ObjectCollection $user The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUser($user, $comparison = null)
    {
        if ($user instanceof \JobScooper\DataAccess\User) {
            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_USER_SLUG, $user->getUserSlug(), $comparison);
        } elseif ($user instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_USER_SLUG, $user->toKeyValue('PrimaryKey', 'UserSlug'), $comparison);
        } else {
            throw new PropelException('filterByUser() only accepts arguments of type \JobScooper\DataAccess\User or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the User relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function joinUser($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('User');

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
            $this->addJoinObject($join, 'User');
        }

        return $this;
    }

    /**
     * Use the User relation User object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserQuery A secondary query class using the current class as primary query
     */
    public function useUserQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUser($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'User', '\JobScooper\DataAccess\UserQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildUserSearchRun $userSearchRun Object to remove from the list of results
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function prune($userSearchRun = null)
    {
        if ($userSearchRun) {
            $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRun->getUserSearchRunId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the user_search_run table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UserSearchRunTableMap::clearInstancePool();
            UserSearchRunTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UserSearchRunTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            UserSearchRunTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            UserSearchRunTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // sluggable behavior

    /**
     * Filter the query on the slug column
     *
     * @param     string $slug The value to use as filter.
     *
     * @return    $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterBySlug($slug)
    {
        return $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY, $slug, Criteria::EQUAL);
    }

    /**
     * Find one object based on its slug
     *
     * @param     string $slug The value to use as filter.
     * @param     ConnectionInterface $con The optional connection object
     *
     * @return    ChildUserSearchRun the result, formatted by the current formatter
     */
    public function findOneBySlug($slug, $con = null)
    {
        return $this->filterBySlug($slug)->findOne($con);
    }

    // timestampable behavior

    /**
     * Filter by the latest updated
     *
     * @param      int $nbDays Maximum age of the latest update in days
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function recentlyUpdated($nbDays = 7)
    {
        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_UPDATED, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by update date desc
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function lastUpdatedFirst()
    {
        return $this->addDescendingOrderByColumn(UserSearchRunTableMap::COL_DATE_UPDATED);
    }

    /**
     * Order by update date asc
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function firstUpdatedFirst()
    {
        return $this->addAscendingOrderByColumn(UserSearchRunTableMap::COL_DATE_UPDATED);
    }

    /**
     * Order by create date desc
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function lastCreatedFirst()
    {
        return $this->addDescendingOrderByColumn(UserSearchRunTableMap::COL_DATE_CREATED);
    }

    /**
     * Filter by the latest created
     *
     * @param      int $nbDays Maximum age of in days
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function recentlyCreated($nbDays = 7)
    {
        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_CREATED, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by create date asc
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function firstCreatedFirst()
    {
        return $this->addAscendingOrderByColumn(UserSearchRunTableMap::COL_DATE_CREATED);
    }

} // UserSearchRunQuery
