<?php

namespace JobScooper\Base;

use \Exception;
use \PDO;
use JobScooper\JobSitePlugin as ChildJobSitePlugin;
use JobScooper\JobSitePluginQuery as ChildJobSitePluginQuery;
use JobScooper\Map\JobSitePluginTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'jobsite_plugin' table.
 *
 *
 *
 * @method     ChildJobSitePluginQuery orderByJobSiteKey($order = Criteria::ASC) Order by the jobsite_key column
 * @method     ChildJobSitePluginQuery orderByPluginClassName($order = Criteria::ASC) Order by the plugin_class_name column
 * @method     ChildJobSitePluginQuery orderBySupportedCountryCodes($order = Criteria::ASC) Order by the supported_country_codes column
 * @method     ChildJobSitePluginQuery orderByResultsFilterType($order = Criteria::ASC) Order by the results_filter_type column
 *
 * @method     ChildJobSitePluginQuery groupByJobSiteKey() Group by the jobsite_key column
 * @method     ChildJobSitePluginQuery groupByPluginClassName() Group by the plugin_class_name column
 * @method     ChildJobSitePluginQuery groupBySupportedCountryCodes() Group by the supported_country_codes column
 * @method     ChildJobSitePluginQuery groupByResultsFilterType() Group by the results_filter_type column
 *
 * @method     ChildJobSitePluginQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildJobSitePluginQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildJobSitePluginQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildJobSitePluginQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildJobSitePluginQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildJobSitePluginQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildJobSitePlugin findOne(ConnectionInterface $con = null) Return the first ChildJobSitePlugin matching the query
 * @method     ChildJobSitePlugin findOneOrCreate(ConnectionInterface $con = null) Return the first ChildJobSitePlugin matching the query, or a new ChildJobSitePlugin object populated from the query conditions when no match is found
 *
 * @method     ChildJobSitePlugin findOneByJobSiteKey(string $jobsite_key) Return the first ChildJobSitePlugin filtered by the jobsite_key column
 * @method     ChildJobSitePlugin findOneByPluginClassName(string $plugin_class_name) Return the first ChildJobSitePlugin filtered by the plugin_class_name column
 * @method     ChildJobSitePlugin findOneBySupportedCountryCodes(array $supported_country_codes) Return the first ChildJobSitePlugin filtered by the supported_country_codes column
 * @method     ChildJobSitePlugin findOneByResultsFilterType(int $results_filter_type) Return the first ChildJobSitePlugin filtered by the results_filter_type column *

 * @method     ChildJobSitePlugin requirePk($key, ConnectionInterface $con = null) Return the ChildJobSitePlugin by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOne(ConnectionInterface $con = null) Return the first ChildJobSitePlugin matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSitePlugin requireOneByJobSiteKey(string $jobsite_key) Return the first ChildJobSitePlugin filtered by the jobsite_key column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByPluginClassName(string $plugin_class_name) Return the first ChildJobSitePlugin filtered by the plugin_class_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneBySupportedCountryCodes(array $supported_country_codes) Return the first ChildJobSitePlugin filtered by the supported_country_codes column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildJobSitePlugin requireOneByResultsFilterType(int $results_filter_type) Return the first ChildJobSitePlugin filtered by the results_filter_type column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildJobSitePlugin[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildJobSitePlugin objects based on current ModelCriteria
 * @method     ChildJobSitePlugin[]|ObjectCollection findByJobSiteKey(string $jobsite_key) Return ChildJobSitePlugin objects filtered by the jobsite_key column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByPluginClassName(string $plugin_class_name) Return ChildJobSitePlugin objects filtered by the plugin_class_name column
 * @method     ChildJobSitePlugin[]|ObjectCollection findBySupportedCountryCodes(array $supported_country_codes) Return ChildJobSitePlugin objects filtered by the supported_country_codes column
 * @method     ChildJobSitePlugin[]|ObjectCollection findByResultsFilterType(int $results_filter_type) Return ChildJobSitePlugin objects filtered by the results_filter_type column
 * @method     ChildJobSitePlugin[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class JobSitePluginQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \JobScooper\Base\JobSitePluginQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\JobScooper\\JobSitePlugin', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildJobSitePluginQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildJobSitePluginQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildJobSitePluginQuery) {
            return $criteria;
        }
        $query = new ChildJobSitePluginQuery();
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
     * @return ChildJobSitePlugin|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = JobSitePluginTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
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
     * @return ChildJobSitePlugin A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT jobsite_key, plugin_class_name, supported_country_codes, results_filter_type FROM jobsite_plugin WHERE jobsite_key = :p0';
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
            /** @var ChildJobSitePlugin $obj */
            $obj = new ChildJobSitePlugin();
            $obj->hydrate($row);
            JobSitePluginTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
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
     * @return ChildJobSitePlugin|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(JobSitePluginTableMap::COL_JOBSITE_KEY, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(JobSitePluginTableMap::COL_JOBSITE_KEY, $keys, Criteria::IN);
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
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByJobSiteKey($jobSiteKey = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($jobSiteKey)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_JOBSITE_KEY, $jobSiteKey, $comparison);
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
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByPluginClassName($pluginClassName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($pluginClassName)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME, $pluginClassName, $comparison);
    }

    /**
     * Filter the query on the supported_country_codes column
     *
     * @param     array $supportedCountryCodes The values to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterBySupportedCountryCodes($supportedCountryCodes = null, $comparison = null)
    {
        $key = $this->getAliasedColName(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES);
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

        return $this->addUsingAlias(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES, $supportedCountryCodes, $comparison);
    }

    /**
     * Filter the query on the supported_country_codes column
     * @param     mixed $supportedCountryCodes The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::CONTAINS_ALL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
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
            $key = $this->getAliasedColName(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES);
            if ($this->containsKey($key)) {
                $this->addAnd($key, $supportedCountryCodes, $comparison);
            } else {
                $this->addAnd($key, $supportedCountryCodes, $comparison);
            }
            $this->addOr($key, null, Criteria::ISNULL);

            return $this;
        }

        return $this->addUsingAlias(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES, $supportedCountryCodes, $comparison);
    }

    /**
     * Filter the query on the results_filter_type column
     *
     * @param     mixed $resultsFilterType The value to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function filterByResultsFilterType($resultsFilterType = null, $comparison = null)
    {
        $valueSet = JobSitePluginTableMap::getValueSet(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE);
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

        return $this->addUsingAlias(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE, $resultsFilterType, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildJobSitePlugin $jobSitePlugin Object to remove from the list of results
     *
     * @return $this|ChildJobSitePluginQuery The current query, for fluid interface
     */
    public function prune($jobSitePlugin = null)
    {
        if ($jobSitePlugin) {
            $this->addUsingAlias(JobSitePluginTableMap::COL_JOBSITE_KEY, $jobSitePlugin->getJobSiteKey(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the jobsite_plugin table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            JobSitePluginTableMap::clearInstancePool();
            JobSitePluginTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(JobSitePluginTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            JobSitePluginTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            JobSitePluginTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // JobSitePluginQuery
