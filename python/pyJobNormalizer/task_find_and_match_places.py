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
from mixin_database import DatabaseMixin
from urllib.parse import urlencode
import requests
MAX_RETRIES = 1
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
from helpers import simpleuni


class FindPlacesFromDBLocationsTask(DatabaseMixin):
    _geoloc_columns = None

    _location_mapping = {}

    def __init__(self, **kwargs):
        """
        Args:
            **kwargs:
        """

        DatabaseMixin.__init__(self, **kwargs)

    def update_all_locations(self, **kwargs):

        """
        Args:
            **kwargs:
        """
        self._geoloc_columns = self.get_table_columns("geolocation")

        self.load_known_locations()

        self.update_job_posting_locations(kwargs["server"])

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

        self.log(u"Getting known locations from DB...")
        result = self.fetch_all_from_query(querysql)

        for val in result:
            self._location_mapping[val['Location']] = {'GeoLocationId': val['GeoLocationId'],
                                                       'DisplayName': val['DisplayName']}

        self.log(u"Loaded {} known location mappings from DB".format(len(self._location_mapping)))

    def update_job_posting_locations(self, geocode_server):

        """
        Args:
            geocode_server:
        """
        self.log(u"Getting locations needing lookup/update...")

        querysql = u"""
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
        locs_needing_setting = set(
            [l["Location"] for l in result if l["Location"] and l["Location"] in self._location_mapping.keys() and
             len(str(l["Location"]).strip()) > 0 ])

        if len(locs_needing_setting) > 0:
            self._update_missing_db_known_locs(locs_needing_setting)

        locs_needing_lookup = set(
            [l["Location"] for l in result if l["Location"] and l["Location"] not in self._location_mapping.keys()])
        if len(locs_needing_lookup) > 0:
            self._lookup_unknown_locations(locs_needing_lookup, geocode_server)

    def _update_mappings_for_loc(self, loc, locfacts):
        """
        Args:
            loc:
            locfacts:
        """
        statement = u"""
            UPDATE jobposting
            SET 
                geolocation_id={},
                location_display_value='{}'
            WHERE 
                location='{}' AND 
                geolocation_id IS NULL
            """.format(locfacts['GeoLocationId'], self.connection.escape_string(locfacts['DisplayName']), self.connection.escape_string(loc))

        rows_updated = self.run_command(statement, close_connection=False)
        self.log(u"Updated {} rows missing information for '{} ({})'".format(rows_updated, loc,
                                                                          str(locfacts)))
        return rows_updated

    def _update_missing_db_known_locs(self, locs):
        """
        Args:
            locs:
        """
        self.log(u"Updating {} known locations who are missing geolocation details in the database...".format(len(locs)))
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
                u"Updated {} job postings in the database that were missing location details for {} known locations.".format(
                    total_updated, len(locs)))

    def call_geocode_api(self, **kwargs):
        """
        Args:
            **kwargs:
        """
        results = None
        r = None
        retries = 0

        if 'retry_count' in kwargs:
            retries = kwargs['retry_count']
            del(kwargs['retry_count'])

        try:
            r = requests.get(**kwargs)
            if r.status_code == requests.codes.ok:
                data = r.json()
                import json
                return json.loads(simpleuni(json.dumps(data)))
            else:
                r.raise_for_status()

        except requests.exceptions.Timeout as t:
            url = "unknown_api_url"
            if hasattr(r, 'url', ):
                url = r.url

            if retries < MAX_RETRIES:
                self.log(u"Warning:  API request '{}' timed out on retry #.   Retrying {} more times...".format(url,
                                                                                                             MAX_RETRIES - retries))
                retries += 1
                kwargs['retry_count'] = retries
                return self.call_geocode_api(**kwargs)
            else:
                from logging import ERROR
                msg = u"ERROR:  API request '{}' timed out and has exceeded max retry count.".format(url)
                self.handle_error(Exception(msg))

            pass
        except Exception as ex:
            url = "unknown_api_url"
            if hasattr(r, 'url', ):
                url = r.url

            msg = u"ERROR:  API request '{}' failed:  ".format(url), str(ex)
            self.handle_error(ex, msg)

        finally:
            r = None

    def _lookup_unknown_locations(self, locs, geocode_server):
        """
        Args:
            locs:
            geocode_server:
        """
        print(u"Finding places for {} unknown locations ...".format(
            len(locs)))
        total_loc_found = 0
        total_loc_notfound = 0
        total_jp_updated = 0
        r = None

        try:
            for l in locs:
                loc = simpleuni(str(l)).strip()
                if len(loc) <= 0:
                    continue

                # Do place lookup
                place_details = {}

                payload = {'query': loc}
                # headers = {'content-type': 'application/json'}
                headers = {}

                print(u"Looking up place for location search value '{}'".format(loc))
                kwargs = {
                    'url': u"{}/places/lookup".format(geocode_server),
                    'params': payload,
                    'headers': headers,
                    'timeout': 30
                }

                print(u"... calling API '{}?{}".format(kwargs['url'], urlencode(payload)))

                place_details = self.call_geocode_api(**kwargs)
                if place_details and len(place_details) > 0:  # if found place:

                    msgPlaceMatch = " place_id=None "
                    if 'place_id' in place_details and place_details['place_id']:
                        msgPlaceMatch = " place_id={} ".format(place_details['place_id'])

                    print(u"... place returned from API: {}, location={}".format(msgPlaceMatch,
                                                                                          place_details[
                                                                                              'formatted_address']))

                    #   insert GeoLocation into DB
                    geolocfacts = {PLACE_DETAIL_GEOCODE_MAPPING[pkey]: str(place_details[pkey]) for pkey in
                                   place_details.keys() if pkey in PLACE_DETAIL_GEOCODE_MAPPING.keys() and
                                   place_details[pkey] is not None
                                   }
                    if geolocfacts and len(geolocfacts) > 0:
                        # print(u"... inserting new geolocation = {}".format(dump_var_to_json(geolocfacts)))

                        if 'geolocation_key' in geolocfacts and len(geolocfacts['geolocation_key']) > 100:
                            geolocfacts['geolocation_key'] = geolocfacts['geolocation_key'][0:100]

                        if 'display_name' in geolocfacts and len(geolocfacts['display_name']) > 100:
                            geolocfacts['display_name'] = geolocfacts['display_name'][0:100]

                        query = """ 
                            SELECT
                                geolocation.geolocation_id as `GeoLocationId`,
                                geolocation.display_name as `DisplayName`
                            FROM geolocation
                            WHERE geolocation_key like '{}'
                            ORDER by geolocation.geolocation_id
                        """.format(geolocfacts['geolocation_key'])

                        existingGeo = self.fetch_one_from_query(query)
                        if existingGeo is not None:
                            print(u"... found existing geolocation_id {} for '{}'.  Using it instead." .format(existingGeo['GeoLocationId'], existingGeo['DisplayName']))
                            locfacts = {'GeoLocationId': existingGeo['GeoLocationId'],
                                        'DisplayName': existingGeo['DisplayName']}
                        else:
                            ins_result = self.add_row("geolocation", "geolocation_id", geolocfacts, self._geoloc_columns)
                            # print(u"... newly inserted geolocation record = {}".format(dump_var_to_json(ins_result)))

                            total_loc_found += 1
                            locfacts = {'GeoLocationId': ins_result['geolocation_id'],
                                        'DisplayName': ins_result['display_name']}

                        #   add the new loc to the known location mappings list
                        self._location_mapping[loc] = locfacts

                        #   update location mappings for loc
                        rows_updated = self._update_mappings_for_loc(loc, locfacts)
                        total_jp_updated += rows_updated
                    else:
                        print(u"... place_id {} found for {} but could not be geocoded.".format(place_details['place_id'], str(loc)))
                        # print(u"... TODO -- store zero results lookups like '{}' to skip future searches".format(loc))
                        total_loc_notfound += 1
                    # if not found place:
                    #   add to failed lookups dataset
                else:
                    print(u"... place for {} not found via API".format(str(loc)))
                    # print(u"... TODO -- store failed lookups like '{}' to skip future failures".format(loc))
                    total_loc_notfound += 1

        except Exception as e:
            self.handle_error(e)

        finally:
            self.close_connection()
            print(
                u"Found places for {} / {} locations; could not find {} / {} locations.  Updated {} job postings based "
                u"on new locations found.".format(
                    total_loc_found, len(locs), total_loc_notfound, len(locs), total_jp_updated))
