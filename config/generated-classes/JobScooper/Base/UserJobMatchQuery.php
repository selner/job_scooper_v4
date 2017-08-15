<?php

namespace JobScooper\Base;

use \Exception;
use \PDO;
use JobScooper\UserJobMatch as ChildUserJobMatch;
use JobScooper\UserJobMatchQuery as ChildUserJobMatchQuery;
use JobScooper\Map\UserJobMatchTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'user_job_match' table.
 *
 *
 *
 * @method     ChildUserJobMatchQuery orderByUserSlug($order = Criteria::ASC) Order by the user_slug column
 * @method     ChildUserJobMatchQuery orderByJobPostingId($order = Criteria::ASC) Order by the jobposting_id column
 * @method     ChildUserJobMatchQuery orderByUserNotificationState($order = Criteria::ASC) Order by the user_notification_state column
 * @method     ChildUserJobMatchQuery orderByUserMatchStatus($order = Criteria::ASC) Order by the user_match_status column
 * @method     ChildUserJobMatchQuery orderByUserMatchExcludeReason($order = Criteria::ASC) Order by the user_match_exclude_reason column
 * @method     ChildUserJobMatchQuery orderByAppRunId($order = Criteria::ASC) Order by the app_run_id column
 *
 * @method     ChildUserJobMatchQuery groupByUserSlug() Group by the user_slug column
 * @method     ChildUserJobMatchQuery groupByJobPostingId() Group by the jobposting_id column
 * @method     ChildUserJobMatchQuery groupByUserNotificationState() Group by the user_notification_state column
 * @method     ChildUserJobMatchQuery groupByUserMatchStatus() Group by the user_match_status column
 * @method     ChildUserJobMatchQuery groupByUserMatchExcludeReason() Group by the user_match_exclude_reason column
 * @method     ChildUserJobMatchQuery groupByAppRunId() Group by the app_run_id column
 *
 * @method     ChildUserJobMatchQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserJobMatchQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserJobMatchQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserJobMatchQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserJobMatchQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserJobMatchQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserJobMatchQuery leftJoinUser($relationAlias = null) Adds a LEFT JOIN clause to the query using the User relation
 * @method     ChildUserJobMatchQuery rightJoinUser($relationAlias = null) Adds a RIGHT JOIN clause to the query using the User relation
 * @method     ChildUserJobMatchQuery innerJoinUser($relationAlias = null) Adds a INNER JOIN clause to the query using the User relation
 *
 * @method     ChildUserJobMatchQuery joinWithUser($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the User relation
 *
 * @method     ChildUserJobMatchQuery leftJoinWithUser() Adds a LEFT JOIN clause and with to the query using the User relation
 * @method     ChildUserJobMatchQuery rightJoinWithUser() Adds a RIGHT JOIN clause and with to the query using the User relation
 * @method     ChildUserJobMatchQuery innerJoinWithUser() Adds a INNER JOIN clause and with to the query using the User relation
 *
 * @method     ChildUserJobMatchQuery leftJoinJobPosting($relationAlias = null) Adds a LEFT JOIN clause to the query using the JobPosting relation
 * @method     ChildUserJobMatchQuery rightJoinJobPosting($relationAlias = null) Adds a RIGHT JOIN clause to the query using the JobPosting relation
 * @method     ChildUserJobMatchQuery innerJoinJobPosting($relationAlias = null) Adds a INNER JOIN clause to the query using the JobPosting relation
 *
 * @method     ChildUserJobMatchQuery joinWithJobPosting($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the JobPosting relation
 *
 * @method     ChildUserJobMatchQuery leftJoinWithJobPosting() Adds a LEFT JOIN clause and with to the query using the JobPosting relation
 * @method     ChildUserJobMatchQuery rightJoinWithJobPosting() Adds a RIGHT JOIN clause and with to the query using the JobPosting relation
 * @method     ChildUserJobMatchQuery innerJoinWithJobPosting() Adds a INNER JOIN clause and with to the query using the JobPosting relation
 *
 * @method     \JobScooper\UserQuery|\JobScooper\JobPostingQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserJobMatch findOne(ConnectionInterface $con = null) Return the first ChildUserJobMatch matching the query
 * @method     ChildUserJobMatch findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserJobMatch matching the query, or a new ChildUserJobMatch object populated from the query conditions when no match is found
 *
 * @method     ChildUserJobMatch findOneByUserSlug(string $user_slug) Return the first ChildUserJobMatch filtered by the user_slug column
 * @method     ChildUserJobMatch findOneByJobPostingId(int $jobposting_id) Return the first ChildUserJobMatch filtered by the jobposting_id column
 * @method     ChildUserJobMatch findOneByUserNotificationState(int $user_notification_state) Return the first ChildUserJobMatch filtered by the user_notification_state column
 * @method     ChildUserJobMatch findOneByUserMatchStatus(int $user_match_status) Return the first ChildUserJobMatch filtered by the user_match_status column
 * @method     ChildUserJobMatch findOneByUserMatchExcludeReason(string $user_match_exclude_reason) Return the first ChildUserJobMatch filtered by the user_match_exclude_reason column
 * @method     ChildUserJobMatch findOneByAppRunId(string $app_run_id) Return the first ChildUserJobMatch filtered by the app_run_id column *

 * @method     ChildUserJobMatch requirePk($key, ConnectionInterface $con = null) Return the ChildUserJobMatch by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserJobMatch requireOne(ConnectionInterface $con = null) Return the first ChildUserJobMatch matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserJobMatch requireOneByUserSlug(string $user_slug) Return the first ChildUserJobMatch filtered by the user_slug column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserJobMatch requireOneByJobPostingId(int $jobposting_id) Return the first ChildUserJobMatch filtered by the jobposting_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserJobMatch requireOneByUserNotificationState(int $user_notification_state) Return the first ChildUserJobMatch filtered by the user_notification_state column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserJobMatch requireOneByUserMatchStatus(int $user_match_status) Return the first ChildUserJobMatch filtered by the user_match_status column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserJobMatch requireOneByUserMatchExcludeReason(string $user_match_exclude_reason) Return the first ChildUserJobMatch filtered by the user_match_exclude_reason column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserJobMatch requireOneByAppRunId(string $app_run_id) Return the first ChildUserJobMatch filtered by the app_run_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserJobMatch[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserJobMatch objects based on current ModelCriteria
 * @method     ChildUserJobMatch[]|ObjectCollection findByUserSlug(string $user_slug) Return ChildUserJobMatch objects filtered by the user_slug column
 * @method     ChildUserJobMatch[]|ObjectCollection findByJobPostingId(int $jobposting_id) Return ChildUserJobMatch objects filtered by the jobposting_id column
 * @method     ChildUserJobMatch[]|ObjectCollection findByUserNotificationState(int $user_notification_state) Return ChildUserJobMatch objects filtered by the user_notification_state column
 * @method     ChildUserJobMatch[]|ObjectCollection findByUserMatchStatus(int $user_match_status) Return ChildUserJobMatch objects filtered by the user_match_status column
 * @method     ChildUserJobMatch[]|ObjectCollection findByUserMatchExcludeReason(string $user_match_exclude_reason) Return ChildUserJobMatch objects filtered by the user_match_exclude_reason column
 * @method     ChildUserJobMatch[]|ObjectCollection findByAppRunId(string $app_run_id) Return ChildUserJobMatch objects filtered by the app_run_id column
 * @method     ChildUserJobMatch[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserJobMatchQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\Base\UserJobMatchQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\UserJobMatch', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUserJobMatchQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildUserJobMatchQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildUserJobMatchQuery) {
            return $criteria;
        }
        $query = new ChildUserJobMatchQuery();
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
     * @param array[$user_slug, $jobposting_id] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildUserJobMatch|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = UserJobMatchTableMap::getInstanceFromPool(serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1])]))))) {
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
     * @return ChildUserJobMatch A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT user_slug, jobposting_id, user_notification_state, user_match_status, user_match_exclude_reason, app_run_id FROM user_job_match WHERE user_slug = :p0 AND jobposting_id = :p1';
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
            /** @var ChildUserJobMatch $obj */
            $obj = new ChildUserJobMatch();
            $obj->hydrate($row);
            UserJobMatchTableMap::addInstanceToPool($obj, serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1])]));
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
     * @return ChildUserJobMatch|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(UserJobMatchTableMap::COL_USER_SLUG, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(UserJobMatchTableMap::COL_JOBPOSTING_ID, $key[1], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(UserJobMatchTableMap::COL_USER_SLUG, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(UserJobMatchTableMap::COL_JOBPOSTING_ID, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $this->addOr($cton0);
        }

        return $this;
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
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByUserSlug($userSlug = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userSlug)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserJobMatchTableMap::COL_USER_SLUG, $userSlug, $comparison);
    }

    /**
     * Filter the query on the jobposting_id column
     *
     * Example usage:
     * <code>
     * $query->filterByJobPostingId(1234); // WHERE jobposting_id = 1234
     * $query->filterByJobPostingId(array(12, 34)); // WHERE jobposting_id IN (12, 34)
     * $query->filterByJobPostingId(array('min' => 12)); // WHERE jobposting_id > 12
     * </code>
     *
     * @see       filterByJobPosting()
     *
     * @param     mixed $jobPostingId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByJobPostingId($jobPostingId = null, $comparison = null)
    {
        if (is_array($jobPostingId)) {
            $useMinMax = false;
            if (isset($jobPostingId['min'])) {
                $this->addUsingAlias(UserJobMatchTableMap::COL_JOBPOSTING_ID, $jobPostingId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($jobPostingId['max'])) {
                $this->addUsingAlias(UserJobMatchTableMap::COL_JOBPOSTING_ID, $jobPostingId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserJobMatchTableMap::COL_JOBPOSTING_ID, $jobPostingId, $comparison);
    }

    /**
     * Filter the query on the user_notification_state column
     *
     * @param     mixed $userNotificationState The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByUserNotificationState($userNotificationState = null, $comparison = null)
    {
        $valueSet = UserJobMatchTableMap::getValueSet(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE);
        if (is_scalar($userNotificationState)) {
            if (!in_array($userNotificationState, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $userNotificationState));
            }
            $userNotificationState = array_search($userNotificationState, $valueSet);
        } elseif (is_array($userNotificationState)) {
            $convertedValues = array();
            foreach ($userNotificationState as $value) {
                if (!in_array($value, $valueSet)) {
                    throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $value));
                }
                $convertedValues []= array_search($value, $valueSet);
            }
            $userNotificationState = $convertedValues;
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE, $userNotificationState, $comparison);
    }

    /**
     * Filter the query on the user_match_status column
     *
     * @param     mixed $userMatchStatus The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByUserMatchStatus($userMatchStatus = null, $comparison = null)
    {
        $valueSet = UserJobMatchTableMap::getValueSet(UserJobMatchTableMap::COL_USER_MATCH_STATUS);
        if (is_scalar($userMatchStatus)) {
            if (!in_array($userMatchStatus, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $userMatchStatus));
            }
            $userMatchStatus = array_search($userMatchStatus, $valueSet);
        } elseif (is_array($userMatchStatus)) {
            $convertedValues = array();
            foreach ($userMatchStatus as $value) {
                if (!in_array($value, $valueSet)) {
                    throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $value));
                }
                $convertedValues []= array_search($value, $valueSet);
            }
            $userMatchStatus = $convertedValues;
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserJobMatchTableMap::COL_USER_MATCH_STATUS, $userMatchStatus, $comparison);
    }

    /**
     * Filter the query on the user_match_exclude_reason column
     *
     * Example usage:
     * <code>
     * $query->filterByUserMatchExcludeReason('fooValue');   // WHERE user_match_exclude_reason = 'fooValue'
     * $query->filterByUserMatchExcludeReason('%fooValue%', Criteria::LIKE); // WHERE user_match_exclude_reason LIKE '%fooValue%'
     * </code>
     *
     * @param     string $userMatchExcludeReason The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByUserMatchExcludeReason($userMatchExcludeReason = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userMatchExcludeReason)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserJobMatchTableMap::COL_USER_MATCH_EXCLUDE_REASON, $userMatchExcludeReason, $comparison);
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
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByAppRunId($appRunId = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($appRunId)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserJobMatchTableMap::COL_APP_RUN_ID, $appRunId, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\User object
     *
     * @param \JobScooper\User|ObjectCollection $user The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByUser($user, $comparison = null)
    {
        if ($user instanceof \JobScooper\User) {
            return $this
                ->addUsingAlias(UserJobMatchTableMap::COL_USER_SLUG, $user->getUserSlug(), $comparison);
        } elseif ($user instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserJobMatchTableMap::COL_USER_SLUG, $user->toKeyValue('PrimaryKey', 'UserSlug'), $comparison);
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
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
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
     * @return \JobScooper\UserQuery A secondary query class using the current class as primary query
     */
    public function useUserQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUser($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'User', '\JobScooper\UserQuery');
    }

    /**
     * Filter the query by a related \JobScooper\JobPosting object
     *
     * @param \JobScooper\JobPosting|ObjectCollection $jobPosting The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function filterByJobPosting($jobPosting, $comparison = null)
    {
        if ($jobPosting instanceof \JobScooper\JobPosting) {
            return $this
                ->addUsingAlias(UserJobMatchTableMap::COL_JOBPOSTING_ID, $jobPosting->getJobPostingId(), $comparison);
        } elseif ($jobPosting instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserJobMatchTableMap::COL_JOBPOSTING_ID, $jobPosting->toKeyValue('PrimaryKey', 'JobPostingId'), $comparison);
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
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
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
     * @return \JobScooper\JobPostingQuery A secondary query class using the current class as primary query
     */
    public function useJobPostingQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinJobPosting($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'JobPosting', '\JobScooper\JobPostingQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildUserJobMatch $userJobMatch Object to remove from the list of results
     *
     * @return $this|ChildUserJobMatchQuery The current query, for fluid interface
     */
    public function prune($userJobMatch = null)
    {
        if ($userJobMatch) {
            $this->addCond('pruneCond0', $this->getAliasedColName(UserJobMatchTableMap::COL_USER_SLUG), $userJobMatch->getUserSlug(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(UserJobMatchTableMap::COL_JOBPOSTING_ID), $userJobMatch->getJobPostingId(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the user_job_match table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UserJobMatchTableMap::clearInstancePool();
            UserJobMatchTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UserJobMatchTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            UserJobMatchTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            UserJobMatchTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // UserJobMatchQuery
