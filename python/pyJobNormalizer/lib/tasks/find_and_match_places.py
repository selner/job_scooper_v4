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
from ..database import DatabaseMixin
import requests
MAX_RETRIES = 3

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


class FindPlacesFromDBLocationsTask(DatabaseMixin):
    dbparams = {}
    _geoloc_columns = None

    _location_mapping = {}

    def update_all_locations(self, **kwargs):

        self.init_connection(**kwargs)

        self._geoloc_columns = self.get_table_columns("geolocation")

        self.load_known_locations()

        self.update_job_posting_locations(kwargs["server"])

    def load_known_locations(self):
        print("Connecting to database...")

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

        result = self.fetch_all_from_query(querysql)

        for val in result:
            self._location_mapping[val['Location']] = {'GeoLocationId': val['GeoLocationId'],
                                                       'DisplayName': val['DisplayName']}

        print("Loaded {} known location mappings from DB")

    def update_job_posting_locations(self, geocode_server):

        print("Getting locations needing lookup/update...")

        querysql = """
            SELECT 
                location as `Location`
            FROM 
                jobposting 
            WHERE 
                jobposting.geolocation_id IS NULL 
            GROUP BY location
            ORDER BY location
            """

        result = self.fetch_all_from_query(querysql)
        locs_needing_setting = set([l["Location"] for l in result if l["Location"] in self._location_mapping.keys()])

        if len(locs_needing_setting) > 0:
            self._update_missing_db_known_locs(locs_needing_setting)

        locs_needing_lookup = set([l["Location"] for l in result if l["Location"] not in self._location_mapping.keys()])
        if len(locs_needing_lookup) > 0:
            self._lookup_unknown_locations(locs_needing_lookup, geocode_server)

    def _update_mappings_for_loc(self, loc, locfacts):
        updateStatement = """
            UPDATE jobposting
            SET 
                geolocation_id={},
                location_display_value='{}'
            WHERE 
                location='{}' AND 
                geolocation_id IS NULL
            """.format(locfacts['GeoLocationId'], locfacts['DisplayName'], loc)

        rows_updated = self.run_command(updateStatement, close_connection=False)
        print("Updated {} rows missing information for '{} ({})'".format(rows_updated, loc,
                                                                         unicode(locfacts)))
        return rows_updated

    def _update_missing_db_known_locs(self, locs):
        print("Updating '{}' known locations who are missing geolocation details in the database...".format(len(locs)))
        total_updated = 0

        try:

            for loc in locs:
                rows_updated = self._update_mappings_for_loc(loc, self._location_mapping[loc])
                total_updated += rows_updated

        except Exception as e:
            print("Exception occurred:{}".format(e))
            raise e

        finally:
            self.close_connection()
            print(
                "Updated {} job postings in the database that were missing location details for {} known locations.".format(
                    total_updated, len(locs)))


    def call_geocode_api(self, **kwargs):
        results = None
        r = None
        retries = 0

        if 'retry_count' in kwargs:
            retries = kwargs['retry_count']

        try:
            r = requests.get(**kwargs)
            if r.status_code == requests.codes.ok:
                return r.json()
            else:
                r.raise_for_status()

        except requests.exceptions.Timeout, t:
            if retries < MAX_RETRIES:
                print("Warning:  API request '{}' timed out on retry #.   Retrying {} more times...".format(r.url, MAX_RETRIES - retries))
                retries += 1
                kwargs['retry_count'] = retries
                self.call_geocode_api(**kwargs)
            else:
                print("ERROR:  API request '{}' timed out and has exceeded max retry count.".format(r.url))
                raise t
            pass
        except Exception, ex:
            print("ERROR:  API request '{}' failed:  ".format(r.url), str(ex))
            raise ex

        finally:
            r = None

    def _lookup_unknown_locations(self, locs, geocode_server):
        print("Finding places for '{}' unknown locations ...".format(
            len(locs)))
        total_loc_found = 0
        total_loc_notfound = 0
        total_jp_updated = 0
        r = None

        try:
            for loc in locs:
                # Do place lookup
                place_details = {}

                payload = {'query': loc}
                # headers = {'content-type': 'application/json'}
                headers = {}
                print("Looking up place location '{}".format(loc))
                kwargs = {
                    'url': "{}/places/lookup".format(geocode_server),
                    'params':  payload,
                    'headers': headers,
                    'timeout': 30
                }
                print("... calling API '{}".format(kwargs['url']))
                place_details = self.call_geocode_api(**kwargs)
                print("... place returned from API: {}".format(unicode(place_details)))

                if place_details and len(place_details) > 0:   # if found place:

                    #   insert GeoLocation into DB
                    geolocfacts = { PLACE_DETAIL_GEOCODE_MAPPING[pkey]: unicode(place_details[pkey]) for pkey in
                                   place_details.keys() if pkey in PLACE_DETAIL_GEOCODE_MAPPING.keys() and
                                   place_details[pkey] is not None
                                  }
                    if geolocfacts and len(geolocfacts) > 0:
                        print("... inserting new geolocation = {}".format(unicode(geolocfacts)))

                        ins_result = self.add_row("geolocation", "geolocation_id", geolocfacts, self._geoloc_columns)
                        print("... newly inserted geolocation record = {}".format(unicode(ins_result)))

                        total_loc_found += 1

                        #   add the new loc to the known location mappings list
                        locfacts = {'GeoLocationId': ins_result['geolocation_id'],
                                    'DisplayName': ins_result['display_name']}
                        self._location_mapping[loc] = locfacts

                        #   update location mappings for loc
                        rows_updated = self._update_mappings_for_loc(loc, locfacts)
                        total_jp_updated += rows_updated
                    else:
                        print("TODO TODO TODO -- store zero results lookups like '{}' to skip future searches".format(loc))
                        total_loc_notfound += 1
                    # if not found place:
                    #   add to failed lookups dataset
                else:
                    print("TODO TODO TODO -- store failed lookups like '{}' to skip future failures".format(loc))
                    total_loc_notfound += 1

        except Exception as e:
            print("Exception occurred:{}".format(e))
            raise e

        finally:
            self.close_connection()
            print(
                "Found places for {} / {} locations; could not find {} / {} locations.  Updated {} job postings based "
                "on new locations found.".format(
                    total_loc_found, len(locs), total_loc_notfound, len(locs), total_jp_updated))
