#!/bin/python
#  -*- coding: utf-8 -*-
#
###########################################################################
#
#  Copyright 2014-18 Bryan Selner
#
#  Licensed under the Apache License, Version 2.0 (the "License"); you may
#  not use this file except in compliance with the License. You may obtain
#  a copy of the License at
#
#      http://www.apache.org/licenses/LICENSE-2.0
#
#  Unless required by applicable law or agreed to in writing, software
#  distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
#  WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
#  License for the specific language governing permissions and limitations
#  under the License.
###########################################################################
from util_tokenize import Tokenizer
from mixin_database import DatabaseMixin
from cleanco import cleanco
import re
from helpers import xstr


class TaskNormalizeJobPosts(DatabaseMixin):
    _tokenizer = None
    _config = {}

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

        self._tokenizer = Tokenizer()
        self._config = dict(kwargs)

    def normalize_new_unprocessed_posts(self):
        raise Exception("Not implemented!")

    def do_normalization(self):
        self.normalize_new_unprocessed_posts()

    def save_normalized_jobpostings(self, posts):
        import helpers
        import os
        filepath = os.path.join("/tmp", "normalized_jobposts.json")
        self.log("Writing normalized posts to {}...".format(filepath))
        helpers.write_json(filepath, posts)

    def normalize_newjobs_batch(self, newposts):
        normalized_jobs = {}

        if len(newposts) > 0:
            for r in newposts:
                try:
                    jobrecord = dict(r)
                    if 'jobsite_key' not in r or 'jobsite_post_id' not in r:
                        continue

                    jobkey = "{}_{}".format(r['jobsite_key'], r['jobsite_post_id'])

                    #
                    # Sanitize / scrub each string-type field in each row
                    #
                    for k in jobrecord.keys():
                        k_compare = str(k).lower()
                        if not jobrecord[k]:
                            continue

                        if isinstance(jobrecord[k], str) and k_compare not in ["url", "jobposting_id"]:
                            remove_punct = True
                            if k_compare in ["location", "first_seen_at", "last_updated_at", "job_posted_date"]:
                                remove_punct = False

                            jobrecord[k] = self._tokenizer.clean_string(jobrecord[k], remove_punct=remove_punct)

                        #
                        # Do any additional processing specific to a particular field for the jobrecord (e.g. company)
                        #
                        if k_compare is "title":
                            jobrecord[k] = self._tokenizer.get_expanded_words(jobrecord[k])
                        elif k_compare is "company":
                            if len(xstr(jobrecord[k])) > 0:
                                newco = cleanco(jobrecord[k])
                                jobrecord[k] = newco.clean_name()
                        elif k_compare in ["first_seen_at", "last_updated_at", "job_posted_date"]:
                            import chronyk
                            if jobrecord[k].lower() is "just posted":
                                jobrecord[k] = chronyk.Chronyk("now")
                            else:
                                try:
                                    import re
                                    if isinstance(jobrecord[k], str):
                                        jobrecord[k] = re.sub("(posted\s(date|at)?)?", "", jobrecord[k])
                                    t = chronyk.Chronyk(jobrecord[k], allowfuture=False)
                                except chronyk.DateRangeError:
                                    pass
                                except ValueError:
                                    pass
                                else:
                                    jobrecord[k] = t.timestring("%Y%m%d")

                    normalized_jobs[jobkey] = jobrecord

                except Exception as ex:
                    self.log("Exception thrown while scrubbing text value at key {} for raw job posting {}: {}".format(r, newposts[r], ex))
                    pass

            #
            # Add title tokens for each job
            #
            tokenized_jobs = self._tokenizer.batch_tokenize_strings(normalized_jobs, u'title', u'title_tokens', u'dict')

            # TODO:  add URL parsing and normalization.  Requires getting the base site URL for each jobsite passed
            #        or stored in the DB

            jobs_to_store = {}
            if tokenized_jobs and len(tokenized_jobs) > 0:
                for k in tokenized_jobs:
                    jobrecord = tokenized_jobs[k]

                    import datetime
                    jobrecord['last_updated_at'] = datetime.datetime.today().strftime("%Y%m%d")

                    #
                    # Generate and set the job reference key which we will use to match jobs across different
                    # sites for the same title/company pairings
                    #
                    if 'title_tokens' in jobrecord and len(xstr(jobrecord['title_tokens'])) > 0:

                        job_ref_key_value = "_".join(jobrecord['title_tokens'])

                        refkey_company = ""
                        refkey_jobsite = ""

                        if 'company' in jobrecord and len(xstr(jobrecord['company'])) > 0:
                            refkey_company = self._tokenizer.filter_to_alphanum(jobrecord['company'])
                        elif 'jobsite_key' in jobrecord and len(xstr(jobrecord['jobsite_key'])) > 0:
                            refkey_jobsite = self._tokenizer.filter_to_alphanum("unknown_via_{}".format(jobrecord['jobsite_key']))

                        job_ref_key_value = "{}{}_{}".format(refkey_company, refkey_jobsite, job_ref_key_value)

                        jobrecord["job_reference_key"] = job_ref_key_value.lower().strip()

                    if "job_reference_key" in jobrecord and len(xstr(jobrecord['job_reference_key'])):
                        jobrecord["key_company_and_title"] = jobrecord['job_reference_key']
                    else:
                        jobrecord["key_company_and_title"] = xstr(re.sub('\W+', "", jobrecord['title'])) + "_" + xstr(re.sub(
                            '\W+', "", jobrecord['company'])).lower().strip()

                    jobs_to_store[k] = jobrecord
            try:
                self.log("... saving {} job postings to JSON file...".format(len(jobs_to_store)))
                self.save_normalized_jobpostings(jobs_to_store)

                self.log("... updating {} job postings in database ...".format(len(jobs_to_store)))
                self.add_normalized_jobpostings_to_db(jobs_to_store)

            except Exception as ex:
                self.log(str(ex))

            return jobs_to_store

    def add_normalized_jobpostings_to_db(self, jobs):

        self.log("... adding/updating {} job postings in the database...".format(len(jobs)))
        try:
            self.add_or_update_rows("jobposting", "jobposting_id", jobs)
        except Exception as ex:
            self.log("Unable to add normalized jobs to database: {}".format(str(ex)))
            pass

        self.log(u"Added normalized job postings to the database.")

class TaskNormalizeJobPostsJson(TaskNormalizeJobPosts):
    _inputfile = None

    def __init__(self, **kwargs):
        TaskNormalizeJobPosts.__init__(self, **kwargs)

        if not ('input' in kwargs and kwargs['input'] and len(kwargs['input']) > 0):
            raise Exception("Input file path was not specified.  Aborting.")

        import os
        self._inputfile = os.path.abspath(self._config['input'])

        if not self._inputfile or self._inputfile is False or not os.path.isfile(self._inputfile):
            raise Exception("Valid input file not found at {}.  Aborting.".format(self._inputfile))


    def normalize_new_unprocessed_posts(self):
        import helpers
        jobs = helpers.load_json(self._inputfile)

        if jobs and len(jobs) > 0:

            if isinstance(jobs, dict):
                jobs = list(jobs.values())

            self.log("Loaded {} new job postings to import...".format(len(jobs)))
            total_rows = self.process_data_in_batches(jobs, self.normalize_newjobs_batch)
            self.log("Normalized {} new job postings.".format(total_rows))

    def process_data_in_batches(self, data, callback, batch_size=1000, return_results=False):

        if not callable(callback):
            raise Exception("Specified callback {} is not callable.".format(str(callback)))

        results = []
        total_rows = len(data)

        try:
            batch_counter = 0

            datachunks = [data[i:i + batch_size] for i in range(0, len(data), batch_size)]

            for chunk in datachunks:
                curidx = batch_counter * batch_size
                self.log(u"... processing records {} - {} of {} through callback {}".format(curidx, min(curidx+batch_size, total_rows), total_rows, str(callback)))

                if return_results == True:
                    results.extend(callback(chunk))
                else:
                    callback(chunk)
                batch_counter += 1

            self.connection.commit()

            if return_results:
                return results
            else:
                return total_rows

        except Exception as ex:
            self.handle_error(ex)


class TaskNormalizeJobPostsMysql(TaskNormalizeJobPosts):

    def normalize_newjobs_batch(self, newposts):
        raw_jobposts_normalized = []

        jobs_stored = TaskNormalizeJobPosts.normalize_newjobs_batch(newposts)
        try:
            self.log("... saving {} job postings to JSON file...".format(len(jobs_stored)))
            self.save_normalized_jobpostings(jobs_stored)

            self.log("... updating {} job postings in database ...".format(len(jobs_stored)))
            self.add_normalized_jobpostings_to_db(jobs_stored)

            self.log("... marking {} source job postings as normalized/processed...".format(len(jobs_stored)))
            for j in jobs_stored:
                raw_jobposts_normalized.append([jobs_stored[j][col] for col in ['jobsite_key', 'jobsite_post_id']])

            upd_query = """
                   UPDATE raw_jobposting 
                   SET 
                       was_normalized = True
                   WHERE
                       jobsite_key = %s AND
                       jobsite_post_id = %s """
            self.update_many(upd_query, raw_jobposts_normalized)

        except Exception as ex:
            self.log(str(ex))

    def normalize_new_unprocessed_posts(self):
        query = '''
            SELECT 
                `raw_jobposting_id`,
                `jobsite_key`,
                `jobsite_post_id`,
                `title`,
                `url`,
                `employment_type`,
                `pay_range`,
                `location`,
                `company`,
                `department`,
                `category`,
                `last_updated_at`,
                `job_posted_date`,
                `first_seen_at`
            FROM `raw_jobposting`
            WHERE
                was_normalized IS NULL or 
                was_normalized = 0 
            ORDER BY 
               first_seen_at asc
        '''
        total_rows = self.fetch_many_with_callback(query, self.normalize_newjobs_batch)
        self.log("Normalized {} new job postings.".format(total_rows))
