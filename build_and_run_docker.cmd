docker rm -f jobs

docker build --tag selner/js4 .

#
# To use on macos or linux:
#     1.  change the PC's volume path to be "/Users/bryan/Dropbox/var-jobs_scooper::/var/local/jobs_scooper --volume /devcode/nltk_data:/root/nltk_data" style instead
#     2.  save as a .sh file
#
docker run --volume C:\Users\bryan\Dropbox\var-jobs_scooper:/var/local/jobs_scooper --volume c:\devcode\nltk_data:/root/nltk_data --name jobs -d selner/js4

docker logs -f jobs

