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


import requests
import helpers
from bs4 import BeautifulSoup
from urlparse import urlparse
import string
import codecs, json

companies = {}
s = requests.Session()

MAX_ITEMS = 500

def underscore_to_camelcase(value):
    def camelcase():
        yield str.lower
        while True:
            yield str.capitalize

    c = camelcase()
    punctMinusUscore = set(string.punctuation.replace("_", ""))

    newVal = ""
    for ch in value:
        if ch in punctMinusUscore:
            newCh = "_"
        else:
            newCh = ch
        newVal += newCh

    splt = str(newVal).split("_")
    if len(splt) > 1:
        return "".join(c.next()(x) if x else '_' for x in splt)
    return str(newVal)

def getSitesFromBing(domainKey):
    itemIndex = 1
    results = []
    nextPageLink = "https://www.bing.com"

    pageNum = 1

    while nextPageLink and MAX_ITEMS > itemIndex > 0:
        nextPageLink = "https://www.bing.com/search?q=site%3a{}&qs=n&sp=-1&pq=site%3a{}&first={}".format(domainKey,
                                                                                                         domainKey,
                                                                                                         itemIndex + 1)
        r = s.get(nextPageLink)
        print "getting search items {}+ for {} from {}".format(itemIndex, domainKey, nextPageLink)
        if r.status_code == 200:
            soup = BeautifulSoup(r.text, 'html.parser')

            citationUrls = soup.find_all("cite")

            for citation in citationUrls:
                url = citation.text
                if not str(url).startswith("http"):
                    if domainKey not in url:
                        url = "http://{}{}".format(domainKey, url)
                    else:
                        url = "http://{}".format(url)
                parsed = urlparse(url)
                results.append(parsed)
            pageNum += 1
            nextPageElemFound = soup.find("a", string=pageNum)
            if not nextPageElemFound:
                nextPageLink = None
        itemIndex = len(results) + 1

    return results

def getJobviteAgents():
    results = {}
    urls = getSitesFromBing("jobs.jobvite.com")

    exportAgentsJson(urls, "jobvite_agents_20180402.json", "AbstractJobviteATS", "/code/job_scooper_v4/Plugins/json-based/jobvite_agents.json")

def parseUrlItem(parsed):
    item = {
        "JobSiteName": "",
        "SiteReferenceKey": "",
        "SourceURL": ""
    }

    import re
    path = parsed.path
    replace = [r"/jobAlerts.*", r"/jobs.*", r"/job/.*", r"/all-jobs.*", r"/search[/|$]", r"/careers/", "^/"]
    for rpl in replace:
        path = re.sub(rpl, '', path, flags=re.IGNORECASE)
    name = underscore_to_camelcase(path)
    rawurl = parsed.geturl()
    if not parsed.query:
        jobsUrl = "{}://{}/{}/search".format(parsed.scheme, parsed.netloc, path)
    else:
        jobsUrl = rawurl

    print "Processing {}...".format(rawurl)

    item.update({
        "JobSiteName": name,
        "SiteReferenceKey": name.lower(),
        "SourceURL": jobsUrl
    })
    #
    # import os, sys
    # item.update({
    #     "OriginalCitation" : rawurl,
    #     "UrlCheckPassed" : False
    # })
    # checkUrl = None
    # try:
    #     checkSession = requests.Session()
    #     checkSession.mount('https://', adapters.HTTPAdapter())
    #     checkUrl = checkSession.get(item["SourceURL"], timeout=5)
    #
    #
    #     item["Response"] = checkUrl.status_code
    #     if checkUrl.status_code != requests.codes.ok:
    #         item["UrlCheckPassed"] = False
    #     else:
    #         retUrl = checkUrl.url
    #         if "invalid" in retUrl:
    #             item["UrlCheckPassed"] = False
    #             item["Response"] = "Redirected to {}".format(retUrl)
    #         else:
    #             item["UrlCheckPassed"] = True
    #             item["Response"] = checkUrl.url
    #
    # except Exception, ex:
    #     item["UrlCheckPassed"] = False
    #     item["Response"] = ex.message
    return item

def exportAgentsJson(data, filepath, baseClass, currentListPath):
    output = {}
    from multiprocessing import Pool

    pool = Pool(processes=10)  # Initalize a pool of 2 processes
    records = pool.map(parseUrlItem, data)
    pool.close()  # this means that no more tasks will be added to the pool
    pool.join()  # this blocks the program till function is run on all the items

    # p = Pool(10)  # Pool tells how many at a time
    # p.terminate()
    # p.join()
    #
    for item in records:
        # if item['UrlCheckPassed']:
            output[item['JobSiteName']] = item
            output[item['JobSiteName']]["PluginExtendsClassName"] = baseClass
        # else:
        #     stritem = pprint.pformat(item)
        #     print "Skipping {} site agent due to error:\n {}".format(item['JobSiteName'], stritem)

    print "Writing {} agents to {}...".format(baseClass, filepath)
    helpers.write_json(filepath, output.values())
    print "{} agents written to {}:{}".format(len(data), filepath, ", ".join(output.keys()))

    mergeNewAgents(currentListPath, output)

def mergeNewAgents(sourceFile, data):

    fp = codecs.open(sourceFile)
    src = json.load(fp)

    keys = [x['JobSiteName'].lower() for x in src['jobsite_plugins']]
    for rec in data.values():
        if rec['JobSiteName'].lower() not in keys:
            src['jobsite_plugins'].append(rec)

    outpath = sourceFile.replace(".json", ".new.json")
    print "Writing merge agent list to {}...".format(outpath)
    helpers.write_json(outpath, src)
    print "{} agents written to {}".format(len(src['jobsite_plugins']), outpath)


if __name__ == '__main__':
    getJobviteAgents()