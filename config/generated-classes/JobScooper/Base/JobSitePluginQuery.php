<?php

namespace JobScooper\Base;

use \Exception;
use \PDO;
use JobScooper\JobSitePlugin as ChildJobSitePlugin;
use JobScooper\JobSitePluginQuery as ChildJobSitePluginQuery;
use JobScooper\Map\JobSitePluginTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'jobsite_plugin' table.
 *
 *
 *
 * @method     ChildJobSitePluginQuery orderByJobSiteKey($order = Criteria::ASC) Order by the jobsite_key column
 * @method     ChildJobSitePluginQuery orderByPluginClassName($order = Criteria::ASC) Order by the plugin_class_name column
 * @method     ChildJobSitePluginQuery orderByDisplayName($order = Criteria::ASC) Order by the display_name column
 * @method     ChildJobSitePluginQuery orderByLastRunAt($order = Criteria::ASC) Order by the date_last_run column
 * @method     ChildJobSitePluginQuery orderByLastRunWasSuccessful($order = Criteria::ASC) Order by the was_successful column
 * @method     ChildJobSitePluginQuery orderByStartNextRunAfter($order = Criteria::ASC) Order by the date_next_run column
 * @method     ChildJobSitePluginQuery orderByLastFailedAt($order = Criteria::ASC) Order by the date_last_failed column
 * @method     ChildJobSitePluginQuery orderByLastUserSearchRunId($order = Criteria::ASC) Order by the last_user_search_run_id column
 * @method     ChildJobSitePluginQuery orderBySupportedCountryCodes($order = Criteria::ASC) Order by the supported_country_codes column
 * @method     ChildJobSitePluginQuery orderByResultsFilterType($order = Criteria::ASC) Order by the results_filter_type column
 *
 * @method     ChildJobSitePluginQuery groupByJobSiteKey() Group by the jobsite_key column
 * @method     ChildJobSitePluginQuery groupByPluginClassName() Group by the plugin_class_name column
 * @method     ChildJobSitePluginQuery groupByDisplayName() Group by the display_name column
 * @method     ChildJobSitePluginQuery groupByLastRunAt() Group by the date_last_run column
 * @method     ChildJobSitePluginQuery groupByLastRunWasSuccessful() Group by the was_successful column
 * @method     ChildJobSitePluginQuery groupByStartNextRunAfter() Group by the date_next_run column
 * @method     ChildJobSitePluginQuery groupByLastFailedAt() Group by the date_last_failed column
 * @method     ChildJobSitePluginQuery groupByLastUserSearchRunId() Group by the last_user_search_run_id column
 * @method     ChildJobSitePluginQuery groupBySupportedCountryCodes() Group by the supported_country_codes column
 * @method     ChildJobSitePluginQuery groupByResultsFilterType() Group by the results_filter_type column
 *
 * @method     ChildJobSitePluginQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildJobSitePluginQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildJobSitePluginQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildJobSitePluginQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildJobSitePluginQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildJobSitePluginQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildJobSitePluginQuery leftJoinUserSearchRun($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchRun relation
 * @method     ChildJobSitePluginQuery rightJoinUserSearchRun($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchRun relation
 * @method     ChildJobSitePluginQuery innerJoinUserSearchRun($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchRun relation
 *
 * @method     ChildJobSitePluginQuery joinWithUserSearchRun($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchRun relation
 *
 * @method     ChildJobSitePluginQuery leftJoinWithUserSearchRun() Adds a LEFT JOIN clause and with to the query using the UserSearchRun relation
 * @method     ChildJobSitePluginQuery rightJoinWithUserSearchRun() Adds a RIGHT JOIN clause and with to the query using the UserSearchRun relation
 * @method     ChildJobSitePluginQuery innerJoinWithUserSearchRun() Adds a INNER JOIN clause and with to the query using the UserSearchRun relation
 *
 * @method     \JobScooper\UserSearchRunQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildJobSitePlugin findOne(ConnectionInterface $con = null) Return the first ChildJobSitePlugin matching the query
 * @method     ChildJobSitePlugin findOneOrCreate(ConnectionInterface $con = null) Return the first ChildJobSitePlugin matching the query, or a new ChildJobSitePlugin object populated from the query conditions when no match is found
 *
 * @method     ChildJobSitePlugin findOneByJobSiteKey(string $jobsite_key) Return the first ChildJobSitePlugin filtered by the jobsite_key column
 * @method     ChildJobSitePlugin findOneByPluginClassName(string $plugin_class_name) Return the first ChildJobSitePlugin filtered by the plugin_class_name column
 * @method     ChildJobSitePlugin findOneByDisplayName(string $display_name) Return the first ChildJobSitePlugin filtered by the display_name column
 * @method     ChildJobSitePlugin findOneByLastRunAt(string $date_last_run) Return the first ChildJobSitePlugin filtered by the date_last_run column
 * @method     ChildJobSitePlugin findOneByLastRunWasSuccessful(boolean $was_successful) Return the first ChildJobSitePlugin filtered by the was_successful column
 * @method     ChildJobSitePlugin findOneByStartNextRunAfter(string $date_next_run) Return the first ChildJobSitePlugin filtered by the date_next_run column
 * @method     ChildJobSitePlugin findOneByLastFailedAt(string $date_last_failed) Return the first ChildJobSitePlugin filtered by the date_last_failed column
 * @method     ChildJobSitePlugin findOneByLastUserSearchRunId(int $last_user_search_run_id) Return the first ChildJobSitePlugin filtered by the last_user_search_run_id column
 * @method     ChildJobSitePlugin findOneBySupportedCountryCodes(array $supported_country_codes) Return the first ChildJobSitePlugin filtered by the supported_country_codes column
 * @method     ChildJobSitePlugin findOneByResultsFilterType(int $results_filter_type) Return the first ChildJobSitePlugin filtered by the results_filter_type column *

 * @method     ChildJobSitePlugin requirePk($key, ConnectionInterface $con = null) Return the ChildJobSitePlugin by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOne(ConnectionInterface $con = null) Return the first ChildJobSitePlugin matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSitePlugin requireOneByJobSiteKey(string $jobsite_key) Return the first ChildJobSitePlugin filtered by the jobsite_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByPluginClassName(string $plugin_class_name) Return the first ChildJobSitePlugin filtered by the plugin_class_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByDisplayName(string $display_name) Return the first ChildJobSitePlugin filtered by the display_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByLastRunAt(string $date_last_run) Return the first ChildJobSitePlugin filtered by the date_last_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByLastRunWasSuccessful(boolean $was_successful) Return the first ChildJobSitePlugin filtered by the was_successful column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByStartNextRunAfter(string $date_next_run) Return the first ChildJobSitePlugin filtered by the date_next_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByLastFailedAt(string $date_last_failed) Return the first ChildJobSitePlugin filtered by the date_last_failed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByLastUserSearchRunId(int $last_user_search_run_id) Return the first ChildJobSitePlugin filtered by the last_user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneBySupportedCountryCodes(array $supported_country_codes) Return the first ChildJobSitePlugin filtered by the supported_country_codes column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByResultsFilterType(int $results_filter_type) Return the first ChildJobSitePlugin filtered by the results_filter_type column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSitePlugin[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildJobSitePlugin objects based on current ModelCriteria
 * @method     ChildJobSitePlugin[]|ObjectCollection findByJobSiteKey(string $jobsite_key) Return ChildJobSitePlugin objects filtered by the jobsite_key column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByPluginClassName(string $plugin_class_name) Return ChildJobSitePlugin objects filtered by the plugin_class_name column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByDisplayName(string $display_name) Return ChildJobSitePlugin objects filtered by the display_name column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByLastRunAt(string $date_last_run) Return ChildJobSitePlugin objects filtered by the date_last_run column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByLastRunWasSuccessful(boolean $was_successful) Return ChildJobSitePlugin objects filtered by the was_successful column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByStartNextRunAfter(string $date_next_run) Return ChildJobSitePlugin objects filtered by the date_next_run column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByLastFailedAt(string $date_last_failed) Return ChildJobSitePlugin objects filtered by the date_last_failed column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByLastUserSearchRunId(int $last_user_search_run_id) Return ChildJobSitePlugin objects filtered by the last_user_search_run_id column
 * @method     ChildJobSitePlugin[]|ObjectCollection findBySupportedCountryCodes(array $supported_country_codes) Return ChildJobSitePlugin objects filtered by the supported_country_codes column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByResultsFilterType(int $results_filter_type) Return ChildJobSitePlugin objects filtered by the results_filter_type column
 * @method     ChildJobSitePlugin[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class JobSitePluginQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\Base\JobSitePluginQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\JobSitePlugin', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildJobSitePluginQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildJobSitePluginQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildJobSitePluginQuery) {
            return $criteria;
        }
        $query = new ChildJobSitePluginQuery();
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
     * @return ChildJobSitePlugin|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = JobSitePluginTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
     * @return ChildJobSitePlugin A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT jobsite_key, plugin_class_name, display_name, date_last_run, was_successful, date_next_run, date_last_failed, supported_country_codes, results_filter_type FROM jobsite_plugin WHERE jobsite_key = :p0';
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
            /** @var ChildJobSitePlugin $obj */
            $obj = new ChildJobSitePlugin();
            $obj->hydrate($row);
            JobSitePluginTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return ChildJobSitePlugin|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(JobSitePluginTableMap::COL_JOBSITE_KEY, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(JobSitePluginTableMap::COL_JOBSITE_KEY, $keys, Criteria::IN);
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
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByJobSiteKey($jobSiteKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobSiteKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_JOBSITE_KEY, $jobSiteKey, $comparison);
    }

    /**
     * Filter the query on the plugin_class_name column
     *
     * Example usage:
     * <code>
     * $query->filterByPluginClassName('fooValue');   // WHERE plugin_class_name = 'fooValue'
     * $query->filterByPluginClassName('%fooValue%', Criteria::LIKE); // WHERE plugin_class_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $pluginClassName The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByPluginClassName($pluginClassName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($pluginClassName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME, $pluginClassName, $comparison);
    }

    /**
     * Filter the query on the display_name column
     *
     * Example usage:
     * <code>
     * $query->filterByDisplayName('fooValue');   // WHERE display_name = 'fooValue'
     * $query->filterByDisplayName('%fooValue%', Criteria::LIKE); // WHERE display_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $displayName The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByDisplayName($displayName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($displayName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_DISPLAY_NAME, $displayName, $comparison);
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
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByLastRunAt($lastRunAt = null, $comparison = null)
    {
        if (is_array($lastRunAt)) {
            $useMinMax = false;
            if (isset($lastRunAt['min'])) {
                $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_LAST_RUN, $lastRunAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastRunAt['max'])) {
                $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_LAST_RUN, $lastRunAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_LAST_RUN, $lastRunAt, $comparison);
    }

    /**
     * Filter the query on the was_successful column
     *
     * Example usage:
     * <code>
     * $query->filterByLastRunWasSuccessful(true); // WHERE was_successful = true
     * $query->filterByLastRunWasSuccessful('yes'); // WHERE was_successful = true
     * </code>
     *
     * @param     boolean|string $lastRunWasSuccessful The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByLastRunWasSuccessful($lastRunWasSuccessful = null, $comparison = null)
    {
        if (is_string($lastRunWasSuccessful)) {
            $lastRunWasSuccessful = in_array(strtolower($lastRunWasSuccessful), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_WAS_SUCCESSFUL, $lastRunWasSuccessful, $comparison);
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
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByStartNextRunAfter($startNextRunAfter = null, $comparison = null)
    {
        if (is_array($startNextRunAfter)) {
            $useMinMax = false;
            if (isset($startNextRunAfter['min'])) {
                $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_NEXT_RUN, $startNextRunAfter['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($startNextRunAfter['max'])) {
                $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_NEXT_RUN, $startNextRunAfter['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_NEXT_RUN, $startNextRunAfter, $comparison);
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
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByLastFailedAt($lastFailedAt = null, $comparison = null)
    {
        if (is_array($lastFailedAt)) {
            $useMinMax = false;
            if (isset($lastFailedAt['min'])) {
                $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastFailedAt['max'])) {
                $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_DATE_LAST_FAILED, $lastFailedAt, $comparison);
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
     * @see       filterByUserSearchRun()
     *
     * @param     mixed $lastUserSearchRunId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByLastUserSearchRunId($lastUserSearchRunId = null, $comparison = null)
    {
        if (is_array($lastUserSearchRunId)) {
            $useMinMax = false;
            if (isset($lastUserSearchRunId['min'])) {
                $this->addUsingAlias(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastUserSearchRunId['max'])) {
                $this->addUsingAlias(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId, $comparison);
    }

    /**
     * Filter the query on the supported_country_codes column
     *
     * @param     array $supportedCountryCodes The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterBySupportedCountryCodes($supportedCountryCodes = null, $comparison = null)
    {
        $key = $this->getAliasedColName(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES);
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            foreach ($supportedCountryCodes as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_SOME) {
            foreach ($supportedCountryCodes as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addOr($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            foreach ($supportedCountryCodes as $value) {
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

        return $this->addUsingAlias(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES, $supportedCountryCodes, $comparison);
    }

    /**
     * Filter the query on the supported_country_codes column
     * @param     mixed $supportedCountryCodes The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterBySupportedCountryCode($supportedCountryCodes = null, $comparison = null)
    {
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            if (is_scalar($supportedCountryCodes)) {
                $supportedCountryCodes = '%| ' . $supportedCountryCodes . ' |%';
                $comparison = Criteria::LIKE;
            }
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            $supportedCountryCodes = '%| ' . $supportedCountryCodes . ' |%';
            $comparison = Criteria::NOT_LIKE;
            $key = $this->getAliasedColName(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $supportedCountryCodes, $comparison);
            } else {
                $this->addAnd($key, $supportedCountryCodes, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES, $supportedCountryCodes, $comparison);
    }

    /**
     * Filter the query on the results_filter_type column
     *
     * @param     mixed $resultsFilterType The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByResultsFilterType($resultsFilterType = null, $comparison = null)
    {
        $valueSet = JobSitePluginTableMap::getValueSet(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE);
        if (is_scalar($resultsFilterType)) {
            if (!in_array($resultsFilterType, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $resultsFilterType));
            }
            $resultsFilterType = array_search($resultsFilterType, $valueSet);
        } elseif (is_array($resultsFilterType)) {
            $convertedValues = array();
            foreach ($resultsFilterType as $value) {
                if (!in_array($value, $valueSet)) {
                    throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $value));
                }
                $convertedValues []= array_search($value, $valueSet);
            }
            $resultsFilterType = $convertedValues;
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE, $resultsFilterType, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\UserSearchRun object
     *
     * @param \JobScooper\UserSearchRun|ObjectCollection $userSearchRun The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByUserSearchRun($userSearchRun, $comparison = null)
    {
        if ($userSearchRun instanceof \JobScooper\UserSearchRun) {
            return $this
                ->addUsingAlias(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID, $userSearchRun->getUserSearchRunId(), $comparison);
        } elseif ($userSearchRun instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID, $userSearchRun->toKeyValue('PrimaryKey', 'UserSearchRunId'), $comparison);
        } else {
            throw new PropelException('filterByUserSearchRun() only accepts arguments of type \JobScooper\UserSearchRun or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearchRun relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
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
     * @return \JobScooper\UserSearchRunQuery A secondary query class using the current class as primary query
     */
    public function useUserSearchRunQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinUserSearchRun($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearchRun', '\JobScooper\UserSearchRunQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildJobSitePlugin $jobSitePlugin Object to remove from the list of results
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function prune($jobSitePlugin = null)
    {
        if ($jobSitePlugin) {
            $this->addUsingAlias(JobSitePluginTableMap::COL_JOBSITE_KEY, $jobSitePlugin->getJobSiteKey(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the jobsite_plugin table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            JobSitePluginTableMap::clearInstancePool();
            JobSitePluginTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(JobSitePluginTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            JobSitePluginTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            JobSitePluginTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // JobSitePluginQuery
