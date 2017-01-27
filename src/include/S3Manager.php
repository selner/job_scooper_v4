<?php
if (!strlen(__ROOT__) > 0) { define('__ROOT__', dirname(dirname(__FILE__))); }
require_once(__ROOT__.'/include/Options.php');

# run "composer require aws/aws-sdk-php" before using
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\Credentials\CredentialProvider;


class S3Manager {
    private $s3Client = null;
    private $bucket = "www.rvelocity.net";
    private $logger = null;

    public function isConnected() { return !is_null($this->s3Client); }

    public function __construct($bucket, $region, $logger=null)
    {
        if(strlen($bucket) > 0 && strlen($region) > 0)
        {
            $this->bucket = $bucket;

            $this->s3Client  = new Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => $region,
                'credentials' => CredentialProvider::defaultProvider()
            ]);
        }

        if ($logger)
            $this->logger = $logger;
        elseif($GLOBALS['logger'])
            $this->logger = $GLOBALS['logger'];
        else
            $this->logger = new \Scooper\ScooperLogger($GLOBALS['USERDATA']['directories']['stage1'] );

    }

    public function publishFolderToBucket($sourceDirectory, $bucketKeyPrefix="")
    {
        if($this->isConnected() === false)
        {
            $this->logger->logLine("S3 not connected so ignoring....");
            return;
        }

        $dest = 's3://'.$this->bucket."/". $bucketKeyPrefix;
        $this->logger->logLine("Publishing folder '" . $sourceDirectory . "' to " . $dest);

        try {
            $manager = new \Aws\S3\Transfer($this->s3Client, $sourceDirectory, $dest,[
                'before' => function (\Aws\Command $command) {
                        // Apply a canned ACL
                        $command['ACL'] = 'public-read';
                    },
            ]);
            $manager->transfer();
        } catch (S3Exception $e) {
            $msg = "Error deleting objects from S3:  " . $e->getMessage();
            $this->logger->logLine($msg);
            throw new Exception($msg);
        }

    }

    public function downloadObjectsToFile($bucketKeyPrefix, $downloadDirectory)
    {
        if($this->isConnected() === false)
        {
            $this->logger->logLine("S3 not connected so ignoring....");
            return;
        }

        $srcPath = 's3://'.$this->bucket.  $bucketKeyPrefix;

        $this->logger->logLine("Downloading objects from '" . $srcPath . "' to " . $downloadDirectory);
        try
        {
            $manager = new \Aws\S3\Transfer($this->s3Client, $srcPath, $downloadDirectory, []);
            $manager->transfer();
        } catch (S3Exception $e) {
            $msg = "Error deleting objects from S3:  " . $e->getMessage();
            $this->logger->logLine($msg);
            throw new Exception($msg);
        }
    }

    public function getMatchingObjects($bucketKeyPrefix)
    {
        try
        {
            $retObjects = array();
            $matches = $this->getObjectKeyMatches($bucketKeyPrefix);
            if($matches)
                foreach($matches as $match)
                {
                    $retObjects[$match] = $this->getObject($match);
                }
            else
                $this->logger->logLine("No objects found in S3 bucket '".$this->bucket."' matching prefix  " .$bucketKeyPrefix);

            return $retObjects;
        } catch (S3Exception $e) {
            $msg = "Error deleting objects from S3:  " . $e->getMessage();
            $this->logger->logLine($msg);
            throw new Exception($msg);
        }
    }

    public function getObject($bucketKey)
    {
        if($this->isConnected() === false)
        {
            $this->logger->logLine("S3 not connected so ignoring....");
            return null;
        }

        try
        {
            $this->logger->logLine("Deleting objects from S3 with the following key:  " . $bucketKey );
            $result = $this->s3Client->getObject(array(
                'Bucket' => $this->bucket,
                'Key'    => $bucketKey
            ));

            $data = array(
                'Bucket' => $this->bucket,
                'Key' => $bucketKey,
                'Body' => $result['Body'],
                'BodyDecoded' => json_decode($result['Body'], true, 512,JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP)
            );
            return $data;

        } catch (S3Exception $e) {
            $msg = "Error getting object from S3:  " . $e->getMessage();
            $this->logger->logLine($msg);
            throw new Exception($msg);
        }
    }

    public function deleteObjects($bucketKeyPrefix)
    {
        if($this->isConnected() === false)
        {
            $this->logger->logLine("S3 not connected so ignoring....");
            return;
        }

        try
        {
            $this->logger->logLine("Deleting objects from S3 with the following key prefix:  " . $bucketKeyPrefix );
//            $this->s3Client->deleteMatchingObjects($this->bucket, $bucketKeyPrefix);

        } catch (S3Exception $e) {
            $msg = "Error deleting objects from S3:  " . $e->getMessage();
            $this->logger->logLine($msg);
            throw new Exception($msg);
        }

    }

    public function getObjectKeyMatches($bucketKeyPrefix)
    {
        if($this->isConnected() === false)
        {
            $this->logger->logLine("S3 not connected so ignoring....");
            return null;
        }

        try {
            $objects = $this->s3Client->getIterator('ListObjects', array('Bucket' => $this->bucket));
            $arrMatches = array();
            foreach ($objects as $object) {
                if (substr($object['Key'], 0, (strlen($bucketKeyPrefix))) == $bucketKeyPrefix)
                {
                    $arrMatches[] = $object['Key'];
                }
            }
            return $arrMatches;
        } catch (S3Exception $e) {
            $msg = "Error listing objects from S3 bucket '".$this->bucket."':  " . $e->getMessage();
            $this->logger->logLine($msg);
            throw new Exception($msg);
        }
    }

    public function uploadObject($key, $data)
    {
        if($this->isConnected() === false)
        {
            $this->logger->logLine("S3 not connected so ignoring....");
            return null;
        }

        try {
            $this->logger->logLine("Uploading object to S3 bucket '".$this->bucket."' with key '".$key);
            // Upload data.
            $result = $this->s3Client->putObject(array(
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'ACL'          => 'public-read',
                'StorageClass' => 'REDUCED_REDUNDANCY',
                'ContentType'  => 'text/json',
                'Body'   => json_encode($data, JSON_PRETTY_PRINT )
            ));
            return $result;
        } catch (S3Exception $e) {
            $msg = "Error uploading object to S3 bucket '".$this->bucket."' with key '".$key."':  " . $e->getMessage();
            $this->logger->logLine($msg);
            throw new Exception($msg);
        }

    }
}

