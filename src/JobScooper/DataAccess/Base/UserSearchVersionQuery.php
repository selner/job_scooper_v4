<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\UserSearchVersion as ChildUserSearchVersion;
use JobScooper\DataAccess\UserSearchVersionQuery as ChildUserSearchVersionQuery;
use JobScooper\DataAccess\Map\UserSearchVersionTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'user_search_version' table.
 *
 *
 *
 * @method     ChildUserSearchVersionQuery orderByUserSearchId($order = Criteria::ASC) Order by the user_search_id column
 * @method     ChildUserSearchVersionQuery orderByUserId($order = Criteria::ASC) Order by the user_id column
 * @method     ChildUserSearchVersionQuery orderByGeoLocationId($order = Criteria::ASC) Order by the geolocation_id column
 * @method     ChildUserSearchVersionQuery orderByUserSearchKey($order = Criteria::ASC) Order by the user_search_key column
 * @method     ChildUserSearchVersionQuery orderByKeywords($order = Criteria::ASC) Order by the keywords column
 * @method     ChildUserSearchVersionQuery orderByKeywordTokens($order = Criteria::ASC) Order by the keyword_tokens column
 * @method     ChildUserSearchVersionQuery orderBySearchKeyFromConfig($order = Criteria::ASC) Order by the search_key_from_config column
 * @method     ChildUserSearchVersionQuery orderByCreatedAt($order = Criteria::ASC) Order by the date_created column
 * @method     ChildUserSearchVersionQuery orderByUpdatedAt($order = Criteria::ASC) Order by the date_updated column
 * @method     ChildUserSearchVersionQuery orderByLastCompletedAt($order = Criteria::ASC) Order by the date_last_completed column
 * @method     ChildUserSearchVersionQuery orderByVersion($order = Criteria::ASC) Order by the version column
 *
 * @method     ChildUserSearchVersionQuery groupByUserSearchId() Group by the user_search_id column
 * @method     ChildUserSearchVersionQuery groupByUserId() Group by the user_id column
 * @method     ChildUserSearchVersionQuery groupByGeoLocationId() Group by the geolocation_id column
 * @method     ChildUserSearchVersionQuery groupByUserSearchKey() Group by the user_search_key column
 * @method     ChildUserSearchVersionQuery groupByKeywords() Group by the keywords column
 * @method     ChildUserSearchVersionQuery groupByKeywordTokens() Group by the keyword_tokens column
 * @method     ChildUserSearchVersionQuery groupBySearchKeyFromConfig() Group by the search_key_from_config column
 * @method     ChildUserSearchVersionQuery groupByCreatedAt() Group by the date_created column
 * @method     ChildUserSearchVersionQuery groupByUpdatedAt() Group by the date_updated column
 * @method     ChildUserSearchVersionQuery groupByLastCompletedAt() Group by the date_last_completed column
 * @method     ChildUserSearchVersionQuery groupByVersion() Group by the version column
 *
 * @method     ChildUserSearchVersionQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchVersionQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchVersionQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchVersionQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchVersionQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchVersionQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserSearchVersionQuery leftJoinUserSearch($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearch relation
 * @method     ChildUserSearchVersionQuery rightJoinUserSearch($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearch relation
 * @method     ChildUserSearchVersionQuery innerJoinUserSearch($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearch relation
 *
 * @method     ChildUserSearchVersionQuery joinWithUserSearch($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearch relation
 *
 * @method     ChildUserSearchVersionQuery leftJoinWithUserSearch() Adds a LEFT JOIN clause and with to the query using the UserSearch relation
 * @method     ChildUserSearchVersionQuery rightJoinWithUserSearch() Adds a RIGHT JOIN clause and with to the query using the UserSearch relation
 * @method     ChildUserSearchVersionQuery innerJoinWithUserSearch() Adds a INNER JOIN clause and with to the query using the UserSearch relation
 *
 * @method     \JobScooper\DataAccess\UserSearchQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearchVersion findOne(ConnectionInterface $con = null) Return the first ChildUserSearchVersion matching the query
 * @method     ChildUserSearchVersion findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearchVersion matching the query, or a new ChildUserSearchVersion object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearchVersion findOneByUserSearchId(int $user_search_id) Return the first ChildUserSearchVersion filtered by the user_search_id column
 * @method     ChildUserSearchVersion findOneByUserId(int $user_id) Return the first ChildUserSearchVersion filtered by the user_id column
 * @method     ChildUserSearchVersion findOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearchVersion filtered by the geolocation_id column
 * @method     ChildUserSearchVersion findOneByUserSearchKey(string $user_search_key) Return the first ChildUserSearchVersion filtered by the user_search_key column
 * @method     ChildUserSearchVersion findOneByKeywords(array $keywords) Return the first ChildUserSearchVersion filtered by the keywords column
 * @method     ChildUserSearchVersion findOneByKeywordTokens(array $keyword_tokens) Return the first ChildUserSearchVersion filtered by the keyword_tokens column
 * @method     ChildUserSearchVersion findOneBySearchKeyFromConfig(string $search_key_from_config) Return the first ChildUserSearchVersion filtered by the search_key_from_config column
 * @method     ChildUserSearchVersion findOneByCreatedAt(string $date_created) Return the first ChildUserSearchVersion filtered by the date_created column
 * @method     ChildUserSearchVersion findOneByUpdatedAt(string $date_updated) Return the first ChildUserSearchVersion filtered by the date_updated column
 * @method     ChildUserSearchVersion findOneByLastCompletedAt(string $date_last_completed) Return the first ChildUserSearchVersion filtered by the date_last_completed column
 * @method     ChildUserSearchVersion findOneByVersion(int $version) Return the first ChildUserSearchVersion filtered by the version column *

 * @method     ChildUserSearchVersion requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearchVersion by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOne(ConnectionInterface $con = null) Return the first ChildUserSearchVersion matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchVersion requireOneByUserSearchId(int $user_search_id) Return the first ChildUserSearchVersion filtered by the user_search_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByUserId(int $user_id) Return the first ChildUserSearchVersion filtered by the user_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearchVersion filtered by the geolocation_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByUserSearchKey(string $user_search_key) Return the first ChildUserSearchVersion filtered by the user_search_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByKeywords(array $keywords) Return the first ChildUserSearchVersion filtered by the keywords column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByKeywordTokens(array $keyword_tokens) Return the first ChildUserSearchVersion filtered by the keyword_tokens column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneBySearchKeyFromConfig(string $search_key_from_config) Return the first ChildUserSearchVersion filtered by the search_key_from_config column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByCreatedAt(string $date_created) Return the first ChildUserSearchVersion filtered by the date_created column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByUpdatedAt(string $date_updated) Return the first ChildUserSearchVersion filtered by the date_updated column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByLastCompletedAt(string $date_last_completed) Return the first ChildUserSearchVersion filtered by the date_last_completed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearchVersion requireOneByVersion(int $version) Return the first ChildUserSearchVersion filtered by the version column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearchVersion[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearchVersion objects based on current ModelCriteria
 * @method     ChildUserSearchVersion[]|ObjectCollection findByUserSearchId(int $user_search_id) Return ChildUserSearchVersion objects filtered by the user_search_id column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByUserId(int $user_id) Return ChildUserSearchVersion objects filtered by the user_id column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByGeoLocationId(int $geolocation_id) Return ChildUserSearchVersion objects filtered by the geolocation_id column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByUserSearchKey(string $user_search_key) Return ChildUserSearchVersion objects filtered by the user_search_key column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByKeywords(array $keywords) Return ChildUserSearchVersion objects filtered by the keywords column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByKeywordTokens(array $keyword_tokens) Return ChildUserSearchVersion objects filtered by the keyword_tokens column
 * @method     ChildUserSearchVersion[]|ObjectCollection findBySearchKeyFromConfig(string $search_key_from_config) Return ChildUserSearchVersion objects filtered by the search_key_from_config column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByCreatedAt(string $date_created) Return ChildUserSearchVersion objects filtered by the date_created column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByUpdatedAt(string $date_updated) Return ChildUserSearchVersion objects filtered by the date_updated column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByLastCompletedAt(string $date_last_completed) Return ChildUserSearchVersion objects filtered by the date_last_completed column
 * @method     ChildUserSearchVersion[]|ObjectCollection findByVersion(int $version) Return ChildUserSearchVersion objects filtered by the version column
 * @method     ChildUserSearchVersion[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchVersionQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\DataAccess\Base\UserSearchVersionQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\DataAccess\\UserSearchVersion', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUserSearchVersionQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildUserSearchVersionQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildUserSearchVersionQuery) {
            return $criteria;
        }
        $query = new ChildUserSearchVersionQuery();
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
     * @param array[$user_search_id, $version] $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildUserSearchVersion|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchVersionTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = UserSearchVersionTableMap::getInstanceFromPool(serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1])]))))) {
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
     * @return ChildUserSearchVersion A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT user_search_id, user_id, geolocation_id, user_search_key, keywords, keyword_tokens, search_key_from_config, date_created, date_updated, date_last_completed, version FROM user_search_version WHERE user_search_id = :p0 AND version = :p1';
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
            /** @var ChildUserSearchVersion $obj */
            $obj = new ChildUserSearchVersion();
            $obj->hydrate($row);
            UserSearchVersionTableMap::addInstanceToPool($obj, serialize([(null === $key[0] || is_scalar($key[0]) || is_callable([$key[0], '__toString']) ? (string) $key[0] : $key[0]), (null === $key[1] || is_scalar($key[1]) || is_callable([$key[1], '__toString']) ? (string) $key[1] : $key[1])]));
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
     * @return ChildUserSearchVersion|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {
        $this->addUsingAlias(UserSearchVersionTableMap::COL_USER_SEARCH_ID, $key[0], Criteria::EQUAL);
        $this->addUsingAlias(UserSearchVersionTableMap::COL_VERSION, $key[1], Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {
        if (empty($keys)) {
            return $this->add(null, '1<>1', Criteria::CUSTOM);
        }
        foreach ($keys as $key) {
            $cton0 = $this->getNewCriterion(UserSearchVersionTableMap::COL_USER_SEARCH_ID, $key[0], Criteria::EQUAL);
            $cton1 = $this->getNewCriterion(UserSearchVersionTableMap::COL_VERSION, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $this->addOr($cton0);
        }

        return $this;
    }

    /**
     * Filter the query on the user_search_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUserSearchId(1234); // WHERE user_search_id = 1234
     * $query->filterByUserSearchId(array(12, 34)); // WHERE user_search_id IN (12, 34)
     * $query->filterByUserSearchId(array('min' => 12)); // WHERE user_search_id > 12
     * </code>
     *
     * @see       filterByUserSearch()
     *
     * @param     mixed $userSearchId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByUserSearchId($userSearchId = null, $comparison = null)
    {
        if (is_array($userSearchId)) {
            $useMinMax = false;
            if (isset($userSearchId['min'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_USER_SEARCH_ID, $userSearchId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userSearchId['max'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_USER_SEARCH_ID, $userSearchId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_USER_SEARCH_ID, $userSearchId, $comparison);
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
     * @param     mixed $userId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByUserId($userId = null, $comparison = null)
    {
        if (is_array($userId)) {
            $useMinMax = false;
            if (isset($userId['min'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_USER_ID, $userId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userId['max'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_USER_ID, $userId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_USER_ID, $userId, $comparison);
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
     * @param     mixed $geoLocationId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByGeoLocationId($geoLocationId = null, $comparison = null)
    {
        if (is_array($geoLocationId)) {
            $useMinMax = false;
            if (isset($geoLocationId['min'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_GEOLOCATION_ID, $geoLocationId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($geoLocationId['max'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_GEOLOCATION_ID, $geoLocationId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_GEOLOCATION_ID, $geoLocationId, $comparison);
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
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByUserSearchKey($userSearchKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($userSearchKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_USER_SEARCH_KEY, $userSearchKey, $comparison);
    }

    /**
     * Filter the query on the keywords column
     *
     * @param     array $keywords The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByKeywords($keywords = null, $comparison = null)
    {
        $key = $this->getAliasedColName(UserSearchVersionTableMap::COL_KEYWORDS);
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            foreach ($keywords as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_SOME) {
            foreach ($keywords as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addOr($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            foreach ($keywords as $value) {
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

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_KEYWORDS, $keywords, $comparison);
    }

    /**
     * Filter the query on the keywords column
     * @param     mixed $keywords The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByKeyword($keywords = null, $comparison = null)
    {
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            if (is_scalar($keywords)) {
                $keywords = '%| ' . $keywords . ' |%';
                $comparison = Criteria::LIKE;
            }
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            $keywords = '%| ' . $keywords . ' |%';
            $comparison = Criteria::NOT_LIKE;
            $key = $this->getAliasedColName(UserSearchVersionTableMap::COL_KEYWORDS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $keywords, $comparison);
            } else {
                $this->addAnd($key, $keywords, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_KEYWORDS, $keywords, $comparison);
    }

    /**
     * Filter the query on the keyword_tokens column
     *
     * @param     array $keywordTokens The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByKeywordTokens($keywordTokens = null, $comparison = null)
    {
        $key = $this->getAliasedColName(UserSearchVersionTableMap::COL_KEYWORD_TOKENS);
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            foreach ($keywordTokens as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addAnd($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_SOME) {
            foreach ($keywordTokens as $value) {
                $value = '%| ' . $value . ' |%';
                if ($this->containsKey($key)) {
                    $this->addOr($key, $value, Criteria::LIKE);
                } else {
                    $this->add($key, $value, Criteria::LIKE);
                }
            }

            return $this;
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            foreach ($keywordTokens as $value) {
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

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_KEYWORD_TOKENS, $keywordTokens, $comparison);
    }

    /**
     * Filter the query on the keyword_tokens column
     * @param     mixed $keywordTokens The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByKeywordToken($keywordTokens = null, $comparison = null)
    {
        if (null === $comparison || $comparison == Criteria::CONTAINS_ALL) {
            if (is_scalar($keywordTokens)) {
                $keywordTokens = '%| ' . $keywordTokens . ' |%';
                $comparison = Criteria::LIKE;
            }
        } elseif ($comparison == Criteria::CONTAINS_NONE) {
            $keywordTokens = '%| ' . $keywordTokens . ' |%';
            $comparison = Criteria::NOT_LIKE;
            $key = $this->getAliasedColName(UserSearchVersionTableMap::COL_KEYWORD_TOKENS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $keywordTokens, $comparison);
            } else {
                $this->addAnd($key, $keywordTokens, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_KEYWORD_TOKENS, $keywordTokens, $comparison);
    }

    /**
     * Filter the query on the search_key_from_config column
     *
     * Example usage:
     * <code>
     * $query->filterBySearchKeyFromConfig('fooValue');   // WHERE search_key_from_config = 'fooValue'
     * $query->filterBySearchKeyFromConfig('%fooValue%', Criteria::LIKE); // WHERE search_key_from_config LIKE '%fooValue%'
     * </code>
     *
     * @param     string $searchKeyFromConfig The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterBySearchKeyFromConfig($searchKeyFromConfig = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($searchKeyFromConfig)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_SEARCH_KEY_FROM_CONFIG, $searchKeyFromConfig, $comparison);
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
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByCreatedAt($createdAt = null, $comparison = null)
    {
        if (is_array($createdAt)) {
            $useMinMax = false;
            if (isset($createdAt['min'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_CREATED, $createdAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($createdAt['max'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_CREATED, $createdAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_CREATED, $createdAt, $comparison);
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
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByUpdatedAt($updatedAt = null, $comparison = null)
    {
        if (is_array($updatedAt)) {
            $useMinMax = false;
            if (isset($updatedAt['min'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_UPDATED, $updatedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($updatedAt['max'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_UPDATED, $updatedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_UPDATED, $updatedAt, $comparison);
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
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByLastCompletedAt($lastCompletedAt = null, $comparison = null)
    {
        if (is_array($lastCompletedAt)) {
            $useMinMax = false;
            if (isset($lastCompletedAt['min'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastCompletedAt['max'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt, $comparison);
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
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByVersion($version = null, $comparison = null)
    {
        if (is_array($version)) {
            $useMinMax = false;
            if (isset($version['min'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_VERSION, $version['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($version['max'])) {
                $this->addUsingAlias(UserSearchVersionTableMap::COL_VERSION, $version['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchVersionTableMap::COL_VERSION, $version, $comparison);
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\UserSearch object
     *
     * @param \JobScooper\DataAccess\UserSearch|ObjectCollection $userSearch The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function filterByUserSearch($userSearch, $comparison = null)
    {
        if ($userSearch instanceof \JobScooper\DataAccess\UserSearch) {
            return $this
                ->addUsingAlias(UserSearchVersionTableMap::COL_USER_SEARCH_ID, $userSearch->getUserSearchId(), $comparison);
        } elseif ($userSearch instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(UserSearchVersionTableMap::COL_USER_SEARCH_ID, $userSearch->toKeyValue('PrimaryKey', 'UserSearchId'), $comparison);
        } else {
            throw new PropelException('filterByUserSearch() only accepts arguments of type \JobScooper\DataAccess\UserSearch or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearch relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function joinUserSearch($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserSearch');

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
            $this->addJoinObject($join, 'UserSearch');
        }

        return $this;
    }

    /**
     * Use the UserSearch relation UserSearch object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserSearchQuery A secondary query class using the current class as primary query
     */
    public function useUserSearchQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUserSearch($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearch', '\JobScooper\DataAccess\UserSearchQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildUserSearchVersion $userSearchVersion Object to remove from the list of results
     *
     * @return $this|ChildUserSearchVersionQuery The current query, for fluid interface
     */
    public function prune($userSearchVersion = null)
    {
        if ($userSearchVersion) {
            $this->addCond('pruneCond0', $this->getAliasedColName(UserSearchVersionTableMap::COL_USER_SEARCH_ID), $userSearchVersion->getUserSearchId(), Criteria::NOT_EQUAL);
            $this->addCond('pruneCond1', $this->getAliasedColName(UserSearchVersionTableMap::COL_VERSION), $userSearchVersion->getVersion(), Criteria::NOT_EQUAL);
            $this->combine(array('pruneCond0', 'pruneCond1'), Criteria::LOGICAL_OR);
        }

        return $this;
    }

    /**
     * Deletes all rows from the user_search_version table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchVersionTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UserSearchVersionTableMap::clearInstancePool();
            UserSearchVersionTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchVersionTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UserSearchVersionTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            UserSearchVersionTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            UserSearchVersionTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // UserSearchVersionQuery
