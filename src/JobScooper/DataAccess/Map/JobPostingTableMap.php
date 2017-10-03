<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\JobPosting;
use JobScooper\DataAccess\JobPostingQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\InstancePoolTrait;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Map\TableMapTrait;


/**
 * This class defines the structure of the 'jobposting' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class JobPostingTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.JobPostingTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'jobposting';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\JobPosting';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.JobPosting';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 20;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 20;

    /**
     * the column name for the jobposting_id field
     */
    const COL_JOBPOSTING_ID = 'jobposting.jobposting_id';

    /**
     * the column name for the jobsite field
     */
    const COL_JOBSITE = 'jobposting.jobsite';

    /**
     * the column name for the jobsite_post_id field
     */
    const COL_JOBSITE_POST_ID = 'jobposting.jobsite_post_id';

    /**
     * the column name for the title field
     */
    const COL_TITLE = 'jobposting.title';

    /**
     * the column name for the title_tokens field
     */
    const COL_TITLE_TOKENS = 'jobposting.title_tokens';

    /**
     * the column name for the url field
     */
    const COL_URL = 'jobposting.url';

    /**
     * the column name for the company field
     */
    const COL_COMPANY = 'jobposting.company';

    /**
     * the column name for the location field
     */
    const COL_LOCATION = 'jobposting.location';

    /**
     * the column name for the job_location_id field
     */
    const COL_JOB_LOCATION_ID = 'jobposting.job_location_id';

    /**
     * the column name for the employment_type field
     */
    const COL_EMPLOYMENT_TYPE = 'jobposting.employment_type';

    /**
     * the column name for the department field
     */
    const COL_DEPARTMENT = 'jobposting.department';

    /**
     * the column name for the category field
     */
    const COL_CATEGORY = 'jobposting.category';

    /**
     * the column name for the last_updated_at field
     */
    const COL_LAST_UPDATED_AT = 'jobposting.last_updated_at';

    /**
     * the column name for the job_posted_date field
     */
    const COL_JOB_POSTED_DATE = 'jobposting.job_posted_date';

    /**
     * the column name for the first_seen_at field
     */
    const COL_FIRST_SEEN_AT = 'jobposting.first_seen_at';

    /**
     * the column name for the post_removed_at field
     */
    const COL_POST_REMOVED_AT = 'jobposting.post_removed_at';

    /**
     * the column name for the key_site_and_post_id field
     */
    const COL_KEY_SITE_AND_POST_ID = 'jobposting.key_site_and_post_id';

    /**
     * the column name for the key_company_and_title field
     */
    const COL_KEY_COMPANY_AND_TITLE = 'jobposting.key_company_and_title';

    /**
     * the column name for the title_linked field
     */
    const COL_TITLE_LINKED = 'jobposting.title_linked';

    /**
     * the column name for the duplicates_posting_id field
     */
    const COL_DUPLICATES_POSTING_ID = 'jobposting.duplicates_posting_id';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('JobPostingId', 'JobSite', 'JobSitePostID', 'Title', 'TitleTokens', 'Url', 'Company', 'Location', 'JobLocationId', 'EmploymentType', 'Department', 'Category', 'UpdatedAt', 'PostedAt', 'FirstSeenAt', 'RemovedAt', 'KeySiteAndPostID', 'KeyCompanyAndTitle', 'JobTitleLinked', 'DuplicatesJobPostingId', ),
        self::TYPE_CAMELNAME     => array('jobPostingId', 'jobSite', 'jobSitePostID', 'title', 'titleTokens', 'url', 'company', 'location', 'jobLocationId', 'employmentType', 'department', 'category', 'updatedAt', 'postedAt', 'firstSeenAt', 'removedAt', 'keySiteAndPostID', 'keyCompanyAndTitle', 'jobTitleLinked', 'duplicatesJobPostingId', ),
        self::TYPE_COLNAME       => array(JobPostingTableMap::COL_JOBPOSTING_ID, JobPostingTableMap::COL_JOBSITE, JobPostingTableMap::COL_JOBSITE_POST_ID, JobPostingTableMap::COL_TITLE, JobPostingTableMap::COL_TITLE_TOKENS, JobPostingTableMap::COL_URL, JobPostingTableMap::COL_COMPANY, JobPostingTableMap::COL_LOCATION, JobPostingTableMap::COL_JOB_LOCATION_ID, JobPostingTableMap::COL_EMPLOYMENT_TYPE, JobPostingTableMap::COL_DEPARTMENT, JobPostingTableMap::COL_CATEGORY, JobPostingTableMap::COL_LAST_UPDATED_AT, JobPostingTableMap::COL_JOB_POSTED_DATE, JobPostingTableMap::COL_FIRST_SEEN_AT, JobPostingTableMap::COL_POST_REMOVED_AT, JobPostingTableMap::COL_KEY_SITE_AND_POST_ID, JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE, JobPostingTableMap::COL_TITLE_LINKED, JobPostingTableMap::COL_DUPLICATES_POSTING_ID, ),
        self::TYPE_FIELDNAME     => array('jobposting_id', 'jobsite', 'jobsite_post_id', 'title', 'title_tokens', 'url', 'company', 'location', 'job_location_id', 'employment_type', 'department', 'category', 'last_updated_at', 'job_posted_date', 'first_seen_at', 'post_removed_at', 'key_site_and_post_id', 'key_company_and_title', 'title_linked', 'duplicates_posting_id', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('JobPostingId' => 0, 'JobSite' => 1, 'JobSitePostID' => 2, 'Title' => 3, 'TitleTokens' => 4, 'Url' => 5, 'Company' => 6, 'Location' => 7, 'JobLocationId' => 8, 'EmploymentType' => 9, 'Department' => 10, 'Category' => 11, 'UpdatedAt' => 12, 'PostedAt' => 13, 'FirstSeenAt' => 14, 'RemovedAt' => 15, 'KeySiteAndPostID' => 16, 'KeyCompanyAndTitle' => 17, 'JobTitleLinked' => 18, 'DuplicatesJobPostingId' => 19, ),
        self::TYPE_CAMELNAME     => array('jobPostingId' => 0, 'jobSite' => 1, 'jobSitePostID' => 2, 'title' => 3, 'titleTokens' => 4, 'url' => 5, 'company' => 6, 'location' => 7, 'jobLocationId' => 8, 'employmentType' => 9, 'department' => 10, 'category' => 11, 'updatedAt' => 12, 'postedAt' => 13, 'firstSeenAt' => 14, 'removedAt' => 15, 'keySiteAndPostID' => 16, 'keyCompanyAndTitle' => 17, 'jobTitleLinked' => 18, 'duplicatesJobPostingId' => 19, ),
        self::TYPE_COLNAME       => array(JobPostingTableMap::COL_JOBPOSTING_ID => 0, JobPostingTableMap::COL_JOBSITE => 1, JobPostingTableMap::COL_JOBSITE_POST_ID => 2, JobPostingTableMap::COL_TITLE => 3, JobPostingTableMap::COL_TITLE_TOKENS => 4, JobPostingTableMap::COL_URL => 5, JobPostingTableMap::COL_COMPANY => 6, JobPostingTableMap::COL_LOCATION => 7, JobPostingTableMap::COL_JOB_LOCATION_ID => 8, JobPostingTableMap::COL_EMPLOYMENT_TYPE => 9, JobPostingTableMap::COL_DEPARTMENT => 10, JobPostingTableMap::COL_CATEGORY => 11, JobPostingTableMap::COL_LAST_UPDATED_AT => 12, JobPostingTableMap::COL_JOB_POSTED_DATE => 13, JobPostingTableMap::COL_FIRST_SEEN_AT => 14, JobPostingTableMap::COL_POST_REMOVED_AT => 15, JobPostingTableMap::COL_KEY_SITE_AND_POST_ID => 16, JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE => 17, JobPostingTableMap::COL_TITLE_LINKED => 18, JobPostingTableMap::COL_DUPLICATES_POSTING_ID => 19, ),
        self::TYPE_FIELDNAME     => array('jobposting_id' => 0, 'jobsite' => 1, 'jobsite_post_id' => 2, 'title' => 3, 'title_tokens' => 4, 'url' => 5, 'company' => 6, 'location' => 7, 'job_location_id' => 8, 'employment_type' => 9, 'department' => 10, 'category' => 11, 'last_updated_at' => 12, 'job_posted_date' => 13, 'first_seen_at' => 14, 'post_removed_at' => 15, 'key_site_and_post_id' => 16, 'key_company_and_title' => 17, 'title_linked' => 18, 'duplicates_posting_id' => 19, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, )
    );

    /**
     * Initialize the table attributes and columns
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        $this->setName('jobposting');
        $this->setPhpName('JobPosting');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\JobPosting');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('jobposting_id', 'JobPostingId', 'INTEGER', true, null, null);
        $this->addColumn('jobsite', 'JobSite', 'VARCHAR', true, 100, null);
        $this->addColumn('jobsite_post_id', 'JobSitePostID', 'VARCHAR', true, 255, null);
        $this->addColumn('title', 'Title', 'VARCHAR', true, 255, null);
        $this->addColumn('title_tokens', 'TitleTokens', 'VARCHAR', false, 255, null);
        $this->addColumn('url', 'Url', 'VARCHAR', true, 1024, null);
        $this->addColumn('company', 'Company', 'VARCHAR', false, 100, null);
        $this->addColumn('location', 'Location', 'VARCHAR', false, 255, null);
        $this->addForeignKey('job_location_id', 'JobLocationId', 'INTEGER', 'job_location', 'location_id', false, null, null);
        $this->addColumn('employment_type', 'EmploymentType', 'VARCHAR', false, 100, null);
        $this->addColumn('department', 'Department', 'VARCHAR', false, 255, null);
        $this->addColumn('category', 'Category', 'VARCHAR', false, 100, null);
        $this->addColumn('last_updated_at', 'UpdatedAt', 'TIMESTAMP', true, null, null);
        $this->addColumn('job_posted_date', 'PostedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('first_seen_at', 'FirstSeenAt', 'TIMESTAMP', true, null, null);
        $this->addColumn('post_removed_at', 'RemovedAt', 'TIMESTAMP', false, null, null);
        $this->addColumn('key_site_and_post_id', 'KeySiteAndPostID', 'VARCHAR', true, 255, null);
        $this->getColumn('key_site_and_post_id')->setPrimaryString(true);
        $this->addColumn('key_company_and_title', 'KeyCompanyAndTitle', 'VARCHAR', true, 255, null);
        $this->addColumn('title_linked', 'JobTitleLinked', 'VARCHAR', true, 1024, null);
        $this->addForeignKey('duplicates_posting_id', 'DuplicatesJobPostingId', 'INTEGER', 'jobposting', 'jobposting_id', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('JobLocation', '\\JobScooper\\DataAccess\\JobLocation', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':job_location_id',
    1 => ':location_id',
  ),
), null, null, null, false);
        $this->addRelation('JobPostingRelatedByDuplicatesJobPostingId', '\\JobScooper\\DataAccess\\JobPosting', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':duplicates_posting_id',
    1 => ':jobposting_id',
  ),
), null, null, null, false);
        $this->addRelation('JobPostingRelatedByJobPostingId', '\\JobScooper\\DataAccess\\JobPosting', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':duplicates_posting_id',
    1 => ':jobposting_id',
  ),
), null, null, 'JobPostingsRelatedByJobPostingId', false);
        $this->addRelation('UserJobMatch', '\\JobScooper\\DataAccess\\UserJobMatch', RelationMap::ONE_TO_MANY, array (
  0 =>
  array (
    0 => ':jobposting_id',
    1 => ':jobposting_id',
  ),
), null, null, 'UserJobMatches', false);
    } // buildRelations()

    /**
     *
     * Gets the list of behaviors registered for this table
     *
     * @return array Associative array (name => parameters) of behaviors
     */
    public function getBehaviors()
    {
        return array(
            'timestampable' => array('create_column' => 'first_seen_at', 'update_column' => 'last_updated_at', 'disable_created_at' => 'false', 'disable_updated_at' => 'false', ),
        );
    } // getBehaviors()

    /**
     * Retrieves a string version of the primary key from the DB resultset row that can be used to uniquely identify a row in this table.
     *
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, a serialize()d version of the primary key will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return string The primary key hash of the row
     */
    public static function getPrimaryKeyHashFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        // If the PK cannot be derived from the row, return NULL.
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)];
    }

    /**
     * Retrieves the primary key from the DB resultset row
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, an array of the primary key columns will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return mixed The primary key of the row
     */
    public static function getPrimaryKeyFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        return (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 0 + $offset
                : self::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)
        ];
    }

    /**
     * The class that the tableMap will make instances of.
     *
     * If $withPrefix is true, the returned path
     * uses a dot-path notation which is translated into a path
     * relative to a location on the PHP include_path.
     * (e.g. path.to.MyClass -> 'path/to/MyClass.php')
     *
     * @param boolean $withPrefix Whether or not to return the path with the class name
     * @return string path.to.ClassName
     */
    public static function getOMClass($withPrefix = true)
    {
        return $withPrefix ? JobPostingTableMap::CLASS_DEFAULT : JobPostingTableMap::OM_CLASS;
    }

    /**
     * Populates an object of the default type or an object that inherit from the default.
     *
     * @param array  $row       row returned by DataFetcher->fetch().
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                 One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     * @return array           (JobPosting object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = JobPostingTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = JobPostingTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + JobPostingTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = JobPostingTableMap::OM_CLASS;
            /** @var JobPosting $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            JobPostingTableMap::addInstanceToPool($obj, $key);
        }

        return array($obj, $col);
    }

    /**
     * The returned array will contain objects of the default type or
     * objects that inherit from the default.
     *
     * @param DataFetcherInterface $dataFetcher
     * @return array
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function populateObjects(DataFetcherInterface $dataFetcher)
    {
        $results = array();

        // set the class once to avoid overhead in the loop
        $cls = static::getOMClass(false);
        // populate the object(s)
        while ($row = $dataFetcher->fetch()) {
            $key = JobPostingTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = JobPostingTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var JobPosting $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                JobPostingTableMap::addInstanceToPool($obj, $key);
            } // if key exists
        }

        return $results;
    }
    /**
     * Add all the columns needed to create a new object.
     *
     * Note: any columns that were marked with lazyLoad="true" in the
     * XML schema will not be added to the select list and only loaded
     * on demand.
     *
     * @param Criteria $criteria object containing the columns to add.
     * @param string   $alias    optional table alias
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function addSelectColumns(Criteria $criteria, $alias = null)
    {
        if (null === $alias) {
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOBPOSTING_ID);
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOBSITE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOBSITE_POST_ID);
            $criteria->addSelectColumn(JobPostingTableMap::COL_TITLE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_TITLE_TOKENS);
            $criteria->addSelectColumn(JobPostingTableMap::COL_URL);
            $criteria->addSelectColumn(JobPostingTableMap::COL_COMPANY);
            $criteria->addSelectColumn(JobPostingTableMap::COL_LOCATION);
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOB_LOCATION_ID);
            $criteria->addSelectColumn(JobPostingTableMap::COL_EMPLOYMENT_TYPE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_DEPARTMENT);
            $criteria->addSelectColumn(JobPostingTableMap::COL_CATEGORY);
            $criteria->addSelectColumn(JobPostingTableMap::COL_LAST_UPDATED_AT);
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOB_POSTED_DATE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_FIRST_SEEN_AT);
            $criteria->addSelectColumn(JobPostingTableMap::COL_POST_REMOVED_AT);
            $criteria->addSelectColumn(JobPostingTableMap::COL_KEY_SITE_AND_POST_ID);
            $criteria->addSelectColumn(JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_TITLE_LINKED);
            $criteria->addSelectColumn(JobPostingTableMap::COL_DUPLICATES_POSTING_ID);
        } else {
            $criteria->addSelectColumn($alias . '.jobposting_id');
            $criteria->addSelectColumn($alias . '.jobsite');
            $criteria->addSelectColumn($alias . '.jobsite_post_id');
            $criteria->addSelectColumn($alias . '.title');
            $criteria->addSelectColumn($alias . '.title_tokens');
            $criteria->addSelectColumn($alias . '.url');
            $criteria->addSelectColumn($alias . '.company');
            $criteria->addSelectColumn($alias . '.location');
            $criteria->addSelectColumn($alias . '.job_location_id');
            $criteria->addSelectColumn($alias . '.employment_type');
            $criteria->addSelectColumn($alias . '.department');
            $criteria->addSelectColumn($alias . '.category');
            $criteria->addSelectColumn($alias . '.last_updated_at');
            $criteria->addSelectColumn($alias . '.job_posted_date');
            $criteria->addSelectColumn($alias . '.first_seen_at');
            $criteria->addSelectColumn($alias . '.post_removed_at');
            $criteria->addSelectColumn($alias . '.key_site_and_post_id');
            $criteria->addSelectColumn($alias . '.key_company_and_title');
            $criteria->addSelectColumn($alias . '.title_linked');
            $criteria->addSelectColumn($alias . '.duplicates_posting_id');
        }
    }

    /**
     * Returns the TableMap related to this object.
     * This method is not needed for general use but a specific application could have a need.
     * @return TableMap
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function getTableMap()
    {
        return Propel::getServiceContainer()->getDatabaseMap(JobPostingTableMap::DATABASE_NAME)->getTable(JobPostingTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(JobPostingTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(JobPostingTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new JobPostingTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a JobPosting or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or JobPosting object or primary key or array of primary keys
     *              which is used to create the DELETE statement
     * @param  ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
     public static function doDelete($values, ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobPostingTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\JobPosting) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(JobPostingTableMap::DATABASE_NAME);
            $criteria->add(JobPostingTableMap::COL_JOBPOSTING_ID, (array) $values, Criteria::IN);
        }

        $query = JobPostingQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            JobPostingTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                JobPostingTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the jobposting table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return JobPostingQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a JobPosting or Criteria object.
     *
     * @param mixed               $criteria Criteria or JobPosting object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobPostingTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from JobPosting object
        }

        if ($criteria->containsKey(JobPostingTableMap::COL_JOBPOSTING_ID) && $criteria->keyContainsValue(JobPostingTableMap::COL_JOBPOSTING_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.JobPostingTableMap::COL_JOBPOSTING_ID.')');
        }


        // Set the correct dbName
        $query = JobPostingQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // JobPostingTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
JobPostingTableMap::buildTableMap();