<?php

namespace Jobscooper\DataAccess\Base;

use \Exception;
use \PDO;
use Jobscooper\DataAccess\JobLocation as ChildJobLocation;
use Jobscooper\DataAccess\JobLocationQuery as ChildJobLocationQuery;
use Jobscooper\DataAccess\JobPlaceLookup as ChildJobPlaceLookup;
use Jobscooper\DataAccess\JobPlaceLookupQuery as ChildJobPlaceLookupQuery;
use Jobscooper\DataAccess\JobPosting as ChildJobPosting;
use Jobscooper\DataAccess\JobPostingQuery as ChildJobPostingQuery;
use Jobscooper\DataAccess\Map\JobLocationTableMap;
use Jobscooper\DataAccess\Map\JobPlaceLookupTableMap;
use Jobscooper\DataAccess\Map\JobPostingTableMap;
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

/**
 * Base class that represents a row from the 'job_location' table.
 *
 *
 *
 * @package    propel.generator.Jobscooper.DataAccess.Base
 */
abstract class JobLocation implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Jobscooper\\DataAccess\\Map\\JobLocationTableMap';


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
     * The value for the location_id field.
     *
     * @var        int
     */
    protected $location_id;

    /**
     * The value for the lat field.
     *
     * @var        double
     */
    protected $lat;

    /**
     * The value for the lon field.
     *
     * @var        double
     */
    protected $lon;

    /**
     * The value for the full_display_name field.
     *
     * @var        string
     */
    protected $full_display_name;

    /**
     * The value for the primary_name field.
     *
     * @var        string
     */
    protected $primary_name;

    /**
     * The value for the place field.
     *
     * @var        string
     */
    protected $place;

    /**
     * The value for the county field.
     *
     * @var        string
     */
    protected $county;

    /**
     * The value for the state field.
     *
     * @var        string
     */
    protected $state;

    /**
     * The value for the statecode field.
     *
     * @var        string
     */
    protected $statecode;

    /**
     * The value for the country field.
     *
     * @var        string
     */
    protected $country;

    /**
     * The value for the countrycode field.
     *
     * @var        string
     */
    protected $countrycode;

    /**
     * The value for the alternate_names field.
     *
     * @var        array
     */
    protected $alternate_names;

    /**
     * The unserialized $alternate_names value - i.e. the persisted object.
     * This is necessary to avoid repeated calls to unserialize() at runtime.
     * @var object
     */
    protected $alternate_names_unserialized;

    /**
     * The value for the openstreetmap_id field.
     *
     * @var        int
     */
    protected $openstreetmap_id;

    /**
     * @var        ObjectCollection|ChildJobPosting[] Collection to store aggregation of ChildJobPosting objects.
     */
    protected $collJobPostings;
    protected $collJobPostingsPartial;

    /**
     * @var        ObjectCollection|ChildJobPlaceLookup[] Collection to store aggregation of ChildJobPlaceLookup objects.
     */
    protected $collJobPlaceLookups;
    protected $collJobPlaceLookupsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildJobPosting[]
     */
    protected $jobPostingsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildJobPlaceLookup[]
     */
    protected $jobPlaceLookupsScheduledForDeletion = null;

    /**
     * Initializes internal state of Jobscooper\DataAccess\Base\JobLocation object.
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
     * Compares this with another <code>JobLocation</code> instance.  If
     * <code>obj</code> is an instance of <code>JobLocation</code>, delegates to
     * <code>equals(JobLocation)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|JobLocation The current object, for fluid interface
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
     * Get the [location_id] column value.
     *
     * @return int
     */
    public function getLocationId()
    {
        return $this->location_id;
    }

    /**
     * Get the [lat] column value.
     *
     * @return double
     */
    public function getLatitude()
    {
        return $this->lat;
    }

    /**
     * Get the [lon] column value.
     *
     * @return double
     */
    public function getLogitude()
    {
        return $this->lon;
    }

    /**
     * Get the [full_display_name] column value.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->full_display_name;
    }

    /**
     * Get the [primary_name] column value.
     *
     * @return string
     */
    public function getPrimaryName()
    {
        return $this->primary_name;
    }

    /**
     * Get the [place] column value.
     *
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Get the [county] column value.
     *
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Get the [state] column value.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get the [statecode] column value.
     *
     * @return string
     */
    public function getStateCode()
    {
        return $this->statecode;
    }

    /**
     * Get the [country] column value.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get the [countrycode] column value.
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countrycode;
    }

    /**
     * Get the [alternate_names] column value.
     *
     * @return array
     */
    public function getAlternateNames()
    {
        if (null === $this->alternate_names_unserialized) {
            $this->alternate_names_unserialized = array();
        }
        if (!$this->alternate_names_unserialized && null !== $this->alternate_names) {
            $alternate_names_unserialized = substr($this->alternate_names, 2, -2);
            $this->alternate_names_unserialized = '' !== $alternate_names_unserialized ? explode(' | ', $alternate_names_unserialized) : array();
        }

        return $this->alternate_names_unserialized;
    }

    /**
     * Test the presence of a value in the [alternate_names] array column value.
     * @param      mixed $value
     *
     * @return boolean
     */
    public function hasAlternateName($value)
    {
        return in_array($value, $this->getAlternateNames());
    } // hasAlternateName()

    /**
     * Get the [openstreetmap_id] column value.
     *
     * @return int
     */
    public function getOpenStreetMapId()
    {
        return $this->openstreetmap_id;
    }

    /**
     * Set the value of [location_id] column.
     *
     * @param int $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setLocationId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->location_id !== $v) {
            $this->location_id = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_LOCATION_ID] = true;
        }

        return $this;
    } // setLocationId()

    /**
     * Set the value of [lat] column.
     *
     * @param double $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setLatitude($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->lat !== $v) {
            $this->lat = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_LAT] = true;
        }

        return $this;
    } // setLatitude()

    /**
     * Set the value of [lon] column.
     *
     * @param double $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setLogitude($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->lon !== $v) {
            $this->lon = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_LON] = true;
        }

        return $this;
    } // setLogitude()

    /**
     * Set the value of [full_display_name] column.
     *
     * @param string $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setDisplayName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->full_display_name !== $v) {
            $this->full_display_name = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_FULL_DISPLAY_NAME] = true;
        }

        return $this;
    } // setDisplayName()

    /**
     * Set the value of [primary_name] column.
     *
     * @param string $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setPrimaryName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->primary_name !== $v) {
            $this->primary_name = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_PRIMARY_NAME] = true;
        }

        return $this;
    } // setPrimaryName()

    /**
     * Set the value of [place] column.
     *
     * @param string $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setPlace($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->place !== $v) {
            $this->place = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_PLACE] = true;
        }

        return $this;
    } // setPlace()

    /**
     * Set the value of [county] column.
     *
     * @param string $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setCounty($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->county !== $v) {
            $this->county = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_COUNTY] = true;
        }

        return $this;
    } // setCounty()

    /**
     * Set the value of [state] column.
     *
     * @param string $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setState($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->state !== $v) {
            $this->state = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_STATE] = true;
        }

        return $this;
    } // setState()

    /**
     * Set the value of [statecode] column.
     *
     * @param string $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setStateCode($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->statecode !== $v) {
            $this->statecode = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_STATECODE] = true;
        }

        return $this;
    } // setStateCode()

    /**
     * Set the value of [country] column.
     *
     * @param string $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setCountry($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->country !== $v) {
            $this->country = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_COUNTRY] = true;
        }

        return $this;
    } // setCountry()

    /**
     * Set the value of [countrycode] column.
     *
     * @param string $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setCountryCode($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->countrycode !== $v) {
            $this->countrycode = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_COUNTRYCODE] = true;
        }

        return $this;
    } // setCountryCode()

    /**
     * Set the value of [alternate_names] column.
     *
     * @param array $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setAlternateNames($v)
    {
        if ($this->alternate_names_unserialized !== $v) {
            $this->alternate_names_unserialized = $v;
            $this->alternate_names = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[JobLocationTableMap::COL_ALTERNATE_NAMES] = true;
        }

        return $this;
    } // setAlternateNames()

    /**
     * Adds a value to the [alternate_names] array column value.
     * @param  mixed $value
     *
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function addAlternateName($value)
    {
        $currentArray = $this->getAlternateNames();
        $currentArray []= $value;
        $this->setAlternateNames($currentArray);

        return $this;
    } // addAlternateName()

    /**
     * Removes a value from the [alternate_names] array column value.
     * @param  mixed $value
     *
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function removeAlternateName($value)
    {
        $targetArray = array();
        foreach ($this->getAlternateNames() as $element) {
            if ($element != $value) {
                $targetArray []= $element;
            }
        }
        $this->setAlternateNames($targetArray);

        return $this;
    } // removeAlternateName()

    /**
     * Set the value of [openstreetmap_id] column.
     *
     * @param int $v new value
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function setOpenStreetMapId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->openstreetmap_id !== $v) {
            $this->openstreetmap_id = $v;
            $this->modifiedColumns[JobLocationTableMap::COL_OPENSTREETMAP_ID] = true;
        }

        return $this;
    } // setOpenStreetMapId()

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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : JobLocationTableMap::translateFieldName('LocationId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->location_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : JobLocationTableMap::translateFieldName('Latitude', TableMap::TYPE_PHPNAME, $indexType)];
            $this->lat = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : JobLocationTableMap::translateFieldName('Logitude', TableMap::TYPE_PHPNAME, $indexType)];
            $this->lon = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : JobLocationTableMap::translateFieldName('DisplayName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->full_display_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : JobLocationTableMap::translateFieldName('PrimaryName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->primary_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : JobLocationTableMap::translateFieldName('Place', TableMap::TYPE_PHPNAME, $indexType)];
            $this->place = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : JobLocationTableMap::translateFieldName('County', TableMap::TYPE_PHPNAME, $indexType)];
            $this->county = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : JobLocationTableMap::translateFieldName('State', TableMap::TYPE_PHPNAME, $indexType)];
            $this->state = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : JobLocationTableMap::translateFieldName('StateCode', TableMap::TYPE_PHPNAME, $indexType)];
            $this->statecode = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : JobLocationTableMap::translateFieldName('Country', TableMap::TYPE_PHPNAME, $indexType)];
            $this->country = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : JobLocationTableMap::translateFieldName('CountryCode', TableMap::TYPE_PHPNAME, $indexType)];
            $this->countrycode = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 11 + $startcol : JobLocationTableMap::translateFieldName('AlternateNames', TableMap::TYPE_PHPNAME, $indexType)];
            $this->alternate_names = $col;
            $this->alternate_names_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 12 + $startcol : JobLocationTableMap::translateFieldName('OpenStreetMapId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->openstreetmap_id = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 13; // 13 = JobLocationTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Jobscooper\\DataAccess\\JobLocation'), 0, $e);
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
            $con = Propel::getServiceContainer()->getReadConnection(JobLocationTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildJobLocationQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collJobPostings = null;

            $this->collJobPlaceLookups = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see JobLocation::setDeleted()
     * @see JobLocation::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobLocationTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildJobLocationQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(JobLocationTableMap::DATABASE_NAME);
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
                JobLocationTableMap::addInstanceToPool($this);
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
                    foreach ($this->jobPostingsScheduledForDeletion as $jobPosting) {
                        // need to save related object because we set the relation to null
                        $jobPosting->save($con);
                    }
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

            if ($this->jobPlaceLookupsScheduledForDeletion !== null) {
                if (!$this->jobPlaceLookupsScheduledForDeletion->isEmpty()) {
                    \Jobscooper\DataAccess\JobPlaceLookupQuery::create()
                        ->filterByPrimaryKeys($this->jobPlaceLookupsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->jobPlaceLookupsScheduledForDeletion = null;
                }
            }

            if ($this->collJobPlaceLookups !== null) {
                foreach ($this->collJobPlaceLookups as $referrerFK) {
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

        $this->modifiedColumns[JobLocationTableMap::COL_LOCATION_ID] = true;
        if (null !== $this->location_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . JobLocationTableMap::COL_LOCATION_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(JobLocationTableMap::COL_LOCATION_ID)) {
            $modifiedColumns[':p' . $index++]  = 'location_id';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_LAT)) {
            $modifiedColumns[':p' . $index++]  = 'lat';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_LON)) {
            $modifiedColumns[':p' . $index++]  = 'lon';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_FULL_DISPLAY_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'full_display_name';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_PRIMARY_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'primary_name';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_PLACE)) {
            $modifiedColumns[':p' . $index++]  = 'place';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_COUNTY)) {
            $modifiedColumns[':p' . $index++]  = 'county';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_STATE)) {
            $modifiedColumns[':p' . $index++]  = 'state';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_STATECODE)) {
            $modifiedColumns[':p' . $index++]  = 'statecode';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_COUNTRY)) {
            $modifiedColumns[':p' . $index++]  = 'country';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_COUNTRYCODE)) {
            $modifiedColumns[':p' . $index++]  = 'countrycode';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_ALTERNATE_NAMES)) {
            $modifiedColumns[':p' . $index++]  = 'alternate_names';
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_OPENSTREETMAP_ID)) {
            $modifiedColumns[':p' . $index++]  = 'openstreetmap_id';
        }

        $sql = sprintf(
            'INSERT INTO job_location (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'location_id':
                        $stmt->bindValue($identifier, $this->location_id, PDO::PARAM_INT);
                        break;
                    case 'lat':
                        $stmt->bindValue($identifier, $this->lat, PDO::PARAM_STR);
                        break;
                    case 'lon':
                        $stmt->bindValue($identifier, $this->lon, PDO::PARAM_STR);
                        break;
                    case 'full_display_name':
                        $stmt->bindValue($identifier, $this->full_display_name, PDO::PARAM_STR);
                        break;
                    case 'primary_name':
                        $stmt->bindValue($identifier, $this->primary_name, PDO::PARAM_STR);
                        break;
                    case 'place':
                        $stmt->bindValue($identifier, $this->place, PDO::PARAM_STR);
                        break;
                    case 'county':
                        $stmt->bindValue($identifier, $this->county, PDO::PARAM_STR);
                        break;
                    case 'state':
                        $stmt->bindValue($identifier, $this->state, PDO::PARAM_STR);
                        break;
                    case 'statecode':
                        $stmt->bindValue($identifier, $this->statecode, PDO::PARAM_STR);
                        break;
                    case 'country':
                        $stmt->bindValue($identifier, $this->country, PDO::PARAM_STR);
                        break;
                    case 'countrycode':
                        $stmt->bindValue($identifier, $this->countrycode, PDO::PARAM_STR);
                        break;
                    case 'alternate_names':
                        $stmt->bindValue($identifier, $this->alternate_names, PDO::PARAM_STR);
                        break;
                    case 'openstreetmap_id':
                        $stmt->bindValue($identifier, $this->openstreetmap_id, PDO::PARAM_INT);
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
        $this->setLocationId($pk);

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
        $pos = JobLocationTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getLocationId();
                break;
            case 1:
                return $this->getLatitude();
                break;
            case 2:
                return $this->getLogitude();
                break;
            case 3:
                return $this->getDisplayName();
                break;
            case 4:
                return $this->getPrimaryName();
                break;
            case 5:
                return $this->getPlace();
                break;
            case 6:
                return $this->getCounty();
                break;
            case 7:
                return $this->getState();
                break;
            case 8:
                return $this->getStateCode();
                break;
            case 9:
                return $this->getCountry();
                break;
            case 10:
                return $this->getCountryCode();
                break;
            case 11:
                return $this->getAlternateNames();
                break;
            case 12:
                return $this->getOpenStreetMapId();
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

        if (isset($alreadyDumpedObjects['JobLocation'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['JobLocation'][$this->hashCode()] = true;
        $keys = JobLocationTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getLocationId(),
            $keys[1] => $this->getLatitude(),
            $keys[2] => $this->getLogitude(),
            $keys[3] => $this->getDisplayName(),
            $keys[4] => $this->getPrimaryName(),
            $keys[5] => $this->getPlace(),
            $keys[6] => $this->getCounty(),
            $keys[7] => $this->getState(),
            $keys[8] => $this->getStateCode(),
            $keys[9] => $this->getCountry(),
            $keys[10] => $this->getCountryCode(),
            $keys[11] => $this->getAlternateNames(),
            $keys[12] => $this->getOpenStreetMapId(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
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
            if (null !== $this->collJobPlaceLookups) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'jobPlaceLookups';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'job_place_lookups';
                        break;
                    default:
                        $key = 'JobPlaceLookups';
                }

                $result[$key] = $this->collJobPlaceLookups->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\Jobscooper\DataAccess\JobLocation
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = JobLocationTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Jobscooper\DataAccess\JobLocation
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setLocationId($value);
                break;
            case 1:
                $this->setLatitude($value);
                break;
            case 2:
                $this->setLogitude($value);
                break;
            case 3:
                $this->setDisplayName($value);
                break;
            case 4:
                $this->setPrimaryName($value);
                break;
            case 5:
                $this->setPlace($value);
                break;
            case 6:
                $this->setCounty($value);
                break;
            case 7:
                $this->setState($value);
                break;
            case 8:
                $this->setStateCode($value);
                break;
            case 9:
                $this->setCountry($value);
                break;
            case 10:
                $this->setCountryCode($value);
                break;
            case 11:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setAlternateNames($value);
                break;
            case 12:
                $this->setOpenStreetMapId($value);
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
        $keys = JobLocationTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setLocationId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setLatitude($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setLogitude($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setDisplayName($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setPrimaryName($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setPlace($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setCounty($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setState($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setStateCode($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setCountry($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setCountryCode($arr[$keys[10]]);
        }
        if (array_key_exists($keys[11], $arr)) {
            $this->setAlternateNames($arr[$keys[11]]);
        }
        if (array_key_exists($keys[12], $arr)) {
            $this->setOpenStreetMapId($arr[$keys[12]]);
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
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object, for fluid interface
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
        $criteria = new Criteria(JobLocationTableMap::DATABASE_NAME);

        if ($this->isColumnModified(JobLocationTableMap::COL_LOCATION_ID)) {
            $criteria->add(JobLocationTableMap::COL_LOCATION_ID, $this->location_id);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_LAT)) {
            $criteria->add(JobLocationTableMap::COL_LAT, $this->lat);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_LON)) {
            $criteria->add(JobLocationTableMap::COL_LON, $this->lon);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_FULL_DISPLAY_NAME)) {
            $criteria->add(JobLocationTableMap::COL_FULL_DISPLAY_NAME, $this->full_display_name);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_PRIMARY_NAME)) {
            $criteria->add(JobLocationTableMap::COL_PRIMARY_NAME, $this->primary_name);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_PLACE)) {
            $criteria->add(JobLocationTableMap::COL_PLACE, $this->place);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_COUNTY)) {
            $criteria->add(JobLocationTableMap::COL_COUNTY, $this->county);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_STATE)) {
            $criteria->add(JobLocationTableMap::COL_STATE, $this->state);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_STATECODE)) {
            $criteria->add(JobLocationTableMap::COL_STATECODE, $this->statecode);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_COUNTRY)) {
            $criteria->add(JobLocationTableMap::COL_COUNTRY, $this->country);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_COUNTRYCODE)) {
            $criteria->add(JobLocationTableMap::COL_COUNTRYCODE, $this->countrycode);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_ALTERNATE_NAMES)) {
            $criteria->add(JobLocationTableMap::COL_ALTERNATE_NAMES, $this->alternate_names);
        }
        if ($this->isColumnModified(JobLocationTableMap::COL_OPENSTREETMAP_ID)) {
            $criteria->add(JobLocationTableMap::COL_OPENSTREETMAP_ID, $this->openstreetmap_id);
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
        $criteria = ChildJobLocationQuery::create();
        $criteria->add(JobLocationTableMap::COL_LOCATION_ID, $this->location_id);

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
        $validPk = null !== $this->getLocationId();

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
        return $this->getLocationId();
    }

    /**
     * Generic method to set the primary key (location_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setLocationId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getLocationId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Jobscooper\DataAccess\JobLocation (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setLatitude($this->getLatitude());
        $copyObj->setLogitude($this->getLogitude());
        $copyObj->setDisplayName($this->getDisplayName());
        $copyObj->setPrimaryName($this->getPrimaryName());
        $copyObj->setPlace($this->getPlace());
        $copyObj->setCounty($this->getCounty());
        $copyObj->setState($this->getState());
        $copyObj->setStateCode($this->getStateCode());
        $copyObj->setCountry($this->getCountry());
        $copyObj->setCountryCode($this->getCountryCode());
        $copyObj->setAlternateNames($this->getAlternateNames());
        $copyObj->setOpenStreetMapId($this->getOpenStreetMapId());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getJobPostings() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addJobPosting($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getJobPlaceLookups() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addJobPlaceLookup($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setLocationId(NULL); // this is a auto-increment column, so set to default value
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
     * @return \Jobscooper\DataAccess\JobLocation Clone of current object.
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
        if ('JobPlaceLookup' == $relationName) {
            $this->initJobPlaceLookups();
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
        $this->collJobPostings->setModel('\Jobscooper\DataAccess\JobPosting');
    }

    /**
     * Gets an array of ChildJobPosting objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildJobLocation is new, it will return
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
                    ->filterByJobLocation($this)
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
     * @return $this|ChildJobLocation The current object (for fluent API support)
     */
    public function setJobPostings(Collection $jobPostings, ConnectionInterface $con = null)
    {
        /** @var ChildJobPosting[] $jobPostingsToDelete */
        $jobPostingsToDelete = $this->getJobPostings(new Criteria(), $con)->diff($jobPostings);


        $this->jobPostingsScheduledForDeletion = $jobPostingsToDelete;

        foreach ($jobPostingsToDelete as $jobPostingRemoved) {
            $jobPostingRemoved->setJobLocation(null);
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
                ->filterByJobLocation($this)
                ->count($con);
        }

        return count($this->collJobPostings);
    }

    /**
     * Method called to associate a ChildJobPosting object to this object
     * through the ChildJobPosting foreign key attribute.
     *
     * @param  ChildJobPosting $l ChildJobPosting
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
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
        $jobPosting->setJobLocation($this);
    }

    /**
     * @param  ChildJobPosting $jobPosting The ChildJobPosting object to remove.
     * @return $this|ChildJobLocation The current object (for fluent API support)
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
            $this->jobPostingsScheduledForDeletion[]= $jobPosting;
            $jobPosting->setJobLocation(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this JobLocation is new, it will return
     * an empty collection; or if this JobLocation has previously
     * been saved, it will retrieve related JobPostings from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in JobLocation.
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
     * Clears out the collJobPlaceLookups collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addJobPlaceLookups()
     */
    public function clearJobPlaceLookups()
    {
        $this->collJobPlaceLookups = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collJobPlaceLookups collection loaded partially.
     */
    public function resetPartialJobPlaceLookups($v = true)
    {
        $this->collJobPlaceLookupsPartial = $v;
    }

    /**
     * Initializes the collJobPlaceLookups collection.
     *
     * By default this just sets the collJobPlaceLookups collection to an empty array (like clearcollJobPlaceLookups());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initJobPlaceLookups($overrideExisting = true)
    {
        if (null !== $this->collJobPlaceLookups && !$overrideExisting) {
            return;
        }

        $collectionClassName = JobPlaceLookupTableMap::getTableMap()->getCollectionClassName();

        $this->collJobPlaceLookups = new $collectionClassName;
        $this->collJobPlaceLookups->setModel('\Jobscooper\DataAccess\JobPlaceLookup');
    }

    /**
     * Gets an array of ChildJobPlaceLookup objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildJobLocation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildJobPlaceLookup[] List of ChildJobPlaceLookup objects
     * @throws PropelException
     */
    public function getJobPlaceLookups(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collJobPlaceLookupsPartial && !$this->isNew();
        if (null === $this->collJobPlaceLookups || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collJobPlaceLookups) {
                // return empty collection
                $this->initJobPlaceLookups();
            } else {
                $collJobPlaceLookups = ChildJobPlaceLookupQuery::create(null, $criteria)
                    ->filterByJobLocation($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collJobPlaceLookupsPartial && count($collJobPlaceLookups)) {
                        $this->initJobPlaceLookups(false);

                        foreach ($collJobPlaceLookups as $obj) {
                            if (false == $this->collJobPlaceLookups->contains($obj)) {
                                $this->collJobPlaceLookups->append($obj);
                            }
                        }

                        $this->collJobPlaceLookupsPartial = true;
                    }

                    return $collJobPlaceLookups;
                }

                if ($partial && $this->collJobPlaceLookups) {
                    foreach ($this->collJobPlaceLookups as $obj) {
                        if ($obj->isNew()) {
                            $collJobPlaceLookups[] = $obj;
                        }
                    }
                }

                $this->collJobPlaceLookups = $collJobPlaceLookups;
                $this->collJobPlaceLookupsPartial = false;
            }
        }

        return $this->collJobPlaceLookups;
    }

    /**
     * Sets a collection of ChildJobPlaceLookup objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $jobPlaceLookups A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildJobLocation The current object (for fluent API support)
     */
    public function setJobPlaceLookups(Collection $jobPlaceLookups, ConnectionInterface $con = null)
    {
        /** @var ChildJobPlaceLookup[] $jobPlaceLookupsToDelete */
        $jobPlaceLookupsToDelete = $this->getJobPlaceLookups(new Criteria(), $con)->diff($jobPlaceLookups);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->jobPlaceLookupsScheduledForDeletion = clone $jobPlaceLookupsToDelete;

        foreach ($jobPlaceLookupsToDelete as $jobPlaceLookupRemoved) {
            $jobPlaceLookupRemoved->setJobLocation(null);
        }

        $this->collJobPlaceLookups = null;
        foreach ($jobPlaceLookups as $jobPlaceLookup) {
            $this->addJobPlaceLookup($jobPlaceLookup);
        }

        $this->collJobPlaceLookups = $jobPlaceLookups;
        $this->collJobPlaceLookupsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related JobPlaceLookup objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related JobPlaceLookup objects.
     * @throws PropelException
     */
    public function countJobPlaceLookups(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collJobPlaceLookupsPartial && !$this->isNew();
        if (null === $this->collJobPlaceLookups || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collJobPlaceLookups) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getJobPlaceLookups());
            }

            $query = ChildJobPlaceLookupQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByJobLocation($this)
                ->count($con);
        }

        return count($this->collJobPlaceLookups);
    }

    /**
     * Method called to associate a ChildJobPlaceLookup object to this object
     * through the ChildJobPlaceLookup foreign key attribute.
     *
     * @param  ChildJobPlaceLookup $l ChildJobPlaceLookup
     * @return $this|\Jobscooper\DataAccess\JobLocation The current object (for fluent API support)
     */
    public function addJobPlaceLookup(ChildJobPlaceLookup $l)
    {
        if ($this->collJobPlaceLookups === null) {
            $this->initJobPlaceLookups();
            $this->collJobPlaceLookupsPartial = true;
        }

        if (!$this->collJobPlaceLookups->contains($l)) {
            $this->doAddJobPlaceLookup($l);

            if ($this->jobPlaceLookupsScheduledForDeletion and $this->jobPlaceLookupsScheduledForDeletion->contains($l)) {
                $this->jobPlaceLookupsScheduledForDeletion->remove($this->jobPlaceLookupsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildJobPlaceLookup $jobPlaceLookup The ChildJobPlaceLookup object to add.
     */
    protected function doAddJobPlaceLookup(ChildJobPlaceLookup $jobPlaceLookup)
    {
        $this->collJobPlaceLookups[]= $jobPlaceLookup;
        $jobPlaceLookup->setJobLocation($this);
    }

    /**
     * @param  ChildJobPlaceLookup $jobPlaceLookup The ChildJobPlaceLookup object to remove.
     * @return $this|ChildJobLocation The current object (for fluent API support)
     */
    public function removeJobPlaceLookup(ChildJobPlaceLookup $jobPlaceLookup)
    {
        if ($this->getJobPlaceLookups()->contains($jobPlaceLookup)) {
            $pos = $this->collJobPlaceLookups->search($jobPlaceLookup);
            $this->collJobPlaceLookups->remove($pos);
            if (null === $this->jobPlaceLookupsScheduledForDeletion) {
                $this->jobPlaceLookupsScheduledForDeletion = clone $this->collJobPlaceLookups;
                $this->jobPlaceLookupsScheduledForDeletion->clear();
            }
            $this->jobPlaceLookupsScheduledForDeletion[]= clone $jobPlaceLookup;
            $jobPlaceLookup->setJobLocation(null);
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
        $this->location_id = null;
        $this->lat = null;
        $this->lon = null;
        $this->full_display_name = null;
        $this->primary_name = null;
        $this->place = null;
        $this->county = null;
        $this->state = null;
        $this->statecode = null;
        $this->country = null;
        $this->countrycode = null;
        $this->alternate_names = null;
        $this->alternate_names_unserialized = null;
        $this->openstreetmap_id = null;
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
            if ($this->collJobPostings) {
                foreach ($this->collJobPostings as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collJobPlaceLookups) {
                foreach ($this->collJobPlaceLookups as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collJobPostings = null;
        $this->collJobPlaceLookups = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(JobLocationTableMap::DEFAULT_STRING_FORMAT);
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
