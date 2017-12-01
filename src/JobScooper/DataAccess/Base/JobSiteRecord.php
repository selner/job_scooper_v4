<?php

namespace JobScooper\DataAccess\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\DataAccess\JobPosting as ChildJobPosting;
use JobScooper\DataAccess\JobPostingQuery as ChildJobPostingQuery;
use JobScooper\DataAccess\JobSiteRecord as ChildJobSiteRecord;
use JobScooper\DataAccess\JobSiteRecordQuery as ChildJobSiteRecordQuery;
use JobScooper\DataAccess\JobSiteRecordVersion as ChildJobSiteRecordVersion;
use JobScooper\DataAccess\JobSiteRecordVersionQuery as ChildJobSiteRecordVersionQuery;
use JobScooper\DataAccess\UserSearchRun as ChildUserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\DataAccess\Map\JobPostingTableMap;
use JobScooper\DataAccess\Map\JobSiteRecordTableMap;
use JobScooper\DataAccess\Map\JobSiteRecordVersionTableMap;
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
 * Base class that represents a row from the 'job_site' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.DataAccess.Base
 */
abstract class JobSiteRecord implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\DataAccess\\Map\\JobSiteRecordTableMap';


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
     * The value for the jobsite_key field.
     *
     * @var        string
     */
    protected $jobsite_key;

    /**
     * The value for the plugin_class_name field.
     *
     * @var        string
     */
    protected $plugin_class_name;

    /**
     * The value for the display_name field.
     *
     * @var        string
     */
    protected $display_name;

    /**
     * The value for the is_disabled field.
     *
     * Note: this column has a database default value of: false
     * @var        boolean
     */
    protected $is_disabled;

    /**
     * The value for the date_last_pulled field.
     *
     * @var        DateTime
     */
    protected $date_last_pulled;

    /**
     * The value for the date_last_run field.
     *
     * @var        DateTime
     */
    protected $date_last_run;

    /**
     * The value for the date_last_completed field.
     *
     * @var        DateTime
     */
    protected $date_last_completed;

    /**
     * The value for the date_last_failed field.
     *
     * @var        DateTime
     */
    protected $date_last_failed;

    /**
     * The value for the last_user_search_run_id field.
     *
     * @var        int
     */
    protected $last_user_search_run_id;

    /**
     * Whether the lazy-loaded $last_user_search_run_id value has been loaded from database.
     * This is necessary to avoid repeated lookups if $last_user_search_run_id column is NULL in the db.
     * @var boolean
     */
    protected $last_user_search_run_id_isLoaded = false;

    /**
     * The value for the supported_country_codes field.
     *
     * @var        array
     */
    protected $supported_country_codes;

    /**
     * The unserialized $supported_country_codes value - i.e. the persisted object.
     * This is necessary to avoid repeated calls to unserialize() at runtime.
     * @var object
     */
    protected $supported_country_codes_unserialized;

    /**
     * The value for the results_filter_type field.
     *
     * @var        int
     */
    protected $results_filter_type;

    /**
     * The value for the version field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $version;

    /**
     * @var        ChildUserSearchRun
     */
    protected $aUserSearchRunRelatedByLastUserSearchRunId;

    /**
     * @var        ObjectCollection|ChildJobPosting[] Collection to store aggregation of ChildJobPosting objects.
     */
    protected $collJobPostings;
    protected $collJobPostingsPartial;

    /**
     * @var        ObjectCollection|ChildUserSearchRun[] Collection to store aggregation of ChildUserSearchRun objects.
     */
    protected $collUserSearchRunsRelatedByJobSiteKey;
    protected $collUserSearchRunsRelatedByJobSiteKeyPartial;

    /**
     * @var        ObjectCollection|ChildJobSiteRecordVersion[] Collection to store aggregation of ChildJobSiteRecordVersion objects.
     */
    protected $collJobSiteRecordVersions;
    protected $collJobSiteRecordVersionsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    // versionable behavior


    /**
     * @var bool
     */
    protected $enforceVersion = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildJobPosting[]
     */
    protected $jobPostingsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserSearchRun[]
     */
    protected $userSearchRunsRelatedByJobSiteKeyScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildJobSiteRecordVersion[]
     */
    protected $jobSiteRecordVersionsScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->is_disabled = false;
        $this->version = 0;
    }

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\JobSiteRecord object.
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
     * Compares this with another <code>JobSiteRecord</code> instance.  If
     * <code>obj</code> is an instance of <code>JobSiteRecord</code>, delegates to
     * <code>equals(JobSiteRecord)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|JobSiteRecord The current object, for fluid interface
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
     * Get the [jobsite_key] column value.
     *
     * @return string
     */
    public function getJobSiteKey()
    {
        return $this->jobsite_key;
    }

    /**
     * Get the [plugin_class_name] column value.
     *
     * @return string
     */
    public function getPluginClassName()
    {
        return $this->plugin_class_name;
    }

    /**
     * Get the [display_name] column value.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->display_name;
    }

    /**
     * Get the [is_disabled] column value.
     *
     * @return boolean
     */
    public function getisDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * Get the [is_disabled] column value.
     *
     * @return boolean
     */
    public function isDisabled()
    {
        return $this->getisDisabled();
    }

    /**
     * Get the [optionally formatted] temporal [date_last_pulled] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getLastPulledAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_last_pulled;
        } else {
            return $this->date_last_pulled instanceof \DateTimeInterface ? $this->date_last_pulled->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [date_last_run] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getLastRunAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_last_run;
        } else {
            return $this->date_last_run instanceof \DateTimeInterface ? $this->date_last_run->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [date_last_completed] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getLastCompletedAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_last_completed;
        } else {
            return $this->date_last_completed instanceof \DateTimeInterface ? $this->date_last_completed->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [date_last_failed] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getLastFailedAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_last_failed;
        } else {
            return $this->date_last_failed instanceof \DateTimeInterface ? $this->date_last_failed->format($format) : null;
        }
    }

    /**
     * Get the [last_user_search_run_id] column value.
     *
     * @param      ConnectionInterface $con An optional ConnectionInterface connection to use for fetching this lazy-loaded column.
     * @return int
     */
    public function getLastUserSearchRunId(ConnectionInterface $con = null)
    {
        if (!$this->last_user_search_run_id_isLoaded && $this->last_user_search_run_id === null && !$this->isNew()) {
            $this->loadLastUserSearchRunId($con);
        }

        return $this->last_user_search_run_id;
    }

    /**
     * Load the value for the lazy-loaded [last_user_search_run_id] column.
     *
     * This method performs an additional query to return the value for
     * the [last_user_search_run_id] column, since it is not populated by
     * the hydrate() method.
     *
     * @param      $con ConnectionInterface (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - any underlying error will be wrapped and re-thrown.
     */
    protected function loadLastUserSearchRunId(ConnectionInterface $con = null)
    {
        $c = $this->buildPkeyCriteria();
        $c->addSelectColumn(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID);
        try {
            $dataFetcher = ChildJobSiteRecordQuery::create(null, $c)->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
            $row = $dataFetcher->fetch();
            $dataFetcher->close();

        $firstColumn = $row ? current($row) : null;

            $this->last_user_search_run_id = ($firstColumn !== null) ? (int) $firstColumn : null;
            $this->last_user_search_run_id_isLoaded = true;
        } catch (Exception $e) {
            throw new PropelException("Error loading value for [last_user_search_run_id] column on demand.", 0, $e);
        }
    }
    /**
     * Get the [supported_country_codes] column value.
     *
     * @return array
     */
    public function getSupportedCountryCodes()
    {
        if (null === $this->supported_country_codes_unserialized) {
            $this->supported_country_codes_unserialized = array();
        }
        if (!$this->supported_country_codes_unserialized && null !== $this->supported_country_codes) {
            $supported_country_codes_unserialized = substr($this->supported_country_codes, 2, -2);
            $this->supported_country_codes_unserialized = '' !== $supported_country_codes_unserialized ? explode(' | ', $supported_country_codes_unserialized) : array();
        }

        return $this->supported_country_codes_unserialized;
    }

    /**
     * Test the presence of a value in the [supported_country_codes] array column value.
     * @param      mixed $value
     *
     * @return boolean
     */
    public function hasSupportedCountryCode($value)
    {
        return in_array($value, $this->getSupportedCountryCodes());
    } // hasSupportedCountryCode()

    /**
     * Get the [results_filter_type] column value.
     *
     * @return string
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getResultsFilterType()
    {
        if (null === $this->results_filter_type) {
            return null;
        }
        $valueSet = JobSiteRecordTableMap::getValueSet(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE);
        if (!isset($valueSet[$this->results_filter_type])) {
            throw new PropelException('Unknown stored enum key: ' . $this->results_filter_type);
        }

        return $valueSet[$this->results_filter_type];
    }

    /**
     * Get the [version] column value.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the value of [jobsite_key] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setJobSiteKey($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->jobsite_key !== $v) {
            $this->jobsite_key = $v;
            $this->modifiedColumns[JobSiteRecordTableMap::COL_JOBSITE_KEY] = true;
        }

        return $this;
    } // setJobSiteKey()

    /**
     * Set the value of [plugin_class_name] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setPluginClassName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->plugin_class_name !== $v) {
            $this->plugin_class_name = $v;
            $this->modifiedColumns[JobSiteRecordTableMap::COL_PLUGIN_CLASS_NAME] = true;
        }

        return $this;
    } // setPluginClassName()

    /**
     * Set the value of [display_name] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setDisplayName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->display_name !== $v) {
            $this->display_name = $v;
            $this->modifiedColumns[JobSiteRecordTableMap::COL_DISPLAY_NAME] = true;
        }

        return $this;
    } // setDisplayName()

    /**
     * Sets the value of the [is_disabled] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param  boolean|integer|string $v The new value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setisDisabled($v)
    {
        if ($v !== null) {
            if (is_string($v)) {
                $v = in_array(strtolower($v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                $v = (boolean) $v;
            }
        }

        if ($this->is_disabled !== $v) {
            $this->is_disabled = $v;
            $this->modifiedColumns[JobSiteRecordTableMap::COL_IS_DISABLED] = true;
        }

        return $this;
    } // setisDisabled()

    /**
     * Sets the value of [date_last_pulled] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setLastPulledAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_pulled !== null || $dt !== null) {
            if ($this->date_last_pulled === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_pulled->format("Y-m-d H:i:s.u")) {
                $this->date_last_pulled = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSiteRecordTableMap::COL_DATE_LAST_PULLED] = true;
            }
        } // if either are not null

        return $this;
    } // setLastPulledAt()

    /**
     * Sets the value of [date_last_run] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setLastRunAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_run !== null || $dt !== null) {
            if ($this->date_last_run === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_run->format("Y-m-d H:i:s.u")) {
                $this->date_last_run = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSiteRecordTableMap::COL_DATE_LAST_RUN] = true;
            }
        } // if either are not null

        return $this;
    } // setLastRunAt()

    /**
     * Sets the value of [date_last_completed] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setLastCompletedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_completed !== null || $dt !== null) {
            if ($this->date_last_completed === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_completed->format("Y-m-d H:i:s.u")) {
                $this->date_last_completed = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSiteRecordTableMap::COL_DATE_LAST_COMPLETED] = true;
            }
        } // if either are not null

        return $this;
    } // setLastCompletedAt()

    /**
     * Sets the value of [date_last_failed] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setLastFailedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_failed !== null || $dt !== null) {
            if ($this->date_last_failed === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_failed->format("Y-m-d H:i:s.u")) {
                $this->date_last_failed = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSiteRecordTableMap::COL_DATE_LAST_FAILED] = true;
            }
        } // if either are not null

        return $this;
    } // setLastFailedAt()

    /**
     * Set the value of [last_user_search_run_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setLastUserSearchRunId($v)
    {
        // explicitly set the is-loaded flag to true for this lazy load col;
        // it doesn't matter if the value is actually set or not (logic below) as
        // any attempt to set the value means that no db lookup should be performed
        // when the getLastUserSearchRunId() method is called.
        $this->last_user_search_run_id_isLoaded = true;

        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->last_user_search_run_id !== $v) {
            $this->last_user_search_run_id = $v;
            $this->modifiedColumns[JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID] = true;
        }

        if ($this->aUserSearchRunRelatedByLastUserSearchRunId !== null && $this->aUserSearchRunRelatedByLastUserSearchRunId->getUserSearchRunId() !== $v) {
            $this->aUserSearchRunRelatedByLastUserSearchRunId = null;
        }

        return $this;
    } // setLastUserSearchRunId()

    /**
     * Set the value of [supported_country_codes] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setSupportedCountryCodes($v)
    {
        if ($this->supported_country_codes_unserialized !== $v) {
            $this->supported_country_codes_unserialized = $v;
            $this->supported_country_codes = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[JobSiteRecordTableMap::COL_SUPPORTED_COUNTRY_CODES] = true;
        }

        return $this;
    } // setSupportedCountryCodes()

    /**
     * Adds a value to the [supported_country_codes] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function addSupportedCountryCode($value)
    {
        $currentArray = $this->getSupportedCountryCodes();
        $currentArray []= $value;
        $this->setSupportedCountryCodes($currentArray);

        return $this;
    } // addSupportedCountryCode()

    /**
     * Removes a value from the [supported_country_codes] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function removeSupportedCountryCode($value)
    {
        $targetArray = array();
        foreach ($this->getSupportedCountryCodes() as $element) {
            if ($element != $value) {
                $targetArray []= $element;
            }
        }
        $this->setSupportedCountryCodes($targetArray);

        return $this;
    } // removeSupportedCountryCode()

    /**
     * Set the value of [results_filter_type] column.
     *
     * @param  string $v new value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setResultsFilterType($v)
    {
        if ($v !== null) {
            $valueSet = JobSiteRecordTableMap::getValueSet(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE);
            if (!in_array($v, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $v));
            }
            $v = array_search($v, $valueSet);
        }

        if ($this->results_filter_type !== $v) {
            $this->results_filter_type = $v;
            $this->modifiedColumns[JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE] = true;
        }

        return $this;
    } // setResultsFilterType()

    /**
     * Set the value of [version] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function setVersion($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->version !== $v) {
            $this->version = $v;
            $this->modifiedColumns[JobSiteRecordTableMap::COL_VERSION] = true;
        }

        return $this;
    } // setVersion()

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
            if ($this->is_disabled !== false) {
                return false;
            }

            if ($this->version !== 0) {
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : JobSiteRecordTableMap::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobsite_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : JobSiteRecordTableMap::translateFieldName('PluginClassName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->plugin_class_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : JobSiteRecordTableMap::translateFieldName('DisplayName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->display_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : JobSiteRecordTableMap::translateFieldName('isDisabled', TableMap::TYPE_PHPNAME, $indexType)];
            $this->is_disabled = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : JobSiteRecordTableMap::translateFieldName('LastPulledAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_pulled = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : JobSiteRecordTableMap::translateFieldName('LastRunAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_run = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : JobSiteRecordTableMap::translateFieldName('LastCompletedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_completed = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : JobSiteRecordTableMap::translateFieldName('LastFailedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_failed = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : JobSiteRecordTableMap::translateFieldName('SupportedCountryCodes', TableMap::TYPE_PHPNAME, $indexType)];
            $this->supported_country_codes = $col;
            $this->supported_country_codes_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : JobSiteRecordTableMap::translateFieldName('ResultsFilterType', TableMap::TYPE_PHPNAME, $indexType)];
            $this->results_filter_type = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : JobSiteRecordTableMap::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)];
            $this->version = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 11; // 11 = JobSiteRecordTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\DataAccess\\JobSiteRecord'), 0, $e);
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
        if ($this->aUserSearchRunRelatedByLastUserSearchRunId !== null && $this->last_user_search_run_id !== $this->aUserSearchRunRelatedByLastUserSearchRunId->getUserSearchRunId()) {
            $this->aUserSearchRunRelatedByLastUserSearchRunId = null;
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
            $con = Propel::getServiceContainer()->getReadConnection(JobSiteRecordTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildJobSiteRecordQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        // Reset the last_user_search_run_id lazy-load column
        $this->last_user_search_run_id = null;
        $this->last_user_search_run_id_isLoaded = false;

        if ($deep) {  // also de-associate any related objects?

            $this->aUserSearchRunRelatedByLastUserSearchRunId = null;
            $this->collJobPostings = null;

            $this->collUserSearchRunsRelatedByJobSiteKey = null;

            $this->collJobSiteRecordVersions = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see JobSiteRecord::setDeleted()
     * @see JobSiteRecord::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSiteRecordTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildJobSiteRecordQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSiteRecordTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con, $skipReload) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            // versionable behavior
            if ($this->isVersioningNecessary()) {
                $this->setVersion($this->isNew() ? 1 : $this->getLastVersionNumber($con) + 1);
                $createVersion = true; // for postSave hook
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
                // versionable behavior
                if (isset($createVersion)) {
                    $this->addVersion($con);
                }
                JobSiteRecordTableMap::addInstanceToPool($this);
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

            if ($this->aUserSearchRunRelatedByLastUserSearchRunId !== null) {
                if ($this->aUserSearchRunRelatedByLastUserSearchRunId->isModified() || $this->aUserSearchRunRelatedByLastUserSearchRunId->isNew()) {
                    $affectedRows += $this->aUserSearchRunRelatedByLastUserSearchRunId->save($con);
                }
                $this->setUserSearchRunRelatedByLastUserSearchRunId($this->aUserSearchRunRelatedByLastUserSearchRunId);
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

            if ($this->jobPostingsScheduledForDeletion !== null) {
                if (!$this->jobPostingsScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\JobPostingQuery::create()
                        ->filterByPrimaryKeys($this->jobPostingsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->jobPostingsScheduledForDeletion = null;
                }
            }

            if ($this->collJobPostings !== null) {
                foreach ($this->collJobPostings as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion !== null) {
                if (!$this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\UserSearchRunQuery::create()
                        ->filterByPrimaryKeys($this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion = null;
                }
            }

            if ($this->collUserSearchRunsRelatedByJobSiteKey !== null) {
                foreach ($this->collUserSearchRunsRelatedByJobSiteKey as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->jobSiteRecordVersionsScheduledForDeletion !== null) {
                if (!$this->jobSiteRecordVersionsScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\JobSiteRecordVersionQuery::create()
                        ->filterByPrimaryKeys($this->jobSiteRecordVersionsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->jobSiteRecordVersionsScheduledForDeletion = null;
                }
            }

            if ($this->collJobSiteRecordVersions !== null) {
                foreach ($this->collJobSiteRecordVersions as $referrerFK) {
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


         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_JOBSITE_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'jobsite_key';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_PLUGIN_CLASS_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'plugin_class_name';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DISPLAY_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'display_name';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_IS_DISABLED)) {
            $modifiedColumns[':p' . $index++]  = 'is_disabled';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DATE_LAST_PULLED)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_pulled';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DATE_LAST_RUN)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_run';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DATE_LAST_COMPLETED)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_completed';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DATE_LAST_FAILED)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_failed';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'last_user_search_run_id';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_SUPPORTED_COUNTRY_CODES)) {
            $modifiedColumns[':p' . $index++]  = 'supported_country_codes';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE)) {
            $modifiedColumns[':p' . $index++]  = 'results_filter_type';
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_VERSION)) {
            $modifiedColumns[':p' . $index++]  = 'version';
        }

        $sql = sprintf(
            'INSERT INTO job_site (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'jobsite_key':
                        $stmt->bindValue($identifier, $this->jobsite_key, PDO::PARAM_STR);
                        break;
                    case 'plugin_class_name':
                        $stmt->bindValue($identifier, $this->plugin_class_name, PDO::PARAM_STR);
                        break;
                    case 'display_name':
                        $stmt->bindValue($identifier, $this->display_name, PDO::PARAM_STR);
                        break;
                    case 'is_disabled':
                        $stmt->bindValue($identifier, $this->is_disabled, PDO::PARAM_BOOL);
                        break;
                    case 'date_last_pulled':
                        $stmt->bindValue($identifier, $this->date_last_pulled ? $this->date_last_pulled->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_run':
                        $stmt->bindValue($identifier, $this->date_last_run ? $this->date_last_run->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_completed':
                        $stmt->bindValue($identifier, $this->date_last_completed ? $this->date_last_completed->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_failed':
                        $stmt->bindValue($identifier, $this->date_last_failed ? $this->date_last_failed->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'last_user_search_run_id':
                        $stmt->bindValue($identifier, $this->last_user_search_run_id, PDO::PARAM_INT);
                        break;
                    case 'supported_country_codes':
                        $stmt->bindValue($identifier, $this->supported_country_codes, PDO::PARAM_STR);
                        break;
                    case 'results_filter_type':
                        $stmt->bindValue($identifier, $this->results_filter_type, PDO::PARAM_INT);
                        break;
                    case 'version':
                        $stmt->bindValue($identifier, $this->version, PDO::PARAM_INT);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

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
        $pos = JobSiteRecordTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getJobSiteKey();
                break;
            case 1:
                return $this->getPluginClassName();
                break;
            case 2:
                return $this->getDisplayName();
                break;
            case 3:
                return $this->getisDisabled();
                break;
            case 4:
                return $this->getLastPulledAt();
                break;
            case 5:
                return $this->getLastRunAt();
                break;
            case 6:
                return $this->getLastCompletedAt();
                break;
            case 7:
                return $this->getLastFailedAt();
                break;
            case 8:
                return $this->getLastUserSearchRunId();
                break;
            case 9:
                return $this->getSupportedCountryCodes();
                break;
            case 10:
                return $this->getResultsFilterType();
                break;
            case 11:
                return $this->getVersion();
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

        if (isset($alreadyDumpedObjects['JobSiteRecord'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['JobSiteRecord'][$this->hashCode()] = true;
        $keys = JobSiteRecordTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getJobSiteKey(),
            $keys[1] => $this->getPluginClassName(),
            $keys[2] => $this->getDisplayName(),
            $keys[3] => $this->getisDisabled(),
            $keys[4] => $this->getLastPulledAt(),
            $keys[5] => $this->getLastRunAt(),
            $keys[6] => $this->getLastCompletedAt(),
            $keys[7] => $this->getLastFailedAt(),
            $keys[8] => ($includeLazyLoadColumns) ? $this->getLastUserSearchRunId() : null,
            $keys[9] => $this->getSupportedCountryCodes(),
            $keys[10] => $this->getResultsFilterType(),
            $keys[11] => $this->getVersion(),
        );
        if ($result[$keys[4]] instanceof \DateTimeInterface) {
            $result[$keys[4]] = $result[$keys[4]]->format('c');
        }

        if ($result[$keys[5]] instanceof \DateTimeInterface) {
            $result[$keys[5]] = $result[$keys[5]]->format('c');
        }

        if ($result[$keys[6]] instanceof \DateTimeInterface) {
            $result[$keys[6]] = $result[$keys[6]]->format('c');
        }

        if ($result[$keys[7]] instanceof \DateTimeInterface) {
            $result[$keys[7]] = $result[$keys[7]]->format('c');
        }

        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aUserSearchRunRelatedByLastUserSearchRunId) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userSearchRun';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_search_run';
                        break;
                    default:
                        $key = 'UserSearchRun';
                }

                $result[$key] = $this->aUserSearchRunRelatedByLastUserSearchRunId->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collJobPostings) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'jobPostings';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'jobpostings';
                        break;
                    default:
                        $key = 'JobPostings';
                }

                $result[$key] = $this->collJobPostings->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collUserSearchRunsRelatedByJobSiteKey) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userSearchRuns';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_search_runs';
                        break;
                    default:
                        $key = 'UserSearchRuns';
                }

                $result[$key] = $this->collUserSearchRunsRelatedByJobSiteKey->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collJobSiteRecordVersions) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'jobSiteRecordVersions';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'job_site_versions';
                        break;
                    default:
                        $key = 'JobSiteRecordVersions';
                }

                $result[$key] = $this->collJobSiteRecordVersions->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\JobScooper\DataAccess\JobSiteRecord
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = JobSiteRecordTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\DataAccess\JobSiteRecord
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setJobSiteKey($value);
                break;
            case 1:
                $this->setPluginClassName($value);
                break;
            case 2:
                $this->setDisplayName($value);
                break;
            case 3:
                $this->setisDisabled($value);
                break;
            case 4:
                $this->setLastPulledAt($value);
                break;
            case 5:
                $this->setLastRunAt($value);
                break;
            case 6:
                $this->setLastCompletedAt($value);
                break;
            case 7:
                $this->setLastFailedAt($value);
                break;
            case 8:
                $this->setLastUserSearchRunId($value);
                break;
            case 9:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setSupportedCountryCodes($value);
                break;
            case 10:
                $valueSet = JobSiteRecordTableMap::getValueSet(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE);
                if (isset($valueSet[$value])) {
                    $value = $valueSet[$value];
                }
                $this->setResultsFilterType($value);
                break;
            case 11:
                $this->setVersion($value);
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
        $keys = JobSiteRecordTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setJobSiteKey($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setPluginClassName($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setDisplayName($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setisDisabled($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setLastPulledAt($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setLastRunAt($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setLastCompletedAt($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setLastFailedAt($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setLastUserSearchRunId($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setSupportedCountryCodes($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setResultsFilterType($arr[$keys[10]]);
        }
        if (array_key_exists($keys[11], $arr)) {
            $this->setVersion($arr[$keys[11]]);
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
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object, for fluid interface
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
        $criteria = new Criteria(JobSiteRecordTableMap::DATABASE_NAME);

        if ($this->isColumnModified(JobSiteRecordTableMap::COL_JOBSITE_KEY)) {
            $criteria->add(JobSiteRecordTableMap::COL_JOBSITE_KEY, $this->jobsite_key);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_PLUGIN_CLASS_NAME)) {
            $criteria->add(JobSiteRecordTableMap::COL_PLUGIN_CLASS_NAME, $this->plugin_class_name);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DISPLAY_NAME)) {
            $criteria->add(JobSiteRecordTableMap::COL_DISPLAY_NAME, $this->display_name);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_IS_DISABLED)) {
            $criteria->add(JobSiteRecordTableMap::COL_IS_DISABLED, $this->is_disabled);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DATE_LAST_PULLED)) {
            $criteria->add(JobSiteRecordTableMap::COL_DATE_LAST_PULLED, $this->date_last_pulled);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DATE_LAST_RUN)) {
            $criteria->add(JobSiteRecordTableMap::COL_DATE_LAST_RUN, $this->date_last_run);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DATE_LAST_COMPLETED)) {
            $criteria->add(JobSiteRecordTableMap::COL_DATE_LAST_COMPLETED, $this->date_last_completed);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_DATE_LAST_FAILED)) {
            $criteria->add(JobSiteRecordTableMap::COL_DATE_LAST_FAILED, $this->date_last_failed);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID)) {
            $criteria->add(JobSiteRecordTableMap::COL_LAST_USER_SEARCH_RUN_ID, $this->last_user_search_run_id);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_SUPPORTED_COUNTRY_CODES)) {
            $criteria->add(JobSiteRecordTableMap::COL_SUPPORTED_COUNTRY_CODES, $this->supported_country_codes);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE)) {
            $criteria->add(JobSiteRecordTableMap::COL_RESULTS_FILTER_TYPE, $this->results_filter_type);
        }
        if ($this->isColumnModified(JobSiteRecordTableMap::COL_VERSION)) {
            $criteria->add(JobSiteRecordTableMap::COL_VERSION, $this->version);
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
        $criteria = ChildJobSiteRecordQuery::create();
        $criteria->add(JobSiteRecordTableMap::COL_JOBSITE_KEY, $this->jobsite_key);

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
        $validPk = null !== $this->getJobSiteKey();

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
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->getJobSiteKey();
    }

    /**
     * Generic method to set the primary key (jobsite_key column).
     *
     * @param       string $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setJobSiteKey($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getJobSiteKey();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\DataAccess\JobSiteRecord (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setJobSiteKey($this->getJobSiteKey());
        $copyObj->setPluginClassName($this->getPluginClassName());
        $copyObj->setDisplayName($this->getDisplayName());
        $copyObj->setisDisabled($this->getisDisabled());
        $copyObj->setLastPulledAt($this->getLastPulledAt());
        $copyObj->setLastRunAt($this->getLastRunAt());
        $copyObj->setLastCompletedAt($this->getLastCompletedAt());
        $copyObj->setLastFailedAt($this->getLastFailedAt());
        $copyObj->setLastUserSearchRunId($this->getLastUserSearchRunId());
        $copyObj->setSupportedCountryCodes($this->getSupportedCountryCodes());
        $copyObj->setResultsFilterType($this->getResultsFilterType());
        $copyObj->setVersion($this->getVersion());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getJobPostings() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addJobPosting($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getUserSearchRunsRelatedByJobSiteKey() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserSearchRunRelatedByJobSiteKey($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getJobSiteRecordVersions() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addJobSiteRecordVersion($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
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
     * @return \JobScooper\DataAccess\JobSiteRecord Clone of current object.
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
     * Declares an association between this object and a ChildUserSearchRun object.
     *
     * @param  ChildUserSearchRun $v
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUserSearchRunRelatedByLastUserSearchRunId(ChildUserSearchRun $v = null)
    {
        if ($v === null) {
            $this->setLastUserSearchRunId(NULL);
        } else {
            $this->setLastUserSearchRunId($v->getUserSearchRunId());
        }

        $this->aUserSearchRunRelatedByLastUserSearchRunId = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUserSearchRun object, it will not be re-added.
        if ($v !== null) {
            $v->addJobSiteRecordRelatedByLastUserSearchRunId($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildUserSearchRun object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildUserSearchRun The associated ChildUserSearchRun object.
     * @throws PropelException
     */
    public function getUserSearchRunRelatedByLastUserSearchRunId(ConnectionInterface $con = null)
    {
        if ($this->aUserSearchRunRelatedByLastUserSearchRunId === null && ($this->last_user_search_run_id != 0)) {
            $this->aUserSearchRunRelatedByLastUserSearchRunId = ChildUserSearchRunQuery::create()->findPk($this->last_user_search_run_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUserSearchRunRelatedByLastUserSearchRunId->addJobSiteRecordsRelatedByLastUserSearchRunId($this);
             */
        }

        return $this->aUserSearchRunRelatedByLastUserSearchRunId;
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
        if ('JobPosting' == $relationName) {
            $this->initJobPostings();
            return;
        }
        if ('UserSearchRunRelatedByJobSiteKey' == $relationName) {
            $this->initUserSearchRunsRelatedByJobSiteKey();
            return;
        }
        if ('JobSiteRecordVersion' == $relationName) {
            $this->initJobSiteRecordVersions();
            return;
        }
    }

    /**
     * Clears out the collJobPostings collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addJobPostings()
     */
    public function clearJobPostings()
    {
        $this->collJobPostings = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collJobPostings collection loaded partially.
     */
    public function resetPartialJobPostings($v = true)
    {
        $this->collJobPostingsPartial = $v;
    }

    /**
     * Initializes the collJobPostings collection.
     *
     * By default this just sets the collJobPostings collection to an empty array (like clearcollJobPostings());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initJobPostings($overrideExisting = true)
    {
        if (null !== $this->collJobPostings && !$overrideExisting) {
            return;
        }

        $collectionClassName = JobPostingTableMap::getTableMap()->getCollectionClassName();

        $this->collJobPostings = new $collectionClassName;
        $this->collJobPostings->setModel('\JobScooper\DataAccess\JobPosting');
    }

    /**
     * Gets an array of ChildJobPosting objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildJobSiteRecord is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildJobPosting[] List of ChildJobPosting objects
     * @throws PropelException
     */
    public function getJobPostings(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collJobPostingsPartial && !$this->isNew();
        if (null === $this->collJobPostings || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collJobPostings) {
                // return empty collection
                $this->initJobPostings();
            } else {
                $collJobPostings = ChildJobPostingQuery::create(null, $criteria)
                    ->filterByJobSiteRecord($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collJobPostingsPartial && count($collJobPostings)) {
                        $this->initJobPostings(false);

                        foreach ($collJobPostings as $obj) {
                            if (false == $this->collJobPostings->contains($obj)) {
                                $this->collJobPostings->append($obj);
                            }
                        }

                        $this->collJobPostingsPartial = true;
                    }

                    return $collJobPostings;
                }

                if ($partial && $this->collJobPostings) {
                    foreach ($this->collJobPostings as $obj) {
                        if ($obj->isNew()) {
                            $collJobPostings[] = $obj;
                        }
                    }
                }

                $this->collJobPostings = $collJobPostings;
                $this->collJobPostingsPartial = false;
            }
        }

        return $this->collJobPostings;
    }

    /**
     * Sets a collection of ChildJobPosting objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $jobPostings A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildJobSiteRecord The current object (for fluent API support)
     */
    public function setJobPostings(Collection $jobPostings, ConnectionInterface $con = null)
    {
        /** @var ChildJobPosting[] $jobPostingsToDelete */
        $jobPostingsToDelete = $this->getJobPostings(new Criteria(), $con)->diff($jobPostings);


        $this->jobPostingsScheduledForDeletion = $jobPostingsToDelete;

        foreach ($jobPostingsToDelete as $jobPostingRemoved) {
            $jobPostingRemoved->setJobSiteRecord(null);
        }

        $this->collJobPostings = null;
        foreach ($jobPostings as $jobPosting) {
            $this->addJobPosting($jobPosting);
        }

        $this->collJobPostings = $jobPostings;
        $this->collJobPostingsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related JobPosting objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related JobPosting objects.
     * @throws PropelException
     */
    public function countJobPostings(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collJobPostingsPartial && !$this->isNew();
        if (null === $this->collJobPostings || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collJobPostings) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getJobPostings());
            }

            $query = ChildJobPostingQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByJobSiteRecord($this)
                ->count($con);
        }

        return count($this->collJobPostings);
    }

    /**
     * Method called to associate a ChildJobPosting object to this object
     * through the ChildJobPosting foreign key attribute.
     *
     * @param  ChildJobPosting $l ChildJobPosting
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function addJobPosting(ChildJobPosting $l)
    {
        if ($this->collJobPostings === null) {
            $this->initJobPostings();
            $this->collJobPostingsPartial = true;
        }

        if (!$this->collJobPostings->contains($l)) {
            $this->doAddJobPosting($l);

            if ($this->jobPostingsScheduledForDeletion and $this->jobPostingsScheduledForDeletion->contains($l)) {
                $this->jobPostingsScheduledForDeletion->remove($this->jobPostingsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildJobPosting $jobPosting The ChildJobPosting object to add.
     */
    protected function doAddJobPosting(ChildJobPosting $jobPosting)
    {
        $this->collJobPostings[]= $jobPosting;
        $jobPosting->setJobSiteRecord($this);
    }

    /**
     * @param  ChildJobPosting $jobPosting The ChildJobPosting object to remove.
     * @return $this|ChildJobSiteRecord The current object (for fluent API support)
     */
    public function removeJobPosting(ChildJobPosting $jobPosting)
    {
        if ($this->getJobPostings()->contains($jobPosting)) {
            $pos = $this->collJobPostings->search($jobPosting);
            $this->collJobPostings->remove($pos);
            if (null === $this->jobPostingsScheduledForDeletion) {
                $this->jobPostingsScheduledForDeletion = clone $this->collJobPostings;
                $this->jobPostingsScheduledForDeletion->clear();
            }
            $this->jobPostingsScheduledForDeletion[]= clone $jobPosting;
            $jobPosting->setJobSiteRecord(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this JobSiteRecord is new, it will return
     * an empty collection; or if this JobSiteRecord has previously
     * been saved, it will retrieve related JobPostings from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in JobSiteRecord.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildJobPosting[] List of ChildJobPosting objects
     */
    public function getJobPostingsJoinGeoLocation(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildJobPostingQuery::create(null, $criteria);
        $query->joinWith('GeoLocation', $joinBehavior);

        return $this->getJobPostings($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this JobSiteRecord is new, it will return
     * an empty collection; or if this JobSiteRecord has previously
     * been saved, it will retrieve related JobPostings from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in JobSiteRecord.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildJobPosting[] List of ChildJobPosting objects
     */
    public function getJobPostingsJoinJobPostingRelatedByDuplicatesJobPostingId(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildJobPostingQuery::create(null, $criteria);
        $query->joinWith('JobPostingRelatedByDuplicatesJobPostingId', $joinBehavior);

        return $this->getJobPostings($query, $con);
    }

    /**
     * Clears out the collUserSearchRunsRelatedByJobSiteKey collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserSearchRunsRelatedByJobSiteKey()
     */
    public function clearUserSearchRunsRelatedByJobSiteKey()
    {
        $this->collUserSearchRunsRelatedByJobSiteKey = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collUserSearchRunsRelatedByJobSiteKey collection loaded partially.
     */
    public function resetPartialUserSearchRunsRelatedByJobSiteKey($v = true)
    {
        $this->collUserSearchRunsRelatedByJobSiteKeyPartial = $v;
    }

    /**
     * Initializes the collUserSearchRunsRelatedByJobSiteKey collection.
     *
     * By default this just sets the collUserSearchRunsRelatedByJobSiteKey collection to an empty array (like clearcollUserSearchRunsRelatedByJobSiteKey());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUserSearchRunsRelatedByJobSiteKey($overrideExisting = true)
    {
        if (null !== $this->collUserSearchRunsRelatedByJobSiteKey && !$overrideExisting) {
            return;
        }

        $collectionClassName = UserSearchRunTableMap::getTableMap()->getCollectionClassName();

        $this->collUserSearchRunsRelatedByJobSiteKey = new $collectionClassName;
        $this->collUserSearchRunsRelatedByJobSiteKey->setModel('\JobScooper\DataAccess\UserSearchRun');
    }

    /**
     * Gets an array of ChildUserSearchRun objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildJobSiteRecord is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildUserSearchRun[] List of ChildUserSearchRun objects
     * @throws PropelException
     */
    public function getUserSearchRunsRelatedByJobSiteKey(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchRunsRelatedByJobSiteKeyPartial && !$this->isNew();
        if (null === $this->collUserSearchRunsRelatedByJobSiteKey || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUserSearchRunsRelatedByJobSiteKey) {
                // return empty collection
                $this->initUserSearchRunsRelatedByJobSiteKey();
            } else {
                $collUserSearchRunsRelatedByJobSiteKey = ChildUserSearchRunQuery::create(null, $criteria)
                    ->filterByJobSiteRecordRelatedByJobSiteKey($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collUserSearchRunsRelatedByJobSiteKeyPartial && count($collUserSearchRunsRelatedByJobSiteKey)) {
                        $this->initUserSearchRunsRelatedByJobSiteKey(false);

                        foreach ($collUserSearchRunsRelatedByJobSiteKey as $obj) {
                            if (false == $this->collUserSearchRunsRelatedByJobSiteKey->contains($obj)) {
                                $this->collUserSearchRunsRelatedByJobSiteKey->append($obj);
                            }
                        }

                        $this->collUserSearchRunsRelatedByJobSiteKeyPartial = true;
                    }

                    return $collUserSearchRunsRelatedByJobSiteKey;
                }

                if ($partial && $this->collUserSearchRunsRelatedByJobSiteKey) {
                    foreach ($this->collUserSearchRunsRelatedByJobSiteKey as $obj) {
                        if ($obj->isNew()) {
                            $collUserSearchRunsRelatedByJobSiteKey[] = $obj;
                        }
                    }
                }

                $this->collUserSearchRunsRelatedByJobSiteKey = $collUserSearchRunsRelatedByJobSiteKey;
                $this->collUserSearchRunsRelatedByJobSiteKeyPartial = false;
            }
        }

        return $this->collUserSearchRunsRelatedByJobSiteKey;
    }

    /**
     * Sets a collection of ChildUserSearchRun objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $userSearchRunsRelatedByJobSiteKey A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildJobSiteRecord The current object (for fluent API support)
     */
    public function setUserSearchRunsRelatedByJobSiteKey(Collection $userSearchRunsRelatedByJobSiteKey, ConnectionInterface $con = null)
    {
        /** @var ChildUserSearchRun[] $userSearchRunsRelatedByJobSiteKeyToDelete */
        $userSearchRunsRelatedByJobSiteKeyToDelete = $this->getUserSearchRunsRelatedByJobSiteKey(new Criteria(), $con)->diff($userSearchRunsRelatedByJobSiteKey);


        $this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion = $userSearchRunsRelatedByJobSiteKeyToDelete;

        foreach ($userSearchRunsRelatedByJobSiteKeyToDelete as $userSearchRunRelatedByJobSiteKeyRemoved) {
            $userSearchRunRelatedByJobSiteKeyRemoved->setJobSiteRecordRelatedByJobSiteKey(null);
        }

        $this->collUserSearchRunsRelatedByJobSiteKey = null;
        foreach ($userSearchRunsRelatedByJobSiteKey as $userSearchRunRelatedByJobSiteKey) {
            $this->addUserSearchRunRelatedByJobSiteKey($userSearchRunRelatedByJobSiteKey);
        }

        $this->collUserSearchRunsRelatedByJobSiteKey = $userSearchRunsRelatedByJobSiteKey;
        $this->collUserSearchRunsRelatedByJobSiteKeyPartial = false;

        return $this;
    }

    /**
     * Returns the number of related UserSearchRun objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related UserSearchRun objects.
     * @throws PropelException
     */
    public function countUserSearchRunsRelatedByJobSiteKey(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchRunsRelatedByJobSiteKeyPartial && !$this->isNew();
        if (null === $this->collUserSearchRunsRelatedByJobSiteKey || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserSearchRunsRelatedByJobSiteKey) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getUserSearchRunsRelatedByJobSiteKey());
            }

            $query = ChildUserSearchRunQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByJobSiteRecordRelatedByJobSiteKey($this)
                ->count($con);
        }

        return count($this->collUserSearchRunsRelatedByJobSiteKey);
    }

    /**
     * Method called to associate a ChildUserSearchRun object to this object
     * through the ChildUserSearchRun foreign key attribute.
     *
     * @param  ChildUserSearchRun $l ChildUserSearchRun
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function addUserSearchRunRelatedByJobSiteKey(ChildUserSearchRun $l)
    {
        if ($this->collUserSearchRunsRelatedByJobSiteKey === null) {
            $this->initUserSearchRunsRelatedByJobSiteKey();
            $this->collUserSearchRunsRelatedByJobSiteKeyPartial = true;
        }

        if (!$this->collUserSearchRunsRelatedByJobSiteKey->contains($l)) {
            $this->doAddUserSearchRunRelatedByJobSiteKey($l);

            if ($this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion and $this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion->contains($l)) {
                $this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion->remove($this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildUserSearchRun $userSearchRunRelatedByJobSiteKey The ChildUserSearchRun object to add.
     */
    protected function doAddUserSearchRunRelatedByJobSiteKey(ChildUserSearchRun $userSearchRunRelatedByJobSiteKey)
    {
        $this->collUserSearchRunsRelatedByJobSiteKey[]= $userSearchRunRelatedByJobSiteKey;
        $userSearchRunRelatedByJobSiteKey->setJobSiteRecordRelatedByJobSiteKey($this);
    }

    /**
     * @param  ChildUserSearchRun $userSearchRunRelatedByJobSiteKey The ChildUserSearchRun object to remove.
     * @return $this|ChildJobSiteRecord The current object (for fluent API support)
     */
    public function removeUserSearchRunRelatedByJobSiteKey(ChildUserSearchRun $userSearchRunRelatedByJobSiteKey)
    {
        if ($this->getUserSearchRunsRelatedByJobSiteKey()->contains($userSearchRunRelatedByJobSiteKey)) {
            $pos = $this->collUserSearchRunsRelatedByJobSiteKey->search($userSearchRunRelatedByJobSiteKey);
            $this->collUserSearchRunsRelatedByJobSiteKey->remove($pos);
            if (null === $this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion) {
                $this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion = clone $this->collUserSearchRunsRelatedByJobSiteKey;
                $this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion->clear();
            }
            $this->userSearchRunsRelatedByJobSiteKeyScheduledForDeletion[]= clone $userSearchRunRelatedByJobSiteKey;
            $userSearchRunRelatedByJobSiteKey->setJobSiteRecordRelatedByJobSiteKey(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this JobSiteRecord is new, it will return
     * an empty collection; or if this JobSiteRecord has previously
     * been saved, it will retrieve related UserSearchRunsRelatedByJobSiteKey from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in JobSiteRecord.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearchRun[] List of ChildUserSearchRun objects
     */
    public function getUserSearchRunsRelatedByJobSiteKeyJoinUserSearch(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchRunQuery::create(null, $criteria);
        $query->joinWith('UserSearch', $joinBehavior);

        return $this->getUserSearchRunsRelatedByJobSiteKey($query, $con);
    }

    /**
     * Clears out the collJobSiteRecordVersions collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addJobSiteRecordVersions()
     */
    public function clearJobSiteRecordVersions()
    {
        $this->collJobSiteRecordVersions = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collJobSiteRecordVersions collection loaded partially.
     */
    public function resetPartialJobSiteRecordVersions($v = true)
    {
        $this->collJobSiteRecordVersionsPartial = $v;
    }

    /**
     * Initializes the collJobSiteRecordVersions collection.
     *
     * By default this just sets the collJobSiteRecordVersions collection to an empty array (like clearcollJobSiteRecordVersions());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initJobSiteRecordVersions($overrideExisting = true)
    {
        if (null !== $this->collJobSiteRecordVersions && !$overrideExisting) {
            return;
        }

        $collectionClassName = JobSiteRecordVersionTableMap::getTableMap()->getCollectionClassName();

        $this->collJobSiteRecordVersions = new $collectionClassName;
        $this->collJobSiteRecordVersions->setModel('\JobScooper\DataAccess\JobSiteRecordVersion');
    }

    /**
     * Gets an array of ChildJobSiteRecordVersion objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildJobSiteRecord is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildJobSiteRecordVersion[] List of ChildJobSiteRecordVersion objects
     * @throws PropelException
     */
    public function getJobSiteRecordVersions(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collJobSiteRecordVersionsPartial && !$this->isNew();
        if (null === $this->collJobSiteRecordVersions || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collJobSiteRecordVersions) {
                // return empty collection
                $this->initJobSiteRecordVersions();
            } else {
                $collJobSiteRecordVersions = ChildJobSiteRecordVersionQuery::create(null, $criteria)
                    ->filterByJobSiteRecord($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collJobSiteRecordVersionsPartial && count($collJobSiteRecordVersions)) {
                        $this->initJobSiteRecordVersions(false);

                        foreach ($collJobSiteRecordVersions as $obj) {
                            if (false == $this->collJobSiteRecordVersions->contains($obj)) {
                                $this->collJobSiteRecordVersions->append($obj);
                            }
                        }

                        $this->collJobSiteRecordVersionsPartial = true;
                    }

                    return $collJobSiteRecordVersions;
                }

                if ($partial && $this->collJobSiteRecordVersions) {
                    foreach ($this->collJobSiteRecordVersions as $obj) {
                        if ($obj->isNew()) {
                            $collJobSiteRecordVersions[] = $obj;
                        }
                    }
                }

                $this->collJobSiteRecordVersions = $collJobSiteRecordVersions;
                $this->collJobSiteRecordVersionsPartial = false;
            }
        }

        return $this->collJobSiteRecordVersions;
    }

    /**
     * Sets a collection of ChildJobSiteRecordVersion objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $jobSiteRecordVersions A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildJobSiteRecord The current object (for fluent API support)
     */
    public function setJobSiteRecordVersions(Collection $jobSiteRecordVersions, ConnectionInterface $con = null)
    {
        /** @var ChildJobSiteRecordVersion[] $jobSiteRecordVersionsToDelete */
        $jobSiteRecordVersionsToDelete = $this->getJobSiteRecordVersions(new Criteria(), $con)->diff($jobSiteRecordVersions);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->jobSiteRecordVersionsScheduledForDeletion = clone $jobSiteRecordVersionsToDelete;

        foreach ($jobSiteRecordVersionsToDelete as $jobSiteRecordVersionRemoved) {
            $jobSiteRecordVersionRemoved->setJobSiteRecord(null);
        }

        $this->collJobSiteRecordVersions = null;
        foreach ($jobSiteRecordVersions as $jobSiteRecordVersion) {
            $this->addJobSiteRecordVersion($jobSiteRecordVersion);
        }

        $this->collJobSiteRecordVersions = $jobSiteRecordVersions;
        $this->collJobSiteRecordVersionsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related JobSiteRecordVersion objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related JobSiteRecordVersion objects.
     * @throws PropelException
     */
    public function countJobSiteRecordVersions(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collJobSiteRecordVersionsPartial && !$this->isNew();
        if (null === $this->collJobSiteRecordVersions || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collJobSiteRecordVersions) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getJobSiteRecordVersions());
            }

            $query = ChildJobSiteRecordVersionQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByJobSiteRecord($this)
                ->count($con);
        }

        return count($this->collJobSiteRecordVersions);
    }

    /**
     * Method called to associate a ChildJobSiteRecordVersion object to this object
     * through the ChildJobSiteRecordVersion foreign key attribute.
     *
     * @param  ChildJobSiteRecordVersion $l ChildJobSiteRecordVersion
     * @return $this|\JobScooper\DataAccess\JobSiteRecord The current object (for fluent API support)
     */
    public function addJobSiteRecordVersion(ChildJobSiteRecordVersion $l)
    {
        if ($this->collJobSiteRecordVersions === null) {
            $this->initJobSiteRecordVersions();
            $this->collJobSiteRecordVersionsPartial = true;
        }

        if (!$this->collJobSiteRecordVersions->contains($l)) {
            $this->doAddJobSiteRecordVersion($l);

            if ($this->jobSiteRecordVersionsScheduledForDeletion and $this->jobSiteRecordVersionsScheduledForDeletion->contains($l)) {
                $this->jobSiteRecordVersionsScheduledForDeletion->remove($this->jobSiteRecordVersionsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildJobSiteRecordVersion $jobSiteRecordVersion The ChildJobSiteRecordVersion object to add.
     */
    protected function doAddJobSiteRecordVersion(ChildJobSiteRecordVersion $jobSiteRecordVersion)
    {
        $this->collJobSiteRecordVersions[]= $jobSiteRecordVersion;
        $jobSiteRecordVersion->setJobSiteRecord($this);
    }

    /**
     * @param  ChildJobSiteRecordVersion $jobSiteRecordVersion The ChildJobSiteRecordVersion object to remove.
     * @return $this|ChildJobSiteRecord The current object (for fluent API support)
     */
    public function removeJobSiteRecordVersion(ChildJobSiteRecordVersion $jobSiteRecordVersion)
    {
        if ($this->getJobSiteRecordVersions()->contains($jobSiteRecordVersion)) {
            $pos = $this->collJobSiteRecordVersions->search($jobSiteRecordVersion);
            $this->collJobSiteRecordVersions->remove($pos);
            if (null === $this->jobSiteRecordVersionsScheduledForDeletion) {
                $this->jobSiteRecordVersionsScheduledForDeletion = clone $this->collJobSiteRecordVersions;
                $this->jobSiteRecordVersionsScheduledForDeletion->clear();
            }
            $this->jobSiteRecordVersionsScheduledForDeletion[]= clone $jobSiteRecordVersion;
            $jobSiteRecordVersion->setJobSiteRecord(null);
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
        if (null !== $this->aUserSearchRunRelatedByLastUserSearchRunId) {
            $this->aUserSearchRunRelatedByLastUserSearchRunId->removeJobSiteRecordRelatedByLastUserSearchRunId($this);
        }
        $this->jobsite_key = null;
        $this->plugin_class_name = null;
        $this->display_name = null;
        $this->is_disabled = null;
        $this->date_last_pulled = null;
        $this->date_last_run = null;
        $this->date_last_completed = null;
        $this->date_last_failed = null;
        $this->last_user_search_run_id = null;
        $this->last_user_search_run_id_isLoaded = false;
        $this->supported_country_codes = null;
        $this->supported_country_codes_unserialized = null;
        $this->results_filter_type = null;
        $this->version = null;
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
            if ($this->collJobPostings) {
                foreach ($this->collJobPostings as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collUserSearchRunsRelatedByJobSiteKey) {
                foreach ($this->collUserSearchRunsRelatedByJobSiteKey as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collJobSiteRecordVersions) {
                foreach ($this->collJobSiteRecordVersions as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collJobPostings = null;
        $this->collUserSearchRunsRelatedByJobSiteKey = null;
        $this->collJobSiteRecordVersions = null;
        $this->aUserSearchRunRelatedByLastUserSearchRunId = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'jobsite_key' column
     */
    public function __toString()
    {
        return (string) $this->getJobSiteKey();
    }

    // date_last_pulled behavior

    /**
     * Computes the value of the aggregate column date_last_pulled *
     * @param ConnectionInterface $con A connection object
     *
     * @return mixed The scalar result from the aggregate query
     */
    public function computeLastPulledAt(ConnectionInterface $con)
    {
        $stmt = $con->prepare('SELECT MAX(date_ended) FROM user_search_run WHERE run_result_code = 4 AND user_search_run.JOBSITE_KEY = :p1');
        $stmt->bindValue(':p1', $this->getJobSiteKey());
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * Updates the aggregate column date_last_pulled *
     * @param ConnectionInterface $con A connection object
     */
    public function updateLastPulledAt(ConnectionInterface $con)
    {
        $this->setLastPulledAt($this->computeLastPulledAt($con));
        $this->save($con);
    }

    // date_last_run behavior

    /**
     * Computes the value of the aggregate column date_last_run *
     * @param ConnectionInterface $con A connection object
     *
     * @return mixed The scalar result from the aggregate query
     */
    public function computeLastRunAt(ConnectionInterface $con)
    {
        $stmt = $con->prepare('SELECT MAX(date_started) FROM user_search_run WHERE user_search_run.JOBSITE_KEY = :p1');
        $stmt->bindValue(':p1', $this->getJobSiteKey());
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * Updates the aggregate column date_last_run *
     * @param ConnectionInterface $con A connection object
     */
    public function updateLastRunAt(ConnectionInterface $con)
    {
        $this->setLastRunAt($this->computeLastRunAt($con));
        $this->save($con);
    }

    // date_last_completed behavior

    /**
     * Computes the value of the aggregate column date_last_completed *
     * @param ConnectionInterface $con A connection object
     *
     * @return mixed The scalar result from the aggregate query
     */
    public function computeLastCompletedAt(ConnectionInterface $con)
    {
        $stmt = $con->prepare('SELECT MAX(date_ended) FROM user_search_run WHERE run_result_code = 4 AND user_search_run.JOBSITE_KEY = :p1');
        $stmt->bindValue(':p1', $this->getJobSiteKey());
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * Updates the aggregate column date_last_completed *
     * @param ConnectionInterface $con A connection object
     */
    public function updateLastCompletedAt(ConnectionInterface $con)
    {
        $this->setLastCompletedAt($this->computeLastCompletedAt($con));
        $this->save($con);
    }

    // date_last_failed behavior

    /**
     * Computes the value of the aggregate column date_last_failed *
     * @param ConnectionInterface $con A connection object
     *
     * @return mixed The scalar result from the aggregate query
     */
    public function computeLastFailedAt(ConnectionInterface $con)
    {
        $stmt = $con->prepare('SELECT MAX(date_ended) FROM user_search_run WHERE run_result_code = 1 AND user_search_run.JOBSITE_KEY = :p1');
        $stmt->bindValue(':p1', $this->getJobSiteKey());
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * Updates the aggregate column date_last_failed *
     * @param ConnectionInterface $con A connection object
     */
    public function updateLastFailedAt(ConnectionInterface $con)
    {
        $this->setLastFailedAt($this->computeLastFailedAt($con));
        $this->save($con);
    }

    // aggregate_column behavior

    /**
     * Computes the value of the aggregate column last_user_search_run_id *
     * @param ConnectionInterface $con A connection object
     *
     * @return mixed The scalar result from the aggregate query
     */
    public function computeLastUserSearchRunId(ConnectionInterface $con)
    {
        $stmt = $con->prepare('SELECT MAX(user_search_run_id) FROM user_search_run WHERE user_search_run.JOBSITE_KEY = :p1');
        $stmt->bindValue(':p1', $this->getJobSiteKey());
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * Updates the aggregate column last_user_search_run_id *
     * @param ConnectionInterface $con A connection object
     */
    public function updateLastUserSearchRunId(ConnectionInterface $con)
    {
        $this->setLastUserSearchRunId($this->computeLastUserSearchRunId($con));
        $this->save($con);
    }

    // versionable behavior

    /**
     * Enforce a new Version of this object upon next save.
     *
     * @return $this|\JobScooper\DataAccess\JobSiteRecord
     */
    public function enforceVersioning()
    {
        $this->enforceVersion = true;

        return $this;
    }

    /**
     * Checks whether the current state must be recorded as a version
     *
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     * @return  boolean
     */
    public function isVersioningNecessary(ConnectionInterface $con = null)
    {
        if ($this->alreadyInSave) {
            return false;
        }

        if ($this->enforceVersion) {
            return true;
        }

        if (ChildJobSiteRecordQuery::isVersioningEnabled() && ($this->isNew() || $this->isModified()) || $this->isDeleted()) {
            return true;
        }

        return false;
    }

    /**
     * Creates a version of the current object and saves it.
     *
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  ChildJobSiteRecordVersion A version object
     */
    public function addVersion(ConnectionInterface $con = null)
    {
        $this->enforceVersion = false;

        $version = new ChildJobSiteRecordVersion();
        $version->setJobSiteKey($this->getJobSiteKey());
        $version->setPluginClassName($this->getPluginClassName());
        $version->setDisplayName($this->getDisplayName());
        $version->setisDisabled($this->getisDisabled());
        $version->setLastPulledAt($this->getLastPulledAt());
        $version->setLastRunAt($this->getLastRunAt());
        $version->setLastCompletedAt($this->getLastCompletedAt());
        $version->setLastFailedAt($this->getLastFailedAt());
        $version->setLastUserSearchRunId($this->getLastUserSearchRunId());
        $version->setSupportedCountryCodes($this->getSupportedCountryCodes());
        $version->setResultsFilterType($this->getResultsFilterType());
        $version->setVersion($this->getVersion());
        $version->setJobSiteRecord($this);
        $version->save($con);

        return $version;
    }

    /**
     * Sets the properties of the current object to the value they had at a specific version
     *
     * @param   integer $versionNumber The version number to read
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  $this|ChildJobSiteRecord The current object (for fluent API support)
     */
    public function toVersion($versionNumber, ConnectionInterface $con = null)
    {
        $version = $this->getOneVersion($versionNumber, $con);
        if (!$version) {
            throw new PropelException(sprintf('No ChildJobSiteRecord object found with version %d', $version));
        }
        $this->populateFromVersion($version, $con);

        return $this;
    }

    /**
     * Sets the properties of the current object to the value they had at a specific version
     *
     * @param ChildJobSiteRecordVersion $version The version object to use
     * @param ConnectionInterface   $con the connection to use
     * @param array                 $loadedObjects objects that been loaded in a chain of populateFromVersion calls on referrer or fk objects.
     *
     * @return $this|ChildJobSiteRecord The current object (for fluent API support)
     */
    public function populateFromVersion($version, $con = null, &$loadedObjects = array())
    {
        $loadedObjects['ChildJobSiteRecord'][$version->getJobSiteKey()][$version->getVersion()] = $this;
        $this->setJobSiteKey($version->getJobSiteKey());
        $this->setPluginClassName($version->getPluginClassName());
        $this->setDisplayName($version->getDisplayName());
        $this->setisDisabled($version->getisDisabled());
        $this->setLastPulledAt($version->getLastPulledAt());
        $this->setLastRunAt($version->getLastRunAt());
        $this->setLastCompletedAt($version->getLastCompletedAt());
        $this->setLastFailedAt($version->getLastFailedAt());
        $this->setLastUserSearchRunId($version->getLastUserSearchRunId());
        $this->setSupportedCountryCodes($version->getSupportedCountryCodes());
        $this->setResultsFilterType($version->getResultsFilterType());
        $this->setVersion($version->getVersion());

        return $this;
    }

    /**
     * Gets the latest persisted version number for the current object
     *
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  integer
     */
    public function getLastVersionNumber(ConnectionInterface $con = null)
    {
        $v = ChildJobSiteRecordVersionQuery::create()
            ->filterByJobSiteRecord($this)
            ->orderByVersion('desc')
            ->findOne($con);
        if (!$v) {
            return 0;
        }

        return $v->getVersion();
    }

    /**
     * Checks whether the current object is the latest one
     *
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  Boolean
     */
    public function isLastVersion(ConnectionInterface $con = null)
    {
        return $this->getLastVersionNumber($con) == $this->getVersion();
    }

    /**
     * Retrieves a version object for this entity and a version number
     *
     * @param   integer $versionNumber The version number to read
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  ChildJobSiteRecordVersion A version object
     */
    public function getOneVersion($versionNumber, ConnectionInterface $con = null)
    {
        return ChildJobSiteRecordVersionQuery::create()
            ->filterByJobSiteRecord($this)
            ->filterByVersion($versionNumber)
            ->findOne($con);
    }

    /**
     * Gets all the versions of this object, in incremental order
     *
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  ObjectCollection|ChildJobSiteRecordVersion[] A list of ChildJobSiteRecordVersion objects
     */
    public function getAllVersions(ConnectionInterface $con = null)
    {
        $criteria = new Criteria();
        $criteria->addAscendingOrderByColumn(JobSiteRecordVersionTableMap::COL_VERSION);

        return $this->getJobSiteRecordVersions($criteria, $con);
    }

    /**
     * Compares the current object with another of its version.
     * <code>
     * print_r($book->compareVersion(1));
     * => array(
     *   '1' => array('Title' => 'Book title at version 1'),
     *   '2' => array('Title' => 'Book title at version 2')
     * );
     * </code>
     *
     * @param   integer             $versionNumber
     * @param   string              $keys Main key used for the result diff (versions|columns)
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     * @param   array               $ignoredColumns  The columns to exclude from the diff.
     *
     * @return  array A list of differences
     */
    public function compareVersion($versionNumber, $keys = 'columns', ConnectionInterface $con = null, $ignoredColumns = array())
    {
        $fromVersion = $this->toArray();
        $toVersion = $this->getOneVersion($versionNumber, $con)->toArray();

        return $this->computeDiff($fromVersion, $toVersion, $keys, $ignoredColumns);
    }

    /**
     * Compares two versions of the current object.
     * <code>
     * print_r($book->compareVersions(1, 2));
     * => array(
     *   '1' => array('Title' => 'Book title at version 1'),
     *   '2' => array('Title' => 'Book title at version 2')
     * );
     * </code>
     *
     * @param   integer             $fromVersionNumber
     * @param   integer             $toVersionNumber
     * @param   string              $keys Main key used for the result diff (versions|columns)
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     * @param   array               $ignoredColumns  The columns to exclude from the diff.
     *
     * @return  array A list of differences
     */
    public function compareVersions($fromVersionNumber, $toVersionNumber, $keys = 'columns', ConnectionInterface $con = null, $ignoredColumns = array())
    {
        $fromVersion = $this->getOneVersion($fromVersionNumber, $con)->toArray();
        $toVersion = $this->getOneVersion($toVersionNumber, $con)->toArray();

        return $this->computeDiff($fromVersion, $toVersion, $keys, $ignoredColumns);
    }

    /**
     * Computes the diff between two versions.
     * <code>
     * print_r($book->computeDiff(1, 2));
     * => array(
     *   '1' => array('Title' => 'Book title at version 1'),
     *   '2' => array('Title' => 'Book title at version 2')
     * );
     * </code>
     *
     * @param   array     $fromVersion     An array representing the original version.
     * @param   array     $toVersion       An array representing the destination version.
     * @param   string    $keys            Main key used for the result diff (versions|columns).
     * @param   array     $ignoredColumns  The columns to exclude from the diff.
     *
     * @return  array A list of differences
     */
    protected function computeDiff($fromVersion, $toVersion, $keys = 'columns', $ignoredColumns = array())
    {
        $fromVersionNumber = $fromVersion['Version'];
        $toVersionNumber = $toVersion['Version'];
        $ignoredColumns = array_merge(array(
            'Version',
        ), $ignoredColumns);
        $diff = array();
        foreach ($fromVersion as $key => $value) {
            if (in_array($key, $ignoredColumns)) {
                continue;
            }
            if ($toVersion[$key] != $value) {
                switch ($keys) {
                    case 'versions':
                        $diff[$fromVersionNumber][$key] = $value;
                        $diff[$toVersionNumber][$key] = $toVersion[$key];
                        break;
                    default:
                        $diff[$key] = array(
                            $fromVersionNumber => $value,
                            $toVersionNumber => $toVersion[$key],
                        );
                        break;
                }
            }
        }

        return $diff;
    }
    /**
     * retrieve the last $number versions.
     *
     * @param  Integer             $number The number of record to return.
     * @param  Criteria            $criteria The Criteria object containing modified values.
     * @param  ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return PropelCollection|\JobScooper\DataAccess\JobSiteRecordVersion[] List of \JobScooper\DataAccess\JobSiteRecordVersion objects
     */
    public function getLastVersions($number = 10, $criteria = null, ConnectionInterface $con = null)
    {
        $criteria = ChildJobSiteRecordVersionQuery::create(null, $criteria);
        $criteria->addDescendingOrderByColumn(JobSiteRecordVersionTableMap::COL_VERSION);
        $criteria->limit($number);

        return $this->getJobSiteRecordVersions($criteria, $con);
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
