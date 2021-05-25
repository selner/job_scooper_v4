###########################################################################
#
#  Copyright 2014-21 Bryan Selner
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
#
###########################################################################
from flask import Flask, render_template, request
from webargs.flaskparser import use_kwargs, use_args
from webargs import fields

from api.tasks.match_titles import TaskMatchJobsToKeywords
from api.utils.logger import logmsg

from api.tasks.add_newposts_to_user import TaskAddNewMatchesToUser
from api.tasks.dedupe_jobs import TaskDedupeJobPosting
from api.tasks.find_and_match_places import FindPlacesFromDBLocationsTask
from api.tasks.mark_outofarea_matches import TaskMarkOutOfAreaMatches
from api.tasks.skip_nontitle_matches import TaskMarkNonMatchesAsSkipSend
from api.tasks.tokenize_jobtitles import TaskAddTitleTokens
from api.utils.files import ExtJsonEncoder
from api.utils.logger import logdebug, getLogger

import functools

query = functools.partial(use_kwargs, location="query")
body = functools.partial(use_args, location="json")

app = Flask(__name__)
import os
app.secret_key = os.urandom(24)

app.logger = getLogger()

@app.after_request
def after_request_func(response):
    logdebug("Request: " + request.url + " : " + str(request.remote_addr) + "\nResponse: " + response.get_data(as_text=True))
    return response

# @app.route('/')
# def hello():
#   return render_template('index.html')

# import api.utils.testimport
# @app.route('/test')
# def test():
#   logmsg("This is a test.")
#   return api.utils.testimport.runtest()



# from jobnormalizer.task_add_newposts_to_user import TaskAddNewMatchesToUser
# from jobnormalizer.task_dedupe_jobs import TaskDedupeJobPosting
# from jobnormalizer.task_find_and_match_places import FindPlacesFromDBLocationsTask
# from jobnormalizer.task_mark_outofarea_matches import TaskMarkOutOfAreaMatches
# from jobnormalizer.task_skip_nontitle_matches import TaskMarkNonMatchesAsSkipSend


argmap = {
        # "dsn": fields.Str(required=False),
        # "c": fields.Str(required=False),
        "user": fields.Str(required=True),
        "password": fields.Str(required=True),
        "host": fields.Str(required=True),
        #"port": fields.Int(required=False),}use_args, validate=[validate.Range(min=1, max=9999)])
        "port": fields.Int(required=False),
        "database": fields.Str(required=True),
#         "input": fields.Str(required=False),
#         "output": fields.Str(required=False),
}



argpmap_jsondata = argmap.copy()


@app.route("/config")
def dump_config():
    import json
    return json.dumps(app.config, indent=4, cls=ExtJsonEncoder)


@app.route("/api/set_title_tokens")
@use_kwargs(argmap, location="query")
def api_set_title_tokens(**kwargs):
    try:
        toks = TaskAddTitleTokens(**kwargs)
        ret = toks.update_jobs_without_tokens()
        return {'rows_processed': ret}
    except Exception as ex:
        app.logger(f'Unable to update job title tokes: {ex}')
        raise ex

@app.route("/api/add_jobs_to_users/<string:jobsite_key>/<int:user_id>")
@use_kwargs(argmap, location="query")
def api_add_user_jobs(jobsite_key, user_id, **kwargs):
    matcher = TaskAddNewMatchesToUser(**kwargs)
    return matcher.add_new_posts_to_user(jobsite_key, user_id)

@app.route("/api/process_duplicates")
@use_kwargs(argmap, location="query")
def api_dupes_find(**kwargs):
    matcher = TaskDedupeJobPosting(**kwargs)
    return matcher.dedupe_jobs()

@app.route("/api/update_matches")
@use_kwargs(argmap, location="query")
def api_update_matches(**kwargs):
    matcher = TaskMarkNonMatchesAsSkipSend(**kwargs)
    return matcher.update_job_matches()


@app.route("/api/set_out_of_area/<int:user_id>")
@use_kwargs(argmap, location="query")
def api_set_out_of_area(user_id, **kwargs):
    matcher = TaskMarkOutOfAreaMatches(user_id=user_id, **kwargs)
    return matcher.mark_out_area()

@app.route("/api/update_geocodes")
@use_kwargs(argmap, location="query")
@use_kwargs({'server': fields.Str(required=True), 'location': 'query'})
def api_update_geocodes(**kwargs):
    matcher = FindPlacesFromDBLocationsTask(**kwargs)
    return matcher.update_all_locations(**kwargs)

@app.route("/testjson", methods=["PUT", "POST"])
# @query(argmap)
# @body({"inputdata": fields.Str()})
def viewfunc():
    from flask import request

    import os
    from flask import Flask, flash, request, redirect, url_for
    from werkzeug.utils import secure_filename
    ALLOWED_EXTENSIONS = {'txt', 'pdf', 'png', 'jpg', 'jpeg', 'gif', "json", ""}
    def allowed_file(filename):
        return '.' in filename and \
               filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS
    # page = query_parsed["page"]
    # inputdata = json_parsed["inputdata"]
    if request.method == 'POST':
        # check if the post request has the file part
        if 'file' not in request.files:
            flash('No file part')
            return redirect(request.url)
        file = request.files['file']
        # if user does not select file, browser also
        # submit an empty part without filename
        if file.filename == '':
            flash('No selected file')
            return redirect(request.url)
        if file and allowed_file(file.filename):
            filename = secure_filename(file.filename)
            file.save(os.path.join(app.config['UPLOAD_FOLDER'], filename))
            return redirect(url_for('uploaded_file',
                                    filename=filename))

@app.route("/api/match_user_keywords/<int:user_id>")
@use_kwargs(argmap, location="query")
def api_match_job_keywords(**kwargs):
    import os
    from flask import Flask, flash, request, redirect, url_for
    from werkzeug.utils import secure_filename
    ALLOWED_EXTENSIONS = {'txt', 'pdf', 'png', 'jpg', 'jpeg', 'gif', "json", ""}
    def allowed_file(filename):
        return '.' in filename and \
               filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS
    # page = query_parsed["page"]
    # inputdata = json_parsed["inputdata"]
    if request.method == 'POST':
        # check if the post request has the file part
        if 'file' not in request.files:
            flash('No file part')
            return redirect(request.url)
        file = request.files['file']
        # if user does not select file, browser also
        # submit an empty part without filename
        if file.filename == '':
            flash('No selected file')
            return redirect(request.url)
        if file and allowed_file(file.filename):
            filename = secure_filename(file.filename)
            file.save(os.path.join(app.config['UPLOAD_FOLDER'], filename))
            return redirect(url_for('uploaded_file',
                                    filename=filename))

    matcher = TaskMatchJobsToKeywords(**kwargs)
    matcher.process_user_token_matches()


if __name__ == "__main__":
    app.run(port=5000, debug=True)
