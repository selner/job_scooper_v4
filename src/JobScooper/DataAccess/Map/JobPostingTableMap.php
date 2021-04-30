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
     * the column name for the jobsite_key field
     */
    const COL_JOBSITE_KEY = 'jobposting.jobsite_key';

    /**
     * the column name for the jobsite_post_id field
     */
    const COL_JOBSITE_POST_ID = 'jobposting.jobsite_post_id';

    /**
     * the column name for the title field
     */
    const COL_TITLE = 'jobposting.title';

    /**
     * the column name for the url field
     */
    const COL_URL = 'jobposting.url';

    /**
     * the column name for the employment_type field
     */
    const COL_EMPLOYMENT_TYPE = 'jobposting.employment_type';

    /**
     * the column name for the pay_range field
     */
    const COL_PAY_RANGE = 'jobposting.pay_range';

    /**
     * the column name for the location field
     */
    const COL_LOCATION = 'jobposting.location';

    /**
     * the column name for the company field
     */
    const COL_COMPANY = 'jobposting.company';

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
     * the column name for the location_display_value field
     */
    const COL_LOCATION_DISPLAY_VALUE = 'jobposting.location_display_value';

    /**
     * the column name for the geolocation_id field
     */
    const COL_GEOLOCATION_ID = 'jobposting.geolocation_id';

    /**
     * the column name for the duplicates_posting_id field
     */
    const COL_DUPLICATES_POSTING_ID = 'jobposting.duplicates_posting_id';

    /**
     * the column name for the title_tokens field
     */
    const COL_TITLE_TOKENS = 'jobposting.title_tokens';

    /**
     * the column name for the job_reference_key field
     */
    const COL_JOB_REFERENCE_KEY = 'jobposting.job_reference_key';

    /**
     * the column name for the key_company_and_title field
     */
    const COL_KEY_COMPANY_AND_TITLE = 'jobposting.key_company_and_title';

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
        self::TYPE_PHPNAME       => array('JobPostingId', 'JobSiteKey', 'JobSitePostId', 'Title', 'Url', 'EmploymentType', 'PayRange', 'Location', 'Company', 'Department', 'Category', 'UpdatedAt', 'PostedAt', 'FirstSeenAt', 'LocationDisplayValue', 'GeoLocationId', 'DuplicatesJobPostingId', 'TitleTokens', 'JobReferenceKey', 'KeyCompanyAndTitle', ),
        self::TYPE_CAMELNAME     => array('jobPostingId', 'jobSiteKey', 'jobSitePostId', 'title', 'url', 'employmentType', 'payRange', 'location', 'company', 'department', 'category', 'updatedAt', 'postedAt', 'firstSeenAt', 'locationDisplayValue', 'geoLocationId', 'duplicatesJobPostingId', 'titleTokens', 'jobReferenceKey', 'keyCompanyAndTitle', ),
        self::TYPE_COLNAME       => array(JobPostingTableMap::COL_JOBPOSTING_ID, JobPostingTableMap::COL_JOBSITE_KEY, JobPostingTableMap::COL_JOBSITE_POST_ID, JobPostingTableMap::COL_TITLE, JobPostingTableMap::COL_URL, JobPostingTableMap::COL_EMPLOYMENT_TYPE, JobPostingTableMap::COL_PAY_RANGE, JobPostingTableMap::COL_LOCATION, JobPostingTableMap::COL_COMPANY, JobPostingTableMap::COL_DEPARTMENT, JobPostingTableMap::COL_CATEGORY, JobPostingTableMap::COL_LAST_UPDATED_AT, JobPostingTableMap::COL_JOB_POSTED_DATE, JobPostingTableMap::COL_FIRST_SEEN_AT, JobPostingTableMap::COL_LOCATION_DISPLAY_VALUE, JobPostingTableMap::COL_GEOLOCATION_ID, JobPostingTableMap::COL_DUPLICATES_POSTING_ID, JobPostingTableMap::COL_TITLE_TOKENS, JobPostingTableMap::COL_JOB_REFERENCE_KEY, JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE, ),
        self::TYPE_FIELDNAME     => array('jobposting_id', 'jobsite_key', 'jobsite_post_id', 'title', 'url', 'employment_type', 'pay_range', 'location', 'company', 'department', 'category', 'last_updated_at', 'job_posted_date', 'first_seen_at', 'location_display_value', 'geolocation_id', 'duplicates_posting_id', 'title_tokens', 'job_reference_key', 'key_company_and_title', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('JobPostingId' => 0, 'JobSiteKey' => 1, 'JobSitePostId' => 2, 'Title' => 3, 'Url' => 4, 'EmploymentType' => 5, 'PayRange' => 6, 'Location' => 7, 'Company' => 8, 'Department' => 9, 'Category' => 10, 'UpdatedAt' => 11, 'PostedAt' => 12, 'FirstSeenAt' => 13, 'LocationDisplayValue' => 14, 'GeoLocationId' => 15, 'DuplicatesJobPostingId' => 16, 'TitleTokens' => 17, 'JobReferenceKey' => 18, 'KeyCompanyAndTitle' => 19, ),
        self::TYPE_CAMELNAME     => array('jobPostingId' => 0, 'jobSiteKey' => 1, 'jobSitePostId' => 2, 'title' => 3, 'url' => 4, 'employmentType' => 5, 'payRange' => 6, 'location' => 7, 'company' => 8, 'department' => 9, 'category' => 10, 'updatedAt' => 11, 'postedAt' => 12, 'firstSeenAt' => 13, 'locationDisplayValue' => 14, 'geoLocationId' => 15, 'duplicatesJobPostingId' => 16, 'titleTokens' => 17, 'jobReferenceKey' => 18, 'keyCompanyAndTitle' => 19, ),
        self::TYPE_COLNAME       => array(JobPostingTableMap::COL_JOBPOSTING_ID => 0, JobPostingTableMap::COL_JOBSITE_KEY => 1, JobPostingTableMap::COL_JOBSITE_POST_ID => 2, JobPostingTableMap::COL_TITLE => 3, JobPostingTableMap::COL_URL => 4, JobPostingTableMap::COL_EMPLOYMENT_TYPE => 5, JobPostingTableMap::COL_PAY_RANGE => 6, JobPostingTableMap::COL_LOCATION => 7, JobPostingTableMap::COL_COMPANY => 8, JobPostingTableMap::COL_DEPARTMENT => 9, JobPostingTableMap::COL_CATEGORY => 10, JobPostingTableMap::COL_LAST_UPDATED_AT => 11, JobPostingTableMap::COL_JOB_POSTED_DATE => 12, JobPostingTableMap::COL_FIRST_SEEN_AT => 13, JobPostingTableMap::COL_LOCATION_DISPLAY_VALUE => 14, JobPostingTableMap::COL_GEOLOCATION_ID => 15, JobPostingTableMap::COL_DUPLICATES_POSTING_ID => 16, JobPostingTableMap::COL_TITLE_TOKENS => 17, JobPostingTableMap::COL_JOB_REFERENCE_KEY => 18, JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE => 19, ),
        self::TYPE_FIELDNAME     => array('jobposting_id' => 0, 'jobsite_key' => 1, 'jobsite_post_id' => 2, 'title' => 3, 'url' => 4, 'employment_type' => 5, 'pay_range' => 6, 'location' => 7, 'company' => 8, 'department' => 9, 'category' => 10, 'last_updated_at' => 11, 'job_posted_date' => 12, 'first_seen_at' => 13, 'location_display_value' => 14, 'geolocation_id' => 15, 'duplicates_posting_id' => 16, 'title_tokens' => 17, 'job_reference_key' => 18, 'key_company_and_title' => 19, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, )
    );

    /**
     * Holds a list of column names and their normalized version.
     *
     * @var string[]
     */
    protected $normalizedColumnNameMap = [

        'JobPostingId' => 'JOBPOSTING_ID',
        'JobPosting.JobPostingId' => 'JOBPOSTING_ID',
        'jobPostingId' => 'JOBPOSTING_ID',
        'jobPosting.jobPostingId' => 'JOBPOSTING_ID',
        'JobPostingTableMap::COL_JOBPOSTING_ID' => 'JOBPOSTING_ID',
        'COL_JOBPOSTING_ID' => 'JOBPOSTING_ID',
        'jobposting_id' => 'JOBPOSTING_ID',
        'jobposting.jobposting_id' => 'JOBPOSTING_ID',
        'JobSiteKey' => 'JOBSITE_KEY',
        'JobPosting.JobSiteKey' => 'JOBSITE_KEY',
        'jobSiteKey' => 'JOBSITE_KEY',
        'jobPosting.jobSiteKey' => 'JOBSITE_KEY',
        'JobPostingTableMap::COL_JOBSITE_KEY' => 'JOBSITE_KEY',
        'COL_JOBSITE_KEY' => 'JOBSITE_KEY',
        'jobsite_key' => 'JOBSITE_KEY',
        'jobposting.jobsite_key' => 'JOBSITE_KEY',
        'JobSitePostId' => 'JOBSITE_POST_ID',
        'JobPosting.JobSitePostId' => 'JOBSITE_POST_ID',
        'jobSitePostId' => 'JOBSITE_POST_ID',
        'jobPosting.jobSitePostId' => 'JOBSITE_POST_ID',
        'JobPostingTableMap::COL_JOBSITE_POST_ID' => 'JOBSITE_POST_ID',
        'COL_JOBSITE_POST_ID' => 'JOBSITE_POST_ID',
        'jobsite_post_id' => 'JOBSITE_POST_ID',
        'jobposting.jobsite_post_id' => 'JOBSITE_POST_ID',
        'Title' => 'TITLE',
        'JobPosting.Title' => 'TITLE',
        'title' => 'TITLE',
        'jobPosting.title' => 'TITLE',
        'JobPostingTableMap::COL_TITLE' => 'TITLE',
        'COL_TITLE' => 'TITLE',
        'title' => 'TITLE',
        'jobposting.title' => 'TITLE',
        'Url' => 'URL',
        'JobPosting.Url' => 'URL',
        'url' => 'URL',
        'jobPosting.url' => 'URL',
        'JobPostingTableMap::COL_URL' => 'URL',
        'COL_URL' => 'URL',
        'url' => 'URL',
        'jobposting.url' => 'URL',
        'EmploymentType' => 'EMPLOYMENT_TYPE',
        'JobPosting.EmploymentType' => 'EMPLOYMENT_TYPE',
        'employmentType' => 'EMPLOYMENT_TYPE',
        'jobPosting.employmentType' => 'EMPLOYMENT_TYPE',
        'JobPostingTableMap::COL_EMPLOYMENT_TYPE' => 'EMPLOYMENT_TYPE',
        'COL_EMPLOYMENT_TYPE' => 'EMPLOYMENT_TYPE',
        'employment_type' => 'EMPLOYMENT_TYPE',
        'jobposting.employment_type' => 'EMPLOYMENT_TYPE',
        'PayRange' => 'PAY_RANGE',
        'JobPosting.PayRange' => 'PAY_RANGE',
        'payRange' => 'PAY_RANGE',
        'jobPosting.payRange' => 'PAY_RANGE',
        'JobPostingTableMap::COL_PAY_RANGE' => 'PAY_RANGE',
        'COL_PAY_RANGE' => 'PAY_RANGE',
        'pay_range' => 'PAY_RANGE',
        'jobposting.pay_range' => 'PAY_RANGE',
        'Location' => 'LOCATION',
        'JobPosting.Location' => 'LOCATION',
        'location' => 'LOCATION',
        'jobPosting.location' => 'LOCATION',
        'JobPostingTableMap::COL_LOCATION' => 'LOCATION',
        'COL_LOCATION' => 'LOCATION',
        'location' => 'LOCATION',
        'jobposting.location' => 'LOCATION',
        'Company' => 'COMPANY',
        'JobPosting.Company' => 'COMPANY',
        'company' => 'COMPANY',
        'jobPosting.company' => 'COMPANY',
        'JobPostingTableMap::COL_COMPANY' => 'COMPANY',
        'COL_COMPANY' => 'COMPANY',
        'company' => 'COMPANY',
        'jobposting.company' => 'COMPANY',
        'Department' => 'DEPARTMENT',
        'JobPosting.Department' => 'DEPARTMENT',
        'department' => 'DEPARTMENT',
        'jobPosting.department' => 'DEPARTMENT',
        'JobPostingTableMap::COL_DEPARTMENT' => 'DEPARTMENT',
        'COL_DEPARTMENT' => 'DEPARTMENT',
        'department' => 'DEPARTMENT',
        'jobposting.department' => 'DEPARTMENT',
        'Category' => 'CATEGORY',
        'JobPosting.Category' => 'CATEGORY',
        'category' => 'CATEGORY',
        'jobPosting.category' => 'CATEGORY',
        'JobPostingTableMap::COL_CATEGORY' => 'CATEGORY',
        'COL_CATEGORY' => 'CATEGORY',
        'category' => 'CATEGORY',
        'jobposting.category' => 'CATEGORY',
        'UpdatedAt' => 'LAST_UPDATED_AT',
        'JobPosting.UpdatedAt' => 'LAST_UPDATED_AT',
        'updatedAt' => 'LAST_UPDATED_AT',
        'jobPosting.updatedAt' => 'LAST_UPDATED_AT',
        'JobPostingTableMap::COL_LAST_UPDATED_AT' => 'LAST_UPDATED_AT',
        'COL_LAST_UPDATED_AT' => 'LAST_UPDATED_AT',
        'last_updated_at' => 'LAST_UPDATED_AT',
        'jobposting.last_updated_at' => 'LAST_UPDATED_AT',
        'PostedAt' => 'JOB_POSTED_DATE',
        'JobPosting.PostedAt' => 'JOB_POSTED_DATE',
        'postedAt' => 'JOB_POSTED_DATE',
        'jobPosting.postedAt' => 'JOB_POSTED_DATE',
        'JobPostingTableMap::COL_JOB_POSTED_DATE' => 'JOB_POSTED_DATE',
        'COL_JOB_POSTED_DATE' => 'JOB_POSTED_DATE',
        'job_posted_date' => 'JOB_POSTED_DATE',
        'jobposting.job_posted_date' => 'JOB_POSTED_DATE',
        'FirstSeenAt' => 'FIRST_SEEN_AT',
        'JobPosting.FirstSeenAt' => 'FIRST_SEEN_AT',
        'firstSeenAt' => 'FIRST_SEEN_AT',
        'jobPosting.firstSeenAt' => 'FIRST_SEEN_AT',
        'JobPostingTableMap::COL_FIRST_SEEN_AT' => 'FIRST_SEEN_AT',
        'COL_FIRST_SEEN_AT' => 'FIRST_SEEN_AT',
        'first_seen_at' => 'FIRST_SEEN_AT',
        'jobposting.first_seen_at' => 'FIRST_SEEN_AT',
        'LocationDisplayValue' => 'LOCATION_DISPLAY_VALUE',
        'JobPosting.LocationDisplayValue' => 'LOCATION_DISPLAY_VALUE',
        'locationDisplayValue' => 'LOCATION_DISPLAY_VALUE',
        'jobPosting.locationDisplayValue' => 'LOCATION_DISPLAY_VALUE',
        'JobPostingTableMap::COL_LOCATION_DISPLAY_VALUE' => 'LOCATION_DISPLAY_VALUE',
        'COL_LOCATION_DISPLAY_VALUE' => 'LOCATION_DISPLAY_VALUE',
        'location_display_value' => 'LOCATION_DISPLAY_VALUE',
        'jobposting.location_display_value' => 'LOCATION_DISPLAY_VALUE',
        'GeoLocationId' => 'GEOLOCATION_ID',
        'JobPosting.GeoLocationId' => 'GEOLOCATION_ID',
        'geoLocationId' => 'GEOLOCATION_ID',
        'jobPosting.geoLocationId' => 'GEOLOCATION_ID',
        'JobPostingTableMap::COL_GEOLOCATION_ID' => 'GEOLOCATION_ID',
        'COL_GEOLOCATION_ID' => 'GEOLOCATION_ID',
        'geolocation_id' => 'GEOLOCATION_ID',
        'jobposting.geolocation_id' => 'GEOLOCATION_ID',
        'DuplicatesJobPostingId' => 'DUPLICATES_POSTING_ID',
        'JobPosting.DuplicatesJobPostingId' => 'DUPLICATES_POSTING_ID',
        'duplicatesJobPostingId' => 'DUPLICATES_POSTING_ID',
        'jobPosting.duplicatesJobPostingId' => 'DUPLICATES_POSTING_ID',
        'JobPostingTableMap::COL_DUPLICATES_POSTING_ID' => 'DUPLICATES_POSTING_ID',
        'COL_DUPLICATES_POSTING_ID' => 'DUPLICATES_POSTING_ID',
        'duplicates_posting_id' => 'DUPLICATES_POSTING_ID',
        'jobposting.duplicates_posting_id' => 'DUPLICATES_POSTING_ID',
        'TitleTokens' => 'TITLE_TOKENS',
        'JobPosting.TitleTokens' => 'TITLE_TOKENS',
        'titleTokens' => 'TITLE_TOKENS',
        'jobPosting.titleTokens' => 'TITLE_TOKENS',
        'JobPostingTableMap::COL_TITLE_TOKENS' => 'TITLE_TOKENS',
        'COL_TITLE_TOKENS' => 'TITLE_TOKENS',
        'title_tokens' => 'TITLE_TOKENS',
        'jobposting.title_tokens' => 'TITLE_TOKENS',
        'JobReferenceKey' => 'JOB_REFERENCE_KEY',
        'JobPosting.JobReferenceKey' => 'JOB_REFERENCE_KEY',
        'jobReferenceKey' => 'JOB_REFERENCE_KEY',
        'jobPosting.jobReferenceKey' => 'JOB_REFERENCE_KEY',
        'JobPostingTableMap::COL_JOB_REFERENCE_KEY' => 'JOB_REFERENCE_KEY',
        'COL_JOB_REFERENCE_KEY' => 'JOB_REFERENCE_KEY',
        'job_reference_key' => 'JOB_REFERENCE_KEY',
        'jobposting.job_reference_key' => 'JOB_REFERENCE_KEY',
        'KeyCompanyAndTitle' => 'KEY_COMPANY_AND_TITLE',
        'JobPosting.KeyCompanyAndTitle' => 'KEY_COMPANY_AND_TITLE',
        'keyCompanyAndTitle' => 'KEY_COMPANY_AND_TITLE',
        'jobPosting.keyCompanyAndTitle' => 'KEY_COMPANY_AND_TITLE',
        'JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE' => 'KEY_COMPANY_AND_TITLE',
        'COL_KEY_COMPANY_AND_TITLE' => 'KEY_COMPANY_AND_TITLE',
        'key_company_and_title' => 'KEY_COMPANY_AND_TITLE',
        'jobposting.key_company_and_title' => 'KEY_COMPANY_AND_TITLE',
    ];

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
        $this->addForeignKey('jobsite_key', 'JobSiteKey', 'VARCHAR', 'job_site', 'jobsite_key', true, 100, null);
        $this->addColumn('jobsite_post_id', 'JobSitePostId', 'VARCHAR', true, 1024, null);
        $this->addColumn('title', 'Title', 'VARCHAR', true, 255, null);
        $this->addColumn('url', 'Url', 'VARCHAR', true, 2500, null);
        $this->addColumn('employment_type', 'EmploymentType', 'VARCHAR', false, 100, null);
        $this->addColumn('pay_range', 'PayRange', 'VARCHAR', false, 100, null);
        $this->addColumn('location', 'Location', 'VARCHAR', false, 255, null);
        $this->addColumn('company', 'Company', 'VARCHAR', false, 255, null);
        $this->addColumn('department', 'Department', 'VARCHAR', false, 255, null);
        $this->addColumn('category', 'Category', 'VARCHAR', false, 100, null);
        $this->addColumn('last_updated_at', 'UpdatedAt', 'DATE', true, null, null);
        $this->addColumn('job_posted_date', 'PostedAt', 'DATE', false, null, null);
        $this->addColumn('first_seen_at', 'FirstSeenAt', 'DATE', true, null, null);
        $this->addColumn('location_display_value', 'LocationDisplayValue', 'VARCHAR', false, 255, null);
        $this->addForeignKey('geolocation_id', 'GeoLocationId', 'INTEGER', 'geolocation', 'geolocation_id', false, null, null);
        $this->addForeignKey('duplicates_posting_id', 'DuplicatesJobPostingId', 'INTEGER', 'jobposting', 'jobposting_id', false, null, null);
        $this->addColumn('title_tokens', 'TitleTokens', 'VARCHAR', false, 200, null);
        $this->addColumn('job_reference_key', 'JobReferenceKey', 'VARCHAR', false, 255, null);
        $this->addColumn('key_company_and_title', 'KeyCompanyAndTitle', 'VARCHAR', true, 255, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('JobSiteFromJP', '\\JobScooper\\DataAccess\\JobSiteRecord', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':jobsite_key',
    1 => ':jobsite_key',
  ),
), null, null, null, false);
        $this->addRelation('GeoLocationFromJP', '\\JobScooper\\DataAccess\\GeoLocation', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':geolocation_id',
    1 => ':geolocation_id',
  ),
), null, null, null, false);
        $this->addRelation('DuplicateJobPosting', '\\JobScooper\\DataAccess\\JobPosting', RelationMap::MANY_TO_ONE, array (
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
), 'CASCADE', null, 'UserJobMatches', false);
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
     * Method to invalidate the instance pool of all tables related to jobposting     * by a foreign key with ON DELETE CASCADE
     */
    public static function clearRelatedInstancePool()
    {
        // Invalidate objects in related instance pools,
        // since one or more of them may be deleted by ON DELETE CASCADE/SETNULL rule.
        UserJobMatchTableMap::clearInstancePool();
    }

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
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOBSITE_KEY);
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOBSITE_POST_ID);
            $criteria->addSelectColumn(JobPostingTableMap::COL_TITLE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_URL);
            $criteria->addSelectColumn(JobPostingTableMap::COL_EMPLOYMENT_TYPE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_PAY_RANGE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_LOCATION);
            $criteria->addSelectColumn(JobPostingTableMap::COL_COMPANY);
            $criteria->addSelectColumn(JobPostingTableMap::COL_DEPARTMENT);
            $criteria->addSelectColumn(JobPostingTableMap::COL_CATEGORY);
            $criteria->addSelectColumn(JobPostingTableMap::COL_LAST_UPDATED_AT);
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOB_POSTED_DATE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_FIRST_SEEN_AT);
            $criteria->addSelectColumn(JobPostingTableMap::COL_LOCATION_DISPLAY_VALUE);
            $criteria->addSelectColumn(JobPostingTableMap::COL_GEOLOCATION_ID);
            $criteria->addSelectColumn(JobPostingTableMap::COL_DUPLICATES_POSTING_ID);
            $criteria->addSelectColumn(JobPostingTableMap::COL_TITLE_TOKENS);
            $criteria->addSelectColumn(JobPostingTableMap::COL_JOB_REFERENCE_KEY);
            $criteria->addSelectColumn(JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE);
        } else {
            $criteria->addSelectColumn($alias . '.jobposting_id');
            $criteria->addSelectColumn($alias . '.jobsite_key');
            $criteria->addSelectColumn($alias . '.jobsite_post_id');
            $criteria->addSelectColumn($alias . '.title');
            $criteria->addSelectColumn($alias . '.url');
            $criteria->addSelectColumn($alias . '.employment_type');
            $criteria->addSelectColumn($alias . '.pay_range');
            $criteria->addSelectColumn($alias . '.location');
            $criteria->addSelectColumn($alias . '.company');
            $criteria->addSelectColumn($alias . '.department');
            $criteria->addSelectColumn($alias . '.category');
            $criteria->addSelectColumn($alias . '.last_updated_at');
            $criteria->addSelectColumn($alias . '.job_posted_date');
            $criteria->addSelectColumn($alias . '.first_seen_at');
            $criteria->addSelectColumn($alias . '.location_display_value');
            $criteria->addSelectColumn($alias . '.geolocation_id');
            $criteria->addSelectColumn($alias . '.duplicates_posting_id');
            $criteria->addSelectColumn($alias . '.title_tokens');
            $criteria->addSelectColumn($alias . '.job_reference_key');
            $criteria->addSelectColumn($alias . '.key_company_and_title');
        }
    }

    /**
     * Remove all the columns needed to create a new object.
     *
     * Note: any columns that were marked with lazyLoad="true" in the
     * XML schema will not be removed as they are only loaded on demand.
     *
     * @param Criteria $criteria object containing the columns to remove.
     * @param string   $alias    optional table alias
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function removeSelectColumns(Criteria $criteria, $alias = null)
    {
        if (null === $alias) {
            $criteria->removeSelectColumn(JobPostingTableMap::COL_JOBPOSTING_ID);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_JOBSITE_KEY);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_JOBSITE_POST_ID);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_TITLE);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_URL);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_EMPLOYMENT_TYPE);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_PAY_RANGE);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_LOCATION);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_COMPANY);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_DEPARTMENT);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_CATEGORY);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_LAST_UPDATED_AT);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_JOB_POSTED_DATE);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_FIRST_SEEN_AT);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_LOCATION_DISPLAY_VALUE);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_GEOLOCATION_ID);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_DUPLICATES_POSTING_ID);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_TITLE_TOKENS);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_JOB_REFERENCE_KEY);
            $criteria->removeSelectColumn(JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE);
        } else {
            $criteria->removeSelectColumn($alias . '.jobposting_id');
            $criteria->removeSelectColumn($alias . '.jobsite_key');
            $criteria->removeSelectColumn($alias . '.jobsite_post_id');
            $criteria->removeSelectColumn($alias . '.title');
            $criteria->removeSelectColumn($alias . '.url');
            $criteria->removeSelectColumn($alias . '.employment_type');
            $criteria->removeSelectColumn($alias . '.pay_range');
            $criteria->removeSelectColumn($alias . '.location');
            $criteria->removeSelectColumn($alias . '.company');
            $criteria->removeSelectColumn($alias . '.department');
            $criteria->removeSelectColumn($alias . '.category');
            $criteria->removeSelectColumn($alias . '.last_updated_at');
            $criteria->removeSelectColumn($alias . '.job_posted_date');
            $criteria->removeSelectColumn($alias . '.first_seen_at');
            $criteria->removeSelectColumn($alias . '.location_display_value');
            $criteria->removeSelectColumn($alias . '.geolocation_id');
            $criteria->removeSelectColumn($alias . '.duplicates_posting_id');
            $criteria->removeSelectColumn($alias . '.title_tokens');
            $criteria->removeSelectColumn($alias . '.job_reference_key');
            $criteria->removeSelectColumn($alias . '.key_company_and_title');
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
