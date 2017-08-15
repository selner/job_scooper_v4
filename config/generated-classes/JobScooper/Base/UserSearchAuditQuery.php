<?php

namespace JobScooper\Base;

use \Exception;
use \PDO;
use JobScooper\UserSearchAudit as ChildUserSearchAudit;
use JobScooper\UserSearchAuditQuery as ChildUserSearchAuditQuery;
use JobScooper\Map\UserSearchAuditTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'user_search_audit' table.
 *
 *
 *
 * @method     ChildUserSearchAuditQuery orderByUserSearchId($order = Criteria::ASC) Order by the user_search_run_id column
 * @method     ChildUserSearchAuditQuery orderByKey($order = Criteria::ASC) Order by the key column
 * @method     ChildUserSearchAuditQuery orderByAppRunId($order = Criteria::ASC) Order by the app_run_id column
 * @method     ChildUserSearchAuditQuery orderByUserSlug($order = Criteria::ASC) Order by the user_slug column
 * @method     ChildUserSearchAuditQuery orderByDateSearchRun($order = Criteria::ASC) Order by the date_search_run column
 * @method     ChildUserSearchAuditQuery orderByJobSite($order = Criteria::ASC) Order by the jobsite column
 * @method     ChildUserSearchAuditQuery orderBySearchSettings($order = Criteria::ASC) Order by the search_settings column
 * @method     ChildUserSearchAuditQuery orderBySearchRunResult($order = Criteria::ASC) Order by the search_run_result column
 * @method     ChildUserSearchAuditQuery orderByUpdatedAt($order = Criteria::ASC) Order by the updated_at column
 *
 * @method     ChildUserSearchAuditQuery groupByUserSearchId() Group by the user_search_run_id column
 * @method     ChildUserSearchAuditQuery groupByKey() Group by the key column
 * @method     ChildUserSearchAuditQuery groupByAppRunId() Group by the app_run_id column
 * @method     ChildUserSearchAuditQuery groupByUserSlug() Group by the user_slug column
 * @method     ChildUserSearchAuditQuery groupByDateSearchRun() Group by the date_search_run column
 * @method     ChildUserSearchAuditQuery groupByJobSite() Group by the jobsite column
 * @method     ChildUserSearchAuditQuery groupBySearchSettings() Group by the search_settings column
 * @method     ChildUserSearchAuditQuery groupBySearchRunResult() Group by the search_run_result column
 * @method     ChildUserSearchAuditQuery groupByUpdatedAt() Group by the updated_at column
 *
 * @method     ChildUserSearchAuditQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchAuditQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchAuditQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchAuditQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchAuditQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchAuditQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserSearchAuditQuery leftJoinUser($relationAlias = null) Adds a LEFT JOIN clause to the query using the User relation
 * @method     ChildUserSearchAuditQuery rightJoinUser($relationAlias = null) Adds a RIGHT JOIN clause to the query using the User relation
 * @method     ChildUserSearchAuditQuery innerJoinUser($relationAlias = null) Adds a INNER JOIN clause to the query using the User relation
 *
 * @method     ChildUserSearchAuditQuery joinWithUser($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the User relation
 *
 * @method     ChildUserSearchAuditQuery leftJoinWithUser() Adds a LEFT JOIN clause and with to the query using the User relation
 * @method     ChildUserSearchAuditQuery rightJoinWithUser() Adds a RIGHT JOIN clause and with to the query using the User relation
 * @method     ChildUserSearchAuditQuery innerJoinWithUser() Adds a INNER JOIN clause and with to the query using the User relation
 *
 * @method     \JobScooper\UserQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearchAudit findOne(ConnectionInterface $con = null) Return the first ChildUserSearchAudit matching the query
 * @method     ChildUserSearchAudit findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearchAudit matching the query, or a new ChildUserSearchAudit object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearchAudit findOneByUserSearchId(int $user_search_run_id) Return the first ChildUserSearchAudit filtered by the user_search_run_id column
 * @method     ChildUserSearchAudit findOneByKey(string $key) Return the first ChildUserSearchAudit filtered by the key column
 * @method     ChildUserSearchAudit findOneByAppRunId(string $app_run_id) Return the first ChildUserSearchAudit filtered by the app_run_id column
 * @method     ChildUserSearchAudit findOneByUserSlug(string $user_slug) Return the first ChildUserSearchAudit filtered by the user_slug column
 * @method     ChildUserSearchAudit findOneByDateSearchRun(string $date_search_run) Return the first ChildUserSearchAudit filtered by the date_search_run column
 * @method     ChildUserSearchAudit findOneByJobSite(string $jobsite) Return the first ChildUserSearchAudit filtered by the jobsite column
 * @method     ChildUserSearchAudit findOneBySearchSettings(array $search_settings) Return the first ChildUserSearchAudit filtered by the search_settings column
 * @method     ChildUserSearchAudit findOneBySearchRunResult( $search_run_result) Return the first ChildUserSearchAudit filtered by the search_run_result column
 * @method     ChildUserSearchAudit findOneByUpdatedAt(string $updated_at) Return the first ChildUserSearchAudit filtered by the updated_at column *

 * @method     ChildUserSearchAudit requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearchAudit by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOne(ConnectionInterface $con = null) Return the first ChildUserSearchAudit matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchAudit requireOneByUserSearchId(int $user_search_run_id) Return the first ChildUserSearchAudit filtered by the user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOneByKey(string $key) Return the first ChildUserSearchAudit filtered by the key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOneByAppRunId(string $app_run_id) Return the first ChildUserSearchAudit filtered by the app_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOneByUserSlug(string $user_slug) Return the first ChildUserSearchAudit filtered by the user_slug column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOneByDateSearchRun(string $date_search_run) Return the first ChildUserSearchAudit filtered by the date_search_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOneByJobSite(string $jobsite) Return the first ChildUserSearchAudit filtered by the jobsite column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOneBySearchSettings(array $search_settings) Return the first ChildUserSearchAudit filtered by the search_settings column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOneBySearchRunResult( $search_run_result) Return the first ChildUserSearchAudit filtered by the search_run_result column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchAudit requireOneByUpdatedAt(string $updated_at) Return the first ChildUserSearchAudit filtered by the updated_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchAudit[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearchAudit objects based on current ModelCriteria
 * @method     ChildUserSearchAudit[]|ObjectCollection findByUserSearchId(int $user_search_run_id) Return ChildUserSearchAudit objects filtered by the user_search_run_id column
 * @method     ChildUserSearchAudit[]|ObjectCollection findByKey(string $key) Return ChildUserSearchAudit objects filtered by the key column
 * @method     ChildUserSearchAudit[]|ObjectCollection findByAppRunId(string $app_run_id) Return ChildUserSearchAudit objects filtered by the app_run_id column
 * @method     ChildUserSearchAudit[]|ObjectCollection findByUserSlug(string $user_slug) Return ChildUserSearchAudit objects filtered by the user_slug column
 * @method     ChildUserSearchAudit[]|ObjectCollection findByDateSearchRun(string $date_search_run) Return ChildUserSearchAudit objects filtered by the date_search_run column
 * @method     ChildUserSearchAudit[]|ObjectCollection findByJobSite(string $jobsite) Return ChildUserSearchAudit objects filtered by the jobsite column
 * @method     ChildUserSearchAudit[]|ObjectCollection findBySearchSettings(array $search_settings) Return ChildUserSearchAudit objects filtered by the search_settings column
 * @method     ChildUserSearchAudit[]|ObjectCollection findBySearchRunResult( $search_run_result) Return ChildUserSearchAudit objects filtered by the search_run_result column
 * @method     ChildUserSearchAudit[]|ObjectCollection findByUpdatedAt(string $updated_at) Return ChildUserSearchAudit objects filtered by the updated_at column
 * @method     ChildUserSearchAudit[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchAuditQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\Base\UserSearchAuditQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\UserSearchAudit', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUserSearchAuditQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildUserSearchAuditQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildUserSearchAuditQuery) {
            return $criteria;
        }
        $query = new ChildUserSearchAuditQuery();
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
     * @return ChildUserSearchAudit|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchAuditTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = UserSearchAuditTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
     * @return ChildUserSearchAudit A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT user_search_run_id, key, app_run_id, user_slug, date_search_run, jobsite, search_settings, search_run_result, updated_at FROM user_search_audit WHERE user_search_run_id = :p0';
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
            /** @var ChildUserSearchAudit $obj */
            $obj = new ChildUserSearchAudit();
            $obj->hydrate($row);
            UserSearchAuditTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return ChildUserSearchAudit|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the user_search_run_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUserSearchId(1234); // WHERE user_search_run_id = 1234
     * $query->filterByUserSearchId(array(12, 34)); // WHERE user_search_run_id IN (12, 34)
     * $query->filterByUserSearchId(array('min' => 12)); // WHERE user_search_run_id > 12
     * </code>
     *
     * @param     mixed $userSearchId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByUserSearchId($userSearchId = null, $comparison = null)
    {
        if (is_array($userSearchId)) {
            $useMinMax = false;
            if (isset($userSearchId['min'])) {
                $this->addUsingAlias(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID, $userSearchId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userSearchId['max'])) {
                $this->addUsingAlias(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID, $userSearchId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID, $userSearchId, $comparison);
    }

    /**
     * Filter the query on the key column
     *
     * Example usage:
     * <code>
     * $query->filterByKey('fooValue');   // WHERE key = 'fooValue'
     * $query->filterByKey('%fooValue%', Criteria::LIKE); // WHERE key LIKE '%fooValue%'
     * </code>
     *
     * @param     string $key The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByKey($key = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($key)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_KEY, $key, $comparison);
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
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByAppRunId($appRunId = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($appRunId)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_APP_RUN_ID, $appRunId, $comparison);
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
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByUserSlug($userSlug = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userSlug)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_USER_SLUG, $userSlug, $comparison);
    }

    /**
     * Filter the query on the date_search_run column
     *
     * Example usage:
     * <code>
     * $query->filterByDateSearchRun('2011-03-14'); // WHERE date_search_run = '2011-03-14'
     * $query->filterByDateSearchRun('now'); // WHERE date_search_run = '2011-03-14'
     * $query->filterByDateSearchRun(array('max' => 'yesterday')); // WHERE date_search_run > '2011-03-13'
     * </code>
     *
     * @param     mixed $dateSearchRun The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByDateSearchRun($dateSearchRun = null, $comparison = null)
    {
        if (is_array($dateSearchRun)) {
            $useMinMax = false;
            if (isset($dateSearchRun['min'])) {
                $this->addUsingAlias(UserSearchAuditTableMap::COL_DATE_SEARCH_RUN, $dateSearchRun['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dateSearchRun['max'])) {
                $this->addUsingAlias(UserSearchAuditTableMap::COL_DATE_SEARCH_RUN, $dateSearchRun['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_DATE_SEARCH_RUN, $dateSearchRun, $comparison);
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
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByJobSite($jobSite = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobSite)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_JOBSITE, $jobSite, $comparison);
    }

    /**
     * Filter the query on the search_settings column
     *
     * @param     array $searchSettings The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterBySearchSettings($searchSettings = null, $comparison = null)
    {
        $key = $this->getAliasedColName(UserSearchAuditTableMap::COL_SEARCH_SETTINGS);
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            foreach ($searchSettings as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_SOME) {
            foreach ($searchSettings as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addOr($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            foreach ($searchSettings as $value) {
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

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_SEARCH_SETTINGS, $searchSettings, $comparison);
    }

    /**
     * Filter the query on the search_settings column
     * @param     mixed $searchSettings The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterBySearchSetting($searchSettings = null, $comparison = null)
    {
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            if (is_scalar($searchSettings)) {
                $searchSettings = '%| ' . $searchSettings . ' |%';
                $comparison = Criteria::LIKE;
            }
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            $searchSettings = '%| ' . $searchSettings . ' |%';
            $comparison = Criteria::NOT_LIKE;
            $key = $this->getAliasedColName(UserSearchAuditTableMap::COL_SEARCH_SETTINGS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $searchSettings, $comparison);
            } else {
                $this->addAnd($key, $searchSettings, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_SEARCH_SETTINGS, $searchSettings, $comparison);
    }

    /**
     * Filter the query on the search_run_result column
     *
     * @param     mixed $searchRunResult The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterBySearchRunResult($searchRunResult = null, $comparison = null)
    {
        if (is_object($searchRunResult)) {
            $searchRunResult = serialize($searchRunResult);
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_SEARCH_RUN_RESULT, $searchRunResult, $comparison);
    }

    /**
     * Filter the query on the updated_at column
     *
     * Example usage:
     * <code>
     * $query->filterByUpdatedAt('2011-03-14'); // WHERE updated_at = '2011-03-14'
     * $query->filterByUpdatedAt('now'); // WHERE updated_at = '2011-03-14'
     * $query->filterByUpdatedAt(array('max' => 'yesterday')); // WHERE updated_at > '2011-03-13'
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
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByUpdatedAt($updatedAt = null, $comparison = null)
    {
        if (is_array($updatedAt)) {
            $useMinMax = false;
            if (isset($updatedAt['min'])) {
                $this->addUsingAlias(UserSearchAuditTableMap::COL_UPDATED_AT, $updatedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($updatedAt['max'])) {
                $this->addUsingAlias(UserSearchAuditTableMap::COL_UPDATED_AT, $updatedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchAuditTableMap::COL_UPDATED_AT, $updatedAt, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\User object
     *
     * @param \JobScooper\User|ObjectCollection $user The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function filterByUser($user, $comparison = null)
    {
        if ($user instanceof \JobScooper\User) {
            return $this
                ->addUsingAlias(UserSearchAuditTableMap::COL_USER_SLUG, $user->getUserSlug(), $comparison);
        } elseif ($user instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchAuditTableMap::COL_USER_SLUG, $user->toKeyValue('PrimaryKey', 'UserSlug'), $comparison);
        } else {
            throw new PropelException('filterByUser() only accepts arguments of type \JobScooper\User or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the User relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function joinUser($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
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
     * @return \JobScooper\UserQuery A secondary query class using the current class as primary query
     */
    public function useUserQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinUser($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'User', '\JobScooper\UserQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildUserSearchAudit $userSearchAudit Object to remove from the list of results
     *
     * @return $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function prune($userSearchAudit = null)
    {
        if ($userSearchAudit) {
            $this->addUsingAlias(UserSearchAuditTableMap::COL_USER_SEARCH_RUN_ID, $userSearchAudit->getUserSearchId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the user_search_audit table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchAuditTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UserSearchAuditTableMap::clearInstancePool();
            UserSearchAuditTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchAuditTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UserSearchAuditTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            UserSearchAuditTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            UserSearchAuditTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // timestampable behavior

    /**
     * Filter by the latest updated
     *
     * @param      int $nbDays Maximum age of the latest update in days
     *
     * @return     $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function recentlyUpdated($nbDays = 7)
    {
        return $this->addUsingAlias(UserSearchAuditTableMap::COL_UPDATED_AT, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by update date desc
     *
     * @return     $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function lastUpdatedFirst()
    {
        return $this->addDescendingOrderByColumn(UserSearchAuditTableMap::COL_UPDATED_AT);
    }

    /**
     * Order by update date asc
     *
     * @return     $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function firstUpdatedFirst()
    {
        return $this->addAscendingOrderByColumn(UserSearchAuditTableMap::COL_UPDATED_AT);
    }

    /**
     * Order by create date desc
     *
     * @return     $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function lastCreatedFirst()
    {
        return $this->addDescendingOrderByColumn(UserSearchAuditTableMap::COL_DATE_SEARCH_RUN);
    }

    /**
     * Filter by the latest created
     *
     * @param      int $nbDays Maximum age of in days
     *
     * @return     $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function recentlyCreated($nbDays = 7)
    {
        return $this->addUsingAlias(UserSearchAuditTableMap::COL_DATE_SEARCH_RUN, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by create date asc
     *
     * @return     $this|ChildUserSearchAuditQuery The current query, for fluid interface
     */
    public function firstCreatedFirst()
    {
        return $this->addAscendingOrderByColumn(UserSearchAuditTableMap::COL_DATE_SEARCH_RUN);
    }

} // UserSearchAuditQuery
