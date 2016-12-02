<?php
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/Options.php');

# run "composer require aws/aws-sdk-php" before using

class S3Manager {
    private $s3Client = null;
    private $bucket = "www.rvelocity.net";
    private $keyPrefix = "jobscooper/";


    public function __construct($bucket, $region)
    {
        $this->bucket = $bucket;

        $this->s3Client  = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $region
        ]);
    }

    public function publishFolderToBucket($sourceDirectory, $bucketKeyPrefix="")
    {
        $bucketKeyPrefix = $this->keyPrefix . $bucketKeyPrefix;

        $dest = 's3://'.$this->bucket. "/" . $bucketKeyPrefix;

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Publishing folder '" . $sourceDirectory . "' to " . $dest, \Scooper\C__DISPLAY_NORMAL__);
        $manager = new \Aws\S3\Transfer($this->s3Client, $sourceDirectory, $dest,[
            'before' => function (\Aws\Command $command) {
                    // Apply a canned ACL
                    $command['ACL'] = 'public-read';
                },
        ]);
        $manager->transfer();
    }

    public function publishOutputFiles($outputDirectory)
    {
        $keypath = join(DIRECTORY_SEPARATOR, array($GLOBALS['USERDATA']['user_unique_key'] . "latest"));

        $this->publishFolderToBucket($outputDirectory, $keypath);
    }

    public function downloadObjectsToFile($bucketKeyPrefix, $downloadDirectory)
    {
        $srcPath = 's3://'.$this->bucket. "/" . $this->keyPrefix . $bucketKeyPrefix;

        $manager = new \Aws\S3\Transfer($this->s3Client, $srcPath, $downloadDirectory, []);
        $manager->transfer();
    }
}

