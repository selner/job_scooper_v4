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
from collections import OrderedDict

from mixin_database import DatabaseMixin
import requests
from util_tokenize import STATES, STATECODES

MAX_RETRIES = 0
PLACE_DETAIL_GEOCODE_MAPPING = {
    "formatted_address": "display_name",
    "location_slug": "geolocation_key",
    "place": "place",
    "county": "county",
    "region": "region",
    "regioncode": "regioncode",
    "country": "country",
    "countrycode": "countrycode",
    "latitude": "latitude",
    "longitude": "longitude"
}
from helpers import simpleuni, clean_text_for_matching


class FindPlacesFromDBLocationsTask(DatabaseMixin):
    _geoloc_columns = None

    _location_mapping = {}
    _unknownlocs = {}
    _upperStates = {}

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

        for s in STATES.values():
            self._upperStates[s.upper()] = s

    def update_all_locations(self, **kwargs):

        """
        Args:
            **kwargs:
        """
        self._geoloc_columns = self.get_table_columns("geolocation")

        self.load_known_locations()
        self.load_unknown_locations()

        self.update_job_posting_locations(kwargs["server"])

    def load_unknown_locations(self):
        querysql = """
            SELECT 
                location
            FROM 
                jobposting 
            WHERE 
                geolocation_id IS NULL AND 
                first_seen_at < CURDATE() - 5
            GROUP BY 
                location
            ORDER BY location
            """
        self.log("Getting past unknown locations from DB...")
        result = self.fetch_all_from_query(querysql)

        for val in result:

            self._unknownlocs[val['location']] = True

        total = len(self._unknownlocs)
        self.log(f'Loaded {total} past unknown location mappings from DB')

    def load_known_locations(self):
        querysql = """
            SELECT 
                location as `Location`, 
                jobposting.geolocation_id as `GeoLocationId`,
                geolocation.display_name as `DisplayName`
            FROM 
                jobposting, geolocation 
            WHERE 
                jobposting.geolocation_id IS NOT NULL AND 
                jobposting.geolocation_id = geolocation.geolocation_id 
            GROUP BY 
                location, 
                jobposting.geolocation_id 
            ORDER BY location
            """

        self.log("Getting known locations from DB...")
        result = self.fetch_all_from_query(querysql)

        for val in result:
            self._location_mapping[val['Location']] = {'GeoLocationId': val['GeoLocationId'],
                                                       'DisplayName': val['DisplayName']}

        total = len(self._location_mapping)
        self.log(f'Loaded {total} known location mappings from DB')

    def update_job_posting_locations(self, geocode_server):

        """
        Args:
            geocode_server:
        """
        self.log("Getting locations needing lookup/update...")

        querysql = """
            SELECT 
                location as `Location`
            FROM 
                jobposting 
            WHERE 
                jobposting.geolocation_id IS NULL AND 
                location IS NOT NULL AND 
                location NOT LIKE '' AND 
                first_seen_at >= CURDATE() - 7
            GROUP BY location
            ORDER BY location
            """

        result = self.fetch_all_from_query(querysql)
        locs_needing_setting = {l["Location"] for l in result if
                 l["Location"] and l["Location"] in self._location_mapping.keys()}

        if locs_needing_setting:
            self._update_missing_db_known_locs(locs_needing_setting)

        locs_needing_lookup = []

        for l in result:
            if l["Location"] in self._unknownlocs.keys():
                self.log(f'Skipping location "{l["Location"]}" which failed geolocation in a prior run.')
            elif l["Location"] not in self._location_mapping.keys():

                # note:  needs to happen before cleaning location string so that "or" doesn't capture the
                #        statecode for Oregon ("OR") if possible
                conjuncts = ['or', 'and']
                hasconj = set(l["Location"].split(" ")).intersection(conjuncts)
                if not hasconj or len(hasconj) == 0:
                    locs_needing_lookup.append(l["Location"])
                else:
                    self.log(f'Skipping invalid location combination value {l["Location"]}')

        if locs_needing_lookup:
            self._lookup_unknown_locations(locs_needing_lookup, geocode_server)

    def cleanLocationString(self, val):

        ret = clean_text_for_matching(val)

        nonlocwords = {"Remote", "Based"}
        for nlw in nonlocwords:
            ret = ret.replace(nlw, "").replace("  ", " ")

        import re
        words = re.split(r'(\b)', ret)
        upwords = {str(l).upper() for l in words}

        statematches = upwords.intersection(self._upperStates)
        if statematches:
            for upstate in statematches:
                state = self._upperStates[upstate]
                ret = state + " US" if ret == state else ret.replace(state, STATECODES[state])
        ret = ret.strip()

        return ret

    def _update_mappings_for_loc(self, loc, locfacts):
        """
        Args:
            loc:
            locfacts:
        """
        statement = """
            UPDATE jobposting
            SET 
                geolocation_id=%s,
                location_display_value='%s'
            WHERE 
                location='%s' AND 
                geolocation_id IS NULL
            """ % (locfacts['GeoLocationId'], self.connection.escape_string(locfacts['DisplayName']),
                   self.connection.escape_string(loc))

        rows_updated = self.run_command(statement, close_connection=False)
        self.log(f'Updated {rows_updated} rows missing information for {loc} ({str(locfacts)})')
        return rows_updated

    def _update_missing_db_known_locs(self, locs):
        """
        Args:
            locs:
        """
        self.log(f'Updating {len(locs)} known locations who are missing geolocation details in the database...')
        total_updated = 0

        try:

            for loc in locs:
                rows_updated = self._update_mappings_for_loc(loc, self._location_mapping[loc])
                total_updated += rows_updated

        except Exception as e:
            self.handle_error(e)

        finally:
            self.close_connection()
            self.log(
                f'Updated {total_updated} job postings in the database that were missing location details for {len(locs)} known locations.')

    def call_geocode_api(self, **kwargs):
        """
        Args:
            **kwargs:
        """
        results = None
        r = None
        retries = 0
        url = 'unknown'
        if 'url' in kwargs:
            url = kwargs['url']

        if 'retry_count' in kwargs:
            retries = kwargs['retry_count']
            del (kwargs['retry_count'])

        try:
            r = requests.get(**kwargs)
            url = r.url
            if r.status_code == requests.codes.ok:
                data = r.json()
                import json
                return json.loads(simpleuni(json.dumps(data)))
            else:
                r.raise_for_status()

        except requests.exceptions.Timeout as t:
            if retries < MAX_RETRIES:
                self._logger.warning(
                    f'Warning:  API request {url} timed out on retry #.   Retrying {MAX_RETRIES - retries} more times...')

                retries += 1
                kwargs['retry_count'] = retries
                return self.call_geocode_api(**kwargs)
            else:
                from logging import ERROR
                msg = f'ERROR:  API request "{url}" timed out and has exceeded max retry count.'
                self.handle_error(Exception(msg))

            pass
        except Exception as ex:
            msg = f'ERROR:  API request "{url}" failed: {ex}'
            self.handle_error(ex, msg)

        finally:
            r = None

    def _lookup_unknown_locations(self, locs, geocode_server):
        """
        Args:
            locs:
            geocode_server:
        """
        self.log(f'Finding places for {len(locs)} unknown locations ...')

        total_loc_found = 0
        total_loc_notfound = 0
        total_jp_updated = 0
        r = None

        try:
            for l in locs:
                sourceloc = l
                loc = self.cleanLocationString(l)

                if len(loc) <= 0:
                    continue

                # Do place lookup
                place_details = {}

                payload = {'query': loc, 'localities_only': 1}
                # headers = {'content-type': 'application/json'}
                headers = {}

                self.log(f'Looking up place for location search value "{loc}"')
                kwargs = {
                    'url': f'{geocode_server}/city/lookup',
                    'params': payload,
                    'headers': headers,
                    'timeout': 30
                }

                self.log(f'... calling API {kwargs["url"]}?{payload}')

                place_details = None
                try:
                    place_details = self.call_geocode_api(**kwargs)
                except Exception as ex:
                    self.log(f'Geocode API call failed:  {ex}')
                    raise ex

                if place_details and len(place_details) > 0:  # if found place:

                    if 'place_id' in place_details and place_details['place_id']:
                        self.log(
                            f'... place matched: {place_details["place_id"]}, location={place_details["location_slug"]}')
                    else:
                        self.log(
                            f'... place matched: location={place_details["location_slug"]}')

                    #   insert GeoLocation into DB
                    geolocfacts = {PLACE_DETAIL_GEOCODE_MAPPING[pkey]: str(place_details[pkey])[:99] for pkey in
                                   place_details.keys() if pkey in PLACE_DETAIL_GEOCODE_MAPPING.keys() and
                                   place_details[pkey] is not None
                                   }
                    if geolocfacts and len(geolocfacts) > 0:
                        # self.log("... inserting new geolocation = {}".format(dump_var_to_json(geolocfacts)))

                        if 'location_slug' in geolocfacts and len(geolocfacts['location_slug']) > 100:
                            geolocfacts['location_slug'] = geolocfacts['location_slug'][0:100]

                        if 'display_name' not in geolocfacts:
                            factparts = OrderedDict()

                            if 'place' in geolocfacts and geolocfacts['place']:
                                factparts['place'] = geolocfacts['place']

                            if 'county' in geolocfacts and geolocfacts['county']:
                                factparts['county'] = geolocfacts['county']

                            if 'region' in geolocfacts and  geolocfacts['region']:
                                factparts['region'] = geolocfacts['region']
                            elif 'regioncode' in geolocfacts and geolocfacts['regioncode']:
                                factparts['regioncode'] = geolocfacts['regioncode']

                            if 'country' in geolocfacts and  geolocfacts['country']:
                                factparts['country'] = geolocfacts['country']
                            elif 'countrycode' in geolocfacts and geolocfacts['countrycode']:
                                factparts['countrycode'] = geolocfacts['countrycode']

                            geolocfacts['display_name'] = " ".join(factparts.values())

                        if len(geolocfacts['display_name']) > 100:
                            geolocfacts['display_name'] = geolocfacts['display_name'][0:100]

                        query = """ 
                            SELECT
                                geolocation.geolocation_id as `GeoLocationId`,
                                geolocation.display_name as `DisplayName`
                            FROM geolocation
                            WHERE geolocation_key like '%s'
                            ORDER by geolocation.geolocation_id
                        """ % (geolocfacts['geolocation_key'])


                        existingGeo = self.fetch_one_from_query(query)
                        if existingGeo is not None and len(existingGeo) > 0:
                            self.log(
                                f'... found existing geolocation_id {existingGeo["GeoLocationId"]} for {existingGeo["DisplayName"]}.  Using it instead.')
                            locfacts = {'GeoLocationId': existingGeo['GeoLocationId'],
                                        'DisplayName': existingGeo['DisplayName']}
                        else:
                            ins_result = self.add_row("geolocation", "geolocation_id", geolocfacts,
                                                      self._geoloc_columns)
                            # self.log("... newly inserted geolocation record = {}".format(dump_var_to_json(ins_result)))

                            total_loc_found += 1
                            locfacts = {'GeoLocationId': ins_result['geolocation_id'],
                                        'DisplayName': ins_result['display_name']}

                        #   add the new loc to the known location mappings list
                        self._location_mapping[sourceloc] = locfacts

                        #   update location mappings for loc
                        rows_updated = self._update_mappings_for_loc(sourceloc, locfacts)
                        total_jp_updated += rows_updated
                    else:
                        self.log(
                            f'... place_id {place_details["place_id"]} found for {str(loc)} but could not be geocoded.')
                        # print("... TODO -- store zero results lookups like '{}' to skip future searches".format(loc))
                        total_loc_notfound += 1
                    # if not found place:
                    #   add to failed lookups dataset
                else:
                    self.log(f'... place for {str(loc)} not found via API')
                    # print("... TODO -- store failed lookups like '{}' to skip future failures".format(loc))
                    total_loc_notfound += 1

        except Exception as e:
            self.handle_error(e)

        finally:
            self.close_connection()
            self.log(
                f'Found places for {total_loc_found} / {len(locs)} locations; could not find {len(locs) - total_loc_found} / {locs} locations.  Updated {total_jp_updated} job postings.')
