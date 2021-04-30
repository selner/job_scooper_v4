<?php

namespace JobScooper\DataAccess\Map;

use JobScooper\DataAccess\UserJobMatch;
use JobScooper\DataAccess\UserJobMatchQuery;
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
 * This class defines the structure of the 'user_job_match' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 */
class UserJobMatchTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'JobScooper.DataAccess.Map.UserJobMatchTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'default';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'user_job_match';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\JobScooper\\DataAccess\\UserJobMatch';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'JobScooper.DataAccess.UserJobMatch';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 12;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 12;

    /**
     * the column name for the user_job_match_id field
     */
    const COL_USER_JOB_MATCH_ID = 'user_job_match.user_job_match_id';

    /**
     * the column name for the user_id field
     */
    const COL_USER_ID = 'user_job_match.user_id';

    /**
     * the column name for the jobposting_id field
     */
    const COL_JOBPOSTING_ID = 'user_job_match.jobposting_id';

    /**
     * the column name for the is_job_match field
     */
    const COL_IS_JOB_MATCH = 'user_job_match.is_job_match';

    /**
     * the column name for the good_job_title_keyword_matches field
     */
    const COL_GOOD_JOB_TITLE_KEYWORD_MATCHES = 'user_job_match.good_job_title_keyword_matches';

    /**
     * the column name for the is_excluded field
     */
    const COL_IS_EXCLUDED = 'user_job_match.is_excluded';

    /**
     * the column name for the out_of_user_area field
     */
    const COL_OUT_OF_USER_AREA = 'user_job_match.out_of_user_area';

    /**
     * the column name for the bad_job_title_keyword_matches field
     */
    const COL_BAD_JOB_TITLE_KEYWORD_MATCHES = 'user_job_match.bad_job_title_keyword_matches';

    /**
     * the column name for the bad_company_name_keyword_matches field
     */
    const COL_BAD_COMPANY_NAME_KEYWORD_MATCHES = 'user_job_match.bad_company_name_keyword_matches';

    /**
     * the column name for the user_notification_state field
     */
    const COL_USER_NOTIFICATION_STATE = 'user_job_match.user_notification_state';

    /**
     * the column name for the last_updated_at field
     */
    const COL_LAST_UPDATED_AT = 'user_job_match.last_updated_at';

    /**
     * the column name for the first_matched_at field
     */
    const COL_FIRST_MATCHED_AT = 'user_job_match.first_matched_at';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /** The enumerated values for the user_notification_state field */
    const COL_USER_NOTIFICATION_STATE_NOT_YET_MARKED = 'not-yet-marked';
    const COL_USER_NOTIFICATION_STATE_MARKED = 'marked';
    const COL_USER_NOTIFICATION_STATE_READY_TO_SEND = 'ready-to-send';
    const COL_USER_NOTIFICATION_STATE_SKIP_SEND = 'skip-send';
    const COL_USER_NOTIFICATION_STATE_SENT = 'sent';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('UserJobMatchId', 'UserId', 'JobPostingId', 'IsJobMatch', 'GoodJobTitleKeywordMatches', 'IsExcluded', 'OutOfUserArea', 'BadJobTitleKeywordMatches', 'BadCompanyNameKeywordMatches', 'UserNotificationState', 'UpdatedAt', 'FirstMatchedAt', ),
        self::TYPE_CAMELNAME     => array('userJobMatchId', 'userId', 'jobPostingId', 'isJobMatch', 'goodJobTitleKeywordMatches', 'isExcluded', 'outOfUserArea', 'badJobTitleKeywordMatches', 'badCompanyNameKeywordMatches', 'userNotificationState', 'updatedAt', 'firstMatchedAt', ),
        self::TYPE_COLNAME       => array(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID, UserJobMatchTableMap::COL_USER_ID, UserJobMatchTableMap::COL_JOBPOSTING_ID, UserJobMatchTableMap::COL_IS_JOB_MATCH, UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES, UserJobMatchTableMap::COL_IS_EXCLUDED, UserJobMatchTableMap::COL_OUT_OF_USER_AREA, UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES, UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES, UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE, UserJobMatchTableMap::COL_LAST_UPDATED_AT, UserJobMatchTableMap::COL_FIRST_MATCHED_AT, ),
        self::TYPE_FIELDNAME     => array('user_job_match_id', 'user_id', 'jobposting_id', 'is_job_match', 'good_job_title_keyword_matches', 'is_excluded', 'out_of_user_area', 'bad_job_title_keyword_matches', 'bad_company_name_keyword_matches', 'user_notification_state', 'last_updated_at', 'first_matched_at', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('UserJobMatchId' => 0, 'UserId' => 1, 'JobPostingId' => 2, 'IsJobMatch' => 3, 'GoodJobTitleKeywordMatches' => 4, 'IsExcluded' => 5, 'OutOfUserArea' => 6, 'BadJobTitleKeywordMatches' => 7, 'BadCompanyNameKeywordMatches' => 8, 'UserNotificationState' => 9, 'UpdatedAt' => 10, 'FirstMatchedAt' => 11, ),
        self::TYPE_CAMELNAME     => array('userJobMatchId' => 0, 'userId' => 1, 'jobPostingId' => 2, 'isJobMatch' => 3, 'goodJobTitleKeywordMatches' => 4, 'isExcluded' => 5, 'outOfUserArea' => 6, 'badJobTitleKeywordMatches' => 7, 'badCompanyNameKeywordMatches' => 8, 'userNotificationState' => 9, 'updatedAt' => 10, 'firstMatchedAt' => 11, ),
        self::TYPE_COLNAME       => array(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID => 0, UserJobMatchTableMap::COL_USER_ID => 1, UserJobMatchTableMap::COL_JOBPOSTING_ID => 2, UserJobMatchTableMap::COL_IS_JOB_MATCH => 3, UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES => 4, UserJobMatchTableMap::COL_IS_EXCLUDED => 5, UserJobMatchTableMap::COL_OUT_OF_USER_AREA => 6, UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES => 7, UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES => 8, UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE => 9, UserJobMatchTableMap::COL_LAST_UPDATED_AT => 10, UserJobMatchTableMap::COL_FIRST_MATCHED_AT => 11, ),
        self::TYPE_FIELDNAME     => array('user_job_match_id' => 0, 'user_id' => 1, 'jobposting_id' => 2, 'is_job_match' => 3, 'good_job_title_keyword_matches' => 4, 'is_excluded' => 5, 'out_of_user_area' => 6, 'bad_job_title_keyword_matches' => 7, 'bad_company_name_keyword_matches' => 8, 'user_notification_state' => 9, 'last_updated_at' => 10, 'first_matched_at' => 11, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, )
    );

    /**
     * Holds a list of column names and their normalized version.
     *
     * @var string[]
     */
    protected $normalizedColumnNameMap = [

        'UserJobMatchId' => 'USER_JOB_MATCH_ID',
        'UserJobMatch.UserJobMatchId' => 'USER_JOB_MATCH_ID',
        'userJobMatchId' => 'USER_JOB_MATCH_ID',
        'userJobMatch.userJobMatchId' => 'USER_JOB_MATCH_ID',
        'UserJobMatchTableMap::COL_USER_JOB_MATCH_ID' => 'USER_JOB_MATCH_ID',
        'COL_USER_JOB_MATCH_ID' => 'USER_JOB_MATCH_ID',
        'user_job_match_id' => 'USER_JOB_MATCH_ID',
        'user_job_match.user_job_match_id' => 'USER_JOB_MATCH_ID',
        'UserId' => 'USER_ID',
        'UserJobMatch.UserId' => 'USER_ID',
        'userId' => 'USER_ID',
        'userJobMatch.userId' => 'USER_ID',
        'UserJobMatchTableMap::COL_USER_ID' => 'USER_ID',
        'COL_USER_ID' => 'USER_ID',
        'user_id' => 'USER_ID',
        'user_job_match.user_id' => 'USER_ID',
        'JobPostingId' => 'JOBPOSTING_ID',
        'UserJobMatch.JobPostingId' => 'JOBPOSTING_ID',
        'jobPostingId' => 'JOBPOSTING_ID',
        'userJobMatch.jobPostingId' => 'JOBPOSTING_ID',
        'UserJobMatchTableMap::COL_JOBPOSTING_ID' => 'JOBPOSTING_ID',
        'COL_JOBPOSTING_ID' => 'JOBPOSTING_ID',
        'jobposting_id' => 'JOBPOSTING_ID',
        'user_job_match.jobposting_id' => 'JOBPOSTING_ID',
        'IsJobMatch' => 'IS_JOB_MATCH',
        'UserJobMatch.IsJobMatch' => 'IS_JOB_MATCH',
        'isJobMatch' => 'IS_JOB_MATCH',
        'userJobMatch.isJobMatch' => 'IS_JOB_MATCH',
        'UserJobMatchTableMap::COL_IS_JOB_MATCH' => 'IS_JOB_MATCH',
        'COL_IS_JOB_MATCH' => 'IS_JOB_MATCH',
        'is_job_match' => 'IS_JOB_MATCH',
        'user_job_match.is_job_match' => 'IS_JOB_MATCH',
        'GoodJobTitleKeywordMatches' => 'GOOD_JOB_TITLE_KEYWORD_MATCHES',
        'UserJobMatch.GoodJobTitleKeywordMatches' => 'GOOD_JOB_TITLE_KEYWORD_MATCHES',
        'goodJobTitleKeywordMatches' => 'GOOD_JOB_TITLE_KEYWORD_MATCHES',
        'userJobMatch.goodJobTitleKeywordMatches' => 'GOOD_JOB_TITLE_KEYWORD_MATCHES',
        'UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES' => 'GOOD_JOB_TITLE_KEYWORD_MATCHES',
        'COL_GOOD_JOB_TITLE_KEYWORD_MATCHES' => 'GOOD_JOB_TITLE_KEYWORD_MATCHES',
        'good_job_title_keyword_matches' => 'GOOD_JOB_TITLE_KEYWORD_MATCHES',
        'user_job_match.good_job_title_keyword_matches' => 'GOOD_JOB_TITLE_KEYWORD_MATCHES',
        'IsExcluded' => 'IS_EXCLUDED',
        'UserJobMatch.IsExcluded' => 'IS_EXCLUDED',
        'isExcluded' => 'IS_EXCLUDED',
        'userJobMatch.isExcluded' => 'IS_EXCLUDED',
        'UserJobMatchTableMap::COL_IS_EXCLUDED' => 'IS_EXCLUDED',
        'COL_IS_EXCLUDED' => 'IS_EXCLUDED',
        'is_excluded' => 'IS_EXCLUDED',
        'user_job_match.is_excluded' => 'IS_EXCLUDED',
        'OutOfUserArea' => 'OUT_OF_USER_AREA',
        'UserJobMatch.OutOfUserArea' => 'OUT_OF_USER_AREA',
        'outOfUserArea' => 'OUT_OF_USER_AREA',
        'userJobMatch.outOfUserArea' => 'OUT_OF_USER_AREA',
        'UserJobMatchTableMap::COL_OUT_OF_USER_AREA' => 'OUT_OF_USER_AREA',
        'COL_OUT_OF_USER_AREA' => 'OUT_OF_USER_AREA',
        'out_of_user_area' => 'OUT_OF_USER_AREA',
        'user_job_match.out_of_user_area' => 'OUT_OF_USER_AREA',
        'BadJobTitleKeywordMatches' => 'BAD_JOB_TITLE_KEYWORD_MATCHES',
        'UserJobMatch.BadJobTitleKeywordMatches' => 'BAD_JOB_TITLE_KEYWORD_MATCHES',
        'badJobTitleKeywordMatches' => 'BAD_JOB_TITLE_KEYWORD_MATCHES',
        'userJobMatch.badJobTitleKeywordMatches' => 'BAD_JOB_TITLE_KEYWORD_MATCHES',
        'UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES' => 'BAD_JOB_TITLE_KEYWORD_MATCHES',
        'COL_BAD_JOB_TITLE_KEYWORD_MATCHES' => 'BAD_JOB_TITLE_KEYWORD_MATCHES',
        'bad_job_title_keyword_matches' => 'BAD_JOB_TITLE_KEYWORD_MATCHES',
        'user_job_match.bad_job_title_keyword_matches' => 'BAD_JOB_TITLE_KEYWORD_MATCHES',
        'BadCompanyNameKeywordMatches' => 'BAD_COMPANY_NAME_KEYWORD_MATCHES',
        'UserJobMatch.BadCompanyNameKeywordMatches' => 'BAD_COMPANY_NAME_KEYWORD_MATCHES',
        'badCompanyNameKeywordMatches' => 'BAD_COMPANY_NAME_KEYWORD_MATCHES',
        'userJobMatch.badCompanyNameKeywordMatches' => 'BAD_COMPANY_NAME_KEYWORD_MATCHES',
        'UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES' => 'BAD_COMPANY_NAME_KEYWORD_MATCHES',
        'COL_BAD_COMPANY_NAME_KEYWORD_MATCHES' => 'BAD_COMPANY_NAME_KEYWORD_MATCHES',
        'bad_company_name_keyword_matches' => 'BAD_COMPANY_NAME_KEYWORD_MATCHES',
        'user_job_match.bad_company_name_keyword_matches' => 'BAD_COMPANY_NAME_KEYWORD_MATCHES',
        'UserNotificationState' => 'USER_NOTIFICATION_STATE',
        'UserJobMatch.UserNotificationState' => 'USER_NOTIFICATION_STATE',
        'userNotificationState' => 'USER_NOTIFICATION_STATE',
        'userJobMatch.userNotificationState' => 'USER_NOTIFICATION_STATE',
        'UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE' => 'USER_NOTIFICATION_STATE',
        'COL_USER_NOTIFICATION_STATE' => 'USER_NOTIFICATION_STATE',
        'user_notification_state' => 'USER_NOTIFICATION_STATE',
        'user_job_match.user_notification_state' => 'USER_NOTIFICATION_STATE',
        'UpdatedAt' => 'LAST_UPDATED_AT',
        'UserJobMatch.UpdatedAt' => 'LAST_UPDATED_AT',
        'updatedAt' => 'LAST_UPDATED_AT',
        'userJobMatch.updatedAt' => 'LAST_UPDATED_AT',
        'UserJobMatchTableMap::COL_LAST_UPDATED_AT' => 'LAST_UPDATED_AT',
        'COL_LAST_UPDATED_AT' => 'LAST_UPDATED_AT',
        'last_updated_at' => 'LAST_UPDATED_AT',
        'user_job_match.last_updated_at' => 'LAST_UPDATED_AT',
        'FirstMatchedAt' => 'FIRST_MATCHED_AT',
        'UserJobMatch.FirstMatchedAt' => 'FIRST_MATCHED_AT',
        'firstMatchedAt' => 'FIRST_MATCHED_AT',
        'userJobMatch.firstMatchedAt' => 'FIRST_MATCHED_AT',
        'UserJobMatchTableMap::COL_FIRST_MATCHED_AT' => 'FIRST_MATCHED_AT',
        'COL_FIRST_MATCHED_AT' => 'FIRST_MATCHED_AT',
        'first_matched_at' => 'FIRST_MATCHED_AT',
        'user_job_match.first_matched_at' => 'FIRST_MATCHED_AT',
    ];

    /** The enumerated values for this table */
    protected static $enumValueSets = array(
                UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE => array(
                            self::COL_USER_NOTIFICATION_STATE_NOT_YET_MARKED,
            self::COL_USER_NOTIFICATION_STATE_MARKED,
            self::COL_USER_NOTIFICATION_STATE_READY_TO_SEND,
            self::COL_USER_NOTIFICATION_STATE_SKIP_SEND,
            self::COL_USER_NOTIFICATION_STATE_SENT,
        ),
    );

    /**
     * Gets the list of values for all ENUM and SET columns
     * @return array
     */
    public static function getValueSets()
    {
      return static::$enumValueSets;
    }

    /**
     * Gets the list of values for an ENUM or SET column
     * @param string $colname
     * @return array list of possible values for the column
     */
    public static function getValueSet($colname)
    {
        $valueSets = self::getValueSets();

        return $valueSets[$colname];
    }

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
        $this->setName('user_job_match');
        $this->setPhpName('UserJobMatch');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\JobScooper\\DataAccess\\UserJobMatch');
        $this->setPackage('JobScooper.DataAccess');
        $this->setUseIdGenerator(true);
        $this->setIsCrossRef(true);
        // columns
        $this->addPrimaryKey('user_job_match_id', 'UserJobMatchId', 'INTEGER', true, null, null);
        $this->addForeignKey('user_id', 'UserId', 'INTEGER', 'user', 'user_id', true, null, null);
        $this->addForeignKey('jobposting_id', 'JobPostingId', 'INTEGER', 'jobposting', 'jobposting_id', true, null, null);
        $this->addColumn('is_job_match', 'IsJobMatch', 'BOOLEAN', false, 1, false);
        $this->addColumn('good_job_title_keyword_matches', 'GoodJobTitleKeywordMatches', 'VARCHAR', false, 100, null);
        $this->addColumn('is_excluded', 'IsExcluded', 'BOOLEAN', false, 1, false);
        $this->addColumn('out_of_user_area', 'OutOfUserArea', 'BOOLEAN', false, 1, false);
        $this->addColumn('bad_job_title_keyword_matches', 'BadJobTitleKeywordMatches', 'VARCHAR', false, 100, null);
        $this->addColumn('bad_company_name_keyword_matches', 'BadCompanyNameKeywordMatches', 'VARCHAR', false, 100, null);
        $this->addColumn('user_notification_state', 'UserNotificationState', 'ENUM', false, null, 'not-yet-marked');
        $this->getColumn('user_notification_state')->setValueSet(array (
  0 => 'not-yet-marked',
  1 => 'marked',
  2 => 'ready-to-send',
  3 => 'skip-send',
  4 => 'sent',
));
        $this->addColumn('last_updated_at', 'UpdatedAt', 'DATE', true, null, null);
        $this->addColumn('first_matched_at', 'FirstMatchedAt', 'DATE', true, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('UserFromUJM', '\\JobScooper\\DataAccess\\User', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':user_id',
    1 => ':user_id',
  ),
), 'CASCADE', null, null, false);
        $this->addRelation('JobPostingFromUJM', '\\JobScooper\\DataAccess\\JobPosting', RelationMap::MANY_TO_ONE, array (
  0 =>
  array (
    0 => ':jobposting_id',
    1 => ':jobposting_id',
  ),
), 'CASCADE', null, null, false);
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
            'timestampable' => array('create_column' => 'first_matched_at', 'update_column' => 'last_updated_at', 'disable_created_at' => 'false', 'disable_updated_at' => 'false', ),
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserJobMatchId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return null === $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserJobMatchId', TableMap::TYPE_PHPNAME, $indexType)] || is_scalar($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserJobMatchId', TableMap::TYPE_PHPNAME, $indexType)]) || is_callable([$row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserJobMatchId', TableMap::TYPE_PHPNAME, $indexType)], '__toString']) ? (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserJobMatchId', TableMap::TYPE_PHPNAME, $indexType)] : $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('UserJobMatchId', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('UserJobMatchId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? UserJobMatchTableMap::CLASS_DEFAULT : UserJobMatchTableMap::OM_CLASS;
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
     * @return array           (UserJobMatch object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = UserJobMatchTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = UserJobMatchTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + UserJobMatchTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = UserJobMatchTableMap::OM_CLASS;
            /** @var UserJobMatch $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            UserJobMatchTableMap::addInstanceToPool($obj, $key);
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
            $key = UserJobMatchTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = UserJobMatchTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var UserJobMatch $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                UserJobMatchTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_USER_ID);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_JOBPOSTING_ID);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_IS_JOB_MATCH);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_IS_EXCLUDED);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_OUT_OF_USER_AREA);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_LAST_UPDATED_AT);
            $criteria->addSelectColumn(UserJobMatchTableMap::COL_FIRST_MATCHED_AT);
        } else {
            $criteria->addSelectColumn($alias . '.user_job_match_id');
            $criteria->addSelectColumn($alias . '.user_id');
            $criteria->addSelectColumn($alias . '.jobposting_id');
            $criteria->addSelectColumn($alias . '.is_job_match');
            $criteria->addSelectColumn($alias . '.good_job_title_keyword_matches');
            $criteria->addSelectColumn($alias . '.is_excluded');
            $criteria->addSelectColumn($alias . '.out_of_user_area');
            $criteria->addSelectColumn($alias . '.bad_job_title_keyword_matches');
            $criteria->addSelectColumn($alias . '.bad_company_name_keyword_matches');
            $criteria->addSelectColumn($alias . '.user_notification_state');
            $criteria->addSelectColumn($alias . '.last_updated_at');
            $criteria->addSelectColumn($alias . '.first_matched_at');
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
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_USER_ID);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_JOBPOSTING_ID);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_IS_JOB_MATCH);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_IS_EXCLUDED);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_OUT_OF_USER_AREA);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_LAST_UPDATED_AT);
            $criteria->removeSelectColumn(UserJobMatchTableMap::COL_FIRST_MATCHED_AT);
        } else {
            $criteria->removeSelectColumn($alias . '.user_job_match_id');
            $criteria->removeSelectColumn($alias . '.user_id');
            $criteria->removeSelectColumn($alias . '.jobposting_id');
            $criteria->removeSelectColumn($alias . '.is_job_match');
            $criteria->removeSelectColumn($alias . '.good_job_title_keyword_matches');
            $criteria->removeSelectColumn($alias . '.is_excluded');
            $criteria->removeSelectColumn($alias . '.out_of_user_area');
            $criteria->removeSelectColumn($alias . '.bad_job_title_keyword_matches');
            $criteria->removeSelectColumn($alias . '.bad_company_name_keyword_matches');
            $criteria->removeSelectColumn($alias . '.user_notification_state');
            $criteria->removeSelectColumn($alias . '.last_updated_at');
            $criteria->removeSelectColumn($alias . '.first_matched_at');
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
        return Propel::getServiceContainer()->getDatabaseMap(UserJobMatchTableMap::DATABASE_NAME)->getTable(UserJobMatchTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(UserJobMatchTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(UserJobMatchTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new UserJobMatchTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a UserJobMatch or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or UserJobMatch object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \JobScooper\DataAccess\UserJobMatch) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(UserJobMatchTableMap::DATABASE_NAME);
            $criteria->add(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID, (array) $values, Criteria::IN);
        }

        $query = UserJobMatchQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            UserJobMatchTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                UserJobMatchTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the user_job_match table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return UserJobMatchQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a UserJobMatch or Criteria object.
     *
     * @param mixed               $criteria Criteria or UserJobMatch object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from UserJobMatch object
        }

        if ($criteria->containsKey(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID) && $criteria->keyContainsValue(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.UserJobMatchTableMap::COL_USER_JOB_MATCH_ID.')');
        }


        // Set the correct dbName
        $query = UserJobMatchQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // UserJobMatchTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
UserJobMatchTableMap::buildTableMap();
