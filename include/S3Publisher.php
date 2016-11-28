<?php
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/Options.php');

# run "composer require aws/aws-sdk-php" before using

class S3Publisher {
    private $s3Client = null;
    private $key = "AKIAID46ZVSLZOCQI6AA";
    private $secret = "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";
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

    public function publishOutputFiles($outputDirectory)
    {

        $source = $outputDirectory;

        $dirParts = explode(DIRECTORY_SEPARATOR, $outputDirectory);
        $keypath = $this->keyPrefix . join("/", array_slice($dirParts, count($dirParts) - 4, 2)) . "/latest";
        $dest = 's3://'.$this->bucket . "/" . $keypath;

        if(isset($GLOBALS['logger'])) $GLOBALS['logger']->logLine("Publishing result files from " . $source . " to " . $dest, \Scooper\C__DISPLAY_NORMAL__);
        $manager = new \Aws\S3\Transfer($this->s3Client, $source, $dest,[
            'before' => function (\Aws\Command $command) {
                    // Apply a canned ACL
                    $command['ACL'] = 'public-read';
                },
        ]);
        $manager->transfer();
    }
    
}