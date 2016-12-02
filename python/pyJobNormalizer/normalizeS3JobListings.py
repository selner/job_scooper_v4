#!/bin/python
# -*- coding: utf-8 -*-
import sys
reload(sys)
sys.setdefaultencoding('utf-8')

cli_usage = """
Usage:
  normalizeS3JobListings.py -b <string> [-k <string> --index <string>]
  normalizeS3JobListings.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -b <string>, --bucket <string> AWS S3 bucket name to use
  -k <string>, --columnkey=<string> csv key name for column to tokenize
  --index <string> csv key name for index column in input csv
"""

from docopt import docopt

if __name__ == '__main__':
    print " ".join(sys.argv)
    arguments = docopt(cli_usage, version='0.1.1rc')
    print arguments
    import processfile

    import boto3
    import os

    s3Client = boto3.client('s3')
    s3Resource = boto3.resource('s3')

    dataKey =  arguments['--columnkey']
    if dataKey is None:
        dataKey = "job_title"

    indexKey =  arguments['--index']
    if indexKey is None:
        indexKey = "key_jobsite_siteid"


    bucketName = arguments['--bucket']
    stagingPrefix = "jobscooper/staging/"
    stage1prefix = stagingPrefix + "stage1-rawlistings/"
    stage2prefix = stagingPrefix + "stage2-rawlistings/"

    paginator = s3Client.get_paginator('list_objects')

    # Create a PageIterator from the Paginator
    operation_parameters = {'Bucket': bucketName,
                            'Prefix': stage1prefix}
    page_iterator = paginator.paginate(**operation_parameters)
    for page in page_iterator:
        for item in page['Contents']:
            # for key in sourceBucket.objects.all():
            sourceKey = item['Key']
            if sourceKey.startswith(stage1prefix) and sourceKey.endswith(".json"):
                fileKey = sourceKey.replace(stage1prefix, "")
                infile = os.tempnam()
                outfile = os.tempnam()

                print "Downloading s3 key " + sourceKey + " to temp file " + infile
                s3Resource.Object(bucketName, sourceKey).download_file(infile)
                tokfile = processfile.tokenizeJSONFile(infile, outfile, dataKey=dataKey, indexKey=indexKey)
                if tokfile:
                    uploadFileKey = stage2prefix + fileKey
                    print "Uploading file " + tokfile + " to s3 key " + uploadFileKey

                    s3Resource.Bucket(bucketName).upload_file(tokfile, uploadFileKey)
                    os.unlink(outfile)
                os.unlink(infile)
            else:
                print "Skipped " + item['Key']


    print (u"Tokenized results uploaded to s3://%s/%s" % (bucketName, stage2prefix))