<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\GeoLocation as ChildGeoLocation;
use JobScooper\DataAccess\GeoLocationQuery as ChildGeoLocationQuery;
use JobScooper\DataAccess\JobPosting as ChildJobPosting;
use JobScooper\DataAccess\JobPostingQuery as ChildJobPostingQuery;
use JobScooper\DataAccess\JobSiteRecord as ChildJobSiteRecord;
use JobScooper\DataAccess\UserKeywordSet as ChildUserKeywordSet;
use JobScooper\DataAccess\UserKeywordSetQuery as ChildUserKeywordSetQuery;
use JobScooper\DataAccess\UserSearch as ChildUserSearch;
use JobScooper\DataAccess\UserSearchQuery as ChildUserSearchQuery;
use JobScooper\DataAccess\UserSearchSiteRun as ChildUserSearchSiteRun;
use JobScooper\DataAccess\UserSearchSiteRunQuery as ChildUserSearchSiteRunQuery;
use JobScooper\DataAccess\Map\GeoLocationTableMap;
use JobScooper\DataAccess\Map\JobPostingTableMap;
use JobScooper\DataAccess\Map\UserSearchSiteRunTableMap;
use JobScooper\DataAccess\Map\UserSearchTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Collection\ObjectCombinationCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;

/**
 * Base class that represents a row from the 'geolocation' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.DataAccess.Base
 */
abstract class GeoLocation implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\DataAccess\\Map\\GeoLocationTableMap';


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
     * The value for the geolocation_id field.
     *
     * @var        int
     */
    protected $geolocation_id;

    /**
     * The value for the display_name field.
     *
     * @var        string
     */
    protected $display_name;

    /**
     * The value for the geolocation_key field.
     *
     * @var        string
     */
    protected $geolocation_key;

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
     * The value for the region field.
     *
     * @var        string
     */
    protected $region;

    /**
     * The value for the regioncode field.
     *
     * @var        string
     */
    protected $regioncode;

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
     * The value for the latitude field.
     *
     * @var        double
     */
    protected $latitude;

    /**
     * The value for the longitude field.
     *
     * @var        double
     */
    protected $longitude;

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
     * @var        ObjectCollection|ChildJobPosting[] Collection to store aggregation of ChildJobPosting objects.
     */
    protected $collJobPostings;
    protected $collJobPostingsPartial;

    /**
     * @var        ObjectCollection|ChildUserSearch[] Collection to store aggregation of ChildUserSearch objects.
     */
    protected $collUserSearches;
    protected $collUserSearchesPartial;

    /**
     * @var        ObjectCollection|ChildUserSearchSiteRun[] Collection to store aggregation of ChildUserSearchSiteRun objects.
     */
    protected $collUserSearchSiteRuns;
    protected $collUserSearchSiteRunsPartial;

    /**
     * @var        ObjectCollection|ChildUserKeywordSet[] Cross Collection to store aggregation of ChildUserKeywordSet objects.
     */
    protected $collUserKeywordSetFromuses;

    /**
     * @var bool
     */
    protected $collUserKeywordSetFromusesPartial;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildUserSearch, ChildJobSiteRecord combination combinations.
     */
    protected $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds;

    /**
     * @var bool
     */
    protected $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsPartial;

    /**
     * @var        ObjectCollection|ChildUserSearch[] Cross Collection to store aggregation of ChildUserSearch objects.
     */
    protected $collUserSearchFromUSSRs;

    /**
     * @var bool
     */
    protected $collUserSearchFromUSSRsPartial;

    /**
     * @var        ObjectCollection|ChildJobSiteRecord[] Cross Collection to store aggregation of ChildJobSiteRecord objects.
     */
    protected $collJobSiteFromUSSRs;

    /**
     * @var bool
     */
    protected $collJobSiteFromUSSRsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserKeywordSet[]
     */
    protected $userKeywordSetFromusesScheduledForDeletion = null;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildUserSearch, ChildJobSiteRecord combination combinations.
     */
    protected $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildJobPosting[]
     */
    protected $jobPostingsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserSearch[]
     */
    protected $userSearchesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserSearchSiteRun[]
     */
    protected $userSearchSiteRunsScheduledForDeletion = null;

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\GeoLocation object.
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
     * Compares this with another <code>GeoLocation</code> instance.  If
     * <code>obj</code> is an instance of <code>GeoLocation</code>, delegates to
     * <code>equals(GeoLocation)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|GeoLocation The current object, for fluid interface
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
     * Get the [geolocation_id] column value.
     *
     * @return int
     */
    public function getGeoLocationId()
    {
        return $this->geolocation_id;
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
     * Get the [geolocation_key] column value.
     *
     * @return string
     */
    public function getGeoLocationKey()
    {
        return $this->geolocation_key;
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
     * Get the [region] column value.
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Get the [regioncode] column value.
     *
     * @return string
     */
    public function getRegionCode()
    {
        return $this->regioncode;
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
     * Get the [latitude] column value.
     *
     * @return double
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Get the [longitude] column value.
     *
     * @return double
     */
    public function getLongitude()
    {
        return $this->longitude;
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
     * Set the value of [geolocation_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setGeoLocationId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->geolocation_id !== $v) {
            $this->geolocation_id = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_GEOLOCATION_ID] = true;
        }

        return $this;
    } // setGeoLocationId()

    /**
     * Set the value of [display_name] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setDisplayName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->display_name !== $v) {
            $this->display_name = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_DISPLAY_NAME] = true;
        }

        return $this;
    } // setDisplayName()

    /**
     * Set the value of [geolocation_key] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setGeoLocationKey($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->geolocation_key !== $v) {
            $this->geolocation_key = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_GEOLOCATION_KEY] = true;
        }

        return $this;
    } // setGeoLocationKey()

    /**
     * Set the value of [place] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setPlace($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->place !== $v) {
            $this->place = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_PLACE] = true;
        }

        return $this;
    } // setPlace()

    /**
     * Set the value of [county] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setCounty($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->county !== $v) {
            $this->county = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_COUNTY] = true;
        }

        return $this;
    } // setCounty()

    /**
     * Set the value of [region] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setRegion($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->region !== $v) {
            $this->region = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_REGION] = true;
        }

        return $this;
    } // setRegion()

    /**
     * Set the value of [regioncode] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setRegionCode($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->regioncode !== $v) {
            $this->regioncode = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_REGIONCODE] = true;
        }

        return $this;
    } // setRegionCode()

    /**
     * Set the value of [country] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setCountry($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->country !== $v) {
            $this->country = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_COUNTRY] = true;
        }

        return $this;
    } // setCountry()

    /**
     * Set the value of [countrycode] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setCountryCode($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->countrycode !== $v) {
            $this->countrycode = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_COUNTRYCODE] = true;
        }

        return $this;
    } // setCountryCode()

    /**
     * Set the value of [latitude] column.
     *
     * @param double $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setLatitude($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->latitude !== $v) {
            $this->latitude = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_LATITUDE] = true;
        }

        return $this;
    } // setLatitude()

    /**
     * Set the value of [longitude] column.
     *
     * @param double $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setLongitude($v)
    {
        if ($v !== null) {
            $v = (double) $v;
        }

        if ($this->longitude !== $v) {
            $this->longitude = $v;
            $this->modifiedColumns[GeoLocationTableMap::COL_LONGITUDE] = true;
        }

        return $this;
    } // setLongitude()

    /**
     * Set the value of [alternate_names] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function setAlternateNames($v)
    {
        if ($this->alternate_names_unserialized !== $v) {
            $this->alternate_names_unserialized = $v;
            $this->alternate_names = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[GeoLocationTableMap::COL_ALTERNATE_NAMES] = true;
        }

        return $this;
    } // setAlternateNames()

    /**
     * Adds a value to the [alternate_names] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
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
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : GeoLocationTableMap::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->geolocation_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : GeoLocationTableMap::translateFieldName('DisplayName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->display_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : GeoLocationTableMap::translateFieldName('GeoLocationKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->geolocation_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : GeoLocationTableMap::translateFieldName('Place', TableMap::TYPE_PHPNAME, $indexType)];
            $this->place = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : GeoLocationTableMap::translateFieldName('County', TableMap::TYPE_PHPNAME, $indexType)];
            $this->county = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : GeoLocationTableMap::translateFieldName('Region', TableMap::TYPE_PHPNAME, $indexType)];
            $this->region = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : GeoLocationTableMap::translateFieldName('RegionCode', TableMap::TYPE_PHPNAME, $indexType)];
            $this->regioncode = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : GeoLocationTableMap::translateFieldName('Country', TableMap::TYPE_PHPNAME, $indexType)];
            $this->country = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : GeoLocationTableMap::translateFieldName('CountryCode', TableMap::TYPE_PHPNAME, $indexType)];
            $this->countrycode = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : GeoLocationTableMap::translateFieldName('Latitude', TableMap::TYPE_PHPNAME, $indexType)];
            $this->latitude = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : GeoLocationTableMap::translateFieldName('Longitude', TableMap::TYPE_PHPNAME, $indexType)];
            $this->longitude = (null !== $col) ? (double) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 11 + $startcol : GeoLocationTableMap::translateFieldName('AlternateNames', TableMap::TYPE_PHPNAME, $indexType)];
            $this->alternate_names = $col;
            $this->alternate_names_unserialized = null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 12; // 12 = GeoLocationTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\DataAccess\\GeoLocation'), 0, $e);
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
            $con = Propel::getServiceContainer()->getReadConnection(GeoLocationTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildGeoLocationQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collJobPostings = null;

            $this->collUserSearches = null;

            $this->collUserSearchSiteRuns = null;

            $this->collUserKeywordSetFromuses = null;
            $this->collUserSearchFromUSSRJobSiteFromUSSRAppRunIds = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see GeoLocation::setDeleted()
     * @see GeoLocation::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(GeoLocationTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildGeoLocationQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(GeoLocationTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                GeoLocationTableMap::addInstanceToPool($this);
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

            if ($this->userKeywordSetFromusesScheduledForDeletion !== null) {
                if (!$this->userKeywordSetFromusesScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->userKeywordSetFromusesScheduledForDeletion as $entry) {
                        $entryPk = [];

                        $entryPk[2] = $this->getGeoLocationId();
                        $entryPk[1] = $entry->getUserKeywordSetId();
                        $entryPk[0] = $entry->getUserId();
                        $pks[] = $entryPk;
                    }

                    \JobScooper\DataAccess\UserSearchQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->userKeywordSetFromusesScheduledForDeletion = null;
                }

            }

            if ($this->collUserKeywordSetFromuses) {
                foreach ($this->collUserKeywordSetFromuses as $userKeywordSetFromUS) {
                    if (!$userKeywordSetFromUS->isDeleted() && ($userKeywordSetFromUS->isNew() || $userKeywordSetFromUS->isModified())) {
                        $userKeywordSetFromUS->save($con);
                    }
                }
            }


            if ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion !== null) {
                if (!$this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion as $combination) {
                        $entryPk = [];

                        $entryPk[2] = $this->getGeoLocationId();
                        $entryPk[0] = $combination[0]->getUserId();
                        $entryPk[1] = $combination[0]->getUserKeywordSetId();
                        $entryPk[2] = $combination[0]->getGeoLocationId();
                        $entryPk[3] = $combination[0]->getUserSearchId();
                        $entryPk[4] = $combination[1]->getJobSiteKey();
                        //$combination[2] = AppRunId;
                        $entryPk[5] = $combination[2];

                        $pks[] = $entryPk;
                    }

                    \JobScooper\DataAccess\UserSearchSiteRunQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion = null;
                }

            }

            if (null !== $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds) {
                foreach ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds as $combination) {

                    //$combination[0] = UserSearch (user_search_site_run_fk_4d3978)
                    if (!$combination[0]->isDeleted() && ($combination[0]->isNew() || $combination[0]->isModified())) {
                        $combination[0]->save($con);
                    }

                    //$combination[1] = JobSiteRecord (user_search_site_run_fk_168d10)
                    if (!$combination[1]->isDeleted() && ($combination[1]->isNew() || $combination[1]->isModified())) {
                        $combination[1]->save($con);
                    }

                    //$combination[2] = AppRunId; Nothing to save.
                }
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

            if ($this->userSearchesScheduledForDeletion !== null) {
                if (!$this->userSearchesScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\UserSearchQuery::create()
                        ->filterByPrimaryKeys($this->userSearchesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->userSearchesScheduledForDeletion = null;
                }
            }

            if ($this->collUserSearches !== null) {
                foreach ($this->collUserSearches as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->userSearchSiteRunsScheduledForDeletion !== null) {
                if (!$this->userSearchSiteRunsScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\UserSearchSiteRunQuery::create()
                        ->filterByPrimaryKeys($this->userSearchSiteRunsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->userSearchSiteRunsScheduledForDeletion = null;
                }
            }

            if ($this->collUserSearchSiteRuns !== null) {
                foreach ($this->collUserSearchSiteRuns as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
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

        $this->modifiedColumns[GeoLocationTableMap::COL_GEOLOCATION_ID] = true;

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(GeoLocationTableMap::COL_GEOLOCATION_ID)) {
            $modifiedColumns[':p' . $index++]  = 'geolocation_id';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_DISPLAY_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'display_name';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_GEOLOCATION_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'geolocation_key';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_PLACE)) {
            $modifiedColumns[':p' . $index++]  = 'place';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_COUNTY)) {
            $modifiedColumns[':p' . $index++]  = 'county';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_REGION)) {
            $modifiedColumns[':p' . $index++]  = 'region';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_REGIONCODE)) {
            $modifiedColumns[':p' . $index++]  = 'regioncode';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_COUNTRY)) {
            $modifiedColumns[':p' . $index++]  = 'country';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_COUNTRYCODE)) {
            $modifiedColumns[':p' . $index++]  = 'countrycode';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_LATITUDE)) {
            $modifiedColumns[':p' . $index++]  = 'latitude';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_LONGITUDE)) {
            $modifiedColumns[':p' . $index++]  = 'longitude';
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_ALTERNATE_NAMES)) {
            $modifiedColumns[':p' . $index++]  = 'alternate_names';
        }

        $sql = sprintf(
            'INSERT INTO geolocation (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'geolocation_id':
                        $stmt->bindValue($identifier, $this->geolocation_id, PDO::PARAM_INT);
                        break;
                    case 'display_name':
                        $stmt->bindValue($identifier, $this->display_name, PDO::PARAM_STR);
                        break;
                    case 'geolocation_key':
                        $stmt->bindValue($identifier, $this->geolocation_key, PDO::PARAM_STR);
                        break;
                    case 'place':
                        $stmt->bindValue($identifier, $this->place, PDO::PARAM_STR);
                        break;
                    case 'county':
                        $stmt->bindValue($identifier, $this->county, PDO::PARAM_STR);
                        break;
                    case 'region':
                        $stmt->bindValue($identifier, $this->region, PDO::PARAM_STR);
                        break;
                    case 'regioncode':
                        $stmt->bindValue($identifier, $this->regioncode, PDO::PARAM_STR);
                        break;
                    case 'country':
                        $stmt->bindValue($identifier, $this->country, PDO::PARAM_STR);
                        break;
                    case 'countrycode':
                        $stmt->bindValue($identifier, $this->countrycode, PDO::PARAM_STR);
                        break;
                    case 'latitude':
                        $stmt->bindValue($identifier, $this->latitude, PDO::PARAM_STR);
                        break;
                    case 'longitude':
                        $stmt->bindValue($identifier, $this->longitude, PDO::PARAM_STR);
                        break;
                    case 'alternate_names':
                        $stmt->bindValue($identifier, $this->alternate_names, PDO::PARAM_STR);
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
        if ($pk !== null) {
            $this->setGeoLocationId($pk);
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
        $pos = GeoLocationTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getGeoLocationId();
                break;
            case 1:
                return $this->getDisplayName();
                break;
            case 2:
                return $this->getGeoLocationKey();
                break;
            case 3:
                return $this->getPlace();
                break;
            case 4:
                return $this->getCounty();
                break;
            case 5:
                return $this->getRegion();
                break;
            case 6:
                return $this->getRegionCode();
                break;
            case 7:
                return $this->getCountry();
                break;
            case 8:
                return $this->getCountryCode();
                break;
            case 9:
                return $this->getLatitude();
                break;
            case 10:
                return $this->getLongitude();
                break;
            case 11:
                return $this->getAlternateNames();
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

        if (isset($alreadyDumpedObjects['GeoLocation'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['GeoLocation'][$this->hashCode()] = true;
        $keys = GeoLocationTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getGeoLocationId(),
            $keys[1] => $this->getDisplayName(),
            $keys[2] => $this->getGeoLocationKey(),
            $keys[3] => $this->getPlace(),
            $keys[4] => $this->getCounty(),
            $keys[5] => $this->getRegion(),
            $keys[6] => $this->getRegionCode(),
            $keys[7] => $this->getCountry(),
            $keys[8] => $this->getCountryCode(),
            $keys[9] => $this->getLatitude(),
            $keys[10] => $this->getLongitude(),
            $keys[11] => $this->getAlternateNames(),
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
            if (null !== $this->collUserSearches) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userSearches';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_searches';
                        break;
                    default:
                        $key = 'UserSearches';
                }

                $result[$key] = $this->collUserSearches->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collUserSearchSiteRuns) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userSearchSiteRuns';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_search_site_runs';
                        break;
                    default:
                        $key = 'UserSearchSiteRuns';
                }

                $result[$key] = $this->collUserSearchSiteRuns->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\JobScooper\DataAccess\GeoLocation
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = GeoLocationTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\DataAccess\GeoLocation
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setGeoLocationId($value);
                break;
            case 1:
                $this->setDisplayName($value);
                break;
            case 2:
                $this->setGeoLocationKey($value);
                break;
            case 3:
                $this->setPlace($value);
                break;
            case 4:
                $this->setCounty($value);
                break;
            case 5:
                $this->setRegion($value);
                break;
            case 6:
                $this->setRegionCode($value);
                break;
            case 7:
                $this->setCountry($value);
                break;
            case 8:
                $this->setCountryCode($value);
                break;
            case 9:
                $this->setLatitude($value);
                break;
            case 10:
                $this->setLongitude($value);
                break;
            case 11:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setAlternateNames($value);
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
        $keys = GeoLocationTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setGeoLocationId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setDisplayName($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setGeoLocationKey($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setPlace($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setCounty($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setRegion($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setRegionCode($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setCountry($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setCountryCode($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setLatitude($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setLongitude($arr[$keys[10]]);
        }
        if (array_key_exists($keys[11], $arr)) {
            $this->setAlternateNames($arr[$keys[11]]);
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
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object, for fluid interface
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
        $criteria = new Criteria(GeoLocationTableMap::DATABASE_NAME);

        if ($this->isColumnModified(GeoLocationTableMap::COL_GEOLOCATION_ID)) {
            $criteria->add(GeoLocationTableMap::COL_GEOLOCATION_ID, $this->geolocation_id);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_DISPLAY_NAME)) {
            $criteria->add(GeoLocationTableMap::COL_DISPLAY_NAME, $this->display_name);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_GEOLOCATION_KEY)) {
            $criteria->add(GeoLocationTableMap::COL_GEOLOCATION_KEY, $this->geolocation_key);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_PLACE)) {
            $criteria->add(GeoLocationTableMap::COL_PLACE, $this->place);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_COUNTY)) {
            $criteria->add(GeoLocationTableMap::COL_COUNTY, $this->county);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_REGION)) {
            $criteria->add(GeoLocationTableMap::COL_REGION, $this->region);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_REGIONCODE)) {
            $criteria->add(GeoLocationTableMap::COL_REGIONCODE, $this->regioncode);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_COUNTRY)) {
            $criteria->add(GeoLocationTableMap::COL_COUNTRY, $this->country);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_COUNTRYCODE)) {
            $criteria->add(GeoLocationTableMap::COL_COUNTRYCODE, $this->countrycode);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_LATITUDE)) {
            $criteria->add(GeoLocationTableMap::COL_LATITUDE, $this->latitude);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_LONGITUDE)) {
            $criteria->add(GeoLocationTableMap::COL_LONGITUDE, $this->longitude);
        }
        if ($this->isColumnModified(GeoLocationTableMap::COL_ALTERNATE_NAMES)) {
            $criteria->add(GeoLocationTableMap::COL_ALTERNATE_NAMES, $this->alternate_names);
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
        $criteria = ChildGeoLocationQuery::create();
        $criteria->add(GeoLocationTableMap::COL_GEOLOCATION_ID, $this->geolocation_id);

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
        $validPk = null !== $this->getGeoLocationId();

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
        return $this->getGeoLocationId();
    }

    /**
     * Generic method to set the primary key (geolocation_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setGeoLocationId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getGeoLocationId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\DataAccess\GeoLocation (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setDisplayName($this->getDisplayName());
        $copyObj->setGeoLocationKey($this->getGeoLocationKey());
        $copyObj->setPlace($this->getPlace());
        $copyObj->setCounty($this->getCounty());
        $copyObj->setRegion($this->getRegion());
        $copyObj->setRegionCode($this->getRegionCode());
        $copyObj->setCountry($this->getCountry());
        $copyObj->setCountryCode($this->getCountryCode());
        $copyObj->setLatitude($this->getLatitude());
        $copyObj->setLongitude($this->getLongitude());
        $copyObj->setAlternateNames($this->getAlternateNames());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getJobPostings() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addJobPosting($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getUserSearches() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserSearch($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getUserSearchSiteRuns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserSearchSiteRun($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setGeoLocationId(NULL); // this is a auto-increment column, so set to default value
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
     * @return \JobScooper\DataAccess\GeoLocation Clone of current object.
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
        if ('UserSearch' == $relationName) {
            $this->initUserSearches();
            return;
        }
        if ('UserSearchSiteRun' == $relationName) {
            $this->initUserSearchSiteRuns();
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
     * If this ChildGeoLocation is new, it will return
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
                    ->filterByGeoLocationFromJP($this)
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
     * @return $this|ChildGeoLocation The current object (for fluent API support)
     */
    public function setJobPostings(Collection $jobPostings, ConnectionInterface $con = null)
    {
        /** @var ChildJobPosting[] $jobPostingsToDelete */
        $jobPostingsToDelete = $this->getJobPostings(new Criteria(), $con)->diff($jobPostings);


        $this->jobPostingsScheduledForDeletion = $jobPostingsToDelete;

        foreach ($jobPostingsToDelete as $jobPostingRemoved) {
            $jobPostingRemoved->setGeoLocationFromJP(null);
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
                ->filterByGeoLocationFromJP($this)
                ->count($con);
        }

        return count($this->collJobPostings);
    }

    /**
     * Method called to associate a ChildJobPosting object to this object
     * through the ChildJobPosting foreign key attribute.
     *
     * @param  ChildJobPosting $l ChildJobPosting
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
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
        $jobPosting->setGeoLocationFromJP($this);
    }

    /**
     * @param  ChildJobPosting $jobPosting The ChildJobPosting object to remove.
     * @return $this|ChildGeoLocation The current object (for fluent API support)
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
            $jobPosting->setGeoLocationFromJP(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this GeoLocation is new, it will return
     * an empty collection; or if this GeoLocation has previously
     * been saved, it will retrieve related JobPostings from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in GeoLocation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildJobPosting[] List of ChildJobPosting objects
     */
    public function getJobPostingsJoinJobSiteFromJP(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildJobPostingQuery::create(null, $criteria);
        $query->joinWith('JobSiteFromJP', $joinBehavior);

        return $this->getJobPostings($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this GeoLocation is new, it will return
     * an empty collection; or if this GeoLocation has previously
     * been saved, it will retrieve related JobPostings from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in GeoLocation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildJobPosting[] List of ChildJobPosting objects
     */
    public function getJobPostingsJoinDuplicateJobPosting(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildJobPostingQuery::create(null, $criteria);
        $query->joinWith('DuplicateJobPosting', $joinBehavior);

        return $this->getJobPostings($query, $con);
    }

    /**
     * Clears out the collUserSearches collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserSearches()
     */
    public function clearUserSearches()
    {
        $this->collUserSearches = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collUserSearches collection loaded partially.
     */
    public function resetPartialUserSearches($v = true)
    {
        $this->collUserSearchesPartial = $v;
    }

    /**
     * Initializes the collUserSearches collection.
     *
     * By default this just sets the collUserSearches collection to an empty array (like clearcollUserSearches());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUserSearches($overrideExisting = true)
    {
        if (null !== $this->collUserSearches && !$overrideExisting) {
            return;
        }

        $collectionClassName = UserSearchTableMap::getTableMap()->getCollectionClassName();

        $this->collUserSearches = new $collectionClassName;
        $this->collUserSearches->setModel('\JobScooper\DataAccess\UserSearch');
    }

    /**
     * Gets an array of ChildUserSearch objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGeoLocation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildUserSearch[] List of ChildUserSearch objects
     * @throws PropelException
     */
    public function getUserSearches(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchesPartial && !$this->isNew();
        if (null === $this->collUserSearches || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUserSearches) {
                // return empty collection
                $this->initUserSearches();
            } else {
                $collUserSearches = ChildUserSearchQuery::create(null, $criteria)
                    ->filterByGeoLocationFromUS($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collUserSearchesPartial && count($collUserSearches)) {
                        $this->initUserSearches(false);

                        foreach ($collUserSearches as $obj) {
                            if (false == $this->collUserSearches->contains($obj)) {
                                $this->collUserSearches->append($obj);
                            }
                        }

                        $this->collUserSearchesPartial = true;
                    }

                    return $collUserSearches;
                }

                if ($partial && $this->collUserSearches) {
                    foreach ($this->collUserSearches as $obj) {
                        if ($obj->isNew()) {
                            $collUserSearches[] = $obj;
                        }
                    }
                }

                $this->collUserSearches = $collUserSearches;
                $this->collUserSearchesPartial = false;
            }
        }

        return $this->collUserSearches;
    }

    /**
     * Sets a collection of ChildUserSearch objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $userSearches A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildGeoLocation The current object (for fluent API support)
     */
    public function setUserSearches(Collection $userSearches, ConnectionInterface $con = null)
    {
        /** @var ChildUserSearch[] $userSearchesToDelete */
        $userSearchesToDelete = $this->getUserSearches(new Criteria(), $con)->diff($userSearches);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->userSearchesScheduledForDeletion = clone $userSearchesToDelete;

        foreach ($userSearchesToDelete as $userSearchRemoved) {
            $userSearchRemoved->setGeoLocationFromUS(null);
        }

        $this->collUserSearches = null;
        foreach ($userSearches as $userSearch) {
            $this->addUserSearch($userSearch);
        }

        $this->collUserSearches = $userSearches;
        $this->collUserSearchesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related UserSearch objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related UserSearch objects.
     * @throws PropelException
     */
    public function countUserSearches(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchesPartial && !$this->isNew();
        if (null === $this->collUserSearches || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserSearches) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getUserSearches());
            }

            $query = ChildUserSearchQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGeoLocationFromUS($this)
                ->count($con);
        }

        return count($this->collUserSearches);
    }

    /**
     * Method called to associate a ChildUserSearch object to this object
     * through the ChildUserSearch foreign key attribute.
     *
     * @param  ChildUserSearch $l ChildUserSearch
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function addUserSearch(ChildUserSearch $l)
    {
        if ($this->collUserSearches === null) {
            $this->initUserSearches();
            $this->collUserSearchesPartial = true;
        }

        if (!$this->collUserSearches->contains($l)) {
            $this->doAddUserSearch($l);

            if ($this->userSearchesScheduledForDeletion and $this->userSearchesScheduledForDeletion->contains($l)) {
                $this->userSearchesScheduledForDeletion->remove($this->userSearchesScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildUserSearch $userSearch The ChildUserSearch object to add.
     */
    protected function doAddUserSearch(ChildUserSearch $userSearch)
    {
        $this->collUserSearches[]= $userSearch;
        $userSearch->setGeoLocationFromUS($this);
    }

    /**
     * @param  ChildUserSearch $userSearch The ChildUserSearch object to remove.
     * @return $this|ChildGeoLocation The current object (for fluent API support)
     */
    public function removeUserSearch(ChildUserSearch $userSearch)
    {
        if ($this->getUserSearches()->contains($userSearch)) {
            $pos = $this->collUserSearches->search($userSearch);
            $this->collUserSearches->remove($pos);
            if (null === $this->userSearchesScheduledForDeletion) {
                $this->userSearchesScheduledForDeletion = clone $this->collUserSearches;
                $this->userSearchesScheduledForDeletion->clear();
            }
            $this->userSearchesScheduledForDeletion[]= clone $userSearch;
            $userSearch->setGeoLocationFromUS(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this GeoLocation is new, it will return
     * an empty collection; or if this GeoLocation has previously
     * been saved, it will retrieve related UserSearches from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in GeoLocation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearch[] List of ChildUserSearch objects
     */
    public function getUserSearchesJoinUserKeywordSetFromUS(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchQuery::create(null, $criteria);
        $query->joinWith('UserKeywordSetFromUS', $joinBehavior);

        return $this->getUserSearches($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this GeoLocation is new, it will return
     * an empty collection; or if this GeoLocation has previously
     * been saved, it will retrieve related UserSearches from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in GeoLocation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearch[] List of ChildUserSearch objects
     */
    public function getUserSearchesJoinUserFromUS(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchQuery::create(null, $criteria);
        $query->joinWith('UserFromUS', $joinBehavior);

        return $this->getUserSearches($query, $con);
    }

    /**
     * Clears out the collUserSearchSiteRuns collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserSearchSiteRuns()
     */
    public function clearUserSearchSiteRuns()
    {
        $this->collUserSearchSiteRuns = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collUserSearchSiteRuns collection loaded partially.
     */
    public function resetPartialUserSearchSiteRuns($v = true)
    {
        $this->collUserSearchSiteRunsPartial = $v;
    }

    /**
     * Initializes the collUserSearchSiteRuns collection.
     *
     * By default this just sets the collUserSearchSiteRuns collection to an empty array (like clearcollUserSearchSiteRuns());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUserSearchSiteRuns($overrideExisting = true)
    {
        if (null !== $this->collUserSearchSiteRuns && !$overrideExisting) {
            return;
        }

        $collectionClassName = UserSearchSiteRunTableMap::getTableMap()->getCollectionClassName();

        $this->collUserSearchSiteRuns = new $collectionClassName;
        $this->collUserSearchSiteRuns->setModel('\JobScooper\DataAccess\UserSearchSiteRun');
    }

    /**
     * Gets an array of ChildUserSearchSiteRun objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGeoLocation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildUserSearchSiteRun[] List of ChildUserSearchSiteRun objects
     * @throws PropelException
     */
    public function getUserSearchSiteRuns(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchSiteRunsPartial && !$this->isNew();
        if (null === $this->collUserSearchSiteRuns || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUserSearchSiteRuns) {
                // return empty collection
                $this->initUserSearchSiteRuns();
            } else {
                $collUserSearchSiteRuns = ChildUserSearchSiteRunQuery::create(null, $criteria)
                    ->filterByGeoLocationFromUSSR($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collUserSearchSiteRunsPartial && count($collUserSearchSiteRuns)) {
                        $this->initUserSearchSiteRuns(false);

                        foreach ($collUserSearchSiteRuns as $obj) {
                            if (false == $this->collUserSearchSiteRuns->contains($obj)) {
                                $this->collUserSearchSiteRuns->append($obj);
                            }
                        }

                        $this->collUserSearchSiteRunsPartial = true;
                    }

                    return $collUserSearchSiteRuns;
                }

                if ($partial && $this->collUserSearchSiteRuns) {
                    foreach ($this->collUserSearchSiteRuns as $obj) {
                        if ($obj->isNew()) {
                            $collUserSearchSiteRuns[] = $obj;
                        }
                    }
                }

                $this->collUserSearchSiteRuns = $collUserSearchSiteRuns;
                $this->collUserSearchSiteRunsPartial = false;
            }
        }

        return $this->collUserSearchSiteRuns;
    }

    /**
     * Sets a collection of ChildUserSearchSiteRun objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $userSearchSiteRuns A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildGeoLocation The current object (for fluent API support)
     */
    public function setUserSearchSiteRuns(Collection $userSearchSiteRuns, ConnectionInterface $con = null)
    {
        /** @var ChildUserSearchSiteRun[] $userSearchSiteRunsToDelete */
        $userSearchSiteRunsToDelete = $this->getUserSearchSiteRuns(new Criteria(), $con)->diff($userSearchSiteRuns);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->userSearchSiteRunsScheduledForDeletion = clone $userSearchSiteRunsToDelete;

        foreach ($userSearchSiteRunsToDelete as $userSearchSiteRunRemoved) {
            $userSearchSiteRunRemoved->setGeoLocationFromUSSR(null);
        }

        $this->collUserSearchSiteRuns = null;
        foreach ($userSearchSiteRuns as $userSearchSiteRun) {
            $this->addUserSearchSiteRun($userSearchSiteRun);
        }

        $this->collUserSearchSiteRuns = $userSearchSiteRuns;
        $this->collUserSearchSiteRunsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related UserSearchSiteRun objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related UserSearchSiteRun objects.
     * @throws PropelException
     */
    public function countUserSearchSiteRuns(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchSiteRunsPartial && !$this->isNew();
        if (null === $this->collUserSearchSiteRuns || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserSearchSiteRuns) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getUserSearchSiteRuns());
            }

            $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGeoLocationFromUSSR($this)
                ->count($con);
        }

        return count($this->collUserSearchSiteRuns);
    }

    /**
     * Method called to associate a ChildUserSearchSiteRun object to this object
     * through the ChildUserSearchSiteRun foreign key attribute.
     *
     * @param  ChildUserSearchSiteRun $l ChildUserSearchSiteRun
     * @return $this|\JobScooper\DataAccess\GeoLocation The current object (for fluent API support)
     */
    public function addUserSearchSiteRun(ChildUserSearchSiteRun $l)
    {
        if ($this->collUserSearchSiteRuns === null) {
            $this->initUserSearchSiteRuns();
            $this->collUserSearchSiteRunsPartial = true;
        }

        if (!$this->collUserSearchSiteRuns->contains($l)) {
            $this->doAddUserSearchSiteRun($l);

            if ($this->userSearchSiteRunsScheduledForDeletion and $this->userSearchSiteRunsScheduledForDeletion->contains($l)) {
                $this->userSearchSiteRunsScheduledForDeletion->remove($this->userSearchSiteRunsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildUserSearchSiteRun $userSearchSiteRun The ChildUserSearchSiteRun object to add.
     */
    protected function doAddUserSearchSiteRun(ChildUserSearchSiteRun $userSearchSiteRun)
    {
        $this->collUserSearchSiteRuns[]= $userSearchSiteRun;
        $userSearchSiteRun->setGeoLocationFromUSSR($this);
    }

    /**
     * @param  ChildUserSearchSiteRun $userSearchSiteRun The ChildUserSearchSiteRun object to remove.
     * @return $this|ChildGeoLocation The current object (for fluent API support)
     */
    public function removeUserSearchSiteRun(ChildUserSearchSiteRun $userSearchSiteRun)
    {
        if ($this->getUserSearchSiteRuns()->contains($userSearchSiteRun)) {
            $pos = $this->collUserSearchSiteRuns->search($userSearchSiteRun);
            $this->collUserSearchSiteRuns->remove($pos);
            if (null === $this->userSearchSiteRunsScheduledForDeletion) {
                $this->userSearchSiteRunsScheduledForDeletion = clone $this->collUserSearchSiteRuns;
                $this->userSearchSiteRunsScheduledForDeletion->clear();
            }
            $this->userSearchSiteRunsScheduledForDeletion[]= clone $userSearchSiteRun;
            $userSearchSiteRun->setGeoLocationFromUSSR(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this GeoLocation is new, it will return
     * an empty collection; or if this GeoLocation has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in GeoLocation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearchSiteRun[] List of ChildUserSearchSiteRun objects
     */
    public function getUserSearchSiteRunsJoinUserSearchFromUSSR(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
        $query->joinWith('UserSearchFromUSSR', $joinBehavior);

        return $this->getUserSearchSiteRuns($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this GeoLocation is new, it will return
     * an empty collection; or if this GeoLocation has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in GeoLocation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearchSiteRun[] List of ChildUserSearchSiteRun objects
     */
    public function getUserSearchSiteRunsJoinJobSiteFromUSSR(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
        $query->joinWith('JobSiteFromUSSR', $joinBehavior);

        return $this->getUserSearchSiteRuns($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this GeoLocation is new, it will return
     * an empty collection; or if this GeoLocation has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in GeoLocation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearchSiteRun[] List of ChildUserSearchSiteRun objects
     */
    public function getUserSearchSiteRunsJoinUserFromUSSR(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
        $query->joinWith('UserFromUSSR', $joinBehavior);

        return $this->getUserSearchSiteRuns($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this GeoLocation is new, it will return
     * an empty collection; or if this GeoLocation has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in GeoLocation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearchSiteRun[] List of ChildUserSearchSiteRun objects
     */
    public function getUserSearchSiteRunsJoinUserKeywordSetFromUSSR(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
        $query->joinWith('UserKeywordSetFromUSSR', $joinBehavior);

        return $this->getUserSearchSiteRuns($query, $con);
    }

    /**
     * Clears out the collUserKeywordSetFromuses collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserKeywordSetFromuses()
     */
    public function clearUserKeywordSetFromuses()
    {
        $this->collUserKeywordSetFromuses = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the collUserKeywordSetFromuses crossRef collection.
     *
     * By default this just sets the collUserKeywordSetFromuses collection to an empty collection (like clearUserKeywordSetFromuses());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initUserKeywordSetFromuses()
    {
        $collectionClassName = UserSearchTableMap::getTableMap()->getCollectionClassName();

        $this->collUserKeywordSetFromuses = new $collectionClassName;
        $this->collUserKeywordSetFromusesPartial = true;
        $this->collUserKeywordSetFromuses->setModel('\JobScooper\DataAccess\UserKeywordSet');
    }

    /**
     * Checks if the collUserKeywordSetFromuses collection is loaded.
     *
     * @return bool
     */
    public function isUserKeywordSetFromusesLoaded()
    {
        return null !== $this->collUserKeywordSetFromuses;
    }

    /**
     * Gets a collection of ChildUserKeywordSet objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGeoLocation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildUserKeywordSet[] List of ChildUserKeywordSet objects
     */
    public function getUserKeywordSetFromuses(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserKeywordSetFromusesPartial && !$this->isNew();
        if (null === $this->collUserKeywordSetFromuses || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->collUserKeywordSetFromuses) {
                    $this->initUserKeywordSetFromuses();
                }
            } else {

                $query = ChildUserKeywordSetQuery::create(null, $criteria)
                    ->filterByGeoLocationFromUS($this);
                $collUserKeywordSetFromuses = $query->find($con);
                if (null !== $criteria) {
                    return $collUserKeywordSetFromuses;
                }

                if ($partial && $this->collUserKeywordSetFromuses) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->collUserKeywordSetFromuses as $obj) {
                        if (!$collUserKeywordSetFromuses->contains($obj)) {
                            $collUserKeywordSetFromuses[] = $obj;
                        }
                    }
                }

                $this->collUserKeywordSetFromuses = $collUserKeywordSetFromuses;
                $this->collUserKeywordSetFromusesPartial = false;
            }
        }

        return $this->collUserKeywordSetFromuses;
    }

    /**
     * Sets a collection of UserKeywordSet objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $userKeywordSetFromuses A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildGeoLocation The current object (for fluent API support)
     */
    public function setUserKeywordSetFromuses(Collection $userKeywordSetFromuses, ConnectionInterface $con = null)
    {
        $this->clearUserKeywordSetFromuses();
        $currentUserKeywordSetFromuses = $this->getUserKeywordSetFromuses();

        $userKeywordSetFromusesScheduledForDeletion = $currentUserKeywordSetFromuses->diff($userKeywordSetFromuses);

        foreach ($userKeywordSetFromusesScheduledForDeletion as $toDelete) {
            $this->removeUserKeywordSetFromUS($toDelete);
        }

        foreach ($userKeywordSetFromuses as $userKeywordSetFromUS) {
            if (!$currentUserKeywordSetFromuses->contains($userKeywordSetFromUS)) {
                $this->doAddUserKeywordSetFromUS($userKeywordSetFromUS);
            }
        }

        $this->collUserKeywordSetFromusesPartial = false;
        $this->collUserKeywordSetFromuses = $userKeywordSetFromuses;

        return $this;
    }

    /**
     * Gets the number of UserKeywordSet objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related UserKeywordSet objects
     */
    public function countUserKeywordSetFromuses(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserKeywordSetFromusesPartial && !$this->isNew();
        if (null === $this->collUserKeywordSetFromuses || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserKeywordSetFromuses) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getUserKeywordSetFromuses());
                }

                $query = ChildUserKeywordSetQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByGeoLocationFromUS($this)
                    ->count($con);
            }
        } else {
            return count($this->collUserKeywordSetFromuses);
        }
    }

    /**
     * Associate a ChildUserKeywordSet to this object
     * through the user_search cross reference table.
     *
     * @param ChildUserKeywordSet $userKeywordSetFromUS
     * @return ChildGeoLocation The current object (for fluent API support)
     */
    public function addUserKeywordSetFromUS(ChildUserKeywordSet $userKeywordSetFromUS)
    {
        if ($this->collUserKeywordSetFromuses === null) {
            $this->initUserKeywordSetFromuses();
        }

        if (!$this->getUserKeywordSetFromuses()->contains($userKeywordSetFromUS)) {
            // only add it if the **same** object is not already associated
            $this->collUserKeywordSetFromuses->push($userKeywordSetFromUS);
            $this->doAddUserKeywordSetFromUS($userKeywordSetFromUS);
        }

        return $this;
    }

    /**
     *
     * @param ChildUserKeywordSet $userKeywordSetFromUS
     */
    protected function doAddUserKeywordSetFromUS(ChildUserKeywordSet $userKeywordSetFromUS)
    {
        $userSearch = new ChildUserSearch();

        $userSearch->setUserKeywordSetFromUS($userKeywordSetFromUS);

        $userSearch->setGeoLocationFromUS($this);

        $this->addUserSearch($userSearch);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$userKeywordSetFromUS->isGeoLocationFromusesLoaded()) {
            $userKeywordSetFromUS->initGeoLocationFromuses();
            $userKeywordSetFromUS->getGeoLocationFromuses()->push($this);
        } elseif (!$userKeywordSetFromUS->getGeoLocationFromuses()->contains($this)) {
            $userKeywordSetFromUS->getGeoLocationFromuses()->push($this);
        }

    }

    /**
     * Remove userKeywordSetFromUS of this object
     * through the user_search cross reference table.
     *
     * @param ChildUserKeywordSet $userKeywordSetFromUS
     * @return ChildGeoLocation The current object (for fluent API support)
     */
    public function removeUserKeywordSetFromUS(ChildUserKeywordSet $userKeywordSetFromUS)
    {
        if ($this->getUserKeywordSetFromuses()->contains($userKeywordSetFromUS)) {
            $userSearch = new ChildUserSearch();
            $userSearch->setUserKeywordSetFromUS($userKeywordSetFromUS);
            if ($userKeywordSetFromUS->isGeoLocationFromusesLoaded()) {
                //remove the back reference if available
                $userKeywordSetFromUS->getGeoLocationFromuses()->removeObject($this);
            }

            $userSearch->setGeoLocationFromUS($this);
            $this->removeUserSearch(clone $userSearch);
            $userSearch->clear();

            $this->collUserKeywordSetFromuses->remove($this->collUserKeywordSetFromuses->search($userKeywordSetFromUS));

            if (null === $this->userKeywordSetFromusesScheduledForDeletion) {
                $this->userKeywordSetFromusesScheduledForDeletion = clone $this->collUserKeywordSetFromuses;
                $this->userKeywordSetFromusesScheduledForDeletion->clear();
            }

            $this->userKeywordSetFromusesScheduledForDeletion->push($userKeywordSetFromUS);
        }


        return $this;
    }

    /**
     * Clears out the collUserSearchFromUSSRJobSiteFromUSSRAppRunIds collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserSearchFromUSSRJobSiteFromUSSRAppRunIds()
     */
    public function clearUserSearchFromUSSRJobSiteFromUSSRAppRunIds()
    {
        $this->collUserSearchFromUSSRJobSiteFromUSSRAppRunIds = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds crossRef collection.
     *
     * By default this just sets the combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds collection to an empty collection (like clearUserSearchFromUSSRJobSiteFromUSSRAppRunIds());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initUserSearchFromUSSRJobSiteFromUSSRAppRunIds()
    {
        $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds = new ObjectCombinationCollection;
        $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsPartial = true;
    }

    /**
     * Checks if the combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds collection is loaded.
     *
     * @return bool
     */
    public function isUserSearchFromUSSRJobSiteFromUSSRAppRunIdsLoaded()
    {
        return null !== $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds;
    }

    /**
     * Returns a new query object pre configured with filters from current object and given arguments to query the database.
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     *
     * @return ChildUserSearchQuery
     */
    public function createUserSearchFromUSSRsQuery(ChildJobSiteRecord $jobSiteFromUSSR = null, $appRunId = null, Criteria $criteria = null)
    {
        $criteria = ChildUserSearchQuery::create($criteria)
            ->filterByGeoLocationFromUSSR($this);

        $userSearchSiteRunQuery = $criteria->useUserSearchSiteRunQuery();

        if (null !== $jobSiteFromUSSR) {
            $userSearchSiteRunQuery->filterByJobSiteFromUSSR($jobSiteFromUSSR);
        }

        if (null !== $appRunId) {
            $userSearchSiteRunQuery->filterByAppRunId($appRunId);
        }

        $userSearchSiteRunQuery->endUse();

        return $criteria;
    }

    /**
     * Gets a combined collection of ChildUserSearch, ChildJobSiteRecord objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGeoLocation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCombinationCollection Combination list of ChildUserSearch, ChildJobSiteRecord objects
     */
    public function getUserSearchFromUSSRJobSiteFromUSSRAppRunIds($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsPartial && !$this->isNew();
        if (null === $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds) {
                    $this->initUserSearchFromUSSRJobSiteFromUSSRAppRunIds();
                }
            } else {

                $query = ChildUserSearchSiteRunQuery::create(null, $criteria)
                    ->filterByGeoLocationFromUSSR($this)
                    ->joinUserSearchFromUSSR()
                    ->joinJobSiteFromUSSR()
                ;

                $items = $query->find($con);
                $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds = new ObjectCombinationCollection();
                foreach ($items as $item) {
                    $combination = [];

                    $combination[] = $item->getUserSearchFromUSSR();
                    $combination[] = $item->getJobSiteFromUSSR();
                    $combination[] = $item->getAppRunId();
                    $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds[] = $combination;
                }

                if (null !== $criteria) {
                    return $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds;
                }

                if ($partial && $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds as $obj) {
                        if (!call_user_func_array([$combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds, 'contains'], $obj)) {
                            $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds[] = $obj;
                        }
                    }
                }

                $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds = $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds;
                $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsPartial = false;
            }
        }

        return $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds;
    }

    /**
     * Returns a not cached ObjectCollection of ChildUserSearch objects. This will hit always the databases.
     * If you have attached new ChildUserSearch object to this object you need to call `save` first to get
     * the correct return value. Use getUserSearchFromUSSRJobSiteFromUSSRAppRunIds() to get the current internal state.
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return ChildUserSearch[]|ObjectCollection
     */
    public function getUserSearchFromUSSRs(ChildJobSiteRecord $jobSiteFromUSSR = null, $appRunId = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createUserSearchFromUSSRsQuery($jobSiteFromUSSR, $appRunId, $criteria)->find($con);
    }

    /**
     * Sets a collection of ChildUserSearch, ChildJobSiteRecord combination objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $userSearchFromUSSRJobSiteFromUSSRAppRunIds A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildGeoLocation The current object (for fluent API support)
     */
    public function setUserSearchFromUSSRJobSiteFromUSSRAppRunIds(Collection $userSearchFromUSSRJobSiteFromUSSRAppRunIds, ConnectionInterface $con = null)
    {
        $this->clearUserSearchFromUSSRJobSiteFromUSSRAppRunIds();
        $currentUserSearchFromUSSRJobSiteFromUSSRAppRunIds = $this->getUserSearchFromUSSRJobSiteFromUSSRAppRunIds();

        $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion = $currentUserSearchFromUSSRJobSiteFromUSSRAppRunIds->diff($userSearchFromUSSRJobSiteFromUSSRAppRunIds);

        foreach ($combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion as $toDelete) {
            call_user_func_array([$this, 'removeUserSearchFromUSSRJobSiteFromUSSRAppRunId'], $toDelete);
        }

        foreach ($userSearchFromUSSRJobSiteFromUSSRAppRunIds as $userSearchFromUSSRJobSiteFromUSSRAppRunId) {
            if (!call_user_func_array([$currentUserSearchFromUSSRJobSiteFromUSSRAppRunIds, 'contains'], $userSearchFromUSSRJobSiteFromUSSRAppRunId)) {
                call_user_func_array([$this, 'doAddUserSearchFromUSSRJobSiteFromUSSRAppRunId'], $userSearchFromUSSRJobSiteFromUSSRAppRunId);
            }
        }

        $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsPartial = false;
        $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds = $userSearchFromUSSRJobSiteFromUSSRAppRunIds;

        return $this;
    }

    /**
     * Gets the number of ChildUserSearch, ChildJobSiteRecord combination objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ChildUserSearch, ChildJobSiteRecord combination objects
     */
    public function countUserSearchFromUSSRJobSiteFromUSSRAppRunIds(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsPartial && !$this->isNew();
        if (null === $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getUserSearchFromUSSRJobSiteFromUSSRAppRunIds());
                }

                $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByGeoLocationFromUSSR($this)
                    ->count($con);
            }
        } else {
            return count($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds);
        }
    }

    /**
     * Returns the not cached count of ChildUserSearch objects. This will hit always the databases.
     * If you have attached new ChildUserSearch object to this object you need to call `save` first to get
     * the correct return value. Use getUserSearchFromUSSRJobSiteFromUSSRAppRunIds() to get the current internal state.
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return integer
     */
    public function countUserSearchFromUSSRs(ChildJobSiteRecord $jobSiteFromUSSR = null, $appRunId = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createUserSearchFromUSSRsQuery($jobSiteFromUSSR, $appRunId, $criteria)->count($con);
    }

    /**
     * Associate a ChildUserSearch to this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param string $appRunId
     * @return ChildGeoLocation The current object (for fluent API support)
     */
    public function addUserSearchFromUSSR(ChildUserSearch $userSearchFromUSSR, ChildJobSiteRecord $jobSiteFromUSSR, $appRunId)
    {
        if ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds === null) {
            $this->initUserSearchFromUSSRJobSiteFromUSSRAppRunIds();
        }

        if (!$this->getUserSearchFromUSSRJobSiteFromUSSRAppRunIds()->contains($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds->push($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId);
            $this->doAddUserSearchFromUSSRJobSiteFromUSSRAppRunId($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId);
        }

        return $this;
    }

    /**
     * Associate a ChildJobSiteRecord to this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param string $appRunId
     * @return ChildGeoLocation The current object (for fluent API support)
     */
    public function addJobSiteFromUSSR(ChildJobSiteRecord $jobSiteFromUSSR, ChildUserSearch $userSearchFromUSSR, $appRunId)
    {
        if ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds === null) {
            $this->initUserSearchFromUSSRJobSiteFromUSSRAppRunIds();
        }

        if (!$this->getUserSearchFromUSSRJobSiteFromUSSRAppRunIds()->contains($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds->push($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId);
            $this->doAddUserSearchFromUSSRJobSiteFromUSSRAppRunId($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId);
        }

        return $this;
    }

    /**
     *
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param string $appRunId
     */
    protected function doAddUserSearchFromUSSRJobSiteFromUSSRAppRunId(ChildUserSearch $userSearchFromUSSR, ChildJobSiteRecord $jobSiteFromUSSR, $appRunId)
    {
        $userSearchSiteRun = new ChildUserSearchSiteRun();

        $userSearchSiteRun->setUserSearchFromUSSR($userSearchFromUSSR);
        $userSearchSiteRun->setJobSiteFromUSSR($jobSiteFromUSSR);
        $userSearchSiteRun->setAppRunId($appRunId);


        $userSearchSiteRun->setGeoLocationFromUSSR($this);

        $this->addUserSearchSiteRun($userSearchSiteRun);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($userSearchFromUSSR->isJobSiteFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
            $userSearchFromUSSR->initJobSiteFromUSSRGeoLocationFromUSSRAppRunIds();
            $userSearchFromUSSR->getJobSiteFromUSSRGeoLocationFromUSSRAppRunIds()->push($jobSiteFromUSSR, $this, $appRunId);
        } elseif (!$userSearchFromUSSR->getJobSiteFromUSSRGeoLocationFromUSSRAppRunIds()->contains($jobSiteFromUSSR, $this, $appRunId)) {
            $userSearchFromUSSR->getJobSiteFromUSSRGeoLocationFromUSSRAppRunIds()->push($jobSiteFromUSSR, $this, $appRunId);
        }

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($jobSiteFromUSSR->isUserSearchFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
            $jobSiteFromUSSR->initUserSearchFromUSSRGeoLocationFromUSSRAppRunIds();
            $jobSiteFromUSSR->getUserSearchFromUSSRGeoLocationFromUSSRAppRunIds()->push($userSearchFromUSSR, $this, $appRunId);
        } elseif (!$jobSiteFromUSSR->getUserSearchFromUSSRGeoLocationFromUSSRAppRunIds()->contains($userSearchFromUSSR, $this, $appRunId)) {
            $jobSiteFromUSSR->getUserSearchFromUSSRGeoLocationFromUSSRAppRunIds()->push($userSearchFromUSSR, $this, $appRunId);
        }

    }

    /**
     * Remove userSearchFromUSSR, jobSiteFromUSSR, appRunId of this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param string $appRunId
     * @return ChildGeoLocation The current object (for fluent API support)
     */
    public function removeUserSearchFromUSSRJobSiteFromUSSRAppRunId(ChildUserSearch $userSearchFromUSSR, ChildJobSiteRecord $jobSiteFromUSSR, $appRunId)
    {
        if ($this->getUserSearchFromUSSRJobSiteFromUSSRAppRunIds()->contains($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId)) {
            $userSearchSiteRun = new ChildUserSearchSiteRun();
            $userSearchSiteRun->setUserSearchFromUSSR($userSearchFromUSSR);
            if ($userSearchFromUSSR->isJobSiteFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $userSearchFromUSSR->getJobSiteFromUSSRGeoLocationFromUSSRAppRunIds()->removeObject($jobSiteFromUSSR, $this, $appRunId);
            }

            $userSearchSiteRun->setJobSiteFromUSSR($jobSiteFromUSSR);
            if ($jobSiteFromUSSR->isUserSearchFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $jobSiteFromUSSR->getUserSearchFromUSSRGeoLocationFromUSSRAppRunIds()->removeObject($userSearchFromUSSR, $this, $appRunId);
            }

            $userSearchSiteRun->setAppRunId($appRunId);
            $userSearchSiteRun->setGeoLocationFromUSSR($this);
            $this->removeUserSearchSiteRun(clone $userSearchSiteRun);
            $userSearchSiteRun->clear();

            $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds->remove($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds->search($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId));

            if (null === $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion) {
                $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion = clone $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds;
                $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion->clear();
            }

            $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion->push($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId);
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
        $this->geolocation_id = null;
        $this->display_name = null;
        $this->geolocation_key = null;
        $this->place = null;
        $this->county = null;
        $this->region = null;
        $this->regioncode = null;
        $this->country = null;
        $this->countrycode = null;
        $this->latitude = null;
        $this->longitude = null;
        $this->alternate_names = null;
        $this->alternate_names_unserialized = null;
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
            if ($this->collUserSearches) {
                foreach ($this->collUserSearches as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collUserSearchSiteRuns) {
                foreach ($this->collUserSearchSiteRuns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collUserKeywordSetFromuses) {
                foreach ($this->collUserKeywordSetFromuses as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds) {
                foreach ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collJobPostings = null;
        $this->collUserSearches = null;
        $this->collUserSearchSiteRuns = null;
        $this->collUserKeywordSetFromuses = null;
        $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'display_name' column
     */
    public function __toString()
    {
        return (string) $this->getDisplayName();
    }

    // geocodable behavior

    /**
     * Convenient method to set latitude and longitude values.
     *
     * @param double $latitude     A latitude value.
     * @param double $longitude    A longitude value.
     */
    public function setCoordinates($latitude, $longitude)
    {
        $this->setLatitude($latitude);
        $this->setLongitude($longitude);
    }

    /**
     * Returns an array with latitude and longitude values.
     *
     * @return array
     */
    public function getCoordinates()
    {
        return array(
            'latitude'  => $this->getLatitude(),
            'longitude' => $this->getLongitude()
        );
    }

    /**
     * Returns whether this object has been geocoded or not.
     *
     * @return boolean
     */
    public function isGeocoded()
    {
        $lat = $this->getLatitude();
        $lng = $this->getLongitude();

        return (!empty($lat) && !empty($lng));
    }

    /**
     * Calculates the distance between a given geolocation and this one.
     *
     * @param \JobScooper\DataAccess\GeoLocation $geolocation    A \JobScooper\DataAccess\GeoLocation object.
     * @param double $unit     The unit measure.
     *
     * @return double   The distance between the two objects.
     */
    public function getDistanceTo(\JobScooper\DataAccess\GeoLocation $geolocation, $unit = GeoLocationTableMap::KILOMETERS_UNIT)
    {
        $dist = rad2deg(acos(round(sin(deg2rad($this->getLatitude())) * sin(deg2rad($geolocation->getLatitude())) +  cos(deg2rad($this->getLatitude())) * cos(deg2rad($geolocation->getLatitude())) * cos(deg2rad($this->getLongitude() - $geolocation->getLongitude())),14))) * 60 * GeoLocationTableMap::MILES_UNIT;

        if (GeoLocationTableMap::MILES_UNIT === $unit) {
            return $dist;
        } elseif (GeoLocationTableMap::NAUTICAL_MILES_UNIT === $unit) {
            return $dist * GeoLocationTableMap::NAUTICAL_MILES_UNIT;
        }

        return $dist * GeoLocationTableMap::KILOMETERS_UNIT;
    }

    /**
     * Update geocode information.
     * You can extend this method to fill in other fields.
     *
     * @return \Geocoder\Result\ResultInterface|null
     */
    public function geocode()
    {
        // Do nothing as both 'geocode_ip', and 'geocode_address' are turned off.
        return null;
    }

    /**
     * Check whether the current object is required to be geocoded (again).
     *
     * @return boolean
     */
    public function isGeocodingNecessary()
    {

        return false;
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
