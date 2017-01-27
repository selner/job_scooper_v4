#!/bin/python
# -*- coding: utf-8 -*-
import sys
import uuid
reload(sys)
sys.setdefaultencoding('utf-8')

cli_usage = """
Usage:
  normalizeS3JobListings.py --inkey <string> --outkey <string> [-b <string> --source <string> --column <string> --index <string>]
  normalizeS3JobListings.py --version

Options:
  -h --help  show this help message and exit
  --version  show version and exit
  -v --verbose  print status messages
  -b <string>, --bucket <string> AWS S3 bucket name to use
  -c <string>, --column=<string> csv key name for column to tokenize
  --source=<string> either "s3" or the local directory that contains the files.  [default: s3]
  --inkey=<string> file key name for the input file
  --outkey=<string> file key name for the output file
  --index=<string> csv key name for index column in input csv
"""

from docopt import docopt
import os
import boto3

if __name__ == '__main__':
    print " ".join(sys.argv)
    arguments = docopt(cli_usage, version='0.1.1rc')
    print arguments
    import processfile

    dataKey = arguments['--column']
    if dataKey is None:
        dataKey = "job_title"

    indexKey = arguments['--index']
    if indexKey is None:
        indexKey = "key_jobsite_siteid"

    if arguments['--inkey']:
        stage1key = arguments['--inkey'].replace("'", "")

    if arguments['--outkey']:
        stage2key = arguments['--outkey'].replace("'", "")

    source = arguments['--source'].replace("'", "")

    if source and source.lower() == "s3":

        s3Client = boto3.client('s3')
        s3Resource = boto3.resource('s3')

        bucketName = arguments['--bucket']
        stage1key = "stage1-rawlistings/" + str(uuid.uuid5(uuid.NAMESPACE_URL, "inputfile"))
        stage2key = "stage2-rawlistings/" + str(uuid.uuid5(uuid.NAMESPACE_URL, "outputfile"))

        infile = os.tempnam()
        outfile = os.tempnam()

        print "Downloading s3 key " + stage1key + " to temp file " + infile
        s3Resource.Object(bucketName, stage1key).download_file(infile)
        tokfile = processfile.tokenizeJSONFile(infile, outfile, dataKey=dataKey, indexKey=indexKey)
        if tokfile:
            print "Uploading file " + tokfile + " to s3 key " + stage2key
            s3Resource.Bucket(bucketName).upload_file(tokfile, stage2key)
            os.unlink(outfile)
        else:
            print "No data found to process in " + stage1key

    else:
        infile = os.path.join(source, stage1key)
        outfile = os.path.join(source, stage2key)
        tokfile = processfile.tokenizeJSONFile(infile, outfile, dataKey=dataKey, indexKey=indexKey)

    print (u"Tokenized results completed.")