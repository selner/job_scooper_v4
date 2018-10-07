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

from job_normalizer.utils.helpers import loadcsv
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

    def tokenize_strings(self, list_data, field, field_tokenized="tokenized", ret_type="string"):
        """
        Args:
            list_data:
            field:
            field_tokenized:
            ret_type:
        """

        for k in list_data.keys():
            if isinstance(k, basestring) and len(k) == 0:
                print "String value for key was empty.  Skipping..."
                continue

            tokens = self.get_scrubbed_string_tokens(list_data[k][field])
            sorted(tokens)

            if ret_type == "list":
                list_data[k][field_tokenized] = tokens

            elif ret_type == "set":
                list_data[k][field_tokenized] = set(tokens)
            else:
                # if ret_type == "string" or ret_type is None:
                list_data[k][field_tokenized] = "|{}|".format("|".join(tokens))

        return list_data

    def remove_stop_words(self, listwords):
        """
        Args:
            listwords:
        """
        retwords = [i for i in listwords if i not in self.stopwrds]
        return retwords

    def get_stemmed_words(self, listwords):
        """
        Args:
            listwords:
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

    def get_expanded_words(self, str_words):
        """
        Args:
            str_words:
        """

        if not isinstance(str_words, basestring):
            str_words = str(str_words)
        assert (len(str_words) > 0)
        s = ''.join(ch for ch in str_words if ch not in self.exclude)

        ret_words = []
        words = nltk.word_tokenize(s)
        for i in words:
            loweri = i.strip().lower()
            if loweri in self.expandedwords:
                ret_words.append(self.expandedwords[loweri]['expansion'])
            else:
                ret_words.append(loweri)

        ret_words = nltk.word_tokenize(" ".join(ret_words))
        return ret_words

    def get_scrubbed_string_tokens(self, inputstring):
        """
        Args:
            inputstring:
        """
        if not inputstring:
            return ""
        str_noabbrev = self.get_expanded_words(inputstring)
        nostop_tokens = self.remove_stop_words(str_noabbrev)
        stemmed_tokens = self.get_stemmed_words(nostop_tokens)

        return stemmed_tokens

