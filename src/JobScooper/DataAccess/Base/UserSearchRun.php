<?php

namespace JobScooper\DataAccess\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\DataAccess\GeoLocation as ChildGeoLocation;
use JobScooper\DataAccess\GeoLocationQuery as ChildGeoLocationQuery;
use JobScooper\DataAccess\JobSitePlugin as ChildJobSitePlugin;
use JobScooper\DataAccess\JobSitePluginQuery as ChildJobSitePluginQuery;
use JobScooper\DataAccess\User as ChildUser;
use JobScooper\DataAccess\UserQuery as ChildUserQuery;
use JobScooper\DataAccess\UserSearchRun as ChildUserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\DataAccess\Map\UserSearchRunTableMap;
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
     * The value for the search_key field.
     *
     * @var        string
     */
    protected $search_key;

    /**
     * The value for the user_slug field.
     *
     * @var        string
     */
    protected $user_slug;

    /**
     * The value for the geolocation_id field.
     *
     * @var        int
     */
    protected $geolocation_id;

    /**
     * The value for the jobsite_key field.
     *
     * @var        string
     */
    protected $jobsite_key;

    /**
     * The value for the user_search_run_key field.
     *
     * @var        string
     */
    protected $user_search_run_key;

    /**
     * The value for the search_parameters_data field.
     *
     * @var        string
     */
    protected $search_parameters_data;

    /**
     * The value for the last_app_run_id field.
     *
     * @var        string
     */
    protected $last_app_run_id;

    /**
     * The value for the run_result field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $run_result;

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
     * The value for the date_created field.
     *
     * @var        DateTime
     */
    protected $date_created;

    /**
     * The value for the date_last_run field.
     *
     * @var        DateTime
     */
    protected $date_last_run;

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
     * @var        ChildGeoLocation
     */
    protected $aGeoLocation;

    /**
     * @var        ChildJobSitePlugin
     */
    protected $aJobSitePlugin;

    /**
     * @var        ChildUser
     */
    protected $aUser;

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
        $this->run_result = 0;
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
     * Get the [search_key] column value.
     *
     * @return string
     */
    public function getSearchKey()
    {
        return $this->search_key;
    }

    /**
     * Get the [user_slug] column value.
     *
     * @return string
     */
    public function getUserSlug()
    {
        return $this->user_slug;
    }

    /**
     * Get the [geolocation_id] column value.
     *
     * @return int
     */
    public function getGeoLocationId()
    {
        return $this->geolocation_id;
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
     * Get the [user_search_run_key] column value.
     *
     * @return string
     */
    public function getUserSearchRunKey()
    {
        return $this->user_search_run_key;
    }

    /**
     * Get the [search_parameters_data] column value.
     *
     * @return string
     */
    public function getSearchParametersData()
    {
        return $this->search_parameters_data;
    }

    /**
     * Get the [last_app_run_id] column value.
     *
     * @return string
     */
    public function getAppRunId()
    {
        return $this->last_app_run_id;
    }

    /**
     * Get the [run_result] column value.
     *
     * @return string
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getRunResultCode()
    {
        if (null === $this->run_result) {
            return null;
        }
        $valueSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT);
        if (!isset($valueSet[$this->run_result])) {
            throw new PropelException('Unknown stored enum key: ' . $this->run_result);
        }

        return $valueSet[$this->run_result];
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
     * Get the [optionally formatted] temporal [date_created] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_created;
        } else {
            return $this->date_created instanceof \DateTimeInterface ? $this->date_created->format($format) : null;
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
     * Set the value of [search_key] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setSearchKey($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->search_key !== $v) {
            $this->search_key = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_SEARCH_KEY] = true;
        }

        return $this;
    } // setSearchKey()

    /**
     * Set the value of [user_slug] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setUserSlug($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->user_slug !== $v) {
            $this->user_slug = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_USER_SLUG] = true;
        }

        if ($this->aUser !== null && $this->aUser->getUserSlug() !== $v) {
            $this->aUser = null;
        }

        return $this;
    } // setUserSlug()

    /**
     * Set the value of [geolocation_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setGeoLocationId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->geolocation_id !== $v) {
            $this->geolocation_id = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_GEOLOCATION_ID] = true;
        }

        if ($this->aGeoLocation !== null && $this->aGeoLocation->getGeoLocationId() !== $v) {
            $this->aGeoLocation = null;
        }

        return $this;
    } // setGeoLocationId()

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

        if ($this->aJobSitePlugin !== null && $this->aJobSitePlugin->getJobSiteKey() !== $v) {
            $this->aJobSitePlugin = null;
        }

        return $this;
    } // setJobSiteKey()

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
     * Set the value of [search_parameters_data] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setSearchParametersData($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->search_parameters_data !== $v) {
            $this->search_parameters_data = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_SEARCH_PARAMETERS_DATA] = true;
        }

        return $this;
    } // setSearchParametersData()

    /**
     * Set the value of [last_app_run_id] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setAppRunId($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->last_app_run_id !== $v) {
            $this->last_app_run_id = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_LAST_APP_RUN_ID] = true;
        }

        return $this;
    } // setAppRunId()

    /**
     * Set the value of [run_result] column.
     *
     * @param  string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setRunResultCode($v)
    {
        if ($v !== null) {
            $valueSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT);
            if (!in_array($v, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $v));
            }
            $v = array_search($v, $valueSet);
        }

        if ($this->run_result !== $v) {
            $this->run_result = $v;
            $this->modifiedColumns[UserSearchRunTableMap::COL_RUN_RESULT] = true;
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
     * Sets the value of [date_created] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_created !== null || $dt !== null) {
            if ($this->date_created === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_created->format("Y-m-d H:i:s.u")) {
                $this->date_created = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchRunTableMap::COL_DATE_CREATED] = true;
            }
        } // if either are not null

        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [date_last_run] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setLastRunAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_run !== null || $dt !== null) {
            if ($this->date_last_run === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_run->format("Y-m-d H:i:s.u")) {
                $this->date_last_run = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchRunTableMap::COL_DATE_LAST_RUN] = true;
            }
        } // if either are not null

        return $this;
    } // setLastRunAt()

    /**
     * Sets the value of [date_next_run] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setStartNextRunAfter($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_next_run !== null || $dt !== null) {
            if ($this->date_next_run === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_next_run->format("Y-m-d H:i:s.u")) {
                $this->date_next_run = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchRunTableMap::COL_DATE_NEXT_RUN] = true;
            }
        } // if either are not null

        return $this;
    } // setStartNextRunAfter()

    /**
     * Sets the value of [date_last_failed] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     */
    public function setLastFailedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_failed !== null || $dt !== null) {
            if ($this->date_last_failed === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_failed->format("Y-m-d H:i:s.u")) {
                $this->date_last_failed = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchRunTableMap::COL_DATE_LAST_FAILED] = true;
            }
        } // if either are not null

        return $this;
    } // setLastFailedAt()

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
            if ($this->run_result !== 0) {
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserSearchRunTableMap::translateFieldName('SearchKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->search_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserSearchRunTableMap::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_slug = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserSearchRunTableMap::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->geolocation_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : UserSearchRunTableMap::translateFieldName('JobSiteKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobsite_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : UserSearchRunTableMap::translateFieldName('UserSearchRunKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_run_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : UserSearchRunTableMap::translateFieldName('SearchParametersData', TableMap::TYPE_PHPNAME, $indexType)];
            $this->search_parameters_data = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : UserSearchRunTableMap::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->last_app_run_id = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : UserSearchRunTableMap::translateFieldName('RunResultCode', TableMap::TYPE_PHPNAME, $indexType)];
            $this->run_result = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : UserSearchRunTableMap::translateFieldName('RunErrorDetails', TableMap::TYPE_PHPNAME, $indexType)];
            $this->run_error_details = $col;
            $this->run_error_details_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : UserSearchRunTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_created = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 11 + $startcol : UserSearchRunTableMap::translateFieldName('LastRunAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_run = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 12 + $startcol : UserSearchRunTableMap::translateFieldName('StartNextRunAfter', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_next_run = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 13 + $startcol : UserSearchRunTableMap::translateFieldName('LastFailedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_failed = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 14; // 14 = UserSearchRunTableMap::NUM_HYDRATE_COLUMNS.

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
        if ($this->aUser !== null && $this->user_slug !== $this->aUser->getUserSlug()) {
            $this->aUser = null;
        }
        if ($this->aGeoLocation !== null && $this->geolocation_id !== $this->aGeoLocation->getGeoLocationId()) {
            $this->aGeoLocation = null;
        }
        if ($this->aJobSitePlugin !== null && $this->jobsite_key !== $this->aJobSitePlugin->getJobSiteKey()) {
            $this->aJobSitePlugin = null;
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

            $this->aGeoLocation = null;
            $this->aJobSitePlugin = null;
            $this->aUser = null;
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
                // timestampable behavior

                if (!$this->isColumnModified(UserSearchRunTableMap::COL_DATE_CREATED)) {
                    $this->setCreatedAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
                if (!$this->isColumnModified(UserSearchRunTableMap::COL_DATE_LAST_RUN)) {
                    $this->setLastRunAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(UserSearchRunTableMap::COL_DATE_LAST_RUN)) {
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

            if ($this->aGeoLocation !== null) {
                if ($this->aGeoLocation->isModified() || $this->aGeoLocation->isNew()) {
                    $affectedRows += $this->aGeoLocation->save($con);
                }
                $this->setGeoLocation($this->aGeoLocation);
            }

            if ($this->aJobSitePlugin !== null) {
                if ($this->aJobSitePlugin->isModified() || $this->aJobSitePlugin->isNew()) {
                    $affectedRows += $this->aJobSitePlugin->save($con);
                }
                $this->setJobSitePlugin($this->aJobSitePlugin);
            }

            if ($this->aUser !== null) {
                if ($this->aUser->isModified() || $this->aUser->isNew()) {
                    $affectedRows += $this->aUser->save($con);
                }
                $this->setUser($this->aUser);
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

        $this->modifiedColumns[UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID] = true;
        if (null !== $this->user_search_run_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_run_id';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_SEARCH_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'search_key';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SLUG)) {
            $modifiedColumns[':p' . $index++]  = 'user_slug';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_GEOLOCATION_ID)) {
            $modifiedColumns[':p' . $index++]  = 'geolocation_id';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_JOBSITE_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'jobsite_key';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_run_key';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_SEARCH_PARAMETERS_DATA)) {
            $modifiedColumns[':p' . $index++]  = 'search_parameters_data';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_LAST_APP_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'last_app_run_id';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_RUN_RESULT)) {
            $modifiedColumns[':p' . $index++]  = 'run_result';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS)) {
            $modifiedColumns[':p' . $index++]  = 'run_error_details';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_CREATED)) {
            $modifiedColumns[':p' . $index++]  = 'date_created';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_LAST_RUN)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_run';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_NEXT_RUN)) {
            $modifiedColumns[':p' . $index++]  = 'date_next_run';
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_LAST_FAILED)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_failed';
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
                    case 'search_key':
                        $stmt->bindValue($identifier, $this->search_key, PDO::PARAM_STR);
                        break;
                    case 'user_slug':
                        $stmt->bindValue($identifier, $this->user_slug, PDO::PARAM_STR);
                        break;
                    case 'geolocation_id':
                        $stmt->bindValue($identifier, $this->geolocation_id, PDO::PARAM_INT);
                        break;
                    case 'jobsite_key':
                        $stmt->bindValue($identifier, $this->jobsite_key, PDO::PARAM_STR);
                        break;
                    case 'user_search_run_key':
                        $stmt->bindValue($identifier, $this->user_search_run_key, PDO::PARAM_STR);
                        break;
                    case 'search_parameters_data':
                        $stmt->bindValue($identifier, $this->search_parameters_data, PDO::PARAM_STR);
                        break;
                    case 'last_app_run_id':
                        $stmt->bindValue($identifier, $this->last_app_run_id, PDO::PARAM_STR);
                        break;
                    case 'run_result':
                        $stmt->bindValue($identifier, $this->run_result, PDO::PARAM_INT);
                        break;
                    case 'run_error_details':
                        $stmt->bindValue($identifier, $this->run_error_details, PDO::PARAM_STR);
                        break;
                    case 'date_created':
                        $stmt->bindValue($identifier, $this->date_created ? $this->date_created->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_run':
                        $stmt->bindValue($identifier, $this->date_last_run ? $this->date_last_run->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_next_run':
                        $stmt->bindValue($identifier, $this->date_next_run ? $this->date_next_run->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_failed':
                        $stmt->bindValue($identifier, $this->date_last_failed ? $this->date_last_failed->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
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
                return $this->getSearchKey();
                break;
            case 2:
                return $this->getUserSlug();
                break;
            case 3:
                return $this->getGeoLocationId();
                break;
            case 4:
                return $this->getJobSiteKey();
                break;
            case 5:
                return $this->getUserSearchRunKey();
                break;
            case 6:
                return $this->getSearchParametersData();
                break;
            case 7:
                return $this->getAppRunId();
                break;
            case 8:
                return $this->getRunResultCode();
                break;
            case 9:
                return $this->getRunErrorDetails();
                break;
            case 10:
                return $this->getCreatedAt();
                break;
            case 11:
                return $this->getLastRunAt();
                break;
            case 12:
                return $this->getStartNextRunAfter();
                break;
            case 13:
                return $this->getLastFailedAt();
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
        $result = array(
            $keys[0] => $this->getUserSearchRunId(),
            $keys[1] => $this->getSearchKey(),
            $keys[2] => $this->getUserSlug(),
            $keys[3] => $this->getGeoLocationId(),
            $keys[4] => $this->getJobSiteKey(),
            $keys[5] => $this->getUserSearchRunKey(),
            $keys[6] => $this->getSearchParametersData(),
            $keys[7] => $this->getAppRunId(),
            $keys[8] => $this->getRunResultCode(),
            $keys[9] => $this->getRunErrorDetails(),
            $keys[10] => $this->getCreatedAt(),
            $keys[11] => $this->getLastRunAt(),
            $keys[12] => $this->getStartNextRunAfter(),
            $keys[13] => $this->getLastFailedAt(),
        );
        if ($result[$keys[10]] instanceof \DateTimeInterface) {
            $result[$keys[10]] = $result[$keys[10]]->format('c');
        }

        if ($result[$keys[11]] instanceof \DateTimeInterface) {
            $result[$keys[11]] = $result[$keys[11]]->format('c');
        }

        if ($result[$keys[12]] instanceof \DateTimeInterface) {
            $result[$keys[12]] = $result[$keys[12]]->format('c');
        }

        if ($result[$keys[13]] instanceof \DateTimeInterface) {
            $result[$keys[13]] = $result[$keys[13]]->format('c');
        }

        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aGeoLocation) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'geoLocation';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'geolocation';
                        break;
                    default:
                        $key = 'GeoLocation';
                }

                $result[$key] = $this->aGeoLocation->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aJobSitePlugin) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'jobSitePlugin';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'jobsite_plugin';
                        break;
                    default:
                        $key = 'JobSitePlugin';
                }

                $result[$key] = $this->aJobSitePlugin->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aUser) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'user';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user';
                        break;
                    default:
                        $key = 'User';
                }

                $result[$key] = $this->aUser->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
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
                $this->setSearchKey($value);
                break;
            case 2:
                $this->setUserSlug($value);
                break;
            case 3:
                $this->setGeoLocationId($value);
                break;
            case 4:
                $this->setJobSiteKey($value);
                break;
            case 5:
                $this->setUserSearchRunKey($value);
                break;
            case 6:
                $this->setSearchParametersData($value);
                break;
            case 7:
                $this->setAppRunId($value);
                break;
            case 8:
                $valueSet = UserSearchRunTableMap::getValueSet(UserSearchRunTableMap::COL_RUN_RESULT);
                if (isset($valueSet[$value])) {
                    $value = $valueSet[$value];
                }
                $this->setRunResultCode($value);
                break;
            case 9:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setRunErrorDetails($value);
                break;
            case 10:
                $this->setCreatedAt($value);
                break;
            case 11:
                $this->setLastRunAt($value);
                break;
            case 12:
                $this->setStartNextRunAfter($value);
                break;
            case 13:
                $this->setLastFailedAt($value);
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
            $this->setSearchKey($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setUserSlug($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setGeoLocationId($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setJobSiteKey($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setUserSearchRunKey($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setSearchParametersData($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setAppRunId($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setRunResultCode($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setRunErrorDetails($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setCreatedAt($arr[$keys[10]]);
        }
        if (array_key_exists($keys[11], $arr)) {
            $this->setLastRunAt($arr[$keys[11]]);
        }
        if (array_key_exists($keys[12], $arr)) {
            $this->setStartNextRunAfter($arr[$keys[12]]);
        }
        if (array_key_exists($keys[13], $arr)) {
            $this->setLastFailedAt($arr[$keys[13]]);
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
        if ($this->isColumnModified(UserSearchRunTableMap::COL_SEARCH_KEY)) {
            $criteria->add(UserSearchRunTableMap::COL_SEARCH_KEY, $this->search_key);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SLUG)) {
            $criteria->add(UserSearchRunTableMap::COL_USER_SLUG, $this->user_slug);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_GEOLOCATION_ID)) {
            $criteria->add(UserSearchRunTableMap::COL_GEOLOCATION_ID, $this->geolocation_id);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_JOBSITE_KEY)) {
            $criteria->add(UserSearchRunTableMap::COL_JOBSITE_KEY, $this->jobsite_key);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY)) {
            $criteria->add(UserSearchRunTableMap::COL_USER_SEARCH_RUN_KEY, $this->user_search_run_key);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_SEARCH_PARAMETERS_DATA)) {
            $criteria->add(UserSearchRunTableMap::COL_SEARCH_PARAMETERS_DATA, $this->search_parameters_data);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_LAST_APP_RUN_ID)) {
            $criteria->add(UserSearchRunTableMap::COL_LAST_APP_RUN_ID, $this->last_app_run_id);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_RUN_RESULT)) {
            $criteria->add(UserSearchRunTableMap::COL_RUN_RESULT, $this->run_result);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS)) {
            $criteria->add(UserSearchRunTableMap::COL_RUN_ERROR_DETAILS, $this->run_error_details);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_CREATED)) {
            $criteria->add(UserSearchRunTableMap::COL_DATE_CREATED, $this->date_created);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_LAST_RUN)) {
            $criteria->add(UserSearchRunTableMap::COL_DATE_LAST_RUN, $this->date_last_run);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_NEXT_RUN)) {
            $criteria->add(UserSearchRunTableMap::COL_DATE_NEXT_RUN, $this->date_next_run);
        }
        if ($this->isColumnModified(UserSearchRunTableMap::COL_DATE_LAST_FAILED)) {
            $criteria->add(UserSearchRunTableMap::COL_DATE_LAST_FAILED, $this->date_last_failed);
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
        $copyObj->setSearchKey($this->getSearchKey());
        $copyObj->setUserSlug($this->getUserSlug());
        $copyObj->setGeoLocationId($this->getGeoLocationId());
        $copyObj->setJobSiteKey($this->getJobSiteKey());
        $copyObj->setUserSearchRunKey($this->getUserSearchRunKey());
        $copyObj->setSearchParametersData($this->getSearchParametersData());
        $copyObj->setAppRunId($this->getAppRunId());
        $copyObj->setRunResultCode($this->getRunResultCode());
        $copyObj->setRunErrorDetails($this->getRunErrorDetails());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setLastRunAt($this->getLastRunAt());
        $copyObj->setStartNextRunAfter($this->getStartNextRunAfter());
        $copyObj->setLastFailedAt($this->getLastFailedAt());
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
     * Declares an association between this object and a ChildGeoLocation object.
     *
     * @param  ChildGeoLocation $v
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     * @throws PropelException
     */
    public function setGeoLocation(ChildGeoLocation $v = null)
    {
        if ($v === null) {
            $this->setGeoLocationId(NULL);
        } else {
            $this->setGeoLocationId($v->getGeoLocationId());
        }

        $this->aGeoLocation = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildGeoLocation object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearchRun($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildGeoLocation object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildGeoLocation The associated ChildGeoLocation object.
     * @throws PropelException
     */
    public function getGeoLocation(ConnectionInterface $con = null)
    {
        if ($this->aGeoLocation === null && ($this->geolocation_id != 0)) {
            $this->aGeoLocation = ChildGeoLocationQuery::create()->findPk($this->geolocation_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aGeoLocation->addUserSearchRuns($this);
             */
        }

        return $this->aGeoLocation;
    }

    /**
     * Declares an association between this object and a ChildJobSitePlugin object.
     *
     * @param  ChildJobSitePlugin $v
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     * @throws PropelException
     */
    public function setJobSitePlugin(ChildJobSitePlugin $v = null)
    {
        if ($v === null) {
            $this->setJobSiteKey(NULL);
        } else {
            $this->setJobSiteKey($v->getJobSiteKey());
        }

        $this->aJobSitePlugin = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildJobSitePlugin object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearchRun($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildJobSitePlugin object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildJobSitePlugin The associated ChildJobSitePlugin object.
     * @throws PropelException
     */
    public function getJobSitePlugin(ConnectionInterface $con = null)
    {
        if ($this->aJobSitePlugin === null && (($this->jobsite_key !== "" && $this->jobsite_key !== null))) {
            $this->aJobSitePlugin = ChildJobSitePluginQuery::create()->findPk($this->jobsite_key, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aJobSitePlugin->addUserSearchRuns($this);
             */
        }

        return $this->aJobSitePlugin;
    }

    /**
     * Declares an association between this object and a ChildUser object.
     *
     * @param  ChildUser $v
     * @return $this|\JobScooper\DataAccess\UserSearchRun The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUser(ChildUser $v = null)
    {
        if ($v === null) {
            $this->setUserSlug(NULL);
        } else {
            $this->setUserSlug($v->getUserSlug());
        }

        $this->aUser = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUser object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearchRun($this);
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
    public function getUser(ConnectionInterface $con = null)
    {
        if ($this->aUser === null && (($this->user_slug !== "" && $this->user_slug !== null))) {
            $this->aUser = ChildUserQuery::create()->findPk($this->user_slug, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUser->addUserSearchRuns($this);
             */
        }

        return $this->aUser;
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aGeoLocation) {
            $this->aGeoLocation->removeUserSearchRun($this);
        }
        if (null !== $this->aJobSitePlugin) {
            $this->aJobSitePlugin->removeUserSearchRun($this);
        }
        if (null !== $this->aUser) {
            $this->aUser->removeUserSearchRun($this);
        }
        $this->user_search_run_id = null;
        $this->search_key = null;
        $this->user_slug = null;
        $this->geolocation_id = null;
        $this->jobsite_key = null;
        $this->user_search_run_key = null;
        $this->search_parameters_data = null;
        $this->last_app_run_id = null;
        $this->run_result = null;
        $this->run_error_details = null;
        $this->run_error_details_unserialized = null;
        $this->date_created = null;
        $this->date_last_run = null;
        $this->date_next_run = null;
        $this->date_last_failed = null;
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

        $this->aGeoLocation = null;
        $this->aJobSitePlugin = null;
        $this->aUser = null;
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
        return '' . $this->cleanupSlugPart($this->getJobSiteKey()) . '-' . $this->cleanupSlugPart($this->getUserSlug()) . '-' . $this->cleanupSlugPart($this->getSearchKey()) . '-' . $this->cleanupSlugPart($this->getGeoLocationId()) . '';
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

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     $this|ChildUserSearchRun The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[UserSearchRunTableMap::COL_DATE_LAST_RUN] = true;

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
