<?php

namespace JobScooper\DataAccess\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\DataAccess\JobPosting as ChildJobPosting;
use JobScooper\DataAccess\JobPostingQuery as ChildJobPostingQuery;
use JobScooper\DataAccess\User as ChildUser;
use JobScooper\DataAccess\UserJobMatch as ChildUserJobMatch;
use JobScooper\DataAccess\UserJobMatchQuery as ChildUserJobMatchQuery;
use JobScooper\DataAccess\UserQuery as ChildUserQuery;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;

/**
 * Base class that represents a row from the 'user_job_match' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.DataAccess.Base
 */
abstract class UserJobMatch implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\DataAccess\\Map\\UserJobMatchTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the user_job_match_id field.
     *
     * @var        int
     */
    protected $user_job_match_id;

    /**
     * The value for the user_id field.
     *
     * @var        int
     */
    protected $user_id;

    /**
     * The value for the jobposting_id field.
     *
     * @var        int
     */
    protected $jobposting_id;

    /**
     * The value for the is_job_match field.
     *
     * Note: this column has a database default value of: false
     * @var        boolean
     */
    protected $is_job_match;

    /**
     * The value for the good_job_title_keyword_matches field.
     *
     * @var        string
     */
    protected $good_job_title_keyword_matches;

    /**
     * The value for the is_excluded field.
     *
     * Note: this column has a database default value of: false
     * @var        boolean
     */
    protected $is_excluded;

    /**
     * The value for the out_of_user_area field.
     *
     * Note: this column has a database default value of: false
     * @var        boolean
     */
    protected $out_of_user_area;

    /**
     * The value for the bad_job_title_keyword_matches field.
     *
     * @var        string
     */
    protected $bad_job_title_keyword_matches;

    /**
     * The value for the bad_company_name_keyword_matches field.
     *
     * @var        string
     */
    protected $bad_company_name_keyword_matches;

    /**
     * The value for the user_notification_state field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $user_notification_state;

    /**
     * The value for the last_updated_at field.
     *
     * @var        DateTime
     */
    protected $last_updated_at;

    /**
     * The value for the first_matched_at field.
     *
     * @var        DateTime
     */
    protected $first_matched_at;

    /**
     * @var        ChildUser
     */
    protected $aUserFromUJM;

    /**
     * @var        ChildJobPosting
     */
    protected $aJobPostingFromUJM;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->is_job_match = false;
        $this->is_excluded = false;
        $this->out_of_user_area = false;
        $this->user_notification_state = 0;
    }

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\UserJobMatch object.
     * @see applyDefaults()
     */
    public function __construct()
    {
        $this->applyDefaultValues();
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>UserJobMatch</code> instance.  If
     * <code>obj</code> is an instance of <code>UserJobMatch</code>, delegates to
     * <code>equals(UserJobMatch)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        if (!$obj instanceof static) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey() || null === $obj->getPrimaryKey()) {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return $this|UserJobMatch The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        $cls = new \ReflectionClass($this);
        $propertyNames = [];
        $serializableProperties = array_diff($cls->getProperties(), $cls->getProperties(\ReflectionProperty::IS_STATIC));

        foreach($serializableProperties as $property) {
            $propertyNames[] = $property->getName();
        }

        return $propertyNames;
    }

    /**
     * Get the [user_job_match_id] column value.
     *
     * @return int
     */
    public function getUserJobMatchId()
    {
        return $this->user_job_match_id;
    }

    /**
     * Get the [user_id] column value.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Get the [jobposting_id] column value.
     *
     * @return int
     */
    public function getJobPostingId()
    {
        return $this->jobposting_id;
    }

    /**
     * Get the [is_job_match] column value.
     *
     * @return boolean
     */
    public function getIsJobMatch()
    {
        return $this->is_job_match;
    }

    /**
     * Get the [is_job_match] column value.
     *
     * @return boolean
     */
    public function isJobMatch()
    {
        return $this->getIsJobMatch();
    }

    /**
     * Get the [good_job_title_keyword_matches] column value.
     *
     * @return string
     */
    public function getGoodJobTitleKeywordMatches()
    {
        return $this->good_job_title_keyword_matches;
    }

    /**
     * Get the [is_excluded] column value.
     *
     * @return boolean
     */
    public function getIsExcluded()
    {
        return $this->is_excluded;
    }

    /**
     * Get the [is_excluded] column value.
     *
     * @return boolean
     */
    public function isExcluded()
    {
        return $this->getIsExcluded();
    }

    /**
     * Get the [out_of_user_area] column value.
     *
     * @return boolean
     */
    public function getOutOfUserArea()
    {
        return $this->out_of_user_area;
    }

    /**
     * Get the [out_of_user_area] column value.
     *
     * @return boolean
     */
    public function isOutOfUserArea()
    {
        return $this->getOutOfUserArea();
    }

    /**
     * Get the [bad_job_title_keyword_matches] column value.
     *
     * @return string
     */
    public function getBadJobTitleKeywordMatches()
    {
        return $this->bad_job_title_keyword_matches;
    }

    /**
     * Get the [bad_company_name_keyword_matches] column value.
     *
     * @return string
     */
    public function getBadCompanyNameKeywordMatches()
    {
        return $this->bad_company_name_keyword_matches;
    }

    /**
     * Get the [user_notification_state] column value.
     *
     * @return string
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getUserNotificationState()
    {
        if (null === $this->user_notification_state) {
            return null;
        }
        $valueSet = UserJobMatchTableMap::getValueSet(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE);
        if (!isset($valueSet[$this->user_notification_state])) {
            throw new PropelException('Unknown stored enum key: ' . $this->user_notification_state);
        }

        return $valueSet[$this->user_notification_state];
    }

    /**
     * Get the [optionally formatted] temporal [last_updated_at] column value.
     *
     *
     * @param      string|null $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getUpdatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->last_updated_at;
        } else {
            return $this->last_updated_at instanceof \DateTimeInterface ? $this->last_updated_at->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [first_matched_at] column value.
     *
     *
     * @param      string|null $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getFirstMatchedAt($format = NULL)
    {
        if ($format === null) {
            return $this->first_matched_at;
        } else {
            return $this->first_matched_at instanceof \DateTimeInterface ? $this->first_matched_at->format($format) : null;
        }
    }

    /**
     * Set the value of [user_job_match_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setUserJobMatchId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_job_match_id !== $v) {
            $this->user_job_match_id = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_USER_JOB_MATCH_ID] = true;
        }

        return $this;
    } // setUserJobMatchId()

    /**
     * Set the value of [user_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setUserId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_id !== $v) {
            $this->user_id = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_USER_ID] = true;
        }

        if ($this->aUserFromUJM !== null && $this->aUserFromUJM->getUserId() !== $v) {
            $this->aUserFromUJM = null;
        }

        return $this;
    } // setUserId()

    /**
     * Set the value of [jobposting_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setJobPostingId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->jobposting_id !== $v) {
            $this->jobposting_id = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_JOBPOSTING_ID] = true;
        }

        if ($this->aJobPostingFromUJM !== null && $this->aJobPostingFromUJM->getJobPostingId() !== $v) {
            $this->aJobPostingFromUJM = null;
        }

        return $this;
    } // setJobPostingId()

    /**
     * Sets the value of the [is_job_match] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param  boolean|integer|string $v The new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setIsJobMatch($v)
    {
        if ($v !== null) {
            if (is_string($v)) {
                $v = in_array(strtolower($v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                $v = (boolean) $v;
            }
        }

        if ($this->is_job_match !== $v) {
            $this->is_job_match = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_IS_JOB_MATCH] = true;
        }

        return $this;
    } // setIsJobMatch()

    /**
     * Set the value of [good_job_title_keyword_matches] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setGoodJobTitleKeywordMatches($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->good_job_title_keyword_matches !== $v) {
            $this->good_job_title_keyword_matches = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES] = true;
        }

        return $this;
    } // setGoodJobTitleKeywordMatches()

    /**
     * Sets the value of the [is_excluded] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param  boolean|integer|string $v The new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setIsExcluded($v)
    {
        if ($v !== null) {
            if (is_string($v)) {
                $v = in_array(strtolower($v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                $v = (boolean) $v;
            }
        }

        if ($this->is_excluded !== $v) {
            $this->is_excluded = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_IS_EXCLUDED] = true;
        }

        return $this;
    } // setIsExcluded()

    /**
     * Sets the value of the [out_of_user_area] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param  boolean|integer|string $v The new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setOutOfUserArea($v)
    {
        if ($v !== null) {
            if (is_string($v)) {
                $v = in_array(strtolower($v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                $v = (boolean) $v;
            }
        }

        if ($this->out_of_user_area !== $v) {
            $this->out_of_user_area = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_OUT_OF_USER_AREA] = true;
        }

        return $this;
    } // setOutOfUserArea()

    /**
     * Set the value of [bad_job_title_keyword_matches] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setBadJobTitleKeywordMatches($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->bad_job_title_keyword_matches !== $v) {
            $this->bad_job_title_keyword_matches = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES] = true;
        }

        return $this;
    } // setBadJobTitleKeywordMatches()

    /**
     * Set the value of [bad_company_name_keyword_matches] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setBadCompanyNameKeywordMatches($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->bad_company_name_keyword_matches !== $v) {
            $this->bad_company_name_keyword_matches = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES] = true;
        }

        return $this;
    } // setBadCompanyNameKeywordMatches()

    /**
     * Set the value of [user_notification_state] column.
     *
     * @param  string $v new value
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setUserNotificationState($v)
    {
        if ($v !== null) {
            $valueSet = UserJobMatchTableMap::getValueSet(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE);
            if (!in_array($v, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $v));
            }
            $v = array_search($v, $valueSet);
        }

        if ($this->user_notification_state !== $v) {
            $this->user_notification_state = $v;
            $this->modifiedColumns[UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE] = true;
        }

        return $this;
    } // setUserNotificationState()

    /**
     * Sets the value of [last_updated_at] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->last_updated_at !== null || $dt !== null) {
            if ($this->last_updated_at === null || $dt === null || $dt->format("Y-m-d") !== $this->last_updated_at->format("Y-m-d")) {
                $this->last_updated_at = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserJobMatchTableMap::COL_LAST_UPDATED_AT] = true;
            }
        } // if either are not null

        return $this;
    } // setUpdatedAt()

    /**
     * Sets the value of [first_matched_at] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     */
    public function setFirstMatchedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->first_matched_at !== null || $dt !== null) {
            if ($this->first_matched_at === null || $dt === null || $dt->format("Y-m-d") !== $this->first_matched_at->format("Y-m-d")) {
                $this->first_matched_at = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserJobMatchTableMap::COL_FIRST_MATCHED_AT] = true;
            }
        } // if either are not null

        return $this;
    } // setFirstMatchedAt()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
            if ($this->is_job_match !== false) {
                return false;
            }

            if ($this->is_excluded !== false) {
                return false;
            }

            if ($this->out_of_user_area !== false) {
                return false;
            }

            if ($this->user_notification_state !== 0) {
                return false;
            }

        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserJobMatchTableMap::translateFieldName('UserJobMatchId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_job_match_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserJobMatchTableMap::translateFieldName('UserId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserJobMatchTableMap::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobposting_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserJobMatchTableMap::translateFieldName('IsJobMatch', TableMap::TYPE_PHPNAME, $indexType)];
            $this->is_job_match = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : UserJobMatchTableMap::translateFieldName('GoodJobTitleKeywordMatches', TableMap::TYPE_PHPNAME, $indexType)];
            $this->good_job_title_keyword_matches = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : UserJobMatchTableMap::translateFieldName('IsExcluded', TableMap::TYPE_PHPNAME, $indexType)];
            $this->is_excluded = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : UserJobMatchTableMap::translateFieldName('OutOfUserArea', TableMap::TYPE_PHPNAME, $indexType)];
            $this->out_of_user_area = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : UserJobMatchTableMap::translateFieldName('BadJobTitleKeywordMatches', TableMap::TYPE_PHPNAME, $indexType)];
            $this->bad_job_title_keyword_matches = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : UserJobMatchTableMap::translateFieldName('BadCompanyNameKeywordMatches', TableMap::TYPE_PHPNAME, $indexType)];
            $this->bad_company_name_keyword_matches = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : UserJobMatchTableMap::translateFieldName('UserNotificationState', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_notification_state = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : UserJobMatchTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00') {
                $col = null;
            }
            $this->last_updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 11 + $startcol : UserJobMatchTableMap::translateFieldName('FirstMatchedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00') {
                $col = null;
            }
            $this->first_matched_at = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 12; // 12 = UserJobMatchTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\DataAccess\\UserJobMatch'), 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
        if ($this->aUserFromUJM !== null && $this->user_id !== $this->aUserFromUJM->getUserId()) {
            $this->aUserFromUJM = null;
        }
        if ($this->aJobPostingFromUJM !== null && $this->jobposting_id !== $this->aJobPostingFromUJM->getJobPostingId()) {
            $this->aJobPostingFromUJM = null;
        }
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildUserJobMatchQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aUserFromUJM = null;
            $this->aJobPostingFromUJM = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see UserJobMatch::setDeleted()
     * @see UserJobMatch::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildUserJobMatchQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $this->setDeleted(true);
            }
        });
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($this->alreadyInSave) {
            return 0;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserJobMatchTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior
                $time = time();
                $highPrecision = \Propel\Runtime\Util\PropelDateTime::createHighPrecision();
                if (!$this->isColumnModified(UserJobMatchTableMap::COL_FIRST_MATCHED_AT)) {
                    $this->setFirstMatchedAt($highPrecision);
                }
                if (!$this->isColumnModified(UserJobMatchTableMap::COL_LAST_UPDATED_AT)) {
                    $this->setUpdatedAt($highPrecision);
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(UserJobMatchTableMap::COL_LAST_UPDATED_AT)) {
                    $this->setUpdatedAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                UserJobMatchTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }

            return $affectedRows;
        });
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aUserFromUJM !== null) {
                if ($this->aUserFromUJM->isModified() || $this->aUserFromUJM->isNew()) {
                    $affectedRows += $this->aUserFromUJM->save($con);
                }
                $this->setUserFromUJM($this->aUserFromUJM);
            }

            if ($this->aJobPostingFromUJM !== null) {
                if ($this->aJobPostingFromUJM->isModified() || $this->aJobPostingFromUJM->isNew()) {
                    $affectedRows += $this->aJobPostingFromUJM->save($con);
                }
                $this->setJobPostingFromUJM($this->aJobPostingFromUJM);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                    $affectedRows += 1;
                } else {
                    $affectedRows += $this->doUpdate($con);
                }
                $this->resetModified();
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[UserJobMatchTableMap::COL_USER_JOB_MATCH_ID] = true;
        if (null !== $this->user_job_match_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . UserJobMatchTableMap::COL_USER_JOB_MATCH_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_job_match_id';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_USER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_id';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_JOBPOSTING_ID)) {
            $modifiedColumns[':p' . $index++]  = 'jobposting_id';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_IS_JOB_MATCH)) {
            $modifiedColumns[':p' . $index++]  = 'is_job_match';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES)) {
            $modifiedColumns[':p' . $index++]  = 'good_job_title_keyword_matches';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_IS_EXCLUDED)) {
            $modifiedColumns[':p' . $index++]  = 'is_excluded';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_OUT_OF_USER_AREA)) {
            $modifiedColumns[':p' . $index++]  = 'out_of_user_area';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES)) {
            $modifiedColumns[':p' . $index++]  = 'bad_job_title_keyword_matches';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES)) {
            $modifiedColumns[':p' . $index++]  = 'bad_company_name_keyword_matches';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE)) {
            $modifiedColumns[':p' . $index++]  = 'user_notification_state';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_LAST_UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'last_updated_at';
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_FIRST_MATCHED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'first_matched_at';
        }

        $sql = sprintf(
            'INSERT INTO user_job_match (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'user_job_match_id':
                        $stmt->bindValue($identifier, $this->user_job_match_id, PDO::PARAM_INT);
                        break;
                    case 'user_id':
                        $stmt->bindValue($identifier, $this->user_id, PDO::PARAM_INT);
                        break;
                    case 'jobposting_id':
                        $stmt->bindValue($identifier, $this->jobposting_id, PDO::PARAM_INT);
                        break;
                    case 'is_job_match':
                        $stmt->bindValue($identifier, (int) $this->is_job_match, PDO::PARAM_INT);
                        break;
                    case 'good_job_title_keyword_matches':
                        $stmt->bindValue($identifier, $this->good_job_title_keyword_matches, PDO::PARAM_STR);
                        break;
                    case 'is_excluded':
                        $stmt->bindValue($identifier, (int) $this->is_excluded, PDO::PARAM_INT);
                        break;
                    case 'out_of_user_area':
                        $stmt->bindValue($identifier, (int) $this->out_of_user_area, PDO::PARAM_INT);
                        break;
                    case 'bad_job_title_keyword_matches':
                        $stmt->bindValue($identifier, $this->bad_job_title_keyword_matches, PDO::PARAM_STR);
                        break;
                    case 'bad_company_name_keyword_matches':
                        $stmt->bindValue($identifier, $this->bad_company_name_keyword_matches, PDO::PARAM_STR);
                        break;
                    case 'user_notification_state':
                        $stmt->bindValue($identifier, $this->user_notification_state, PDO::PARAM_INT);
                        break;
                    case 'last_updated_at':
                        $stmt->bindValue($identifier, $this->last_updated_at ? $this->last_updated_at->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'first_matched_at':
                        $stmt->bindValue($identifier, $this->first_matched_at ? $this->first_matched_at->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setUserJobMatchId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserJobMatchTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getUserJobMatchId();
                break;
            case 1:
                return $this->getUserId();
                break;
            case 2:
                return $this->getJobPostingId();
                break;
            case 3:
                return $this->getIsJobMatch();
                break;
            case 4:
                return $this->getGoodJobTitleKeywordMatches();
                break;
            case 5:
                return $this->getIsExcluded();
                break;
            case 6:
                return $this->getOutOfUserArea();
                break;
            case 7:
                return $this->getBadJobTitleKeywordMatches();
                break;
            case 8:
                return $this->getBadCompanyNameKeywordMatches();
                break;
            case 9:
                return $this->getUserNotificationState();
                break;
            case 10:
                return $this->getUpdatedAt();
                break;
            case 11:
                return $this->getFirstMatchedAt();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {

        if (isset($alreadyDumpedObjects['UserJobMatch'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['UserJobMatch'][$this->hashCode()] = true;
        $keys = UserJobMatchTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getUserJobMatchId(),
            $keys[1] => $this->getUserId(),
            $keys[2] => $this->getJobPostingId(),
            $keys[3] => $this->getIsJobMatch(),
            $keys[4] => $this->getGoodJobTitleKeywordMatches(),
            $keys[5] => $this->getIsExcluded(),
            $keys[6] => $this->getOutOfUserArea(),
            $keys[7] => $this->getBadJobTitleKeywordMatches(),
            $keys[8] => $this->getBadCompanyNameKeywordMatches(),
            $keys[9] => $this->getUserNotificationState(),
            $keys[10] => $this->getUpdatedAt(),
            $keys[11] => $this->getFirstMatchedAt(),
        );
        if ($result[$keys[10]] instanceof \DateTimeInterface) {
            $result[$keys[10]] = $result[$keys[10]]->format('c');
        }

        if ($result[$keys[11]] instanceof \DateTimeInterface) {
            $result[$keys[11]] = $result[$keys[11]]->format('c');
        }

        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aUserFromUJM) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'user';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user';
                        break;
                    default:
                        $key = 'UserFromUJM';
                }

                $result[$key] = $this->aUserFromUJM->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aJobPostingFromUJM) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'jobPosting';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'jobposting';
                        break;
                    default:
                        $key = 'JobPostingFromUJM';
                }

                $result[$key] = $this->aJobPostingFromUJM->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param  string $name
     * @param  mixed  $value field value
     * @param  string $type The type of fieldname the $name is of:
     *                one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                Defaults to TableMap::TYPE_PHPNAME.
     * @return $this|\JobScooper\DataAccess\UserJobMatch
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserJobMatchTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\DataAccess\UserJobMatch
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setUserJobMatchId($value);
                break;
            case 1:
                $this->setUserId($value);
                break;
            case 2:
                $this->setJobPostingId($value);
                break;
            case 3:
                $this->setIsJobMatch($value);
                break;
            case 4:
                $this->setGoodJobTitleKeywordMatches($value);
                break;
            case 5:
                $this->setIsExcluded($value);
                break;
            case 6:
                $this->setOutOfUserArea($value);
                break;
            case 7:
                $this->setBadJobTitleKeywordMatches($value);
                break;
            case 8:
                $this->setBadCompanyNameKeywordMatches($value);
                break;
            case 9:
                $valueSet = UserJobMatchTableMap::getValueSet(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE);
                if (isset($valueSet[$value])) {
                    $value = $valueSet[$value];
                }
                $this->setUserNotificationState($value);
                break;
            case 10:
                $this->setUpdatedAt($value);
                break;
            case 11:
                $this->setFirstMatchedAt($value);
                break;
        } // switch()

        return $this;
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = UserJobMatchTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setUserJobMatchId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setUserId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setJobPostingId($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setIsJobMatch($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setGoodJobTitleKeywordMatches($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setIsExcluded($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setOutOfUserArea($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setBadJobTitleKeywordMatches($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setBadCompanyNameKeywordMatches($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setUserNotificationState($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setUpdatedAt($arr[$keys[10]]);
        }
        if (array_key_exists($keys[11], $arr)) {
            $this->setFirstMatchedAt($arr[$keys[11]]);
        }
    }

     /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     * @param string $keyType The type of keys the array uses.
     *
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object, for fluid interface
     */
    public function importFrom($parser, $data, $keyType = TableMap::TYPE_PHPNAME)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), $keyType);

        return $this;
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(UserJobMatchTableMap::DATABASE_NAME);

        if ($this->isColumnModified(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID)) {
            $criteria->add(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID, $this->user_job_match_id);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_USER_ID)) {
            $criteria->add(UserJobMatchTableMap::COL_USER_ID, $this->user_id);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_JOBPOSTING_ID)) {
            $criteria->add(UserJobMatchTableMap::COL_JOBPOSTING_ID, $this->jobposting_id);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_IS_JOB_MATCH)) {
            $criteria->add(UserJobMatchTableMap::COL_IS_JOB_MATCH, $this->is_job_match);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES)) {
            $criteria->add(UserJobMatchTableMap::COL_GOOD_JOB_TITLE_KEYWORD_MATCHES, $this->good_job_title_keyword_matches);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_IS_EXCLUDED)) {
            $criteria->add(UserJobMatchTableMap::COL_IS_EXCLUDED, $this->is_excluded);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_OUT_OF_USER_AREA)) {
            $criteria->add(UserJobMatchTableMap::COL_OUT_OF_USER_AREA, $this->out_of_user_area);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES)) {
            $criteria->add(UserJobMatchTableMap::COL_BAD_JOB_TITLE_KEYWORD_MATCHES, $this->bad_job_title_keyword_matches);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES)) {
            $criteria->add(UserJobMatchTableMap::COL_BAD_COMPANY_NAME_KEYWORD_MATCHES, $this->bad_company_name_keyword_matches);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE)) {
            $criteria->add(UserJobMatchTableMap::COL_USER_NOTIFICATION_STATE, $this->user_notification_state);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_LAST_UPDATED_AT)) {
            $criteria->add(UserJobMatchTableMap::COL_LAST_UPDATED_AT, $this->last_updated_at);
        }
        if ($this->isColumnModified(UserJobMatchTableMap::COL_FIRST_MATCHED_AT)) {
            $criteria->add(UserJobMatchTableMap::COL_FIRST_MATCHED_AT, $this->first_matched_at);
        }

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @throws LogicException if no primary key is defined
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = ChildUserJobMatchQuery::create();
        $criteria->add(UserJobMatchTableMap::COL_USER_JOB_MATCH_ID, $this->user_job_match_id);

        return $criteria;
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        $validPk = null !== $this->getUserJobMatchId();

        $validPrimaryKeyFKs = 0;
        $primaryKeyFKs = [];

        if ($validPk) {
            return crc32(json_encode($this->getPrimaryKey(), JSON_UNESCAPED_UNICODE));
        } elseif ($validPrimaryKeyFKs) {
            return crc32(json_encode($primaryKeyFKs, JSON_UNESCAPED_UNICODE));
        }

        return spl_object_hash($this);
    }

    /**
     * Returns the primary key for this object (row).
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getUserJobMatchId();
    }

    /**
     * Generic method to set the primary key (user_job_match_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setUserJobMatchId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getUserJobMatchId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\DataAccess\UserJobMatch (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setUserId($this->getUserId());
        $copyObj->setJobPostingId($this->getJobPostingId());
        $copyObj->setIsJobMatch($this->getIsJobMatch());
        $copyObj->setGoodJobTitleKeywordMatches($this->getGoodJobTitleKeywordMatches());
        $copyObj->setIsExcluded($this->getIsExcluded());
        $copyObj->setOutOfUserArea($this->getOutOfUserArea());
        $copyObj->setBadJobTitleKeywordMatches($this->getBadJobTitleKeywordMatches());
        $copyObj->setBadCompanyNameKeywordMatches($this->getBadCompanyNameKeywordMatches());
        $copyObj->setUserNotificationState($this->getUserNotificationState());
        $copyObj->setUpdatedAt($this->getUpdatedAt());
        $copyObj->setFirstMatchedAt($this->getFirstMatchedAt());
        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setUserJobMatchId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param  boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return \JobScooper\DataAccess\UserJobMatch Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Declares an association between this object and a ChildUser object.
     *
     * @param  ChildUser $v
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUserFromUJM(ChildUser $v = null)
    {
        if ($v === null) {
            $this->setUserId(NULL);
        } else {
            $this->setUserId($v->getUserId());
        }

        $this->aUserFromUJM = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUser object, it will not be re-added.
        if ($v !== null) {
            $v->addUserJobMatch($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildUser object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildUser The associated ChildUser object.
     * @throws PropelException
     */
    public function getUserFromUJM(ConnectionInterface $con = null)
    {
        if ($this->aUserFromUJM === null && ($this->user_id != 0)) {
            $this->aUserFromUJM = ChildUserQuery::create()->findPk($this->user_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUserFromUJM->addUserJobMatches($this);
             */
        }

        return $this->aUserFromUJM;
    }

    /**
     * Declares an association between this object and a ChildJobPosting object.
     *
     * @param  ChildJobPosting $v
     * @return $this|\JobScooper\DataAccess\UserJobMatch The current object (for fluent API support)
     * @throws PropelException
     */
    public function setJobPostingFromUJM(ChildJobPosting $v = null)
    {
        if ($v === null) {
            $this->setJobPostingId(NULL);
        } else {
            $this->setJobPostingId($v->getJobPostingId());
        }

        $this->aJobPostingFromUJM = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildJobPosting object, it will not be re-added.
        if ($v !== null) {
            $v->addUserJobMatch($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildJobPosting object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildJobPosting The associated ChildJobPosting object.
     * @throws PropelException
     */
    public function getJobPostingFromUJM(ConnectionInterface $con = null)
    {
        if ($this->aJobPostingFromUJM === null && ($this->jobposting_id != 0)) {
            $this->aJobPostingFromUJM = ChildJobPostingQuery::create()->findPk($this->jobposting_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aJobPostingFromUJM->addUserJobMatches($this);
             */
        }

        return $this->aJobPostingFromUJM;
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aUserFromUJM) {
            $this->aUserFromUJM->removeUserJobMatch($this);
        }
        if (null !== $this->aJobPostingFromUJM) {
            $this->aJobPostingFromUJM->removeUserJobMatch($this);
        }
        $this->user_job_match_id = null;
        $this->user_id = null;
        $this->jobposting_id = null;
        $this->is_job_match = null;
        $this->good_job_title_keyword_matches = null;
        $this->is_excluded = null;
        $this->out_of_user_area = null;
        $this->bad_job_title_keyword_matches = null;
        $this->bad_company_name_keyword_matches = null;
        $this->user_notification_state = null;
        $this->last_updated_at = null;
        $this->first_matched_at = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references and back-references to other model objects or collections of model objects.
     *
     * This method is used to reset all php object references (not the actual reference in the database).
     * Necessary for object serialisation.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
        } // if ($deep)

        $this->aUserFromUJM = null;
        $this->aJobPostingFromUJM = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(UserJobMatchTableMap::DEFAULT_STRING_FORMAT);
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     $this|ChildUserJobMatch The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[UserJobMatchTableMap::COL_LAST_UPDATED_AT] = true;

        return $this;
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postSave')) {
            parent::postSave($con);
        }
    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preInsert')) {
            return parent::preInsert($con);
        }
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postInsert')) {
            parent::postInsert($con);
        }
    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preUpdate')) {
            return parent::preUpdate($con);
        }
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postUpdate')) {
            parent::postUpdate($con);
        }
    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preDelete')) {
            return parent::preDelete($con);
        }
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postDelete')) {
            parent::postDelete($con);
        }
    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
