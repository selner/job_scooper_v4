<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\UserSearchRunVersion as ChildUserSearchRunVersion;
use JobScooper\DataAccess\UserSearchRunVersionQuery as ChildUserSearchRunVersionQuery;
use JobScooper\DataAccess\Map\UserSearchRunVersionTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'user_search_run_version' table.
 *
 *
 *
 * @method     ChildUserSearchRunVersionQuery orderByUserSearchRunId($order = Criteria::ASC) Order by the user_search_run_id column
 * @method     ChildUserSearchRunVersionQuery orderBySearchParametersData($order = Criteria::ASC) Order by the search_parameters_data column
 * @method     ChildUserSearchRunVersionQuery orderByAppRunId($order = Criteria::ASC) Order by the last_app_run_id column
 * @method     ChildUserSearchRunVersionQuery orderByRunResultCode($order = Criteria::ASC) Order by the run_result column
 * @method     ChildUserSearchRunVersionQuery orderByRunErrorDetails($order = Criteria::ASC) Order by the run_error_details column
 * @method     ChildUserSearchRunVersionQuery orderByVersion($order = Criteria::ASC) Order by the version column
 *
 * @method     ChildUserSearchRunVersionQuery groupByUserSearchRunId() Group by the user_search_run_id column
 * @method     ChildUserSearchRunVersionQuery groupBySearchParametersData() Group by the search_parameters_data column
 * @method     ChildUserSearchRunVersionQuery groupByAppRunId() Group by the last_app_run_id column
 * @method     ChildUserSearchRunVersionQuery groupByRunResultCode() Group by the run_result column
 * @method     ChildUserSearchRunVersionQuery groupByRunErrorDetails() Group by the run_error_details column
 * @method     ChildUserSearchRunVersionQuery groupByVersion() Group by the version column
 *
 * @method     ChildUserSearchRunVersionQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchRunVersionQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchRunVersionQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchRunVersionQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchRunVersionQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchRunVersionQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserSearchRunVersionQuery leftJoinUserSearchRun($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchRun relation
 * @method     ChildUserSearchRunVersionQuery rightJoinUserSearchRun($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchRun relation
 * @method     ChildUserSearchRunVersionQuery innerJoinUserSearchRun($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchRun relation
 *
 * @method     ChildUserSearchRunVersionQuery joinWithUserSearchRun($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchRun relation
 *
 * @method     ChildUserSearchRunVersionQuery leftJoinWithUserSearchRun() Adds a LEFT JOIN clause and with to the query using the UserSearchRun relation
 * @method     ChildUserSearchRunVersionQuery rightJoinWithUserSearchRun() Adds a RIGHT JOIN clause and with to the query using the UserSearchRun relation
 * @method     ChildUserSearchRunVersionQuery innerJoinWithUserSearchRun() Adds a INNER JOIN clause and with to the query using the UserSearchRun relation
 *
 * @method     \JobScooper\DataAccess\UserSearchRunQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearchRunVersion findOne(ConnectionInterface $con = null) Return the first ChildUserSearchRunVersion matching the query
 * @method     ChildUserSearchRunVersion findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearchRunVersion matching the query, or a new ChildUserSearchRunVersion object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearchRunVersion findOneByUserSearchRunId(int $user_search_run_id) Return the first ChildUserSearchRunVersion filtered by the user_search_run_id column
 * @method     ChildUserSearchRunVersion findOneBySearchParametersData(string $search_parameters_data) Return the first ChildUserSearchRunVersion filtered by the search_parameters_data column
 * @method     ChildUserSearchRunVersion findOneByAppRunId(string $last_app_run_id) Return the first ChildUserSearchRunVersion filtered by the last_app_run_id column
 * @method     ChildUserSearchRunVersion findOneByRunResultCode(int $run_result) Return the first ChildUserSearchRunVersion filtered by the run_result column
 * @method     ChildUserSearchRunVersion findOneByRunErrorDetails(array $run_error_details) Return the first ChildUserSearchRunVersion filtered by the run_error_details column
 * @method     ChildUserSearchRunVersion findOneByVersion(int $version) Return the first ChildUserSearchRunVersion filtered by the version column *

 * @method     ChildUserSearchRunVersion requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearchRunVersion by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRunVersion requireOne(ConnectionInterface $con = null) Return the first ChildUserSearchRunVersion matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchRunVersion requireOneByUserSearchRunId(int $user_search_run_id) Return the first ChildUserSearchRunVersion filtered by the user_search_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRunVersion requireOneBySearchParametersData(string $search_parameters_data) Return the first ChildUserSearchRunVersion filtered by the search_parameters_data column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRunVersion requireOneByAppRunId(string $last_app_run_id) Return the first ChildUserSearchRunVersion filtered by the last_app_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRunVersion requireOneByRunResultCode(int $run_result) Return the first ChildUserSearchRunVersion filtered by the run_result column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRunVersion requireOneByRunErrorDetails(array $run_error_details) Return the first ChildUserSearchRunVersion filtered by the run_error_details column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchRunVersion requireOneByVersion(int $version) Return the first ChildUserSearchRunVersion filtered by the version column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchRunVersion[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearchRunVersion objects based on current ModelCriteria
 * @method     ChildUserSearchRunVersion[]|ObjectCollection findByUserSearchRunId(int $user_search_run_id) Return ChildUserSearchRunVersion objects filtered by the user_search_run_id column
 * @method     ChildUserSearchRunVersion[]|ObjectCollection findBySearchParametersData(string $search_parameters_data) Return ChildUserSearchRunVersion objects filtered by the search_parameters_data column
 * @method     ChildUserSearchRunVersion[]|ObjectCollection findByAppRunId(string $last_app_run_id) Return ChildUserSearchRunVersion objects filtered by the last_app_run_id column
 * @method     ChildUserSearchRunVersion[]|ObjectCollection findByRunResultCode(int $run_result) Return ChildUserSearchRunVersion objects filtered by the run_result column
 * @method     ChildUserSearchRunVersion[]|ObjectCollection findByRunErrorDetails(array $run_error_details) Return ChildUserSearchRunVersion objects filtered by the run_error_details column
 * @method     ChildUserSearchRunVersion[]|ObjectCollection findByVersion(int $version) Return ChildUserSearchRunVersion objects filtered by the version column
 * @method     ChildUserSearchRunVersion[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchRunVersionQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\DataAccess\Base\UserSearchRunVersionQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\DataAccess\\UserSearchRunVersion', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUserSearchRunVersionQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildUserSearchRunVersionQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildUserSearchRunVersionQuery) {
            return $criteria;
        }
        $query = new ChildUserSearchRunVersionQuery();
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
     * @param array[$user_search_run_id, $version] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildUserSearchRunVersion|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchRunVersionTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = UserSearchRunVersionTableMap::getInstanceFromPool(serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1])]))))) {
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
     * @return ChildUserSearchRunVersion A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT user_search_run_id, search_parameters_data, last_app_run_id, run_result, run_error_details, version FROM user_search_run_version WHERE user_search_run_id = :p0 AND version = :p1';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key[0], PDO::PARAM_INT);
            $stmt->bindValue(':p1', $key[1], PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildUserSearchRunVersion $obj */
            $obj = new ChildUserSearchRunVersion();
            $obj->hydrate($row);
            UserSearchRunVersionTableMap::addInstanceToPool($obj, serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1])]));
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
     * @return ChildUserSearchRunVersion|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(UserSearchRunVersionTableMap::COL_VERSION, $key[1], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(UserSearchRunVersionTableMap::COL_VERSION, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $this->addOr($cton0);
        }

        return $this;
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
     * @see       filterByUserSearchRun()
     *
     * @param     mixed $userSearchRunId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterByUserSearchRunId($userSearchRunId = null, $comparison = null)
    {
        if (is_array($userSearchRunId)) {
            $useMinMax = false;
            if (isset($userSearchRunId['min'])) {
                $this->addUsingAlias(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRunId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userSearchRunId['max'])) {
                $this->addUsingAlias(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRunId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRunId, $comparison);
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
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterBySearchParametersData($searchParametersData = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($searchParametersData)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunVersionTableMap::COL_SEARCH_PARAMETERS_DATA, $searchParametersData, $comparison);
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
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterByAppRunId($appRunId = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($appRunId)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunVersionTableMap::COL_LAST_APP_RUN_ID, $appRunId, $comparison);
    }

    /**
     * Filter the query on the run_result column
     *
     * @param     mixed $runResultCode The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterByRunResultCode($runResultCode = null, $comparison = null)
    {
        $valueSet = UserSearchRunVersionTableMap::getValueSet(UserSearchRunVersionTableMap::COL_RUN_RESULT);
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

        return $this->addUsingAlias(UserSearchRunVersionTableMap::COL_RUN_RESULT, $runResultCode, $comparison);
    }

    /**
     * Filter the query on the run_error_details column
     *
     * @param     array $runErrorDetails The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterByRunErrorDetails($runErrorDetails = null, $comparison = null)
    {
        $key = $this->getAliasedColName(UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS);
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

        return $this->addUsingAlias(UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS, $runErrorDetails, $comparison);
    }

    /**
     * Filter the query on the run_error_details column
     * @param     mixed $runErrorDetails The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
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
            $key = $this->getAliasedColName(UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $runErrorDetails, $comparison);
            } else {
                $this->addAnd($key, $runErrorDetails, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS, $runErrorDetails, $comparison);
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
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterByVersion($version = null, $comparison = null)
    {
        if (is_array($version)) {
            $useMinMax = false;
            if (isset($version['min'])) {
                $this->addUsingAlias(UserSearchRunVersionTableMap::COL_VERSION, $version['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($version['max'])) {
                $this->addUsingAlias(UserSearchRunVersionTableMap::COL_VERSION, $version['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchRunVersionTableMap::COL_VERSION, $version, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\UserSearchRun object
     *
     * @param \JobScooper\DataAccess\UserSearchRun|ObjectCollection $userSearchRun The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function filterByUserSearchRun($userSearchRun, $comparison = null)
    {
        if ($userSearchRun instanceof \JobScooper\DataAccess\UserSearchRun) {
            return $this
                ->addUsingAlias(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRun->getUserSearchRunId(), $comparison);
        } elseif ($userSearchRun instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $userSearchRun->toKeyValue('PrimaryKey', 'UserSearchRunId'), $comparison);
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
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function joinUserSearchRun($relationAlias = null, $joinType = Criteria::INNER_JOIN)
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
    public function useUserSearchRunQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUserSearchRun($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearchRun', '\JobScooper\DataAccess\UserSearchRunQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildUserSearchRunVersion $userSearchRunVersion Object to remove from the list of results
     *
     * @return $this|ChildUserSearchRunVersionQuery The current query, for fluid interface
     */
    public function prune($userSearchRunVersion = null)
    {
        if ($userSearchRunVersion) {
            $this->addCond('pruneCond0', $this->getAliasedColName(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID), $userSearchRunVersion->getUserSearchRunId(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(UserSearchRunVersionTableMap::COL_VERSION), $userSearchRunVersion->getVersion(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the user_search_run_version table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunVersionTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UserSearchRunVersionTableMap::clearInstancePool();
            UserSearchRunVersionTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunVersionTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UserSearchRunVersionTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            UserSearchRunVersionTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            UserSearchRunVersionTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // UserSearchRunVersionQuery
