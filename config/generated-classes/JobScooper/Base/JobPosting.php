<?php

namespace JobScooper\Base;

use \DateTime;
use \Exception;
use \PDO;
use JobScooper\JobPosting as ChildJobPosting;
use JobScooper\JobPostingQuery as ChildJobPostingQuery;
use JobScooper\UserJobMatch as ChildUserJobMatch;
use JobScooper\UserJobMatchQuery as ChildUserJobMatchQuery;
use JobScooper\Map\JobPostingTableMap;
use JobScooper\Map\UserJobMatchTableMap;
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
 * Base class that represents a row from the 'jobposting' table.
 *
 *
 *
 * @package    propel.generator.JobScooper.Base
 */
abstract class JobPosting implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\JobScooper\\Map\\JobPostingTableMap';


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
     * The value for the jobposting_id field.
     *
     * @var        int
     */
    protected $jobposting_id;

    /**
     * The value for the jobsite field.
     *
     * @var        string
     */
    protected $jobsite;

    /**
     * The value for the jobsite_post_id field.
     *
     * @var        string
     */
    protected $jobsite_post_id;

    /**
     * The value for the title field.
     *
     * @var        string
     */
    protected $title;

    /**
     * The value for the title_tokens field.
     *
     * @var        string
     */
    protected $title_tokens;

    /**
     * The value for the url field.
     *
     * @var        string
     */
    protected $url;

    /**
     * The value for the company field.
     *
     * @var        string
     */
    protected $company;

    /**
     * The value for the location field.
     *
     * @var        string
     */
    protected $location;

    /**
     * The value for the employment_type field.
     *
     * @var        string
     */
    protected $employment_type;

    /**
     * The value for the department field.
     *
     * @var        string
     */
    protected $department;

    /**
     * The value for the category field.
     *
     * @var        string
     */
    protected $category;

    /**
     * The value for the last_updated_at field.
     *
     * @var        DateTime
     */
    protected $last_updated_at;

    /**
     * The value for the job_posted_date field.
     *
     * @var        DateTime
     */
    protected $job_posted_date;

    /**
     * The value for the first_seen_at field.
     *
     * @var        DateTime
     */
    protected $first_seen_at;

    /**
     * The value for the post_removed_at field.
     *
     * @var        DateTime
     */
    protected $post_removed_at;

    /**
     * The value for the key_site_and_post_id field.
     *
     * @var        string
     */
    protected $key_site_and_post_id;

    /**
     * The value for the key_company_and_title field.
     *
     * @var        string
     */
    protected $key_company_and_title;

    /**
     * The value for the title_linked field.
     *
     * @var        string
     */
    protected $title_linked;

    /**
     * @var        ObjectCollection|ChildUserJobMatch[] Collection to store aggregation of ChildUserJobMatch objects.
     */
    protected $collUserJobMatches;
    protected $collUserJobMatchesPartial;

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
     * Initializes internal state of JobScooper\Base\JobPosting object.
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
     * Compares this with another <code>JobPosting</code> instance.  If
     * <code>obj</code> is an instance of <code>JobPosting</code>, delegates to
     * <code>equals(JobPosting)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|JobPosting The current object, for fluid interface
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
     * Get the [jobposting_id] column value.
     *
     * @return int
     */
    public function getJobPostingId()
    {
        return $this->jobposting_id;
    }

    /**
     * Get the [jobsite] column value.
     *
     * @return string
     */
    public function getJobSite()
    {
        return $this->jobsite;
    }

    /**
     * Get the [jobsite_post_id] column value.
     *
     * @return string
     */
    public function getJobSitePostID()
    {
        return $this->jobsite_post_id;
    }

    /**
     * Get the [title] column value.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the [title_tokens] column value.
     *
     * @return string
     */
    public function getTitleTokens()
    {
        return $this->title_tokens;
    }

    /**
     * Get the [url] column value.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the [company] column value.
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Get the [location] column value.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Get the [employment_type] column value.
     *
     * @return string
     */
    public function getEmploymentType()
    {
        return $this->employment_type;
    }

    /**
     * Get the [department] column value.
     *
     * @return string
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * Get the [category] column value.
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Get the [optionally formatted] temporal [last_updated_at] column value.
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
            return $this->last_updated_at;
        } else {
            return $this->last_updated_at instanceof \DateTimeInterface ? $this->last_updated_at->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [job_posted_date] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getPostedAt($format = NULL)
    {
        if ($format === null) {
            return $this->job_posted_date;
        } else {
            return $this->job_posted_date instanceof \DateTimeInterface ? $this->job_posted_date->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [first_seen_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getFirstSeenAt($format = NULL)
    {
        if ($format === null) {
            return $this->first_seen_at;
        } else {
            return $this->first_seen_at instanceof \DateTimeInterface ? $this->first_seen_at->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [post_removed_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getRemovedAt($format = NULL)
    {
        if ($format === null) {
            return $this->post_removed_at;
        } else {
            return $this->post_removed_at instanceof \DateTimeInterface ? $this->post_removed_at->format($format) : null;
        }
    }

    /**
     * Get the [key_site_and_post_id] column value.
     *
     * @return string
     */
    public function getKeySiteAndPostID()
    {
        return $this->key_site_and_post_id;
    }

    /**
     * Get the [key_company_and_title] column value.
     *
     * @return string
     */
    public function getKeyCompanyAndTitle()
    {
        return $this->key_company_and_title;
    }

    /**
     * Get the [title_linked] column value.
     *
     * @return string
     */
    public function getJobTitleLinked()
    {
        return $this->title_linked;
    }

    /**
     * Set the value of [jobposting_id] column.
     *
     * @param int $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setJobPostingId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->jobposting_id !== $v) {
            $this->jobposting_id = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_JOBPOSTING_ID] = true;
        }

        return $this;
    } // setJobPostingId()

    /**
     * Set the value of [jobsite] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setJobSite($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->jobsite !== $v) {
            $this->jobsite = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_JOBSITE] = true;
        }

        return $this;
    } // setJobSite()

    /**
     * Set the value of [jobsite_post_id] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setJobSitePostID($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->jobsite_post_id !== $v) {
            $this->jobsite_post_id = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_JOBSITE_POST_ID] = true;
        }

        return $this;
    } // setJobSitePostID()

    /**
     * Set the value of [title] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setTitle($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->title !== $v) {
            $this->title = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_TITLE] = true;
        }

        return $this;
    } // setTitle()

    /**
     * Set the value of [title_tokens] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setTitleTokens($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->title_tokens !== $v) {
            $this->title_tokens = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_TITLE_TOKENS] = true;
        }

        return $this;
    } // setTitleTokens()

    /**
     * Set the value of [url] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setUrl($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->url !== $v) {
            $this->url = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_URL] = true;
        }

        return $this;
    } // setUrl()

    /**
     * Set the value of [company] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setCompany($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->company !== $v) {
            $this->company = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_COMPANY] = true;
        }

        return $this;
    } // setCompany()

    /**
     * Set the value of [location] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setLocation($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->location !== $v) {
            $this->location = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_LOCATION] = true;
        }

        return $this;
    } // setLocation()

    /**
     * Set the value of [employment_type] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setEmploymentType($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->employment_type !== $v) {
            $this->employment_type = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_EMPLOYMENT_TYPE] = true;
        }

        return $this;
    } // setEmploymentType()

    /**
     * Set the value of [department] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setDepartment($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->department !== $v) {
            $this->department = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_DEPARTMENT] = true;
        }

        return $this;
    } // setDepartment()

    /**
     * Set the value of [category] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setCategory($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->category !== $v) {
            $this->category = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_CATEGORY] = true;
        }

        return $this;
    } // setCategory()

    /**
     * Sets the value of [last_updated_at] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->last_updated_at !== null || $dt !== null) {
            if ($this->last_updated_at === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->last_updated_at->format("Y-m-d H:i:s.u")) {
                $this->last_updated_at = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobPostingTableMap::COL_LAST_UPDATED_AT] = true;
            }
        } // if either are not null

        return $this;
    } // setUpdatedAt()

    /**
     * Sets the value of [job_posted_date] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setPostedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->job_posted_date !== null || $dt !== null) {
            if ($this->job_posted_date === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->job_posted_date->format("Y-m-d H:i:s.u")) {
                $this->job_posted_date = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobPostingTableMap::COL_JOB_POSTED_DATE] = true;
            }
        } // if either are not null

        return $this;
    } // setPostedAt()

    /**
     * Sets the value of [first_seen_at] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setFirstSeenAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->first_seen_at !== null || $dt !== null) {
            if ($this->first_seen_at === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->first_seen_at->format("Y-m-d H:i:s.u")) {
                $this->first_seen_at = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobPostingTableMap::COL_FIRST_SEEN_AT] = true;
            }
        } // if either are not null

        return $this;
    } // setFirstSeenAt()

    /**
     * Sets the value of [post_removed_at] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setRemovedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->post_removed_at !== null || $dt !== null) {
            if ($this->post_removed_at === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->post_removed_at->format("Y-m-d H:i:s.u")) {
                $this->post_removed_at = $dt === null ? null : clone $dt;
                $this->modifiedColumns[JobPostingTableMap::COL_POST_REMOVED_AT] = true;
            }
        } // if either are not null

        return $this;
    } // setRemovedAt()

    /**
     * Set the value of [key_site_and_post_id] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setKeySiteAndPostID($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->key_site_and_post_id !== $v) {
            $this->key_site_and_post_id = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_KEY_SITE_AND_POST_ID] = true;
        }

        return $this;
    } // setKeySiteAndPostID()

    /**
     * Set the value of [key_company_and_title] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setKeyCompanyAndTitle($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->key_company_and_title !== $v) {
            $this->key_company_and_title = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE] = true;
        }

        return $this;
    } // setKeyCompanyAndTitle()

    /**
     * Set the value of [title_linked] column.
     *
     * @param string $v new value
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
     */
    public function setJobTitleLinked($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->title_linked !== $v) {
            $this->title_linked = $v;
            $this->modifiedColumns[JobPostingTableMap::COL_TITLE_LINKED] = true;
        }

        return $this;
    } // setJobTitleLinked()

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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : JobPostingTableMap::translateFieldName('JobPostingId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobposting_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : JobPostingTableMap::translateFieldName('JobSite', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobsite = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : JobPostingTableMap::translateFieldName('JobSitePostID', TableMap::TYPE_PHPNAME, $indexType)];
            $this->jobsite_post_id = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : JobPostingTableMap::translateFieldName('Title', TableMap::TYPE_PHPNAME, $indexType)];
            $this->title = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : JobPostingTableMap::translateFieldName('TitleTokens', TableMap::TYPE_PHPNAME, $indexType)];
            $this->title_tokens = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : JobPostingTableMap::translateFieldName('Url', TableMap::TYPE_PHPNAME, $indexType)];
            $this->url = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : JobPostingTableMap::translateFieldName('Company', TableMap::TYPE_PHPNAME, $indexType)];
            $this->company = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : JobPostingTableMap::translateFieldName('Location', TableMap::TYPE_PHPNAME, $indexType)];
            $this->location = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : JobPostingTableMap::translateFieldName('EmploymentType', TableMap::TYPE_PHPNAME, $indexType)];
            $this->employment_type = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : JobPostingTableMap::translateFieldName('Department', TableMap::TYPE_PHPNAME, $indexType)];
            $this->department = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : JobPostingTableMap::translateFieldName('Category', TableMap::TYPE_PHPNAME, $indexType)];
            $this->category = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 11 + $startcol : JobPostingTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->last_updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 12 + $startcol : JobPostingTableMap::translateFieldName('PostedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->job_posted_date = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 13 + $startcol : JobPostingTableMap::translateFieldName('FirstSeenAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->first_seen_at = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 14 + $startcol : JobPostingTableMap::translateFieldName('RemovedAt', TableMap::TYPE_PHPNAME, $indexType)];
            $this->post_removed_at = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 15 + $startcol : JobPostingTableMap::translateFieldName('KeySiteAndPostID', TableMap::TYPE_PHPNAME, $indexType)];
            $this->key_site_and_post_id = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 16 + $startcol : JobPostingTableMap::translateFieldName('KeyCompanyAndTitle', TableMap::TYPE_PHPNAME, $indexType)];
            $this->key_company_and_title = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 17 + $startcol : JobPostingTableMap::translateFieldName('JobTitleLinked', TableMap::TYPE_PHPNAME, $indexType)];
            $this->title_linked = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 18; // 18 = JobPostingTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\JobScooper\\JobPosting'), 0, $e);
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
            $con = Propel::getServiceContainer()->getReadConnection(JobPostingTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildJobPostingQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collUserJobMatches = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see JobPosting::setDeleted()
     * @see JobPosting::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(JobPostingTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildJobPostingQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(JobPostingTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior

                if (!$this->isColumnModified(JobPostingTableMap::COL_FIRST_SEEN_AT)) {
                    $this->setFirstSeenAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
                if (!$this->isColumnModified(JobPostingTableMap::COL_LAST_UPDATED_AT)) {
                    $this->setUpdatedAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(JobPostingTableMap::COL_LAST_UPDATED_AT)) {
                    $this->setUpdatedAt(\Propel\Runtime\Util\PropelDateTime::createHighPrecision());
                }
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                JobPostingTableMap::addInstanceToPool($this);
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
                    \JobScooper\UserJobMatchQuery::create()
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

        $this->modifiedColumns[JobPostingTableMap::COL_JOBPOSTING_ID] = true;
        if (null !== $this->jobposting_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . JobPostingTableMap::COL_JOBPOSTING_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(JobPostingTableMap::COL_JOBPOSTING_ID)) {
            $modifiedColumns[':p' . $index++]  = 'jobposting_id';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_JOBSITE)) {
            $modifiedColumns[':p' . $index++]  = 'jobsite';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_JOBSITE_POST_ID)) {
            $modifiedColumns[':p' . $index++]  = 'jobsite_post_id';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_TITLE)) {
            $modifiedColumns[':p' . $index++]  = 'title';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_TITLE_TOKENS)) {
            $modifiedColumns[':p' . $index++]  = 'title_tokens';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_URL)) {
            $modifiedColumns[':p' . $index++]  = 'url';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_COMPANY)) {
            $modifiedColumns[':p' . $index++]  = 'company';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_LOCATION)) {
            $modifiedColumns[':p' . $index++]  = 'location';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_EMPLOYMENT_TYPE)) {
            $modifiedColumns[':p' . $index++]  = 'employment_type';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_DEPARTMENT)) {
            $modifiedColumns[':p' . $index++]  = 'department';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_CATEGORY)) {
            $modifiedColumns[':p' . $index++]  = 'category';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_LAST_UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'last_updated_at';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_JOB_POSTED_DATE)) {
            $modifiedColumns[':p' . $index++]  = 'job_posted_date';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_FIRST_SEEN_AT)) {
            $modifiedColumns[':p' . $index++]  = 'first_seen_at';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_POST_REMOVED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'post_removed_at';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_KEY_SITE_AND_POST_ID)) {
            $modifiedColumns[':p' . $index++]  = 'key_site_and_post_id';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE)) {
            $modifiedColumns[':p' . $index++]  = 'key_company_and_title';
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_TITLE_LINKED)) {
            $modifiedColumns[':p' . $index++]  = 'title_linked';
        }

        $sql = sprintf(
            'INSERT INTO jobposting (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'jobposting_id':
                        $stmt->bindValue($identifier, $this->jobposting_id, PDO::PARAM_INT);
                        break;
                    case 'jobsite':
                        $stmt->bindValue($identifier, $this->jobsite, PDO::PARAM_STR);
                        break;
                    case 'jobsite_post_id':
                        $stmt->bindValue($identifier, $this->jobsite_post_id, PDO::PARAM_STR);
                        break;
                    case 'title':
                        $stmt->bindValue($identifier, $this->title, PDO::PARAM_STR);
                        break;
                    case 'title_tokens':
                        $stmt->bindValue($identifier, $this->title_tokens, PDO::PARAM_STR);
                        break;
                    case 'url':
                        $stmt->bindValue($identifier, $this->url, PDO::PARAM_STR);
                        break;
                    case 'company':
                        $stmt->bindValue($identifier, $this->company, PDO::PARAM_STR);
                        break;
                    case 'location':
                        $stmt->bindValue($identifier, $this->location, PDO::PARAM_STR);
                        break;
                    case 'employment_type':
                        $stmt->bindValue($identifier, $this->employment_type, PDO::PARAM_STR);
                        break;
                    case 'department':
                        $stmt->bindValue($identifier, $this->department, PDO::PARAM_STR);
                        break;
                    case 'category':
                        $stmt->bindValue($identifier, $this->category, PDO::PARAM_STR);
                        break;
                    case 'last_updated_at':
                        $stmt->bindValue($identifier, $this->last_updated_at ? $this->last_updated_at->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'job_posted_date':
                        $stmt->bindValue($identifier, $this->job_posted_date ? $this->job_posted_date->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'first_seen_at':
                        $stmt->bindValue($identifier, $this->first_seen_at ? $this->first_seen_at->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'post_removed_at':
                        $stmt->bindValue($identifier, $this->post_removed_at ? $this->post_removed_at->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'key_site_and_post_id':
                        $stmt->bindValue($identifier, $this->key_site_and_post_id, PDO::PARAM_STR);
                        break;
                    case 'key_company_and_title':
                        $stmt->bindValue($identifier, $this->key_company_and_title, PDO::PARAM_STR);
                        break;
                    case 'title_linked':
                        $stmt->bindValue($identifier, $this->title_linked, PDO::PARAM_STR);
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
        $this->setJobPostingId($pk);

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
        $pos = JobPostingTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getJobPostingId();
                break;
            case 1:
                return $this->getJobSite();
                break;
            case 2:
                return $this->getJobSitePostID();
                break;
            case 3:
                return $this->getTitle();
                break;
            case 4:
                return $this->getTitleTokens();
                break;
            case 5:
                return $this->getUrl();
                break;
            case 6:
                return $this->getCompany();
                break;
            case 7:
                return $this->getLocation();
                break;
            case 8:
                return $this->getEmploymentType();
                break;
            case 9:
                return $this->getDepartment();
                break;
            case 10:
                return $this->getCategory();
                break;
            case 11:
                return $this->getUpdatedAt();
                break;
            case 12:
                return $this->getPostedAt();
                break;
            case 13:
                return $this->getFirstSeenAt();
                break;
            case 14:
                return $this->getRemovedAt();
                break;
            case 15:
                return $this->getKeySiteAndPostID();
                break;
            case 16:
                return $this->getKeyCompanyAndTitle();
                break;
            case 17:
                return $this->getJobTitleLinked();
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

        if (isset($alreadyDumpedObjects['JobPosting'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['JobPosting'][$this->hashCode()] = true;
        $keys = JobPostingTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getJobPostingId(),
            $keys[1] => $this->getJobSite(),
            $keys[2] => $this->getJobSitePostID(),
            $keys[3] => $this->getTitle(),
            $keys[4] => $this->getTitleTokens(),
            $keys[5] => $this->getUrl(),
            $keys[6] => $this->getCompany(),
            $keys[7] => $this->getLocation(),
            $keys[8] => $this->getEmploymentType(),
            $keys[9] => $this->getDepartment(),
            $keys[10] => $this->getCategory(),
            $keys[11] => $this->getUpdatedAt(),
            $keys[12] => $this->getPostedAt(),
            $keys[13] => $this->getFirstSeenAt(),
            $keys[14] => $this->getRemovedAt(),
            $keys[15] => $this->getKeySiteAndPostID(),
            $keys[16] => $this->getKeyCompanyAndTitle(),
            $keys[17] => $this->getJobTitleLinked(),
        );
        if ($result[$keys[11]] instanceof \DateTimeInterface) {
            $result[$keys[11]] = $result[$keys[11]]->format('c');
        }

        if ($result[$keys[12]] instanceof \DateTimeInterface) {
            $result[$keys[12]] = $result[$keys[12]]->format('c');
        }

        if ($result[$keys[13]] instanceof \DateTimeInterface) {
            $result[$keys[13]] = $result[$keys[13]]->format('c');
        }

        if ($result[$keys[14]] instanceof \DateTimeInterface) {
            $result[$keys[14]] = $result[$keys[14]]->format('c');
        }

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
     * @return $this|\JobScooper\JobPosting
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = JobPostingTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\JobScooper\JobPosting
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setJobPostingId($value);
                break;
            case 1:
                $this->setJobSite($value);
                break;
            case 2:
                $this->setJobSitePostID($value);
                break;
            case 3:
                $this->setTitle($value);
                break;
            case 4:
                $this->setTitleTokens($value);
                break;
            case 5:
                $this->setUrl($value);
                break;
            case 6:
                $this->setCompany($value);
                break;
            case 7:
                $this->setLocation($value);
                break;
            case 8:
                $this->setEmploymentType($value);
                break;
            case 9:
                $this->setDepartment($value);
                break;
            case 10:
                $this->setCategory($value);
                break;
            case 11:
                $this->setUpdatedAt($value);
                break;
            case 12:
                $this->setPostedAt($value);
                break;
            case 13:
                $this->setFirstSeenAt($value);
                break;
            case 14:
                $this->setRemovedAt($value);
                break;
            case 15:
                $this->setKeySiteAndPostID($value);
                break;
            case 16:
                $this->setKeyCompanyAndTitle($value);
                break;
            case 17:
                $this->setJobTitleLinked($value);
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
        $keys = JobPostingTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setJobPostingId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setJobSite($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setJobSitePostID($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setTitle($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setTitleTokens($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setUrl($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setCompany($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setLocation($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setEmploymentType($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setDepartment($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setCategory($arr[$keys[10]]);
        }
        if (array_key_exists($keys[11], $arr)) {
            $this->setUpdatedAt($arr[$keys[11]]);
        }
        if (array_key_exists($keys[12], $arr)) {
            $this->setPostedAt($arr[$keys[12]]);
        }
        if (array_key_exists($keys[13], $arr)) {
            $this->setFirstSeenAt($arr[$keys[13]]);
        }
        if (array_key_exists($keys[14], $arr)) {
            $this->setRemovedAt($arr[$keys[14]]);
        }
        if (array_key_exists($keys[15], $arr)) {
            $this->setKeySiteAndPostID($arr[$keys[15]]);
        }
        if (array_key_exists($keys[16], $arr)) {
            $this->setKeyCompanyAndTitle($arr[$keys[16]]);
        }
        if (array_key_exists($keys[17], $arr)) {
            $this->setJobTitleLinked($arr[$keys[17]]);
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
     * @return $this|\JobScooper\JobPosting The current object, for fluid interface
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
        $criteria = new Criteria(JobPostingTableMap::DATABASE_NAME);

        if ($this->isColumnModified(JobPostingTableMap::COL_JOBPOSTING_ID)) {
            $criteria->add(JobPostingTableMap::COL_JOBPOSTING_ID, $this->jobposting_id);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_JOBSITE)) {
            $criteria->add(JobPostingTableMap::COL_JOBSITE, $this->jobsite);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_JOBSITE_POST_ID)) {
            $criteria->add(JobPostingTableMap::COL_JOBSITE_POST_ID, $this->jobsite_post_id);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_TITLE)) {
            $criteria->add(JobPostingTableMap::COL_TITLE, $this->title);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_TITLE_TOKENS)) {
            $criteria->add(JobPostingTableMap::COL_TITLE_TOKENS, $this->title_tokens);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_URL)) {
            $criteria->add(JobPostingTableMap::COL_URL, $this->url);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_COMPANY)) {
            $criteria->add(JobPostingTableMap::COL_COMPANY, $this->company);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_LOCATION)) {
            $criteria->add(JobPostingTableMap::COL_LOCATION, $this->location);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_EMPLOYMENT_TYPE)) {
            $criteria->add(JobPostingTableMap::COL_EMPLOYMENT_TYPE, $this->employment_type);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_DEPARTMENT)) {
            $criteria->add(JobPostingTableMap::COL_DEPARTMENT, $this->department);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_CATEGORY)) {
            $criteria->add(JobPostingTableMap::COL_CATEGORY, $this->category);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_LAST_UPDATED_AT)) {
            $criteria->add(JobPostingTableMap::COL_LAST_UPDATED_AT, $this->last_updated_at);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_JOB_POSTED_DATE)) {
            $criteria->add(JobPostingTableMap::COL_JOB_POSTED_DATE, $this->job_posted_date);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_FIRST_SEEN_AT)) {
            $criteria->add(JobPostingTableMap::COL_FIRST_SEEN_AT, $this->first_seen_at);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_POST_REMOVED_AT)) {
            $criteria->add(JobPostingTableMap::COL_POST_REMOVED_AT, $this->post_removed_at);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_KEY_SITE_AND_POST_ID)) {
            $criteria->add(JobPostingTableMap::COL_KEY_SITE_AND_POST_ID, $this->key_site_and_post_id);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE)) {
            $criteria->add(JobPostingTableMap::COL_KEY_COMPANY_AND_TITLE, $this->key_company_and_title);
        }
        if ($this->isColumnModified(JobPostingTableMap::COL_TITLE_LINKED)) {
            $criteria->add(JobPostingTableMap::COL_TITLE_LINKED, $this->title_linked);
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
        $criteria = ChildJobPostingQuery::create();
        $criteria->add(JobPostingTableMap::COL_JOBPOSTING_ID, $this->jobposting_id);

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
        $validPk = null !== $this->getJobPostingId();

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
        return $this->getJobPostingId();
    }

    /**
     * Generic method to set the primary key (jobposting_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setJobPostingId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getJobPostingId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \JobScooper\JobPosting (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setJobSite($this->getJobSite());
        $copyObj->setJobSitePostID($this->getJobSitePostID());
        $copyObj->setTitle($this->getTitle());
        $copyObj->setTitleTokens($this->getTitleTokens());
        $copyObj->setUrl($this->getUrl());
        $copyObj->setCompany($this->getCompany());
        $copyObj->setLocation($this->getLocation());
        $copyObj->setEmploymentType($this->getEmploymentType());
        $copyObj->setDepartment($this->getDepartment());
        $copyObj->setCategory($this->getCategory());
        $copyObj->setUpdatedAt($this->getUpdatedAt());
        $copyObj->setPostedAt($this->getPostedAt());
        $copyObj->setFirstSeenAt($this->getFirstSeenAt());
        $copyObj->setRemovedAt($this->getRemovedAt());
        $copyObj->setKeySiteAndPostID($this->getKeySiteAndPostID());
        $copyObj->setKeyCompanyAndTitle($this->getKeyCompanyAndTitle());
        $copyObj->setJobTitleLinked($this->getJobTitleLinked());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getUserJobMatches() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserJobMatch($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setJobPostingId(NULL); // this is a auto-increment column, so set to default value
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
     * @return \JobScooper\JobPosting Clone of current object.
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
        $this->collUserJobMatches->setModel('\JobScooper\UserJobMatch');
    }

    /**
     * Gets an array of ChildUserJobMatch objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildJobPosting is new, it will return
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
                    ->filterByJobPosting($this)
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
     * @return $this|ChildJobPosting The current object (for fluent API support)
     */
    public function setUserJobMatches(Collection $userJobMatches, ConnectionInterface $con = null)
    {
        /** @var ChildUserJobMatch[] $userJobMatchesToDelete */
        $userJobMatchesToDelete = $this->getUserJobMatches(new Criteria(), $con)->diff($userJobMatches);


        $this->userJobMatchesScheduledForDeletion = $userJobMatchesToDelete;

        foreach ($userJobMatchesToDelete as $userJobMatchRemoved) {
            $userJobMatchRemoved->setJobPosting(null);
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
                ->filterByJobPosting($this)
                ->count($con);
        }

        return count($this->collUserJobMatches);
    }

    /**
     * Method called to associate a ChildUserJobMatch object to this object
     * through the ChildUserJobMatch foreign key attribute.
     *
     * @param  ChildUserJobMatch $l ChildUserJobMatch
     * @return $this|\JobScooper\JobPosting The current object (for fluent API support)
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
        $userJobMatch->setJobPosting($this);
    }

    /**
     * @param  ChildUserJobMatch $userJobMatch The ChildUserJobMatch object to remove.
     * @return $this|ChildJobPosting The current object (for fluent API support)
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
            $userJobMatch->setJobPosting(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this JobPosting is new, it will return
     * an empty collection; or if this JobPosting has previously
     * been saved, it will retrieve related UserJobMatches from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in JobPosting.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserJobMatch[] List of ChildUserJobMatch objects
     */
    public function getUserJobMatchesJoinUser(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserJobMatchQuery::create(null, $criteria);
        $query->joinWith('User', $joinBehavior);

        return $this->getUserJobMatches($query, $con);
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        $this->jobposting_id = null;
        $this->jobsite = null;
        $this->jobsite_post_id = null;
        $this->title = null;
        $this->title_tokens = null;
        $this->url = null;
        $this->company = null;
        $this->location = null;
        $this->employment_type = null;
        $this->department = null;
        $this->category = null;
        $this->last_updated_at = null;
        $this->job_posted_date = null;
        $this->first_seen_at = null;
        $this->post_removed_at = null;
        $this->key_site_and_post_id = null;
        $this->key_company_and_title = null;
        $this->title_linked = null;
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
        } // if ($deep)

        $this->collUserJobMatches = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'key_site_and_post_id' column
     */
    public function __toString()
    {
        return (string) $this->getKeySiteAndPostID();
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     $this|ChildJobPosting The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[JobPostingTableMap::COL_LAST_UPDATED_AT] = true;

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
