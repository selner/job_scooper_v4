<?php

namespace JobScooper\Base;

use \Exception;
use \PDO;
use JobScooper\JobSitePluginLastRun as ChildJobSitePluginLastRun;
use JobScooper\JobSitePluginLastRunQuery as ChildJobSitePluginLastRunQuery;
use JobScooper\Map\JobSitePluginLastRunTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'jobsite_plugin_last_run' table.
 *
 *
 *
 * @method     ChildJobSitePluginLastRunQuery orderByJobSite($order = Criteria::ASC) Order by the jobsite column
 * @method     ChildJobSitePluginLastRunQuery orderByLastUserSearchRunId($order = Criteria::ASC) Order by the last_user_search_run_id column
 * @method     ChildJobSitePluginLastRunQuery orderByFirstRunAt($order = Criteria::ASC) Order by the date_first_run column
 * @method     ChildJobSitePluginLastRunQuery orderByLastRunAt($order = Criteria::ASC) Order by the date_last_run column
 * @method     ChildJobSitePluginLastRunQuery orderByLastSucceededAt($order = Criteria::ASC) Order by the date_last_succeeded column
 * @method     ChildJobSitePluginLastRunQuery orderByLastFailedAt($order = Criteria::ASC) Order by the date_last_failed column
 * @method     ChildJobSitePluginLastRunQuery orderByWasSuccessful($order = Criteria::ASC) Order by the was_successful column
 * @method     ChildJobSitePluginLastRunQuery orderByRecentErrorDetails($order = Criteria::ASC) Order by the error_details column
 *
 * @method     ChildJobSitePluginLastRunQuery groupByJobSite() Group by the jobsite column
 * @method     ChildJobSitePluginLastRunQuery groupByLastUserSearchRunId() Group by the last_user_search_run_id column
 * @method     ChildJobSitePluginLastRunQuery groupByFirstRunAt() Group by the date_first_run column
 * @method     ChildJobSitePluginLastRunQuery groupByLastRunAt() Group by the date_last_run column
 * @method     ChildJobSitePluginLastRunQuery groupByLastSucceededAt() Group by the date_last_succeeded column
 * @method     ChildJobSitePluginLastRunQuery groupByLastFailedAt() Group by the date_last_failed column
 * @method     ChildJobSitePluginLastRunQuery groupByWasSuccessful() Group by the was_successful column
 * @method     ChildJobSitePluginLastRunQuery groupByRecentErrorDetails() Group by the error_details column
 *
 * @method     ChildJobSitePluginLastRunQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildJobSitePluginLastRunQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildJobSitePluginLastRunQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildJobSitePluginLastRunQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildJobSitePluginLastRunQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildJobSitePluginLastRunQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildJobSitePluginLastRun findOne(ConnectionInterface $con = null) Return the first ChildJobSitePluginLastRun matching the query
 * @method     ChildJobSitePluginLastRun findOneOrCreate(ConnectionInterface $con = null) Return the first ChildJobSitePluginLastRun matching the query, or a new ChildJobSitePluginLastRun object populated from the query conditions when no match is found
 *
 * @method     ChildJobSitePluginLastRun findOneByJobSite(string $jobsite) Return the first ChildJobSitePluginLastRun filtered by the jobsite column
 * @method     ChildJobSitePluginLastRun findOneByLastUserSearchRunId(int $last_user_search_run_id) Return the first ChildJobSitePluginLastRun filtered by the last_user_search_run_id column
 * @method     ChildJobSitePluginLastRun findOneByFirstRunAt(string $date_first_run) Return the first ChildJobSitePluginLastRun filtered by the date_first_run column
 * @method     ChildJobSitePluginLastRun findOneByLastRunAt(string $date_last_run) Return the first ChildJobSitePluginLastRun filtered by the date_last_run column
 * @method     ChildJobSitePluginLastRun findOneByLastSucceededAt(string $date_last_succeeded) Return the first ChildJobSitePluginLastRun filtered by the date_last_succeeded column
 * @method     ChildJobSitePluginLastRun findOneByLastFailedAt(string $date_last_failed) Return the first ChildJobSitePluginLastRun filtered by the date_last_failed column
 * @method     ChildJobSitePluginLastRun findOneByWasSuccessful(boolean $was_successful) Return the first ChildJobSitePluginLastRun filtered by the was_successful column
 * @method     ChildJobSitePluginLastRun findOneByRecentErrorDetails(array $error_details) Return the first ChildJobSitePluginLastRun filtered by the error_details column *

 * @method     ChildJobSitePluginLastRun requirePk($key, ConnectionInterface $con = null) Return the ChildJobSitePluginLastRun by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePluginLastRun requireOne(ConnectionInterface $con = null) Return the first ChildJobSitePluginLastRun matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSitePluginLastRun requireOneByJobSite(string $jobsite) Return the first ChildJobSitePluginLastRun filtered by the jobsite column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePluginLastRun requireOneByLastUserSearchRunId(int $last_user_search_run_id) Return the first ChildJobSitePluginLastRun filtered by the last_user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePluginLastRun requireOneByFirstRunAt(string $date_first_run) Return the first ChildJobSitePluginLastRun filtered by the date_first_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePluginLastRun requireOneByLastRunAt(string $date_last_run) Return the first ChildJobSitePluginLastRun filtered by the date_last_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePluginLastRun requireOneByLastSucceededAt(string $date_last_succeeded) Return the first ChildJobSitePluginLastRun filtered by the date_last_succeeded column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePluginLastRun requireOneByLastFailedAt(string $date_last_failed) Return the first ChildJobSitePluginLastRun filtered by the date_last_failed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePluginLastRun requireOneByWasSuccessful(boolean $was_successful) Return the first ChildJobSitePluginLastRun filtered by the was_successful column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePluginLastRun requireOneByRecentErrorDetails(array $error_details) Return the first ChildJobSitePluginLastRun filtered by the error_details column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildJobSitePluginLastRun objects based on current ModelCriteria
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection findByJobSite(string $jobsite) Return ChildJobSitePluginLastRun objects filtered by the jobsite column
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection findByLastUserSearchRunId(int $last_user_search_run_id) Return ChildJobSitePluginLastRun objects filtered by the last_user_search_run_id column
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection findByFirstRunAt(string $date_first_run) Return ChildJobSitePluginLastRun objects filtered by the date_first_run column
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection findByLastRunAt(string $date_last_run) Return ChildJobSitePluginLastRun objects filtered by the date_last_run column
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection findByLastSucceededAt(string $date_last_succeeded) Return ChildJobSitePluginLastRun objects filtered by the date_last_succeeded column
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection findByLastFailedAt(string $date_last_failed) Return ChildJobSitePluginLastRun objects filtered by the date_last_failed column
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection findByWasSuccessful(boolean $was_successful) Return ChildJobSitePluginLastRun objects filtered by the was_successful column
 * @method     ChildJobSitePluginLastRun[]|ObjectCollection findByRecentErrorDetails(array $error_details) Return ChildJobSitePluginLastRun objects filtered by the error_details column
 * @method     ChildJobSitePluginLastRun[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class JobSitePluginLastRunQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\Base\JobSitePluginLastRunQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\JobSitePluginLastRun', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildJobSitePluginLastRunQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildJobSitePluginLastRunQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildJobSitePluginLastRunQuery) {
            return $criteria;
        }
        $query = new ChildJobSitePluginLastRunQuery();
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
     * @return ChildJobSitePluginLastRun|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(JobSitePluginLastRunTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = JobSitePluginLastRunTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
     * @return ChildJobSitePluginLastRun A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT jobsite, last_user_search_run_id, date_first_run, date_last_run, date_last_succeeded, date_last_failed, was_successful, error_details FROM jobsite_plugin_last_run WHERE jobsite = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildJobSitePluginLastRun $obj */
            $obj = new ChildJobSitePluginLastRun();
            $obj->hydrate($row);
            JobSitePluginLastRunTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return ChildJobSitePluginLastRun|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_JOBSITE, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_JOBSITE, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the jobsite column
     *
     * Example usage:
     * <code>
     * $query->filterByJobSite('fooValue');   // WHERE jobsite = 'fooValue'
     * $query->filterByJobSite('%fooValue%', Criteria::LIKE); // WHERE jobsite LIKE '%fooValue%'
     * </code>
     *
     * @param     string $jobSite The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByJobSite($jobSite = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobSite)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_JOBSITE, $jobSite, $comparison);
    }

    /**
     * Filter the query on the last_user_search_run_id column
     *
     * Example usage:
     * <code>
     * $query->filterByLastUserSearchRunId(1234); // WHERE last_user_search_run_id = 1234
     * $query->filterByLastUserSearchRunId(array(12, 34)); // WHERE last_user_search_run_id IN (12, 34)
     * $query->filterByLastUserSearchRunId(array('min' => 12)); // WHERE last_user_search_run_id > 12
     * </code>
     *
     * @param     mixed $lastUserSearchRunId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByLastUserSearchRunId($lastUserSearchRunId = null, $comparison = null)
    {
        if (is_array($lastUserSearchRunId)) {
            $useMinMax = false;
            if (isset($lastUserSearchRunId['min'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastUserSearchRunId['max'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId, $comparison);
    }

    /**
     * Filter the query on the date_first_run column
     *
     * Example usage:
     * <code>
     * $query->filterByFirstRunAt('2011-03-14'); // WHERE date_first_run = '2011-03-14'
     * $query->filterByFirstRunAt('now'); // WHERE date_first_run = '2011-03-14'
     * $query->filterByFirstRunAt(array('max' => 'yesterday')); // WHERE date_first_run > '2011-03-13'
     * </code>
     *
     * @param     mixed $firstRunAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByFirstRunAt($firstRunAt = null, $comparison = null)
    {
        if (is_array($firstRunAt)) {
            $useMinMax = false;
            if (isset($firstRunAt['min'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN, $firstRunAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($firstRunAt['max'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN, $firstRunAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN, $firstRunAt, $comparison);
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
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByLastRunAt($lastRunAt = null, $comparison = null)
    {
        if (is_array($lastRunAt)) {
            $useMinMax = false;
            if (isset($lastRunAt['min'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN, $lastRunAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastRunAt['max'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN, $lastRunAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN, $lastRunAt, $comparison);
    }

    /**
     * Filter the query on the date_last_succeeded column
     *
     * Example usage:
     * <code>
     * $query->filterByLastSucceededAt('2011-03-14'); // WHERE date_last_succeeded = '2011-03-14'
     * $query->filterByLastSucceededAt('now'); // WHERE date_last_succeeded = '2011-03-14'
     * $query->filterByLastSucceededAt(array('max' => 'yesterday')); // WHERE date_last_succeeded > '2011-03-13'
     * </code>
     *
     * @param     mixed $lastSucceededAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByLastSucceededAt($lastSucceededAt = null, $comparison = null)
    {
        if (is_array($lastSucceededAt)) {
            $useMinMax = false;
            if (isset($lastSucceededAt['min'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_SUCCEEDED, $lastSucceededAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastSucceededAt['max'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_SUCCEEDED, $lastSucceededAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_SUCCEEDED, $lastSucceededAt, $comparison);
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
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByLastFailedAt($lastFailedAt = null, $comparison = null)
    {
        if (is_array($lastFailedAt)) {
            $useMinMax = false;
            if (isset($lastFailedAt['min'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastFailedAt['max'])) {
                $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_FAILED, $lastFailedAt, $comparison);
    }

    /**
     * Filter the query on the was_successful column
     *
     * Example usage:
     * <code>
     * $query->filterByWasSuccessful(true); // WHERE was_successful = true
     * $query->filterByWasSuccessful('yes'); // WHERE was_successful = true
     * </code>
     *
     * @param     boolean|string $wasSuccessful The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByWasSuccessful($wasSuccessful = null, $comparison = null)
    {
        if (is_string($wasSuccessful)) {
            $wasSuccessful = in_array(strtolower($wasSuccessful), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_WAS_SUCCESSFUL, $wasSuccessful, $comparison);
    }

    /**
     * Filter the query on the error_details column
     *
     * @param     array $recentErrorDetails The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByRecentErrorDetails($recentErrorDetails = null, $comparison = null)
    {
        $key = $this->getAliasedColName(JobSitePluginLastRunTableMap::COL_ERROR_DETAILS);
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            foreach ($recentErrorDetails as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_SOME) {
            foreach ($recentErrorDetails as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addOr($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            foreach ($recentErrorDetails as $value) {
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

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_ERROR_DETAILS, $recentErrorDetails, $comparison);
    }

    /**
     * Filter the query on the error_details column
     * @param     mixed $recentErrorDetails The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function filterByRecentErrorDetail($recentErrorDetails = null, $comparison = null)
    {
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            if (is_scalar($recentErrorDetails)) {
                $recentErrorDetails = '%| ' . $recentErrorDetails . ' |%';
                $comparison = Criteria::LIKE;
            }
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            $recentErrorDetails = '%| ' . $recentErrorDetails . ' |%';
            $comparison = Criteria::NOT_LIKE;
            $key = $this->getAliasedColName(JobSitePluginLastRunTableMap::COL_ERROR_DETAILS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $recentErrorDetails, $comparison);
            } else {
                $this->addAnd($key, $recentErrorDetails, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_ERROR_DETAILS, $recentErrorDetails, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildJobSitePluginLastRun $jobSitePluginLastRun Object to remove from the list of results
     *
     * @return $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function prune($jobSitePluginLastRun = null)
    {
        if ($jobSitePluginLastRun) {
            $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_JOBSITE, $jobSitePluginLastRun->getJobSite(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the jobsite_plugin_last_run table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginLastRunTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            JobSitePluginLastRunTableMap::clearInstancePool();
            JobSitePluginLastRunTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginLastRunTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(JobSitePluginLastRunTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            JobSitePluginLastRunTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            JobSitePluginLastRunTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // timestampable behavior

    /**
     * Filter by the latest updated
     *
     * @param      int $nbDays Maximum age of the latest update in days
     *
     * @return     $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function recentlyUpdated($nbDays = 7)
    {
        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by update date desc
     *
     * @return     $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function lastUpdatedFirst()
    {
        return $this->addDescendingOrderByColumn(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN);
    }

    /**
     * Order by update date asc
     *
     * @return     $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function firstUpdatedFirst()
    {
        return $this->addAscendingOrderByColumn(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN);
    }

    /**
     * Order by create date desc
     *
     * @return     $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function lastCreatedFirst()
    {
        return $this->addDescendingOrderByColumn(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN);
    }

    /**
     * Filter by the latest created
     *
     * @param      int $nbDays Maximum age of in days
     *
     * @return     $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function recentlyCreated($nbDays = 7)
    {
        return $this->addUsingAlias(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by create date asc
     *
     * @return     $this|ChildJobSitePluginLastRunQuery The current query, for fluid interface
     */
    public function firstCreatedFirst()
    {
        return $this->addAscendingOrderByColumn(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN);
    }

} // JobSitePluginLastRunQuery
