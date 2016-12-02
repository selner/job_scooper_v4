<?php
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/Options.php');

# run "composer require aws/aws-sdk-php" before using

class S3Publisher {
    private $s3Client = null;
    private $bucket = "www.rvelocity.net";
    private $keyPrefix = "jobs_output/";


    public function __construct($bucket, $region)
    {
        $this->bucket = $bucket;

        $this->s3Client  = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $region
        ]);
    }

    public function publishFilesToBucket($outputDirectory, $bucketName=null, $bucketKeyPrefix="")
    {
        if ($bucketName == null)
        {
            $bucketName = $this->bucket;
        }
        if ($bucketKeyPrefix == null)
        {
            $bucketKeyPrefix = $this->keyPrefix;
        }

        $source = $outputDirectory;

        $dest = 's3://'.$bucketName . "/" . $bucketKeyPrefix;

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Publishing result files from " . $source . " to " . $dest, \Scooper\C__DISPLAY_NORMAL__);
        $manager = new \Aws\S3\Transfer($this->s3Client, $source, $dest,[
            'before' => function (\Aws\Command $command) {
                    // Apply a canned ACL
                    $command['ACL'] = 'public-read';
                },
        ]);
        $manager->transfer();
    }

    public function publishOutputFiles($outputDirectory)
    {
        $dirParts = explode(DIRECTORY_SEPARATOR, $outputDirectory);
        $keypath = $this->keyPrefix . join("/", array_slice($dirParts, count($dirParts) - 4, 2)) . "/latest";

        $this->publishFilesToBucket($outputDirectory, $this->bucket, $keypath);
    }


}