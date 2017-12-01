<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\JobSiteRecordVersion as ChildJobSiteRecordVersion;
use JobScooper\DataAccess\JobSiteRecordVersionQuery as ChildJobSiteRecordVersionQuery;
use JobScooper\DataAccess\Map\JobSiteRecordVersionTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'job_site_version' table.
 *
 *
 *
 * @method     ChildJobSiteRecordVersionQuery orderByJobSiteKey($order = Criteria::ASC) Order by the jobsite_key column
 * @method     ChildJobSiteRecordVersionQuery orderByPluginClassName($order = Criteria::ASC) Order by the plugin_class_name column
 * @method     ChildJobSiteRecordVersionQuery orderByDisplayName($order = Criteria::ASC) Order by the display_name column
 * @method     ChildJobSiteRecordVersionQuery orderByisDisabled($order = Criteria::ASC) Order by the is_disabled column
 * @method     ChildJobSiteRecordVersionQuery orderByLastPulledAt($order = Criteria::ASC) Order by the date_last_pulled column
 * @method     ChildJobSiteRecordVersionQuery orderByLastRunAt($order = Criteria::ASC) Order by the date_last_run column
 * @method     ChildJobSiteRecordVersionQuery orderByLastCompletedAt($order = Criteria::ASC) Order by the date_last_completed column
 * @method     ChildJobSiteRecordVersionQuery orderByLastFailedAt($order = Criteria::ASC) Order by the date_last_failed column
 * @method     ChildJobSiteRecordVersionQuery orderByLastUserSearchRunId($order = Criteria::ASC) Order by the last_user_search_run_id column
 * @method     ChildJobSiteRecordVersionQuery orderBySupportedCountryCodes($order = Criteria::ASC) Order by the supported_country_codes column
 * @method     ChildJobSiteRecordVersionQuery orderByResultsFilterType($order = Criteria::ASC) Order by the results_filter_type column
 * @method     ChildJobSiteRecordVersionQuery orderByVersion($order = Criteria::ASC) Order by the version column
 *
 * @method     ChildJobSiteRecordVersionQuery groupByJobSiteKey() Group by the jobsite_key column
 * @method     ChildJobSiteRecordVersionQuery groupByPluginClassName() Group by the plugin_class_name column
 * @method     ChildJobSiteRecordVersionQuery groupByDisplayName() Group by the display_name column
 * @method     ChildJobSiteRecordVersionQuery groupByisDisabled() Group by the is_disabled column
 * @method     ChildJobSiteRecordVersionQuery groupByLastPulledAt() Group by the date_last_pulled column
 * @method     ChildJobSiteRecordVersionQuery groupByLastRunAt() Group by the date_last_run column
 * @method     ChildJobSiteRecordVersionQuery groupByLastCompletedAt() Group by the date_last_completed column
 * @method     ChildJobSiteRecordVersionQuery groupByLastFailedAt() Group by the date_last_failed column
 * @method     ChildJobSiteRecordVersionQuery groupByLastUserSearchRunId() Group by the last_user_search_run_id column
 * @method     ChildJobSiteRecordVersionQuery groupBySupportedCountryCodes() Group by the supported_country_codes column
 * @method     ChildJobSiteRecordVersionQuery groupByResultsFilterType() Group by the results_filter_type column
 * @method     ChildJobSiteRecordVersionQuery groupByVersion() Group by the version column
 *
 * @method     ChildJobSiteRecordVersionQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildJobSiteRecordVersionQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildJobSiteRecordVersionQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildJobSiteRecordVersionQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildJobSiteRecordVersionQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildJobSiteRecordVersionQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildJobSiteRecordVersionQuery leftJoinJobSiteRecord($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobSiteRecord relation
 * @method     ChildJobSiteRecordVersionQuery rightJoinJobSiteRecord($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobSiteRecord relation
 * @method     ChildJobSiteRecordVersionQuery innerJoinJobSiteRecord($relationAlias = null) Adds a INNER JOIN clause to the query using the JobSiteRecord relation
 *
 * @method     ChildJobSiteRecordVersionQuery joinWithJobSiteRecord($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobSiteRecord relation
 *
 * @method     ChildJobSiteRecordVersionQuery leftJoinWithJobSiteRecord() Adds a LEFT JOIN clause and with to the query using the JobSiteRecord relation
 * @method     ChildJobSiteRecordVersionQuery rightJoinWithJobSiteRecord() Adds a RIGHT JOIN clause and with to the query using the JobSiteRecord relation
 * @method     ChildJobSiteRecordVersionQuery innerJoinWithJobSiteRecord() Adds a INNER JOIN clause and with to the query using the JobSiteRecord relation
 *
 * @method     \JobScooper\DataAccess\JobSiteRecordQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildJobSiteRecordVersion findOne(ConnectionInterface $con = null) Return the first ChildJobSiteRecordVersion matching the query
 * @method     ChildJobSiteRecordVersion findOneOrCreate(ConnectionInterface $con = null) Return the first ChildJobSiteRecordVersion matching the query, or a new ChildJobSiteRecordVersion object populated from the query conditions when no match is found
 *
 * @method     ChildJobSiteRecordVersion findOneByJobSiteKey(string $jobsite_key) Return the first ChildJobSiteRecordVersion filtered by the jobsite_key column
 * @method     ChildJobSiteRecordVersion findOneByPluginClassName(string $plugin_class_name) Return the first ChildJobSiteRecordVersion filtered by the plugin_class_name column
 * @method     ChildJobSiteRecordVersion findOneByDisplayName(string $display_name) Return the first ChildJobSiteRecordVersion filtered by the display_name column
 * @method     ChildJobSiteRecordVersion findOneByisDisabled(boolean $is_disabled) Return the first ChildJobSiteRecordVersion filtered by the is_disabled column
 * @method     ChildJobSiteRecordVersion findOneByLastPulledAt(string $date_last_pulled) Return the first ChildJobSiteRecordVersion filtered by the date_last_pulled column
 * @method     ChildJobSiteRecordVersion findOneByLastRunAt(string $date_last_run) Return the first ChildJobSiteRecordVersion filtered by the date_last_run column
 * @method     ChildJobSiteRecordVersion findOneByLastCompletedAt(string $date_last_completed) Return the first ChildJobSiteRecordVersion filtered by the date_last_completed column
 * @method     ChildJobSiteRecordVersion findOneByLastFailedAt(string $date_last_failed) Return the first ChildJobSiteRecordVersion filtered by the date_last_failed column
 * @method     ChildJobSiteRecordVersion findOneByLastUserSearchRunId(int $last_user_search_run_id) Return the first ChildJobSiteRecordVersion filtered by the last_user_search_run_id column
 * @method     ChildJobSiteRecordVersion findOneBySupportedCountryCodes(array $supported_country_codes) Return the first ChildJobSiteRecordVersion filtered by the supported_country_codes column
 * @method     ChildJobSiteRecordVersion findOneByResultsFilterType(int $results_filter_type) Return the first ChildJobSiteRecordVersion filtered by the results_filter_type column
 * @method     ChildJobSiteRecordVersion findOneByVersion(int $version) Return the first ChildJobSiteRecordVersion filtered by the version column *

 * @method     ChildJobSiteRecordVersion requirePk($key, ConnectionInterface $con = null) Return the ChildJobSiteRecordVersion by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOne(ConnectionInterface $con = null) Return the first ChildJobSiteRecordVersion matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSiteRecordVersion requireOneByJobSiteKey(string $jobsite_key) Return the first ChildJobSiteRecordVersion filtered by the jobsite_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByPluginClassName(string $plugin_class_name) Return the first ChildJobSiteRecordVersion filtered by the plugin_class_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByDisplayName(string $display_name) Return the first ChildJobSiteRecordVersion filtered by the display_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByisDisabled(boolean $is_disabled) Return the first ChildJobSiteRecordVersion filtered by the is_disabled column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByLastPulledAt(string $date_last_pulled) Return the first ChildJobSiteRecordVersion filtered by the date_last_pulled column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByLastRunAt(string $date_last_run) Return the first ChildJobSiteRecordVersion filtered by the date_last_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByLastCompletedAt(string $date_last_completed) Return the first ChildJobSiteRecordVersion filtered by the date_last_completed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByLastFailedAt(string $date_last_failed) Return the first ChildJobSiteRecordVersion filtered by the date_last_failed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByLastUserSearchRunId(int $last_user_search_run_id) Return the first ChildJobSiteRecordVersion filtered by the last_user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneBySupportedCountryCodes(array $supported_country_codes) Return the first ChildJobSiteRecordVersion filtered by the supported_country_codes column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByResultsFilterType(int $results_filter_type) Return the first ChildJobSiteRecordVersion filtered by the results_filter_type column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecordVersion requireOneByVersion(int $version) Return the first ChildJobSiteRecordVersion filtered by the version column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildJobSiteRecordVersion objects based on current ModelCriteria
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByJobSiteKey(string $jobsite_key) Return ChildJobSiteRecordVersion objects filtered by the jobsite_key column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByPluginClassName(string $plugin_class_name) Return ChildJobSiteRecordVersion objects filtered by the plugin_class_name column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByDisplayName(string $display_name) Return ChildJobSiteRecordVersion objects filtered by the display_name column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByisDisabled(boolean $is_disabled) Return ChildJobSiteRecordVersion objects filtered by the is_disabled column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByLastPulledAt(string $date_last_pulled) Return ChildJobSiteRecordVersion objects filtered by the date_last_pulled column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByLastRunAt(string $date_last_run) Return ChildJobSiteRecordVersion objects filtered by the date_last_run column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByLastCompletedAt(string $date_last_completed) Return ChildJobSiteRecordVersion objects filtered by the date_last_completed column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByLastFailedAt(string $date_last_failed) Return ChildJobSiteRecordVersion objects filtered by the date_last_failed column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByLastUserSearchRunId(int $last_user_search_run_id) Return ChildJobSiteRecordVersion objects filtered by the last_user_search_run_id column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findBySupportedCountryCodes(array $supported_country_codes) Return ChildJobSiteRecordVersion objects filtered by the supported_country_codes column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByResultsFilterType(int $results_filter_type) Return ChildJobSiteRecordVersion objects filtered by the results_filter_type column
 * @method     ChildJobSiteRecordVersion[]|ObjectCollection findByVersion(int $version) Return ChildJobSiteRecordVersion objects filtered by the version column
 * @method     ChildJobSiteRecordVersion[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class JobSiteRecordVersionQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\DataAccess\Base\JobSiteRecordVersionQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\DataAccess\\JobSiteRecordVersion', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildJobSiteRecordVersionQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildJobSiteRecordVersionQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildJobSiteRecordVersionQuery) {
            return $criteria;
        }
        $query = new ChildJobSiteRecordVersionQuery();
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
     * $obj = $c->findPk(array(12, 34), $con);
     * </code>
     *
     * @param array[$jobsite_key, $version] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildJobSiteRecordVersion|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(JobSiteRecordVersionTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = JobSiteRecordVersionTableMap::getInstanceFromPool(serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1])]))))) {
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
     * @return ChildJobSiteRecordVersion A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT jobsite_key, plugin_class_name, display_name, is_disabled, date_last_pulled, date_last_run, date_last_completed, date_last_failed, supported_country_codes, results_filter_type, version FROM job_site_version WHERE jobsite_key = :p0 AND version = :p1';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key[0], PDO::PARAM_STR);
            $stmt->bindValue(':p1', $key[1], PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildJobSiteRecordVersion $obj */
            $obj = new ChildJobSiteRecordVersion();
            $obj->hydrate($row);
            JobSiteRecordVersionTableMap::addInstanceToPool($obj, serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1])]));
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
     * @return ChildJobSiteRecordVersion|array|mixed the result, formatted by the current formatter
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
     * $objs = $c->findPks(array(array(12, 56), array(832, 123), array(123, 456)), $con);
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
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_VERSION, $key[1], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(JobSiteRecordVersionTableMap::COL_VERSION, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $this->addOr($cton0);
        }

        return $this;
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
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByJobSiteKey($jobSiteKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobSiteKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY, $jobSiteKey, $comparison);
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
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByPluginClassName($pluginClassName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($pluginClassName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_PLUGIN_CLASS_NAME, $pluginClassName, $comparison);
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
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByDisplayName($displayName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($displayName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DISPLAY_NAME, $displayName, $comparison);
    }

    /**
     * Filter the query on the is_disabled column
     *
     * Example usage:
     * <code>
     * $query->filterByisDisabled(true); // WHERE is_disabled = true
     * $query->filterByisDisabled('yes'); // WHERE is_disabled = true
     * </code>
     *
     * @param     boolean|string $isDisabled The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByisDisabled($isDisabled = null, $comparison = null)
    {
        if (is_string($isDisabled)) {
            $isDisabled = in_array(strtolower($isDisabled), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_IS_DISABLED, $isDisabled, $comparison);
    }

    /**
     * Filter the query on the date_last_pulled column
     *
     * Example usage:
     * <code>
     * $query->filterByLastPulledAt('2011-03-14'); // WHERE date_last_pulled = '2011-03-14'
     * $query->filterByLastPulledAt('now'); // WHERE date_last_pulled = '2011-03-14'
     * $query->filterByLastPulledAt(array('max' => 'yesterday')); // WHERE date_last_pulled > '2011-03-13'
     * </code>
     *
     * @param     mixed $lastPulledAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByLastPulledAt($lastPulledAt = null, $comparison = null)
    {
        if (is_array($lastPulledAt)) {
            $useMinMax = false;
            if (isset($lastPulledAt['min'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_PULLED, $lastPulledAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastPulledAt['max'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_PULLED, $lastPulledAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_PULLED, $lastPulledAt, $comparison);
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
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByLastRunAt($lastRunAt = null, $comparison = null)
    {
        if (is_array($lastRunAt)) {
            $useMinMax = false;
            if (isset($lastRunAt['min'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_RUN, $lastRunAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastRunAt['max'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_RUN, $lastRunAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_RUN, $lastRunAt, $comparison);
    }

    /**
     * Filter the query on the date_last_completed column
     *
     * Example usage:
     * <code>
     * $query->filterByLastCompletedAt('2011-03-14'); // WHERE date_last_completed = '2011-03-14'
     * $query->filterByLastCompletedAt('now'); // WHERE date_last_completed = '2011-03-14'
     * $query->filterByLastCompletedAt(array('max' => 'yesterday')); // WHERE date_last_completed > '2011-03-13'
     * </code>
     *
     * @param     mixed $lastCompletedAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByLastCompletedAt($lastCompletedAt = null, $comparison = null)
    {
        if (is_array($lastCompletedAt)) {
            $useMinMax = false;
            if (isset($lastCompletedAt['min'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastCompletedAt['max'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt, $comparison);
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
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByLastFailedAt($lastFailedAt = null, $comparison = null)
    {
        if (is_array($lastFailedAt)) {
            $useMinMax = false;
            if (isset($lastFailedAt['min'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastFailedAt['max'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_DATE_LAST_FAILED, $lastFailedAt, $comparison);
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
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByLastUserSearchRunId($lastUserSearchRunId = null, $comparison = null)
    {
        if (is_array($lastUserSearchRunId)) {
            $useMinMax = false;
            if (isset($lastUserSearchRunId['min'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastUserSearchRunId['max'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId, $comparison);
    }

    /**
     * Filter the query on the supported_country_codes column
     *
     * @param     array $supportedCountryCodes The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterBySupportedCountryCodes($supportedCountryCodes = null, $comparison = null)
    {
        $key = $this->getAliasedColName(JobSiteRecordVersionTableMap::COL_SUPPORTED_COUNTRY_CODES);
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

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_SUPPORTED_COUNTRY_CODES, $supportedCountryCodes, $comparison);
    }

    /**
     * Filter the query on the supported_country_codes column
     * @param     mixed $supportedCountryCodes The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
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
            $key = $this->getAliasedColName(JobSiteRecordVersionTableMap::COL_SUPPORTED_COUNTRY_CODES);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $supportedCountryCodes, $comparison);
            } else {
                $this->addAnd($key, $supportedCountryCodes, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_SUPPORTED_COUNTRY_CODES, $supportedCountryCodes, $comparison);
    }

    /**
     * Filter the query on the results_filter_type column
     *
     * @param     mixed $resultsFilterType The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByResultsFilterType($resultsFilterType = null, $comparison = null)
    {
        $valueSet = JobSiteRecordVersionTableMap::getValueSet(JobSiteRecordVersionTableMap::COL_RESULTS_FILTER_TYPE);
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

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_RESULTS_FILTER_TYPE, $resultsFilterType, $comparison);
    }

    /**
     * Filter the query on the version column
     *
     * Example usage:
     * <code>
     * $query->filterByVersion(1234); // WHERE version = 1234
     * $query->filterByVersion(array(12, 34)); // WHERE version IN (12, 34)
     * $query->filterByVersion(array('min' => 12)); // WHERE version > 12
     * </code>
     *
     * @param     mixed $version The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByVersion($version = null, $comparison = null)
    {
        if (is_array($version)) {
            $useMinMax = false;
            if (isset($version['min'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_VERSION, $version['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($version['max'])) {
                $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_VERSION, $version['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordVersionTableMap::COL_VERSION, $version, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\JobSiteRecord object
     *
     * @param \JobScooper\DataAccess\JobSiteRecord|ObjectCollection $jobSiteRecord The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function filterByJobSiteRecord($jobSiteRecord, $comparison = null)
    {
        if ($jobSiteRecord instanceof \JobScooper\DataAccess\JobSiteRecord) {
            return $this
                ->addUsingAlias(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY, $jobSiteRecord->getJobSiteKey(), $comparison);
        } elseif ($jobSiteRecord instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY, $jobSiteRecord->toKeyValue('PrimaryKey', 'JobSiteKey'), $comparison);
        } else {
            throw new PropelException('filterByJobSiteRecord() only accepts arguments of type \JobScooper\DataAccess\JobSiteRecord or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the JobSiteRecord relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function joinJobSiteRecord($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('JobSiteRecord');

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
            $this->addJoinObject($join, 'JobSiteRecord');
        }

        return $this;
    }

    /**
     * Use the JobSiteRecord relation JobSiteRecord object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\JobSiteRecordQuery A secondary query class using the current class as primary query
     */
    public function useJobSiteRecordQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinJobSiteRecord($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobSiteRecord', '\JobScooper\DataAccess\JobSiteRecordQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildJobSiteRecordVersion $jobSiteRecordVersion Object to remove from the list of results
     *
     * @return $this|ChildJobSiteRecordVersionQuery The current query, for fluid interface
     */
    public function prune($jobSiteRecordVersion = null)
    {
        if ($jobSiteRecordVersion) {
            $this->addCond('pruneCond0', $this->getAliasedColName(JobSiteRecordVersionTableMap::COL_JOBSITE_KEY), $jobSiteRecordVersion->getJobSiteKey(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(JobSiteRecordVersionTableMap::COL_VERSION), $jobSiteRecordVersion->getVersion(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the job_site_version table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSiteRecordVersionTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            JobSiteRecordVersionTableMap::clearInstancePool();
            JobSiteRecordVersionTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSiteRecordVersionTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(JobSiteRecordVersionTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            JobSiteRecordVersionTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            JobSiteRecordVersionTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // JobSiteRecordVersionQuery
