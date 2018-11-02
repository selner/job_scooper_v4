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

from helpers import loadcsv
import nltk
import string
from nltk.stem.snowball import SnowballStemmer
from nltk.corpus import stopwords
import os
import re
from collections import OrderedDict
import unidecode

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

#
#  expanded_words_list
#
#  Load the job title abbreviations into memory from file
#
filepath = os.path.dirname(os.path.abspath(__file__))  # /a/b/c/d/e
abbrevfile = os.path.join(filepath, "static", "job-title-abbreviations.csv")
expanded_words_list = loadcsv(abbrevfile, "abbreviation")['dict']


class Tokenizer:
    _expandwords = None

    def __init__(self):
        self.exclude = string.punctuation
        self.snowstemmer = SnowballStemmer("english")
        self.stopwrds = stopwords.words('english')
        self.mktgterms = [
            'new',
            'apply now',
            'apply',
            'easy apply'
            'send resume'
        ]


    def remove_stop_words(self, listwords):
        """
        Args:
            listwords:
        """
        retwords = [i for i in listwords if i not in self.stopwrds]
        return retwords

    def remove_marketing_only_terms(self, listwords):
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

    def replace_punctuation(self, value, replace_with=" "):
        s = ""
        for ch in str(value):
            if ch in self.exclude:
                s += replace_with
            else:
                s += ch
        return s

    def clean_string(self, value, remove_punct=True):
        str_words = value
        if not isinstance(str_words, str):
            str_words = str(value)
            if len(str_words) <= 0:
                return str_words

        str_words = unidecode.unidecode(str_words)
        # for a given token, remove any unicode chars that
        # can't translate to ascii.  This handles scrubbing out
        # the myriad of dash variants, etc from the token
        removeuni = str_words.encode('ascii', 'ignore')
        str_words = removeuni.decode()

        if remove_punct is True:
            str_words = self.replace_punctuation(str_words, " ")

        str_words = re.sub(r' {1,}', ' ', str_words)

        str_words = str_words.strip()

        return str_words


    @property
    def expandedwords(self):
        global expanded_words_list

        return expanded_words_list

    def get_expanded_words(self, str_words):
        """
        Args:
            str_words:
        """
        ret_words = []

        words = nltk.word_tokenize(str_words)
        for i in words:

            # for a given token, remove any unicode chars that
            # can't translate to ascii.  This handles scrubbing out
            # the myriad of dash variants, etc from the token
            removeuni = i.encode('ascii', 'ignore')
            loweri = removeuni.decode().strip().lower()

            if len(loweri) == 0 or loweri.isalnum() is not True:
                continue

            if loweri in self.expandedwords:
                ret_words.append(self.expandedwords[loweri]['expansion'])
            else:
                ret_words.append(loweri)

        ret_words = nltk.word_tokenize(" ".join(ret_words))
        return ret_words

    def get_tokens_from_string(self, value):
        """
        Args:
            value:
        """
        if not value:
            return ""
        str_noabbrev = self.get_expanded_words(value)
        nostop_tokens = self.remove_stop_words(str_noabbrev)
        stemmed_tokens = self.get_stemmed_words(nostop_tokens)

        return stemmed_tokens


    def tokenize_string(self, value):
        """
        Args:
            value
        """

        if not value or len(value) == 0:
            return ""

        tokens = self.get_tokens_from_string(value)
        tokens = list(set(tokens))
        tokens.sort()

        return tokens

    def batch_tokenize_strings(self, list_data, field, field_tokenized="tokenized", ret_type="string"):
        """
        Args:
            list_data:
            field:
            field_tokenized:
            ret_type:
        """

        for k in list_data.keys():
            if isinstance(k, str) and len(k) == 0:
                print("String value for key was empty.  Skipping...")
                continue

            fieldval = self.clean_string(list_data[k][field])
            list_data[k][field] = fieldval
            tokens = self.tokenize_string(fieldval)
            final_tokens = []

            if ret_type == "list":
                final_tokens = list(set(tokens))
            elif ret_type == "set":
                final_tokens = set(tokens)
            elif ret_type == "dict":
                final_tokens = OrderedDict(zip(tokens, tokens))
            else:
                # if ret_type == "string" or ret_type is None:
                final_tokens = "|{}|".format("|".join(tokens))

            list_data[k][field_tokenized] = final_tokens
        return list_data

    def filter_to_alphanum(self, value):
        if value and len(value) > 0:
            return re.sub(r'\W+', '', str(value))
        return ""

