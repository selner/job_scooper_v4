<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\JobSiteRecord as ChildJobSiteRecord;
use JobScooper\DataAccess\JobSiteRecordQuery as ChildJobSiteRecordQuery;
use JobScooper\DataAccess\Map\JobSiteRecordTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'job_site' table.
 *
 *
 *
 * @method     ChildJobSiteRecordQuery orderByJobSiteKey($order = Criteria::ASC) Order by the jobsite_key column
 * @method     ChildJobSiteRecordQuery orderByPluginClassName($order = Criteria::ASC) Order by the plugin_class_name column
 * @method     ChildJobSiteRecordQuery orderByDisplayName($order = Criteria::ASC) Order by the display_name column
 * @method     ChildJobSiteRecordQuery orderByisDisabled($order = Criteria::ASC) Order by the is_disabled column
 * @method     ChildJobSiteRecordQuery orderByLastPulledAt($order = Criteria::ASC) Order by the date_last_pulled column
 * @method     ChildJobSiteRecordQuery orderByLastRunAt($order = Criteria::ASC) Order by the date_last_run column
 * @method     ChildJobSiteRecordQuery orderByLastCompletedAt($order = Criteria::ASC) Order by the date_last_completed column
 * @method     ChildJobSiteRecordQuery orderByLastFailedAt($order = Criteria::ASC) Order by the date_last_failed column
 * @method     ChildJobSiteRecordQuery orderByLastUserSearchRunId($order = Criteria::ASC) Order by the last_user_search_run_id column
 * @method     ChildJobSiteRecordQuery orderBySupportedCountryCodes($order = Criteria::ASC) Order by the supported_country_codes column
 * @method     ChildJobSiteRecordQuery orderByResultsFilterType($order = Criteria::ASC) Order by the results_filter_type column
 * @method     ChildJobSiteRecordQuery orderByVersion($order = Criteria::ASC) Order by the version column
 *
 * @method     ChildJobSiteRecordQuery groupByJobSiteKey() Group by the jobsite_key column
 * @method     ChildJobSiteRecordQuery groupByPluginClassName() Group by the plugin_class_name column
 * @method     ChildJobSiteRecordQuery groupByDisplayName() Group by the display_name column
 * @method     ChildJobSiteRecordQuery groupByisDisabled() Group by the is_disabled column
 * @method     ChildJobSiteRecordQuery groupByLastPulledAt() Group by the date_last_pulled column
 * @method     ChildJobSiteRecordQuery groupByLastRunAt() Group by the date_last_run column
 * @method     ChildJobSiteRecordQuery groupByLastCompletedAt() Group by the date_last_completed column
 * @method     ChildJobSiteRecordQuery groupByLastFailedAt() Group by the date_last_failed column
 * @method     ChildJobSiteRecordQuery groupByLastUserSearchRunId() Group by the last_user_search_run_id column
 * @method     ChildJobSiteRecordQuery groupBySupportedCountryCodes() Group by the supported_country_codes column
 * @method     ChildJobSiteRecordQuery groupByResultsFilterType() Group by the results_filter_type column
 * @method     ChildJobSiteRecordQuery groupByVersion() Group by the version column
 *
 * @method     ChildJobSiteRecordQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildJobSiteRecordQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildJobSiteRecordQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildJobSiteRecordQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildJobSiteRecordQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildJobSiteRecordQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildJobSiteRecordQuery leftJoinUserSearchRunRelatedByLastUserSearchRunId($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchRunRelatedByLastUserSearchRunId relation
 * @method     ChildJobSiteRecordQuery rightJoinUserSearchRunRelatedByLastUserSearchRunId($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchRunRelatedByLastUserSearchRunId relation
 * @method     ChildJobSiteRecordQuery innerJoinUserSearchRunRelatedByLastUserSearchRunId($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchRunRelatedByLastUserSearchRunId relation
 *
 * @method     ChildJobSiteRecordQuery joinWithUserSearchRunRelatedByLastUserSearchRunId($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchRunRelatedByLastUserSearchRunId relation
 *
 * @method     ChildJobSiteRecordQuery leftJoinWithUserSearchRunRelatedByLastUserSearchRunId() Adds a LEFT JOIN clause and with to the query using the UserSearchRunRelatedByLastUserSearchRunId relation
 * @method     ChildJobSiteRecordQuery rightJoinWithUserSearchRunRelatedByLastUserSearchRunId() Adds a RIGHT JOIN clause and with to the query using the UserSearchRunRelatedByLastUserSearchRunId relation
 * @method     ChildJobSiteRecordQuery innerJoinWithUserSearchRunRelatedByLastUserSearchRunId() Adds a INNER JOIN clause and with to the query using the UserSearchRunRelatedByLastUserSearchRunId relation
 *
 * @method     ChildJobSiteRecordQuery leftJoinJobPosting($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobPosting relation
 * @method     ChildJobSiteRecordQuery rightJoinJobPosting($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobPosting relation
 * @method     ChildJobSiteRecordQuery innerJoinJobPosting($relationAlias = null) Adds a INNER JOIN clause to the query using the JobPosting relation
 *
 * @method     ChildJobSiteRecordQuery joinWithJobPosting($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobPosting relation
 *
 * @method     ChildJobSiteRecordQuery leftJoinWithJobPosting() Adds a LEFT JOIN clause and with to the query using the JobPosting relation
 * @method     ChildJobSiteRecordQuery rightJoinWithJobPosting() Adds a RIGHT JOIN clause and with to the query using the JobPosting relation
 * @method     ChildJobSiteRecordQuery innerJoinWithJobPosting() Adds a INNER JOIN clause and with to the query using the JobPosting relation
 *
 * @method     ChildJobSiteRecordQuery leftJoinUserSearchRunRelatedByJobSiteKey($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchRunRelatedByJobSiteKey relation
 * @method     ChildJobSiteRecordQuery rightJoinUserSearchRunRelatedByJobSiteKey($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchRunRelatedByJobSiteKey relation
 * @method     ChildJobSiteRecordQuery innerJoinUserSearchRunRelatedByJobSiteKey($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchRunRelatedByJobSiteKey relation
 *
 * @method     ChildJobSiteRecordQuery joinWithUserSearchRunRelatedByJobSiteKey($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchRunRelatedByJobSiteKey relation
 *
 * @method     ChildJobSiteRecordQuery leftJoinWithUserSearchRunRelatedByJobSiteKey() Adds a LEFT JOIN clause and with to the query using the UserSearchRunRelatedByJobSiteKey relation
 * @method     ChildJobSiteRecordQuery rightJoinWithUserSearchRunRelatedByJobSiteKey() Adds a RIGHT JOIN clause and with to the query using the UserSearchRunRelatedByJobSiteKey relation
 * @method     ChildJobSiteRecordQuery innerJoinWithUserSearchRunRelatedByJobSiteKey() Adds a INNER JOIN clause and with to the query using the UserSearchRunRelatedByJobSiteKey relation
 *
 * @method     ChildJobSiteRecordQuery leftJoinJobSiteRecordVersion($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobSiteRecordVersion relation
 * @method     ChildJobSiteRecordQuery rightJoinJobSiteRecordVersion($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobSiteRecordVersion relation
 * @method     ChildJobSiteRecordQuery innerJoinJobSiteRecordVersion($relationAlias = null) Adds a INNER JOIN clause to the query using the JobSiteRecordVersion relation
 *
 * @method     ChildJobSiteRecordQuery joinWithJobSiteRecordVersion($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobSiteRecordVersion relation
 *
 * @method     ChildJobSiteRecordQuery leftJoinWithJobSiteRecordVersion() Adds a LEFT JOIN clause and with to the query using the JobSiteRecordVersion relation
 * @method     ChildJobSiteRecordQuery rightJoinWithJobSiteRecordVersion() Adds a RIGHT JOIN clause and with to the query using the JobSiteRecordVersion relation
 * @method     ChildJobSiteRecordQuery innerJoinWithJobSiteRecordVersion() Adds a INNER JOIN clause and with to the query using the JobSiteRecordVersion relation
 *
 * @method     \JobScooper\DataAccess\UserSearchRunQuery|\JobScooper\DataAccess\JobPostingQuery|\JobScooper\DataAccess\JobSiteRecordVersionQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildJobSiteRecord findOne(ConnectionInterface $con = null) Return the first ChildJobSiteRecord matching the query
 * @method     ChildJobSiteRecord findOneOrCreate(ConnectionInterface $con = null) Return the first ChildJobSiteRecord matching the query, or a new ChildJobSiteRecord object populated from the query conditions when no match is found
 *
 * @method     ChildJobSiteRecord findOneByJobSiteKey(string $jobsite_key) Return the first ChildJobSiteRecord filtered by the jobsite_key column
 * @method     ChildJobSiteRecord findOneByPluginClassName(string $plugin_class_name) Return the first ChildJobSiteRecord filtered by the plugin_class_name column
 * @method     ChildJobSiteRecord findOneByDisplayName(string $display_name) Return the first ChildJobSiteRecord filtered by the display_name column
 * @method     ChildJobSiteRecord findOneByisDisabled(boolean $is_disabled) Return the first ChildJobSiteRecord filtered by the is_disabled column
 * @method     ChildJobSiteRecord findOneByLastPulledAt(string $date_last_pulled) Return the first ChildJobSiteRecord filtered by the date_last_pulled column
 * @method     ChildJobSiteRecord findOneByLastRunAt(string $date_last_run) Return the first ChildJobSiteRecord filtered by the date_last_run column
 * @method     ChildJobSiteRecord findOneByLastCompletedAt(string $date_last_completed) Return the first ChildJobSiteRecord filtered by the date_last_completed column
 * @method     ChildJobSiteRecord findOneByLastFailedAt(string $date_last_failed) Return the first ChildJobSiteRecord filtered by the date_last_failed column
 * @method     ChildJobSiteRecord findOneByLastUserSearchRunId(int $last_user_search_run_id) Return the first ChildJobSiteRecord filtered by the last_user_search_run_id column
 * @method     ChildJobSiteRecord findOneBySupportedCountryCodes(array $supported_country_codes) Return the first ChildJobSiteRecord filtered by the supported_country_codes column
 * @method     ChildJobSiteRecord findOneByResultsFilterType(int $results_filter_type) Return the first ChildJobSiteRecord filtered by the results_filter_type column
 * @method     ChildJobSiteRecord findOneByVersion(int $version) Return the first ChildJobSiteRecord filtered by the version column *

 * @method     ChildJobSiteRecord requirePk($key, ConnectionInterface $con = null) Return the ChildJobSiteRecord by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOne(ConnectionInterface $con = null) Return the first ChildJobSiteRecord matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSiteRecord requireOneByJobSiteKey(string $jobsite_key) Return the first ChildJobSiteRecord filtered by the jobsite_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByPluginClassName(string $plugin_class_name) Return the first ChildJobSiteRecord filtered by the plugin_class_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByDisplayName(string $display_name) Return the first ChildJobSiteRecord filtered by the display_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByisDisabled(boolean $is_disabled) Return the first ChildJobSiteRecord filtered by the is_disabled column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByLastPulledAt(string $date_last_pulled) Return the first ChildJobSiteRecord filtered by the date_last_pulled column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByLastRunAt(string $date_last_run) Return the first ChildJobSiteRecord filtered by the date_last_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByLastCompletedAt(string $date_last_completed) Return the first ChildJobSiteRecord filtered by the date_last_completed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByLastFailedAt(string $date_last_failed) Return the first ChildJobSiteRecord filtered by the date_last_failed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByLastUserSearchRunId(int $last_user_search_run_id) Return the first ChildJobSiteRecord filtered by the last_user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneBySupportedCountryCodes(array $supported_country_codes) Return the first ChildJobSiteRecord filtered by the supported_country_codes column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByResultsFilterType(int $results_filter_type) Return the first ChildJobSiteRecord filtered by the results_filter_type column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSiteRecord requireOneByVersion(int $version) Return the first ChildJobSiteRecord filtered by the version column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSiteRecord[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildJobSiteRecord objects based on current ModelCriteria
 * @method     ChildJobSiteRecord[]|ObjectCollection findByJobSiteKey(string $jobsite_key) Return ChildJobSiteRecord objects filtered by the jobsite_key column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByPluginClassName(string $plugin_class_name) Return ChildJobSiteRecord objects filtered by the plugin_class_name column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByDisplayName(string $display_name) Return ChildJobSiteRecord objects filtered by the display_name column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByisDisabled(boolean $is_disabled) Return ChildJobSiteRecord objects filtered by the is_disabled column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByLastPulledAt(string $date_last_pulled) Return ChildJobSiteRecord objects filtered by the date_last_pulled column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByLastRunAt(string $date_last_run) Return ChildJobSiteRecord objects filtered by the date_last_run column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByLastCompletedAt(string $date_last_completed) Return ChildJobSiteRecord objects filtered by the date_last_completed column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByLastFailedAt(string $date_last_failed) Return ChildJobSiteRecord objects filtered by the date_last_failed column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByLastUserSearchRunId(int $last_user_search_run_id) Return ChildJobSiteRecord objects filtered by the last_user_search_run_id column
 * @method     ChildJobSiteRecord[]|ObjectCollection findBySupportedCountryCodes(array $supported_country_codes) Return ChildJobSiteRecord objects filtered by the supported_country_codes column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByResultsFilterType(int $results_filter_type) Return ChildJobSiteRecord objects filtered by the results_filter_type column
 * @method     ChildJobSiteRecord[]|ObjectCollection findByVersion(int $version) Return ChildJobSiteRecord objects filtered by the version column
 * @method     ChildJobSiteRecord[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class JobSiteRecordQuery extends ModelCriteria
{

    // versionable behavior

    /**
     * Whether the versioning is enabled
     */
    static $isVersioningEnabled = true;
protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\DataAccess\Base\JobSiteRecordQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\DataAccess\\JobSiteRecord', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildJobSiteRecordQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildJobSiteRecordQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildJobSiteRecordQuery) {
            return $criteria;
        }
        $query = new ChildJobSiteRecordQuery();
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
     * @return ChildJobSiteRecord|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(JobSiteRecordTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = JobSiteRecordTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
     * @return ChildJobSiteRecord A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT jobsite_key, plugin_class_name, display_name, is_disabled, date_last_pulled, date_last_run, date_last_completed, date_last_failed, supported_country_codes, results_filter_type, version FROM job_site WHERE jobsite_key = :p0';
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
            /** @var ChildJobSiteRecord $obj */
            $obj = new ChildJobSiteRecord();
            $obj->hydrate($row);
            JobSiteRecordTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return ChildJobSiteRecord|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_JOBSITE_KEY, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_JOBSITE_KEY, $keys, Criteria::IN);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByJobSiteKey($jobSiteKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobSiteKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_JOBSITE_KEY, $jobSiteKey, $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByPluginClassName($pluginClassName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($pluginClassName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_PLUGIN_CLASS_NAME, $pluginClassName, $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByDisplayName($displayName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($displayName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_DISPLAY_NAME, $displayName, $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByisDisabled($isDisabled = null, $comparison = null)
    {
        if (is_string($isDisabled)) {
            $isDisabled = in_array(strtolower($isDisabled), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_IS_DISABLED, $isDisabled, $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByLastPulledAt($lastPulledAt = null, $comparison = null)
    {
        if (is_array($lastPulledAt)) {
            $useMinMax = false;
            if (isset($lastPulledAt['min'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_PULLED, $lastPulledAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastPulledAt['max'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_PULLED, $lastPulledAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_PULLED, $lastPulledAt, $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByLastRunAt($lastRunAt = null, $comparison = null)
    {
        if (is_array($lastRunAt)) {
            $useMinMax = false;
            if (isset($lastRunAt['min'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_RUN, $lastRunAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastRunAt['max'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_RUN, $lastRunAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_RUN, $lastRunAt, $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByLastCompletedAt($lastCompletedAt = null, $comparison = null)
    {
        if (is_array($lastCompletedAt)) {
            $useMinMax = false;
            if (isset($lastCompletedAt['min'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastCompletedAt['max'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt, $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByLastFailedAt($lastFailedAt = null, $comparison = null)
    {
        if (is_array($lastFailedAt)) {
            $useMinMax = false;
            if (isset($lastFailedAt['min'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastFailedAt['max'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_FAILED, $lastFailedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_DATE_LAST_FAILED, $lastFailedAt, $comparison);
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
     * @see       filterByUserSearchRunRelatedByLastUserSearchRunId()
     *
     * @param     mixed $lastUserSearchRunId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByLastUserSearchRunId($lastUserSearchRunId = null, $comparison = null)
    {
        if (is_array($lastUserSearchRunId)) {
            $useMinMax = false;
            if (isset($lastUserSearchRunId['min'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastUserSearchRunId['max'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID, $lastUserSearchRunId, $comparison);
    }

    /**
     * Filter the query on the supported_country_codes column
     *
     * @param     array $supportedCountryCodes The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterBySupportedCountryCodes($supportedCountryCodes = null, $comparison = null)
    {
        $key = $this->getAliasedColName(JobSiteRecordTableMap::COL_SUPPORTED_COUNTRY_CODES);
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

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_SUPPORTED_COUNTRY_CODES, $supportedCountryCodes, $comparison);
    }

    /**
     * Filter the query on the supported_country_codes column
     * @param     mixed $supportedCountryCodes The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
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
            $key = $this->getAliasedColName(JobSiteRecordTableMap::COL_SUPPORTED_COUNTRY_CODES);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $supportedCountryCodes, $comparison);
            } else {
                $this->addAnd($key, $supportedCountryCodes, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_SUPPORTED_COUNTRY_CODES, $supportedCountryCodes, $comparison);
    }

    /**
     * Filter the query on the results_filter_type column
     *
     * @param     mixed $resultsFilterType The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByResultsFilterType($resultsFilterType = null, $comparison = null)
    {
        $valueSet = JobSiteRecordTableMap::getValueSet(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE);
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

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE, $resultsFilterType, $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByVersion($version = null, $comparison = null)
    {
        if (is_array($version)) {
            $useMinMax = false;
            if (isset($version['min'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_VERSION, $version['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($version['max'])) {
                $this->addUsingAlias(JobSiteRecordTableMap::COL_VERSION, $version['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSiteRecordTableMap::COL_VERSION, $version, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\UserSearchRun object
     *
     * @param \JobScooper\DataAccess\UserSearchRun|ObjectCollection $userSearchRun The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByUserSearchRunRelatedByLastUserSearchRunId($userSearchRun, $comparison = null)
    {
        if ($userSearchRun instanceof \JobScooper\DataAccess\UserSearchRun) {
            return $this
                ->addUsingAlias(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID, $userSearchRun->getUserSearchRunId(), $comparison);
        } elseif ($userSearchRun instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID, $userSearchRun->toKeyValue('PrimaryKey', 'UserSearchRunId'), $comparison);
        } else {
            throw new PropelException('filterByUserSearchRunRelatedByLastUserSearchRunId() only accepts arguments of type \JobScooper\DataAccess\UserSearchRun or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearchRunRelatedByLastUserSearchRunId relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function joinUserSearchRunRelatedByLastUserSearchRunId($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserSearchRunRelatedByLastUserSearchRunId');

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
            $this->addJoinObject($join, 'UserSearchRunRelatedByLastUserSearchRunId');
        }

        return $this;
    }

    /**
     * Use the UserSearchRunRelatedByLastUserSearchRunId relation UserSearchRun object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserSearchRunQuery A secondary query class using the current class as primary query
     */
    public function useUserSearchRunRelatedByLastUserSearchRunIdQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinUserSearchRunRelatedByLastUserSearchRunId($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearchRunRelatedByLastUserSearchRunId', '\JobScooper\DataAccess\UserSearchRunQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\JobPosting object
     *
     * @param \JobScooper\DataAccess\JobPosting|ObjectCollection $jobPosting the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByJobPosting($jobPosting, $comparison = null)
    {
        if ($jobPosting instanceof \JobScooper\DataAccess\JobPosting) {
            return $this
                ->addUsingAlias(JobSiteRecordTableMap::COL_JOBSITE_KEY, $jobPosting->getJobSiteKey(), $comparison);
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
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function joinJobPosting($relationAlias = null, $joinType = Criteria::INNER_JOIN)
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
    public function useJobPostingQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinJobPosting($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobPosting', '\JobScooper\DataAccess\JobPostingQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\UserSearchRun object
     *
     * @param \JobScooper\DataAccess\UserSearchRun|ObjectCollection $userSearchRun the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByUserSearchRunRelatedByJobSiteKey($userSearchRun, $comparison = null)
    {
        if ($userSearchRun instanceof \JobScooper\DataAccess\UserSearchRun) {
            return $this
                ->addUsingAlias(JobSiteRecordTableMap::COL_JOBSITE_KEY, $userSearchRun->getJobSiteKey(), $comparison);
        } elseif ($userSearchRun instanceof ObjectCollection) {
            return $this
                ->useUserSearchRunRelatedByJobSiteKeyQuery()
                ->filterByPrimaryKeys($userSearchRun->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByUserSearchRunRelatedByJobSiteKey() only accepts arguments of type \JobScooper\DataAccess\UserSearchRun or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearchRunRelatedByJobSiteKey relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function joinUserSearchRunRelatedByJobSiteKey($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserSearchRunRelatedByJobSiteKey');

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
            $this->addJoinObject($join, 'UserSearchRunRelatedByJobSiteKey');
        }

        return $this;
    }

    /**
     * Use the UserSearchRunRelatedByJobSiteKey relation UserSearchRun object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserSearchRunQuery A secondary query class using the current class as primary query
     */
    public function useUserSearchRunRelatedByJobSiteKeyQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUserSearchRunRelatedByJobSiteKey($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearchRunRelatedByJobSiteKey', '\JobScooper\DataAccess\UserSearchRunQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\JobSiteRecordVersion object
     *
     * @param \JobScooper\DataAccess\JobSiteRecordVersion|ObjectCollection $jobSiteRecordVersion the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function filterByJobSiteRecordVersion($jobSiteRecordVersion, $comparison = null)
    {
        if ($jobSiteRecordVersion instanceof \JobScooper\DataAccess\JobSiteRecordVersion) {
            return $this
                ->addUsingAlias(JobSiteRecordTableMap::COL_JOBSITE_KEY, $jobSiteRecordVersion->getJobSiteKey(), $comparison);
        } elseif ($jobSiteRecordVersion instanceof ObjectCollection) {
            return $this
                ->useJobSiteRecordVersionQuery()
                ->filterByPrimaryKeys($jobSiteRecordVersion->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByJobSiteRecordVersion() only accepts arguments of type \JobScooper\DataAccess\JobSiteRecordVersion or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the JobSiteRecordVersion relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function joinJobSiteRecordVersion($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('JobSiteRecordVersion');

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
            $this->addJoinObject($join, 'JobSiteRecordVersion');
        }

        return $this;
    }

    /**
     * Use the JobSiteRecordVersion relation JobSiteRecordVersion object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\JobSiteRecordVersionQuery A secondary query class using the current class as primary query
     */
    public function useJobSiteRecordVersionQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinJobSiteRecordVersion($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobSiteRecordVersion', '\JobScooper\DataAccess\JobSiteRecordVersionQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildJobSiteRecord $jobSiteRecord Object to remove from the list of results
     *
     * @return $this|ChildJobSiteRecordQuery The current query, for fluid interface
     */
    public function prune($jobSiteRecord = null)
    {
        if ($jobSiteRecord) {
            $this->addUsingAlias(JobSiteRecordTableMap::COL_JOBSITE_KEY, $jobSiteRecord->getJobSiteKey(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the job_site table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSiteRecordTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            JobSiteRecordTableMap::clearInstancePool();
            JobSiteRecordTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSiteRecordTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(JobSiteRecordTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            JobSiteRecordTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            JobSiteRecordTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // versionable behavior

    /**
     * Checks whether versioning is enabled
     *
     * @return boolean
     */
    static public function isVersioningEnabled()
    {
        return self::$isVersioningEnabled;
    }

    /**
     * Enables versioning
     */
    static public function enableVersioning()
    {
        self::$isVersioningEnabled = true;
    }

    /**
     * Disables versioning
     */
    static public function disableVersioning()
    {
        self::$isVersioningEnabled = false;
    }

} // JobSiteRecordQuery
