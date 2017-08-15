<?php

namespace JobScooper\Base;

use \Exception;
use \PDO;
use JobScooper\UserSearchRun as ChildUserSearchRun;
use JobScooper\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\Map\UserSearchRunTableMap;
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
 * @method     ChildUserSearchRunQuery orderByKey($order = Criteria::ASC) Order by the key column
 * @method     ChildUserSearchRunQuery orderByAppRunId($order = Criteria::ASC) Order by the app_run_id column
 * @method     ChildUserSearchRunQuery orderByUserSlug($order = Criteria::ASC) Order by the user_slug column
 * @method     ChildUserSearchRunQuery orderByDateSearchRun($order = Criteria::ASC) Order by the date_search_run column
 * @method     ChildUserSearchRunQuery orderByJobSite($order = Criteria::ASC) Order by the jobsite column
 * @method     ChildUserSearchRunQuery orderBySearchSettings($order = Criteria::ASC) Order by the search_settings column
 * @method     ChildUserSearchRunQuery orderBySearchRunResult($order = Criteria::ASC) Order by the search_run_result column
 * @method     ChildUserSearchRunQuery orderByUpdatedAt($order = Criteria::ASC) Order by the updated_at column
 *
 * @method     ChildUserSearchRunQuery groupByUserSearchRunId() Group by the user_search_run_id column
 * @method     ChildUserSearchRunQuery groupByKey() Group by the key column
 * @method     ChildUserSearchRunQuery groupByAppRunId() Group by the app_run_id column
 * @method     ChildUserSearchRunQuery groupByUserSlug() Group by the user_slug column
 * @method     ChildUserSearchRunQuery groupByDateSearchRun() Group by the date_search_run column
 * @method     ChildUserSearchRunQuery groupByJobSite() Group by the jobsite column
 * @method     ChildUserSearchRunQuery groupBySearchSettings() Group by the search_settings column
 * @method     ChildUserSearchRunQuery groupBySearchRunResult() Group by the search_run_result column
 * @method     ChildUserSearchRunQuery groupByUpdatedAt() Group by the updated_at column
 *
 * @method     ChildUserSearchRunQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchRunQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchRunQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchRunQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchRunQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchRunQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
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
 * @method     \JobScooper\UserQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearchRun findOne(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query
 * @method     ChildUserSearchRun findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query, or a new ChildUserSearchRun object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearchRun findOneByUserSearchRunId(int $user_search_run_id) Return the first ChildUserSearchRun filtered by the user_search_run_id column
 * @method     ChildUserSearchRun findOneByKey(string $key) Return the first ChildUserSearchRun filtered by the key column
 * @method     ChildUserSearchRun findOneByAppRunId(string $app_run_id) Return the first ChildUserSearchRun filtered by the app_run_id column
 * @method     ChildUserSearchRun findOneByUserSlug(string $user_slug) Return the first ChildUserSearchRun filtered by the user_slug column
 * @method     ChildUserSearchRun findOneByDateSearchRun(string $date_search_run) Return the first ChildUserSearchRun filtered by the date_search_run column
 * @method     ChildUserSearchRun findOneByJobSite(string $jobsite) Return the first ChildUserSearchRun filtered by the jobsite column
 * @method     ChildUserSearchRun findOneBySearchSettings(array $search_settings) Return the first ChildUserSearchRun filtered by the search_settings column
 * @method     ChildUserSearchRun findOneBySearchRunResult( $search_run_result) Return the first ChildUserSearchRun filtered by the search_run_result column
 * @method     ChildUserSearchRun findOneByUpdatedAt(string $updated_at) Return the first ChildUserSearchRun filtered by the updated_at column *

 * @method     ChildUserSearchRun requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearchRun by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOne(ConnectionInterface $con = null) Return the first ChildUserSearchRun matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchRun requireOneByUserSearchRunId(int $user_search_run_id) Return the first ChildUserSearchRun filtered by the user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByKey(string $key) Return the first ChildUserSearchRun filtered by the key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByAppRunId(string $app_run_id) Return the first ChildUserSearchRun filtered by the app_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByUserSlug(string $user_slug) Return the first ChildUserSearchRun filtered by the user_slug column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByDateSearchRun(string $date_search_run) Return the first ChildUserSearchRun filtered by the date_search_run column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByJobSite(string $jobsite) Return the first ChildUserSearchRun filtered by the jobsite column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneBySearchSettings(array $search_settings) Return the first ChildUserSearchRun filtered by the search_settings column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneBySearchRunResult( $search_run_result) Return the first ChildUserSearchRun filtered by the search_run_result column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRun requireOneByUpdatedAt(string $updated_at) Return the first ChildUserSearchRun filtered by the updated_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchRun[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearchRun objects based on current ModelCriteria
 * @method     ChildUserSearchRun[]|ObjectCollection findByUserSearchRunId(int $user_search_run_id) Return ChildUserSearchRun objects filtered by the user_search_run_id column
 * @method     ChildUserSearchRun[]|ObjectCollection findByKey(string $key) Return ChildUserSearchRun objects filtered by the key column
 * @method     ChildUserSearchRun[]|ObjectCollection findByAppRunId(string $app_run_id) Return ChildUserSearchRun objects filtered by the app_run_id column
 * @method     ChildUserSearchRun[]|ObjectCollection findByUserSlug(string $user_slug) Return ChildUserSearchRun objects filtered by the user_slug column
 * @method     ChildUserSearchRun[]|ObjectCollection findByDateSearchRun(string $date_search_run) Return ChildUserSearchRun objects filtered by the date_search_run column
 * @method     ChildUserSearchRun[]|ObjectCollection findByJobSite(string $jobsite) Return ChildUserSearchRun objects filtered by the jobsite column
 * @method     ChildUserSearchRun[]|ObjectCollection findBySearchSettings(array $search_settings) Return ChildUserSearchRun objects filtered by the search_settings column
 * @method     ChildUserSearchRun[]|ObjectCollection findBySearchRunResult( $search_run_result) Return ChildUserSearchRun objects filtered by the search_run_result column
 * @method     ChildUserSearchRun[]|ObjectCollection findByUpdatedAt(string $updated_at) Return ChildUserSearchRun objects filtered by the updated_at column
 * @method     ChildUserSearchRun[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchRunQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\Base\UserSearchRunQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\UserSearchRun', $modelAlias = null)
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
        $sql = 'SELECT user_search_run_id, key, app_run_id, user_slug, date_search_run, jobsite, search_settings, search_run_result, updated_at FROM user_search_run WHERE user_search_run_id = :p0';
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
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByKey($key = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($key)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_KEY, $key, $comparison);
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
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByDateSearchRun($dateSearchRun = null, $comparison = null)
    {
        if (is_array($dateSearchRun)) {
            $useMinMax = false;
            if (isset($dateSearchRun['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_SEARCH_RUN, $dateSearchRun['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dateSearchRun['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_SEARCH_RUN, $dateSearchRun['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_SEARCH_RUN, $dateSearchRun, $comparison);
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
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByJobSite($jobSite = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobSite)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_JOBSITE, $jobSite, $comparison);
    }

    /**
     * Filter the query on the search_settings column
     *
     * @param     array $searchSettings The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterBySearchSettings($searchSettings = null, $comparison = null)
    {
        $key = $this->getAliasedColName(UserSearchRunTableMap::COL_SEARCH_SETTINGS);
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

        return $this->addUsingAlias(UserSearchRunTableMap::COL_SEARCH_SETTINGS, $searchSettings, $comparison);
    }

    /**
     * Filter the query on the search_settings column
     * @param     mixed $searchSettings The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
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
            $key = $this->getAliasedColName(UserSearchRunTableMap::COL_SEARCH_SETTINGS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $searchSettings, $comparison);
            } else {
                $this->addAnd($key, $searchSettings, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_SEARCH_SETTINGS, $searchSettings, $comparison);
    }

    /**
     * Filter the query on the search_run_result column
     *
     * @param     mixed $searchRunResult The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterBySearchRunResult($searchRunResult = null, $comparison = null)
    {
        if (is_object($searchRunResult)) {
            $searchRunResult = serialize($searchRunResult);
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_SEARCH_RUN_RESULT, $searchRunResult, $comparison);
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
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUpdatedAt($updatedAt = null, $comparison = null)
    {
        if (is_array($updatedAt)) {
            $useMinMax = false;
            if (isset($updatedAt['min'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_UPDATED_AT, $updatedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($updatedAt['max'])) {
                $this->addUsingAlias(UserSearchRunTableMap::COL_UPDATED_AT, $updatedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunTableMap::COL_UPDATED_AT, $updatedAt, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\User object
     *
     * @param \JobScooper\User|ObjectCollection $user The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function filterByUser($user, $comparison = null)
    {
        if ($user instanceof \JobScooper\User) {
            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_USER_SLUG, $user->getUserSlug(), $comparison);
        } elseif ($user instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchRunTableMap::COL_USER_SLUG, $user->toKeyValue('PrimaryKey', 'UserSlug'), $comparison);
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
     * @return $this|ChildUserSearchRunQuery The current query, for fluid interface
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
        return $this->addUsingAlias(UserSearchRunTableMap::COL_UPDATED_AT, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by update date desc
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function lastUpdatedFirst()
    {
        return $this->addDescendingOrderByColumn(UserSearchRunTableMap::COL_UPDATED_AT);
    }

    /**
     * Order by update date asc
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function firstUpdatedFirst()
    {
        return $this->addAscendingOrderByColumn(UserSearchRunTableMap::COL_UPDATED_AT);
    }

    /**
     * Order by create date desc
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function lastCreatedFirst()
    {
        return $this->addDescendingOrderByColumn(UserSearchRunTableMap::COL_DATE_SEARCH_RUN);
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
        return $this->addUsingAlias(UserSearchRunTableMap::COL_DATE_SEARCH_RUN, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by create date asc
     *
     * @return     $this|ChildUserSearchRunQuery The current query, for fluid interface
     */
    public function firstCreatedFirst()
    {
        return $this->addAscendingOrderByColumn(UserSearchRunTableMap::COL_DATE_SEARCH_RUN);
    }

} // UserSearchRunQuery
