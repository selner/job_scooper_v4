<?php

namespace JobScooper\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\JobSitePluginLastRun as ChildJobSitePluginLastRun;
use JobScooper\JobSitePluginLastRunQuery as ChildJobSitePluginLastRunQuery;
use JobScooper\Map\JobSitePluginLastRunTableMap;
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
 * Base class that represents a row from the 'jobsite_plugin_last_run' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.Base
 */
abstract class JobSitePluginLastRun implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\Map\\JobSitePluginLastRunTableMap';


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
     * The value for the jobsite field.
     *
     * @var        string
     */
    protected $jobsite;

    /**
     * The value for the last_user_search_run_id field.
     *
     * @var        int
     */
    protected $last_user_search_run_id;

    /**
     * The value for the date_first_run field.
     *
     * @var        DateTime
     */
    protected $date_first_run;

    /**
     * The value for the date_last_run field.
     *
     * @var        DateTime
     */
    protected $date_last_run;

    /**
     * The value for the date_last_succeeded field.
     *
     * @var        DateTime
     */
    protected $date_last_succeeded;

    /**
     * The value for the date_last_failed field.
     *
     * @var        DateTime
     */
    protected $date_last_failed;

    /**
     * The value for the was_successful field.
     *
     * @var        boolean
     */
    protected $was_successful;

    /**
     * The value for the error_details field.
     *
     * @var        array
     */
    protected $error_details;

    /**
     * The unserialized $error_details value - i.e. the persisted object.
     * This is necessary to avoid repeated calls to unserialize() at runtime.
     * @var object
     */
    protected $error_details_unserialized;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * Initializes internal state of JobScooper\Base\JobSitePluginLastRun object.
     */
    public function __construct()
    {
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
     * Compares this with another <code>JobSitePluginLastRun</code> instance.  If
     * <code>obj</code> is an instance of <code>JobSitePluginLastRun</code>, delegates to
     * <code>equals(JobSitePluginLastRun)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|JobSitePluginLastRun The current object, for fluid interface
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
     * Get the [jobsite] column value.
     *
     * @return string
     */
    public function getJobSite()
    {
        return $this->jobsite;
    }

    /**
     * Get the [last_user_search_run_id] column value.
     *
     * @return int
     */
    public function getLastUserSearchRunId()
    {
        return $this->last_user_search_run_id;
    }

    /**
     * Get the [optionally formatted] temporal [date_first_run] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getFirstRunAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_first_run;
        } else {
            return $this->date_first_run instanceof \DateTimeInterface ? $this->date_first_run->format($format) : null;
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
     * Get the [optionally formatted] temporal [date_last_succeeded] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getLastSucceededAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_last_succeeded;
        } else {
            return $this->date_last_succeeded instanceof \DateTimeInterface ? $this->date_last_succeeded->format($format) : null;
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
     * Get the [was_successful] column value.
     *
     * @return boolean
     */
    public function getWasSuccessful()
    {
        return $this->was_successful;
    }

    /**
     * Get the [was_successful] column value.
     *
     * @return boolean
     */
    public function isWasSuccessful()
    {
        return $this->getWasSuccessful();
    }

    /**
     * Get the [error_details] column value.
     *
     * @return array
     */
    public function getRecentErrorDetails()
    {
        if (null === $this->error_details_unserialized) {
            $this->error_details_unserialized = array();
        }
        if (!$this->error_details_unserialized && null !== $this->error_details) {
            $error_details_unserialized = substr($this->error_details, 2, -2);
            $this->error_details_unserialized = '' !== $error_details_unserialized ? explode(' | ', $error_details_unserialized) : array();
        }

        return $this->error_details_unserialized;
    }

    /**
     * Test the presence of a value in the [error_details] array column value.
     * @param      mixed $value
     *
     * @return boolean
     */
    public function hasRecentErrorDetail($value)
    {
        return in_array($value, $this->getRecentErrorDetails());
    } // hasRecentErrorDetail()

    /**
     * Set the value of [jobsite] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function setJobSite($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->jobsite !== $v) {
            $this->jobsite = $v;
            $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_JOBSITE] = true;
        }

        return $this;
    } // setJobSite()

    /**
     * Set the value of [last_user_search_run_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function setLastUserSearchRunId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->last_user_search_run_id !== $v) {
            $this->last_user_search_run_id = $v;
            $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_LAST_USER_SEARCH_RUN_ID] = true;
        }

        return $this;
    } // setLastUserSearchRunId()

    /**
     * Sets the value of [date_first_run] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function setFirstRunAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_first_run !== null || $dt !== null) {
            if ($this->date_first_run === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_first_run->format("Y-m-d H:i:s.u")) {
                $this->date_first_run = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN] = true;
            }
        } // if either are not null

        return $this;
    } // setFirstRunAt()

    /**
     * Sets the value of [date_last_run] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function setLastRunAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_run !== null || $dt !== null) {
            if ($this->date_last_run === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_run->format("Y-m-d H:i:s.u")) {
                $this->date_last_run = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN] = true;
            }
        } // if either are not null

        return $this;
    } // setLastRunAt()

    /**
     * Sets the value of [date_last_succeeded] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function setLastSucceededAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_succeeded !== null || $dt !== null) {
            if ($this->date_last_succeeded === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_succeeded->format("Y-m-d H:i:s.u")) {
                $this->date_last_succeeded = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_DATE_LAST_SUCCEEDED] = true;
            }
        } // if either are not null

        return $this;
    } // setLastSucceededAt()

    /**
     * Sets the value of [date_last_failed] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function setLastFailedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_failed !== null || $dt !== null) {
            if ($this->date_last_failed === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_failed->format("Y-m-d H:i:s.u")) {
                $this->date_last_failed = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_DATE_LAST_FAILED] = true;
            }
        } // if either are not null

        return $this;
    } // setLastFailedAt()

    /**
     * Sets the value of the [was_successful] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param  boolean|integer|string $v The new value
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function setWasSuccessful($v)
    {
        if ($v !== null) {
            if (is_string($v)) {
                $v = in_array(strtolower($v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                $v = (boolean) $v;
            }
        }

        if ($this->was_successful !== $v) {
            $this->was_successful = $v;
            $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_WAS_SUCCESSFUL] = true;
        }

        return $this;
    } // setWasSuccessful()

    /**
     * Set the value of [error_details] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function setRecentErrorDetails($v)
    {
        if ($this->error_details_unserialized !== $v) {
            $this->error_details_unserialized = $v;
            $this->error_details = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_ERROR_DETAILS] = true;
        }

        return $this;
    } // setRecentErrorDetails()

    /**
     * Adds a value to the [error_details] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function addRecentErrorDetail($value)
    {
        $currentArray = $this->getRecentErrorDetails();
        $currentArray []= $value;
        $this->setRecentErrorDetails($currentArray);

        return $this;
    } // addRecentErrorDetail()

    /**
     * Removes a value from the [error_details] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\JobSitePluginLastRun The current object (for fluent API support)
     */
    public function removeRecentErrorDetail($value)
    {
        $targetArray = array();
        foreach ($this->getRecentErrorDetails() as $element) {
            if ($element != $value) {
                $targetArray []= $element;
            }
        }
        $this->setRecentErrorDetails($targetArray);

        return $this;
    } // removeRecentErrorDetail()

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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : JobSitePluginLastRunTableMap::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobsite = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : JobSitePluginLastRunTableMap::translateFieldName('LastUserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->last_user_search_run_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : JobSitePluginLastRunTableMap::translateFieldName('FirstRunAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_first_run = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : JobSitePluginLastRunTableMap::translateFieldName('LastRunAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_run = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : JobSitePluginLastRunTableMap::translateFieldName('LastSucceededAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_succeeded = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : JobSitePluginLastRunTableMap::translateFieldName('LastFailedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_failed = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : JobSitePluginLastRunTableMap::translateFieldName('WasSuccessful', TableMap::TYPE_PHPNAME, $indexType)];
            $this->was_successful = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : JobSitePluginLastRunTableMap::translateFieldName('RecentErrorDetails', TableMap::TYPE_PHPNAME, $indexType)];
            $this->error_details = $col;
            $this->error_details_unserialized = null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 8; // 8 = JobSitePluginLastRunTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\JobSitePluginLastRun'), 0, $e);
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
            $con = Propel::getServiceContainer()->getReadConnection(JobSitePluginLastRunTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildJobSitePluginLastRunQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see JobSitePluginLastRun::setDeleted()
     * @see JobSitePluginLastRun::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginLastRunTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildJobSitePluginLastRunQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginLastRunTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con, $skipReload) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior

                if (!$this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN)) {
                    $this->setFirstRunAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
                if (!$this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN)) {
                    $this->setLastRunAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN)) {
                    $this->setLastRunAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
            }
            if ($ret) {
                $affectedRows = $this->doSave($con, $skipReload);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                JobSitePluginLastRunTableMap::addInstanceToPool($this);
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

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                    $affectedRows += 1;
                } else {
                    $affectedRows += $this->doUpdate($con);
                    if (!$skipReload) {
                        $reloadObject = true;
                    }
                }
                $this->resetModified();
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
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_JOBSITE)) {
            $modifiedColumns[':p' . $index++]  = 'jobsite';
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_LAST_USER_SEARCH_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'last_user_search_run_id';
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN)) {
            $modifiedColumns[':p' . $index++]  = 'date_first_run';
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_run';
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_LAST_SUCCEEDED)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_succeeded';
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_LAST_FAILED)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_failed';
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_WAS_SUCCESSFUL)) {
            $modifiedColumns[':p' . $index++]  = 'was_successful';
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_ERROR_DETAILS)) {
            $modifiedColumns[':p' . $index++]  = 'error_details';
        }

        $sql = sprintf(
            'INSERT INTO jobsite_plugin_last_run (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'jobsite':
                        $stmt->bindValue($identifier, $this->jobsite, PDO::PARAM_STR);
                        break;
                    case 'last_user_search_run_id':
                        $stmt->bindValue($identifier, $this->last_user_search_run_id, PDO::PARAM_INT);
                        break;
                    case 'date_first_run':
                        $stmt->bindValue($identifier, $this->date_first_run ? $this->date_first_run->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_run':
                        $stmt->bindValue($identifier, $this->date_last_run ? $this->date_last_run->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_succeeded':
                        $stmt->bindValue($identifier, $this->date_last_succeeded ? $this->date_last_succeeded->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_failed':
                        $stmt->bindValue($identifier, $this->date_last_failed ? $this->date_last_failed->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'was_successful':
                        $stmt->bindValue($identifier, $this->was_successful, PDO::PARAM_BOOL);
                        break;
                    case 'error_details':
                        $stmt->bindValue($identifier, $this->error_details, PDO::PARAM_STR);
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
        $pos = JobSitePluginLastRunTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getJobSite();
                break;
            case 1:
                return $this->getLastUserSearchRunId();
                break;
            case 2:
                return $this->getFirstRunAt();
                break;
            case 3:
                return $this->getLastRunAt();
                break;
            case 4:
                return $this->getLastSucceededAt();
                break;
            case 5:
                return $this->getLastFailedAt();
                break;
            case 6:
                return $this->getWasSuccessful();
                break;
            case 7:
                return $this->getRecentErrorDetails();
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
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array())
    {

        if (isset($alreadyDumpedObjects['JobSitePluginLastRun'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['JobSitePluginLastRun'][$this->hashCode()] = true;
        $keys = JobSitePluginLastRunTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getJobSite(),
            $keys[1] => $this->getLastUserSearchRunId(),
            $keys[2] => $this->getFirstRunAt(),
            $keys[3] => $this->getLastRunAt(),
            $keys[4] => $this->getLastSucceededAt(),
            $keys[5] => $this->getLastFailedAt(),
            $keys[6] => $this->getWasSuccessful(),
            $keys[7] => $this->getRecentErrorDetails(),
        );
        if ($result[$keys[2]] instanceof \DateTimeInterface) {
            $result[$keys[2]] = $result[$keys[2]]->format('c');
        }

        if ($result[$keys[3]] instanceof \DateTimeInterface) {
            $result[$keys[3]] = $result[$keys[3]]->format('c');
        }

        if ($result[$keys[4]] instanceof \DateTimeInterface) {
            $result[$keys[4]] = $result[$keys[4]]->format('c');
        }

        if ($result[$keys[5]] instanceof \DateTimeInterface) {
            $result[$keys[5]] = $result[$keys[5]]->format('c');
        }

        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
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
     * @return $this|\JobScooper\JobSitePluginLastRun
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = JobSitePluginLastRunTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\JobSitePluginLastRun
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setJobSite($value);
                break;
            case 1:
                $this->setLastUserSearchRunId($value);
                break;
            case 2:
                $this->setFirstRunAt($value);
                break;
            case 3:
                $this->setLastRunAt($value);
                break;
            case 4:
                $this->setLastSucceededAt($value);
                break;
            case 5:
                $this->setLastFailedAt($value);
                break;
            case 6:
                $this->setWasSuccessful($value);
                break;
            case 7:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setRecentErrorDetails($value);
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
        $keys = JobSitePluginLastRunTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setJobSite($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setLastUserSearchRunId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setFirstRunAt($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setLastRunAt($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setLastSucceededAt($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setLastFailedAt($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setWasSuccessful($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setRecentErrorDetails($arr[$keys[7]]);
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
     * @return $this|\JobScooper\JobSitePluginLastRun The current object, for fluid interface
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
        $criteria = new Criteria(JobSitePluginLastRunTableMap::DATABASE_NAME);

        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_JOBSITE)) {
            $criteria->add(JobSitePluginLastRunTableMap::COL_JOBSITE, $this->jobsite);
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_LAST_USER_SEARCH_RUN_ID)) {
            $criteria->add(JobSitePluginLastRunTableMap::COL_LAST_USER_SEARCH_RUN_ID, $this->last_user_search_run_id);
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN)) {
            $criteria->add(JobSitePluginLastRunTableMap::COL_DATE_FIRST_RUN, $this->date_first_run);
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN)) {
            $criteria->add(JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN, $this->date_last_run);
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_LAST_SUCCEEDED)) {
            $criteria->add(JobSitePluginLastRunTableMap::COL_DATE_LAST_SUCCEEDED, $this->date_last_succeeded);
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_DATE_LAST_FAILED)) {
            $criteria->add(JobSitePluginLastRunTableMap::COL_DATE_LAST_FAILED, $this->date_last_failed);
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_WAS_SUCCESSFUL)) {
            $criteria->add(JobSitePluginLastRunTableMap::COL_WAS_SUCCESSFUL, $this->was_successful);
        }
        if ($this->isColumnModified(JobSitePluginLastRunTableMap::COL_ERROR_DETAILS)) {
            $criteria->add(JobSitePluginLastRunTableMap::COL_ERROR_DETAILS, $this->error_details);
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
        $criteria = ChildJobSitePluginLastRunQuery::create();
        $criteria->add(JobSitePluginLastRunTableMap::COL_JOBSITE, $this->jobsite);

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
        $validPk = null !== $this->getJobSite();

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
        return $this->getJobSite();
    }

    /**
     * Generic method to set the primary key (jobsite column).
     *
     * @param       string $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setJobSite($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getJobSite();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\JobSitePluginLastRun (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setJobSite($this->getJobSite());
        $copyObj->setLastUserSearchRunId($this->getLastUserSearchRunId());
        $copyObj->setFirstRunAt($this->getFirstRunAt());
        $copyObj->setLastRunAt($this->getLastRunAt());
        $copyObj->setLastSucceededAt($this->getLastSucceededAt());
        $copyObj->setLastFailedAt($this->getLastFailedAt());
        $copyObj->setWasSuccessful($this->getWasSuccessful());
        $copyObj->setRecentErrorDetails($this->getRecentErrorDetails());
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
     * @return \JobScooper\JobSitePluginLastRun Clone of current object.
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
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        $this->jobsite = null;
        $this->last_user_search_run_id = null;
        $this->date_first_run = null;
        $this->date_last_run = null;
        $this->date_last_succeeded = null;
        $this->date_last_failed = null;
        $this->was_successful = null;
        $this->error_details = null;
        $this->error_details_unserialized = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
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

    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'jobsite' column
     */
    public function __toString()
    {
        return (string) $this->getJobSite();
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     $this|ChildJobSitePluginLastRun The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[JobSitePluginLastRunTableMap::COL_DATE_LAST_RUN] = true;

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
