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
 * @method     ChildUserSearchRunQuery orderByUserSearchId($order = Criteria::ASC) Order by the user_search_id column
 * @method     ChildUserSearchRunQuery orderByAppRunId($order = Criteria::ASC) Order by the app_run_id column
 * @method     ChildUserSearchRunQuery orderByUserSearchRunKey($order = Criteria::ASC) Order by the user_search_run_key column
 * @method     ChildUserSearchRunQuery orderByJobSiteKey($order = Criteria::ASC) Order by the jobsite_key column
 * @method     ChildUserSearchRunQuery orderBySearchStartUrl($order = Criteria::ASC) Order by the search_start_url column
 * @method     ChildUserSearchRunQuery orderByRunResultCode($order = Criteria::ASC) Order by the run_result_code column
 * @method     ChildUserSearchRunQuery orderByRunErrorDetails($order = Criteria::ASC) Order by the run_error_details column
 * @method     ChildUserSearchRunQuery orderByStartedAt($order = Criteria::ASC) Order by the date_started column
 * @method     ChildUserSearchRunQuery orderByEndedAt($order = Criteria::ASC) Order by the date_ended column
 *
 * @method     ChildUserSearchRunQuery groupByUserSearchRunId() Group by the user_search_run_id column
 * @method     ChildUserSearchRunQuery groupByUserSearchId() Group by the user_search_id column
 * @method     ChildUserSearchRunQuery groupByAppRunId() Group by the app_run_id column
 * @method     ChildUserSearchRunQuery groupByUserSearchRunKey() Group by the user_search_run_key column
 * @method     ChildUserSearchRunQuery groupByJobSiteKey() Group by the jobsite_key column
 * @method     ChildUserSearchRunQuery groupBySearchStartUrl() Group by the search_start_url column
 * @method     ChildUserSearchRunQuery groupByRunResultCode() Group by the run_result_code column
 * @method     ChildUserSearchRunQuery groupByRunErrorDetails() Group by the run_error_details column
 * @method     ChildUserSearchRunQuery groupByStartedAt() Group by the date_started column
 * @method     ChildUserSearchRunQuery groupByEndedAt() Group by the date_ended column
 *
 * @method     ChildUserSearchRunQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchRunQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchRunQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchRunQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchRunQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchRunQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserSearchRunQuery leftJoinUserSearch($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearch relation
 * @method     ChildUserSearchRunQuery rightJoinUserSearch($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearch relation
 * @method     ChildUserSearchRunQuery innerJoinUserSearch($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearch relation
 *
 * @method     ChildUserSearchRunQuery joinWithUserSearch($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearch relation
 *
 * @method     ChildUserSearchRunQuery leftJoinWithUserSearch() Adds a LEFT JOIN clause and with to the query using the UserSearch relation
 * @method     ChildUserSearchRunQuery rightJoinWithUserSearch() Adds a RIGHT JOIN clause and with to the query using the UserSearch relation
 * @method     ChildUserSearchRunQuery innerJoinWithUserSearch() Adds a INNER JOIN clause and with to the query using the UserSearch relation
 *
 * @method     ChildUserSearchRunQuery leftJoinJobSiteRecordRelatedByJobSiteKey($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobSiteRecordRelatedByJobSiteKey relation
 * @method     ChildUserSearchRunQuery rightJoinJobSiteRecordRelatedByJobSiteKey($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobSiteRecordRelatedByJobSiteKey relation
 * @method     ChildUserSearchRunQuery innerJoinJobSiteRecordRelatedByJobSiteKey($relationAlias = null) Adds a INNER JOIN clause to the query using the JobSiteRecordRelatedByJobSiteKey relation
 *
 * @method     ChildUserSearchRunQuery joinWithJobSiteRecordRelatedByJobSiteKey($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobSiteRecordRelatedByJobSiteKey relation
 *
 * @method     ChildUserSearchRunQuery leftJoinWithJobSiteRecordRelatedByJobSiteKey() Adds a LEFT JOIN clause and with to the query using the JobSiteRecordRelatedByJobSiteKey relation
 * @method     ChildUserSearchRunQuery rightJoinWithJobSiteRecordRelatedByJobSiteKey() Adds a RIGHT JOIN clause and with to the query using the JobSiteRecordRelatedByJobSiteKey relation
 * @method     ChildUserSearchRunQuery innerJoinWithJobSiteRecordRelatedByJobSiteKey() Adds a INNER JOIN clause and with to the query using the JobSiteRecordRelatedByJobSiteKey relation
 *
 * @method     ChildUserSearchRunQuery leftJoinJobSiteRecordRelatedByLastUserSearchRunId($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobSiteRecordRelatedByLastUserSearchRunId relation
 * @method     ChildUserSearchRunQuery rightJoinJobSiteRecordRelatedByLastUserSearchRunId($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobSiteRecordRelatedByLastUserSearchRunId relation
 * @method     ChildUserSearchRunQuery innerJoinJobSiteRecordRelatedByLastUserSearchRunId($relationAlias = null) Adds a INNER JOIN clause to the query using the JobSiteRecordRelatedByLastUserSearchRunId relation
 *
 * @method     ChildUserSearchRunQuery joinWithJobSiteRecordRelatedByLastUserSearchRunId($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobSiteRecordRelatedByLastUserSearchRunId relation
 *
 * @method     ChildUserSearchRunQuery leftJoinWithJobSiteRecordRelatedByLastUserSearchRunId() Adds a LEFT JOIN clause and with to the query using the JobSiteRecordRelatedByLastUserSearchRunId relation
 * @method     ChildUserSearchRunQuery rightJoinWithJobSiteRecordRelatedByLastUserSearchRunId() Adds a RIGHT JOIN clause and with to the query using the JobSiteRecordRelatedByLastUserSearchRunId relation
 * @method     ChildUserSearchRunQuery innerJoinWithJobSiteRecordRelatedByLastUserSearchRunId() Adds a INNER JOIN clause and with to the query using the JobSiteRecordRelatedByLastUserSearchRunId relation
 *
 * @method     \JobScooper\DataAccess\UserSearchQuery|\JobScooper\DataAccess\JobSiteRecordQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearchRun findOne(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query
 * @method     ChildUserSearchRun findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query, or a new ChildUserSearchRun object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearchRun findOneByUserSearchRunId(int $user_search_run_id) Return the first ChildUserSearchRun filtered by the user_search_run_id column
 * @method     ChildUserSearchRun findOneByUserSearchId(int $user_search_id) Return the first ChildUserSearchRun filtered by the user_search_id column
 * @method     ChildUserSearchRun findOneByAppRunId(string $app_run_id) Return the first ChildUserSearchRun filtered by the app_run_id column
 * @method     ChildUserSearchRun findOneByUserSearchRunKey(string $user_search_run_key) Return the first ChildUserSearchRun filtered by the user_search_run_key column
 * @method     ChildUserSearchRun findOneByJobSiteKey(string $jobsite_key) Return the first ChildUserSearchRun filtered by the jobsite_key column
 * @method     ChildUserSearchRun findOneBySearchStartUrl(string $search_start_url) Return the first ChildUserSearchRun filtered by the search_start_url column
 * @method     ChildUserSearchRun findOneByRunResultCode(int $run_result_code) Return the first ChildUserSearchRun filtered by the run_result_code column
 * @method     ChildUserSearchRun findOneByRunErrorDetails(array $run_error_details) Return the first ChildUserSearchRun filtered by the run_error_details column
 * @method     ChildUserSearchRun findOneByStartedAt(string $date_started) Return the first ChildUserSearchRun filtered by the date_started column
 * @method     ChildUserSearchRun findOneByEndedAt(string $date_ended) Return the first ChildUserSearchRun filtered by the date_ended column *

 * @method     ChildUserSearchRun requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearchRun by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOne(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchRun requireOneByUserSearchRunId(int $user_search_run_id) Return the first ChildUserSearchRun filtered by the user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByUserSearchId(int $user_search_id) Return the first ChildUserSearchRun filtered by the user_search_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByAppRunId(string $app_run_id) Return the first ChildUserSearchRun filtered by the app_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByUserSearchRunKey(string $user_search_run_key) Return the first ChildUserSearchRun filtered by the user_search_run_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByJobSiteKey(string $jobsite_key) Return the first ChildUserSearchRun filtered by the jobsite_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneBySearchStartUrl(string $search_start_url) Return the first ChildUserSearchRun filtered by the search_start_url column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByRunResultCode(int $run_result_code) Return the first ChildUserSearchRun filtered by the run_result_code column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByRunErrorDetails(array $run_error_details) Return the first ChildUserSearchRun filtered by the run_error_details column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByStartedAt(string $date_started) Return the first ChildUserSearchRun filtered by the date_started column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByEndedAt(string $date_ended) Return the first ChildUserSearchRun filtered by the date_ended column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchRun[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearchRun objects based on current ModelCriteria
 * @method     ChildUserSearchRun[]|ObjectCollection findByUserSearchRunId(int $user_search_run_id) Return ChildUserSearchRun objects filtered by the user_search_run_id column
 * @method     ChildUserSearchRun[]|ObjectCollection findByUserSearchId(int $user_search_id) Return ChildUserSearchRun objects filtered by the user_search_id column
 * @method     ChildUserSearchRun[]|ObjectCollection findByAppRunId(string $app_run_id) Return ChildUserSearchRun objects filtered by the app_run_id column
 * @method     ChildUserSearchRun[]|ObjectCollection findByUserSearchRunKey(string $user_search_run_key) Return ChildUserSearchRun objects filtered by the user_search_run_key column
 * @method     ChildUserSearchRun[]|ObjectCollection findByJobSiteKey(string $jobsite_key) Return ChildUserSearchRun objects filtered by the jobsite_key column
 * @method     ChildUserSearchRun[]|ObjectCollection findBySearchStartUrl(string $search_start_url) Return ChildUserSearchRun objects filtered by the search_start_url column
 * @method     ChildUserSearchRun[]|ObjectCollection findByRunResultCode(int $run_result_code) Return ChildUserSearchRun objects filtered by the run_result_code column
 * @method     ChildUserSearchRun[]|ObjectCollection findByRunErrorDetails(array $run_error_details) Return ChildUserSearchRun objects filtered by the run_error_details column
 * @method     ChildUserSearchRun[]|ObjectCollection findByStartedAt(string $date_started) Return ChildUserSearchRun objects filtered by the date_started column
 * @method     ChildUserSearchRun[]|ObjectCollection findByEndedAt(string $date_ended) Return ChildUserSearchRun objects filtered by the date_ended column
 * @method     ChildUserSearchRun[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchRunQuery extends ModelCriteria
{

    // delegate behavior

    protected $delegatedFields = [
        'UserId' => 'UserSearch',
        'GeoLocationId' => 'UserSearch',
        'UserSearchKey' => 'UserSearch',
        'Keywords' => 'UserSearch',
        'KeywordTokens' => 'UserSearch',
        'SearchKeyFromConfig' => 'UserSearch',
        'CreatedAt' => 'UserSearch',
        'UpdatedAt' => 'UserSearch',
        'LastCompletedAt' => 'UserSearch',
        'Version' => 'UserSearch',
    ];

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
        $sql = 'SELECT user_search_run_id, user_search_id, app_run_id, user_search_run_key, jobsite_key, search_start_url, run_result_code, run_error_details, date_started, date_ended FROM user_search_run WHERE user_search_run_id = :p0';
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
     * Filter the query on the user_search_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUserSearchId(1234); // WHERE user_search_id = 1234
     * $query->filterByUserSearchId(array(12, 34)); // WHERE user_search_id IN (12, 34)
     * $query->filterByUserSearchId(array('min' => 12)); // WHERE user_search_id > 12
     * </code>
     *
     * @see       filterByUserSearch()
     *
     * @param     mixed $userSearchId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUserSearchId($userSearchId = null, $comparison = null)
    {
        if (is_array($userSearchId)) {
            $useMinMax = false;
            if (isset($userSearchId['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_ID, $userSearchId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userSearchId['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_ID, $userSearchId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_ID, $userSearchId, $comparison);
    }

    /**
     * Filter the query on the app_run_id column
     *
     * Example usage:
     * <code>
     * $query->filterByAppRunId('fooValue');   // WHERE app_run_id = 'fooValue'
     * $query->filterByAppRunId('%fooValue%', Criteria::LIKE); // WHERE app_run_id LIKE '%fooValue%'
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

        return $this->addUsingAlias(UserSearchRunTableMap::COL_APP_RUN_ID, $appRunId, $comparison);
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
     * Filter the query on the search_start_url column
     *
     * Example usage:
     * <code>
     * $query->filterBySearchStartUrl('fooValue');   // WHERE search_start_url = 'fooValue'
     * $query->filterBySearchStartUrl('%fooValue%', Criteria::LIKE); // WHERE search_start_url LIKE '%fooValue%'
     * </code>
     *
     * @param     string $searchStartUrl The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterBySearchStartUrl($searchStartUrl = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($searchStartUrl)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_SEARCH_START_URL, $searchStartUrl, $comparison);
    }

    /**
     * Filter the query on the run_result_code column
     *
     * @param     mixed $runResultCode The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByRunResultCode($runResultCode = null, $comparison = null)
    {
        $valueSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT_CODE);
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

        return $this->addUsingAlias(UserSearchRunTableMap::COL_RUN_RESULT_CODE, $runResultCode, $comparison);
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
     * Filter the query on the date_started column
     *
     * Example usage:
     * <code>
     * $query->filterByStartedAt('2011-03-14'); // WHERE date_started = '2011-03-14'
     * $query->filterByStartedAt('now'); // WHERE date_started = '2011-03-14'
     * $query->filterByStartedAt(array('max' => 'yesterday')); // WHERE date_started > '2011-03-13'
     * </code>
     *
     * @param     mixed $startedAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByStartedAt($startedAt = null, $comparison = null)
    {
        if (is_array($startedAt)) {
            $useMinMax = false;
            if (isset($startedAt['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_STARTED, $startedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($startedAt['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_STARTED, $startedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_STARTED, $startedAt, $comparison);
    }

    /**
     * Filter the query on the date_ended column
     *
     * Example usage:
     * <code>
     * $query->filterByEndedAt('2011-03-14'); // WHERE date_ended = '2011-03-14'
     * $query->filterByEndedAt('now'); // WHERE date_ended = '2011-03-14'
     * $query->filterByEndedAt(array('max' => 'yesterday')); // WHERE date_ended > '2011-03-13'
     * </code>
     *
     * @param     mixed $endedAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByEndedAt($endedAt = null, $comparison = null)
    {
        if (is_array($endedAt)) {
            $useMinMax = false;
            if (isset($endedAt['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_ENDED, $endedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($endedAt['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_ENDED, $endedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_ENDED, $endedAt, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\UserSearch object
     *
     * @param \JobScooper\DataAccess\UserSearch|ObjectCollection $userSearch The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUserSearch($userSearch, $comparison = null)
    {
        if ($userSearch instanceof \JobScooper\DataAccess\UserSearch) {
            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_ID, $userSearch->getUserSearchId(), $comparison);
        } elseif ($userSearch instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_ID, $userSearch->toKeyValue('PrimaryKey', 'UserSearchId'), $comparison);
        } else {
            throw new PropelException('filterByUserSearch() only accepts arguments of type \JobScooper\DataAccess\UserSearch or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearch relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function joinUserSearch($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserSearch');

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
            $this->addJoinObject($join, 'UserSearch');
        }

        return $this;
    }

    /**
     * Use the UserSearch relation UserSearch object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserSearchQuery A secondary query class using the current class as primary query
     */
    public function useUserSearchQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUserSearch($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearch', '\JobScooper\DataAccess\UserSearchQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\JobSiteRecord object
     *
     * @param \JobScooper\DataAccess\JobSiteRecord|ObjectCollection $jobSiteRecord The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByJobSiteRecordRelatedByJobSiteKey($jobSiteRecord, $comparison = null)
    {
        if ($jobSiteRecord instanceof \JobScooper\DataAccess\JobSiteRecord) {
            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_JOBSITE_KEY, $jobSiteRecord->getJobSiteKey(), $comparison);
        } elseif ($jobSiteRecord instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_JOBSITE_KEY, $jobSiteRecord->toKeyValue('PrimaryKey', 'JobSiteKey'), $comparison);
        } else {
            throw new PropelException('filterByJobSiteRecordRelatedByJobSiteKey() only accepts arguments of type \JobScooper\DataAccess\JobSiteRecord or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the JobSiteRecordRelatedByJobSiteKey relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function joinJobSiteRecordRelatedByJobSiteKey($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('JobSiteRecordRelatedByJobSiteKey');

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
            $this->addJoinObject($join, 'JobSiteRecordRelatedByJobSiteKey');
        }

        return $this;
    }

    /**
     * Use the JobSiteRecordRelatedByJobSiteKey relation JobSiteRecord object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\JobSiteRecordQuery A secondary query class using the current class as primary query
     */
    public function useJobSiteRecordRelatedByJobSiteKeyQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinJobSiteRecordRelatedByJobSiteKey($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobSiteRecordRelatedByJobSiteKey', '\JobScooper\DataAccess\JobSiteRecordQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\JobSiteRecord object
     *
     * @param \JobScooper\DataAccess\JobSiteRecord|ObjectCollection $jobSiteRecord the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByJobSiteRecordRelatedByLastUserSearchRunId($jobSiteRecord, $comparison = null)
    {
        if ($jobSiteRecord instanceof \JobScooper\DataAccess\JobSiteRecord) {
            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $jobSiteRecord->getLastUserSearchRunId(), $comparison);
        } elseif ($jobSiteRecord instanceof ObjectCollection) {
            return $this
                ->useJobSiteRecordRelatedByLastUserSearchRunIdQuery()
                ->filterByPrimaryKeys($jobSiteRecord->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByJobSiteRecordRelatedByLastUserSearchRunId() only accepts arguments of type \JobScooper\DataAccess\JobSiteRecord or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the JobSiteRecordRelatedByLastUserSearchRunId relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function joinJobSiteRecordRelatedByLastUserSearchRunId($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('JobSiteRecordRelatedByLastUserSearchRunId');

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
            $this->addJoinObject($join, 'JobSiteRecordRelatedByLastUserSearchRunId');
        }

        return $this;
    }

    /**
     * Use the JobSiteRecordRelatedByLastUserSearchRunId relation JobSiteRecord object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\JobSiteRecordQuery A secondary query class using the current class as primary query
     */
    public function useJobSiteRecordRelatedByLastUserSearchRunIdQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinJobSiteRecordRelatedByLastUserSearchRunId($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobSiteRecordRelatedByLastUserSearchRunId', '\JobScooper\DataAccess\JobSiteRecordQuery');
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
     * Code to execute before every DELETE statement
     *
     * @param     ConnectionInterface $con The connection object used by the query
     */
    protected function basePreDelete(ConnectionInterface $con)
    {
        // aggregate_column_relation_date_last_pulled behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastPulledAts($con);
        // aggregate_column_relation_date_last_run behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastRunAts($con);
        // aggregate_column_relation_date_last_completed behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastCompletedAts($con);
        // aggregate_column_relation_date_last_failed behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastFailedAts($con);
        // aggregate_column_relation_aggregate_column behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds($con);
        // aggregate_column_relation_us_date_last_completed behavior
        $this->findRelatedUserSearchLastCompletedAts($con);

        return $this->preDelete($con);
    }

    /**
     * Code to execute after every DELETE statement
     *
     * @param     int $affectedRows the number of deleted rows
     * @param     ConnectionInterface $con The connection object used by the query
     */
    protected function basePostDelete($affectedRows, ConnectionInterface $con)
    {
        // aggregate_column_relation_date_last_pulled behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastPulledAts($con);
        // aggregate_column_relation_date_last_run behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastRunAts($con);
        // aggregate_column_relation_date_last_completed behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastCompletedAts($con);
        // aggregate_column_relation_date_last_failed behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastFailedAts($con);
        // aggregate_column_relation_aggregate_column behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds($con);
        // aggregate_column_relation_us_date_last_completed behavior
        $this->updateRelatedUserSearchLastCompletedAts($con);

        return $this->postDelete($affectedRows, $con);
    }

    /**
     * Code to execute before every UPDATE statement
     *
     * @param     array $values The associative array of columns and values for the update
     * @param     ConnectionInterface $con The connection object used by the query
     * @param     boolean $forceIndividualSaves If false (default), the resulting call is a Criteria::doUpdate(), otherwise it is a series of save() calls on all the found objects
     */
    protected function basePreUpdate(&$values, ConnectionInterface $con, $forceIndividualSaves = false)
    {
        // aggregate_column_relation_date_last_pulled behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastPulledAts($con);
        // aggregate_column_relation_date_last_run behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastRunAts($con);
        // aggregate_column_relation_date_last_completed behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastCompletedAts($con);
        // aggregate_column_relation_date_last_failed behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastFailedAts($con);
        // aggregate_column_relation_aggregate_column behavior
        $this->findRelatedJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds($con);
        // aggregate_column_relation_us_date_last_completed behavior
        $this->findRelatedUserSearchLastCompletedAts($con);

        return $this->preUpdate($values, $con, $forceIndividualSaves);
    }

    /**
     * Code to execute after every UPDATE statement
     *
     * @param     int $affectedRows the number of updated rows
     * @param     ConnectionInterface $con The connection object used by the query
     */
    protected function basePostUpdate($affectedRows, ConnectionInterface $con)
    {
        // aggregate_column_relation_date_last_pulled behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastPulledAts($con);
        // aggregate_column_relation_date_last_run behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastRunAts($con);
        // aggregate_column_relation_date_last_completed behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastCompletedAts($con);
        // aggregate_column_relation_date_last_failed behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastFailedAts($con);
        // aggregate_column_relation_aggregate_column behavior
        $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds($con);
        // aggregate_column_relation_us_date_last_completed behavior
        $this->updateRelatedUserSearchLastCompletedAts($con);

        return $this->postUpdate($affectedRows, $con);
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

    // delegate behavior
    /**
    * Filter the query by user_id column
    *
    * Example usage:
    * <code>
        * $query->filterByUserId(1234); // WHERE user_id = 1234
        * $query->filterByUserId(array(12, 34)); // WHERE user_id IN (12, 34)
        * $query->filterByUserId(array('min' => 12)); // WHERE user_id >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByUserId($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByUserId($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByUserId($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByUserId($order)->endUse();
    }
    /**
    * Filter the query by geolocation_id column
    *
    * Example usage:
    * <code>
        * $query->filterByGeoLocationId(1234); // WHERE geolocation_id = 1234
        * $query->filterByGeoLocationId(array(12, 34)); // WHERE geolocation_id IN (12, 34)
        * $query->filterByGeoLocationId(array('min' => 12)); // WHERE geolocation_id >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByGeoLocationId($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByGeoLocationId($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByGeoLocationId($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByGeoLocationId($order)->endUse();
    }
    /**
    * Filter the query by user_search_key column
    *
    * Example usage:
    * <code>
        * $query->filterByUserSearchKey(1234); // WHERE user_search_key = 1234
        * $query->filterByUserSearchKey(array(12, 34)); // WHERE user_search_key IN (12, 34)
        * $query->filterByUserSearchKey(array('min' => 12)); // WHERE user_search_key >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByUserSearchKey($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByUserSearchKey($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByUserSearchKey($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByUserSearchKey($order)->endUse();
    }
    /**
    * Filter the query by keywords column
    *
    * Example usage:
    * <code>
        * $query->filterByKeywords(1234); // WHERE keywords = 1234
        * $query->filterByKeywords(array(12, 34)); // WHERE keywords IN (12, 34)
        * $query->filterByKeywords(array('min' => 12)); // WHERE keywords >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByKeywords($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByKeywords($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByKeywords($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByKeywords($order)->endUse();
    }
    /**
    * Filter the query by keyword_tokens column
    *
    * Example usage:
    * <code>
        * $query->filterByKeywordTokens(1234); // WHERE keyword_tokens = 1234
        * $query->filterByKeywordTokens(array(12, 34)); // WHERE keyword_tokens IN (12, 34)
        * $query->filterByKeywordTokens(array('min' => 12)); // WHERE keyword_tokens >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByKeywordTokens($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByKeywordTokens($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByKeywordTokens($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByKeywordTokens($order)->endUse();
    }
    /**
    * Filter the query by search_key_from_config column
    *
    * Example usage:
    * <code>
        * $query->filterBySearchKeyFromConfig(1234); // WHERE search_key_from_config = 1234
        * $query->filterBySearchKeyFromConfig(array(12, 34)); // WHERE search_key_from_config IN (12, 34)
        * $query->filterBySearchKeyFromConfig(array('min' => 12)); // WHERE search_key_from_config >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterBySearchKeyFromConfig($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterBySearchKeyFromConfig($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderBySearchKeyFromConfig($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderBySearchKeyFromConfig($order)->endUse();
    }
    /**
    * Filter the query by date_created column
    *
    * Example usage:
    * <code>
        * $query->filterByCreatedAt(1234); // WHERE date_created = 1234
        * $query->filterByCreatedAt(array(12, 34)); // WHERE date_created IN (12, 34)
        * $query->filterByCreatedAt(array('min' => 12)); // WHERE date_created >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByCreatedAt($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByCreatedAt($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByCreatedAt($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByCreatedAt($order)->endUse();
    }
    /**
    * Filter the query by date_updated column
    *
    * Example usage:
    * <code>
        * $query->filterByUpdatedAt(1234); // WHERE date_updated = 1234
        * $query->filterByUpdatedAt(array(12, 34)); // WHERE date_updated IN (12, 34)
        * $query->filterByUpdatedAt(array('min' => 12)); // WHERE date_updated >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByUpdatedAt($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByUpdatedAt($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByUpdatedAt($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByUpdatedAt($order)->endUse();
    }
    /**
    * Filter the query by date_last_completed column
    *
    * Example usage:
    * <code>
        * $query->filterByLastCompletedAt(1234); // WHERE date_last_completed = 1234
        * $query->filterByLastCompletedAt(array(12, 34)); // WHERE date_last_completed IN (12, 34)
        * $query->filterByLastCompletedAt(array('min' => 12)); // WHERE date_last_completed >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByLastCompletedAt($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByLastCompletedAt($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByLastCompletedAt($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByLastCompletedAt($order)->endUse();
    }
    /**
    * Filter the query by version column
    *
    * Example usage:
    * <code>
        * $query->filterByVersion(1234); // WHERE version = 1234
        * $query->filterByVersion(array(12, 34)); // WHERE version IN (12, 34)
        * $query->filterByVersion(array('min' => 12)); // WHERE version >= 12
        * </code>
    *
    * @param     mixed $value The value to use as filter.
    *              Use scalar values for equality.
    *              Use array values for in_array() equivalent.
    *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
    * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
    *
    * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
    */
    public function filterByVersion($value = null, $comparison = null)
    {
        return $this->useUserSearchQuery()->filterByVersion($value, $comparison)->endUse();
    }

    /**
    * Adds an ORDER BY clause to the query
    * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
    * Infers $column and $order from $columnName and some optional arguments
    * Examples:
    *   $c->orderBy('Book.CreatedAt')
    *    => $c->addAscendingOrderByColumn(BookTableMap::CREATED_AT)
    *   $c->orderBy('Book.CategoryId', 'desc')
    *    => $c->addDescendingOrderByColumn(BookTableMap::CATEGORY_ID)
    *
    * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
    *
    * @return $this|ModelCriteria The current object, for fluid interface
    */
    public function orderByVersion($order = Criteria::ASC)
    {
        return $this->useUserSearchQuery()->orderByVersion($order)->endUse();
    }

    /**
     * Adds a condition on a column based on a column phpName and a value
     * Uses introspection to translate the column phpName into a fully qualified name
     * Warning: recognizes only the phpNames of the main Model (not joined tables)
     * <code>
     * $c->filterBy('Title', 'foo');
     * </code>
     *
     * @see Criteria::add()
     *
     * @param string $column     A string representing thecolumn phpName, e.g. 'AuthorId'
     * @param mixed  $value      A value for the condition
     * @param string $comparison What to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ModelCriteria The current object, for fluid interface
     */
    public function filterBy($column, $value, $comparison = Criteria::EQUAL)
    {
        if (isset($this->delegatedFields[$column])) {
            $methodUse = "use{$this->delegatedFields[$column]}Query";

            return $this->{$methodUse}()->filterBy($column, $value, $comparison)->endUse();
        } else {
            return $this->add($this->getRealColumnName($column), $value, $comparison);
        }
    }

    // aggregate_column_relation_date_last_pulled behavior

    /**
     * Finds the related JobSiteRecord objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedJobSiteRecordRelatedByJobSiteKeyLastPulledAts($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastPulledAts = \JobScooper\DataAccess\JobSiteRecordQuery::create()
            ->joinUserSearchRunRelatedByJobSiteKey($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastPulledAts($con)
    {
        foreach ($this->jobSiteRecordRelatedByJobSiteKeyLastPulledAts as $jobSiteRecordRelatedByJobSiteKeyLastPulledAt) {
            $jobSiteRecordRelatedByJobSiteKeyLastPulledAt->updateLastPulledAt($con);
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastPulledAts = array();
    }

    // aggregate_column_relation_date_last_run behavior

    /**
     * Finds the related JobSiteRecord objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedJobSiteRecordRelatedByJobSiteKeyLastRunAts($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastRunAts = \JobScooper\DataAccess\JobSiteRecordQuery::create()
            ->joinUserSearchRunRelatedByJobSiteKey($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastRunAts($con)
    {
        foreach ($this->jobSiteRecordRelatedByJobSiteKeyLastRunAts as $jobSiteRecordRelatedByJobSiteKeyLastRunAt) {
            $jobSiteRecordRelatedByJobSiteKeyLastRunAt->updateLastRunAt($con);
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastRunAts = array();
    }

    // aggregate_column_relation_date_last_completed behavior

    /**
     * Finds the related JobSiteRecord objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedJobSiteRecordRelatedByJobSiteKeyLastCompletedAts($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastCompletedAts = \JobScooper\DataAccess\JobSiteRecordQuery::create()
            ->joinUserSearchRunRelatedByJobSiteKey($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastCompletedAts($con)
    {
        foreach ($this->jobSiteRecordRelatedByJobSiteKeyLastCompletedAts as $jobSiteRecordRelatedByJobSiteKeyLastCompletedAt) {
            $jobSiteRecordRelatedByJobSiteKeyLastCompletedAt->updateLastCompletedAt($con);
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastCompletedAts = array();
    }

    // aggregate_column_relation_date_last_failed behavior

    /**
     * Finds the related JobSiteRecord objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedJobSiteRecordRelatedByJobSiteKeyLastFailedAts($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastFailedAts = \JobScooper\DataAccess\JobSiteRecordQuery::create()
            ->joinUserSearchRunRelatedByJobSiteKey($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastFailedAts($con)
    {
        foreach ($this->jobSiteRecordRelatedByJobSiteKeyLastFailedAts as $jobSiteRecordRelatedByJobSiteKeyLastFailedAt) {
            $jobSiteRecordRelatedByJobSiteKeyLastFailedAt->updateLastFailedAt($con);
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastFailedAts = array();
    }

    // aggregate_column_relation_aggregate_column behavior

    /**
     * Finds the related JobSiteRecord objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds = \JobScooper\DataAccess\JobSiteRecordQuery::create()
            ->joinUserSearchRunRelatedByJobSiteKey($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds($con)
    {
        foreach ($this->jobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds as $jobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId) {
            $jobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId->updateLastUserSearchRunId($con);
        }
        $this->jobSiteRecordRelatedByJobSiteKeyLastUserSearchRunIds = array();
    }

    // aggregate_column_relation_us_date_last_completed behavior

    /**
     * Finds the related UserSearch objects and keep them for later
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function findRelatedUserSearchLastCompletedAts($con)
    {
        $criteria = clone $this;
        if ($this->useAliasInSQL) {
            $alias = $this->getModelAlias();
            $criteria->removeAlias($alias);
        } else {
            $alias = '';
        }
        $this->userSearchLastCompletedAts = \JobScooper\DataAccess\UserSearchQuery::create()
            ->joinUserSearchRun($alias)
            ->mergeWith($criteria)
            ->find($con);
    }

    protected function updateRelatedUserSearchLastCompletedAts($con)
    {
        foreach ($this->userSearchLastCompletedAts as $userSearchLastCompletedAt) {
            $userSearchLastCompletedAt->updateLastCompletedAt($con);
        }
        $this->userSearchLastCompletedAts = array();
    }

} // UserSearchRunQuery
