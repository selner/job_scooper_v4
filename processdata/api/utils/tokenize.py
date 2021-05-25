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


import os
import string
from collections import OrderedDict
import nltk
from nltk.stem.snowball import SnowballStemmer
from nltk.corpus import stopwords
from api.utils.files import loadcsv
from api.utils.logger import logmsg

STATES = {
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

STATECODES = { STATES[k]: k for k in STATES}

# NOTE:  Need to run the download once per machine to get the dictionaries
# nltk.download()

#
#  expanded_words_list
#
#  Load the job title abbreviations into memory from file
#
filepath = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # /a/b/c/d/e
abbrevfile = os.path.join(filepath, "static", "job-title-abbreviations.csv")
expanded_words_list = loadcsv(abbrevfile, "abbreviation")['dict']


class Tokenizer:
    _expandwords = None

    def __init__(self):
        self.exclude = string.punctuation
        self.snowstemmer = SnowballStemmer("english")
        self.stopwrds = stopwords.words('english')

    def batch_tokenize_strings(self, list_data, field, field_tokenized="tokenized", ret_type="string", maxlength=None):
        """
        Args:
            list_data:
            field: string
            field_tokenized: string
            ret_type: string
            :type field_tokenized: object
        """

        for k in list(list_data.keys()):
            if isinstance(k, str) and len(k) == 0:
                logmsg("String value for key was empty.  Skipping...")
                continue

            tokens = self.tokenize_string(list_data[k][field])
            final_tokens = []

            if maxlength and tokens:
                fieldval = "|{}|".join(tokens)
                while(len(fieldval) >= maxlength):
                    tokens.reverse()
                    tokens.pop()
                    tokens.reverse()
                    fieldval = "|{}|".join(tokens)

            if ret_type == "list":
                final_tokens = list(set(tokens))
            elif ret_type == "set":
                final_tokens = set(tokens)
            elif ret_type == "dict":
                final_tokens = OrderedDict(zip(tokens, tokens))
            else:
                # if ret_type == "string" or ret_type is None:
                final_tokens = f'|{"|".join(tokens)}|'


            list_data[k][field_tokenized] = final_tokens
        return list_data

    def tokenize_string(self, value):
        """
        Args:
            value
        """

        if not value or not isinstance(value, str) or len(value) == 0:
            return ""

        tokens = self.get_tokens_from_string(value)
        tokens = list(set(tokens))
        tokens.sort()

        return tokens

    def remove_stop_words(self, listwords):
        """
        Args:
            listwords:
        """
        return [i for i in listwords if i not in self.stopwrds]

    def get_stemmed_words(self, listwords):
        """
        Args:
            listwords:
        """
        return [self.snowstemmer.stem(i) for i in listwords]

    def replace_punctuation(self, value, replace_with=" "):
        s = ""
        for ch in str(value):
            if ch in self.exclude:
                s += replace_with
            else:
                s += ch
        return s

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

        if not isinstance(str_words, str):
            str_words = str(str_words)
        if len(str_words) <= 0:
            return ret_words

        s = self.replace_punctuation(str_words, " ")

        words = nltk.word_tokenize(s)
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

        return self.get_stemmed_words(nostop_tokens)

    def get_tokens_from_dbvalue(self, val):
        """
        Args:
            value:
        """
        if not val:
            return []

        dbtokensval = str(val)
        dbtokens = dbtokensval.split("_||_")

        return dbtokens

    def get_dbcolumn_val_for_tokens(self, tokens):
        return f'|{"_||_".join(tokens)}|'

