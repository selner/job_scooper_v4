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
 * @method     ChildUserSearchQuery orderByUserSearchId($order = Criteria::ASC) Order by the user_search_id column
 * @method     ChildUserSearchQuery orderByUserId($order = Criteria::ASC) Order by the user_id column
 * @method     ChildUserSearchQuery orderByGeoLocationId($order = Criteria::ASC) Order by the geolocation_id column
 * @method     ChildUserSearchQuery orderByUserSearchKey($order = Criteria::ASC) Order by the user_search_key column
 * @method     ChildUserSearchQuery orderByKeywords($order = Criteria::ASC) Order by the keywords column
 * @method     ChildUserSearchQuery orderByKeywordTokens($order = Criteria::ASC) Order by the keyword_tokens column
 * @method     ChildUserSearchQuery orderBySearchKeyFromConfig($order = Criteria::ASC) Order by the search_key_from_config column
 * @method     ChildUserSearchQuery orderByCreatedAt($order = Criteria::ASC) Order by the date_created column
 * @method     ChildUserSearchQuery orderByUpdatedAt($order = Criteria::ASC) Order by the date_updated column
 * @method     ChildUserSearchQuery orderByLastCompletedAt($order = Criteria::ASC) Order by the date_last_completed column
 * @method     ChildUserSearchQuery orderByVersion($order = Criteria::ASC) Order by the version column
 *
 * @method     ChildUserSearchQuery groupByUserSearchId() Group by the user_search_id column
 * @method     ChildUserSearchQuery groupByUserId() Group by the user_id column
 * @method     ChildUserSearchQuery groupByGeoLocationId() Group by the geolocation_id column
 * @method     ChildUserSearchQuery groupByUserSearchKey() Group by the user_search_key column
 * @method     ChildUserSearchQuery groupByKeywords() Group by the keywords column
 * @method     ChildUserSearchQuery groupByKeywordTokens() Group by the keyword_tokens column
 * @method     ChildUserSearchQuery groupBySearchKeyFromConfig() Group by the search_key_from_config column
 * @method     ChildUserSearchQuery groupByCreatedAt() Group by the date_created column
 * @method     ChildUserSearchQuery groupByUpdatedAt() Group by the date_updated column
 * @method     ChildUserSearchQuery groupByLastCompletedAt() Group by the date_last_completed column
 * @method     ChildUserSearchQuery groupByVersion() Group by the version column
 *
 * @method     ChildUserSearchQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildUserSearchQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildUserSearchQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildUserSearchQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildUserSearchQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildUserSearchQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildUserSearchQuery leftJoinUser($relationAlias = null) Adds a LEFT JOIN clause to the query using the User relation
 * @method     ChildUserSearchQuery rightJoinUser($relationAlias = null) Adds a RIGHT JOIN clause to the query using the User relation
 * @method     ChildUserSearchQuery innerJoinUser($relationAlias = null) Adds a INNER JOIN clause to the query using the User relation
 *
 * @method     ChildUserSearchQuery joinWithUser($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the User relation
 *
 * @method     ChildUserSearchQuery leftJoinWithUser() Adds a LEFT JOIN clause and with to the query using the User relation
 * @method     ChildUserSearchQuery rightJoinWithUser() Adds a RIGHT JOIN clause and with to the query using the User relation
 * @method     ChildUserSearchQuery innerJoinWithUser() Adds a INNER JOIN clause and with to the query using the User relation
 *
 * @method     ChildUserSearchQuery leftJoinGeoLocation($relationAlias = null) Adds a LEFT JOIN clause to the query using the GeoLocation relation
 * @method     ChildUserSearchQuery rightJoinGeoLocation($relationAlias = null) Adds a RIGHT JOIN clause to the query using the GeoLocation relation
 * @method     ChildUserSearchQuery innerJoinGeoLocation($relationAlias = null) Adds a INNER JOIN clause to the query using the GeoLocation relation
 *
 * @method     ChildUserSearchQuery joinWithGeoLocation($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the GeoLocation relation
 *
 * @method     ChildUserSearchQuery leftJoinWithGeoLocation() Adds a LEFT JOIN clause and with to the query using the GeoLocation relation
 * @method     ChildUserSearchQuery rightJoinWithGeoLocation() Adds a RIGHT JOIN clause and with to the query using the GeoLocation relation
 * @method     ChildUserSearchQuery innerJoinWithGeoLocation() Adds a INNER JOIN clause and with to the query using the GeoLocation relation
 *
 * @method     ChildUserSearchQuery leftJoinUserSearchRun($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchRun relation
 * @method     ChildUserSearchQuery rightJoinUserSearchRun($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchRun relation
 * @method     ChildUserSearchQuery innerJoinUserSearchRun($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchRun relation
 *
 * @method     ChildUserSearchQuery joinWithUserSearchRun($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchRun relation
 *
 * @method     ChildUserSearchQuery leftJoinWithUserSearchRun() Adds a LEFT JOIN clause and with to the query using the UserSearchRun relation
 * @method     ChildUserSearchQuery rightJoinWithUserSearchRun() Adds a RIGHT JOIN clause and with to the query using the UserSearchRun relation
 * @method     ChildUserSearchQuery innerJoinWithUserSearchRun() Adds a INNER JOIN clause and with to the query using the UserSearchRun relation
 *
 * @method     ChildUserSearchQuery leftJoinUserSearchVersion($relationAlias = null) Adds a LEFT JOIN clause to the query using the UserSearchVersion relation
 * @method     ChildUserSearchQuery rightJoinUserSearchVersion($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UserSearchVersion relation
 * @method     ChildUserSearchQuery innerJoinUserSearchVersion($relationAlias = null) Adds a INNER JOIN clause to the query using the UserSearchVersion relation
 *
 * @method     ChildUserSearchQuery joinWithUserSearchVersion($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the UserSearchVersion relation
 *
 * @method     ChildUserSearchQuery leftJoinWithUserSearchVersion() Adds a LEFT JOIN clause and with to the query using the UserSearchVersion relation
 * @method     ChildUserSearchQuery rightJoinWithUserSearchVersion() Adds a RIGHT JOIN clause and with to the query using the UserSearchVersion relation
 * @method     ChildUserSearchQuery innerJoinWithUserSearchVersion() Adds a INNER JOIN clause and with to the query using the UserSearchVersion relation
 *
 * @method     \JobScooper\DataAccess\UserQuery|\JobScooper\DataAccess\GeoLocationQuery|\JobScooper\DataAccess\UserSearchRunQuery|\JobScooper\DataAccess\UserSearchVersionQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildUserSearch findOne(ConnectionInterface $con = null) Return the first ChildUserSearch matching the query
 * @method     ChildUserSearch findOneOrCreate(ConnectionInterface $con = null) Return the first ChildUserSearch matching the query, or a new ChildUserSearch object populated from the query conditions when no match is found
 *
 * @method     ChildUserSearch findOneByUserSearchId(int $user_search_id) Return the first ChildUserSearch filtered by the user_search_id column
 * @method     ChildUserSearch findOneByUserId(int $user_id) Return the first ChildUserSearch filtered by the user_id column
 * @method     ChildUserSearch findOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearch filtered by the geolocation_id column
 * @method     ChildUserSearch findOneByUserSearchKey(string $user_search_key) Return the first ChildUserSearch filtered by the user_search_key column
 * @method     ChildUserSearch findOneByKeywords(array $keywords) Return the first ChildUserSearch filtered by the keywords column
 * @method     ChildUserSearch findOneByKeywordTokens(array $keyword_tokens) Return the first ChildUserSearch filtered by the keyword_tokens column
 * @method     ChildUserSearch findOneBySearchKeyFromConfig(string $search_key_from_config) Return the first ChildUserSearch filtered by the search_key_from_config column
 * @method     ChildUserSearch findOneByCreatedAt(string $date_created) Return the first ChildUserSearch filtered by the date_created column
 * @method     ChildUserSearch findOneByUpdatedAt(string $date_updated) Return the first ChildUserSearch filtered by the date_updated column
 * @method     ChildUserSearch findOneByLastCompletedAt(string $date_last_completed) Return the first ChildUserSearch filtered by the date_last_completed column
 * @method     ChildUserSearch findOneByVersion(int $version) Return the first ChildUserSearch filtered by the version column *

 * @method     ChildUserSearch requirePk($key, ConnectionInterface $con = null) Return the ChildUserSearch by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOne(ConnectionInterface $con = null) Return the first ChildUserSearch matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearch requireOneByUserSearchId(int $user_search_id) Return the first ChildUserSearch filtered by the user_search_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByUserId(int $user_id) Return the first ChildUserSearch filtered by the user_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByGeoLocationId(int $geolocation_id) Return the first ChildUserSearch filtered by the geolocation_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByUserSearchKey(string $user_search_key) Return the first ChildUserSearch filtered by the user_search_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByKeywords(array $keywords) Return the first ChildUserSearch filtered by the keywords column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByKeywordTokens(array $keyword_tokens) Return the first ChildUserSearch filtered by the keyword_tokens column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneBySearchKeyFromConfig(string $search_key_from_config) Return the first ChildUserSearch filtered by the search_key_from_config column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByCreatedAt(string $date_created) Return the first ChildUserSearch filtered by the date_created column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByUpdatedAt(string $date_updated) Return the first ChildUserSearch filtered by the date_updated column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByLastCompletedAt(string $date_last_completed) Return the first ChildUserSearch filtered by the date_last_completed column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildUserSearch requireOneByVersion(int $version) Return the first ChildUserSearch filtered by the version column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildUserSearch[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildUserSearch objects based on current ModelCriteria
 * @method     ChildUserSearch[]|ObjectCollection findByUserSearchId(int $user_search_id) Return ChildUserSearch objects filtered by the user_search_id column
 * @method     ChildUserSearch[]|ObjectCollection findByUserId(int $user_id) Return ChildUserSearch objects filtered by the user_id column
 * @method     ChildUserSearch[]|ObjectCollection findByGeoLocationId(int $geolocation_id) Return ChildUserSearch objects filtered by the geolocation_id column
 * @method     ChildUserSearch[]|ObjectCollection findByUserSearchKey(string $user_search_key) Return ChildUserSearch objects filtered by the user_search_key column
 * @method     ChildUserSearch[]|ObjectCollection findByKeywords(array $keywords) Return ChildUserSearch objects filtered by the keywords column
 * @method     ChildUserSearch[]|ObjectCollection findByKeywordTokens(array $keyword_tokens) Return ChildUserSearch objects filtered by the keyword_tokens column
 * @method     ChildUserSearch[]|ObjectCollection findBySearchKeyFromConfig(string $search_key_from_config) Return ChildUserSearch objects filtered by the search_key_from_config column
 * @method     ChildUserSearch[]|ObjectCollection findByCreatedAt(string $date_created) Return ChildUserSearch objects filtered by the date_created column
 * @method     ChildUserSearch[]|ObjectCollection findByUpdatedAt(string $date_updated) Return ChildUserSearch objects filtered by the date_updated column
 * @method     ChildUserSearch[]|ObjectCollection findByLastCompletedAt(string $date_last_completed) Return ChildUserSearch objects filtered by the date_last_completed column
 * @method     ChildUserSearch[]|ObjectCollection findByVersion(int $version) Return ChildUserSearch objects filtered by the version column
 * @method     ChildUserSearch[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class UserSearchQuery extends ModelCriteria
{

    // versionable behavior

    /**
     * Whether the versioning is enabled
     */
    static $isVersioningEnabled = true;
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
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
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

        if ((null !== ($obj = UserSearchTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
        $sql = 'SELECT user_search_id, user_id, geolocation_id, user_search_key, keywords, keyword_tokens, search_key_from_config, date_created, date_updated, date_last_completed, version FROM user_search WHERE user_search_id = :p0';
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
            /** @var ChildUserSearch $obj */
            $obj = new ChildUserSearch();
            $obj->hydrate($row);
            UserSearchTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_ID, $key, Criteria::EQUAL);
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

        return $this->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_ID, $keys, Criteria::IN);
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
     * @param     mixed $userSearchId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserSearchId($userSearchId = null, $comparison = null)
    {
        if (is_array($userSearchId)) {
            $useMinMax = false;
            if (isset($userSearchId['min'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_ID, $userSearchId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userSearchId['max'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_ID, $userSearchId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_ID, $userSearchId, $comparison);
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
     * @see       filterByUser()
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
     * Filter the query on the geolocation_id column
     *
     * Example usage:
     * <code>
     * $query->filterByGeoLocationId(1234); // WHERE geolocation_id = 1234
     * $query->filterByGeoLocationId(array(12, 34)); // WHERE geolocation_id IN (12, 34)
     * $query->filterByGeoLocationId(array('min' => 12)); // WHERE geolocation_id > 12
     * </code>
     *
     * @see       filterByGeoLocation()
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
     * Filter the query on the keywords column
     *
     * @param     array $keywords The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByKeywords($keywords = null, $comparison = null)
    {
        $key = $this->getAliasedColName(UserSearchTableMap::COL_KEYWORDS);
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

        return $this->addUsingAlias(UserSearchTableMap::COL_KEYWORDS, $keywords, $comparison);
    }

    /**
     * Filter the query on the keywords column
     * @param     mixed $keywords The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
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
            $key = $this->getAliasedColName(UserSearchTableMap::COL_KEYWORDS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $keywords, $comparison);
            } else {
                $this->addAnd($key, $keywords, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_KEYWORDS, $keywords, $comparison);
    }

    /**
     * Filter the query on the keyword_tokens column
     *
     * @param     array $keywordTokens The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByKeywordTokens($keywordTokens = null, $comparison = null)
    {
        $key = $this->getAliasedColName(UserSearchTableMap::COL_KEYWORD_TOKENS);
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

        return $this->addUsingAlias(UserSearchTableMap::COL_KEYWORD_TOKENS, $keywordTokens, $comparison);
    }

    /**
     * Filter the query on the keyword_tokens column
     * @param     mixed $keywordTokens The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
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
            $key = $this->getAliasedColName(UserSearchTableMap::COL_KEYWORD_TOKENS);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $keywordTokens, $comparison);
            } else {
                $this->addAnd($key, $keywordTokens, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_KEYWORD_TOKENS, $keywordTokens, $comparison);
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterBySearchKeyFromConfig($searchKeyFromConfig = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($searchKeyFromConfig)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_SEARCH_KEY_FROM_CONFIG, $searchKeyFromConfig, $comparison);
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByLastCompletedAt($lastCompletedAt = null, $comparison = null)
    {
        if (is_array($lastCompletedAt)) {
            $useMinMax = false;
            if (isset($lastCompletedAt['min'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastCompletedAt['max'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_DATE_LAST_COMPLETED, $lastCompletedAt, $comparison);
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByVersion($version = null, $comparison = null)
    {
        if (is_array($version)) {
            $useMinMax = false;
            if (isset($version['min'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_VERSION, $version['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($version['max'])) {
                $this->addUsingAlias(UserSearchTableMap::COL_VERSION, $version['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(UserSearchTableMap::COL_VERSION, $version, $comparison);
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
    public function filterByUser($user, $comparison = null)
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
            throw new PropelException('filterByUser() only accepts arguments of type \JobScooper\DataAccess\User or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the User relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
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
     * @return \JobScooper\DataAccess\UserQuery A secondary query class using the current class as primary query
     */
    public function useUserQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUser($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'User', '\JobScooper\DataAccess\UserQuery');
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
    public function filterByGeoLocation($geoLocation, $comparison = null)
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
            throw new PropelException('filterByGeoLocation() only accepts arguments of type \JobScooper\DataAccess\GeoLocation or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the GeoLocation relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function joinGeoLocation($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('GeoLocation');

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
            $this->addJoinObject($join, 'GeoLocation');
        }

        return $this;
    }

    /**
     * Use the GeoLocation relation GeoLocation object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\GeoLocationQuery A secondary query class using the current class as primary query
     */
    public function useGeoLocationQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinGeoLocation($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'GeoLocation', '\JobScooper\DataAccess\GeoLocationQuery');
    }

    /**
     * Filter the query by a related \JobScooper\DataAccess\UserSearchRun object
     *
     * @param \JobScooper\DataAccess\UserSearchRun|ObjectCollection $userSearchRun the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserSearchRun($userSearchRun, $comparison = null)
    {
        if ($userSearchRun instanceof \JobScooper\DataAccess\UserSearchRun) {
            return $this
                ->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_ID, $userSearchRun->getUserSearchId(), $comparison);
        } elseif ($userSearchRun instanceof ObjectCollection) {
            return $this
                ->useUserSearchRunQuery()
                ->filterByPrimaryKeys($userSearchRun->getPrimaryKeys())
                ->endUse();
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
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
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
     * Filter the query by a related \JobScooper\DataAccess\UserSearchVersion object
     *
     * @param \JobScooper\DataAccess\UserSearchVersion|ObjectCollection $userSearchVersion the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildUserSearchQuery The current query, for fluid interface
     */
    public function filterByUserSearchVersion($userSearchVersion, $comparison = null)
    {
        if ($userSearchVersion instanceof \JobScooper\DataAccess\UserSearchVersion) {
            return $this
                ->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_ID, $userSearchVersion->getUserSearchId(), $comparison);
        } elseif ($userSearchVersion instanceof ObjectCollection) {
            return $this
                ->useUserSearchVersionQuery()
                ->filterByPrimaryKeys($userSearchVersion->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByUserSearchVersion() only accepts arguments of type \JobScooper\DataAccess\UserSearchVersion or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the UserSearchVersion relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildUserSearchQuery The current query, for fluid interface
     */
    public function joinUserSearchVersion($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UserSearchVersion');

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
            $this->addJoinObject($join, 'UserSearchVersion');
        }

        return $this;
    }

    /**
     * Use the UserSearchVersion relation UserSearchVersion object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \JobScooper\DataAccess\UserSearchVersionQuery A secondary query class using the current class as primary query
     */
    public function useUserSearchVersionQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinUserSearchVersion($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'UserSearchVersion', '\JobScooper\DataAccess\UserSearchVersionQuery');
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
            $this->addUsingAlias(UserSearchTableMap::COL_USER_SEARCH_ID, $userSearch->getUserSearchId(), Criteria::NOT_EQUAL);
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

} // UserSearchQuery
