<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\GeoLocation as ChildGeoLocation;
use JobScooper\DataAccess\JobPosting as ChildJobPosting;
use JobScooper\DataAccess\JobPostingQuery as ChildJobPostingQuery;
use JobScooper\DataAccess\JobSiteRecord as ChildJobSiteRecord;
use JobScooper\DataAccess\JobSiteRecordQuery as ChildJobSiteRecordQuery;
use JobScooper\DataAccess\User as ChildUser;
use JobScooper\DataAccess\UserJobMatch as ChildUserJobMatch;
use JobScooper\DataAccess\UserJobMatchQuery as ChildUserJobMatchQuery;
use JobScooper\DataAccess\UserKeywordSet as ChildUserKeywordSet;
use JobScooper\DataAccess\UserKeywordSetQuery as ChildUserKeywordSetQuery;
use JobScooper\DataAccess\UserQuery as ChildUserQuery;
use JobScooper\DataAccess\UserSearch as ChildUserSearch;
use JobScooper\DataAccess\UserSearchQuery as ChildUserSearchQuery;
use JobScooper\DataAccess\UserSearchSiteRun as ChildUserSearchSiteRun;
use JobScooper\DataAccess\UserSearchSiteRunQuery as ChildUserSearchSiteRunQuery;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use JobScooper\DataAccess\Map\UserKeywordSetTableMap;
use JobScooper\DataAccess\Map\UserSearchSiteRunTableMap;
use JobScooper\DataAccess\Map\UserSearchTableMap;
use JobScooper\DataAccess\Map\UserTableMap;
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
 * Base class that represents a row from the 'user' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.DataAccess.Base
 */
abstract class User implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\DataAccess\\Map\\UserTableMap';


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
     * The value for the user_slug field.
     *
     * @var        string
     */
    protected $user_slug;

    /**
     * The value for the email_address field.
     *
     * @var        string
     */
    protected $email_address;

    /**
     * The value for the name field.
     *
     * Note: this column has a database default value of: 'email_address'
     * @var        string
     */
    protected $name;

    /**
     * The value for the config_file_path field.
     *
     * @var        string
     */
    protected $config_file_path;

    /**
     * @var        ObjectCollection|ChildUserKeywordSet[] Collection to store aggregation of ChildUserKeywordSet objects.
     */
    protected $collUserKeywordSets;
    protected $collUserKeywordSetsPartial;

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
     * @var        ObjectCollection|ChildUserJobMatch[] Collection to store aggregation of ChildUserJobMatch objects.
     */
    protected $collUserJobMatches;
    protected $collUserJobMatchesPartial;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildUserKeywordSet, ChildGeoLocation combination combinations.
     */
    protected $combinationCollUserKeywordSetFromUSGeoLocationFromuses;

    /**
     * @var bool
     */
    protected $combinationCollUserKeywordSetFromUSGeoLocationFromusesPartial;

    /**
     * @var        ObjectCollection|ChildUserKeywordSet[] Cross Collection to store aggregation of ChildUserKeywordSet objects.
     */
    protected $collUserKeywordSetFromuses;

    /**
     * @var bool
     */
    protected $collUserKeywordSetFromusesPartial;

    /**
     * @var        ObjectCollection|ChildGeoLocation[] Cross Collection to store aggregation of ChildGeoLocation objects.
     */
    protected $collGeoLocationFromuses;

    /**
     * @var bool
     */
    protected $collGeoLocationFromusesPartial;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildJobSiteRecord, ChildUserSearch combination combinations.
     */
    protected $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds;

    /**
     * @var bool
     */
    protected $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsPartial;

    /**
     * @var        ObjectCollection|ChildJobSiteRecord[] Cross Collection to store aggregation of ChildJobSiteRecord objects.
     */
    protected $collJobSiteFromUSSRs;

    /**
     * @var bool
     */
    protected $collJobSiteFromUSSRsPartial;

    /**
     * @var        ObjectCollection|ChildUserSearch[] Cross Collection to store aggregation of ChildUserSearch objects.
     */
    protected $collUserSearchFromUSSRs;

    /**
     * @var bool
     */
    protected $collUserSearchFromUSSRsPartial;

    /**
     * @var        ObjectCollection|ChildJobPosting[] Cross Collection to store aggregation of ChildJobPosting objects.
     */
    protected $collJobPostingFromUJMs;

    /**
     * @var bool
     */
    protected $collJobPostingFromUJMsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildUserKeywordSet, ChildGeoLocation combination combinations.
     */
    protected $combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion = null;

    /**
     * @var ObjectCombinationCollection Cross CombinationCollection to store aggregation of ChildJobSiteRecord, ChildUserSearch combination combinations.
     */
    protected $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildJobPosting[]
     */
    protected $jobPostingFromUJMsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserKeywordSet[]
     */
    protected $userKeywordSetsScheduledForDeletion = null;

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
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserJobMatch[]
     */
    protected $userJobMatchesScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->name = 'email_address';
    }

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\User object.
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
     * Compares this with another <code>User</code> instance.  If
     * <code>obj</code> is an instance of <code>User</code>, delegates to
     * <code>equals(User)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|User The current object, for fluid interface
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
     * Get the [user_slug] column value.
     *
     * @return string
     */
    public function getUserSlug()
    {
        return $this->user_slug;
    }

    /**
     * Get the [email_address] column value.
     *
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->email_address;
    }

    /**
     * Get the [name] column value.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the [config_file_path] column value.
     *
     * @return string
     */
    public function getConfigFilePath()
    {
        return $this->config_file_path;
    }

    /**
     * Set the value of [user_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
     */
    public function setUserId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_id !== $v) {
            $this->user_id = $v;
            $this->modifiedColumns[UserTableMap::COL_USER_ID] = true;
        }

        return $this;
    } // setUserId()

    /**
     * Set the value of [user_slug] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
     */
    public function setUserSlug($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->user_slug !== $v) {
            $this->user_slug = $v;
            $this->modifiedColumns[UserTableMap::COL_USER_SLUG] = true;
        }

        return $this;
    } // setUserSlug()

    /**
     * Set the value of [email_address] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
     */
    public function setEmailAddress($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->email_address !== $v) {
            $this->email_address = $v;
            $this->modifiedColumns[UserTableMap::COL_EMAIL_ADDRESS] = true;
        }

        return $this;
    } // setEmailAddress()

    /**
     * Set the value of [name] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
     */
    public function setName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->name !== $v) {
            $this->name = $v;
            $this->modifiedColumns[UserTableMap::COL_NAME] = true;
        }

        return $this;
    } // setName()

    /**
     * Set the value of [config_file_path] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
     */
    public function setConfigFilePath($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->config_file_path !== $v) {
            $this->config_file_path = $v;
            $this->modifiedColumns[UserTableMap::COL_CONFIG_FILE_PATH] = true;
        }

        return $this;
    } // setConfigFilePath()

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
            if ($this->name !== 'email_address') {
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserTableMap::translateFieldName('UserId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserTableMap::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_slug = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserTableMap::translateFieldName('EmailAddress', TableMap::TYPE_PHPNAME, $indexType)];
            $this->email_address = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserTableMap::translateFieldName('Name', TableMap::TYPE_PHPNAME, $indexType)];
            $this->name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : UserTableMap::translateFieldName('ConfigFilePath', TableMap::TYPE_PHPNAME, $indexType)];
            $this->config_file_path = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 5; // 5 = UserTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\DataAccess\\User'), 0, $e);
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
            $con = Propel::getServiceContainer()->getReadConnection(UserTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildUserQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collUserKeywordSets = null;

            $this->collUserSearches = null;

            $this->collUserSearchSiteRuns = null;

            $this->collUserJobMatches = null;

            $this->collUserKeywordSetFromUSGeoLocationFromuses = null;
            $this->collJobSiteFromUSSRUserSearchFromUSSRAppRunIds = null;
            $this->collJobPostingFromUJMs = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see User::setDeleted()
     * @see User::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildUserQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            // sluggable behavior

            if ($this->isColumnModified(UserTableMap::COL_USER_SLUG) && $this->getUserSlug()) {
                $this->setUserSlug($this->makeSlugUnique($this->getUserSlug()));
            } else {
                $this->setUserSlug($this->createSlug());
            }
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
                UserTableMap::addInstanceToPool($this);
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

            if ($this->combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion !== null) {
                if (!$this->combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion as $combination) {
                        $entryPk = [];

                        $entryPk[0] = $this->getUserId();
                        $entryPk[1] = $combination[0]->getUserKeywordSetKey();
                        $entryPk[0] = $combination[0]->getUserId();
                        $entryPk[2] = $combination[1]->getGeoLocationId();

                        $pks[] = $entryPk;
                    }

                    \JobScooper\DataAccess\UserSearchQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion = null;
                }

            }

            if (null !== $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses) {
                foreach ($this->combinationCollUserKeywordSetFromUSGeoLocationFromuses as $combination) {

                    //$combination[0] = UserKeywordSet (user_search_fk_ca577f)
                    if (!$combination[0]->isDeleted() && ($combination[0]->isNew() || $combination[0]->isModified())) {
                        $combination[0]->save($con);
                    }

                    //$combination[1] = GeoLocation (user_search_fk_38c4c7)
                    if (!$combination[1]->isDeleted() && ($combination[1]->isNew() || $combination[1]->isModified())) {
                        $combination[1]->save($con);
                    }

                }
            }


            if ($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion !== null) {
                if (!$this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion as $combination) {
                        $entryPk = [];

                        $entryPk[] = $this->getUserId();
                        $entryPk[2] = $combination[0]->getJobSiteKey();
                        $entryPk[] = $combination[1]->getUserId();
                        $entryPk[0] = $combination[1]->getUserKeywordSetKey();
                        $entryPk[1] = $combination[1]->getGeoLocationId();
                        $entryPk[] = $combination[1]->getUserSearchKey();
                        //$combination[2] = AppRunId;
                        $entryPk[3] = $combination[2];

                        $pks[] = $entryPk;
                    }

                    \JobScooper\DataAccess\UserSearchSiteRunQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion = null;
                }

            }

            if (null !== $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds) {
                foreach ($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds as $combination) {

                    //$combination[0] = JobSiteRecord (user_search_site_run_fk_168d10)
                    if (!$combination[0]->isDeleted() && ($combination[0]->isNew() || $combination[0]->isModified())) {
                        $combination[0]->save($con);
                    }

                    //$combination[1] = UserSearch (user_search_site_run_fk_03b478)
                    if (!$combination[1]->isDeleted() && ($combination[1]->isNew() || $combination[1]->isModified())) {
                        $combination[1]->save($con);
                    }

                    //$combination[2] = AppRunId; Nothing to save.
                }
            }


            if ($this->jobPostingFromUJMsScheduledForDeletion !== null) {
                if (!$this->jobPostingFromUJMsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    foreach ($this->jobPostingFromUJMsScheduledForDeletion as $entry) {
                        $entryPk = [];

                        $entryPk[0] = $this->getUserId();
                        $entryPk[1] = $entry->getJobPostingId();
                        $pks[] = $entryPk;
                    }

                    \JobScooper\DataAccess\UserJobMatchQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);

                    $this->jobPostingFromUJMsScheduledForDeletion = null;
                }

            }

            if ($this->collJobPostingFromUJMs) {
                foreach ($this->collJobPostingFromUJMs as $jobPostingFromUJM) {
                    if (!$jobPostingFromUJM->isDeleted() && ($jobPostingFromUJM->isNew() || $jobPostingFromUJM->isModified())) {
                        $jobPostingFromUJM->save($con);
                    }
                }
            }


            if ($this->userKeywordSetsScheduledForDeletion !== null) {
                if (!$this->userKeywordSetsScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\UserKeywordSetQuery::create()
                        ->filterByPrimaryKeys($this->userKeywordSetsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->userKeywordSetsScheduledForDeletion = null;
                }
            }

            if ($this->collUserKeywordSets !== null) {
                foreach ($this->collUserKeywordSets as $referrerFK) {
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

            if ($this->userJobMatchesScheduledForDeletion !== null) {
                if (!$this->userJobMatchesScheduledForDeletion->isEmpty()) {
                    \JobScooper\DataAccess\UserJobMatchQuery::create()
                        ->filterByPrimaryKeys($this->userJobMatchesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->userJobMatchesScheduledForDeletion = null;
                }
            }

            if ($this->collUserJobMatches !== null) {
                foreach ($this->collUserJobMatches as $referrerFK) {
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

        $this->modifiedColumns[UserTableMap::COL_USER_ID] = true;

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(UserTableMap::COL_USER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_id';
        }
        if ($this->isColumnModified(UserTableMap::COL_USER_SLUG)) {
            $modifiedColumns[':p' . $index++]  = 'user_slug';
        }
        if ($this->isColumnModified(UserTableMap::COL_EMAIL_ADDRESS)) {
            $modifiedColumns[':p' . $index++]  = 'email_address';
        }
        if ($this->isColumnModified(UserTableMap::COL_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'name';
        }
        if ($this->isColumnModified(UserTableMap::COL_CONFIG_FILE_PATH)) {
            $modifiedColumns[':p' . $index++]  = 'config_file_path';
        }

        $sql = sprintf(
            'INSERT INTO user (%s) VALUES (%s)',
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
                    case 'user_slug':
                        $stmt->bindValue($identifier, $this->user_slug, PDO::PARAM_STR);
                        break;
                    case 'email_address':
                        $stmt->bindValue($identifier, $this->email_address, PDO::PARAM_STR);
                        break;
                    case 'name':
                        $stmt->bindValue($identifier, $this->name, PDO::PARAM_STR);
                        break;
                    case 'config_file_path':
                        $stmt->bindValue($identifier, $this->config_file_path, PDO::PARAM_STR);
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
        $pos = UserTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getUserSlug();
                break;
            case 2:
                return $this->getEmailAddress();
                break;
            case 3:
                return $this->getName();
                break;
            case 4:
                return $this->getConfigFilePath();
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

        if (isset($alreadyDumpedObjects['User'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['User'][$this->hashCode()] = true;
        $keys = UserTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getUserId(),
            $keys[1] => $this->getUserSlug(),
            $keys[2] => $this->getEmailAddress(),
            $keys[3] => $this->getName(),
            $keys[4] => $this->getConfigFilePath(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collUserKeywordSets) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userKeywordSets';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_keyword_sets';
                        break;
                    default:
                        $key = 'UserKeywordSets';
                }

                $result[$key] = $this->collUserKeywordSets->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
            if (null !== $this->collUserJobMatches) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userJobMatches';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_job_matches';
                        break;
                    default:
                        $key = 'UserJobMatches';
                }

                $result[$key] = $this->collUserJobMatches->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
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
     * @return $this|\JobScooper\DataAccess\User
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\DataAccess\User
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setUserId($value);
                break;
            case 1:
                $this->setUserSlug($value);
                break;
            case 2:
                $this->setEmailAddress($value);
                break;
            case 3:
                $this->setName($value);
                break;
            case 4:
                $this->setConfigFilePath($value);
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
        $keys = UserTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setUserId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setUserSlug($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setEmailAddress($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setName($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setConfigFilePath($arr[$keys[4]]);
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
     * @return $this|\JobScooper\DataAccess\User The current object, for fluid interface
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
        $criteria = new Criteria(UserTableMap::DATABASE_NAME);

        if ($this->isColumnModified(UserTableMap::COL_USER_ID)) {
            $criteria->add(UserTableMap::COL_USER_ID, $this->user_id);
        }
        if ($this->isColumnModified(UserTableMap::COL_USER_SLUG)) {
            $criteria->add(UserTableMap::COL_USER_SLUG, $this->user_slug);
        }
        if ($this->isColumnModified(UserTableMap::COL_EMAIL_ADDRESS)) {
            $criteria->add(UserTableMap::COL_EMAIL_ADDRESS, $this->email_address);
        }
        if ($this->isColumnModified(UserTableMap::COL_NAME)) {
            $criteria->add(UserTableMap::COL_NAME, $this->name);
        }
        if ($this->isColumnModified(UserTableMap::COL_CONFIG_FILE_PATH)) {
            $criteria->add(UserTableMap::COL_CONFIG_FILE_PATH, $this->config_file_path);
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
        $criteria = ChildUserQuery::create();
        $criteria->add(UserTableMap::COL_USER_ID, $this->user_id);

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
        $validPk = null !== $this->getUserId();

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
        return $this->getUserId();
    }

    /**
     * Generic method to set the primary key (user_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setUserId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getUserId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\DataAccess\User (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setUserSlug($this->getUserSlug());
        $copyObj->setEmailAddress($this->getEmailAddress());
        $copyObj->setName($this->getName());
        $copyObj->setConfigFilePath($this->getConfigFilePath());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getUserKeywordSets() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserKeywordSet($relObj->copy($deepCopy));
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

            foreach ($this->getUserJobMatches() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserJobMatch($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setUserId(NULL); // this is a auto-increment column, so set to default value
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
     * @return \JobScooper\DataAccess\User Clone of current object.
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
        if ('UserKeywordSet' == $relationName) {
            $this->initUserKeywordSets();
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
        if ('UserJobMatch' == $relationName) {
            $this->initUserJobMatches();
            return;
        }
    }

    /**
     * Clears out the collUserKeywordSets collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserKeywordSets()
     */
    public function clearUserKeywordSets()
    {
        $this->collUserKeywordSets = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collUserKeywordSets collection loaded partially.
     */
    public function resetPartialUserKeywordSets($v = true)
    {
        $this->collUserKeywordSetsPartial = $v;
    }

    /**
     * Initializes the collUserKeywordSets collection.
     *
     * By default this just sets the collUserKeywordSets collection to an empty array (like clearcollUserKeywordSets());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUserKeywordSets($overrideExisting = true)
    {
        if (null !== $this->collUserKeywordSets && !$overrideExisting) {
            return;
        }

        $collectionClassName = UserKeywordSetTableMap::getTableMap()->getCollectionClassName();

        $this->collUserKeywordSets = new $collectionClassName;
        $this->collUserKeywordSets->setModel('\JobScooper\DataAccess\UserKeywordSet');
    }

    /**
     * Gets an array of ChildUserKeywordSet objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUser is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildUserKeywordSet[] List of ChildUserKeywordSet objects
     * @throws PropelException
     */
    public function getUserKeywordSets(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserKeywordSetsPartial && !$this->isNew();
        if (null === $this->collUserKeywordSets || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUserKeywordSets) {
                // return empty collection
                $this->initUserKeywordSets();
            } else {
                $collUserKeywordSets = ChildUserKeywordSetQuery::create(null, $criteria)
                    ->filterByUserFromUKS($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collUserKeywordSetsPartial && count($collUserKeywordSets)) {
                        $this->initUserKeywordSets(false);

                        foreach ($collUserKeywordSets as $obj) {
                            if (false == $this->collUserKeywordSets->contains($obj)) {
                                $this->collUserKeywordSets->append($obj);
                            }
                        }

                        $this->collUserKeywordSetsPartial = true;
                    }

                    return $collUserKeywordSets;
                }

                if ($partial && $this->collUserKeywordSets) {
                    foreach ($this->collUserKeywordSets as $obj) {
                        if ($obj->isNew()) {
                            $collUserKeywordSets[] = $obj;
                        }
                    }
                }

                $this->collUserKeywordSets = $collUserKeywordSets;
                $this->collUserKeywordSetsPartial = false;
            }
        }

        return $this->collUserKeywordSets;
    }

    /**
     * Sets a collection of ChildUserKeywordSet objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $userKeywordSets A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function setUserKeywordSets(Collection $userKeywordSets, ConnectionInterface $con = null)
    {
        /** @var ChildUserKeywordSet[] $userKeywordSetsToDelete */
        $userKeywordSetsToDelete = $this->getUserKeywordSets(new Criteria(), $con)->diff($userKeywordSets);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->userKeywordSetsScheduledForDeletion = clone $userKeywordSetsToDelete;

        foreach ($userKeywordSetsToDelete as $userKeywordSetRemoved) {
            $userKeywordSetRemoved->setUserFromUKS(null);
        }

        $this->collUserKeywordSets = null;
        foreach ($userKeywordSets as $userKeywordSet) {
            $this->addUserKeywordSet($userKeywordSet);
        }

        $this->collUserKeywordSets = $userKeywordSets;
        $this->collUserKeywordSetsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related UserKeywordSet objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related UserKeywordSet objects.
     * @throws PropelException
     */
    public function countUserKeywordSets(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserKeywordSetsPartial && !$this->isNew();
        if (null === $this->collUserKeywordSets || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserKeywordSets) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getUserKeywordSets());
            }

            $query = ChildUserKeywordSetQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByUserFromUKS($this)
                ->count($con);
        }

        return count($this->collUserKeywordSets);
    }

    /**
     * Method called to associate a ChildUserKeywordSet object to this object
     * through the ChildUserKeywordSet foreign key attribute.
     *
     * @param  ChildUserKeywordSet $l ChildUserKeywordSet
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
     */
    public function addUserKeywordSet(ChildUserKeywordSet $l)
    {
        if ($this->collUserKeywordSets === null) {
            $this->initUserKeywordSets();
            $this->collUserKeywordSetsPartial = true;
        }

        if (!$this->collUserKeywordSets->contains($l)) {
            $this->doAddUserKeywordSet($l);

            if ($this->userKeywordSetsScheduledForDeletion and $this->userKeywordSetsScheduledForDeletion->contains($l)) {
                $this->userKeywordSetsScheduledForDeletion->remove($this->userKeywordSetsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildUserKeywordSet $userKeywordSet The ChildUserKeywordSet object to add.
     */
    protected function doAddUserKeywordSet(ChildUserKeywordSet $userKeywordSet)
    {
        $this->collUserKeywordSets[]= $userKeywordSet;
        $userKeywordSet->setUserFromUKS($this);
    }

    /**
     * @param  ChildUserKeywordSet $userKeywordSet The ChildUserKeywordSet object to remove.
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function removeUserKeywordSet(ChildUserKeywordSet $userKeywordSet)
    {
        if ($this->getUserKeywordSets()->contains($userKeywordSet)) {
            $pos = $this->collUserKeywordSets->search($userKeywordSet);
            $this->collUserKeywordSets->remove($pos);
            if (null === $this->userKeywordSetsScheduledForDeletion) {
                $this->userKeywordSetsScheduledForDeletion = clone $this->collUserKeywordSets;
                $this->userKeywordSetsScheduledForDeletion->clear();
            }
            $this->userKeywordSetsScheduledForDeletion[]= clone $userKeywordSet;
            $userKeywordSet->setUserFromUKS(null);
        }

        return $this;
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
     * If this ChildUser is new, it will return
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
                    ->filterByUserFromUS($this)
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
     * @return $this|ChildUser The current object (for fluent API support)
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
            $userSearchRemoved->setUserFromUS(null);
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
                ->filterByUserFromUS($this)
                ->count($con);
        }

        return count($this->collUserSearches);
    }

    /**
     * Method called to associate a ChildUserSearch object to this object
     * through the ChildUserSearch foreign key attribute.
     *
     * @param  ChildUserSearch $l ChildUserSearch
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
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
        $userSearch->setUserFromUS($this);
    }

    /**
     * @param  ChildUserSearch $userSearch The ChildUserSearch object to remove.
     * @return $this|ChildUser The current object (for fluent API support)
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
            $userSearch->setUserFromUS(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this User is new, it will return
     * an empty collection; or if this User has previously
     * been saved, it will retrieve related UserSearches from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in User.
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
     * Otherwise if this User is new, it will return
     * an empty collection; or if this User has previously
     * been saved, it will retrieve related UserSearches from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in User.
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
     * If this ChildUser is new, it will return
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
                    ->filterByUserFromUSSR($this)
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
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function setUserSearchSiteRuns(Collection $userSearchSiteRuns, ConnectionInterface $con = null)
    {
        /** @var ChildUserSearchSiteRun[] $userSearchSiteRunsToDelete */
        $userSearchSiteRunsToDelete = $this->getUserSearchSiteRuns(new Criteria(), $con)->diff($userSearchSiteRuns);


        $this->userSearchSiteRunsScheduledForDeletion = $userSearchSiteRunsToDelete;

        foreach ($userSearchSiteRunsToDelete as $userSearchSiteRunRemoved) {
            $userSearchSiteRunRemoved->setUserFromUSSR(null);
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
                ->filterByUserFromUSSR($this)
                ->count($con);
        }

        return count($this->collUserSearchSiteRuns);
    }

    /**
     * Method called to associate a ChildUserSearchSiteRun object to this object
     * through the ChildUserSearchSiteRun foreign key attribute.
     *
     * @param  ChildUserSearchSiteRun $l ChildUserSearchSiteRun
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
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
        $userSearchSiteRun->setUserFromUSSR($this);
    }

    /**
     * @param  ChildUserSearchSiteRun $userSearchSiteRun The ChildUserSearchSiteRun object to remove.
     * @return $this|ChildUser The current object (for fluent API support)
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
            $userSearchSiteRun->setUserFromUSSR(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this User is new, it will return
     * an empty collection; or if this User has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in User.
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
     * Otherwise if this User is new, it will return
     * an empty collection; or if this User has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in User.
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
     * Otherwise if this User is new, it will return
     * an empty collection; or if this User has previously
     * been saved, it will retrieve related UserSearchSiteRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in User.
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
     * Clears out the collUserJobMatches collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserJobMatches()
     */
    public function clearUserJobMatches()
    {
        $this->collUserJobMatches = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collUserJobMatches collection loaded partially.
     */
    public function resetPartialUserJobMatches($v = true)
    {
        $this->collUserJobMatchesPartial = $v;
    }

    /**
     * Initializes the collUserJobMatches collection.
     *
     * By default this just sets the collUserJobMatches collection to an empty array (like clearcollUserJobMatches());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUserJobMatches($overrideExisting = true)
    {
        if (null !== $this->collUserJobMatches && !$overrideExisting) {
            return;
        }

        $collectionClassName = UserJobMatchTableMap::getTableMap()->getCollectionClassName();

        $this->collUserJobMatches = new $collectionClassName;
        $this->collUserJobMatches->setModel('\JobScooper\DataAccess\UserJobMatch');
    }

    /**
     * Gets an array of ChildUserJobMatch objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUser is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildUserJobMatch[] List of ChildUserJobMatch objects
     * @throws PropelException
     */
    public function getUserJobMatches(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserJobMatchesPartial && !$this->isNew();
        if (null === $this->collUserJobMatches || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUserJobMatches) {
                // return empty collection
                $this->initUserJobMatches();
            } else {
                $collUserJobMatches = ChildUserJobMatchQuery::create(null, $criteria)
                    ->filterByUserFromUJM($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collUserJobMatchesPartial && count($collUserJobMatches)) {
                        $this->initUserJobMatches(false);

                        foreach ($collUserJobMatches as $obj) {
                            if (false == $this->collUserJobMatches->contains($obj)) {
                                $this->collUserJobMatches->append($obj);
                            }
                        }

                        $this->collUserJobMatchesPartial = true;
                    }

                    return $collUserJobMatches;
                }

                if ($partial && $this->collUserJobMatches) {
                    foreach ($this->collUserJobMatches as $obj) {
                        if ($obj->isNew()) {
                            $collUserJobMatches[] = $obj;
                        }
                    }
                }

                $this->collUserJobMatches = $collUserJobMatches;
                $this->collUserJobMatchesPartial = false;
            }
        }

        return $this->collUserJobMatches;
    }

    /**
     * Sets a collection of ChildUserJobMatch objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $userJobMatches A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function setUserJobMatches(Collection $userJobMatches, ConnectionInterface $con = null)
    {
        /** @var ChildUserJobMatch[] $userJobMatchesToDelete */
        $userJobMatchesToDelete = $this->getUserJobMatches(new Criteria(), $con)->diff($userJobMatches);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->userJobMatchesScheduledForDeletion = clone $userJobMatchesToDelete;

        foreach ($userJobMatchesToDelete as $userJobMatchRemoved) {
            $userJobMatchRemoved->setUserFromUJM(null);
        }

        $this->collUserJobMatches = null;
        foreach ($userJobMatches as $userJobMatch) {
            $this->addUserJobMatch($userJobMatch);
        }

        $this->collUserJobMatches = $userJobMatches;
        $this->collUserJobMatchesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related UserJobMatch objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related UserJobMatch objects.
     * @throws PropelException
     */
    public function countUserJobMatches(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserJobMatchesPartial && !$this->isNew();
        if (null === $this->collUserJobMatches || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserJobMatches) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getUserJobMatches());
            }

            $query = ChildUserJobMatchQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByUserFromUJM($this)
                ->count($con);
        }

        return count($this->collUserJobMatches);
    }

    /**
     * Method called to associate a ChildUserJobMatch object to this object
     * through the ChildUserJobMatch foreign key attribute.
     *
     * @param  ChildUserJobMatch $l ChildUserJobMatch
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
     */
    public function addUserJobMatch(ChildUserJobMatch $l)
    {
        if ($this->collUserJobMatches === null) {
            $this->initUserJobMatches();
            $this->collUserJobMatchesPartial = true;
        }

        if (!$this->collUserJobMatches->contains($l)) {
            $this->doAddUserJobMatch($l);

            if ($this->userJobMatchesScheduledForDeletion and $this->userJobMatchesScheduledForDeletion->contains($l)) {
                $this->userJobMatchesScheduledForDeletion->remove($this->userJobMatchesScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildUserJobMatch $userJobMatch The ChildUserJobMatch object to add.
     */
    protected function doAddUserJobMatch(ChildUserJobMatch $userJobMatch)
    {
        $this->collUserJobMatches[]= $userJobMatch;
        $userJobMatch->setUserFromUJM($this);
    }

    /**
     * @param  ChildUserJobMatch $userJobMatch The ChildUserJobMatch object to remove.
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function removeUserJobMatch(ChildUserJobMatch $userJobMatch)
    {
        if ($this->getUserJobMatches()->contains($userJobMatch)) {
            $pos = $this->collUserJobMatches->search($userJobMatch);
            $this->collUserJobMatches->remove($pos);
            if (null === $this->userJobMatchesScheduledForDeletion) {
                $this->userJobMatchesScheduledForDeletion = clone $this->collUserJobMatches;
                $this->userJobMatchesScheduledForDeletion->clear();
            }
            $this->userJobMatchesScheduledForDeletion[]= clone $userJobMatch;
            $userJobMatch->setUserFromUJM(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this User is new, it will return
     * an empty collection; or if this User has previously
     * been saved, it will retrieve related UserJobMatches from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in User.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserJobMatch[] List of ChildUserJobMatch objects
     */
    public function getUserJobMatchesJoinJobPostingFromUJM(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserJobMatchQuery::create(null, $criteria);
        $query->joinWith('JobPostingFromUJM', $joinBehavior);

        return $this->getUserJobMatches($query, $con);
    }

    /**
     * Clears out the collUserKeywordSetFromUSGeoLocationFromuses collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserKeywordSetFromUSGeoLocationFromuses()
     */
    public function clearUserKeywordSetFromUSGeoLocationFromuses()
    {
        $this->collUserKeywordSetFromUSGeoLocationFromuses = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the combinationCollUserKeywordSetFromUSGeoLocationFromuses crossRef collection.
     *
     * By default this just sets the combinationCollUserKeywordSetFromUSGeoLocationFromuses collection to an empty collection (like clearUserKeywordSetFromUSGeoLocationFromuses());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initUserKeywordSetFromUSGeoLocationFromuses()
    {
        $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses = new ObjectCombinationCollection;
        $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesPartial = true;
    }

    /**
     * Checks if the combinationCollUserKeywordSetFromUSGeoLocationFromuses collection is loaded.
     *
     * @return bool
     */
    public function isUserKeywordSetFromUSGeoLocationFromusesLoaded()
    {
        return null !== $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses;
    }

    /**
     * Gets a combined collection of ChildUserKeywordSet, ChildGeoLocation objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUser is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCombinationCollection Combination list of ChildUserKeywordSet, ChildGeoLocation objects
     */
    public function getUserKeywordSetFromUSGeoLocationFromuses($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesPartial && !$this->isNew();
        if (null === $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses) {
                    $this->initUserKeywordSetFromUSGeoLocationFromuses();
                }
            } else {

                $query = ChildUserSearchQuery::create(null, $criteria)
                    ->filterByUserFromUS($this)
                    ->joinUserKeywordSetFromUS()
                    ->joinGeoLocationFromUS()
                ;

                $items = $query->find($con);
                $combinationCollUserKeywordSetFromUSGeoLocationFromuses = new ObjectCombinationCollection();
                foreach ($items as $item) {
                    $combination = [];

                    $combination[] = $item->getUserKeywordSetFromUS();
                    $combination[] = $item->getGeoLocationFromUS();
                    $combinationCollUserKeywordSetFromUSGeoLocationFromuses[] = $combination;
                }

                if (null !== $criteria) {
                    return $combinationCollUserKeywordSetFromUSGeoLocationFromuses;
                }

                if ($partial && $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->combinationCollUserKeywordSetFromUSGeoLocationFromuses as $obj) {
                        if (!call_user_func_array([$combinationCollUserKeywordSetFromUSGeoLocationFromuses, 'contains'], $obj)) {
                            $combinationCollUserKeywordSetFromUSGeoLocationFromuses[] = $obj;
                        }
                    }
                }

                $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses = $combinationCollUserKeywordSetFromUSGeoLocationFromuses;
                $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesPartial = false;
            }
        }

        return $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses;
    }

    /**
     * Returns a not cached ObjectCollection of ChildUserKeywordSet objects. This will hit always the databases.
     * If you have attached new ChildUserKeywordSet object to this object you need to call `save` first to get
     * the correct return value. Use getUserKeywordSetFromUSGeoLocationFromuses() to get the current internal state.
     *
     * @param ChildGeoLocation $geoLocationFromUS
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return ChildUserKeywordSet[]|ObjectCollection
     */
    public function getUserKeywordSetFromuses(ChildGeoLocation $geoLocationFromUS = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createUserKeywordSetFromusesQuery($geoLocationFromUS, $criteria)->find($con);
    }

    /**
     * Sets a collection of ChildUserKeywordSet, ChildGeoLocation combination objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $userKeywordSetFromUSGeoLocationFromuses A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function setUserKeywordSetFromUSGeoLocationFromuses(Collection $userKeywordSetFromUSGeoLocationFromuses, ConnectionInterface $con = null)
    {
        $this->clearUserKeywordSetFromUSGeoLocationFromuses();
        $currentUserKeywordSetFromUSGeoLocationFromuses = $this->getUserKeywordSetFromUSGeoLocationFromuses();

        $combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion = $currentUserKeywordSetFromUSGeoLocationFromuses->diff($userKeywordSetFromUSGeoLocationFromuses);

        foreach ($combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion as $toDelete) {
            call_user_func_array([$this, 'removeUserKeywordSetFromUSGeoLocationFromUS'], $toDelete);
        }

        foreach ($userKeywordSetFromUSGeoLocationFromuses as $userKeywordSetFromUSGeoLocationFromUS) {
            if (!call_user_func_array([$currentUserKeywordSetFromUSGeoLocationFromuses, 'contains'], $userKeywordSetFromUSGeoLocationFromUS)) {
                call_user_func_array([$this, 'doAddUserKeywordSetFromUSGeoLocationFromUS'], $userKeywordSetFromUSGeoLocationFromUS);
            }
        }

        $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesPartial = false;
        $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses = $userKeywordSetFromUSGeoLocationFromuses;

        return $this;
    }

    /**
     * Gets the number of ChildUserKeywordSet, ChildGeoLocation combination objects related by a many-to-many relationship
     * to the current object by way of the user_search cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ChildUserKeywordSet, ChildGeoLocation combination objects
     */
    public function countUserKeywordSetFromUSGeoLocationFromuses(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesPartial && !$this->isNew();
        if (null === $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getUserKeywordSetFromUSGeoLocationFromuses());
                }

                $query = ChildUserSearchQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByUserFromUS($this)
                    ->count($con);
            }
        } else {
            return count($this->combinationCollUserKeywordSetFromUSGeoLocationFromuses);
        }
    }

    /**
     * Returns the not cached count of ChildUserKeywordSet objects. This will hit always the databases.
     * If you have attached new ChildUserKeywordSet object to this object you need to call `save` first to get
     * the correct return value. Use getUserKeywordSetFromUSGeoLocationFromuses() to get the current internal state.
     *
     * @param ChildGeoLocation $geoLocationFromUS
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return integer
     */
    public function countUserKeywordSetFromuses(ChildGeoLocation $geoLocationFromUS = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createUserKeywordSetFromusesQuery($geoLocationFromUS, $criteria)->count($con);
    }

    /**
     * Associate a ChildUserKeywordSet to this object
     * through the user_search cross reference table.
     *
     * @param ChildUserKeywordSet $userKeywordSetFromUS,
     * @param ChildGeoLocation $geoLocationFromUS
     * @return ChildUser The current object (for fluent API support)
     */
    public function addUserKeywordSetFromUS(ChildUserKeywordSet $userKeywordSetFromUS, ChildGeoLocation $geoLocationFromUS)
    {
        if ($this->combinationCollUserKeywordSetFromUSGeoLocationFromuses === null) {
            $this->initUserKeywordSetFromUSGeoLocationFromuses();
        }

        if (!$this->getUserKeywordSetFromUSGeoLocationFromuses()->contains($userKeywordSetFromUS, $geoLocationFromUS)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses->push($userKeywordSetFromUS, $geoLocationFromUS);
            $this->doAddUserKeywordSetFromUSGeoLocationFromUS($userKeywordSetFromUS, $geoLocationFromUS);
        }

        return $this;
    }

    /**
     * Associate a ChildGeoLocation to this object
     * through the user_search cross reference table.
     *
     * @param ChildGeoLocation $geoLocationFromUS,
     * @param ChildUserKeywordSet $userKeywordSetFromUS
     * @return ChildUser The current object (for fluent API support)
     */
    public function addGeoLocationFromUS(ChildGeoLocation $geoLocationFromUS, ChildUserKeywordSet $userKeywordSetFromUS)
    {
        if ($this->combinationCollUserKeywordSetFromUSGeoLocationFromuses === null) {
            $this->initUserKeywordSetFromUSGeoLocationFromuses();
        }

        if (!$this->getUserKeywordSetFromUSGeoLocationFromuses()->contains($geoLocationFromUS, $userKeywordSetFromUS)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses->push($geoLocationFromUS, $userKeywordSetFromUS);
            $this->doAddUserKeywordSetFromUSGeoLocationFromUS($geoLocationFromUS, $userKeywordSetFromUS);
        }

        return $this;
    }

    /**
     *
     * @param ChildUserKeywordSet $userKeywordSetFromUS,
     * @param ChildGeoLocation $geoLocationFromUS
     */
    protected function doAddUserKeywordSetFromUSGeoLocationFromUS(ChildUserKeywordSet $userKeywordSetFromUS, ChildGeoLocation $geoLocationFromUS)
    {
        $userSearch = new ChildUserSearch();

        $userSearch->setUserKeywordSetFromUS($userKeywordSetFromUS);
        $userSearch->setGeoLocationFromUS($geoLocationFromUS);

        $userSearch->setUserFromUS($this);

        $this->addUserSearch($userSearch);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($userKeywordSetFromUS->isUserFromUSGeoLocationFromusesLoaded()) {
            $userKeywordSetFromUS->initUserFromUSGeoLocationFromuses();
            $userKeywordSetFromUS->getUserFromUSGeoLocationFromuses()->push($this, $geoLocationFromUS);
        } elseif (!$userKeywordSetFromUS->getUserFromUSGeoLocationFromuses()->contains($this, $geoLocationFromUS)) {
            $userKeywordSetFromUS->getUserFromUSGeoLocationFromuses()->push($this, $geoLocationFromUS);
        }

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($geoLocationFromUS->isUserFromUSUserKeywordSetFromusesLoaded()) {
            $geoLocationFromUS->initUserFromUSUserKeywordSetFromuses();
            $geoLocationFromUS->getUserFromUSUserKeywordSetFromuses()->push($this, $userKeywordSetFromUS);
        } elseif (!$geoLocationFromUS->getUserFromUSUserKeywordSetFromuses()->contains($this, $userKeywordSetFromUS)) {
            $geoLocationFromUS->getUserFromUSUserKeywordSetFromuses()->push($this, $userKeywordSetFromUS);
        }

    }

    /**
     * Remove userKeywordSetFromUS, geoLocationFromUS of this object
     * through the user_search cross reference table.
     *
     * @param ChildUserKeywordSet $userKeywordSetFromUS,
     * @param ChildGeoLocation $geoLocationFromUS
     * @return ChildUser The current object (for fluent API support)
     */
    public function removeUserKeywordSetFromUSGeoLocationFromUS(ChildUserKeywordSet $userKeywordSetFromUS, ChildGeoLocation $geoLocationFromUS)
    {
        if ($this->getUserKeywordSetFromUSGeoLocationFromuses()->contains($userKeywordSetFromUS, $geoLocationFromUS)) {
            $userSearch = new ChildUserSearch();
            $userSearch->setUserKeywordSetFromUS($userKeywordSetFromUS);
            if ($userKeywordSetFromUS->isUserFromUSGeoLocationFromusesLoaded()) {
                //remove the back reference if available
                $userKeywordSetFromUS->getUserFromUSGeoLocationFromuses()->removeObject($this, $geoLocationFromUS);
            }

            $userSearch->setGeoLocationFromUS($geoLocationFromUS);
            if ($geoLocationFromUS->isUserFromUSUserKeywordSetFromusesLoaded()) {
                //remove the back reference if available
                $geoLocationFromUS->getUserFromUSUserKeywordSetFromuses()->removeObject($this, $userKeywordSetFromUS);
            }

            $userSearch->setUserFromUS($this);
            $this->removeUserSearch(clone $userSearch);
            $userSearch->clear();

            $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses->remove($this->combinationCollUserKeywordSetFromUSGeoLocationFromuses->search($userKeywordSetFromUS, $geoLocationFromUS));

            if (null === $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion) {
                $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion = clone $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses;
                $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion->clear();
            }

            $this->combinationCollUserKeywordSetFromUSGeoLocationFromusesScheduledForDeletion->push($userKeywordSetFromUS, $geoLocationFromUS);
        }


        return $this;
    }

    /**
     * Clears out the collJobSiteFromUSSRUserSearchFromUSSRAppRunIds collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addJobSiteFromUSSRUserSearchFromUSSRAppRunIds()
     */
    public function clearJobSiteFromUSSRUserSearchFromUSSRAppRunIds()
    {
        $this->collJobSiteFromUSSRUserSearchFromUSSRAppRunIds = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds crossRef collection.
     *
     * By default this just sets the combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds collection to an empty collection (like clearJobSiteFromUSSRUserSearchFromUSSRAppRunIds());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initJobSiteFromUSSRUserSearchFromUSSRAppRunIds()
    {
        $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds = new ObjectCombinationCollection;
        $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsPartial = true;
    }

    /**
     * Checks if the combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds collection is loaded.
     *
     * @return bool
     */
    public function isJobSiteFromUSSRUserSearchFromUSSRAppRunIdsLoaded()
    {
        return null !== $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds;
    }

    /**
     * Returns a new query object pre configured with filters from current object and given arguments to query the database.
     *
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     *
     * @return ChildJobSiteRecordQuery
     */
    public function createJobSiteFromUSSRsQuery(ChildUserSearch $userSearchFromUSSR = null, $appRunId = null, Criteria $criteria = null)
    {
        $criteria = ChildJobSiteRecordQuery::create($criteria)
            ->filterByUserFromUSSR($this);

        $userSearchSiteRunQuery = $criteria->useUserSearchSiteRunQuery();

        if (null !== $userSearchFromUSSR) {
            $userSearchSiteRunQuery->filterByUserSearchFromUSSR($userSearchFromUSSR);
        }

        if (null !== $appRunId) {
            $userSearchSiteRunQuery->filterByAppRunId($appRunId);
        }

        $userSearchSiteRunQuery->endUse();

        return $criteria;
    }

    /**
     * Gets a combined collection of ChildJobSiteRecord, ChildUserSearch objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUser is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCombinationCollection Combination list of ChildJobSiteRecord, ChildUserSearch objects
     */
    public function getJobSiteFromUSSRUserSearchFromUSSRAppRunIds($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsPartial && !$this->isNew();
        if (null === $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds) {
                    $this->initJobSiteFromUSSRUserSearchFromUSSRAppRunIds();
                }
            } else {

                $query = ChildUserSearchSiteRunQuery::create(null, $criteria)
                    ->filterByUserFromUSSR($this)
                    ->joinJobSiteFromUSSR()
                    ->joinUserSearchFromUSSR()
                ;

                $items = $query->find($con);
                $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds = new ObjectCombinationCollection();
                foreach ($items as $item) {
                    $combination = [];

                    $combination[] = $item->getJobSiteFromUSSR();
                    $combination[] = $item->getUserSearchFromUSSR();
                    $combination[] = $item->getAppRunId();
                    $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds[] = $combination;
                }

                if (null !== $criteria) {
                    return $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds;
                }

                if ($partial && $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds as $obj) {
                        if (!call_user_func_array([$combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds, 'contains'], $obj)) {
                            $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds[] = $obj;
                        }
                    }
                }

                $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds = $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds;
                $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsPartial = false;
            }
        }

        return $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds;
    }

    /**
     * Returns a not cached ObjectCollection of ChildJobSiteRecord objects. This will hit always the databases.
     * If you have attached new ChildJobSiteRecord object to this object you need to call `save` first to get
     * the correct return value. Use getJobSiteFromUSSRUserSearchFromUSSRAppRunIds() to get the current internal state.
     *
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return ChildJobSiteRecord[]|ObjectCollection
     */
    public function getJobSiteFromUSSRs(ChildUserSearch $userSearchFromUSSR = null, $appRunId = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createJobSiteFromUSSRsQuery($userSearchFromUSSR, $appRunId, $criteria)->find($con);
    }

    /**
     * Sets a collection of ChildJobSiteRecord, ChildUserSearch combination objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $jobSiteFromUSSRUserSearchFromUSSRAppRunIds A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function setJobSiteFromUSSRUserSearchFromUSSRAppRunIds(Collection $jobSiteFromUSSRUserSearchFromUSSRAppRunIds, ConnectionInterface $con = null)
    {
        $this->clearJobSiteFromUSSRUserSearchFromUSSRAppRunIds();
        $currentJobSiteFromUSSRUserSearchFromUSSRAppRunIds = $this->getJobSiteFromUSSRUserSearchFromUSSRAppRunIds();

        $combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion = $currentJobSiteFromUSSRUserSearchFromUSSRAppRunIds->diff($jobSiteFromUSSRUserSearchFromUSSRAppRunIds);

        foreach ($combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion as $toDelete) {
            call_user_func_array([$this, 'removeJobSiteFromUSSRUserSearchFromUSSRAppRunId'], $toDelete);
        }

        foreach ($jobSiteFromUSSRUserSearchFromUSSRAppRunIds as $jobSiteFromUSSRUserSearchFromUSSRAppRunId) {
            if (!call_user_func_array([$currentJobSiteFromUSSRUserSearchFromUSSRAppRunIds, 'contains'], $jobSiteFromUSSRUserSearchFromUSSRAppRunId)) {
                call_user_func_array([$this, 'doAddJobSiteFromUSSRUserSearchFromUSSRAppRunId'], $jobSiteFromUSSRUserSearchFromUSSRAppRunId);
            }
        }

        $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsPartial = false;
        $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds = $jobSiteFromUSSRUserSearchFromUSSRAppRunIds;

        return $this;
    }

    /**
     * Gets the number of ChildJobSiteRecord, ChildUserSearch combination objects related by a many-to-many relationship
     * to the current object by way of the user_search_site_run cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ChildJobSiteRecord, ChildUserSearch combination objects
     */
    public function countJobSiteFromUSSRUserSearchFromUSSRAppRunIds(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsPartial && !$this->isNew();
        if (null === $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getJobSiteFromUSSRUserSearchFromUSSRAppRunIds());
                }

                $query = ChildUserSearchSiteRunQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByUserFromUSSR($this)
                    ->count($con);
            }
        } else {
            return count($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds);
        }
    }

    /**
     * Returns the not cached count of ChildJobSiteRecord objects. This will hit always the databases.
     * If you have attached new ChildJobSiteRecord object to this object you need to call `save` first to get
     * the correct return value. Use getJobSiteFromUSSRUserSearchFromUSSRAppRunIds() to get the current internal state.
     *
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param string $appRunId
     * @param Criteria $criteria
     * @param ConnectionInterface $con
     *
     * @return integer
     */
    public function countJobSiteFromUSSRs(ChildUserSearch $userSearchFromUSSR = null, $appRunId = null, Criteria $criteria = null, ConnectionInterface $con = null)
    {
        return $this->createJobSiteFromUSSRsQuery($userSearchFromUSSR, $appRunId, $criteria)->count($con);
    }

    /**
     * Associate a ChildJobSiteRecord to this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param string $appRunId
     * @return ChildUser The current object (for fluent API support)
     */
    public function addJobSiteFromUSSR(ChildJobSiteRecord $jobSiteFromUSSR, ChildUserSearch $userSearchFromUSSR, $appRunId)
    {
        if ($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds === null) {
            $this->initJobSiteFromUSSRUserSearchFromUSSRAppRunIds();
        }

        if (!$this->getJobSiteFromUSSRUserSearchFromUSSRAppRunIds()->contains($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds->push($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId);
            $this->doAddJobSiteFromUSSRUserSearchFromUSSRAppRunId($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId);
        }

        return $this;
    }

    /**
     * Associate a ChildUserSearch to this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param string $appRunId
     * @return ChildUser The current object (for fluent API support)
     */
    public function addUserSearchFromUSSR(ChildUserSearch $userSearchFromUSSR, ChildJobSiteRecord $jobSiteFromUSSR, $appRunId)
    {
        if ($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds === null) {
            $this->initJobSiteFromUSSRUserSearchFromUSSRAppRunIds();
        }

        if (!$this->getJobSiteFromUSSRUserSearchFromUSSRAppRunIds()->contains($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId)) {
            // only add it if the **same** object is not already associated
            $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds->push($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId);
            $this->doAddJobSiteFromUSSRUserSearchFromUSSRAppRunId($userSearchFromUSSR, $jobSiteFromUSSR, $appRunId);
        }

        return $this;
    }

    /**
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param string $appRunId
     */
    protected function doAddJobSiteFromUSSRUserSearchFromUSSRAppRunId(ChildJobSiteRecord $jobSiteFromUSSR, ChildUserSearch $userSearchFromUSSR, $appRunId)
    {
        $userSearchSiteRun = new ChildUserSearchSiteRun();

        $userSearchSiteRun->setJobSiteFromUSSR($jobSiteFromUSSR);
        $userSearchSiteRun->setUserSearchFromUSSR($userSearchFromUSSR);
        $userSearchSiteRun->setAppRunId($appRunId);


        $userSearchSiteRun->setUserFromUSSR($this);

        $this->addUserSearchSiteRun($userSearchSiteRun);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($jobSiteFromUSSR->isUserSearchFromUSSRUserFromUSSRAppRunIdsLoaded()) {
            $jobSiteFromUSSR->initUserSearchFromUSSRUserFromUSSRAppRunIds();
            $jobSiteFromUSSR->getUserSearchFromUSSRUserFromUSSRAppRunIds()->push($userSearchFromUSSR, $this, $appRunId);
        } elseif (!$jobSiteFromUSSR->getUserSearchFromUSSRUserFromUSSRAppRunIds()->contains($userSearchFromUSSR, $this, $appRunId)) {
            $jobSiteFromUSSR->getUserSearchFromUSSRUserFromUSSRAppRunIds()->push($userSearchFromUSSR, $this, $appRunId);
        }

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if ($userSearchFromUSSR->isJobSiteFromUSSRUserFromUSSRAppRunIdsLoaded()) {
            $userSearchFromUSSR->initJobSiteFromUSSRUserFromUSSRAppRunIds();
            $userSearchFromUSSR->getJobSiteFromUSSRUserFromUSSRAppRunIds()->push($jobSiteFromUSSR, $this, $appRunId);
        } elseif (!$userSearchFromUSSR->getJobSiteFromUSSRUserFromUSSRAppRunIds()->contains($jobSiteFromUSSR, $this, $appRunId)) {
            $userSearchFromUSSR->getJobSiteFromUSSRUserFromUSSRAppRunIds()->push($jobSiteFromUSSR, $this, $appRunId);
        }

    }

    /**
     * Remove jobSiteFromUSSR, userSearchFromUSSR, appRunId of this object
     * through the user_search_site_run cross reference table.
     *
     * @param ChildJobSiteRecord $jobSiteFromUSSR,
     * @param ChildUserSearch $userSearchFromUSSR,
     * @param string $appRunId
     * @return ChildUser The current object (for fluent API support)
     */
    public function removeJobSiteFromUSSRUserSearchFromUSSRAppRunId(ChildJobSiteRecord $jobSiteFromUSSR, ChildUserSearch $userSearchFromUSSR, $appRunId)
    {
        if ($this->getJobSiteFromUSSRUserSearchFromUSSRAppRunIds()->contains($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId)) {
            $userSearchSiteRun = new ChildUserSearchSiteRun();
            $userSearchSiteRun->setJobSiteFromUSSR($jobSiteFromUSSR);
            if ($jobSiteFromUSSR->isUserSearchFromUSSRUserFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $jobSiteFromUSSR->getUserSearchFromUSSRUserFromUSSRAppRunIds()->removeObject($userSearchFromUSSR, $this, $appRunId);
            }

            $userSearchSiteRun->setUserSearchFromUSSR($userSearchFromUSSR);
            if ($userSearchFromUSSR->isJobSiteFromUSSRUserFromUSSRAppRunIdsLoaded()) {
                //remove the back reference if available
                $userSearchFromUSSR->getJobSiteFromUSSRUserFromUSSRAppRunIds()->removeObject($jobSiteFromUSSR, $this, $appRunId);
            }

            $userSearchSiteRun->setAppRunId($appRunId);
            $userSearchSiteRun->setUserFromUSSR($this);
            $this->removeUserSearchSiteRun(clone $userSearchSiteRun);
            $userSearchSiteRun->clear();

            $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds->remove($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds->search($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId));

            if (null === $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion) {
                $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion = clone $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds;
                $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion->clear();
            }

            $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIdsScheduledForDeletion->push($jobSiteFromUSSR, $userSearchFromUSSR, $appRunId);
        }


        return $this;
    }

    /**
     * Clears out the collJobPostingFromUJMs collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addJobPostingFromUJMs()
     */
    public function clearJobPostingFromUJMs()
    {
        $this->collJobPostingFromUJMs = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Initializes the collJobPostingFromUJMs crossRef collection.
     *
     * By default this just sets the collJobPostingFromUJMs collection to an empty collection (like clearJobPostingFromUJMs());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initJobPostingFromUJMs()
    {
        $collectionClassName = UserJobMatchTableMap::getTableMap()->getCollectionClassName();

        $this->collJobPostingFromUJMs = new $collectionClassName;
        $this->collJobPostingFromUJMsPartial = true;
        $this->collJobPostingFromUJMs->setModel('\JobScooper\DataAccess\JobPosting');
    }

    /**
     * Checks if the collJobPostingFromUJMs collection is loaded.
     *
     * @return bool
     */
    public function isJobPostingFromUJMsLoaded()
    {
        return null !== $this->collJobPostingFromUJMs;
    }

    /**
     * Gets a collection of ChildJobPosting objects related by a many-to-many relationship
     * to the current object by way of the user_job_match cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUser is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildJobPosting[] List of ChildJobPosting objects
     */
    public function getJobPostingFromUJMs(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collJobPostingFromUJMsPartial && !$this->isNew();
        if (null === $this->collJobPostingFromUJMs || null !== $criteria || $partial) {
            if ($this->isNew()) {
                // return empty collection
                if (null === $this->collJobPostingFromUJMs) {
                    $this->initJobPostingFromUJMs();
                }
            } else {

                $query = ChildJobPostingQuery::create(null, $criteria)
                    ->filterByUserFromUJM($this);
                $collJobPostingFromUJMs = $query->find($con);
                if (null !== $criteria) {
                    return $collJobPostingFromUJMs;
                }

                if ($partial && $this->collJobPostingFromUJMs) {
                    //make sure that already added objects gets added to the list of the database.
                    foreach ($this->collJobPostingFromUJMs as $obj) {
                        if (!$collJobPostingFromUJMs->contains($obj)) {
                            $collJobPostingFromUJMs[] = $obj;
                        }
                    }
                }

                $this->collJobPostingFromUJMs = $collJobPostingFromUJMs;
                $this->collJobPostingFromUJMsPartial = false;
            }
        }

        return $this->collJobPostingFromUJMs;
    }

    /**
     * Sets a collection of JobPosting objects related by a many-to-many relationship
     * to the current object by way of the user_job_match cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $jobPostingFromUJMs A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function setJobPostingFromUJMs(Collection $jobPostingFromUJMs, ConnectionInterface $con = null)
    {
        $this->clearJobPostingFromUJMs();
        $currentJobPostingFromUJMs = $this->getJobPostingFromUJMs();

        $jobPostingFromUJMsScheduledForDeletion = $currentJobPostingFromUJMs->diff($jobPostingFromUJMs);

        foreach ($jobPostingFromUJMsScheduledForDeletion as $toDelete) {
            $this->removeJobPostingFromUJM($toDelete);
        }

        foreach ($jobPostingFromUJMs as $jobPostingFromUJM) {
            if (!$currentJobPostingFromUJMs->contains($jobPostingFromUJM)) {
                $this->doAddJobPostingFromUJM($jobPostingFromUJM);
            }
        }

        $this->collJobPostingFromUJMsPartial = false;
        $this->collJobPostingFromUJMs = $jobPostingFromUJMs;

        return $this;
    }

    /**
     * Gets the number of JobPosting objects related by a many-to-many relationship
     * to the current object by way of the user_job_match cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related JobPosting objects
     */
    public function countJobPostingFromUJMs(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collJobPostingFromUJMsPartial && !$this->isNew();
        if (null === $this->collJobPostingFromUJMs || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collJobPostingFromUJMs) {
                return 0;
            } else {

                if ($partial && !$criteria) {
                    return count($this->getJobPostingFromUJMs());
                }

                $query = ChildJobPostingQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByUserFromUJM($this)
                    ->count($con);
            }
        } else {
            return count($this->collJobPostingFromUJMs);
        }
    }

    /**
     * Associate a ChildJobPosting to this object
     * through the user_job_match cross reference table.
     *
     * @param ChildJobPosting $jobPostingFromUJM
     * @return ChildUser The current object (for fluent API support)
     */
    public function addJobPostingFromUJM(ChildJobPosting $jobPostingFromUJM)
    {
        if ($this->collJobPostingFromUJMs === null) {
            $this->initJobPostingFromUJMs();
        }

        if (!$this->getJobPostingFromUJMs()->contains($jobPostingFromUJM)) {
            // only add it if the **same** object is not already associated
            $this->collJobPostingFromUJMs->push($jobPostingFromUJM);
            $this->doAddJobPostingFromUJM($jobPostingFromUJM);
        }

        return $this;
    }

    /**
     *
     * @param ChildJobPosting $jobPostingFromUJM
     */
    protected function doAddJobPostingFromUJM(ChildJobPosting $jobPostingFromUJM)
    {
        $userJobMatch = new ChildUserJobMatch();

        $userJobMatch->setJobPostingFromUJM($jobPostingFromUJM);

        $userJobMatch->setUserFromUJM($this);

        $this->addUserJobMatch($userJobMatch);

        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$jobPostingFromUJM->isUserFromUJMsLoaded()) {
            $jobPostingFromUJM->initUserFromUJMs();
            $jobPostingFromUJM->getUserFromUJMs()->push($this);
        } elseif (!$jobPostingFromUJM->getUserFromUJMs()->contains($this)) {
            $jobPostingFromUJM->getUserFromUJMs()->push($this);
        }

    }

    /**
     * Remove jobPostingFromUJM of this object
     * through the user_job_match cross reference table.
     *
     * @param ChildJobPosting $jobPostingFromUJM
     * @return ChildUser The current object (for fluent API support)
     */
    public function removeJobPostingFromUJM(ChildJobPosting $jobPostingFromUJM)
    {
        if ($this->getJobPostingFromUJMs()->contains($jobPostingFromUJM)) {
            $userJobMatch = new ChildUserJobMatch();
            $userJobMatch->setJobPostingFromUJM($jobPostingFromUJM);
            if ($jobPostingFromUJM->isUserFromUJMsLoaded()) {
                //remove the back reference if available
                $jobPostingFromUJM->getUserFromUJMs()->removeObject($this);
            }

            $userJobMatch->setUserFromUJM($this);
            $this->removeUserJobMatch(clone $userJobMatch);
            $userJobMatch->clear();

            $this->collJobPostingFromUJMs->remove($this->collJobPostingFromUJMs->search($jobPostingFromUJM));

            if (null === $this->jobPostingFromUJMsScheduledForDeletion) {
                $this->jobPostingFromUJMsScheduledForDeletion = clone $this->collJobPostingFromUJMs;
                $this->jobPostingFromUJMsScheduledForDeletion->clear();
            }

            $this->jobPostingFromUJMsScheduledForDeletion->push($jobPostingFromUJM);
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
        $this->user_id = null;
        $this->user_slug = null;
        $this->email_address = null;
        $this->name = null;
        $this->config_file_path = null;
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
            if ($this->collUserKeywordSets) {
                foreach ($this->collUserKeywordSets as $o) {
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
            if ($this->collUserJobMatches) {
                foreach ($this->collUserJobMatches as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->combinationCollUserKeywordSetFromUSGeoLocationFromuses) {
                foreach ($this->combinationCollUserKeywordSetFromUSGeoLocationFromuses as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds) {
                foreach ($this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collJobPostingFromUJMs) {
                foreach ($this->collJobPostingFromUJMs as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collUserKeywordSets = null;
        $this->collUserSearches = null;
        $this->collUserSearchSiteRuns = null;
        $this->collUserJobMatches = null;
        $this->combinationCollUserKeywordSetFromUSGeoLocationFromuses = null;
        $this->combinationCollJobSiteFromUSSRUserSearchFromUSSRAppRunIds = null;
        $this->collJobPostingFromUJMs = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'user_slug' column
     */
    public function __toString()
    {
        return (string) $this->getUserSlug();
    }

    // sluggable behavior

    /**
     * Wrap the setter for slug value
     *
     * @param   string
     * @return  $this|User
     */
    public function setSlug($v)
    {
        return $this->setUserSlug($v);
    }

    /**
     * Wrap the getter for slug value
     *
     * @return  string
     */
    public function getSlug()
    {
        return $this->getUserSlug();
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
        return '' . $this->cleanupSlugPart($this->getEmailAddress()) . '';
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
    protected function makeSlugUnique($slug, $separator = '-', $alreadyExists = false)
    {
        if (!$alreadyExists) {
            $slug2 = $slug;
        } else {
            $slug2 = $slug . $separator;
        }

        $adapter = \Propel\Runtime\Propel::getServiceContainer()->getAdapter('default');
        $col = 'q.UserSlug';
        $compare = $alreadyExists ? $adapter->compareRegex($col, '?') : sprintf('%s = ?', $col);

        $query = \JobScooper\DataAccess\UserQuery::create('q')
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
            ->addDescendingOrderByColumn($adapter->strLength('user_slug'))
            ->addDescendingOrderByColumn('user_slug')
        ->findOne();

        // First duplicate slug
        if (null == $object) {
            return $slug2 . '1';
        }

        $slugNum = substr($object->getUserSlug(), strlen($slug) + 1);
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
