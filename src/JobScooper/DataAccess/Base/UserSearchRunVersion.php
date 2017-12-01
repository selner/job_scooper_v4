<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\UserSearchRun as ChildUserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\DataAccess\UserSearchRunVersionQuery as ChildUserSearchRunVersionQuery;
use JobScooper\DataAccess\Map\UserSearchRunVersionTableMap;
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

/**
 * Base class that represents a row from the 'user_search_run_version' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.DataAccess.Base
 */
abstract class UserSearchRunVersion implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\DataAccess\\Map\\UserSearchRunVersionTableMap';


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
     * The value for the version field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $version;

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
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->run_result = 0;
        $this->version = 0;
    }

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\UserSearchRunVersion object.
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
     * Compares this with another <code>UserSearchRunVersion</code> instance.  If
     * <code>obj</code> is an instance of <code>UserSearchRunVersion</code>, delegates to
     * <code>equals(UserSearchRunVersion)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|UserSearchRunVersion The current object, for fluid interface
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
        $valueSet = UserSearchRunVersionTableMap::getValueSet(UserSearchRunVersionTableMap::COL_RUN_RESULT);
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
     * Get the [version] column value.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the value of [user_search_run_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
     */
    public function setUserSearchRunId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->user_search_run_id !== $v) {
            $this->user_search_run_id = $v;
            $this->modifiedColumns[UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID] = true;
        }

        if ($this->aUserSearchRun !== null && $this->aUserSearchRun->getUserSearchRunId() !== $v) {
            $this->aUserSearchRun = null;
        }

        return $this;
    } // setUserSearchRunId()

    /**
     * Set the value of [search_parameters_data] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
     */
    public function setSearchParametersData($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->search_parameters_data !== $v) {
            $this->search_parameters_data = $v;
            $this->modifiedColumns[UserSearchRunVersionTableMap::COL_SEARCH_PARAMETERS_DATA] = true;
        }

        return $this;
    } // setSearchParametersData()

    /**
     * Set the value of [last_app_run_id] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
     */
    public function setAppRunId($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->last_app_run_id !== $v) {
            $this->last_app_run_id = $v;
            $this->modifiedColumns[UserSearchRunVersionTableMap::COL_LAST_APP_RUN_ID] = true;
        }

        return $this;
    } // setAppRunId()

    /**
     * Set the value of [run_result] column.
     *
     * @param  string $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function setRunResultCode($v)
    {
        if ($v !== null) {
            $valueSet = UserSearchRunVersionTableMap::getValueSet(UserSearchRunVersionTableMap::COL_RUN_RESULT);
            if (!in_array($v, $valueSet)) {
                throw new PropelException(sprintf('Value "%s" is not accepted in this enumerated column', $v));
            }
            $v = array_search($v, $valueSet);
        }

        if ($this->run_result !== $v) {
            $this->run_result = $v;
            $this->modifiedColumns[UserSearchRunVersionTableMap::COL_RUN_RESULT] = true;
        }

        return $this;
    } // setRunResultCode()

    /**
     * Set the value of [run_error_details] column.
     *
     * @param array $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
     */
    public function setRunErrorDetails($v)
    {
        if ($this->run_error_details_unserialized !== $v) {
            $this->run_error_details_unserialized = $v;
            $this->run_error_details = '| ' . implode(' | ', $v) . ' |';
            $this->modifiedColumns[UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS] = true;
        }

        return $this;
    } // setRunErrorDetails()

    /**
     * Adds a value to the [run_error_details] array column value.
     * @param  mixed $value
     *
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
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
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
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
     * Set the value of [version] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
     */
    public function setVersion($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->version !== $v) {
            $this->version = $v;
            $this->modifiedColumns[UserSearchRunVersionTableMap::COL_VERSION] = true;
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
            if ($this->run_result !== 0) {
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserSearchRunVersionTableMap::translateFieldName('UserSearchRunId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_search_run_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserSearchRunVersionTableMap::translateFieldName('SearchParametersData', TableMap::TYPE_PHPNAME, $indexType)];
            $this->search_parameters_data = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserSearchRunVersionTableMap::translateFieldName('AppRunId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->last_app_run_id = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserSearchRunVersionTableMap::translateFieldName('RunResultCode', TableMap::TYPE_PHPNAME, $indexType)];
            $this->run_result = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : UserSearchRunVersionTableMap::translateFieldName('RunErrorDetails', TableMap::TYPE_PHPNAME, $indexType)];
            $this->run_error_details = $col;
            $this->run_error_details_unserialized = null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : UserSearchRunVersionTableMap::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)];
            $this->version = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 6; // 6 = UserSearchRunVersionTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\DataAccess\\UserSearchRunVersion'), 0, $e);
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
        if ($this->aUserSearchRun !== null && $this->user_search_run_id !== $this->aUserSearchRun->getUserSearchRunId()) {
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
            $con = Propel::getServiceContainer()->getReadConnection(UserSearchRunVersionTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildUserSearchRunVersionQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

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
     * @see UserSearchRunVersion::setDeleted()
     * @see UserSearchRunVersion::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunVersionTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildUserSearchRunVersionQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(UserSearchRunVersionTableMap::DATABASE_NAME);
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
                UserSearchRunVersionTableMap::addInstanceToPool($this);
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
                } else {
                    $affectedRows += $this->doUpdate($con);
                }
                $this->resetModified();
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


         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'user_search_run_id';
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_SEARCH_PARAMETERS_DATA)) {
            $modifiedColumns[':p' . $index++]  = 'search_parameters_data';
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_LAST_APP_RUN_ID)) {
            $modifiedColumns[':p' . $index++]  = 'last_app_run_id';
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_RUN_RESULT)) {
            $modifiedColumns[':p' . $index++]  = 'run_result';
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS)) {
            $modifiedColumns[':p' . $index++]  = 'run_error_details';
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_VERSION)) {
            $modifiedColumns[':p' . $index++]  = 'version';
        }

        $sql = sprintf(
            'INSERT INTO user_search_run_version (%s) VALUES (%s)',
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
        $pos = UserSearchRunVersionTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getSearchParametersData();
                break;
            case 2:
                return $this->getAppRunId();
                break;
            case 3:
                return $this->getRunResultCode();
                break;
            case 4:
                return $this->getRunErrorDetails();
                break;
            case 5:
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

        if (isset($alreadyDumpedObjects['UserSearchRunVersion'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['UserSearchRunVersion'][$this->hashCode()] = true;
        $keys = UserSearchRunVersionTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getUserSearchRunId(),
            $keys[1] => $this->getSearchParametersData(),
            $keys[2] => $this->getAppRunId(),
            $keys[3] => $this->getRunResultCode(),
            $keys[4] => $this->getRunErrorDetails(),
            $keys[5] => $this->getVersion(),
        );
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
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserSearchRunVersionTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setUserSearchRunId($value);
                break;
            case 1:
                $this->setSearchParametersData($value);
                break;
            case 2:
                $this->setAppRunId($value);
                break;
            case 3:
                $valueSet = UserSearchRunVersionTableMap::getValueSet(UserSearchRunVersionTableMap::COL_RUN_RESULT);
                if (isset($valueSet[$value])) {
                    $value = $valueSet[$value];
                }
                $this->setRunResultCode($value);
                break;
            case 4:
                if (!is_array($value)) {
                    $v = trim(substr($value, 2, -2));
                    $value = $v ? explode(' | ', $v) : array();
                }
                $this->setRunErrorDetails($value);
                break;
            case 5:
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
        $keys = UserSearchRunVersionTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setUserSearchRunId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setSearchParametersData($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setAppRunId($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setRunResultCode($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setRunErrorDetails($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setVersion($arr[$keys[5]]);
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
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object, for fluid interface
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
        $criteria = new Criteria(UserSearchRunVersionTableMap::DATABASE_NAME);

        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID)) {
            $criteria->add(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $this->user_search_run_id);
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_SEARCH_PARAMETERS_DATA)) {
            $criteria->add(UserSearchRunVersionTableMap::COL_SEARCH_PARAMETERS_DATA, $this->search_parameters_data);
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_LAST_APP_RUN_ID)) {
            $criteria->add(UserSearchRunVersionTableMap::COL_LAST_APP_RUN_ID, $this->last_app_run_id);
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_RUN_RESULT)) {
            $criteria->add(UserSearchRunVersionTableMap::COL_RUN_RESULT, $this->run_result);
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS)) {
            $criteria->add(UserSearchRunVersionTableMap::COL_RUN_ERROR_DETAILS, $this->run_error_details);
        }
        if ($this->isColumnModified(UserSearchRunVersionTableMap::COL_VERSION)) {
            $criteria->add(UserSearchRunVersionTableMap::COL_VERSION, $this->version);
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
        $criteria = ChildUserSearchRunVersionQuery::create();
        $criteria->add(UserSearchRunVersionTableMap::COL_USER_SEARCH_RUN_ID, $this->user_search_run_id);
        $criteria->add(UserSearchRunVersionTableMap::COL_VERSION, $this->version);

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
        $validPk = null !== $this->getUserSearchRunId() &&
            null !== $this->getVersion();

        $validPrimaryKeyFKs = 1;
        $primaryKeyFKs = [];

        //relation user_search_run_version_fk_9da929 to table user_search_run
        if ($this->aUserSearchRun && $hash = spl_object_hash($this->aUserSearchRun)) {
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
        $pks[0] = $this->getUserSearchRunId();
        $pks[1] = $this->getVersion();

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
        $this->setUserSearchRunId($keys[0]);
        $this->setVersion($keys[1]);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return (null === $this->getUserSearchRunId()) && (null === $this->getVersion());
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\DataAccess\UserSearchRunVersion (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setUserSearchRunId($this->getUserSearchRunId());
        $copyObj->setSearchParametersData($this->getSearchParametersData());
        $copyObj->setAppRunId($this->getAppRunId());
        $copyObj->setRunResultCode($this->getRunResultCode());
        $copyObj->setRunErrorDetails($this->getRunErrorDetails());
        $copyObj->setVersion($this->getVersion());
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
     * @return \JobScooper\DataAccess\UserSearchRunVersion Clone of current object.
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
     * @return $this|\JobScooper\DataAccess\UserSearchRunVersion The current object (for fluent API support)
     * @throws PropelException
     */
    public function setUserSearchRun(ChildUserSearchRun $v = null)
    {
        if ($v === null) {
            $this->setUserSearchRunId(NULL);
        } else {
            $this->setUserSearchRunId($v->getUserSearchRunId());
        }

        $this->aUserSearchRun = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildUserSearchRun object, it will not be re-added.
        if ($v !== null) {
            $v->addUserSearchRunVersion($this);
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
        if ($this->aUserSearchRun === null && ($this->user_search_run_id != 0)) {
            $this->aUserSearchRun = ChildUserSearchRunQuery::create()->findPk($this->user_search_run_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aUserSearchRun->addUserSearchRunVersions($this);
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
            $this->aUserSearchRun->removeUserSearchRunVersion($this);
        }
        $this->user_search_run_id = null;
        $this->search_parameters_data = null;
        $this->last_app_run_id = null;
        $this->run_result = null;
        $this->run_error_details = null;
        $this->run_error_details_unserialized = null;
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
        } // if ($deep)

        $this->aUserSearchRun = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(UserSearchRunVersionTableMap::DEFAULT_STRING_FORMAT);
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
