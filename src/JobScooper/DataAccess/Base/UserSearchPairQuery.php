<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\UserSearchPair as ChildUserSearchPair;
use JobScooper\DataAccess\UserSearchPairQuery as ChildUserSearchPairQuery;
use JobScooper\DataAccess\Map\UserSearchPairTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'user_search_pair' table.
 *
 *
 *
 * @method     ChildUserSearchPairQuery orderByUserSearchPairId($order = Criteria::ASC) Order by the user_search_pair_id column
 * @method     ChildUserSearchPairQuery orderByUserId($order = Criteria::ASC) Order by the user_id column
 * @method     ChildUserSearchPairQuery orderByUserKeyword($order = Criteria::ASC) Order by the user_keyword column
 * @method     ChildUserSearchPairQuery orderByGeoLocationId($order = Criteria::ASC) Order by the geolocation_id column
 * @method     ChildUserSearchPairQuery orderByIsActive($order = Criteria::ASC) Order by the is_active column
 *
 * @method     ChildUserSearchPairQuery groupByUserSearchPairId() Group by the user_search_pair_id column
 * @method     ChildUserSearchPairQuery groupByUserId() Group by the user_id column
 * @method     ChildUserSearchPairQuery groupByUserKeyword() Group by the user_keyword column
 * @method     ChildUserSearchPairQuery groupByGeoLocationId() Group by the geolocation_id column
 * @method     ChildUserSearchPairQuery groupByIsActive() Group by the is_active column
 *
 * @method     ChildUserSearchPairQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchPairQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchPairQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchPairQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchPairQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchPairQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserSearchPairQuery leftJoinUserFromUS($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserFromUS relation
 * @method     ChildUserSearchPairQuery rightJoinUserFromUS($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserFromUS relation
 * @method     ChildUserSearchPairQuery innerJoinUserFromUS($relationAlias = null) Adds a INNER JOIN clause to the query using the UserFromUS relation
 *
 * @method     ChildUserSearchPairQuery joinWithUserFromUS($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserFromUS relation
 *
 * @method     ChildUserSearchPairQuery leftJoinWithUserFromUS() Adds a LEFT JOIN clause and with to the query using the UserFromUS relation
 * @method     ChildUserSearchPairQuery rightJoinWithUserFromUS() Adds a RIGHT JOIN clause and with to the query using the UserFromUS relation
 * @method     ChildUserSearchPairQuery innerJoinWithUserFromUS() Adds a INNER JOIN clause and with to the query using the UserFromUS relation
 *
 * @method     ChildUserSearchPairQuery leftJoinGeoLocationFromUS($relationAlias = null) Adds a LEFT JOIN clause to the query using the GeoLocationFromUS relation
 * @method     ChildUserSearchPairQuery rightJoinGeoLocationFromUS($relationAlias = null) Adds a RIGHT JOIN clause to the query using the GeoLocationFromUS relation
 * @method     ChildUserSearchPairQuery innerJoinGeoLocationFromUS($relationAlias = null) Adds a INNER JOIN clause to the query using the GeoLocationFromUS relation
 *
 * @method     ChildUserSearchPairQuery joinWithGeoLocationFromUS($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the GeoLocationFromUS relation
 *
 * @method     ChildUserSearchPairQuery leftJoinWithGeoLocationFromUS() Adds a LEFT JOIN clause and with to the query using the GeoLocationFromUS relation
 * @method     ChildUserSearchPairQuery rightJoinWithGeoLocationFromUS() Adds a RIGHT JOIN clause and with to the query using the GeoLocationFromUS relation
 * @method     ChildUserSearchPairQuery innerJoinWithGeoLocationFromUS() Adds a INNER JOIN clause and with to the query using the GeoLocationFromUS relation
 *
 * @method     ChildUserSearchPairQuery leftJoinUserSearchSiteRun($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchSiteRun relation
 * @method     ChildUserSearchPairQuery rightJoinUserSearchSiteRun($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchSiteRun relation
 * @method     ChildUserSearchPairQuery innerJoinUserSearchSiteRun($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchSiteRun relation
 *
 * @method     ChildUserSearchPairQuery joinWithUserSearchSiteRun($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchSiteRun relation
 *
 * @method     ChildUserSearchPairQuery leftJoinWithUserSearchSiteRun() Adds a LEFT JOIN clause and with to the query using the UserSearchSiteRun relation
 * @method     ChildUserSearchPairQuery rightJoinWithUserSearchSiteRun() Adds a RIGHT JOIN clause and with to the query using the UserSearchSiteRun relation
 * @method     ChildUserSearchPairQuery innerJoinWithUserSearchSiteRun() Adds a INNER JOIN clause and with to the query using the UserSearchSiteRun relation
 *
 * @method     \JobScooper\DataAccess\UserQuery|\JobScooper\DataAccess\GeoLocationQuery|\JobScooper\DataAccess\UserSearchSiteRunQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearchPair findOne(ConnectionInterface $con = null) Return the first ChildUserSearchPair matching the query
 * @method     ChildUserSearchPair findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearchPair matching the query, or a new ChildUserSearchPair object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearchPair findOneByUserSearchPairId(int $user_search_pair_id) Return the first ChildUserSearchPair filtered by the user_search_pair_id column
 * @method     ChildUserSearchPair findOneByUserId(int $user_id) Return the first ChildUserSearchPair filtered by the user_id column
 * @method     ChildUserSearchPair findOneByUserKeyword(string $user_keyword) Return the first ChildUserSearchPair filtered by the user_keyword column
 * @method     ChildUserSearchPair findOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearchPair filtered by the geolocation_id column
 * @method     ChildUserSearchPair findOneByIsActive(boolean $is_active) Return the first ChildUserSearchPair filtered by the is_active column *

 * @method     ChildUserSearchPair requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearchPair by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchPair requireOne(ConnectionInterface $con = null) Return the first ChildUserSearchPair matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchPair requireOneByUserSearchPairId(int $user_search_pair_id) Return the first ChildUserSearchPair filtered by the user_search_pair_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchPair requireOneByUserId(int $user_id) Return the first ChildUserSearchPair filtered by the user_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchPair requireOneByUserKeyword(string $user_keyword) Return the first ChildUserSearchPair filtered by the user_keyword column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchPair requireOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearchPair filtered by the geolocation_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchPair requireOneByIsActive(boolean $is_active) Return the first ChildUserSearchPair filtered by the is_active column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchPair[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearchPair objects based on current ModelCriteria
 * @method     ChildUserSearchPair[]|ObjectCollection findByUserSearchPairId(int $user_search_pair_id) Return ChildUserSearchPair objects filtered by the user_search_pair_id column
 * @method     ChildUserSearchPair[]|ObjectCollection findByUserId(int $user_id) Return ChildUserSearchPair objects filtered by the user_id column
 * @method     ChildUserSearchPair[]|ObjectCollection findByUserKeyword(string $user_keyword) Return ChildUserSearchPair objects filtered by the user_keyword column
 * @method     ChildUserSearchPair[]|ObjectCollection findByGeoLocationId(int $geolocation_id) Return ChildUserSearchPair objects filtered by the geolocation_id column
 * @method     ChildUserSearchPair[]|ObjectCollection findByIsActive(boolean $is_active) Return ChildUserSearchPair objects filtered by the is_active column
 * @method     ChildUserSearchPair[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchPairQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\DataAccess\Base\UserSearchPairQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\DataAccess\\UserSearchPair', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUserSearchPairQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildUserSearchPairQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildUserSearchPairQuery) {
            return $criteria;
        }
        $query = new ChildUserSearchPairQuery();
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
     * @return ChildUserSearchPair|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchPairTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = UserSearchPairTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
     * @return ChildUserSearchPair A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT user_search_pair_id, user_id, user_keyword, geolocation_id, is_active FROM user_search_pair WHERE user_search_pair_id = :p0';
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
            /** @var ChildUserSearchPair $obj */
            $obj = new ChildUserSearchPair();
            $obj->hydrate($row);
            UserSearchPairTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return ChildUserSearchPair|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the user_search_pair_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUserSearchPairId(1234); // WHERE user_search_pair_id = 1234
     * $query->filterByUserSearchPairId(array(12, 34)); // WHERE user_search_pair_id IN (12, 34)
     * $query->filterByUserSearchPairId(array('min' => 12)); // WHERE user_search_pair_id > 12
     * </code>
     *
     * @param     mixed $userSearchPairId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByUserSearchPairId($userSearchPairId = null, $comparison = null)
    {
        if (is_array($userSearchPairId)) {
            $useMinMax = false;
            if (isset($userSearchPairId['min'])) {
                $this->addUsingAlias(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, $userSearchPairId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userSearchPairId['max'])) {
                $this->addUsingAlias(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, $userSearchPairId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, $userSearchPairId, $comparison);
    }

    /**
     * Filter the query on the user_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUserId(1234); // WHERE user_id = 1234
     * $query->filterByUserId(array(12, 34)); // WHERE user_id IN (12, 34)
     * $query->filterByUserId(array('min' => 12)); // WHERE user_id > 12
     * </code>
     *
     * @see       filterByUserFromUS()
     *
     * @param     mixed $userId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByUserId($userId = null, $comparison = null)
    {
        if (is_array($userId)) {
            $useMinMax = false;
            if (isset($userId['min'])) {
                $this->addUsingAlias(UserSearchPairTableMap::COL_USER_ID, $userId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userId['max'])) {
                $this->addUsingAlias(UserSearchPairTableMap::COL_USER_ID, $userId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchPairTableMap::COL_USER_ID, $userId, $comparison);
    }

    /**
     * Filter the query on the user_keyword column
     *
     * Example usage:
     * <code>
     * $query->filterByUserKeyword('fooValue');   // WHERE user_keyword = 'fooValue'
     * $query->filterByUserKeyword('%fooValue%', Criteria::LIKE); // WHERE user_keyword LIKE '%fooValue%'
     * </code>
     *
     * @param     string $userKeyword The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByUserKeyword($userKeyword = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userKeyword)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchPairTableMap::COL_USER_KEYWORD, $userKeyword, $comparison);
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
     * @see       filterByGeoLocationFromUS()
     *
     * @param     mixed $geoLocationId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByGeoLocationId($geoLocationId = null, $comparison = null)
    {
        if (is_array($geoLocationId)) {
            $useMinMax = false;
            if (isset($geoLocationId['min'])) {
                $this->addUsingAlias(UserSearchPairTableMap::COL_GEOLOCATION_ID, $geoLocationId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($geoLocationId['max'])) {
                $this->addUsingAlias(UserSearchPairTableMap::COL_GEOLOCATION_ID, $geoLocationId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchPairTableMap::COL_GEOLOCATION_ID, $geoLocationId, $comparison);
    }

    /**
     * Filter the query on the is_active column
     *
     * Example usage:
     * <code>
     * $query->filterByIsActive(true); // WHERE is_active = true
     * $query->filterByIsActive('yes'); // WHERE is_active = true
     * </code>
     *
     * @param     boolean|string $isActive The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByIsActive($isActive = null, $comparison = null)
    {
        if (is_string($isActive)) {
            $isActive = in_array(strtolower($isActive), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(UserSearchPairTableMap::COL_IS_ACTIVE, $isActive, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\User object
     *
     * @param \JobScooper\DataAccess\User|ObjectCollection $user The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByUserFromUS($user, $comparison = null)
    {
        if ($user instanceof \JobScooper\DataAccess\User) {
            return $this
                ->addUsingAlias(UserSearchPairTableMap::COL_USER_ID, $user->getUserId(), $comparison);
        } elseif ($user instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchPairTableMap::COL_USER_ID, $user->toKeyValue('PrimaryKey', 'UserId'), $comparison);
        } else {
            throw new PropelException('filterByUserFromUS() only accepts arguments of type \JobScooper\DataAccess\User or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserFromUS relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function joinUserFromUS($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserFromUS');

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
            $this->addJoinObject($join, 'UserFromUS');
        }

        return $this;
    }

    /**
     * Use the UserFromUS relation User object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserQuery A secondary query class using the current class as primary query
     */
    public function useUserFromUSQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUserFromUS($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserFromUS', '\JobScooper\DataAccess\UserQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\GeoLocation object
     *
     * @param \JobScooper\DataAccess\GeoLocation|ObjectCollection $geoLocation The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByGeoLocationFromUS($geoLocation, $comparison = null)
    {
        if ($geoLocation instanceof \JobScooper\DataAccess\GeoLocation) {
            return $this
                ->addUsingAlias(UserSearchPairTableMap::COL_GEOLOCATION_ID, $geoLocation->getGeoLocationId(), $comparison);
        } elseif ($geoLocation instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchPairTableMap::COL_GEOLOCATION_ID, $geoLocation->toKeyValue('PrimaryKey', 'GeoLocationId'), $comparison);
        } else {
            throw new PropelException('filterByGeoLocationFromUS() only accepts arguments of type \JobScooper\DataAccess\GeoLocation or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the GeoLocationFromUS relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function joinGeoLocationFromUS($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('GeoLocationFromUS');

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
            $this->addJoinObject($join, 'GeoLocationFromUS');
        }

        return $this;
    }

    /**
     * Use the GeoLocationFromUS relation GeoLocation object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\GeoLocationQuery A secondary query class using the current class as primary query
     */
    public function useGeoLocationFromUSQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinGeoLocationFromUS($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'GeoLocationFromUS', '\JobScooper\DataAccess\GeoLocationQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\UserSearchSiteRun object
     *
     * @param \JobScooper\DataAccess\UserSearchSiteRun|ObjectCollection $userSearchSiteRun the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function filterByUserSearchSiteRun($userSearchSiteRun, $comparison = null)
    {
        if ($userSearchSiteRun instanceof \JobScooper\DataAccess\UserSearchSiteRun) {
            return $this
                ->addUsingAlias(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, $userSearchSiteRun->getUserSearchPairId(), $comparison);
        } elseif ($userSearchSiteRun instanceof ObjectCollection) {
            return $this
                ->useUserSearchSiteRunQuery()
                ->filterByPrimaryKeys($userSearchSiteRun->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByUserSearchSiteRun() only accepts arguments of type \JobScooper\DataAccess\UserSearchSiteRun or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearchSiteRun relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function joinUserSearchSiteRun($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserSearchSiteRun');

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
            $this->addJoinObject($join, 'UserSearchSiteRun');
        }

        return $this;
    }

    /**
     * Use the UserSearchSiteRun relation UserSearchSiteRun object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserSearchSiteRunQuery A secondary query class using the current class as primary query
     */
    public function useUserSearchSiteRunQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUserSearchSiteRun($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearchSiteRun', '\JobScooper\DataAccess\UserSearchSiteRunQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildUserSearchPair $userSearchPair Object to remove from the list of results
     *
     * @return $this|ChildUserSearchPairQuery The current query, for fluid interface
     */
    public function prune($userSearchPair = null)
    {
        if ($userSearchPair) {
            $this->addUsingAlias(UserSearchPairTableMap::COL_USER_SEARCH_PAIR_ID, $userSearchPair->getUserSearchPairId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the user_search_pair table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchPairTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UserSearchPairTableMap::clearInstancePool();
            UserSearchPairTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchPairTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UserSearchPairTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            UserSearchPairTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            UserSearchPairTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // UserSearchPairQuery
