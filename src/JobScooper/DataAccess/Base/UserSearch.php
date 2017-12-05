<?php

namespace JobScooper\DataAccess\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\DataAccess\GeoLocation as ChildGeoLocation;
use JobScooper\DataAccess\GeoLocationQuery as ChildGeoLocationQuery;
use JobScooper\DataAccess\JobSiteRecord as ChildJobSiteRecord;
use JobScooper\DataAccess\JobSiteRecordQuery as ChildJobSiteRecordQuery;
use JobScooper\DataAccess\User as ChildUser;
use JobScooper\DataAccess\UserKeywordSet as ChildUserKeywordSet;
use JobScooper\DataAccess\UserKeywordSetQuery as ChildUserKeywordSetQuery;
use JobScooper\DataAccess\UserQuery as ChildUserQuery;
use JobScooper\DataAccess\UserSearch as ChildUserSearch;
use JobScooper\DataAccess\UserSearchQuery as ChildUserSearchQuery;
use JobScooper\DataAccess\UserSearchSiteRun as ChildUserSearchSiteRun;
use JobScooper\DataAccess\UserSearchSiteRunQuery as ChildUserSearchSiteRunQuery;
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
use Propel\Runtime\Util\PropelDateTime;

/**
 * Base class that represents a row from the 'user_search' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.DataAccess.Base
 */
abstract class UserSearch implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\DataAccess\\Map\\UserSearchTableMap';


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
     * The value for the user_id field.
     *
     * @var        int
     */
    protected $user_id;

    /**
     * The value for the user_keyword_set_id field.
     *
     * @var        int
     */
    protected $user_keyword_set_id;

    /**
     * The value for the geolocation_id field.
     *
     * @var        int
     */
    protected $geolocation_id;

    /**
     * The value for the user_search_id field.
     *
     * @var        int
     */
    protected $user_search_id;

    /**
     * The value for the date_created field.
     *
     * @var        DateTime
     */
    protected $date_created;

    /**
     * The value for the date_updated field.
     *
     * @var        DateTime
     */
    protected $date_updated;

    /**
     * The value for the user_search_key field.
     *
     * @var        string
     */
    protected $user_search_key;

    /**
     * @var        ChildUserKeywordSet
     */
    protected $aUserKeywordSetFromUS;

    /**
     * @var        ChildGeoLocation
     */
    protected $aGeoLocationFromUS;

    /**
     * @var        ChildUser
     */
    protected $aUserFromUS;

    /**
     * @var        ObjectCollection|ChildUserSearchSiteRun[] Collection to store aggregation of ChildUserSearchSiteRun objects.
     */
    protected $collUserSearchSiteRuns;
    protected $collUserSearchSiteRunsPartial;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildJobSiteRecord, ChildUser, ChildUserKeywordSet, ChildGeoLocation combination combinations.
     */
    protected $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds;

    /**
     * @var bool
     */
    protected $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsPartial;

    /**
     * @var        ObjectCollection|ChildJobSiteRecord[] Cross Collection to store aggregation of ChildJobSiteRecord objects.
     */
    protected $collJobSiteFromUSSRs;

    /**
     * @var bool
     */
    protected $collJobSiteFromUSSRsPartial;

    /**
     * @var        ObjectCollection|ChildUser[] Cross Collection to store aggregation of ChildUser objects.
     */
    protected $collUserFromUSSRs;

    /**
     * @var bool
     */
    protected $collUserFromUSSRsPartial;

    /**
     * @var        ObjectCollection|ChildUserKeywordSet[] Cross Collection to store aggregation of ChildUserKeywordSet objects.
     */
    protected $collUserKeywordSetFromUSSRs;

    /**
     * @var bool
     */
    protected $collUserKeywordSetFromUSSRsPartial;

    /**
     * @var        ObjectCollection|ChildGeoLocation[] Cross Collection to store aggregation of ChildGeoLocation objects.
     */
    protected $collGeoLocationFromUSSRs;

    /**
     * @var bool
     */
    protected $collGeoLocationFromUSSRsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildJobSiteRecord, ChildUser, ChildUserKeywordSet, ChildGeoLocation combination combinations.
     */
    protected $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserSearchSiteRun[]
     */
    protected $userSearchSiteRunsScheduledForDeletion = null;

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\UserSearch object.
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
     * Compares this with another <code>UserSearch</code> instance.  If
     * <code>obj</code> is an instance of <code>UserSearch</code>, delegates to
     * <code>equals(UserSearch)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|UserSearch The current object, for fluid interface
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
     * Get the [user_id] column value.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Get the [user_keyword_set_id] column value.
     *
     * @return int
     */
    public function getUserKeywordSetId()
    {
        return $this->user_keyword_set_id;
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
     * Get the [user_search_id] column value.
     *
     * @return int
     */
    public function getUserSearchId()
    {
        return $this->user_search_id;
    }

    /**
     * Get the [optionally formatted] temporal [date_created] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
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
     * Get the [optionally formatted] temporal [date_updated] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getUpdatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->date_updated;
        } else {
            return $this->date_updated instanceof \DateTimeInterface ? $this->date_updated->format($format) : null;
        }
    }

    /**
     * Get the [user_search_key] column value.
     *
     * @return string
     */
    public function getUserSearchKey()
    {
        return $this->user_search_key;
    }

    /**
     * Set the value of [user_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setUserId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_id !== $v) {
            $this->user_id = $v;
            $this->modifiedColumns[UserSearchTableMap::COL_USER_ID] = true;
        }

        if ($this->aUserKeywordSetFromUS !== null && $this->aUserKeywordSetFromUS->getUserId() !== $v) {
            $this->aUserKeywordSetFromUS = null;
        }

        if ($this->aUserFromUS !== null && $this->aUserFromUS->getUserId() !== $v) {
            $this->aUserFromUS = null;
        }

        return $this;
    } // setUserId()

    /**
     * Set the value of [user_keyword_set_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setUserKeywordSetId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_keyword_set_id !== $v) {
            $this->user_keyword_set_id = $v;
            $this->modifiedColumns[UserSearchTableMap::COL_USER_KEYWORD_SET_ID] = true;
        }

        if ($this->aUserKeywordSetFromUS !== null && $this->aUserKeywordSetFromUS->getUserKeywordSetId() !== $v) {
            $this->aUserKeywordSetFromUS = null;
        }

        return $this;
    } // setUserKeywordSetId()

    /**
     * Set the value of [geolocation_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setGeoLocationId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->geolocation_id !== $v) {
            $this->geolocation_id = $v;
            $this->modifiedColumns[UserSearchTableMap::COL_GEOLOCATION_ID] = true;
        }

        if ($this->aGeoLocationFromUS !== null && $this->aGeoLocationFromUS->getGeoLocationId() !== $v) {
            $this->aGeoLocationFromUS = null;
        }

        return $this;
    } // setGeoLocationId()

    /**
     * Set the value of [user_search_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setUserSearchId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_search_id !== $v) {
            $this->user_search_id = $v;
            $this->modifiedColumns[UserSearchTableMap::COL_USER_SEARCH_ID] = true;
        }

        return $this;
    } // setUserSearchId()

    /**
     * Sets the value of [date_created] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_created !== null || $dt !== null) {
            if ($this->date_created === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_created->format("Y-m-d H:i:s.u")) {
                $this->date_created = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchTableMap::COL_DATE_CREATED] = true;
            }
        } // if either are not null

        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [date_updated] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_updated !== null || $dt !== null) {
            if ($this->date_updated === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_updated->format("Y-m-d H:i:s.u")) {
                $this->date_updated = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchTableMap::COL_DATE_UPDATED] = true;
            }
        } // if either are not null

        return $this;
    } // setUpdatedAt()

    /**
     * Set the value of [user_search_key] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setUserSearchKey($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->user_search_key !== $v) {
            $this->user_search_key = $v;
            $this->modifiedColumns[UserSearchTableMap::COL_USER_SEARCH_KEY] = true;
        }

        return $this;
    } // setUserSearchKey()

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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserSearchTableMap::translateFieldName('UserId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserSearchTableMap::translateFieldName('UserKeywordSetId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_keyword_set_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserSearchTableMap::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->geolocation_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserSearchTableMap::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : UserSearchTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->date_created = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : UserSearchTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->date_updated = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : UserSearchTableMap::translateFieldName('UserSearchKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_key = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 7; // 7 = UserSearchTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\DataAccess\\UserSearch'), 0, $e);
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
        if ($this->aUserKeywordSetFromUS !== null && $this->user_id !== $this->aUserKeywordSetFromUS->getUserId()) {
            $this->aUserKeywordSetFromUS = null;
        }
        if ($this->aUserFromUS !== null && $this->user_id !== $this->aUserFromUS->getUserId()) {
            $this->aUserFromUS = null;
        }
        if ($this->aUserKeywordSetFromUS !== null && $this->user_keyword_set_id !== $this->aUserKeywordSetFromUS->getUserKeywordSetId()) {
            $this->aUserKeywordSetFromUS = null;
        }
        if ($this->aGeoLocationFromUS !== null && $this->geolocation_id !== $this->aGeoLocationFromUS->getGeoLocationId()) {
            $this->aGeoLocationFromUS = null;
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
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildUserSearchQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aUserKeywordSetFromUS = null;
            $this->aGeoLocationFromUS = null;
            $this->aUserFromUS = null;
            $this->collUserSearchSiteRuns = null;

            $this->collJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see UserSearch::setDeleted()
     * @see UserSearch::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildUserSearchQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con, $skipReload) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            // sluggable behavior

            if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_KEY) && $this->getUserSearchKey()) {
                $this->setUserSearchKey($this->makeSlugUnique($this->getUserSearchKey()));
            } else {
                $this->setUserSearchKey($this->createSlug());
            }
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior

                if (!$this->isColumnModified(UserSearchTableMap::COL_DATE_CREATED)) {
                    $this->setCreatedAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
                if (!$this->isColumnModified(UserSearchTableMap::COL_DATE_UPDATED)) {
                    $this->setUpdatedAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(UserSearchTableMap::COL_DATE_UPDATED)) {
                    $this->setUpdatedAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
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
                UserSearchTableMap::addInstanceToPool($this);
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

            if ($this->aUserKeywordSetFromUS !== null) {
                if ($this->aUserKeywordSetFromUS->isModified() || $this->aUserKeywordSetFromUS->isNew()) {
                    $affectedRows += $this->aUserKeywordSetFromUS->save($con);
                }
                $this->setUserKeywordSetFromUS($this->aUserKeywordSetFromUS);
            }

            if ($this->aGeoLocationFromUS !== null) {
                if ($this->aGeoLocationFromUS->isModified() || $this->aGeoLocationFromUS->isNew()) {
                    $affectedRows += $this->aGeoLocationFromUS->save($con);
                }
                $this->setGeoLocationFromUS($this->aGeoLocationFromUS);
            }

            if ($this->aUserFromUS !== null) {
                if ($this->aUserFromUS->isModified() || $this->aUserFromUS->isNew()) {
                    $affectedRows += $this->aUserFromUS->save($con);
                }
                $this->setUserFromUS($this->aUserFromUS);
            }

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

            if ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion !== null) {
                if (!$this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion as $combination) {
                        $entryPk = [];

                        $entryPk[0] = $this->getUserId();
                        $entryPk[1] = $this->getUserKeywordSetId();
                        $entryPk[2] = $this->getGeoLocationId();
                        $entryPk[3] = $this->getUserSearchId();
                        $entryPk[4] = $combination[0]->getJobSiteKey();
                        $entryPk[0] = $combination[1]->getUserId();
                        $entryPk[0] = $combination[2]->getUserId();
                        $entryPk[1] = $combination[2]->getUserKeywordSetId();
                        $entryPk[2] = $combination[3]->getGeoLocationId();
                        //$combination[4] = AppRunId;
                        $entryPk[5] = $combination[4];

                        $pks[] = $entryPk;
                    }

                    \JobScooper\DataAccess\UserSearchSiteRunQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion = null;
                }

            }

            if (null !== $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds) {
                foreach ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds as $combination) {

                    //$combination[0] = JobSiteRecord (user_search_site_run_fk_168d10)
                    if (!$combination[0]->isDeleted() && ($combination[0]->isNew() || $combination[0]->isModified())) {
                        $combination[0]->save($con);
                    }

                    //$combination[1] = User (user_search_site_run_fk_38da1e)
                    if (!$combination[1]->isDeleted() && ($combination[1]->isNew() || $combination[1]->isModified())) {
                        $combination[1]->save($con);
                    }

                    //$combination[2] = UserKeywordSet (user_search_site_run_fk_09a66b)
                    if (!$combination[2]->isDeleted() && ($combination[2]->isNew() || $combination[2]->isModified())) {
                        $combination[2]->save($con);
                    }

                    //$combination[3] = GeoLocation (user_search_site_run_fk_38c4c7)
                    if (!$combination[3]->isDeleted() && ($combination[3]->isNew() || $combination[3]->isModified())) {
                        $combination[3]->save($con);
                    }

                    //$combination[4] = AppRunId; Nothing to save.
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
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_id';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_KEYWORD_SET_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_keyword_set_id';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_GEOLOCATION_ID)) {
            $modifiedColumns[':p' . $index++]  = 'geolocation_id';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_id';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_CREATED)) {
            $modifiedColumns[':p' . $index++]  = 'date_created';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_UPDATED)) {
            $modifiedColumns[':p' . $index++]  = 'date_updated';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_key';
        }

        $sql = sprintf(
            'INSERT INTO user_search (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'user_id':
                        $stmt->bindValue($identifier, $this->user_id, PDO::PARAM_INT);
                        break;
                    case 'user_keyword_set_id':
                        $stmt->bindValue($identifier, $this->user_keyword_set_id, PDO::PARAM_INT);
                        break;
                    case 'geolocation_id':
                        $stmt->bindValue($identifier, $this->geolocation_id, PDO::PARAM_INT);
                        break;
                    case 'user_search_id':
                        $stmt->bindValue($identifier, $this->user_search_id, PDO::PARAM_INT);
                        break;
                    case 'date_created':
                        $stmt->bindValue($identifier, $this->date_created ? $this->date_created->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_updated':
                        $stmt->bindValue($identifier, $this->date_updated ? $this->date_updated->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'user_search_key':
                        $stmt->bindValue($identifier, $this->user_search_key, PDO::PARAM_STR);
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
        $this->setUserId($pk);

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
        $pos = UserSearchTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getUserId();
                break;
            case 1:
                return $this->getUserKeywordSetId();
                break;
            case 2:
                return $this->getGeoLocationId();
                break;
            case 3:
                return $this->getUserSearchId();
                break;
            case 4:
                return $this->getCreatedAt();
                break;
            case 5:
                return $this->getUpdatedAt();
                break;
            case 6:
                return $this->getUserSearchKey();
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

        if (isset($alreadyDumpedObjects['UserSearch'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['UserSearch'][$this->hashCode()] = true;
        $keys = UserSearchTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getUserId(),
            $keys[1] => $this->getUserKeywordSetId(),
            $keys[2] => $this->getGeoLocationId(),
            $keys[3] => $this->getUserSearchId(),
            $keys[4] => $this->getCreatedAt(),
            $keys[5] => $this->getUpdatedAt(),
            $keys[6] => $this->getUserSearchKey(),
        );
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

        if ($includeForeignObjects) {
            if (null !== $this->aUserKeywordSetFromUS) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userKeywordSet';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_keyword_set';
                        break;
                    default:
                        $key = 'UserKeywordSetFromUS';
                }

                $result[$key] = $this->aUserKeywordSetFromUS->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aGeoLocationFromUS) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'geoLocation';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'geolocation';
                        break;
                    default:
                        $key = 'GeoLocationFromUS';
                }

                $result[$key] = $this->aGeoLocationFromUS->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aUserFromUS) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'user';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user';
                        break;
                    default:
                        $key = 'UserFromUS';
                }

                $result[$key] = $this->aUserFromUS->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
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
     * @return $this|\JobScooper\DataAccess\UserSearch
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserSearchTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\DataAccess\UserSearch
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setUserId($value);
                break;
            case 1:
                $this->setUserKeywordSetId($value);
                break;
            case 2:
                $this->setGeoLocationId($value);
                break;
            case 3:
                $this->setUserSearchId($value);
                break;
            case 4:
                $this->setCreatedAt($value);
                break;
            case 5:
                $this->setUpdatedAt($value);
                break;
            case 6:
                $this->setUserSearchKey($value);
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
        $keys = UserSearchTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setUserId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setUserKeywordSetId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setGeoLocationId($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setUserSearchId($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setCreatedAt($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setUpdatedAt($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setUserSearchKey($arr[$keys[6]]);
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
     * @return $this|\JobScooper\DataAccess\UserSearch The current object, for fluid interface
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
        $criteria = new Criteria(UserSearchTableMap::DATABASE_NAME);

        if ($this->isColumnModified(UserSearchTableMap::COL_USER_ID)) {
            $criteria->add(UserSearchTableMap::COL_USER_ID, $this->user_id);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_KEYWORD_SET_ID)) {
            $criteria->add(UserSearchTableMap::COL_USER_KEYWORD_SET_ID, $this->user_keyword_set_id);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_GEOLOCATION_ID)) {
            $criteria->add(UserSearchTableMap::COL_GEOLOCATION_ID, $this->geolocation_id);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_ID)) {
            $criteria->add(UserSearchTableMap::COL_USER_SEARCH_ID, $this->user_search_id);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_CREATED)) {
            $criteria->add(UserSearchTableMap::COL_DATE_CREATED, $this->date_created);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_UPDATED)) {
            $criteria->add(UserSearchTableMap::COL_DATE_UPDATED, $this->date_updated);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_KEY)) {
            $criteria->add(UserSearchTableMap::COL_USER_SEARCH_KEY, $this->user_search_key);
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
        $criteria = ChildUserSearchQuery::create();
        $criteria->add(UserSearchTableMap::COL_USER_ID, $this->user_id);
        $criteria->add(UserSearchTableMap::COL_USER_KEYWORD_SET_ID, $this->user_keyword_set_id);
        $criteria->add(UserSearchTableMap::COL_GEOLOCATION_ID, $this->geolocation_id);

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
        $validPk = null !== $this->getUserId() &&
            null !== $this->getUserKeywordSetId() &&
            null !== $this->getGeoLocationId();

        $validPrimaryKeyFKs = 4;
        $primaryKeyFKs = [];

        //relation user_search_fk_b9a4d0 to table user_keyword_set
        if ($this->aUserKeywordSetFromUS && $hash = spl_object_hash($this->aUserKeywordSetFromUS)) {
            $primaryKeyFKs[] = $hash;
        } else {
            $validPrimaryKeyFKs = false;
        }

        //relation user_search_fk_38c4c7 to table geolocation
        if ($this->aGeoLocationFromUS && $hash = spl_object_hash($this->aGeoLocationFromUS)) {
            $primaryKeyFKs[] = $hash;
        } else {
            $validPrimaryKeyFKs = false;
        }

        //relation user_search_fk_38da1e to table user
        if ($this->aUserFromUS && $hash = spl_object_hash($this->aUserFromUS)) {
            $primaryKeyFKs[] = $hash;
        } else {
            $validPrimaryKeyFKs = false;
        }

        if ($validPk) {
            return crc32(json_encode($this->getPrimaryKey(), JSON_UNESCAPED_UNICODE));
        } elseif ($validPrimaryKeyFKs) {
            return crc32(json_encode($primaryKeyFKs, JSON_UNESCAPED_UNICODE));
        }

        return spl_object_hash($this);
    }

    /**
     * Returns the composite primary key for this object.
     * The array elements will be in same order as specified in XML.
     * @return array
     */
    public function getPrimaryKey()
    {
        $pks = array();
        $pks[0] = $this->getUserId();
        $pks[1] = $this->getUserKeywordSetId();
        $pks[2] = $this->getGeoLocationId();

        return $pks;
    }

    /**
     * Set the [composite] primary key.
     *
     * @param      array $keys The elements of the composite key (order must match the order in XML file).
     * @return void
     */
    public function setPrimaryKey($keys)
    {
        $this->setUserId($keys[0]);
        $this->setUserKeywordSetId($keys[1]);
        $this->setGeoLocationId($keys[2]);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return (null === $this->getUserId()) && (null === $this->getUserKeywordSetId()) && (null === $this->getGeoLocationId());
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\DataAccess\UserSearch (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setUserId($this->getUserId());
        $copyObj->setUserKeywordSetId($this->getUserKeywordSetId());
        $copyObj->setGeoLocationId($this->getGeoLocationId());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());
        $copyObj->setUserSearchKey($this->getUserSearchKey());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getUserSearchSiteRuns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserSearchSiteRun($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setUserSearchId(NULL); // this is a auto-increment column, so set to default value
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
     * @return \JobScooper\DataAccess\UserSearch Clone of current object.
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
     * Declares an association between this object and a ChildUserKeywordSet object.
     *
     * @param  ChildUserKeywordSet $v
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUserKeywordSetFromUS(ChildUserKeywordSet $v = null)
    {
        if ($v === null) {
            $this->setUserKeywordSetId(NULL);
        } else {
            $this->setUserKeywordSetId($v->getUserKeywordSetId());
        }

        if ($v === null) {
            $this->setUserId(NULL);
        } else {
            $this->setUserId($v->getUserId());
        }

        $this->aUserKeywordSetFromUS = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUserKeywordSet object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearch($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildUserKeywordSet object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildUserKeywordSet The associated ChildUserKeywordSet object.
     * @throws PropelException
     */
    public function getUserKeywordSetFromUS(ConnectionInterface $con = null)
    {
        if ($this->aUserKeywordSetFromUS === null && ($this->user_keyword_set_id != 0 && $this->user_id != 0)) {
            $this->aUserKeywordSetFromUS = ChildUserKeywordSetQuery::create()->findPk(array($this->user_id, $this->user_keyword_set_id), $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUserKeywordSetFromUS->addUserSearches($this);
             */
        }

        return $this->aUserKeywordSetFromUS;
    }

    /**
     * Declares an association between this object and a ChildGeoLocation object.
     *
     * @param  ChildGeoLocation $v
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     * @throws PropelException
     */
    public function setGeoLocationFromUS(ChildGeoLocation $v = null)
    {
        if ($v === null) {
            $this->setGeoLocationId(NULL);
        } else {
            $this->setGeoLocationId($v->getGeoLocationId());
        }

        $this->aGeoLocationFromUS = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildGeoLocation object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearch($this);
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
    public function getGeoLocationFromUS(ConnectionInterface $con = null)
    {
        if ($this->aGeoLocationFromUS === null && ($this->geolocation_id != 0)) {
            $this->aGeoLocationFromUS = ChildGeoLocationQuery::create()->findPk($this->geolocation_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aGeoLocationFromUS->addUserSearches($this);
             */
        }

        return $this->aGeoLocationFromUS;
    }

    /**
     * Declares an association between this object and a ChildUser object.
     *
     * @param  ChildUser $v
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUserFromUS(ChildUser $v = null)
    {
        if ($v === null) {
            $this->setUserId(NULL);
        } else {
            $this->setUserId($v->getUserId());
        }

        $this->aUserFromUS = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUser object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearch($this);
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
    public function getUserFromUS(ConnectionInterface $con = null)
    {
        if ($this->aUserFromUS === null && ($this->user_id != 0)) {
            $this->aUserFromUS = ChildUserQuery::create()->findPk($this->user_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUserFromUS->addUserSearches($this);
             */
        }

        return $this->aUserFromUS;
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
        if ('UserSearchSiteRun' == $relationName) {
            $this->initUserSearchSiteRuns();
            return;
        }
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
     * If this ChildUserSearch is new, it will return
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
                    ->filterByUserSearchFromUSSR($this)
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
     * @return $this|ChildUserSearch The current object (for fluent API support)
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
            $userSearchSiteRunRemoved->setUserSearchFromUSSR(null);
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
                ->filterByUserSearchFromUSSR($this)
                ->count($con);
        }

        return count($this->collUserSearchSiteRuns);
    }

    /**
     * Method called to associate a ChildUserSearchSiteRun object to this object
     * through the ChildUserSearchSiteRun foreign key attribute.
     *
     * @param  ChildUserSearchSiteRun $l ChildUserSearchSiteRun
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
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
        $userSearchSiteRun->setUserSearchFromUSSR($this);
    }

    /**
     * @param  ChildUserSearchSiteRun $userSearchSiteRun The ChildUserSearchSiteRun object to remove.
     * @return $this|ChildUserSearch The current object (for fluent API support)
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
            $userSearchSiteRun->setUserSearchFromUSSR(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this UserSearch is new, it will return
     * an empty collection; or if this UserSearch has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserSearch.
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
     * Otherwise if this UserSearch is new, it will return
     * an empty collection; or if this UserSearch has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserSearch.
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
     * Otherwise if this UserSearch is new, it will return
     * an empty collection; or if this UserSearch has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserSearch.
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
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this UserSearch is new, it will return
     * an empty collection; or if this UserSearch has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserSearch.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearchSiteRun[] List of ChildUserSearchSiteRun objects
     */
    public function getUserSearchSiteRunsJoinGeoLocationFromUSSR(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
        $query->joinWith('GeoLocationFromUSSR', $joinBehavior);

        return $this->getUserSearchSiteRuns($query, $con);
    }

    /**
     * Clears out the collJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()
     */
    public function clearJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()
    {
        $this->collJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds crossRef collection.
     *
     * By default this just sets the combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds collection to an empty collection (like clearJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()
    {
        $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds = new ObjectCombinationCollection;
        $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsPartial = true;
    }

    /**
     * Checks if the combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds collection is loaded.
     *
     * @return bool
     */
    public function isJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()
    {
        return null !== $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds;
    }

    /**
     * Returns a new query object pre configured with filters from current object and given arguments to query the database.
     *
     * @param ChildUser $userFromUSSR,
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     *
     * @return ChildJobSiteRecordQuery
     */
    public function createJobSiteFromUSSRsQuery(ChildUser $userFromUSSR = null, ChildUserKeywordSet $userKeywordSetFromUSSR = null, ChildGeoLocation $geoLocationFromUSSR = null, $appRunId = null, Criteria $criteria = null)
    {
        $criteria = ChildJobSiteRecordQuery::create($criteria)
            ->filterByUserSearchFromUSSR($this);

        $userSearchSiteRunQuery = $criteria->useUserSearchSiteRunQuery();

        if (null !== $userFromUSSR) {
            $userSearchSiteRunQuery->filterByUserFromUSSR($userFromUSSR);
        }

        if (null !== $userKeywordSetFromUSSR) {
            $userSearchSiteRunQuery->filterByUserKeywordSetFromUSSR($userKeywordSetFromUSSR);
        }

        if (null !== $geoLocationFromUSSR) {
            $userSearchSiteRunQuery->filterByGeoLocationFromUSSR($geoLocationFromUSSR);
        }

        if (null !== $appRunId) {
            $userSearchSiteRunQuery->filterByAppRunId($appRunId);
        }

        $userSearchSiteRunQuery->endUse();

        return $criteria;
    }

    /**
     * Gets a combined collection of ChildJobSiteRecord, ChildUser, ChildUserKeywordSet, ChildGeoLocation objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUserSearch is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCombinationCollection Combination list of ChildJobSiteRecord, ChildUser, ChildUserKeywordSet, ChildGeoLocation objects
     */
    public function getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsPartial && !$this->isNew();
        if (null === $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds) {
                    $this->initJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();
                }
            } else {

                $query = ChildUserSearchSiteRunQuery::create(null, $criteria)
                    ->filterByUserSearchFromUSSR($this)
                    ->joinJobSiteFromUSSR()
                    ->joinUserFromUSSR()
                    ->joinUserKeywordSetFromUSSR()
                    ->joinGeoLocationFromUSSR()
                ;

                $items = $query->find($con);
                $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds = new ObjectCombinationCollection();
                foreach ($items as $item) {
                    $combination = [];

                    $combination[] = $item->getJobSiteFromUSSR();
                    $combination[] = $item->getUserFromUSSR();
                    $combination[] = $item->getUserKeywordSetFromUSSR();
                    $combination[] = $item->getGeoLocationFromUSSR();
                    $combination[] = $item->getAppRunId();
                    $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds[] = $combination;
                }

                if (null !== $criteria) {
                    return $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds;
                }

                if ($partial && $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds as $obj) {
                        if (!call_user_func_array([$combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds, 'contains'], $obj)) {
                            $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds[] = $obj;
                        }
                    }
                }

                $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds = $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds;
                $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsPartial = false;
            }
        }

        return $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds;
    }

    /**
     * Returns a not cached ObjectCollection of ChildJobSiteRecord objects. This will hit always the databases.
     * If you have attached new ChildJobSiteRecord object to this object you need to call `save` first to get
     * the correct return value. Use getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds() to get the current internal state.
     *
     * @param ChildUser $userFromUSSR,
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return ChildJobSiteRecord[]|ObjectCollection
     */
    public function getJobSiteFromUSSRs(ChildUser $userFromUSSR = null, ChildUserKeywordSet $userKeywordSetFromUSSR = null, ChildGeoLocation $geoLocationFromUSSR = null, $appRunId = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createJobSiteFromUSSRsQuery($userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId, $criteria)->find($con);
    }

    /**
     * Sets a collection of ChildJobSiteRecord, ChildUser, ChildUserKeywordSet, ChildGeoLocation combination objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $jobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildUserSearch The current object (for fluent API support)
     */
    public function setJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds(Collection $jobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds, ConnectionInterface $con = null)
    {
        $this->clearJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();
        $currentJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds = $this->getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();

        $combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion = $currentJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds->diff($jobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds);

        foreach ($combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion as $toDelete) {
            call_user_func_array([$this, 'removeJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId'], $toDelete);
        }

        foreach ($jobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds as $jobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId) {
            if (!call_user_func_array([$currentJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds, 'contains'], $jobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId)) {
                call_user_func_array([$this, 'doAddJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId'], $jobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId);
            }
        }

        $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsPartial = false;
        $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds = $jobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds;

        return $this;
    }

    /**
     * Gets the number of ChildJobSiteRecord, ChildUser, ChildUserKeywordSet, ChildGeoLocation combination objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ChildJobSiteRecord, ChildUser, ChildUserKeywordSet, ChildGeoLocation combination objects
     */
    public function countJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsPartial && !$this->isNew();
        if (null === $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds());
                }

                $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByUserSearchFromUSSR($this)
                    ->count($con);
            }
        } else {
            return count($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds);
        }
    }

    /**
     * Returns the not cached count of ChildJobSiteRecord objects. This will hit always the databases.
     * If you have attached new ChildJobSiteRecord object to this object you need to call `save` first to get
     * the correct return value. Use getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds() to get the current internal state.
     *
     * @param ChildUser $userFromUSSR,
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return integer
     */
    public function countJobSiteFromUSSRs(ChildUser $userFromUSSR = null, ChildUserKeywordSet $userKeywordSetFromUSSR = null, ChildGeoLocation $geoLocationFromUSSR = null, $appRunId = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createJobSiteFromUSSRsQuery($userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId, $criteria)->count($con);
    }

    /**
     * Associate a ChildJobSiteRecord to this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUser $userFromUSSR,
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param string $appRunId
     * @return ChildUserSearch The current object (for fluent API support)
     */
    public function addJobSiteFromUSSR(ChildJobSiteRecord $jobSiteFromUSSR, ChildUser $userFromUSSR, ChildUserKeywordSet $userKeywordSetFromUSSR, ChildGeoLocation $geoLocationFromUSSR, $appRunId)
    {
        if ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds === null) {
            $this->initJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();
        }

        if (!$this->getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->contains($jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds->push($jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
            $this->doAddJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId($jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
        }

        return $this;
    }

    /**
     * Associate a ChildUser to this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildUser $userFromUSSR,
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param string $appRunId
     * @return ChildUserSearch The current object (for fluent API support)
     */
    public function addUserFromUSSR(ChildUser $userFromUSSR, ChildJobSiteRecord $jobSiteFromUSSR, ChildUserKeywordSet $userKeywordSetFromUSSR, ChildGeoLocation $geoLocationFromUSSR, $appRunId)
    {
        if ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds === null) {
            $this->initJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();
        }

        if (!$this->getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->contains($userFromUSSR, $jobSiteFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds->push($userFromUSSR, $jobSiteFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
            $this->doAddJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId($userFromUSSR, $jobSiteFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
        }

        return $this;
    }

    /**
     * Associate a ChildUserKeywordSet to this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUser $userFromUSSR,
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param string $appRunId
     * @return ChildUserSearch The current object (for fluent API support)
     */
    public function addUserKeywordSetFromUSSR(ChildUserKeywordSet $userKeywordSetFromUSSR, ChildJobSiteRecord $jobSiteFromUSSR, ChildUser $userFromUSSR, ChildGeoLocation $geoLocationFromUSSR, $appRunId)
    {
        if ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds === null) {
            $this->initJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();
        }

        if (!$this->getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->contains($userKeywordSetFromUSSR, $jobSiteFromUSSR, $userFromUSSR, $geoLocationFromUSSR, $appRunId)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds->push($userKeywordSetFromUSSR, $jobSiteFromUSSR, $userFromUSSR, $geoLocationFromUSSR, $appRunId);
            $this->doAddJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId($userKeywordSetFromUSSR, $jobSiteFromUSSR, $userFromUSSR, $geoLocationFromUSSR, $appRunId);
        }

        return $this;
    }

    /**
     * Associate a ChildGeoLocation to this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUser $userFromUSSR,
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param string $appRunId
     * @return ChildUserSearch The current object (for fluent API support)
     */
    public function addGeoLocationFromUSSR(ChildGeoLocation $geoLocationFromUSSR, ChildJobSiteRecord $jobSiteFromUSSR, ChildUser $userFromUSSR, ChildUserKeywordSet $userKeywordSetFromUSSR, $appRunId)
    {
        if ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds === null) {
            $this->initJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();
        }

        if (!$this->getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->contains($geoLocationFromUSSR, $jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $appRunId)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds->push($geoLocationFromUSSR, $jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $appRunId);
            $this->doAddJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId($geoLocationFromUSSR, $jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $appRunId);
        }

        return $this;
    }

    /**
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUser $userFromUSSR,
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param string $appRunId
     */
    protected function doAddJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId(ChildJobSiteRecord $jobSiteFromUSSR, ChildUser $userFromUSSR, ChildUserKeywordSet $userKeywordSetFromUSSR, ChildGeoLocation $geoLocationFromUSSR, $appRunId)
    {
        $userSearchSiteRun = new ChildUserSearchSiteRun();

        $userSearchSiteRun->setJobSiteFromUSSR($jobSiteFromUSSR);
        $userSearchSiteRun->setUserFromUSSR($userFromUSSR);
        $userSearchSiteRun->setUserKeywordSetFromUSSR($userKeywordSetFromUSSR);
        $userSearchSiteRun->setGeoLocationFromUSSR($geoLocationFromUSSR);
        $userSearchSiteRun->setAppRunId($appRunId);


        $userSearchSiteRun->setUserSearchFromUSSR($this);

        $this->addUserSearchSiteRun($userSearchSiteRun);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($jobSiteFromUSSR->isUserSearchFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
            $jobSiteFromUSSR->initUserSearchFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();
            $jobSiteFromUSSR->getUserSearchFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->push($this, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
        } elseif (!$jobSiteFromUSSR->getUserSearchFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->contains($this, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId)) {
            $jobSiteFromUSSR->getUserSearchFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->push($this, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
        }

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($userFromUSSR->isUserSearchFromUSSRJobSiteFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
            $userFromUSSR->initUserSearchFromUSSRJobSiteFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds();
            $userFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->push($this, $jobSiteFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
        } elseif (!$userFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->contains($this, $jobSiteFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId)) {
            $userFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->push($this, $jobSiteFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
        }

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($userKeywordSetFromUSSR->isUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
            $userKeywordSetFromUSSR->initUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRGeoLocationFromUSSRAppRunIds();
            $userKeywordSetFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRGeoLocationFromUSSRAppRunIds()->push($this, $jobSiteFromUSSR, $userFromUSSR, $geoLocationFromUSSR, $appRunId);
        } elseif (!$userKeywordSetFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRGeoLocationFromUSSRAppRunIds()->contains($this, $jobSiteFromUSSR, $userFromUSSR, $geoLocationFromUSSR, $appRunId)) {
            $userKeywordSetFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRGeoLocationFromUSSRAppRunIds()->push($this, $jobSiteFromUSSR, $userFromUSSR, $geoLocationFromUSSR, $appRunId);
        }

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($geoLocationFromUSSR->isUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRAppRunIdsLoaded()) {
            $geoLocationFromUSSR->initUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRAppRunIds();
            $geoLocationFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRAppRunIds()->push($this, $jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $appRunId);
        } elseif (!$geoLocationFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRAppRunIds()->contains($this, $jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $appRunId)) {
            $geoLocationFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRAppRunIds()->push($this, $jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $appRunId);
        }

    }

    /**
     * Remove jobSiteFromUSSR, userFromUSSR, userKeywordSetFromUSSR, geoLocationFromUSSR, appRunId of this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUser $userFromUSSR,
     * @param ChildUserKeywordSet $userKeywordSetFromUSSR,
     * @param ChildGeoLocation $geoLocationFromUSSR,
     * @param string $appRunId
     * @return ChildUserSearch The current object (for fluent API support)
     */
    public function removeJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunId(ChildJobSiteRecord $jobSiteFromUSSR, ChildUser $userFromUSSR, ChildUserKeywordSet $userKeywordSetFromUSSR, ChildGeoLocation $geoLocationFromUSSR, $appRunId)
    {
        if ($this->getJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->contains($jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId)) {
            $userSearchSiteRun = new ChildUserSearchSiteRun();
            $userSearchSiteRun->setJobSiteFromUSSR($jobSiteFromUSSR);
            if ($jobSiteFromUSSR->isUserSearchFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $jobSiteFromUSSR->getUserSearchFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->removeObject($this, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
            }

            $userSearchSiteRun->setUserFromUSSR($userFromUSSR);
            if ($userFromUSSR->isUserSearchFromUSSRJobSiteFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $userFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds()->removeObject($this, $jobSiteFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
            }

            $userSearchSiteRun->setUserKeywordSetFromUSSR($userKeywordSetFromUSSR);
            if ($userKeywordSetFromUSSR->isUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRGeoLocationFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $userKeywordSetFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRGeoLocationFromUSSRAppRunIds()->removeObject($this, $jobSiteFromUSSR, $userFromUSSR, $geoLocationFromUSSR, $appRunId);
            }

            $userSearchSiteRun->setGeoLocationFromUSSR($geoLocationFromUSSR);
            if ($geoLocationFromUSSR->isUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $geoLocationFromUSSR->getUserSearchFromUSSRJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRAppRunIds()->removeObject($this, $jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $appRunId);
            }

            $userSearchSiteRun->setAppRunId($appRunId);
            $userSearchSiteRun->setUserSearchFromUSSR($this);
            $this->removeUserSearchSiteRun(clone $userSearchSiteRun);
            $userSearchSiteRun->clear();

            $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds->remove($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds->search($jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId));

            if (null === $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion) {
                $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion = clone $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds;
                $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion->clear();
            }

            $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIdsScheduledForDeletion->push($jobSiteFromUSSR, $userFromUSSR, $userKeywordSetFromUSSR, $geoLocationFromUSSR, $appRunId);
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
        if (null !== $this->aUserKeywordSetFromUS) {
            $this->aUserKeywordSetFromUS->removeUserSearch($this);
        }
        if (null !== $this->aGeoLocationFromUS) {
            $this->aGeoLocationFromUS->removeUserSearch($this);
        }
        if (null !== $this->aUserFromUS) {
            $this->aUserFromUS->removeUserSearch($this);
        }
        $this->user_id = null;
        $this->user_keyword_set_id = null;
        $this->geolocation_id = null;
        $this->user_search_id = null;
        $this->date_created = null;
        $this->date_updated = null;
        $this->user_search_key = null;
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
            if ($this->collUserSearchSiteRuns) {
                foreach ($this->collUserSearchSiteRuns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds) {
                foreach ($this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collUserSearchSiteRuns = null;
        $this->combinationCollJobSiteFromUSSRUserFromUSSRUserKeywordSetFromUSSRGeoLocationFromUSSRAppRunIds = null;
        $this->aUserKeywordSetFromUS = null;
        $this->aGeoLocationFromUS = null;
        $this->aUserFromUS = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'user_search_key' column
     */
    public function __toString()
    {
        return (string) $this->getUserSearchKey();
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     $this|ChildUserSearch The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[UserSearchTableMap::COL_DATE_UPDATED] = true;

        return $this;
    }

    // sluggable behavior

    /**
     * Wrap the setter for slug value
     *
     * @param   string
     * @return  $this|UserSearch
     */
    public function setSlug($v)
    {
        return $this->setUserSearchKey($v);
    }

    /**
     * Wrap the getter for slug value
     *
     * @return  string
     */
    public function getSlug()
    {
        return $this->getUserSearchKey();
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
        return 'user' . $this->cleanupSlugPart($this->getUserId()) . '_ukwd' . $this->cleanupSlugPart($this->getUserKeywordSetId()) . '_geo' . $this->cleanupSlugPart($this->getGeoLocationId()) . '';
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
        $col = 'q.UserSearchKey';
        $compare = $alreadyExists ? $adapter->compareRegex($col, '?') : sprintf('%s = ?', $col);

        $query = \JobScooper\DataAccess\UserSearchQuery::create('q')
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
            ->addDescendingOrderByColumn($adapter->strLength('user_search_key'))
            ->addDescendingOrderByColumn('user_search_key')
        ->findOne();

        // First duplicate slug
        if (null == $object) {
            return $slug2 . '1';
        }

        $slugNum = substr($object->getUserSearchKey(), strlen($slug) + 1);
        if (0 == $slugNum[0]) {
            $slugNum[0] = 1;
        }

        return $slug2 . ($slugNum + 1);
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
