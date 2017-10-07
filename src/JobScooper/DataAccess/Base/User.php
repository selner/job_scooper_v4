<?php

namespace JobScooper\DataAccess\Base;

use \Exception;
use \PDO;
use JobScooper\DataAccess\User as ChildUser;
use JobScooper\DataAccess\UserJobMatch as ChildUserJobMatch;
use JobScooper\DataAccess\UserJobMatchQuery as ChildUserJobMatchQuery;
use JobScooper\DataAccess\UserQuery as ChildUserQuery;
use JobScooper\DataAccess\UserSearchRun as ChildUserSearchRun;
use JobScooper\DataAccess\UserSearchRunQuery as ChildUserSearchRunQuery;
use JobScooper\DataAccess\Map\UserJobMatchTableMap;
use JobScooper\DataAccess\Map\UserSearchRunTableMap;
use JobScooper\DataAccess\Map\UserTableMap;
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
     * The value for the user_slug field.
     *
     * @var        string
     */
    protected $user_slug;

    /**
     * The value for the name field.
     *
     * @var        string
     */
    protected $name;

    /**
     * The value for the email_address field.
     *
     * @var        string
     */
    protected $email_address;

    /**
     * The value for the configuration_file_path field.
     *
     * @var        string
     */
    protected $configuration_file_path;

    /**
     * @var        ObjectCollection|ChildUserJobMatch[] Collection to store aggregation of ChildUserJobMatch objects.
     */
    protected $collUserJobMatches;
    protected $collUserJobMatchesPartial;

    /**
     * @var        ObjectCollection|ChildUserSearchRun[] Collection to store aggregation of ChildUserSearchRun objects.
     */
    protected $collUserSearchRuns;
    protected $collUserSearchRunsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserJobMatch[]
     */
    protected $userJobMatchesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserSearchRun[]
     */
    protected $userSearchRunsScheduledForDeletion = null;

    /**
     * Initializes internal state of JobScooper\DataAccess\Base\User object.
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
     * Get the [user_slug] column value.
     *
     * @return string
     */
    public function getUserSlug()
    {
        return $this->user_slug;
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
     * Get the [email_address] column value.
     *
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->email_address;
    }

    /**
     * Get the [configuration_file_path] column value.
     *
     * @return string
     */
    public function getConfigFilePath()
    {
        return $this->configuration_file_path;
    }

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
     * Set the value of [configuration_file_path] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
     */
    public function setConfigFilePath($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->configuration_file_path !== $v) {
            $this->configuration_file_path = $v;
            $this->modifiedColumns[UserTableMap::COL_CONFIGURATION_FILE_PATH] = true;
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserTableMap::translateFieldName('UserSlug', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_slug = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserTableMap::translateFieldName('Name', TableMap::TYPE_PHPNAME, $indexType)];
            $this->name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : UserTableMap::translateFieldName('EmailAddress', TableMap::TYPE_PHPNAME, $indexType)];
            $this->email_address = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : UserTableMap::translateFieldName('ConfigFilePath', TableMap::TYPE_PHPNAME, $indexType)];
            $this->configuration_file_path = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 4; // 4 = UserTableMap::NUM_HYDRATE_COLUMNS.

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

            $this->collUserJobMatches = null;

            $this->collUserSearchRuns = null;

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
        if ($this->isColumnModified(UserTableMap::COL_USER_SLUG)) {
            $modifiedColumns[':p' . $index++]  = 'user_slug';
        }
        if ($this->isColumnModified(UserTableMap::COL_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'name';
        }
        if ($this->isColumnModified(UserTableMap::COL_EMAIL_ADDRESS)) {
            $modifiedColumns[':p' . $index++]  = 'email_address';
        }
        if ($this->isColumnModified(UserTableMap::COL_CONFIGURATION_FILE_PATH)) {
            $modifiedColumns[':p' . $index++]  = 'configuration_file_path';
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
                    case 'user_slug':
                        $stmt->bindValue($identifier, $this->user_slug, PDO::PARAM_STR);
                        break;
                    case 'name':
                        $stmt->bindValue($identifier, $this->name, PDO::PARAM_STR);
                        break;
                    case 'email_address':
                        $stmt->bindValue($identifier, $this->email_address, PDO::PARAM_STR);
                        break;
                    case 'configuration_file_path':
                        $stmt->bindValue($identifier, $this->configuration_file_path, PDO::PARAM_STR);
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
                return $this->getUserSlug();
                break;
            case 1:
                return $this->getName();
                break;
            case 2:
                return $this->getEmailAddress();
                break;
            case 3:
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
            $keys[0] => $this->getUserSlug(),
            $keys[1] => $this->getName(),
            $keys[2] => $this->getEmailAddress(),
            $keys[3] => $this->getConfigFilePath(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
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
                $this->setUserSlug($value);
                break;
            case 1:
                $this->setName($value);
                break;
            case 2:
                $this->setEmailAddress($value);
                break;
            case 3:
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
            $this->setUserSlug($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setName($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setEmailAddress($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setConfigFilePath($arr[$keys[3]]);
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

        if ($this->isColumnModified(UserTableMap::COL_USER_SLUG)) {
            $criteria->add(UserTableMap::COL_USER_SLUG, $this->user_slug);
        }
        if ($this->isColumnModified(UserTableMap::COL_NAME)) {
            $criteria->add(UserTableMap::COL_NAME, $this->name);
        }
        if ($this->isColumnModified(UserTableMap::COL_EMAIL_ADDRESS)) {
            $criteria->add(UserTableMap::COL_EMAIL_ADDRESS, $this->email_address);
        }
        if ($this->isColumnModified(UserTableMap::COL_CONFIGURATION_FILE_PATH)) {
            $criteria->add(UserTableMap::COL_CONFIGURATION_FILE_PATH, $this->configuration_file_path);
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
        $criteria->add(UserTableMap::COL_USER_SLUG, $this->user_slug);

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
        $validPk = null !== $this->getUserSlug();

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
        return $this->getUserSlug();
    }

    /**
     * Generic method to set the primary key (user_slug column).
     *
     * @param       string $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setUserSlug($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getUserSlug();
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
        $copyObj->setName($this->getName());
        $copyObj->setEmailAddress($this->getEmailAddress());
        $copyObj->setConfigFilePath($this->getConfigFilePath());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getUserJobMatches() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserJobMatch($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getUserSearchRuns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserSearchRun($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

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
        if ('UserJobMatch' == $relationName) {
            $this->initUserJobMatches();
            return;
        }
        if ('UserSearchRun' == $relationName) {
            $this->initUserSearchRuns();
            return;
        }
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
                    ->filterByUser($this)
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


        $this->userJobMatchesScheduledForDeletion = $userJobMatchesToDelete;

        foreach ($userJobMatchesToDelete as $userJobMatchRemoved) {
            $userJobMatchRemoved->setUser(null);
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
                ->filterByUser($this)
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
        $userJobMatch->setUser($this);
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
            $userJobMatch->setUser(null);
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
    public function getUserJobMatchesJoinJobPosting(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserJobMatchQuery::create(null, $criteria);
        $query->joinWith('JobPosting', $joinBehavior);

        return $this->getUserJobMatches($query, $con);
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
     * If this ChildUser is new, it will return
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
                    ->filterByUser($this)
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
     * @return $this|ChildUser The current object (for fluent API support)
     */
    public function setUserSearchRuns(Collection $userSearchRuns, ConnectionInterface $con = null)
    {
        /** @var ChildUserSearchRun[] $userSearchRunsToDelete */
        $userSearchRunsToDelete = $this->getUserSearchRuns(new Criteria(), $con)->diff($userSearchRuns);


        $this->userSearchRunsScheduledForDeletion = $userSearchRunsToDelete;

        foreach ($userSearchRunsToDelete as $userSearchRunRemoved) {
            $userSearchRunRemoved->setUser(null);
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
                ->filterByUser($this)
                ->count($con);
        }

        return count($this->collUserSearchRuns);
    }

    /**
     * Method called to associate a ChildUserSearchRun object to this object
     * through the ChildUserSearchRun foreign key attribute.
     *
     * @param  ChildUserSearchRun $l ChildUserSearchRun
     * @return $this|\JobScooper\DataAccess\User The current object (for fluent API support)
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
        $userSearchRun->setUser($this);
    }

    /**
     * @param  ChildUserSearchRun $userSearchRun The ChildUserSearchRun object to remove.
     * @return $this|ChildUser The current object (for fluent API support)
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
            $userSearchRun->setUser(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this User is new, it will return
     * an empty collection; or if this User has previously
     * been saved, it will retrieve related UserSearchRuns from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in User.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserSearchRun[] List of ChildUserSearchRun objects
     */
    public function getUserSearchRunsJoinLocation(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserSearchRunQuery::create(null, $criteria);
        $query->joinWith('Location', $joinBehavior);

        return $this->getUserSearchRuns($query, $con);
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        $this->user_slug = null;
        $this->name = null;
        $this->email_address = null;
        $this->configuration_file_path = null;
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
            if ($this->collUserJobMatches) {
                foreach ($this->collUserJobMatches as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collUserSearchRuns) {
                foreach ($this->collUserSearchRuns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collUserJobMatches = null;
        $this->collUserSearchRuns = null;
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
