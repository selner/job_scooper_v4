<?php

namespace JobScooper\DataAccess\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\DataAccess\JobSiteRecord as ChildJobSiteRecord;
use JobScooper\DataAccess\JobSiteRecordQuery as ChildJobSiteRecordQuery;
use JobScooper\DataAccess\UserSearch as ChildUserSearch;
use JobScooper\DataAccess\UserSearchQuery as ChildUserSearchQuery;
use JobScooper\DataAccess\UserSearchRun as ChildUserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\DataAccess\Map\JobSiteRecordTableMap;
use JobScooper\DataAccess\Map\UserSearchRunTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;

/**
 * Base class that represents a row from the 'user_search_run' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.DataAccess.Base
 */
abstract class UserSearchRun implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\DataAccess\\Map\\UserSearchRunTableMap';


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
     * The value for the user_search_run_id field.
     *
     * @var        int
     */
    protected $user_search_run_id;

    /**
     * The value for the user_search_id field.
     *
     * @var        int
     */
    protected $user_search_id;

    /**
     * The value for the app_run_id field.
     *
     * @var        string
     */
    protected $app_run_id;

    /**
     * The value for the user_search_run_key field.
     *
     * @var        string
     */
    protected $user_search_run_key;

    /**
     * The value for the jobsite_key field.
     *
     * @var        string
     */
    protected $jobsite_key;

    /**
     * The value for the search_start_url field.
     *
     * @var        string
     */
    protected $search_start_url;

    /**
     * The value for the run_result_code field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $run_result_code;

    /**
     * The value for the run_error_details field.
     *
     * @var        array
     */
    protected $run_error_details;

    /**
     * The unserialized $run_error_details value - i.e. the persisted object.
     * This is necessary to avoid repeated calls to unserialize() at runtime.
     * @var object
     */
    protected $run_error_details_unserialized;

    /**
     * The value for the date_started field.
     *
     * @var        DateTime
     */
    protected $date_started;

    /**
     * The value for the date_ended field.
     *
     * @var        DateTime
     */
    protected $date_ended;

    /**
     * @var        ChildUserSearch
     */
    protected $aUserSearch;

    /**
     * @var        ChildJobSiteRecord
     */
    protected $aJobSiteRecordRelatedByJobSiteKey;

    /**
     * @var        ObjectCollection|ChildJobSiteRecord[] Collection to store aggregation of ChildJobSiteRecord objects.
     */
    protected $collJobSiteRecordsRelatedByLastUserSearchRunId;
    protected $collJobSiteRecordsRelatedByLastUserSearchRunIdPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    // aggregate_column_relation_date_last_pulled behavior
    /**
     * @var ChildJobSiteRecord
     */
    protected $oldJobSiteRecordRelatedByJobSiteKeyLastPulledAt;

    // aggregate_column_relation_date_last_run behavior
    /**
     * @var ChildJobSiteRecord
     */
    protected $oldJobSiteRecordRelatedByJobSiteKeyLastRunAt;

    // aggregate_column_relation_date_last_completed behavior
    /**
     * @var ChildJobSiteRecord
     */
    protected $oldJobSiteRecordRelatedByJobSiteKeyLastCompletedAt;

    // aggregate_column_relation_date_last_failed behavior
    /**
     * @var ChildJobSiteRecord
     */
    protected $oldJobSiteRecordRelatedByJobSiteKeyLastFailedAt;

    // aggregate_column_relation_aggregate_column behavior
    /**
     * @var ChildJobSiteRecord
     */
    protected $oldJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId;

    // aggregate_column_relation_us_date_last_completed behavior
    /**
     * @var ChildUserSearch
     */
    protected $oldUserSearchLastCompletedAt;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildJobSiteRecord[]
     */
    protected $jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->run_result_code = 0;
    }

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\UserSearchRun object.
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
     * Compares this with another <code>UserSearchRun</code> instance.  If
     * <code>obj</code> is an instance of <code>UserSearchRun</code>, delegates to
     * <code>equals(UserSearchRun)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|UserSearchRun The current object, for fluid interface
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
     * Get the [user_search_run_id] column value.
     *
     * @return int
     */
    public function getUserSearchRunId()
    {
        return $this->user_search_run_id;
    }

    /**
     * Get the [user_search_id] column value.
     *
     * @return int
     */
    public function getUserSearchId()
    {
        return $this->user_search_id;
    }

    /**
     * Get the [app_run_id] column value.
     *
     * @return string
     */
    public function getAppRunId()
    {
        return $this->app_run_id;
    }

    /**
     * Get the [user_search_run_key] column value.
     *
     * @return string
     */
    public function getUserSearchRunKey()
    {
        return $this->user_search_run_key;
    }

    /**
     * Get the [jobsite_key] column value.
     *
     * @return string
     */
    public function getJobSiteKey()
    {
        return $this->jobsite_key;
    }

    /**
     * Get the [search_start_url] column value.
     *
     * @return string
     */
    public function getSearchStartUrl()
    {
        return $this->search_start_url;
    }

    /**
     * Get the [run_result_code] column value.
     *
     * @return string
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getRunResultCode()
    {
        if (null === $this->run_result_code) {
            return null;
        }
        $valueSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT_CODE);
        if (!isset($valueSet[$this->run_result_code])) {
            throw new PropelException('Unknown stored enum key: ' . $this->run_result_code);
        }

        return $valueSet[$this->run_result_code];
    }

    /**
     * Get the [run_error_details] column value.
     *
     * @return array
     */
    public function getRunErrorDetails()
    {
        if (null === $this->run_error_details_unserialized) {
            $this->run_error_details_unserialized = array();
        }
        if (!$this->run_error_details_unserialized && null !== $this->run_error_details) {
            $run_error_details_unserialized = substr($this->run_error_details, 2, -2);
            $this->run_error_details_unserialized = '' !== $run_error_details_unserialized ? explode(' | ', $run_error_details_unserialized) : array();
        }

        return $this->run_error_details_unserialized;
    }

    /**
     * Test the presence of a value in the [run_error_details] array column value.
     * @param      mixed $value
     *
     * @return boolean
     */
    public function hasRunErrorDetail($value)
    {
        return in_array($value, $this->getRunErrorDetails());
    } // hasRunErrorDetail()

    /**
     * Get the [optionally formatted] temporal [date_started] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getStartedAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_started;
        } else {
            return $this->date_started instanceof \DateTimeInterface ? $this->date_started->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [date_ended] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getEndedAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_ended;
        } else {
            return $this->date_ended instanceof \DateTimeInterface ? $this->date_ended->format($format) : null;
        }
    }

    /**
     * Set the value of [user_search_run_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setUserSearchRunId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_search_run_id !== $v) {
            $this->user_search_run_id = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID] = true;
        }

        return $this;
    } // setUserSearchRunId()

    /**
     * Set the value of [user_search_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setUserSearchId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_search_id !== $v) {
            $this->user_search_id = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_USER_SEARCH_ID] = true;
        }

        if ($this->aUserSearch !== null && $this->aUserSearch->getUserSearchId() !== $v) {
            $this->aUserSearch = null;
        }

        return $this;
    } // setUserSearchId()

    /**
     * Set the value of [app_run_id] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setAppRunId($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->app_run_id !== $v) {
            $this->app_run_id = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_APP_RUN_ID] = true;
        }

        return $this;
    } // setAppRunId()

    /**
     * Set the value of [user_search_run_key] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setUserSearchRunKey($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->user_search_run_key !== $v) {
            $this->user_search_run_key = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY] = true;
        }

        return $this;
    } // setUserSearchRunKey()

    /**
     * Set the value of [jobsite_key] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setJobSiteKey($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->jobsite_key !== $v) {
            $this->jobsite_key = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_JOBSITE_KEY] = true;
        }

        if ($this->aJobSiteRecordRelatedByJobSiteKey !== null && $this->aJobSiteRecordRelatedByJobSiteKey->getJobSiteKey() !== $v) {
            $this->aJobSiteRecordRelatedByJobSiteKey = null;
        }

        return $this;
    } // setJobSiteKey()

    /**
     * Set the value of [search_start_url] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setSearchStartUrl($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->search_start_url !== $v) {
            $this->search_start_url = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_SEARCH_START_URL] = true;
        }

        return $this;
    } // setSearchStartUrl()

    /**
     * Set the value of [run_result_code] column.
     *
     * @param  string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setRunResultCode($v)
    {
        if ($v !== null) {
            $valueSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT_CODE);
            if (!in_array($v, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $v));
            }
            $v = array_search($v, $valueSet);
        }

        if ($this->run_result_code !== $v) {
            $this->run_result_code = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_RUN_RESULT_CODE] = true;
        }

        return $this;
    } // setRunResultCode()

    /**
     * Set the value of [run_error_details] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setRunErrorDetails($v)
    {
        if ($this->run_error_details_unserialized !== $v) {
            $this->run_error_details_unserialized = $v;
            $this->run_error_details = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[UserSearchRunTableMap::COL_RUN_ERROR_DETAILS] = true;
        }

        return $this;
    } // setRunErrorDetails()

    /**
     * Adds a value to the [run_error_details] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function addRunErrorDetail($value)
    {
        $currentArray = $this->getRunErrorDetails();
        $currentArray []= $value;
        $this->setRunErrorDetails($currentArray);

        return $this;
    } // addRunErrorDetail()

    /**
     * Removes a value from the [run_error_details] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function removeRunErrorDetail($value)
    {
        $targetArray = array();
        foreach ($this->getRunErrorDetails() as $element) {
            if ($element != $value) {
                $targetArray []= $element;
            }
        }
        $this->setRunErrorDetails($targetArray);

        return $this;
    } // removeRunErrorDetail()

    /**
     * Sets the value of [date_started] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setStartedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_started !== null || $dt !== null) {
            if ($this->date_started === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_started->format("Y-m-d H:i:s.u")) {
                $this->date_started = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchRunTableMap::COL_DATE_STARTED] = true;
            }
        } // if either are not null

        return $this;
    } // setStartedAt()

    /**
     * Sets the value of [date_ended] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setEndedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_ended !== null || $dt !== null) {
            if ($this->date_ended === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_ended->format("Y-m-d H:i:s.u")) {
                $this->date_ended = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchRunTableMap::COL_DATE_ENDED] = true;
            }
        } // if either are not null

        return $this;
    } // setEndedAt()

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
            if ($this->run_result_code !== 0) {
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserSearchRunTableMap::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_run_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserSearchRunTableMap::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserSearchRunTableMap::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->app_run_id = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserSearchRunTableMap::translateFieldName('UserSearchRunKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_run_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : UserSearchRunTableMap::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobsite_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : UserSearchRunTableMap::translateFieldName('SearchStartUrl', TableMap::TYPE_PHPNAME, $indexType)];
            $this->search_start_url = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : UserSearchRunTableMap::translateFieldName('RunResultCode', TableMap::TYPE_PHPNAME, $indexType)];
            $this->run_result_code = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : UserSearchRunTableMap::translateFieldName('RunErrorDetails', TableMap::TYPE_PHPNAME, $indexType)];
            $this->run_error_details = $col;
            $this->run_error_details_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : UserSearchRunTableMap::translateFieldName('StartedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_started = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : UserSearchRunTableMap::translateFieldName('EndedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_ended = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 10; // 10 = UserSearchRunTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\DataAccess\\UserSearchRun'), 0, $e);
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
        if ($this->aUserSearch !== null && $this->user_search_id !== $this->aUserSearch->getUserSearchId()) {
            $this->aUserSearch = null;
        }
        if ($this->aJobSiteRecordRelatedByJobSiteKey !== null && $this->jobsite_key !== $this->aJobSiteRecordRelatedByJobSiteKey->getJobSiteKey()) {
            $this->aJobSiteRecordRelatedByJobSiteKey = null;
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
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchRunTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildUserSearchRunQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aUserSearch = null;
            $this->aJobSiteRecordRelatedByJobSiteKey = null;
            $this->collJobSiteRecordsRelatedByLastUserSearchRunId = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see UserSearchRun::setDeleted()
     * @see UserSearchRun::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildUserSearchRunQuery::create()
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
     * Since this table was configured to reload rows on update, the object will
     * be reloaded from the database if an UPDATE operation is performed (unless
     * the $skipReload parameter is TRUE).
     *
     * Since this table was configured to reload rows on insert, the object will
     * be reloaded from the database if an INSERT operation is performed (unless
     * the $skipReload parameter is TRUE).
     *
     * @param      ConnectionInterface $con
     * @param      boolean $skipReload Whether to skip the reload for this object from database.
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null, $skipReload = false)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($this->alreadyInSave) {
            return 0;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con, $skipReload) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            // sluggable behavior

            if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY) && $this->getUserSearchRunKey()) {
                $this->setUserSearchRunKey($this->makeSlugUnique($this->getUserSearchRunKey()));
            } else {
                $this->setUserSearchRunKey($this->createSlug());
            }
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con, $skipReload);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                // aggregate_column_relation_date_last_pulled behavior
                $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastPulledAt($con);
                // aggregate_column_relation_date_last_run behavior
                $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastRunAt($con);
                // aggregate_column_relation_date_last_completed behavior
                $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastCompletedAt($con);
                // aggregate_column_relation_date_last_failed behavior
                $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastFailedAt($con);
                // aggregate_column_relation_aggregate_column behavior
                $this->updateRelatedJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId($con);
                // aggregate_column_relation_us_date_last_completed behavior
                $this->updateRelatedUserSearchLastCompletedAt($con);
                UserSearchRunTableMap::addInstanceToPool($this);
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
     * @param      boolean $skipReload Whether to skip the reload for this object from database.
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con, $skipReload = false)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            $reloadObject = false;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aUserSearch !== null) {
                if ($this->aUserSearch->isModified() || $this->aUserSearch->isNew()) {
                    $affectedRows += $this->aUserSearch->save($con);
                }
                $this->setUserSearch($this->aUserSearch);
            }

            if ($this->aJobSiteRecordRelatedByJobSiteKey !== null) {
                if ($this->aJobSiteRecordRelatedByJobSiteKey->isModified() || $this->aJobSiteRecordRelatedByJobSiteKey->isNew()) {
                    $affectedRows += $this->aJobSiteRecordRelatedByJobSiteKey->save($con);
                }
                $this->setJobSiteRecordRelatedByJobSiteKey($this->aJobSiteRecordRelatedByJobSiteKey);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                    $affectedRows += 1;
                    if (!$skipReload) {
                        $reloadObject = true;
                    }
                } else {
                    $affectedRows += $this->doUpdate($con);
                    if (!$skipReload) {
                        $reloadObject = true;
                    }
                }
                $this->resetModified();
            }

            if ($this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion !== null) {
                if (!$this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion->isEmpty()) {
                    foreach ($this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion as $jobSiteRecordRelatedByLastUserSearchRunId) {
                        // need to save related object because we set the relation to null
                        $jobSiteRecordRelatedByLastUserSearchRunId->save($con);
                    }
                    $this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion = null;
                }
            }

            if ($this->collJobSiteRecordsRelatedByLastUserSearchRunId !== null) {
                foreach ($this->collJobSiteRecordsRelatedByLastUserSearchRunId as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

            if ($reloadObject) {
                $this->reload($con);
            }

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

        $this->modifiedColumns[UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID] = true;
        if (null !== $this->user_search_run_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_run_id';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_id';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_APP_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'app_run_id';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_run_key';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_JOBSITE_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'jobsite_key';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_SEARCH_START_URL)) {
            $modifiedColumns[':p' . $index++]  = 'search_start_url';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_RUN_RESULT_CODE)) {
            $modifiedColumns[':p' . $index++]  = 'run_result_code';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS)) {
            $modifiedColumns[':p' . $index++]  = 'run_error_details';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_STARTED)) {
            $modifiedColumns[':p' . $index++]  = 'date_started';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_ENDED)) {
            $modifiedColumns[':p' . $index++]  = 'date_ended';
        }

        $sql = sprintf(
            'INSERT INTO user_search_run (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'user_search_run_id':
                        $stmt->bindValue($identifier, $this->user_search_run_id, PDO::PARAM_INT);
                        break;
                    case 'user_search_id':
                        $stmt->bindValue($identifier, $this->user_search_id, PDO::PARAM_INT);
                        break;
                    case 'app_run_id':
                        $stmt->bindValue($identifier, $this->app_run_id, PDO::PARAM_STR);
                        break;
                    case 'user_search_run_key':
                        $stmt->bindValue($identifier, $this->user_search_run_key, PDO::PARAM_STR);
                        break;
                    case 'jobsite_key':
                        $stmt->bindValue($identifier, $this->jobsite_key, PDO::PARAM_STR);
                        break;
                    case 'search_start_url':
                        $stmt->bindValue($identifier, $this->search_start_url, PDO::PARAM_STR);
                        break;
                    case 'run_result_code':
                        $stmt->bindValue($identifier, $this->run_result_code, PDO::PARAM_INT);
                        break;
                    case 'run_error_details':
                        $stmt->bindValue($identifier, $this->run_error_details, PDO::PARAM_STR);
                        break;
                    case 'date_started':
                        $stmt->bindValue($identifier, $this->date_started ? $this->date_started->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_ended':
                        $stmt->bindValue($identifier, $this->date_ended ? $this->date_ended->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
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
        $this->setUserSearchRunId($pk);

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
        $pos = UserSearchRunTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getUserSearchRunId();
                break;
            case 1:
                return $this->getUserSearchId();
                break;
            case 2:
                return $this->getAppRunId();
                break;
            case 3:
                return $this->getUserSearchRunKey();
                break;
            case 4:
                return $this->getJobSiteKey();
                break;
            case 5:
                return $this->getSearchStartUrl();
                break;
            case 6:
                return $this->getRunResultCode();
                break;
            case 7:
                return $this->getRunErrorDetails();
                break;
            case 8:
                return $this->getStartedAt();
                break;
            case 9:
                return $this->getEndedAt();
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

        if (isset($alreadyDumpedObjects['UserSearchRun'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['UserSearchRun'][$this->hashCode()] = true;
        $keys = UserSearchRunTableMap::getFieldNames($keyType);
        $keys_user_search = \JobScooper\DataAccess\Map\UserSearchTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getUserSearchRunId(),
            $keys[1] => $this->getUserSearchId(),
            $keys[2] => $this->getAppRunId(),
            $keys[3] => $this->getUserSearchRunKey(),
            $keys[4] => $this->getJobSiteKey(),
            $keys[5] => $this->getSearchStartUrl(),
            $keys[6] => $this->getRunResultCode(),
            $keys[7] => $this->getRunErrorDetails(),
            $keys[8] => $this->getStartedAt(),
            $keys[9] => $this->getEndedAt(),
            $keys_user_search[1] => $this->getUserId(),
            $keys_user_search[2] => $this->getGeoLocationId(),
            $keys_user_search[3] => $this->getUserSearchKey(),
            $keys_user_search[4] => $this->getKeywords(),
            $keys_user_search[5] => $this->getKeywordTokens(),
            $keys_user_search[6] => $this->getSearchKeyFromConfig(),
            $keys_user_search[7] => $this->getCreatedAt(),
            $keys_user_search[8] => $this->getUpdatedAt(),
            $keys_user_search[9] => $this->getLastCompletedAt(),
            $keys_user_search[10] => $this->getVersion(),

        );
        if ($result[$keys[8]] instanceof \DateTimeInterface) {
            $result[$keys[8]] = $result[$keys[8]]->format('c');
        }

        if ($result[$keys[9]] instanceof \DateTimeInterface) {
            $result[$keys[9]] = $result[$keys[9]]->format('c');
        }

        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aUserSearch) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userSearch';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_search';
                        break;
                    default:
                        $key = 'UserSearch';
                }

                $result[$key] = $this->aUserSearch->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aJobSiteRecordRelatedByJobSiteKey) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'jobSiteRecord';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'job_site';
                        break;
                    default:
                        $key = 'JobSiteRecord';
                }

                $result[$key] = $this->aJobSiteRecordRelatedByJobSiteKey->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collJobSiteRecordsRelatedByLastUserSearchRunId) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'jobSiteRecords';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'job_sites';
                        break;
                    default:
                        $key = 'JobSiteRecords';
                }

                $result[$key] = $this->collJobSiteRecordsRelatedByLastUserSearchRunId->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\JobScooper\DataAccess\UserSearchRun
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserSearchRunTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\DataAccess\UserSearchRun
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setUserSearchRunId($value);
                break;
            case 1:
                $this->setUserSearchId($value);
                break;
            case 2:
                $this->setAppRunId($value);
                break;
            case 3:
                $this->setUserSearchRunKey($value);
                break;
            case 4:
                $this->setJobSiteKey($value);
                break;
            case 5:
                $this->setSearchStartUrl($value);
                break;
            case 6:
                $valueSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT_CODE);
                if (isset($valueSet[$value])) {
                    $value = $valueSet[$value];
                }
                $this->setRunResultCode($value);
                break;
            case 7:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setRunErrorDetails($value);
                break;
            case 8:
                $this->setStartedAt($value);
                break;
            case 9:
                $this->setEndedAt($value);
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
        $keys = UserSearchRunTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setUserSearchRunId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setUserSearchId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setAppRunId($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setUserSearchRunKey($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setJobSiteKey($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setSearchStartUrl($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setRunResultCode($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setRunErrorDetails($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setStartedAt($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setEndedAt($arr[$keys[9]]);
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
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object, for fluid interface
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
        $criteria = new Criteria(UserSearchRunTableMap::DATABASE_NAME);

        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID)) {
            $criteria->add(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $this->user_search_run_id);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_ID)) {
            $criteria->add(UserSearchRunTableMap::COL_USER_SEARCH_ID, $this->user_search_id);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_APP_RUN_ID)) {
            $criteria->add(UserSearchRunTableMap::COL_APP_RUN_ID, $this->app_run_id);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY)) {
            $criteria->add(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY, $this->user_search_run_key);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_JOBSITE_KEY)) {
            $criteria->add(UserSearchRunTableMap::COL_JOBSITE_KEY, $this->jobsite_key);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_SEARCH_START_URL)) {
            $criteria->add(UserSearchRunTableMap::COL_SEARCH_START_URL, $this->search_start_url);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_RUN_RESULT_CODE)) {
            $criteria->add(UserSearchRunTableMap::COL_RUN_RESULT_CODE, $this->run_result_code);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS)) {
            $criteria->add(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS, $this->run_error_details);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_STARTED)) {
            $criteria->add(UserSearchRunTableMap::COL_DATE_STARTED, $this->date_started);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_ENDED)) {
            $criteria->add(UserSearchRunTableMap::COL_DATE_ENDED, $this->date_ended);
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
        $criteria = ChildUserSearchRunQuery::create();
        $criteria->add(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID, $this->user_search_run_id);

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
        $validPk = null !== $this->getUserSearchRunId();

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
        return $this->getUserSearchRunId();
    }

    /**
     * Generic method to set the primary key (user_search_run_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setUserSearchRunId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getUserSearchRunId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\DataAccess\UserSearchRun (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setUserSearchId($this->getUserSearchId());
        $copyObj->setAppRunId($this->getAppRunId());
        $copyObj->setUserSearchRunKey($this->getUserSearchRunKey());
        $copyObj->setJobSiteKey($this->getJobSiteKey());
        $copyObj->setSearchStartUrl($this->getSearchStartUrl());
        $copyObj->setRunResultCode($this->getRunResultCode());
        $copyObj->setRunErrorDetails($this->getRunErrorDetails());
        $copyObj->setStartedAt($this->getStartedAt());
        $copyObj->setEndedAt($this->getEndedAt());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getJobSiteRecordsRelatedByLastUserSearchRunId() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addJobSiteRecordRelatedByLastUserSearchRunId($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setUserSearchRunId(NULL); // this is a auto-increment column, so set to default value
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
     * @return \JobScooper\DataAccess\UserSearchRun Clone of current object.
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
     * Declares an association between this object and a ChildUserSearch object.
     *
     * @param  ChildUserSearch $v
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUserSearch(ChildUserSearch $v = null)
    {
        // aggregate_column_relation behavior
        if (null !== $this->aUserSearch && $v !== $this->aUserSearch) {
            $this->oldUserSearchLastCompletedAt = $this->aUserSearch;
        }
        if ($v === null) {
            $this->setUserSearchId(NULL);
        } else {
            $this->setUserSearchId($v->getUserSearchId());
        }

        $this->aUserSearch = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUserSearch object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearchRun($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildUserSearch object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildUserSearch The associated ChildUserSearch object.
     * @throws PropelException
     */
    public function getUserSearch(ConnectionInterface $con = null)
    {
        if ($this->aUserSearch === null && ($this->user_search_id != 0)) {
            $this->aUserSearch = ChildUserSearchQuery::create()->findPk($this->user_search_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUserSearch->addUserSearchRuns($this);
             */
        }

        return $this->aUserSearch;
    }

    /**
     * Declares an association between this object and a ChildJobSiteRecord object.
     *
     * @param  ChildJobSiteRecord $v
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     * @throws PropelException
     */
    public function setJobSiteRecordRelatedByJobSiteKey(ChildJobSiteRecord $v = null)
    {
        // aggregate_column_relation behavior
        if (null !== $this->aJobSiteRecordRelatedByJobSiteKey && $v !== $this->aJobSiteRecordRelatedByJobSiteKey) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId = $this->aJobSiteRecordRelatedByJobSiteKey;
        }
        // aggregate_column_relation behavior
        if (null !== $this->aJobSiteRecordRelatedByJobSiteKey && $v !== $this->aJobSiteRecordRelatedByJobSiteKey) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastFailedAt = $this->aJobSiteRecordRelatedByJobSiteKey;
        }
        // aggregate_column_relation behavior
        if (null !== $this->aJobSiteRecordRelatedByJobSiteKey && $v !== $this->aJobSiteRecordRelatedByJobSiteKey) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastCompletedAt = $this->aJobSiteRecordRelatedByJobSiteKey;
        }
        // aggregate_column_relation behavior
        if (null !== $this->aJobSiteRecordRelatedByJobSiteKey && $v !== $this->aJobSiteRecordRelatedByJobSiteKey) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastRunAt = $this->aJobSiteRecordRelatedByJobSiteKey;
        }
        // aggregate_column_relation behavior
        if (null !== $this->aJobSiteRecordRelatedByJobSiteKey && $v !== $this->aJobSiteRecordRelatedByJobSiteKey) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastPulledAt = $this->aJobSiteRecordRelatedByJobSiteKey;
        }
        if ($v === null) {
            $this->setJobSiteKey(NULL);
        } else {
            $this->setJobSiteKey($v->getJobSiteKey());
        }

        $this->aJobSiteRecordRelatedByJobSiteKey = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildJobSiteRecord object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearchRunRelatedByJobSiteKey($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildJobSiteRecord object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildJobSiteRecord The associated ChildJobSiteRecord object.
     * @throws PropelException
     */
    public function getJobSiteRecordRelatedByJobSiteKey(ConnectionInterface $con = null)
    {
        if ($this->aJobSiteRecordRelatedByJobSiteKey === null && (($this->jobsite_key !== "" && $this->jobsite_key !== null))) {
            $this->aJobSiteRecordRelatedByJobSiteKey = ChildJobSiteRecordQuery::create()->findPk($this->jobsite_key, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aJobSiteRecordRelatedByJobSiteKey->addUserSearchRunsRelatedByJobSiteKey($this);
             */
        }

        return $this->aJobSiteRecordRelatedByJobSiteKey;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('JobSiteRecordRelatedByLastUserSearchRunId' == $relationName) {
            $this->initJobSiteRecordsRelatedByLastUserSearchRunId();
            return;
        }
    }

    /**
     * Clears out the collJobSiteRecordsRelatedByLastUserSearchRunId collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addJobSiteRecordsRelatedByLastUserSearchRunId()
     */
    public function clearJobSiteRecordsRelatedByLastUserSearchRunId()
    {
        $this->collJobSiteRecordsRelatedByLastUserSearchRunId = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collJobSiteRecordsRelatedByLastUserSearchRunId collection loaded partially.
     */
    public function resetPartialJobSiteRecordsRelatedByLastUserSearchRunId($v = true)
    {
        $this->collJobSiteRecordsRelatedByLastUserSearchRunIdPartial = $v;
    }

    /**
     * Initializes the collJobSiteRecordsRelatedByLastUserSearchRunId collection.
     *
     * By default this just sets the collJobSiteRecordsRelatedByLastUserSearchRunId collection to an empty array (like clearcollJobSiteRecordsRelatedByLastUserSearchRunId());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initJobSiteRecordsRelatedByLastUserSearchRunId($overrideExisting = true)
    {
        if (null !== $this->collJobSiteRecordsRelatedByLastUserSearchRunId && !$overrideExisting) {
            return;
        }

        $collectionClassName = JobSiteRecordTableMap::getTableMap()->getCollectionClassName();

        $this->collJobSiteRecordsRelatedByLastUserSearchRunId = new $collectionClassName;
        $this->collJobSiteRecordsRelatedByLastUserSearchRunId->setModel('\JobScooper\DataAccess\JobSiteRecord');
    }

    /**
     * Gets an array of ChildJobSiteRecord objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUserSearchRun is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildJobSiteRecord[] List of ChildJobSiteRecord objects
     * @throws PropelException
     */
    public function getJobSiteRecordsRelatedByLastUserSearchRunId(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collJobSiteRecordsRelatedByLastUserSearchRunIdPartial && !$this->isNew();
        if (null === $this->collJobSiteRecordsRelatedByLastUserSearchRunId || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collJobSiteRecordsRelatedByLastUserSearchRunId) {
                // return empty collection
                $this->initJobSiteRecordsRelatedByLastUserSearchRunId();
            } else {
                $collJobSiteRecordsRelatedByLastUserSearchRunId = ChildJobSiteRecordQuery::create(null, $criteria)
                    ->filterByUserSearchRunRelatedByLastUserSearchRunId($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collJobSiteRecordsRelatedByLastUserSearchRunIdPartial && count($collJobSiteRecordsRelatedByLastUserSearchRunId)) {
                        $this->initJobSiteRecordsRelatedByLastUserSearchRunId(false);

                        foreach ($collJobSiteRecordsRelatedByLastUserSearchRunId as $obj) {
                            if (false == $this->collJobSiteRecordsRelatedByLastUserSearchRunId->contains($obj)) {
                                $this->collJobSiteRecordsRelatedByLastUserSearchRunId->append($obj);
                            }
                        }

                        $this->collJobSiteRecordsRelatedByLastUserSearchRunIdPartial = true;
                    }

                    return $collJobSiteRecordsRelatedByLastUserSearchRunId;
                }

                if ($partial && $this->collJobSiteRecordsRelatedByLastUserSearchRunId) {
                    foreach ($this->collJobSiteRecordsRelatedByLastUserSearchRunId as $obj) {
                        if ($obj->isNew()) {
                            $collJobSiteRecordsRelatedByLastUserSearchRunId[] = $obj;
                        }
                    }
                }

                $this->collJobSiteRecordsRelatedByLastUserSearchRunId = $collJobSiteRecordsRelatedByLastUserSearchRunId;
                $this->collJobSiteRecordsRelatedByLastUserSearchRunIdPartial = false;
            }
        }

        return $this->collJobSiteRecordsRelatedByLastUserSearchRunId;
    }

    /**
     * Sets a collection of ChildJobSiteRecord objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $jobSiteRecordsRelatedByLastUserSearchRunId A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildUserSearchRun The current object (for fluent API support)
     */
    public function setJobSiteRecordsRelatedByLastUserSearchRunId(Collection $jobSiteRecordsRelatedByLastUserSearchRunId, ConnectionInterface $con = null)
    {
        /** @var ChildJobSiteRecord[] $jobSiteRecordsRelatedByLastUserSearchRunIdToDelete */
        $jobSiteRecordsRelatedByLastUserSearchRunIdToDelete = $this->getJobSiteRecordsRelatedByLastUserSearchRunId(new Criteria(), $con)->diff($jobSiteRecordsRelatedByLastUserSearchRunId);


        $this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion = $jobSiteRecordsRelatedByLastUserSearchRunIdToDelete;

        foreach ($jobSiteRecordsRelatedByLastUserSearchRunIdToDelete as $jobSiteRecordRelatedByLastUserSearchRunIdRemoved) {
            $jobSiteRecordRelatedByLastUserSearchRunIdRemoved->setUserSearchRunRelatedByLastUserSearchRunId(null);
        }

        $this->collJobSiteRecordsRelatedByLastUserSearchRunId = null;
        foreach ($jobSiteRecordsRelatedByLastUserSearchRunId as $jobSiteRecordRelatedByLastUserSearchRunId) {
            $this->addJobSiteRecordRelatedByLastUserSearchRunId($jobSiteRecordRelatedByLastUserSearchRunId);
        }

        $this->collJobSiteRecordsRelatedByLastUserSearchRunId = $jobSiteRecordsRelatedByLastUserSearchRunId;
        $this->collJobSiteRecordsRelatedByLastUserSearchRunIdPartial = false;

        return $this;
    }

    /**
     * Returns the number of related JobSiteRecord objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related JobSiteRecord objects.
     * @throws PropelException
     */
    public function countJobSiteRecordsRelatedByLastUserSearchRunId(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collJobSiteRecordsRelatedByLastUserSearchRunIdPartial && !$this->isNew();
        if (null === $this->collJobSiteRecordsRelatedByLastUserSearchRunId || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collJobSiteRecordsRelatedByLastUserSearchRunId) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getJobSiteRecordsRelatedByLastUserSearchRunId());
            }

            $query = ChildJobSiteRecordQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByUserSearchRunRelatedByLastUserSearchRunId($this)
                ->count($con);
        }

        return count($this->collJobSiteRecordsRelatedByLastUserSearchRunId);
    }

    /**
     * Method called to associate a ChildJobSiteRecord object to this object
     * through the ChildJobSiteRecord foreign key attribute.
     *
     * @param  ChildJobSiteRecord $l ChildJobSiteRecord
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function addJobSiteRecordRelatedByLastUserSearchRunId(ChildJobSiteRecord $l)
    {
        if ($this->collJobSiteRecordsRelatedByLastUserSearchRunId === null) {
            $this->initJobSiteRecordsRelatedByLastUserSearchRunId();
            $this->collJobSiteRecordsRelatedByLastUserSearchRunIdPartial = true;
        }

        if (!$this->collJobSiteRecordsRelatedByLastUserSearchRunId->contains($l)) {
            $this->doAddJobSiteRecordRelatedByLastUserSearchRunId($l);

            if ($this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion and $this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion->contains($l)) {
                $this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion->remove($this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildJobSiteRecord $jobSiteRecordRelatedByLastUserSearchRunId The ChildJobSiteRecord object to add.
     */
    protected function doAddJobSiteRecordRelatedByLastUserSearchRunId(ChildJobSiteRecord $jobSiteRecordRelatedByLastUserSearchRunId)
    {
        $this->collJobSiteRecordsRelatedByLastUserSearchRunId[]= $jobSiteRecordRelatedByLastUserSearchRunId;
        $jobSiteRecordRelatedByLastUserSearchRunId->setUserSearchRunRelatedByLastUserSearchRunId($this);
    }

    /**
     * @param  ChildJobSiteRecord $jobSiteRecordRelatedByLastUserSearchRunId The ChildJobSiteRecord object to remove.
     * @return $this|ChildUserSearchRun The current object (for fluent API support)
     */
    public function removeJobSiteRecordRelatedByLastUserSearchRunId(ChildJobSiteRecord $jobSiteRecordRelatedByLastUserSearchRunId)
    {
        if ($this->getJobSiteRecordsRelatedByLastUserSearchRunId()->contains($jobSiteRecordRelatedByLastUserSearchRunId)) {
            $pos = $this->collJobSiteRecordsRelatedByLastUserSearchRunId->search($jobSiteRecordRelatedByLastUserSearchRunId);
            $this->collJobSiteRecordsRelatedByLastUserSearchRunId->remove($pos);
            if (null === $this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion) {
                $this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion = clone $this->collJobSiteRecordsRelatedByLastUserSearchRunId;
                $this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion->clear();
            }
            $this->jobSiteRecordsRelatedByLastUserSearchRunIdScheduledForDeletion[]= $jobSiteRecordRelatedByLastUserSearchRunId;
            $jobSiteRecordRelatedByLastUserSearchRunId->setUserSearchRunRelatedByLastUserSearchRunId(null);
        }

        return $this;
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aUserSearch) {
            $this->aUserSearch->removeUserSearchRun($this);
        }
        if (null !== $this->aJobSiteRecordRelatedByJobSiteKey) {
            $this->aJobSiteRecordRelatedByJobSiteKey->removeUserSearchRunRelatedByJobSiteKey($this);
        }
        $this->user_search_run_id = null;
        $this->user_search_id = null;
        $this->app_run_id = null;
        $this->user_search_run_key = null;
        $this->jobsite_key = null;
        $this->search_start_url = null;
        $this->run_result_code = null;
        $this->run_error_details = null;
        $this->run_error_details_unserialized = null;
        $this->date_started = null;
        $this->date_ended = null;
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
            if ($this->collJobSiteRecordsRelatedByLastUserSearchRunId) {
                foreach ($this->collJobSiteRecordsRelatedByLastUserSearchRunId as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collJobSiteRecordsRelatedByLastUserSearchRunId = null;
        $this->aUserSearch = null;
        $this->aJobSiteRecordRelatedByJobSiteKey = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(UserSearchRunTableMap::DEFAULT_STRING_FORMAT);
    }

    // sluggable behavior

    /**
     * Wrap the setter for slug value
     *
     * @param   string
     * @return  $this|UserSearchRun
     */
    public function setSlug($v)
    {
        return $this->setUserSearchRunKey($v);
    }

    /**
     * Wrap the getter for slug value
     *
     * @return  string
     */
    public function getSlug()
    {
        return $this->getUserSearchRunKey();
    }

    /**
     * Create a unique slug based on the object
     *
     * @return string The object slug
     */
    protected function createSlug()
    {
        $slug = $this->createRawSlug();
        $slug = $this->limitSlugSize($slug);
        $slug = $this->makeSlugUnique($slug);

        return $slug;
    }

    /**
     * Create the slug from the appropriate columns
     *
     * @return string
     */
    protected function createRawSlug()
    {
        return '' . $this->cleanupSlugPart($this->getUserSearchKey()) . '_' . $this->cleanupSlugPart($this->getJobSiteKey()) . '_' . $this->cleanupSlugPart($this->getAppRunId()) . '';
    }

    /**
     * Cleanup a string to make a slug of it
     * Removes special characters, replaces blanks with a separator, and trim it
     *
     * @param     string $slug        the text to slugify
     * @param     string $replacement the separator used by slug
     * @return    string               the slugified text
     */
    protected static function cleanupSlugPart($slug, $replacement = '')
    {
        // transliterate
        if (function_exists('iconv')) {
            $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
        }

        // lowercase
        if (function_exists('mb_strtolower')) {
            $slug = mb_strtolower($slug);
        } else {
            $slug = strtolower($slug);
        }

        // remove accents resulting from OSX's iconv
        $slug = str_replace(array('\'', '`', '^'), '', $slug);

        // replace non letter or digits with separator
        $slug = preg_replace('/[^\w\/]+/u', $replacement, $slug);

        // trim
        $slug = trim($slug, $replacement);

        if (empty($slug)) {
            return 'n-a';
        }

        return $slug;
    }


    /**
     * Make sure the slug is short enough to accommodate the column size
     *
     * @param    string $slug            the slug to check
     *
     * @return string                        the truncated slug
     */
    protected static function limitSlugSize($slug, $incrementReservedSpace = 3)
    {
        // check length, as suffix could put it over maximum
        if (strlen($slug) > (100 - $incrementReservedSpace)) {
            $slug = substr($slug, 0, 100 - $incrementReservedSpace);
        }

        return $slug;
    }


    /**
     * Get the slug, ensuring its uniqueness
     *
     * @param    string $slug            the slug to check
     * @param    string $separator       the separator used by slug
     * @param    int    $alreadyExists   false for the first try, true for the second, and take the high count + 1
     * @return   string                   the unique slug
     */
    protected function makeSlugUnique($slug, $separator = '-', $alreadyExists = false)
    {
        if (!$alreadyExists) {
            $slug2 = $slug;
        } else {
            $slug2 = $slug . $separator;
        }

        $adapter = \Propel\Runtime\Propel::getServiceContainer()->getAdapter('default');
        $col = 'q.UserSearchRunKey';
        $compare = $alreadyExists ? $adapter->compareRegex($col, '?') : sprintf('%s = ?', $col);

        $query = \JobScooper\DataAccess\UserSearchRunQuery::create('q')
            ->where($compare, $alreadyExists ? '^' . $slug2 . '[0-9]+$' : $slug2)
            ->prune($this)
        ;

        if (!$alreadyExists) {
            $count = $query->count();
            if ($count > 0) {
                return $this->makeSlugUnique($slug, $separator, true);
            }

            return $slug2;
        }

        $adapter = \Propel\Runtime\Propel::getServiceContainer()->getAdapter('default');
        // Already exists
        $object = $query
            ->addDescendingOrderByColumn($adapter->strLength('user_search_run_key'))
            ->addDescendingOrderByColumn('user_search_run_key')
        ->findOne();

        // First duplicate slug
        if (null == $object) {
            return $slug2 . '1';
        }

        $slugNum = substr($object->getUserSearchRunKey(), strlen($slug) + 1);
        if (0 == $slugNum[0]) {
            $slugNum[0] = 1;
        }

        return $slug2 . ($slugNum + 1);
    }

    // aggregate_column_relation_date_last_pulled behavior

    /**
     * Update the aggregate column in the related JobSiteRecordRelatedByJobSiteKey object
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastPulledAt(ConnectionInterface $con)
    {
        if ($jobSiteRecordRelatedByJobSiteKey = $this->getJobSiteRecordRelatedByJobSiteKey()) {
            $jobSiteRecordRelatedByJobSiteKey->updateLastPulledAt($con);
        }
        if ($this->oldJobSiteRecordRelatedByJobSiteKeyLastPulledAt) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastPulledAt->updateLastPulledAt($con);
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastPulledAt = null;
        }
    }

    // aggregate_column_relation_date_last_run behavior

    /**
     * Update the aggregate column in the related JobSiteRecordRelatedByJobSiteKey object
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastRunAt(ConnectionInterface $con)
    {
        if ($jobSiteRecordRelatedByJobSiteKey = $this->getJobSiteRecordRelatedByJobSiteKey()) {
            $jobSiteRecordRelatedByJobSiteKey->updateLastRunAt($con);
        }
        if ($this->oldJobSiteRecordRelatedByJobSiteKeyLastRunAt) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastRunAt->updateLastRunAt($con);
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastRunAt = null;
        }
    }

    // aggregate_column_relation_date_last_completed behavior

    /**
     * Update the aggregate column in the related JobSiteRecordRelatedByJobSiteKey object
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastCompletedAt(ConnectionInterface $con)
    {
        if ($jobSiteRecordRelatedByJobSiteKey = $this->getJobSiteRecordRelatedByJobSiteKey()) {
            $jobSiteRecordRelatedByJobSiteKey->updateLastCompletedAt($con);
        }
        if ($this->oldJobSiteRecordRelatedByJobSiteKeyLastCompletedAt) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastCompletedAt->updateLastCompletedAt($con);
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastCompletedAt = null;
        }
    }

    // aggregate_column_relation_date_last_failed behavior

    /**
     * Update the aggregate column in the related JobSiteRecordRelatedByJobSiteKey object
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastFailedAt(ConnectionInterface $con)
    {
        if ($jobSiteRecordRelatedByJobSiteKey = $this->getJobSiteRecordRelatedByJobSiteKey()) {
            $jobSiteRecordRelatedByJobSiteKey->updateLastFailedAt($con);
        }
        if ($this->oldJobSiteRecordRelatedByJobSiteKeyLastFailedAt) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastFailedAt->updateLastFailedAt($con);
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastFailedAt = null;
        }
    }

    // aggregate_column_relation_aggregate_column behavior

    /**
     * Update the aggregate column in the related JobSiteRecordRelatedByJobSiteKey object
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function updateRelatedJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId(ConnectionInterface $con)
    {
        if ($jobSiteRecordRelatedByJobSiteKey = $this->getJobSiteRecordRelatedByJobSiteKey()) {
            $jobSiteRecordRelatedByJobSiteKey->updateLastUserSearchRunId($con);
        }
        if ($this->oldJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId) {
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId->updateLastUserSearchRunId($con);
            $this->oldJobSiteRecordRelatedByJobSiteKeyLastUserSearchRunId = null;
        }
    }

    // aggregate_column_relation_us_date_last_completed behavior

    /**
     * Update the aggregate column in the related UserSearch object
     *
     * @param ConnectionInterface $con A connection object
     */
    protected function updateRelatedUserSearchLastCompletedAt(ConnectionInterface $con)
    {
        if ($userSearch = $this->getUserSearch()) {
            $userSearch->updateLastCompletedAt($con);
        }
        if ($this->oldUserSearchLastCompletedAt) {
            $this->oldUserSearchLastCompletedAt->updateLastCompletedAt($con);
            $this->oldUserSearchLastCompletedAt = null;
        }
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
     * Catches calls to undefined methods.
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

    // delegate behavior

    if (is_callable(array('\JobScooper\DataAccess\UserSearch', $name))) {
        $delegate = $this->getUserSearch();
        if (!$delegate) {
            $delegate = new ChildUserSearch();
            $this->setUserSearch($delegate);
        }

        return call_user_func_array(array($delegate, $name), $params);
    }
        return $this->__parentCall($name, $params);
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
    public function __parentCall($name, $params)
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
