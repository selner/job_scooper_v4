<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\GeoLocation as ChildGeoLocation;
use JobScooper\DataAccess\JobSiteRecord as ChildJobSiteRecord;
use JobScooper\DataAccess\User as ChildUser;
use JobScooper\DataAccess\UserKeywordSet as ChildUserKeywordSet;
use JobScooper\DataAccess\UserKeywordSetQuery as ChildUserKeywordSetQuery;
use JobScooper\DataAccess\UserQuery as ChildUserQuery;
use JobScooper\DataAccess\UserSearch as ChildUserSearch;
use JobScooper\DataAccess\UserSearchQuery as ChildUserSearchQuery;
use JobScooper\DataAccess\UserSearchSiteRun as ChildUserSearchSiteRun;
use JobScooper\DataAccess\UserSearchSiteRunQuery as ChildUserSearchSiteRunQuery;
use JobScooper\DataAccess\Map\UserKeywordSetTableMap;
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
 * Base class that represents a row from the 'user_keyword_set' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.DataAccess.Base
 */
abstract class UserKeywordSet implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\DataAccess\\Map\\UserKeywordSetTableMap';


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
     * The value for the search_key_from_config field.
     *
     * @var        string
     */
    protected $search_key_from_config;

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
     * The value for the user_keyword_set_key field.
     *
     * @var        string
     */
    protected $user_keyword_set_key;

    /**
     * @var        ChildUser
     */
    protected $aUserFromUKS;

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
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildGeoLocation, ChildUser combination combinations.
     */
    protected $combinationCollGeoLocationFromUSUserFromuses;

    /**
     * @var bool
     */
    protected $combinationCollGeoLocationFromUSUserFromusesPartial;

    /**
     * @var        ObjectCollection|ChildGeoLocation[] Cross Collection to store aggregation of ChildGeoLocation objects.
     */
    protected $collGeoLocationFromuses;

    /**
     * @var bool
     */
    protected $collGeoLocationFromusesPartial;

    /**
     * @var        ObjectCollection|ChildUser[] Cross Collection to store aggregation of ChildUser objects.
     */
    protected $collUserFromuses;

    /**
     * @var bool
     */
    protected $collUserFromusesPartial;

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
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildGeoLocation, ChildUser combination combinations.
     */
    protected $combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion = null;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildUserSearch, ChildJobSiteRecord combination combinations.
     */
    protected $combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion = null;

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
     * Initializes internal state of JobScooper\DataAccess\Base\UserKeywordSet object.
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
     * Compares this with another <code>UserKeywordSet</code> instance.  If
     * <code>obj</code> is an instance of <code>UserKeywordSet</code>, delegates to
     * <code>equals(UserKeywordSet)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|UserKeywordSet The current object, for fluid interface
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
     * Get the [search_key_from_config] column value.
     *
     * @return string
     */
    public function getSearchKeyFromConfig()
    {
        return $this->search_key_from_config;
    }

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
     * Get the [user_keyword_set_key] column value.
     *
     * @return string
     */
    public function getUserKeywordSetKey()
    {
        return $this->user_keyword_set_key;
    }

    /**
     * Set the value of [user_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
     */
    public function setUserId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_id !== $v) {
            $this->user_id = $v;
            $this->modifiedColumns[UserKeywordSetTableMap::COL_USER_ID] = true;
        }

        if ($this->aUserFromUKS !== null && $this->aUserFromUKS->getUserId() !== $v) {
            $this->aUserFromUKS = null;
        }

        return $this;
    } // setUserId()

    /**
     * Set the value of [user_keyword_set_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
     */
    public function setUserKeywordSetId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_keyword_set_id !== $v) {
            $this->user_keyword_set_id = $v;
            $this->modifiedColumns[UserKeywordSetTableMap::COL_USER_KEYWORD_SET_ID] = true;
        }

        return $this;
    } // setUserKeywordSetId()

    /**
     * Set the value of [keywords] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
     */
    public function setKeywords($v)
    {
        if ($this->keywords_unserialized !== $v) {
            $this->keywords_unserialized = $v;
            $this->keywords = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[UserKeywordSetTableMap::COL_KEYWORDS] = true;
        }

        return $this;
    } // setKeywords()

    /**
     * Adds a value to the [keywords] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
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
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
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
     * Set the value of [search_key_from_config] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
     */
    public function setSearchKeyFromConfig($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->search_key_from_config !== $v) {
            $this->search_key_from_config = $v;
            $this->modifiedColumns[UserKeywordSetTableMap::COL_SEARCH_KEY_FROM_CONFIG] = true;
        }

        return $this;
    } // setSearchKeyFromConfig()

    /**
     * Set the value of [keyword_tokens] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
     */
    public function setKeywordTokens($v)
    {
        if ($this->keyword_tokens_unserialized !== $v) {
            $this->keyword_tokens_unserialized = $v;
            $this->keyword_tokens = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[UserKeywordSetTableMap::COL_KEYWORD_TOKENS] = true;
        }

        return $this;
    } // setKeywordTokens()

    /**
     * Adds a value to the [keyword_tokens] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
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
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
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
     * Set the value of [user_keyword_set_key] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
     */
    public function setUserKeywordSetKey($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->user_keyword_set_key !== $v) {
            $this->user_keyword_set_key = $v;
            $this->modifiedColumns[UserKeywordSetTableMap::COL_USER_KEYWORD_SET_KEY] = true;
        }

        return $this;
    } // setUserKeywordSetKey()

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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserKeywordSetTableMap::translateFieldName('UserId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserKeywordSetTableMap::translateFieldName('UserKeywordSetId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_keyword_set_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserKeywordSetTableMap::translateFieldName('Keywords', TableMap::TYPE_PHPNAME, $indexType)];
            $this->keywords = $col;
            $this->keywords_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserKeywordSetTableMap::translateFieldName('SearchKeyFromConfig', TableMap::TYPE_PHPNAME, $indexType)];
            $this->search_key_from_config = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : UserKeywordSetTableMap::translateFieldName('KeywordTokens', TableMap::TYPE_PHPNAME, $indexType)];
            $this->keyword_tokens = $col;
            $this->keyword_tokens_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : UserKeywordSetTableMap::translateFieldName('UserKeywordSetKey', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_keyword_set_key = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 6; // 6 = UserKeywordSetTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\DataAccess\\UserKeywordSet'), 0, $e);
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
        if ($this->aUserFromUKS !== null && $this->user_id !== $this->aUserFromUKS->getUserId()) {
            $this->aUserFromUKS = null;
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
            $con = Propel::getServiceContainer()->getReadConnection(UserKeywordSetTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildUserKeywordSetQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aUserFromUKS = null;
            $this->collUserSearches = null;

            $this->collUserSearchSiteRuns = null;

            $this->collGeoLocationFromUSUserFromuses = null;
            $this->collUserSearchFromUSSRJobSiteFromUSSRAppRunIds = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see UserKeywordSet::setDeleted()
     * @see UserKeywordSet::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserKeywordSetTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildUserKeywordSetQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserKeywordSetTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con, $skipReload) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            // sluggable behavior

            if ($this->isColumnModified(UserKeywordSetTableMap::COL_USER_KEYWORD_SET_KEY) && $this->getUserKeywordSetKey()) {
                $this->setUserKeywordSetKey($this->makeSlugUnique($this->getUserKeywordSetKey()));
            } else {
                $this->setUserKeywordSetKey($this->createSlug());
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
                UserKeywordSetTableMap::addInstanceToPool($this);
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

            if ($this->aUserFromUKS !== null) {
                if ($this->aUserFromUKS->isModified() || $this->aUserFromUKS->isNew()) {
                    $affectedRows += $this->aUserFromUKS->save($con);
                }
                $this->setUserFromUKS($this->aUserFromUKS);
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

            if ($this->combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion !== null) {
                if (!$this->combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion as $combination) {
                        $entryPk = [];

                        $entryPk[1] = $this->getUserKeywordSetId();
                        $entryPk[0] = $this->getUserId();
                        $entryPk[2] = $combination[0]->getGeoLocationId();
                        $entryPk[0] = $combination[1]->getUserId();

                        $pks[] = $entryPk;
                    }

                    \JobScooper\DataAccess\UserSearchQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion = null;
                }

            }

            if (null !== $this->combinationCollGeoLocationFromUSUserFromuses) {
                foreach ($this->combinationCollGeoLocationFromUSUserFromuses as $combination) {

                    //$combination[0] = GeoLocation (user_search_fk_38c4c7)
                    if (!$combination[0]->isDeleted() && ($combination[0]->isNew() || $combination[0]->isModified())) {
                        $combination[0]->save($con);
                    }

                    //$combination[1] = User (user_search_fk_38da1e)
                    if (!$combination[1]->isDeleted() && ($combination[1]->isNew() || $combination[1]->isModified())) {
                        $combination[1]->save($con);
                    }

                }
            }


            if ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion !== null) {
                if (!$this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIdsScheduledForDeletion as $combination) {
                        $entryPk = [];

                        $entryPk[0] = $this->getUserId();
                        $entryPk[1] = $this->getUserKeywordSetId();
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

        $this->modifiedColumns[UserKeywordSetTableMap::COL_USER_KEYWORD_SET_ID] = true;

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_USER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_id';
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_USER_KEYWORD_SET_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_keyword_set_id';
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_KEYWORDS)) {
            $modifiedColumns[':p' . $index++]  = 'keywords';
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_SEARCH_KEY_FROM_CONFIG)) {
            $modifiedColumns[':p' . $index++]  = 'search_key_from_config';
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_KEYWORD_TOKENS)) {
            $modifiedColumns[':p' . $index++]  = 'keyword_tokens';
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_USER_KEYWORD_SET_KEY)) {
            $modifiedColumns[':p' . $index++]  = 'user_keyword_set_key';
        }

        $sql = sprintf(
            'INSERT INTO user_keyword_set (%s) VALUES (%s)',
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
                    case 'keywords':
                        $stmt->bindValue($identifier, $this->keywords, PDO::PARAM_STR);
                        break;
                    case 'search_key_from_config':
                        $stmt->bindValue($identifier, $this->search_key_from_config, PDO::PARAM_STR);
                        break;
                    case 'keyword_tokens':
                        $stmt->bindValue($identifier, $this->keyword_tokens, PDO::PARAM_STR);
                        break;
                    case 'user_keyword_set_key':
                        $stmt->bindValue($identifier, $this->user_keyword_set_key, PDO::PARAM_STR);
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
            $this->setUserId($pk);
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
        $pos = UserKeywordSetTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getKeywords();
                break;
            case 3:
                return $this->getSearchKeyFromConfig();
                break;
            case 4:
                return $this->getKeywordTokens();
                break;
            case 5:
                return $this->getUserKeywordSetKey();
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

        if (isset($alreadyDumpedObjects['UserKeywordSet'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['UserKeywordSet'][$this->hashCode()] = true;
        $keys = UserKeywordSetTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getUserId(),
            $keys[1] => $this->getUserKeywordSetId(),
            $keys[2] => $this->getKeywords(),
            $keys[3] => $this->getSearchKeyFromConfig(),
            $keys[4] => $this->getKeywordTokens(),
            $keys[5] => $this->getUserKeywordSetKey(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aUserFromUKS) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'user';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user';
                        break;
                    default:
                        $key = 'UserFromUKS';
                }

                $result[$key] = $this->aUserFromUKS->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
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
     * @return $this|\JobScooper\DataAccess\UserKeywordSet
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserKeywordSetTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\DataAccess\UserKeywordSet
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
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setKeywords($value);
                break;
            case 3:
                $this->setSearchKeyFromConfig($value);
                break;
            case 4:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setKeywordTokens($value);
                break;
            case 5:
                $this->setUserKeywordSetKey($value);
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
        $keys = UserKeywordSetTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setUserId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setUserKeywordSetId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setKeywords($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setSearchKeyFromConfig($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setKeywordTokens($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setUserKeywordSetKey($arr[$keys[5]]);
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
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object, for fluid interface
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
        $criteria = new Criteria(UserKeywordSetTableMap::DATABASE_NAME);

        if ($this->isColumnModified(UserKeywordSetTableMap::COL_USER_ID)) {
            $criteria->add(UserKeywordSetTableMap::COL_USER_ID, $this->user_id);
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_USER_KEYWORD_SET_ID)) {
            $criteria->add(UserKeywordSetTableMap::COL_USER_KEYWORD_SET_ID, $this->user_keyword_set_id);
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_KEYWORDS)) {
            $criteria->add(UserKeywordSetTableMap::COL_KEYWORDS, $this->keywords);
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_SEARCH_KEY_FROM_CONFIG)) {
            $criteria->add(UserKeywordSetTableMap::COL_SEARCH_KEY_FROM_CONFIG, $this->search_key_from_config);
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_KEYWORD_TOKENS)) {
            $criteria->add(UserKeywordSetTableMap::COL_KEYWORD_TOKENS, $this->keyword_tokens);
        }
        if ($this->isColumnModified(UserKeywordSetTableMap::COL_USER_KEYWORD_SET_KEY)) {
            $criteria->add(UserKeywordSetTableMap::COL_USER_KEYWORD_SET_KEY, $this->user_keyword_set_key);
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
        $criteria = ChildUserKeywordSetQuery::create();
        $criteria->add(UserKeywordSetTableMap::COL_USER_ID, $this->user_id);
        $criteria->add(UserKeywordSetTableMap::COL_USER_KEYWORD_SET_ID, $this->user_keyword_set_id);

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
            null !== $this->getUserKeywordSetId();

        $validPrimaryKeyFKs = 1;
        $primaryKeyFKs = [];

        //relation user_keyword_set_fk_38da1e to table user
        if ($this->aUserFromUKS && $hash = spl_object_hash($this->aUserFromUKS)) {
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
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return (null === $this->getUserId()) && (null === $this->getUserKeywordSetId());
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\DataAccess\UserKeywordSet (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setUserId($this->getUserId());
        $copyObj->setKeywords($this->getKeywords());
        $copyObj->setSearchKeyFromConfig($this->getSearchKeyFromConfig());
        $copyObj->setKeywordTokens($this->getKeywordTokens());
        $copyObj->setUserKeywordSetKey($this->getUserKeywordSetKey());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

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
            $copyObj->setUserKeywordSetId(NULL); // this is a auto-increment column, so set to default value
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
     * @return \JobScooper\DataAccess\UserKeywordSet Clone of current object.
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
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUserFromUKS(ChildUser $v = null)
    {
        if ($v === null) {
            $this->setUserId(NULL);
        } else {
            $this->setUserId($v->getUserId());
        }

        $this->aUserFromUKS = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUser object, it will not be re-added.
        if ($v !== null) {
            $v->addUserKeywordSet($this);
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
    public function getUserFromUKS(ConnectionInterface $con = null)
    {
        if ($this->aUserFromUKS === null && ($this->user_id != 0)) {
            $this->aUserFromUKS = ChildUserQuery::create()->findPk($this->user_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUserFromUKS->addUserKeywordSets($this);
             */
        }

        return $this->aUserFromUKS;
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
     * If this ChildUserKeywordSet is new, it will return
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
                    ->filterByUserKeywordSetFromUS($this)
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
     * @return $this|ChildUserKeywordSet The current object (for fluent API support)
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
            $userSearchRemoved->setUserKeywordSetFromUS(null);
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
                ->filterByUserKeywordSetFromUS($this)
                ->count($con);
        }

        return count($this->collUserSearches);
    }

    /**
     * Method called to associate a ChildUserSearch object to this object
     * through the ChildUserSearch foreign key attribute.
     *
     * @param  ChildUserSearch $l ChildUserSearch
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
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
        $userSearch->setUserKeywordSetFromUS($this);
    }

    /**
     * @param  ChildUserSearch $userSearch The ChildUserSearch object to remove.
     * @return $this|ChildUserKeywordSet The current object (for fluent API support)
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
            $userSearch->setUserKeywordSetFromUS(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this UserKeywordSet is new, it will return
     * an empty collection; or if this UserKeywordSet has previously
     * been saved, it will retrieve related UserSearches from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserKeywordSet.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearch[] List of ChildUserSearch objects
     */
    public function getUserSearchesJoinGeoLocationFromUS(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchQuery::create(null, $criteria);
        $query->joinWith('GeoLocationFromUS', $joinBehavior);

        return $this->getUserSearches($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this UserKeywordSet is new, it will return
     * an empty collection; or if this UserKeywordSet has previously
     * been saved, it will retrieve related UserSearches from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserKeywordSet.
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
     * If this ChildUserKeywordSet is new, it will return
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
                    ->filterByUserKeywordSetFromUSSR($this)
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
     * @return $this|ChildUserKeywordSet The current object (for fluent API support)
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
            $userSearchSiteRunRemoved->setUserKeywordSetFromUSSR(null);
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
                ->filterByUserKeywordSetFromUSSR($this)
                ->count($con);
        }

        return count($this->collUserSearchSiteRuns);
    }

    /**
     * Method called to associate a ChildUserSearchSiteRun object to this object
     * through the ChildUserSearchSiteRun foreign key attribute.
     *
     * @param  ChildUserSearchSiteRun $l ChildUserSearchSiteRun
     * @return $this|\JobScooper\DataAccess\UserKeywordSet The current object (for fluent API support)
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
        $userSearchSiteRun->setUserKeywordSetFromUSSR($this);
    }

    /**
     * @param  ChildUserSearchSiteRun $userSearchSiteRun The ChildUserSearchSiteRun object to remove.
     * @return $this|ChildUserKeywordSet The current object (for fluent API support)
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
            $userSearchSiteRun->setUserKeywordSetFromUSSR(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this UserKeywordSet is new, it will return
     * an empty collection; or if this UserKeywordSet has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserKeywordSet.
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
     * Otherwise if this UserKeywordSet is new, it will return
     * an empty collection; or if this UserKeywordSet has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserKeywordSet.
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
     * Otherwise if this UserKeywordSet is new, it will return
     * an empty collection; or if this UserKeywordSet has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserKeywordSet.
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
     * Otherwise if this UserKeywordSet is new, it will return
     * an empty collection; or if this UserKeywordSet has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserKeywordSet.
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
     * Clears out the collGeoLocationFromUSUserFromuses collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addGeoLocationFromUSUserFromuses()
     */
    public function clearGeoLocationFromUSUserFromuses()
    {
        $this->collGeoLocationFromUSUserFromuses = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the combinationCollGeoLocationFromUSUserFromuses crossRef collection.
     *
     * By default this just sets the combinationCollGeoLocationFromUSUserFromuses collection to an empty collection (like clearGeoLocationFromUSUserFromuses());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initGeoLocationFromUSUserFromuses()
    {
        $this->combinationCollGeoLocationFromUSUserFromuses = new ObjectCombinationCollection;
        $this->combinationCollGeoLocationFromUSUserFromusesPartial = true;
    }

    /**
     * Checks if the combinationCollGeoLocationFromUSUserFromuses collection is loaded.
     *
     * @return bool
     */
    public function isGeoLocationFromUSUserFromusesLoaded()
    {
        return null !== $this->combinationCollGeoLocationFromUSUserFromuses;
    }

    /**
     * Gets a combined collection of ChildGeoLocation, ChildUser objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUserKeywordSet is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCombinationCollection Combination list of ChildGeoLocation, ChildUser objects
     */
    public function getGeoLocationFromUSUserFromuses($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollGeoLocationFromUSUserFromusesPartial && !$this->isNew();
        if (null === $this->combinationCollGeoLocationFromUSUserFromuses || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->combinationCollGeoLocationFromUSUserFromuses) {
                    $this->initGeoLocationFromUSUserFromuses();
                }
            } else {

                $query = ChildUserSearchQuery::create(null, $criteria)
                    ->filterByUserKeywordSetFromUS($this)
                    ->joinGeoLocationFromUS()
                    ->joinUserFromUS()
                ;

                $items = $query->find($con);
                $combinationCollGeoLocationFromUSUserFromuses = new ObjectCombinationCollection();
                foreach ($items as $item) {
                    $combination = [];

                    $combination[] = $item->getGeoLocationFromUS();
                    $combination[] = $item->getUserFromUS();
                    $combinationCollGeoLocationFromUSUserFromuses[] = $combination;
                }

                if (null !== $criteria) {
                    return $combinationCollGeoLocationFromUSUserFromuses;
                }

                if ($partial && $this->combinationCollGeoLocationFromUSUserFromuses) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->combinationCollGeoLocationFromUSUserFromuses as $obj) {
                        if (!call_user_func_array([$combinationCollGeoLocationFromUSUserFromuses, 'contains'], $obj)) {
                            $combinationCollGeoLocationFromUSUserFromuses[] = $obj;
                        }
                    }
                }

                $this->combinationCollGeoLocationFromUSUserFromuses = $combinationCollGeoLocationFromUSUserFromuses;
                $this->combinationCollGeoLocationFromUSUserFromusesPartial = false;
            }
        }

        return $this->combinationCollGeoLocationFromUSUserFromuses;
    }

    /**
     * Returns a not cached ObjectCollection of ChildGeoLocation objects. This will hit always the databases.
     * If you have attached new ChildGeoLocation object to this object you need to call `save` first to get
     * the correct return value. Use getGeoLocationFromUSUserFromuses() to get the current internal state.
     *
     * @param ChildUser $userFromUS
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return ChildGeoLocation[]|ObjectCollection
     */
    public function getGeoLocationFromuses(ChildUser $userFromUS = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createGeoLocationFromusesQuery($userFromUS, $criteria)->find($con);
    }

    /**
     * Sets a collection of ChildGeoLocation, ChildUser combination objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $geoLocationFromUSUserFromuses A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildUserKeywordSet The current object (for fluent API support)
     */
    public function setGeoLocationFromUSUserFromuses(Collection $geoLocationFromUSUserFromuses, ConnectionInterface $con = null)
    {
        $this->clearGeoLocationFromUSUserFromuses();
        $currentGeoLocationFromUSUserFromuses = $this->getGeoLocationFromUSUserFromuses();

        $combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion = $currentGeoLocationFromUSUserFromuses->diff($geoLocationFromUSUserFromuses);

        foreach ($combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion as $toDelete) {
            call_user_func_array([$this, 'removeGeoLocationFromUSUserFromUS'], $toDelete);
        }

        foreach ($geoLocationFromUSUserFromuses as $geoLocationFromUSUserFromUS) {
            if (!call_user_func_array([$currentGeoLocationFromUSUserFromuses, 'contains'], $geoLocationFromUSUserFromUS)) {
                call_user_func_array([$this, 'doAddGeoLocationFromUSUserFromUS'], $geoLocationFromUSUserFromUS);
            }
        }

        $this->combinationCollGeoLocationFromUSUserFromusesPartial = false;
        $this->combinationCollGeoLocationFromUSUserFromuses = $geoLocationFromUSUserFromuses;

        return $this;
    }

    /**
     * Gets the number of ChildGeoLocation, ChildUser combination objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ChildGeoLocation, ChildUser combination objects
     */
    public function countGeoLocationFromUSUserFromuses(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollGeoLocationFromUSUserFromusesPartial && !$this->isNew();
        if (null === $this->combinationCollGeoLocationFromUSUserFromuses || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->combinationCollGeoLocationFromUSUserFromuses) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getGeoLocationFromUSUserFromuses());
                }

                $query = ChildUserSearchQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByUserKeywordSetFromUS($this)
                    ->count($con);
            }
        } else {
            return count($this->combinationCollGeoLocationFromUSUserFromuses);
        }
    }

    /**
     * Returns the not cached count of ChildGeoLocation objects. This will hit always the databases.
     * If you have attached new ChildGeoLocation object to this object you need to call `save` first to get
     * the correct return value. Use getGeoLocationFromUSUserFromuses() to get the current internal state.
     *
     * @param ChildUser $userFromUS
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return integer
     */
    public function countGeoLocationFromuses(ChildUser $userFromUS = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createGeoLocationFromusesQuery($userFromUS, $criteria)->count($con);
    }

    /**
     * Associate a ChildGeoLocation to this object
     * through the user_search cross reference table.
     *
     * @param ChildGeoLocation $geoLocationFromUS,
     * @param ChildUser $userFromUS
     * @return ChildUserKeywordSet The current object (for fluent API support)
     */
    public function addGeoLocationFromUS(ChildGeoLocation $geoLocationFromUS, ChildUser $userFromUS)
    {
        if ($this->combinationCollGeoLocationFromUSUserFromuses === null) {
            $this->initGeoLocationFromUSUserFromuses();
        }

        if (!$this->getGeoLocationFromUSUserFromuses()->contains($geoLocationFromUS, $userFromUS)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollGeoLocationFromUSUserFromuses->push($geoLocationFromUS, $userFromUS);
            $this->doAddGeoLocationFromUSUserFromUS($geoLocationFromUS, $userFromUS);
        }

        return $this;
    }

    /**
     * Associate a ChildUser to this object
     * through the user_search cross reference table.
     *
     * @param ChildUser $userFromUS,
     * @param ChildGeoLocation $geoLocationFromUS
     * @return ChildUserKeywordSet The current object (for fluent API support)
     */
    public function addUserFromUS(ChildUser $userFromUS, ChildGeoLocation $geoLocationFromUS)
    {
        if ($this->combinationCollGeoLocationFromUSUserFromuses === null) {
            $this->initGeoLocationFromUSUserFromuses();
        }

        if (!$this->getGeoLocationFromUSUserFromuses()->contains($userFromUS, $geoLocationFromUS)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollGeoLocationFromUSUserFromuses->push($userFromUS, $geoLocationFromUS);
            $this->doAddGeoLocationFromUSUserFromUS($userFromUS, $geoLocationFromUS);
        }

        return $this;
    }

    /**
     *
     * @param ChildGeoLocation $geoLocationFromUS,
     * @param ChildUser $userFromUS
     */
    protected function doAddGeoLocationFromUSUserFromUS(ChildGeoLocation $geoLocationFromUS, ChildUser $userFromUS)
    {
        $userSearch = new ChildUserSearch();

        $userSearch->setGeoLocationFromUS($geoLocationFromUS);
        $userSearch->setUserFromUS($userFromUS);

        $userSearch->setUserKeywordSetFromUS($this);

        $this->addUserSearch($userSearch);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($geoLocationFromUS->isUserKeywordSetFromUSUserFromusesLoaded()) {
            $geoLocationFromUS->initUserKeywordSetFromUSUserFromuses();
            $geoLocationFromUS->getUserKeywordSetFromUSUserFromuses()->push($this, $userFromUS);
        } elseif (!$geoLocationFromUS->getUserKeywordSetFromUSUserFromuses()->contains($this, $userFromUS)) {
            $geoLocationFromUS->getUserKeywordSetFromUSUserFromuses()->push($this, $userFromUS);
        }

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($userFromUS->isUserKeywordSetFromUSGeoLocationFromusesLoaded()) {
            $userFromUS->initUserKeywordSetFromUSGeoLocationFromuses();
            $userFromUS->getUserKeywordSetFromUSGeoLocationFromuses()->push($this, $geoLocationFromUS);
        } elseif (!$userFromUS->getUserKeywordSetFromUSGeoLocationFromuses()->contains($this, $geoLocationFromUS)) {
            $userFromUS->getUserKeywordSetFromUSGeoLocationFromuses()->push($this, $geoLocationFromUS);
        }

    }

    /**
     * Remove geoLocationFromUS, userFromUS of this object
     * through the user_search cross reference table.
     *
     * @param ChildGeoLocation $geoLocationFromUS,
     * @param ChildUser $userFromUS
     * @return ChildUserKeywordSet The current object (for fluent API support)
     */
    public function removeGeoLocationFromUSUserFromUS(ChildGeoLocation $geoLocationFromUS, ChildUser $userFromUS)
    {
        if ($this->getGeoLocationFromUSUserFromuses()->contains($geoLocationFromUS, $userFromUS)) {
            $userSearch = new ChildUserSearch();
            $userSearch->setGeoLocationFromUS($geoLocationFromUS);
            if ($geoLocationFromUS->isUserKeywordSetFromUSUserFromusesLoaded()) {
                //remove the back reference if available
                $geoLocationFromUS->getUserKeywordSetFromUSUserFromuses()->removeObject($this, $userFromUS);
            }

            $userSearch->setUserFromUS($userFromUS);
            if ($userFromUS->isUserKeywordSetFromUSGeoLocationFromusesLoaded()) {
                //remove the back reference if available
                $userFromUS->getUserKeywordSetFromUSGeoLocationFromuses()->removeObject($this, $geoLocationFromUS);
            }

            $userSearch->setUserKeywordSetFromUS($this);
            $this->removeUserSearch(clone $userSearch);
            $userSearch->clear();

            $this->combinationCollGeoLocationFromUSUserFromuses->remove($this->combinationCollGeoLocationFromUSUserFromuses->search($geoLocationFromUS, $userFromUS));

            if (null === $this->combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion) {
                $this->combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion = clone $this->combinationCollGeoLocationFromUSUserFromuses;
                $this->combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion->clear();
            }

            $this->combinationCollGeoLocationFromUSUserFromusesScheduledForDeletion->push($geoLocationFromUS, $userFromUS);
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
            ->filterByUserKeywordSetFromUSSR($this);

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
     * If this ChildUserKeywordSet is new, it will return
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
                    ->filterByUserKeywordSetFromUSSR($this)
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
     * @return $this|ChildUserKeywordSet The current object (for fluent API support)
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
                    ->filterByUserKeywordSetFromUSSR($this)
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
     * @return ChildUserKeywordSet The current object (for fluent API support)
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
     * @return ChildUserKeywordSet The current object (for fluent API support)
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


        $userSearchSiteRun->setUserKeywordSetFromUSSR($this);

        $this->addUserSearchSiteRun($userSearchSiteRun);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($userSearchFromUSSR->isJobSiteFromUSSRUserKeywordSetFromUSSRAppRunIdsLoaded()) {
            $userSearchFromUSSR->initJobSiteFromUSSRUserKeywordSetFromUSSRAppRunIds();
            $userSearchFromUSSR->getJobSiteFromUSSRUserKeywordSetFromUSSRAppRunIds()->push($jobSiteFromUSSR, $this, $appRunId);
        } elseif (!$userSearchFromUSSR->getJobSiteFromUSSRUserKeywordSetFromUSSRAppRunIds()->contains($jobSiteFromUSSR, $this, $appRunId)) {
            $userSearchFromUSSR->getJobSiteFromUSSRUserKeywordSetFromUSSRAppRunIds()->push($jobSiteFromUSSR, $this, $appRunId);
        }

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($jobSiteFromUSSR->isUserSearchFromUSSRUserKeywordSetFromUSSRAppRunIdsLoaded()) {
            $jobSiteFromUSSR->initUserSearchFromUSSRUserKeywordSetFromUSSRAppRunIds();
            $jobSiteFromUSSR->getUserSearchFromUSSRUserKeywordSetFromUSSRAppRunIds()->push($userSearchFromUSSR, $this, $appRunId);
        } elseif (!$jobSiteFromUSSR->getUserSearchFromUSSRUserKeywordSetFromUSSRAppRunIds()->contains($userSearchFromUSSR, $this, $appRunId)) {
            $jobSiteFromUSSR->getUserSearchFromUSSRUserKeywordSetFromUSSRAppRunIds()->push($userSearchFromUSSR, $this, $appRunId);
        }

    }

    /**
     * Remove userSearchFromUSSR, jobSiteFromUSSR, appRunId of this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param string $appRunId
     * @return ChildUserKeywordSet The current object (for fluent API support)
     */
    public function removeUserSearchFromUSSRJobSiteFromUSSRAppRunId(ChildUserSearch $userSearchFromUSSR, ChildJobSiteRecord $jobSiteFromUSSR, $appRunId)
    {
        if ($this->getUserSearchFromUSSRJobSiteFromUSSRAppRunIds()->contains($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId)) {
            $userSearchSiteRun = new ChildUserSearchSiteRun();
            $userSearchSiteRun->setUserSearchFromUSSR($userSearchFromUSSR);
            if ($userSearchFromUSSR->isJobSiteFromUSSRUserKeywordSetFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $userSearchFromUSSR->getJobSiteFromUSSRUserKeywordSetFromUSSRAppRunIds()->removeObject($jobSiteFromUSSR, $this, $appRunId);
            }

            $userSearchSiteRun->setJobSiteFromUSSR($jobSiteFromUSSR);
            if ($jobSiteFromUSSR->isUserSearchFromUSSRUserKeywordSetFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $jobSiteFromUSSR->getUserSearchFromUSSRUserKeywordSetFromUSSRAppRunIds()->removeObject($userSearchFromUSSR, $this, $appRunId);
            }

            $userSearchSiteRun->setAppRunId($appRunId);
            $userSearchSiteRun->setUserKeywordSetFromUSSR($this);
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
        if (null !== $this->aUserFromUKS) {
            $this->aUserFromUKS->removeUserKeywordSet($this);
        }
        $this->user_id = null;
        $this->user_keyword_set_id = null;
        $this->keywords = null;
        $this->keywords_unserialized = null;
        $this->search_key_from_config = null;
        $this->keyword_tokens = null;
        $this->keyword_tokens_unserialized = null;
        $this->user_keyword_set_key = null;
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
            if ($this->combinationCollGeoLocationFromUSUserFromuses) {
                foreach ($this->combinationCollGeoLocationFromUSUserFromuses as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds) {
                foreach ($this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collUserSearches = null;
        $this->collUserSearchSiteRuns = null;
        $this->combinationCollGeoLocationFromUSUserFromuses = null;
        $this->combinationCollUserSearchFromUSSRJobSiteFromUSSRAppRunIds = null;
        $this->aUserFromUKS = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'user_keyword_set_key' column
     */
    public function __toString()
    {
        return (string) $this->getUserKeywordSetKey();
    }

    // sluggable behavior

    /**
     * Wrap the setter for slug value
     *
     * @param   string
     * @return  $this|UserKeywordSet
     */
    public function setSlug($v)
    {
        return $this->setUserKeywordSetKey($v);
    }

    /**
     * Wrap the getter for slug value
     *
     * @return  string
     */
    public function getSlug()
    {
        return $this->getUserKeywordSetKey();
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
        return 'user' . $this->cleanupSlugPart($this->getUserId()) . '_kwd' . $this->cleanupSlugPart($this->getUserKeywordSetId()) . '_search' . $this->cleanupSlugPart($this->getSearchKeyFromConfig()) . '';
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
        $col = 'q.UserKeywordSetKey';
        $compare = $alreadyExists ? $adapter->compareRegex($col, '?') : sprintf('%s = ?', $col);

        $query = \JobScooper\DataAccess\UserKeywordSetQuery::create('q')
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
            ->addDescendingOrderByColumn($adapter->strLength('user_keyword_set_key'))
            ->addDescendingOrderByColumn('user_keyword_set_key')
        ->findOne();

        // First duplicate slug
        if (null == $object) {
            return $slug2 . '1';
        }

        $slugNum = substr($object->getUserKeywordSetKey(), strlen($slug) + 1);
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
