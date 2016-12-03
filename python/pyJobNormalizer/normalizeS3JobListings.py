#!/bin/python
# -*- coding: utf-8 -*-
import sys
reload(sys)
sys.setdefaultencoding('utf-8')

cli_usage = """
Usage:
  normalizeS3JobListings.py -b <string> --inkey <string> --outkey <string> [--column <string> --index <string>]
  normalizeS3JobListings.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -b <string>, --bucket <string> AWS S3 bucket name to use
  -c <string>, --column=<string> csv key name for column to tokenize
  --inkey <string> S3 key name for the input file
  --outkey <string> S3 key name for the output file
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

    dataKey =  arguments['--column']
    if dataKey is None:
        dataKey = "job_title"

    indexKey =  arguments['--index']
    if indexKey is None:
        indexKey = "key_jobsite_siteid"


    bucketName = arguments['--bucket']
    stagingPrefix = "jobscooper/staging/"
    stage1key = stagingPrefix + "stage1-rawlistings/"
    stage2key = stagingPrefix + "stage2-rawlistings/"
    infile = os.tempnam()
    outfile = os.tempnam()

    if arguments['--inkey']:
        stage1key = arguments['--inkey'].replace("'", "")

    if arguments['--outkey']:
        stage2key = arguments['--outkey'].replace("'", "")

    print "Downloading s3 key " + stage1key + " to temp file " + infile
    s3Resource.Object(bucketName, stage1key).download_file(infile)
    tokfile = processfile.tokenizeJSONFile(infile, outfile, dataKey=dataKey, indexKey=indexKey)
    if tokfile:
        print "Uploading file " + tokfile + " to s3 key " + stage2key

        s3Resource.Bucket(bucketName).upload_file(tokfile, stage2key)
        os.unlink(outfile)
    else:
        print "No data found to process in " + stage1key
    os.unlink(infile)
    # response = s3Resource.Bucket(bucketName).delete_objects(
    #     Delete={
    #         'Objects': listDeleteObjects,
    #         'Quiet': False
    #         })
    # print "Deleted processed S3 objects.  Result: " + str(response)
    #
    # paginator = s3Client.get_paginator('list_objects')
    #
    # # Create a PageIterator from the Paginator
    # operation_parameters = {'Bucket': bucketName,
    #                         'Prefix': stage1key}
    # listDeleteObjects = []
    # page_iterator = paginator.paginate(**operation_parameters)
    # for page in page_iterator:
    #     if 'Contents' in page:
    #         for item in page['Contents']:
    #             # for key in sourceBucket.objects.all():
    #             sourceKey = item['Key']
    #             if sourceKey.startswith(stage1key) and sourceKey.endswith(".json"):
    #                 keyParts = sourceKey.split("/")
    #                 fileKey = keyParts[len(keyParts)-2] + "-" + keyParts[len(keyParts)-1]
    #                 infile = os.tempnam()
    #                 outfile = os.tempnam()
    #
    #                 print "Downloading s3 key " + sourceKey + " to temp file " + infile
    #                 s3Resource.Object(bucketName, sourceKey).download_file(infile)
    #                 tokfile = processfile.tokenizeJSONFile(infile, outfile, dataKey=dataKey, indexKey=indexKey)
    #
    #                 if tokfile:
    #                     uploadFileKey = stage2key + fileKey
    #                     print "Uploading file " + tokfile + " to s3 key " + uploadFileKey
    #
    #                     s3Resource.Bucket(bucketName).upload_file(tokfile, uploadFileKey)
    #                     os.unlink(outfile)
    #                 else:
    #                     print "No data found to process in " + item['Key']
    #                 s3Resource.Bucket(bucketName)
    #                 listDeleteObjects.append({"Key": sourceKey})
    #                 os.unlink(infile)
    #
    #         if listDeleteObjects:
    #             print "Deleting processed objects from S3: " + str(listDeleteObjects)
    #             # response = s3Resource.Bucket(bucketName).delete_objects(
    #             #     Delete={
    #             #         'Objects': listDeleteObjects,
    #             #         'Quiet': False
    #             #         })
    #             # print "Deleted processed S3 objects.  Result: " + str(response)


    print (u"Tokenized results uploaded to s3://%s/%s" % (bucketName, stage2key))