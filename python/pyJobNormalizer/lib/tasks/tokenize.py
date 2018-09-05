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

from ..helpers import loadcsv, writedicttocsv
import nltk
import codecs
import string
from nltk.stem.snowball import SnowballStemmer
from nltk.corpus import stopwords
import os


states = {
        'AK': 'Alaska',
        'AL': 'Alabama',
        'AR': 'Arkansas',
        'AS': 'American Samoa',
        'AZ': 'Arizona',
        'CA': 'California',
        'CO': 'Colorado',
        'CT': 'Connecticut',
        'DC': 'District of Columbia',
        'DE': 'Delaware',
        'FL': 'Florida',
        'GA': 'Georgia',
        'GU': 'Guam',
        'HI': 'Hawaii',
        'IA': 'Iowa',
        'ID': 'Idaho',
        'IL': 'Illinois',
        'IN': 'Indiana',
        'KS': 'Kansas',
        'KY': 'Kentucky',
        'LA': 'Louisiana',
        'MA': 'Massachusetts',
        'MD': 'Maryland',
        'ME': 'Maine',
        'MI': 'Michigan',
        'MN': 'Minnesota',
        'MO': 'Missouri',
        'MP': 'Northern Mariana Islands',
        'MS': 'Mississippi',
        'MT': 'Montana',
        'NA': 'National',
        'NC': 'North Carolina',
        'ND': 'North Dakota',
        'NE': 'Nebraska',
        'NH': 'New Hampshire',
        'NJ': 'New Jersey',
        'NM': 'New Mexico',
        'NV': 'Nevada',
        'NY': 'New York',
        'OH': 'Ohio',
        'OK': 'Oklahoma',
        'OR': 'Oregon',
        'PA': 'Pennsylvania',
        'PR': 'Puerto Rico',
        'RI': 'Rhode Island',
        'SC': 'South Carolina',
        'SD': 'South Dakota',
        'TN': 'Tennessee',
        'TX': 'Texas',
        'UT': 'Utah',
        'VA': 'Virginia',
        'VI': 'Virgin Islands',
        'VT': 'Vermont',
        'WA': 'Washington',
        'WI': 'Wisconsin',
        'WV': 'West Virginia',
        'WY': 'Wyoming'
}


# NOTE:  Need to run the download once per machine to get the dictionaries
# nltk.download()


class Tokenizer:
    _expandwords = None

    def __init__(self):

        self.exclude = set(codecs.encode(string.punctuation, "utf-8"))
        self.snowstemmer = SnowballStemmer("english")
        self.stopwrds = stopwords.words('english')

    def tokenizeStrings(self, listData, field, field_tokenized="tokenized", ret_type="string"):
        """

        Args:
            listData:
            field:
            field_tokenized:
            ret_type:

        Returns:

        """

        for k in listData.keys():
            if isinstance(k, basestring) and len(k) == 0:
                print "String value for key was empty.  Skipping..."
                continue

            tokens = self.getScrubbedStringTokens(listData[k][field])
            sorted(tokens)

            if ret_type == "list":
                listData[k][field_tokenized] = tokens

            elif ret_type == "set":
                listData[k][field_tokenized] = set(tokens)
            else:
                # if ret_type == "string" or ret_type is None:
                listData[k][field_tokenized] = "|{}|".format("|".join(tokens))

        return listData

    def removeStopWords(self, listwords):
        """

        Args:
            listwords:

        Returns:

        """
        retwords = [i for i in listwords if i not in self.stopwrds]
        return retwords

    def getStemmedWords(self, listwords):
        """

        Args:
            listwords:

        Returns:

        """
        retwords = [self.snowstemmer.stem(i) for i in listwords]
        return retwords


    @property
    def expandedwords(self):
        if not self._expandwords:
            filepath = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # /a/b/c/d/e

            abbrevfile = os.path.join(filepath, "static", "job-title-abbreviations.csv")
            self._expandwords = loadcsv(abbrevfile, "abbreviation")['dict']

        return self._expandwords

    def getExpandedWords(self, strWords):
        """

        Args:
            strWords:

        Returns:

        """

        if not isinstance(strWords, basestring):
            strWords = str(strWords)
        assert (len(strWords) > 0)
        s = ''.join(ch for ch in strWords if ch not in self.exclude)

        retWords = []
        words = nltk.word_tokenize(s)
        for i in words:
            loweri = i.strip().lower()
            if loweri in self.expandedwords:
                retWords.append(self.expandedwords[loweri]['expansion'])
            else:
                retWords.append(loweri)

        retWords = nltk.word_tokenize(" ".join(retWords))
        return retWords

    def getScrubbedStringTokens(self, inputstring):
        """

        Args:
            inputstring:

        Returns:

        """
        if not inputstring:
            return ""
        str_noabbrev = self.getExpandedWords(inputstring)
        nostop_tokens = self.removeStopWords(str_noabbrev)
        stemmed_tokens = self.getStemmedWords(nostop_tokens)

        return stemmed_tokens

    def tokenizeFile(self, inputfile, outputfile, datakey=None, indexKey=None):
        """

        Args:
            inputfile:
            outputfile:
            datakey:
            indexKey:

        Returns:

        """
        if indexKey is None:
            indexKey = 0
        if datakey is None:
            datakey = 0

        data = loadcsv(inputfile, indexKey)
        fields = data['fieldnames']
        dictData = data['dict']
        dictStrings = {}
        # if (isinstance(dictData, dict) and len(dictData) > 0):
        #     for k, v in dictData.items():
        #         dictStrings[k] = v[datakey]
        # print k, v, "\n"
        # print v[datakey], "\n", "\n"
        #    listStrings = [k, v[datakey] for k, v in dictData.items()]
        tokenkey = str(datakey) + "tokenized"
        outData = self.tokenizeStrings(dictData, datakey, tokenkey)
        fields.append(tokenkey)
        writedicttocsv(outputfile, outData, fields)

        return outData
