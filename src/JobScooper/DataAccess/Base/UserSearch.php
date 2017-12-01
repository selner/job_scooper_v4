<?php

namespace JobScooper\DataAccess\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\DataAccess\GeoLocation as ChildGeoLocation;
use JobScooper\DataAccess\GeoLocationQuery as ChildGeoLocationQuery;
use JobScooper\DataAccess\User as ChildUser;
use JobScooper\DataAccess\UserQuery as ChildUserQuery;
use JobScooper\DataAccess\UserSearch as ChildUserSearch;
use JobScooper\DataAccess\UserSearchQuery as ChildUserSearchQuery;
use JobScooper\DataAccess\UserSearchRun as ChildUserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\DataAccess\UserSearchVersion as ChildUserSearchVersion;
use JobScooper\DataAccess\UserSearchVersionQuery as ChildUserSearchVersionQuery;
use JobScooper\DataAccess\Map\UserSearchRunTableMap;
use JobScooper\DataAccess\Map\UserSearchTableMap;
use JobScooper\DataAccess\Map\UserSearchVersionTableMap;
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
     * The value for the user_search_id field.
     *
     * @var        int
     */
    protected $user_search_id;

    /**
     * The value for the user_id field.
     *
     * @var        int
     */
    protected $user_id;

    /**
     * The value for the geolocation_id field.
     *
     * @var        int
     */
    protected $geolocation_id;

    /**
     * The value for the user_search_key field.
     *
     * @var        string
     */
    protected $user_search_key;

    /**
     * The value for the keywords field.
     *
     * @var        array
     */
    protected $keywords;

    /**
     * The unserialized $keywords value - i.e. the persisted object.
     * This is necessary to avoid repeated calls to unserialize() at runtime.
     * @var object
     */
    protected $keywords_unserialized;

    /**
     * The value for the keyword_tokens field.
     *
     * @var        array
     */
    protected $keyword_tokens;

    /**
     * The unserialized $keyword_tokens value - i.e. the persisted object.
     * This is necessary to avoid repeated calls to unserialize() at runtime.
     * @var object
     */
    protected $keyword_tokens_unserialized;

    /**
     * The value for the search_key_from_config field.
     *
     * @var        string
     */
    protected $search_key_from_config;

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
     * The value for the date_last_completed field.
     *
     * @var        DateTime
     */
    protected $date_last_completed;

    /**
     * The value for the version field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $version;

    /**
     * @var        ChildUser
     */
    protected $aUser;

    /**
     * @var        ChildGeoLocation
     */
    protected $aGeoLocation;

    /**
     * @var        ObjectCollection|ChildUserSearchRun[] Collection to store aggregation of ChildUserSearchRun objects.
     */
    protected $collUserSearchRuns;
    protected $collUserSearchRunsPartial;

    /**
     * @var        ObjectCollection|ChildUserSearchVersion[] Collection to store aggregation of ChildUserSearchVersion objects.
     */
    protected $collUserSearchVersions;
    protected $collUserSearchVersionsPartial;

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
     * @var ObjectCollection|ChildUserSearchRun[]
     */
    protected $userSearchRunsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserSearchVersion[]
     */
    protected $userSearchVersionsScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->version = 0;
    }

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\UserSearch object.
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
     * Get the [user_search_id] column value.
     *
     * @return int
     */
    public function getUserSearchId()
    {
        return $this->user_search_id;
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
     * Get the [geolocation_id] column value.
     *
     * @return int
     */
    public function getGeoLocationId()
    {
        return $this->geolocation_id;
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
     * Get the [keywords] column value.
     *
     * @return array
     */
    public function getKeywords()
    {
        if (null === $this->keywords_unserialized) {
            $this->keywords_unserialized = array();
        }
        if (!$this->keywords_unserialized && null !== $this->keywords) {
            $keywords_unserialized = substr($this->keywords, 2, -2);
            $this->keywords_unserialized = '' !== $keywords_unserialized ? explode(' | ', $keywords_unserialized) : array();
        }

        return $this->keywords_unserialized;
    }

    /**
     * Test the presence of a value in the [keywords] array column value.
     * @param      mixed $value
     *
     * @return boolean
     */
    public function hasKeyword($value)
    {
        return in_array($value, $this->getKeywords());
    } // hasKeyword()

    /**
     * Get the [keyword_tokens] column value.
     *
     * @return array
     */
    public function getKeywordTokens()
    {
        if (null === $this->keyword_tokens_unserialized) {
            $this->keyword_tokens_unserialized = array();
        }
        if (!$this->keyword_tokens_unserialized && null !== $this->keyword_tokens) {
            $keyword_tokens_unserialized = substr($this->keyword_tokens, 2, -2);
            $this->keyword_tokens_unserialized = '' !== $keyword_tokens_unserialized ? explode(' | ', $keyword_tokens_unserialized) : array();
        }

        return $this->keyword_tokens_unserialized;
    }

    /**
     * Test the presence of a value in the [keyword_tokens] array column value.
     * @param      mixed $value
     *
     * @return boolean
     */
    public function hasKeywordToken($value)
    {
        return in_array($value, $this->getKeywordTokens());
    } // hasKeywordToken()

    /**
     * Get the [search_key_from_config] column value.
     *
     * @return string
     */
    public function getSearchKeyFromConfig()
    {
        return $this->search_key_from_config;
    }

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
     * Get the [optionally formatted] temporal [date_updated] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
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
     * Get the [version] column value.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

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

        if ($this->aUser !== null && $this->aUser->getUserId() !== $v) {
            $this->aUser = null;
        }

        return $this;
    } // setUserId()

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

        if ($this->aGeoLocation !== null && $this->aGeoLocation->getGeoLocationId() !== $v) {
            $this->aGeoLocation = null;
        }

        return $this;
    } // setGeoLocationId()

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
     * Set the value of [keywords] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setKeywords($v)
    {
        if ($this->keywords_unserialized !== $v) {
            $this->keywords_unserialized = $v;
            $this->keywords = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[UserSearchTableMap::COL_KEYWORDS] = true;
        }

        return $this;
    } // setKeywords()

    /**
     * Adds a value to the [keywords] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function addKeyword($value)
    {
        $currentArray = $this->getKeywords();
        $currentArray []= $value;
        $this->setKeywords($currentArray);

        return $this;
    } // addKeyword()

    /**
     * Removes a value from the [keywords] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function removeKeyword($value)
    {
        $targetArray = array();
        foreach ($this->getKeywords() as $element) {
            if ($element != $value) {
                $targetArray []= $element;
            }
        }
        $this->setKeywords($targetArray);

        return $this;
    } // removeKeyword()

    /**
     * Set the value of [keyword_tokens] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setKeywordTokens($v)
    {
        if ($this->keyword_tokens_unserialized !== $v) {
            $this->keyword_tokens_unserialized = $v;
            $this->keyword_tokens = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[UserSearchTableMap::COL_KEYWORD_TOKENS] = true;
        }

        return $this;
    } // setKeywordTokens()

    /**
     * Adds a value to the [keyword_tokens] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function addKeywordToken($value)
    {
        $currentArray = $this->getKeywordTokens();
        $currentArray []= $value;
        $this->setKeywordTokens($currentArray);

        return $this;
    } // addKeywordToken()

    /**
     * Removes a value from the [keyword_tokens] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function removeKeywordToken($value)
    {
        $targetArray = array();
        foreach ($this->getKeywordTokens() as $element) {
            if ($element != $value) {
                $targetArray []= $element;
            }
        }
        $this->setKeywordTokens($targetArray);

        return $this;
    } // removeKeywordToken()

    /**
     * Set the value of [search_key_from_config] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setSearchKeyFromConfig($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->search_key_from_config !== $v) {
            $this->search_key_from_config = $v;
            $this->modifiedColumns[UserSearchTableMap::COL_SEARCH_KEY_FROM_CONFIG] = true;
        }

        return $this;
    } // setSearchKeyFromConfig()

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
     * Sets the value of [date_last_completed] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setLastCompletedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->date_last_completed !== null || $dt !== null) {
            if ($this->date_last_completed === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->date_last_completed->format("Y-m-d H:i:s.u")) {
                $this->date_last_completed = $dt === null ? null : clone $dt;
                $this->modifiedColumns[UserSearchTableMap::COL_DATE_LAST_COMPLETED] = true;
            }
        } // if either are not null

        return $this;
    } // setLastCompletedAt()

    /**
     * Set the value of [version] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function setVersion($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->version !== $v) {
            $this->version = $v;
            $this->modifiedColumns[UserSearchTableMap::COL_VERSION] = true;
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserSearchTableMap::translateFieldName('UserSearchId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserSearchTableMap::translateFieldName('UserId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserSearchTableMap::translateFieldName('GeoLocationId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->geolocation_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserSearchTableMap::translateFieldName('UserSearchKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_key = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : UserSearchTableMap::translateFieldName('Keywords', TableMap::TYPE_PHPNAME, $indexType)];
            $this->keywords = $col;
            $this->keywords_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : UserSearchTableMap::translateFieldName('KeywordTokens', TableMap::TYPE_PHPNAME, $indexType)];
            $this->keyword_tokens = $col;
            $this->keyword_tokens_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : UserSearchTableMap::translateFieldName('SearchKeyFromConfig', TableMap::TYPE_PHPNAME, $indexType)];
            $this->search_key_from_config = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : UserSearchTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_created = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : UserSearchTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_updated = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : UserSearchTableMap::translateFieldName('LastCompletedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->date_last_completed = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : UserSearchTableMap::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)];
            $this->version = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 11; // 11 = UserSearchTableMap::NUM_HYDRATE_COLUMNS.

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
        if ($this->aUser !== null && $this->user_id !== $this->aUser->getUserId()) {
            $this->aUser = null;
        }
        if ($this->aGeoLocation !== null && $this->geolocation_id !== $this->aGeoLocation->getGeoLocationId()) {
            $this->aGeoLocation = null;
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

            $this->aUser = null;
            $this->aGeoLocation = null;
            $this->collUserSearchRuns = null;

            $this->collUserSearchVersions = null;

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
            // versionable behavior
            if ($this->isVersioningNecessary()) {
                $this->setVersion($this->isNew() ? 1 : $this->getLastVersionNumber($con) + 1);
                $createVersion = true; // for postSave hook
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
                // versionable behavior
                if (isset($createVersion)) {
                    $this->addVersion($con);
                }
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

            if ($this->aUser !== null) {
                if ($this->aUser->isModified() || $this->aUser->isNew()) {
                    $affectedRows += $this->aUser->save($con);
                }
                $this->setUser($this->aUser);
            }

            if ($this->aGeoLocation !== null) {
                if ($this->aGeoLocation->isModified() || $this->aGeoLocation->isNew()) {
                    $affectedRows += $this->aGeoLocation->save($con);
                }
                $this->setGeoLocation($this->aGeoLocation);
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

            if ($this->userSearchRunsScheduledForDeletion !== null) {
                if (!$this->userSearchRunsScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\UserSearchRunQuery::create()
                        ->filterByPrimaryKeys($this->userSearchRunsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->userSearchRunsScheduledForDeletion = null;
                }
            }

            if ($this->collUserSearchRuns !== null) {
                foreach ($this->collUserSearchRuns as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->userSearchVersionsScheduledForDeletion !== null) {
                if (!$this->userSearchVersionsScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\UserSearchVersionQuery::create()
                        ->filterByPrimaryKeys($this->userSearchVersionsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->userSearchVersionsScheduledForDeletion = null;
                }
            }

            if ($this->collUserSearchVersions !== null) {
                foreach ($this->collUserSearchVersions as $referrerFK) {
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

        $this->modifiedColumns[UserSearchTableMap::COL_USER_SEARCH_ID] = true;
        if (null !== $this->user_search_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . UserSearchTableMap::COL_USER_SEARCH_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_id';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_id';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_GEOLOCATION_ID)) {
            $modifiedColumns[':p' . $index++]  = 'geolocation_id';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_key';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_KEYWORDS)) {
            $modifiedColumns[':p' . $index++]  = 'keywords';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_KEYWORD_TOKENS)) {
            $modifiedColumns[':p' . $index++]  = 'keyword_tokens';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_SEARCH_KEY_FROM_CONFIG)) {
            $modifiedColumns[':p' . $index++]  = 'search_key_from_config';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_CREATED)) {
            $modifiedColumns[':p' . $index++]  = 'date_created';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_UPDATED)) {
            $modifiedColumns[':p' . $index++]  = 'date_updated';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_LAST_COMPLETED)) {
            $modifiedColumns[':p' . $index++]  = 'date_last_completed';
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_VERSION)) {
            $modifiedColumns[':p' . $index++]  = 'version';
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
                    case 'user_search_id':
                        $stmt->bindValue($identifier, $this->user_search_id, PDO::PARAM_INT);
                        break;
                    case 'user_id':
                        $stmt->bindValue($identifier, $this->user_id, PDO::PARAM_INT);
                        break;
                    case 'geolocation_id':
                        $stmt->bindValue($identifier, $this->geolocation_id, PDO::PARAM_INT);
                        break;
                    case 'user_search_key':
                        $stmt->bindValue($identifier, $this->user_search_key, PDO::PARAM_STR);
                        break;
                    case 'keywords':
                        $stmt->bindValue($identifier, $this->keywords, PDO::PARAM_STR);
                        break;
                    case 'keyword_tokens':
                        $stmt->bindValue($identifier, $this->keyword_tokens, PDO::PARAM_STR);
                        break;
                    case 'search_key_from_config':
                        $stmt->bindValue($identifier, $this->search_key_from_config, PDO::PARAM_STR);
                        break;
                    case 'date_created':
                        $stmt->bindValue($identifier, $this->date_created ? $this->date_created->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_updated':
                        $stmt->bindValue($identifier, $this->date_updated ? $this->date_updated->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'date_last_completed':
                        $stmt->bindValue($identifier, $this->date_last_completed ? $this->date_last_completed->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
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

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setUserSearchId($pk);

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
                return $this->getUserSearchId();
                break;
            case 1:
                return $this->getUserId();
                break;
            case 2:
                return $this->getGeoLocationId();
                break;
            case 3:
                return $this->getUserSearchKey();
                break;
            case 4:
                return $this->getKeywords();
                break;
            case 5:
                return $this->getKeywordTokens();
                break;
            case 6:
                return $this->getSearchKeyFromConfig();
                break;
            case 7:
                return $this->getCreatedAt();
                break;
            case 8:
                return $this->getUpdatedAt();
                break;
            case 9:
                return $this->getLastCompletedAt();
                break;
            case 10:
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

        if (isset($alreadyDumpedObjects['UserSearch'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['UserSearch'][$this->hashCode()] = true;
        $keys = UserSearchTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getUserSearchId(),
            $keys[1] => $this->getUserId(),
            $keys[2] => $this->getGeoLocationId(),
            $keys[3] => $this->getUserSearchKey(),
            $keys[4] => $this->getKeywords(),
            $keys[5] => $this->getKeywordTokens(),
            $keys[6] => $this->getSearchKeyFromConfig(),
            $keys[7] => $this->getCreatedAt(),
            $keys[8] => $this->getUpdatedAt(),
            $keys[9] => $this->getLastCompletedAt(),
            $keys[10] => $this->getVersion(),
        );
        if ($result[$keys[7]] instanceof \DateTimeInterface) {
            $result[$keys[7]] = $result[$keys[7]]->format('c');
        }

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
            if (null !== $this->collUserSearchRuns) {

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

                $result[$key] = $this->collUserSearchRuns->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collUserSearchVersions) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userSearchVersions';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_search_versions';
                        break;
                    default:
                        $key = 'UserSearchVersions';
                }

                $result[$key] = $this->collUserSearchVersions->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
                $this->setUserSearchId($value);
                break;
            case 1:
                $this->setUserId($value);
                break;
            case 2:
                $this->setGeoLocationId($value);
                break;
            case 3:
                $this->setUserSearchKey($value);
                break;
            case 4:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setKeywords($value);
                break;
            case 5:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setKeywordTokens($value);
                break;
            case 6:
                $this->setSearchKeyFromConfig($value);
                break;
            case 7:
                $this->setCreatedAt($value);
                break;
            case 8:
                $this->setUpdatedAt($value);
                break;
            case 9:
                $this->setLastCompletedAt($value);
                break;
            case 10:
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
        $keys = UserSearchTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setUserSearchId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setUserId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setGeoLocationId($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setUserSearchKey($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setKeywords($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setKeywordTokens($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setSearchKeyFromConfig($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setCreatedAt($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setUpdatedAt($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setLastCompletedAt($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setVersion($arr[$keys[10]]);
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

        if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_ID)) {
            $criteria->add(UserSearchTableMap::COL_USER_SEARCH_ID, $this->user_search_id);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_ID)) {
            $criteria->add(UserSearchTableMap::COL_USER_ID, $this->user_id);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_GEOLOCATION_ID)) {
            $criteria->add(UserSearchTableMap::COL_GEOLOCATION_ID, $this->geolocation_id);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_USER_SEARCH_KEY)) {
            $criteria->add(UserSearchTableMap::COL_USER_SEARCH_KEY, $this->user_search_key);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_KEYWORDS)) {
            $criteria->add(UserSearchTableMap::COL_KEYWORDS, $this->keywords);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_KEYWORD_TOKENS)) {
            $criteria->add(UserSearchTableMap::COL_KEYWORD_TOKENS, $this->keyword_tokens);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_SEARCH_KEY_FROM_CONFIG)) {
            $criteria->add(UserSearchTableMap::COL_SEARCH_KEY_FROM_CONFIG, $this->search_key_from_config);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_CREATED)) {
            $criteria->add(UserSearchTableMap::COL_DATE_CREATED, $this->date_created);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_UPDATED)) {
            $criteria->add(UserSearchTableMap::COL_DATE_UPDATED, $this->date_updated);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_DATE_LAST_COMPLETED)) {
            $criteria->add(UserSearchTableMap::COL_DATE_LAST_COMPLETED, $this->date_last_completed);
        }
        if ($this->isColumnModified(UserSearchTableMap::COL_VERSION)) {
            $criteria->add(UserSearchTableMap::COL_VERSION, $this->version);
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
        $criteria->add(UserSearchTableMap::COL_USER_SEARCH_ID, $this->user_search_id);

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
        $validPk = null !== $this->getUserSearchId();

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
        return $this->getUserSearchId();
    }

    /**
     * Generic method to set the primary key (user_search_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setUserSearchId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getUserSearchId();
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
        $copyObj->setGeoLocationId($this->getGeoLocationId());
        $copyObj->setUserSearchKey($this->getUserSearchKey());
        $copyObj->setKeywords($this->getKeywords());
        $copyObj->setKeywordTokens($this->getKeywordTokens());
        $copyObj->setSearchKeyFromConfig($this->getSearchKeyFromConfig());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());
        $copyObj->setLastCompletedAt($this->getLastCompletedAt());
        $copyObj->setVersion($this->getVersion());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getUserSearchRuns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserSearchRun($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getUserSearchVersions() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserSearchVersion($relObj->copy($deepCopy));
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
     * Declares an association between this object and a ChildUser object.
     *
     * @param  ChildUser $v
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUser(ChildUser $v = null)
    {
        if ($v === null) {
            $this->setUserId(NULL);
        } else {
            $this->setUserId($v->getUserId());
        }

        $this->aUser = $v;

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
    public function getUser(ConnectionInterface $con = null)
    {
        if ($this->aUser === null && ($this->user_id != 0)) {
            $this->aUser = ChildUserQuery::create()->findPk($this->user_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUser->addUserSearches($this);
             */
        }

        return $this->aUser;
    }

    /**
     * Declares an association between this object and a ChildGeoLocation object.
     *
     * @param  ChildGeoLocation $v
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
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
    public function getGeoLocation(ConnectionInterface $con = null)
    {
        if ($this->aGeoLocation === null && ($this->geolocation_id != 0)) {
            $this->aGeoLocation = ChildGeoLocationQuery::create()->findPk($this->geolocation_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aGeoLocation->addUserSearches($this);
             */
        }

        return $this->aGeoLocation;
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
        if ('UserSearchRun' == $relationName) {
            $this->initUserSearchRuns();
            return;
        }
        if ('UserSearchVersion' == $relationName) {
            $this->initUserSearchVersions();
            return;
        }
    }

    /**
     * Clears out the collUserSearchRuns collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserSearchRuns()
     */
    public function clearUserSearchRuns()
    {
        $this->collUserSearchRuns = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collUserSearchRuns collection loaded partially.
     */
    public function resetPartialUserSearchRuns($v = true)
    {
        $this->collUserSearchRunsPartial = $v;
    }

    /**
     * Initializes the collUserSearchRuns collection.
     *
     * By default this just sets the collUserSearchRuns collection to an empty array (like clearcollUserSearchRuns());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUserSearchRuns($overrideExisting = true)
    {
        if (null !== $this->collUserSearchRuns && !$overrideExisting) {
            return;
        }

        $collectionClassName = UserSearchRunTableMap::getTableMap()->getCollectionClassName();

        $this->collUserSearchRuns = new $collectionClassName;
        $this->collUserSearchRuns->setModel('\JobScooper\DataAccess\UserSearchRun');
    }

    /**
     * Gets an array of ChildUserSearchRun objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUserSearch is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildUserSearchRun[] List of ChildUserSearchRun objects
     * @throws PropelException
     */
    public function getUserSearchRuns(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchRunsPartial && !$this->isNew();
        if (null === $this->collUserSearchRuns || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUserSearchRuns) {
                // return empty collection
                $this->initUserSearchRuns();
            } else {
                $collUserSearchRuns = ChildUserSearchRunQuery::create(null, $criteria)
                    ->filterByUserSearch($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collUserSearchRunsPartial && count($collUserSearchRuns)) {
                        $this->initUserSearchRuns(false);

                        foreach ($collUserSearchRuns as $obj) {
                            if (false == $this->collUserSearchRuns->contains($obj)) {
                                $this->collUserSearchRuns->append($obj);
                            }
                        }

                        $this->collUserSearchRunsPartial = true;
                    }

                    return $collUserSearchRuns;
                }

                if ($partial && $this->collUserSearchRuns) {
                    foreach ($this->collUserSearchRuns as $obj) {
                        if ($obj->isNew()) {
                            $collUserSearchRuns[] = $obj;
                        }
                    }
                }

                $this->collUserSearchRuns = $collUserSearchRuns;
                $this->collUserSearchRunsPartial = false;
            }
        }

        return $this->collUserSearchRuns;
    }

    /**
     * Sets a collection of ChildUserSearchRun objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $userSearchRuns A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildUserSearch The current object (for fluent API support)
     */
    public function setUserSearchRuns(Collection $userSearchRuns, ConnectionInterface $con = null)
    {
        /** @var ChildUserSearchRun[] $userSearchRunsToDelete */
        $userSearchRunsToDelete = $this->getUserSearchRuns(new Criteria(), $con)->diff($userSearchRuns);


        $this->userSearchRunsScheduledForDeletion = $userSearchRunsToDelete;

        foreach ($userSearchRunsToDelete as $userSearchRunRemoved) {
            $userSearchRunRemoved->setUserSearch(null);
        }

        $this->collUserSearchRuns = null;
        foreach ($userSearchRuns as $userSearchRun) {
            $this->addUserSearchRun($userSearchRun);
        }

        $this->collUserSearchRuns = $userSearchRuns;
        $this->collUserSearchRunsPartial = false;

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
    public function countUserSearchRuns(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchRunsPartial && !$this->isNew();
        if (null === $this->collUserSearchRuns || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserSearchRuns) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getUserSearchRuns());
            }

            $query = ChildUserSearchRunQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByUserSearch($this)
                ->count($con);
        }

        return count($this->collUserSearchRuns);
    }

    /**
     * Method called to associate a ChildUserSearchRun object to this object
     * through the ChildUserSearchRun foreign key attribute.
     *
     * @param  ChildUserSearchRun $l ChildUserSearchRun
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function addUserSearchRun(ChildUserSearchRun $l)
    {
        if ($this->collUserSearchRuns === null) {
            $this->initUserSearchRuns();
            $this->collUserSearchRunsPartial = true;
        }

        if (!$this->collUserSearchRuns->contains($l)) {
            $this->doAddUserSearchRun($l);

            if ($this->userSearchRunsScheduledForDeletion and $this->userSearchRunsScheduledForDeletion->contains($l)) {
                $this->userSearchRunsScheduledForDeletion->remove($this->userSearchRunsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildUserSearchRun $userSearchRun The ChildUserSearchRun object to add.
     */
    protected function doAddUserSearchRun(ChildUserSearchRun $userSearchRun)
    {
        $this->collUserSearchRuns[]= $userSearchRun;
        $userSearchRun->setUserSearch($this);
    }

    /**
     * @param  ChildUserSearchRun $userSearchRun The ChildUserSearchRun object to remove.
     * @return $this|ChildUserSearch The current object (for fluent API support)
     */
    public function removeUserSearchRun(ChildUserSearchRun $userSearchRun)
    {
        if ($this->getUserSearchRuns()->contains($userSearchRun)) {
            $pos = $this->collUserSearchRuns->search($userSearchRun);
            $this->collUserSearchRuns->remove($pos);
            if (null === $this->userSearchRunsScheduledForDeletion) {
                $this->userSearchRunsScheduledForDeletion = clone $this->collUserSearchRuns;
                $this->userSearchRunsScheduledForDeletion->clear();
            }
            $this->userSearchRunsScheduledForDeletion[]= clone $userSearchRun;
            $userSearchRun->setUserSearch(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this UserSearch is new, it will return
     * an empty collection; or if this UserSearch has previously
     * been saved, it will retrieve related UserSearchRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserSearch.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearchRun[] List of ChildUserSearchRun objects
     */
    public function getUserSearchRunsJoinJobSiteRecordRelatedByJobSiteKey(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchRunQuery::create(null, $criteria);
        $query->joinWith('JobSiteRecordRelatedByJobSiteKey', $joinBehavior);

        return $this->getUserSearchRuns($query, $con);
    }

    /**
     * Clears out the collUserSearchVersions collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserSearchVersions()
     */
    public function clearUserSearchVersions()
    {
        $this->collUserSearchVersions = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collUserSearchVersions collection loaded partially.
     */
    public function resetPartialUserSearchVersions($v = true)
    {
        $this->collUserSearchVersionsPartial = $v;
    }

    /**
     * Initializes the collUserSearchVersions collection.
     *
     * By default this just sets the collUserSearchVersions collection to an empty array (like clearcollUserSearchVersions());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUserSearchVersions($overrideExisting = true)
    {
        if (null !== $this->collUserSearchVersions && !$overrideExisting) {
            return;
        }

        $collectionClassName = UserSearchVersionTableMap::getTableMap()->getCollectionClassName();

        $this->collUserSearchVersions = new $collectionClassName;
        $this->collUserSearchVersions->setModel('\JobScooper\DataAccess\UserSearchVersion');
    }

    /**
     * Gets an array of ChildUserSearchVersion objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUserSearch is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildUserSearchVersion[] List of ChildUserSearchVersion objects
     * @throws PropelException
     */
    public function getUserSearchVersions(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchVersionsPartial && !$this->isNew();
        if (null === $this->collUserSearchVersions || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUserSearchVersions) {
                // return empty collection
                $this->initUserSearchVersions();
            } else {
                $collUserSearchVersions = ChildUserSearchVersionQuery::create(null, $criteria)
                    ->filterByUserSearch($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collUserSearchVersionsPartial && count($collUserSearchVersions)) {
                        $this->initUserSearchVersions(false);

                        foreach ($collUserSearchVersions as $obj) {
                            if (false == $this->collUserSearchVersions->contains($obj)) {
                                $this->collUserSearchVersions->append($obj);
                            }
                        }

                        $this->collUserSearchVersionsPartial = true;
                    }

                    return $collUserSearchVersions;
                }

                if ($partial && $this->collUserSearchVersions) {
                    foreach ($this->collUserSearchVersions as $obj) {
                        if ($obj->isNew()) {
                            $collUserSearchVersions[] = $obj;
                        }
                    }
                }

                $this->collUserSearchVersions = $collUserSearchVersions;
                $this->collUserSearchVersionsPartial = false;
            }
        }

        return $this->collUserSearchVersions;
    }

    /**
     * Sets a collection of ChildUserSearchVersion objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $userSearchVersions A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildUserSearch The current object (for fluent API support)
     */
    public function setUserSearchVersions(Collection $userSearchVersions, ConnectionInterface $con = null)
    {
        /** @var ChildUserSearchVersion[] $userSearchVersionsToDelete */
        $userSearchVersionsToDelete = $this->getUserSearchVersions(new Criteria(), $con)->diff($userSearchVersions);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->userSearchVersionsScheduledForDeletion = clone $userSearchVersionsToDelete;

        foreach ($userSearchVersionsToDelete as $userSearchVersionRemoved) {
            $userSearchVersionRemoved->setUserSearch(null);
        }

        $this->collUserSearchVersions = null;
        foreach ($userSearchVersions as $userSearchVersion) {
            $this->addUserSearchVersion($userSearchVersion);
        }

        $this->collUserSearchVersions = $userSearchVersions;
        $this->collUserSearchVersionsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related UserSearchVersion objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related UserSearchVersion objects.
     * @throws PropelException
     */
    public function countUserSearchVersions(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserSearchVersionsPartial && !$this->isNew();
        if (null === $this->collUserSearchVersions || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserSearchVersions) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getUserSearchVersions());
            }

            $query = ChildUserSearchVersionQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByUserSearch($this)
                ->count($con);
        }

        return count($this->collUserSearchVersions);
    }

    /**
     * Method called to associate a ChildUserSearchVersion object to this object
     * through the ChildUserSearchVersion foreign key attribute.
     *
     * @param  ChildUserSearchVersion $l ChildUserSearchVersion
     * @return $this|\JobScooper\DataAccess\UserSearch The current object (for fluent API support)
     */
    public function addUserSearchVersion(ChildUserSearchVersion $l)
    {
        if ($this->collUserSearchVersions === null) {
            $this->initUserSearchVersions();
            $this->collUserSearchVersionsPartial = true;
        }

        if (!$this->collUserSearchVersions->contains($l)) {
            $this->doAddUserSearchVersion($l);

            if ($this->userSearchVersionsScheduledForDeletion and $this->userSearchVersionsScheduledForDeletion->contains($l)) {
                $this->userSearchVersionsScheduledForDeletion->remove($this->userSearchVersionsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildUserSearchVersion $userSearchVersion The ChildUserSearchVersion object to add.
     */
    protected function doAddUserSearchVersion(ChildUserSearchVersion $userSearchVersion)
    {
        $this->collUserSearchVersions[]= $userSearchVersion;
        $userSearchVersion->setUserSearch($this);
    }

    /**
     * @param  ChildUserSearchVersion $userSearchVersion The ChildUserSearchVersion object to remove.
     * @return $this|ChildUserSearch The current object (for fluent API support)
     */
    public function removeUserSearchVersion(ChildUserSearchVersion $userSearchVersion)
    {
        if ($this->getUserSearchVersions()->contains($userSearchVersion)) {
            $pos = $this->collUserSearchVersions->search($userSearchVersion);
            $this->collUserSearchVersions->remove($pos);
            if (null === $this->userSearchVersionsScheduledForDeletion) {
                $this->userSearchVersionsScheduledForDeletion = clone $this->collUserSearchVersions;
                $this->userSearchVersionsScheduledForDeletion->clear();
            }
            $this->userSearchVersionsScheduledForDeletion[]= clone $userSearchVersion;
            $userSearchVersion->setUserSearch(null);
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
        if (null !== $this->aUser) {
            $this->aUser->removeUserSearch($this);
        }
        if (null !== $this->aGeoLocation) {
            $this->aGeoLocation->removeUserSearch($this);
        }
        $this->user_search_id = null;
        $this->user_id = null;
        $this->geolocation_id = null;
        $this->user_search_key = null;
        $this->keywords = null;
        $this->keywords_unserialized = null;
        $this->keyword_tokens = null;
        $this->keyword_tokens_unserialized = null;
        $this->search_key_from_config = null;
        $this->date_created = null;
        $this->date_updated = null;
        $this->date_last_completed = null;
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
            if ($this->collUserSearchRuns) {
                foreach ($this->collUserSearchRuns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collUserSearchVersions) {
                foreach ($this->collUserSearchVersions as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collUserSearchRuns = null;
        $this->collUserSearchVersions = null;
        $this->aUser = null;
        $this->aGeoLocation = null;
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
        return '' . $this->cleanupSlugPart($this->getUserId()) . '_' . $this->cleanupSlugPart($this->getSearchKeyFromConfig()) . '_' . $this->cleanupSlugPart($this->getGeoLocationId()) . '';
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
        if (strlen($slug) > (128 - $incrementReservedSpace)) {
            $slug = substr($slug, 0, 128 - $incrementReservedSpace);
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
    protected function makeSlugUnique($slug, $separator = '_', $alreadyExists = false)
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

    // us_date_last_completed behavior

    /**
     * Computes the value of the aggregate column date_last_completed *
     * @param ConnectionInterface $con A connection object
     *
     * @return mixed The scalar result from the aggregate query
     */
    public function computeLastCompletedAt(ConnectionInterface $con)
    {
        $stmt = $con->prepare('SELECT MAX(date_ended) FROM user_search_run WHERE run_result_code = 4 AND user_search_run.USER_SEARCH_ID = :p1');
        $stmt->bindValue(':p1', $this->getUserSearchId());
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

    // versionable behavior

    /**
     * Enforce a new Version of this object upon next save.
     *
     * @return $this|\JobScooper\DataAccess\UserSearch
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

        if (ChildUserSearchQuery::isVersioningEnabled() && ($this->isNew() || $this->isModified()) || $this->isDeleted()) {
            return true;
        }

        return false;
    }

    /**
     * Creates a version of the current object and saves it.
     *
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  ChildUserSearchVersion A version object
     */
    public function addVersion(ConnectionInterface $con = null)
    {
        $this->enforceVersion = false;

        $version = new ChildUserSearchVersion();
        $version->setUserSearchId($this->getUserSearchId());
        $version->setUserId($this->getUserId());
        $version->setGeoLocationId($this->getGeoLocationId());
        $version->setUserSearchKey($this->getUserSearchKey());
        $version->setKeywords($this->getKeywords());
        $version->setKeywordTokens($this->getKeywordTokens());
        $version->setSearchKeyFromConfig($this->getSearchKeyFromConfig());
        $version->setCreatedAt($this->getCreatedAt());
        $version->setUpdatedAt($this->getUpdatedAt());
        $version->setLastCompletedAt($this->getLastCompletedAt());
        $version->setVersion($this->getVersion());
        $version->setUserSearch($this);
        $version->save($con);

        return $version;
    }

    /**
     * Sets the properties of the current object to the value they had at a specific version
     *
     * @param   integer $versionNumber The version number to read
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  $this|ChildUserSearch The current object (for fluent API support)
     */
    public function toVersion($versionNumber, ConnectionInterface $con = null)
    {
        $version = $this->getOneVersion($versionNumber, $con);
        if (!$version) {
            throw new PropelException(sprintf('No ChildUserSearch object found with version %d', $version));
        }
        $this->populateFromVersion($version, $con);

        return $this;
    }

    /**
     * Sets the properties of the current object to the value they had at a specific version
     *
     * @param ChildUserSearchVersion $version The version object to use
     * @param ConnectionInterface   $con the connection to use
     * @param array                 $loadedObjects objects that been loaded in a chain of populateFromVersion calls on referrer or fk objects.
     *
     * @return $this|ChildUserSearch The current object (for fluent API support)
     */
    public function populateFromVersion($version, $con = null, &$loadedObjects = array())
    {
        $loadedObjects['ChildUserSearch'][$version->getUserSearchId()][$version->getVersion()] = $this;
        $this->setUserSearchId($version->getUserSearchId());
        $this->setUserId($version->getUserId());
        $this->setGeoLocationId($version->getGeoLocationId());
        $this->setUserSearchKey($version->getUserSearchKey());
        $this->setKeywords($version->getKeywords());
        $this->setKeywordTokens($version->getKeywordTokens());
        $this->setSearchKeyFromConfig($version->getSearchKeyFromConfig());
        $this->setCreatedAt($version->getCreatedAt());
        $this->setUpdatedAt($version->getUpdatedAt());
        $this->setLastCompletedAt($version->getLastCompletedAt());
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
        $v = ChildUserSearchVersionQuery::create()
            ->filterByUserSearch($this)
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
     * @return  ChildUserSearchVersion A version object
     */
    public function getOneVersion($versionNumber, ConnectionInterface $con = null)
    {
        return ChildUserSearchVersionQuery::create()
            ->filterByUserSearch($this)
            ->filterByVersion($versionNumber)
            ->findOne($con);
    }

    /**
     * Gets all the versions of this object, in incremental order
     *
     * @param   ConnectionInterface $con The ConnectionInterface connection to use.
     *
     * @return  ObjectCollection|ChildUserSearchVersion[] A list of ChildUserSearchVersion objects
     */
    public function getAllVersions(ConnectionInterface $con = null)
    {
        $criteria = new Criteria();
        $criteria->addAscendingOrderByColumn(UserSearchVersionTableMap::COL_VERSION);

        return $this->getUserSearchVersions($criteria, $con);
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
     * @return PropelCollection|\JobScooper\DataAccess\UserSearchVersion[] List of \JobScooper\DataAccess\UserSearchVersion objects
     */
    public function getLastVersions($number = 10, $criteria = null, ConnectionInterface $con = null)
    {
        $criteria = ChildUserSearchVersionQuery::create(null, $criteria);
        $criteria->addDescendingOrderByColumn(UserSearchVersionTableMap::COL_VERSION);
        $criteria->limit($number);

        return $this->getUserSearchVersions($criteria, $con);
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
