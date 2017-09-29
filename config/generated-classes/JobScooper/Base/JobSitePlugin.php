<?php

namespace JobScooper\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\JobSitePluginQuery as ChildJobSitePluginQuery;
use JobScooper\UserSearchRun as ChildUserSearchRun;
use JobScooper\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\Map\JobSitePluginTableMap;
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
 * Base class that represents a row from the 'jobsite_plugin' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.Base
 */
abstract class JobSitePlugin implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\Map\\JobSitePluginTableMap';


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
     * The value for the date_last_run field.
     *
     * @var        DateTime
     */
    protected $date_last_run;

    /**
     * The value for the was_successful field.
     *
     * @var        boolean
     */
    protected $was_successful;

    /**
     * The value for the date_next_run field.
     *
     * @var        DateTime
     */
    protected $date_next_run;

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
     * @var        ChildUserSearchRun
     */
    protected $aUserSearchRun;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * Initializes internal state of JobScooper\Base\JobSitePlugin object.
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
     * Compares this with another <code>JobSitePlugin</code> instance.  If
     * <code>obj</code> is an instance of <code>JobSitePlugin</code>, delegates to
     * <code>equals(JobSitePlugin)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|JobSitePlugin The current object, for fluid interface
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
     * Get the [was_successful] column value.
     *
     * @return boolean
     */
    public function getLastRunWasSuccessful()
    {
        return $this->was_successful;
    }

    /**
     * Get the [was_successful] column value.
     *
     * @return boolean
     */
    public function isLastRunWasSuccessful()
    {
        return $this->getLastRunWasSuccessful();
    }

    /**
     * Get the [optionally formatted] temporal [date_next_run] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getStartNextRunAfter($format = NULL)
    {
        if ($format === null) {
            return $this->date_next_run;
        } else {
            return $this->date_next_run instanceof \DateTimeInterface ? $this->date_next_run->format($format) : null;
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
        $c->addSelectColumn(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID);
        try {
            $dataFetcher = ChildJobSitePluginQuery::create(null, $c)->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
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
        $valueSet = JobSitePluginTableMap::getValueSet(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE);
        if (!isset($valueSet[$this->results_filter_type])) {
            throw new PropelException('Unknown stored enum key: ' . $this->results_filter_type);
        }

        return $valueSet[$this->results_filter_type];
    }

    /**
     * Set the value of [jobsite_key] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     */
    public function setJobSiteKey($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->jobsite_key !== $v) {
            $this->jobsite_key = $v;
            $this->modifiedColumns[JobSitePluginTableMap::COL_JOBSITE_KEY] = true;
        }

        return $this;
    } // setJobSiteKey()

    /**
     * Set the value of [plugin_class_name] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     */
    public function setPluginClassName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->plugin_class_name !== $v) {
            $this->plugin_class_name = $v;
            $this->modifiedColumns[JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME] = true;
        }

        return $this;
    } // setPluginClassName()

    /**
     * Set the value of [display_name] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     */
    public function setDisplayName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->display_name !== $v) {
            $this->display_name = $v;
            $this->modifiedColumns[JobSitePluginTableMap::COL_DISPLAY_NAME] = true;
        }

        return $this;
    } // setDisplayName()

    /**
     * Sets the value of [date_last_run] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     */
    public function setLastRunAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_run !== null || $dt !== null) {
            if ($this->date_last_run === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_run->format("Y-m-d H:i:s.u")) {
                $this->date_last_run = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSitePluginTableMap::COL_DATE_LAST_RUN] = true;
            }
        } // if either are not null

        return $this;
    } // setLastRunAt()

    /**
     * Sets the value of the [was_successful] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param  boolean|integer|string $v The new value
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     */
    public function setLastRunWasSuccessful($v)
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
            $this->modifiedColumns[JobSitePluginTableMap::COL_WAS_SUCCESSFUL] = true;
        }

        return $this;
    } // setLastRunWasSuccessful()

    /**
     * Sets the value of [date_next_run] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     */
    public function setStartNextRunAfter($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_next_run !== null || $dt !== null) {
            if ($this->date_next_run === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_next_run->format("Y-m-d H:i:s.u")) {
                $this->date_next_run = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSitePluginTableMap::COL_DATE_NEXT_RUN] = true;
            }
        } // if either are not null

        return $this;
    } // setStartNextRunAfter()

    /**
     * Sets the value of [date_last_failed] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     */
    public function setLastFailedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_failed !== null || $dt !== null) {
            if ($this->date_last_failed === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_failed->format("Y-m-d H:i:s.u")) {
                $this->date_last_failed = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobSitePluginTableMap::COL_DATE_LAST_FAILED] = true;
            }
        } // if either are not null

        return $this;
    } // setLastFailedAt()

    /**
     * Set the value of [last_user_search_run_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
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
            $this->modifiedColumns[JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID] = true;
        }

        if ($this->aUserSearchRun !== null && $this->aUserSearchRun->getUserSearchRunId() !== $v) {
            $this->aUserSearchRun = null;
        }

        return $this;
    } // setLastUserSearchRunId()

    /**
     * Set the value of [supported_country_codes] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     */
    public function setSupportedCountryCodes($v)
    {
        if ($this->supported_country_codes_unserialized !== $v) {
            $this->supported_country_codes_unserialized = $v;
            $this->supported_country_codes = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES] = true;
        }

        return $this;
    } // setSupportedCountryCodes()

    /**
     * Adds a value to the [supported_country_codes] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
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
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
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
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setResultsFilterType($v)
    {
        if ($v !== null) {
            $valueSet = JobSitePluginTableMap::getValueSet(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE);
            if (!in_array($v, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $v));
            }
            $v = array_search($v, $valueSet);
        }

        if ($this->results_filter_type !== $v) {
            $this->results_filter_type = $v;
            $this->modifiedColumns[JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE] = true;
        }

        return $this;
    } // setResultsFilterType()

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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : JobSitePluginTableMap::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobsite_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : JobSitePluginTableMap::translateFieldName('PluginClassName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->plugin_class_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : JobSitePluginTableMap::translateFieldName('DisplayName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->display_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : JobSitePluginTableMap::translateFieldName('LastRunAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_run = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : JobSitePluginTableMap::translateFieldName('LastRunWasSuccessful', TableMap::TYPE_PHPNAME, $indexType)];
            $this->was_successful = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : JobSitePluginTableMap::translateFieldName('StartNextRunAfter', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_next_run = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : JobSitePluginTableMap::translateFieldName('LastFailedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_failed = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : JobSitePluginTableMap::translateFieldName('SupportedCountryCodes', TableMap::TYPE_PHPNAME, $indexType)];
            $this->supported_country_codes = $col;
            $this->supported_country_codes_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : JobSitePluginTableMap::translateFieldName('ResultsFilterType', TableMap::TYPE_PHPNAME, $indexType)];
            $this->results_filter_type = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 9; // 9 = JobSitePluginTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\JobSitePlugin'), 0, $e);
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
        if ($this->aUserSearchRun !== null && $this->last_user_search_run_id !== $this->aUserSearchRun->getUserSearchRunId()) {
            $this->aUserSearchRun = null;
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
            $con = Propel::getServiceContainer()->getReadConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildJobSitePluginQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
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

            $this->aUserSearchRun = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see JobSitePlugin::setDeleted()
     * @see JobSitePlugin::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildJobSitePluginQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(JobSitePluginTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con, $skipReload) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
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
                JobSitePluginTableMap::addInstanceToPool($this);
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

            if ($this->aUserSearchRun !== null) {
                if ($this->aUserSearchRun->isModified() || $this->aUserSearchRun->isNew()) {
                    $affectedRows += $this->aUserSearchRun->save($con);
                }
                $this->setUserSearchRun($this->aUserSearchRun);
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
        if ($this->isColumnModified(JobSitePluginTableMap::COL_JOBSITE_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'jobsite_key';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'plugin_class_name';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_DISPLAY_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'display_name';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_DATE_LAST_RUN)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_run';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_WAS_SUCCESSFUL)) {
            $modifiedColumns[':p' . $index++]  = 'was_successful';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_DATE_NEXT_RUN)) {
            $modifiedColumns[':p' . $index++]  = 'date_next_run';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_DATE_LAST_FAILED)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_failed';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'last_user_search_run_id';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES)) {
            $modifiedColumns[':p' . $index++]  = 'supported_country_codes';
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE)) {
            $modifiedColumns[':p' . $index++]  = 'results_filter_type';
        }

        $sql = sprintf(
            'INSERT INTO jobsite_plugin (%s) VALUES (%s)',
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
                    case 'date_last_run':
                        $stmt->bindValue($identifier, $this->date_last_run ? $this->date_last_run->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'was_successful':
                        $stmt->bindValue($identifier, $this->was_successful, PDO::PARAM_BOOL);
                        break;
                    case 'date_next_run':
                        $stmt->bindValue($identifier, $this->date_next_run ? $this->date_next_run->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
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
        $pos = JobSitePluginTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getLastRunAt();
                break;
            case 4:
                return $this->getLastRunWasSuccessful();
                break;
            case 5:
                return $this->getStartNextRunAfter();
                break;
            case 6:
                return $this->getLastFailedAt();
                break;
            case 7:
                return $this->getLastUserSearchRunId();
                break;
            case 8:
                return $this->getSupportedCountryCodes();
                break;
            case 9:
                return $this->getResultsFilterType();
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

        if (isset($alreadyDumpedObjects['JobSitePlugin'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['JobSitePlugin'][$this->hashCode()] = true;
        $keys = JobSitePluginTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getJobSiteKey(),
            $keys[1] => $this->getPluginClassName(),
            $keys[2] => $this->getDisplayName(),
            $keys[3] => $this->getLastRunAt(),
            $keys[4] => $this->getLastRunWasSuccessful(),
            $keys[5] => $this->getStartNextRunAfter(),
            $keys[6] => $this->getLastFailedAt(),
            $keys[7] => ($includeLazyLoadColumns) ? $this->getLastUserSearchRunId() : null,
            $keys[8] => $this->getSupportedCountryCodes(),
            $keys[9] => $this->getResultsFilterType(),
        );
        if ($result[$keys[3]] instanceof \DateTimeInterface) {
            $result[$keys[3]] = $result[$keys[3]]->format('c');
        }

        if ($result[$keys[5]] instanceof \DateTimeInterface) {
            $result[$keys[5]] = $result[$keys[5]]->format('c');
        }

        if ($result[$keys[6]] instanceof \DateTimeInterface) {
            $result[$keys[6]] = $result[$keys[6]]->format('c');
        }

        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aUserSearchRun) {

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

                $result[$key] = $this->aUserSearchRun->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
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
     * @return $this|\JobScooper\JobSitePlugin
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = JobSitePluginTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\JobSitePlugin
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
                $this->setLastRunAt($value);
                break;
            case 4:
                $this->setLastRunWasSuccessful($value);
                break;
            case 5:
                $this->setStartNextRunAfter($value);
                break;
            case 6:
                $this->setLastFailedAt($value);
                break;
            case 7:
                $this->setLastUserSearchRunId($value);
                break;
            case 8:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setSupportedCountryCodes($value);
                break;
            case 9:
                $valueSet = JobSitePluginTableMap::getValueSet(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE);
                if (isset($valueSet[$value])) {
                    $value = $valueSet[$value];
                }
                $this->setResultsFilterType($value);
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
        $keys = JobSitePluginTableMap::getFieldNames($keyType);

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
            $this->setLastRunAt($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setLastRunWasSuccessful($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setStartNextRunAfter($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setLastFailedAt($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setLastUserSearchRunId($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setSupportedCountryCodes($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setResultsFilterType($arr[$keys[9]]);
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
     * @return $this|\JobScooper\JobSitePlugin The current object, for fluid interface
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
        $criteria = new Criteria(JobSitePluginTableMap::DATABASE_NAME);

        if ($this->isColumnModified(JobSitePluginTableMap::COL_JOBSITE_KEY)) {
            $criteria->add(JobSitePluginTableMap::COL_JOBSITE_KEY, $this->jobsite_key);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME)) {
            $criteria->add(JobSitePluginTableMap::COL_PLUGIN_CLASS_NAME, $this->plugin_class_name);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_DISPLAY_NAME)) {
            $criteria->add(JobSitePluginTableMap::COL_DISPLAY_NAME, $this->display_name);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_DATE_LAST_RUN)) {
            $criteria->add(JobSitePluginTableMap::COL_DATE_LAST_RUN, $this->date_last_run);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_WAS_SUCCESSFUL)) {
            $criteria->add(JobSitePluginTableMap::COL_WAS_SUCCESSFUL, $this->was_successful);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_DATE_NEXT_RUN)) {
            $criteria->add(JobSitePluginTableMap::COL_DATE_NEXT_RUN, $this->date_next_run);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_DATE_LAST_FAILED)) {
            $criteria->add(JobSitePluginTableMap::COL_DATE_LAST_FAILED, $this->date_last_failed);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID)) {
            $criteria->add(JobSitePluginTableMap::COL_LAST_USER_SEARCH_RUN_ID, $this->last_user_search_run_id);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES)) {
            $criteria->add(JobSitePluginTableMap::COL_SUPPORTED_COUNTRY_CODES, $this->supported_country_codes);
        }
        if ($this->isColumnModified(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE)) {
            $criteria->add(JobSitePluginTableMap::COL_RESULTS_FILTER_TYPE, $this->results_filter_type);
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
        $criteria = ChildJobSitePluginQuery::create();
        $criteria->add(JobSitePluginTableMap::COL_JOBSITE_KEY, $this->jobsite_key);

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
     * @param      object $copyObj An object of \JobScooper\JobSitePlugin (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setJobSiteKey($this->getJobSiteKey());
        $copyObj->setPluginClassName($this->getPluginClassName());
        $copyObj->setDisplayName($this->getDisplayName());
        $copyObj->setLastRunAt($this->getLastRunAt());
        $copyObj->setLastRunWasSuccessful($this->getLastRunWasSuccessful());
        $copyObj->setStartNextRunAfter($this->getStartNextRunAfter());
        $copyObj->setLastFailedAt($this->getLastFailedAt());
        $copyObj->setLastUserSearchRunId($this->getLastUserSearchRunId());
        $copyObj->setSupportedCountryCodes($this->getSupportedCountryCodes());
        $copyObj->setResultsFilterType($this->getResultsFilterType());
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
     * @return \JobScooper\JobSitePlugin Clone of current object.
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
     * @return $this|\JobScooper\JobSitePlugin The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUserSearchRun(ChildUserSearchRun $v = null)
    {
        if ($v === null) {
            $this->setLastUserSearchRunId(NULL);
        } else {
            $this->setLastUserSearchRunId($v->getUserSearchRunId());
        }

        $this->aUserSearchRun = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUserSearchRun object, it will not be re-added.
        if ($v !== null) {
            $v->addJobSitePlugin($this);
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
    public function getUserSearchRun(ConnectionInterface $con = null)
    {
        if ($this->aUserSearchRun === null && ($this->last_user_search_run_id != 0)) {
            $this->aUserSearchRun = ChildUserSearchRunQuery::create()->findPk($this->last_user_search_run_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUserSearchRun->addJobSitePlugins($this);
             */
        }

        return $this->aUserSearchRun;
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aUserSearchRun) {
            $this->aUserSearchRun->removeJobSitePlugin($this);
        }
        $this->jobsite_key = null;
        $this->plugin_class_name = null;
        $this->display_name = null;
        $this->date_last_run = null;
        $this->was_successful = null;
        $this->date_next_run = null;
        $this->date_last_failed = null;
        $this->last_user_search_run_id = null;
        $this->last_user_search_run_id_isLoaded = false;
        $this->supported_country_codes = null;
        $this->supported_country_codes_unserialized = null;
        $this->results_filter_type = null;
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

        $this->aUserSearchRun = null;
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
