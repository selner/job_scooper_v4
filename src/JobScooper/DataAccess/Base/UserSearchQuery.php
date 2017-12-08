<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\UserSearch as ChildUserSearch;
use JobScooper\DataAccess\UserSearchQuery as ChildUserSearchQuery;
use JobScooper\DataAccess\Map\UserSearchTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'user_search' table.
 *
 *
 *
 * @method     ChildUserSearchQuery orderByUserId($order = Criteria::ASC) Order by the user_id column
 * @method     ChildUserSearchQuery orderByUserKeywordSetKey($order = Criteria::ASC) Order by the user_keyword_set_key column
 * @method     ChildUserSearchQuery orderByGeoLocationId($order = Criteria::ASC) Order by the geolocation_id column
 * @method     ChildUserSearchQuery orderByUserSearchKey($order = Criteria::ASC) Order by the user_search_key column
 * @method     ChildUserSearchQuery orderByCreatedAt($order = Criteria::ASC) Order by the date_created column
 * @method     ChildUserSearchQuery orderByUpdatedAt($order = Criteria::ASC) Order by the date_updated column
 *
 * @method     ChildUserSearchQuery groupByUserId() Group by the user_id column
 * @method     ChildUserSearchQuery groupByUserKeywordSetKey() Group by the user_keyword_set_key column
 * @method     ChildUserSearchQuery groupByGeoLocationId() Group by the geolocation_id column
 * @method     ChildUserSearchQuery groupByUserSearchKey() Group by the user_search_key column
 * @method     ChildUserSearchQuery groupByCreatedAt() Group by the date_created column
 * @method     ChildUserSearchQuery groupByUpdatedAt() Group by the date_updated column
 *
 * @method     ChildUserSearchQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserSearchQuery leftJoinUserFromUS($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserFromUS relation
 * @method     ChildUserSearchQuery rightJoinUserFromUS($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserFromUS relation
 * @method     ChildUserSearchQuery innerJoinUserFromUS($relationAlias = null) Adds a INNER JOIN clause to the query using the UserFromUS relation
 *
 * @method     ChildUserSearchQuery joinWithUserFromUS($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserFromUS relation
 *
 * @method     ChildUserSearchQuery leftJoinWithUserFromUS() Adds a LEFT JOIN clause and with to the query using the UserFromUS relation
 * @method     ChildUserSearchQuery rightJoinWithUserFromUS() Adds a RIGHT JOIN clause and with to the query using the UserFromUS relation
 * @method     ChildUserSearchQuery innerJoinWithUserFromUS() Adds a INNER JOIN clause and with to the query using the UserFromUS relation
 *
 * @method     ChildUserSearchQuery leftJoinUserKeywordSetFromUS($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserKeywordSetFromUS relation
 * @method     ChildUserSearchQuery rightJoinUserKeywordSetFromUS($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserKeywordSetFromUS relation
 * @method     ChildUserSearchQuery innerJoinUserKeywordSetFromUS($relationAlias = null) Adds a INNER JOIN clause to the query using the UserKeywordSetFromUS relation
 *
 * @method     ChildUserSearchQuery joinWithUserKeywordSetFromUS($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserKeywordSetFromUS relation
 *
 * @method     ChildUserSearchQuery leftJoinWithUserKeywordSetFromUS() Adds a LEFT JOIN clause and with to the query using the UserKeywordSetFromUS relation
 * @method     ChildUserSearchQuery rightJoinWithUserKeywordSetFromUS() Adds a RIGHT JOIN clause and with to the query using the UserKeywordSetFromUS relation
 * @method     ChildUserSearchQuery innerJoinWithUserKeywordSetFromUS() Adds a INNER JOIN clause and with to the query using the UserKeywordSetFromUS relation
 *
 * @method     ChildUserSearchQuery leftJoinGeoLocationFromUS($relationAlias = null) Adds a LEFT JOIN clause to the query using the GeoLocationFromUS relation
 * @method     ChildUserSearchQuery rightJoinGeoLocationFromUS($relationAlias = null) Adds a RIGHT JOIN clause to the query using the GeoLocationFromUS relation
 * @method     ChildUserSearchQuery innerJoinGeoLocationFromUS($relationAlias = null) Adds a INNER JOIN clause to the query using the GeoLocationFromUS relation
 *
 * @method     ChildUserSearchQuery joinWithGeoLocationFromUS($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the GeoLocationFromUS relation
 *
 * @method     ChildUserSearchQuery leftJoinWithGeoLocationFromUS() Adds a LEFT JOIN clause and with to the query using the GeoLocationFromUS relation
 * @method     ChildUserSearchQuery rightJoinWithGeoLocationFromUS() Adds a RIGHT JOIN clause and with to the query using the GeoLocationFromUS relation
 * @method     ChildUserSearchQuery innerJoinWithGeoLocationFromUS() Adds a INNER JOIN clause and with to the query using the GeoLocationFromUS relation
 *
 * @method     ChildUserSearchQuery leftJoinUserSearchSiteRun($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchSiteRun relation
 * @method     ChildUserSearchQuery rightJoinUserSearchSiteRun($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchSiteRun relation
 * @method     ChildUserSearchQuery innerJoinUserSearchSiteRun($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchSiteRun relation
 *
 * @method     ChildUserSearchQuery joinWithUserSearchSiteRun($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchSiteRun relation
 *
 * @method     ChildUserSearchQuery leftJoinWithUserSearchSiteRun() Adds a LEFT JOIN clause and with to the query using the UserSearchSiteRun relation
 * @method     ChildUserSearchQuery rightJoinWithUserSearchSiteRun() Adds a RIGHT JOIN clause and with to the query using the UserSearchSiteRun relation
 * @method     ChildUserSearchQuery innerJoinWithUserSearchSiteRun() Adds a INNER JOIN clause and with to the query using the UserSearchSiteRun relation
 *
 * @method     \JobScooper\DataAccess\UserQuery|\JobScooper\DataAccess\UserKeywordSetQuery|\JobScooper\DataAccess\GeoLocationQuery|\JobScooper\DataAccess\UserSearchSiteRunQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearch findOne(ConnectionInterface $con = null) Return the first ChildUserSearch matching the query
 * @method     ChildUserSearch findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearch matching the query, or a new ChildUserSearch object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearch findOneByUserId(int $user_id) Return the first ChildUserSearch filtered by the user_id column
 * @method     ChildUserSearch findOneByUserKeywordSetKey(string $user_keyword_set_key) Return the first ChildUserSearch filtered by the user_keyword_set_key column
 * @method     ChildUserSearch findOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearch filtered by the geolocation_id column
 * @method     ChildUserSearch findOneByUserSearchKey(string $user_search_key) Return the first ChildUserSearch filtered by the user_search_key column
 * @method     ChildUserSearch findOneByCreatedAt(string $date_created) Return the first ChildUserSearch filtered by the date_created column
 * @method     ChildUserSearch findOneByUpdatedAt(string $date_updated) Return the first ChildUserSearch filtered by the date_updated column *

 * @method     ChildUserSearch requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearch by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOne(ConnectionInterface $con = null) Return the first ChildUserSearch matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearch requireOneByUserId(int $user_id) Return the first ChildUserSearch filtered by the user_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByUserKeywordSetKey(string $user_keyword_set_key) Return the first ChildUserSearch filtered by the user_keyword_set_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearch filtered by the geolocation_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByUserSearchKey(string $user_search_key) Return the first ChildUserSearch filtered by the user_search_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByCreatedAt(string $date_created) Return the first ChildUserSearch filtered by the date_created column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByUpdatedAt(string $date_updated) Return the first ChildUserSearch filtered by the date_updated column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearch[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearch objects based on current ModelCriteria
 * @method     ChildUserSearch[]|ObjectCollection findByUserId(int $user_id) Return ChildUserSearch objects filtered by the user_id column
 * @method     ChildUserSearch[]|ObjectCollection findByUserKeywordSetKey(string $user_keyword_set_key) Return ChildUserSearch objects filtered by the user_keyword_set_key column
 * @method     ChildUserSearch[]|ObjectCollection findByGeoLocationId(int $geolocation_id) Return ChildUserSearch objects filtered by the geolocation_id column
 * @method     ChildUserSearch[]|ObjectCollection findByUserSearchKey(string $user_search_key) Return ChildUserSearch objects filtered by the user_search_key column
 * @method     ChildUserSearch[]|ObjectCollection findByCreatedAt(string $date_created) Return ChildUserSearch objects filtered by the date_created column
 * @method     ChildUserSearch[]|ObjectCollection findByUpdatedAt(string $date_updated) Return ChildUserSearch objects filtered by the date_updated column
 * @method     ChildUserSearch[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\DataAccess\Base\UserSearchQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\DataAccess\\UserSearch', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUserSearchQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildUserSearchQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildUserSearchQuery) {
            return $criteria;
        }
        $query = new ChildUserSearchQuery();
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
     * $obj = $c->findPk(array(12, 34, 56), $con);
     * </code>
     *
     * @param array[$user_id, $user_keyword_set_key, $geolocation_id] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildUserSearch|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = UserSearchTableMap::getInstanceFromPool(serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1]), (null === $key[2] || is_scalar($key[2]) || is_callable([$key[2], '__toString']) ? (string) $key[2] : $key[2])]))))) {
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
     * @return ChildUserSearch A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT user_id, user_keyword_set_key, geolocation_id, user_search_key, date_created, date_updated FROM user_search WHERE user_id = :p0 AND user_keyword_set_key = :p1 AND geolocation_id = :p2';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key[0], PDO::PARAM_INT);
            $stmt->bindValue(':p1', $key[1], PDO::PARAM_STR);
            $stmt->bindValue(':p2', $key[2], PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildUserSearch $obj */
            $obj = new ChildUserSearch();
            $obj->hydrate($row);
            UserSearchTableMap::addInstanceToPool($obj, serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1]), (null === $key[2] || is_scalar($key[2]) || is_callable([$key[2], '__toString']) ? (string) $key[2] : $key[2])]));
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
     * @return ChildUserSearch|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(UserSearchTableMap::COL_USER_ID, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(UserSearchTableMap::COL_USER_KEYWORD_SET_KEY, $key[1], Criteria::EQUAL);
        $this->addUsingAlias(UserSearchTableMap::COL_GEOLOCATION_ID, $key[2], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(UserSearchTableMap::COL_USER_ID, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(UserSearchTableMap::COL_USER_KEYWORD_SET_KEY, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $cton2 = $this->getNewCriterion(UserSearchTableMap::COL_GEOLOCATION_ID, $key[2], Criteria::EQUAL);
            $cton0->addAnd($cton2);
            $this->addOr($cton0);
        }

        return $this;
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
     * @see       filterByUserKeywordSetFromUS()
     *
     * @param     mixed $userId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserId($userId = null, $comparison = null)
    {
        if (is_array($userId)) {
            $useMinMax = false;
            if (isset($userId['min'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_USER_ID, $userId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userId['max'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_USER_ID, $userId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_USER_ID, $userId, $comparison);
    }

    /**
     * Filter the query on the user_keyword_set_key column
     *
     * Example usage:
     * <code>
     * $query->filterByUserKeywordSetKey('fooValue');   // WHERE user_keyword_set_key = 'fooValue'
     * $query->filterByUserKeywordSetKey('%fooValue%', Criteria::LIKE); // WHERE user_keyword_set_key LIKE '%fooValue%'
     * </code>
     *
     * @param     string $userKeywordSetKey The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserKeywordSetKey($userKeywordSetKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userKeywordSetKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_USER_KEYWORD_SET_KEY, $userKeywordSetKey, $comparison);
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByGeoLocationId($geoLocationId = null, $comparison = null)
    {
        if (is_array($geoLocationId)) {
            $useMinMax = false;
            if (isset($geoLocationId['min'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_GEOLOCATION_ID, $geoLocationId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($geoLocationId['max'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_GEOLOCATION_ID, $geoLocationId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_GEOLOCATION_ID, $geoLocationId, $comparison);
    }

    /**
     * Filter the query on the user_search_key column
     *
     * Example usage:
     * <code>
     * $query->filterByUserSearchKey('fooValue');   // WHERE user_search_key = 'fooValue'
     * $query->filterByUserSearchKey('%fooValue%', Criteria::LIKE); // WHERE user_search_key LIKE '%fooValue%'
     * </code>
     *
     * @param     string $userSearchKey The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserSearchKey($userSearchKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userSearchKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_KEY, $userSearchKey, $comparison);
    }

    /**
     * Filter the query on the date_created column
     *
     * Example usage:
     * <code>
     * $query->filterByCreatedAt('2011-03-14'); // WHERE date_created = '2011-03-14'
     * $query->filterByCreatedAt('now'); // WHERE date_created = '2011-03-14'
     * $query->filterByCreatedAt(array('max' => 'yesterday')); // WHERE date_created > '2011-03-13'
     * </code>
     *
     * @param     mixed $createdAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByCreatedAt($createdAt = null, $comparison = null)
    {
        if (is_array($createdAt)) {
            $useMinMax = false;
            if (isset($createdAt['min'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_DATE_CREATED, $createdAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($createdAt['max'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_DATE_CREATED, $createdAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_DATE_CREATED, $createdAt, $comparison);
    }

    /**
     * Filter the query on the date_updated column
     *
     * Example usage:
     * <code>
     * $query->filterByUpdatedAt('2011-03-14'); // WHERE date_updated = '2011-03-14'
     * $query->filterByUpdatedAt('now'); // WHERE date_updated = '2011-03-14'
     * $query->filterByUpdatedAt(array('max' => 'yesterday')); // WHERE date_updated > '2011-03-13'
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUpdatedAt($updatedAt = null, $comparison = null)
    {
        if (is_array($updatedAt)) {
            $useMinMax = false;
            if (isset($updatedAt['min'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_DATE_UPDATED, $updatedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($updatedAt['max'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_DATE_UPDATED, $updatedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_DATE_UPDATED, $updatedAt, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\User object
     *
     * @param \JobScooper\DataAccess\User|ObjectCollection $user The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserFromUS($user, $comparison = null)
    {
        if ($user instanceof \JobScooper\DataAccess\User) {
            return $this
                ->addUsingAlias(UserSearchTableMap::COL_USER_ID, $user->getUserId(), $comparison);
        } elseif ($user instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchTableMap::COL_USER_ID, $user->toKeyValue('PrimaryKey', 'UserId'), $comparison);
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
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
     * Filter the query by a related \JobScooper\DataAccess\UserKeywordSet object
     *
     * @param \JobScooper\DataAccess\UserKeywordSet $userKeywordSet The related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserKeywordSetFromUS($userKeywordSet, $comparison = null)
    {
        if ($userKeywordSet instanceof \JobScooper\DataAccess\UserKeywordSet) {
            return $this
                ->addUsingAlias(UserSearchTableMap::COL_USER_KEYWORD_SET_KEY, $userKeywordSet->getUserKeywordSetKey(), $comparison)
                ->addUsingAlias(UserSearchTableMap::COL_USER_ID, $userKeywordSet->getUserId(), $comparison);
        } else {
            throw new PropelException('filterByUserKeywordSetFromUS() only accepts arguments of type \JobScooper\DataAccess\UserKeywordSet');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserKeywordSetFromUS relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function joinUserKeywordSetFromUS($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserKeywordSetFromUS');

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
            $this->addJoinObject($join, 'UserKeywordSetFromUS');
        }

        return $this;
    }

    /**
     * Use the UserKeywordSetFromUS relation UserKeywordSet object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserKeywordSetQuery A secondary query class using the current class as primary query
     */
    public function useUserKeywordSetFromUSQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUserKeywordSetFromUS($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserKeywordSetFromUS', '\JobScooper\DataAccess\UserKeywordSetQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\GeoLocation object
     *
     * @param \JobScooper\DataAccess\GeoLocation|ObjectCollection $geoLocation The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByGeoLocationFromUS($geoLocation, $comparison = null)
    {
        if ($geoLocation instanceof \JobScooper\DataAccess\GeoLocation) {
            return $this
                ->addUsingAlias(UserSearchTableMap::COL_GEOLOCATION_ID, $geoLocation->getGeoLocationId(), $comparison);
        } elseif ($geoLocation instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchTableMap::COL_GEOLOCATION_ID, $geoLocation->toKeyValue('PrimaryKey', 'GeoLocationId'), $comparison);
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
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
     * @return ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserSearchSiteRun($userSearchSiteRun, $comparison = null)
    {
        if ($userSearchSiteRun instanceof \JobScooper\DataAccess\UserSearchSiteRun) {
            return $this
                ->addUsingAlias(UserSearchTableMap::COL_USER_ID, $userSearchSiteRun->getUserId(), $comparison)
                ->addUsingAlias(UserSearchTableMap::COL_USER_KEYWORD_SET_KEY, $userSearchSiteRun->getUserKeywordSetKey(), $comparison)
                ->addUsingAlias(UserSearchTableMap::COL_GEOLOCATION_ID, $userSearchSiteRun->getGeoLocationId(), $comparison)
                ->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_KEY, $userSearchSiteRun->getUserSearchKey(), $comparison);
        } else {
            throw new PropelException('filterByUserSearchSiteRun() only accepts arguments of type \JobScooper\DataAccess\UserSearchSiteRun');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearchSiteRun relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
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
     * Filter the query by a related JobSiteRecord object
     * using the user_search_site_run table as cross reference
     *
     * @param JobSiteRecord $jobSiteRecord the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByJobSiteFromUSSR($jobSiteRecord, $comparison = Criteria::EQUAL)
    {
        return $this
            ->useUserSearchSiteRunQuery()
            ->filterByJobSiteFromUSSR($jobSiteRecord, $comparison)
            ->endUse();
    }

    /**
     * Filter the query by a related GeoLocation object
     * using the user_search_site_run table as cross reference
     *
     * @param GeoLocation $geoLocation the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByGeoLocationFromUSSR($geoLocation, $comparison = Criteria::EQUAL)
    {
        return $this
            ->useUserSearchSiteRunQuery()
            ->filterByGeoLocationFromUSSR($geoLocation, $comparison)
            ->endUse();
    }

    /**
     * Exclude object from result
     *
     * @param   ChildUserSearch $userSearch Object to remove from the list of results
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function prune($userSearch = null)
    {
        if ($userSearch) {
            $this->addCond('pruneCond0', $this->getAliasedColName(UserSearchTableMap::COL_USER_ID), $userSearch->getUserId(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(UserSearchTableMap::COL_USER_KEYWORD_SET_KEY), $userSearch->getUserKeywordSetKey(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond2', $this->getAliasedColName(UserSearchTableMap::COL_GEOLOCATION_ID), $userSearch->getGeoLocationId(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1', 'pruneCond2'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the user_search table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UserSearchTableMap::clearInstancePool();
            UserSearchTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UserSearchTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            UserSearchTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            UserSearchTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    // sluggable behavior

    /**
     * Filter the query on the slug column
     *
     * @param     string $slug The value to use as filter.
     *
     * @return    $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterBySlug($slug)
    {
        return $this->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_KEY, $slug, Criteria::EQUAL);
    }

    /**
     * Find one object based on its slug
     *
     * @param     string $slug The value to use as filter.
     * @param     ConnectionInterface $con The optional connection object
     *
     * @return    ChildUserSearch the result, formatted by the current formatter
     */
    public function findOneBySlug($slug, $con = null)
    {
        return $this->filterBySlug($slug)->findOne($con);
    }

    // timestampable behavior

    /**
     * Filter by the latest updated
     *
     * @param      int $nbDays Maximum age of the latest update in days
     *
     * @return     $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function recentlyUpdated($nbDays = 7)
    {
        return $this->addUsingAlias(UserSearchTableMap::COL_DATE_UPDATED, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by update date desc
     *
     * @return     $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function lastUpdatedFirst()
    {
        return $this->addDescendingOrderByColumn(UserSearchTableMap::COL_DATE_UPDATED);
    }

    /**
     * Order by update date asc
     *
     * @return     $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function firstUpdatedFirst()
    {
        return $this->addAscendingOrderByColumn(UserSearchTableMap::COL_DATE_UPDATED);
    }

    /**
     * Order by create date desc
     *
     * @return     $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function lastCreatedFirst()
    {
        return $this->addDescendingOrderByColumn(UserSearchTableMap::COL_DATE_CREATED);
    }

    /**
     * Filter by the latest created
     *
     * @param      int $nbDays Maximum age of in days
     *
     * @return     $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function recentlyCreated($nbDays = 7)
    {
        return $this->addUsingAlias(UserSearchTableMap::COL_DATE_CREATED, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by create date asc
     *
     * @return     $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function firstCreatedFirst()
    {
        return $this->addAscendingOrderByColumn(UserSearchTableMap::COL_DATE_CREATED);
    }

} // UserSearchQuery
