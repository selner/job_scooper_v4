<?php
/**
 * Copyright 2014-18 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the 'License'); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace JobScooper\DataAccess;

use JobScooper\DataAccess\Base\UserQuery as BaseUserQuery;
use JobScooper\DataAccess\Map\UserTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\TableMap;

/**
 * Skeleton subclass for performing query and update operations on the 'user' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class UserQuery extends BaseUserQuery
{

    /**
     *
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return User|null
     *
     */
    public static function getUserByUserSlug($slug)
    {
        if (empty($slug)) {
            throw new \Exception('Unable to search for user by user slug.  Missing required user slug parameter.');
        }

        $user = UserQuery::create()
            ->filterByUserSlug($slug)
            ->findOne();

        return $user;
    }

    /**
     *
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return User|null
     *
     */
    public static function findOrCreateUserByUserSlug($slug, $arrUserFactsToSet = array(), $overwriteFacts = false)
    {
        if (empty($slug)) {
            throw new \Exception('Unable to search for user by user slug.  Missing required user slug parameter.');
        }

        $user = UserQuery::create()
            ->filterByUserSlug($slug)
            ->findOneOrCreate();

        if ($user->isNew() || $overwriteFacts === true) {
            UserQuery::updateUserFacts($user, $arrUserFactsToSet);
            $user->setUserSlug($slug);
        }
        $user->save();

        return $user;
    }

    /**
     * @param       $email_address
     * @param array $arrUserFactsToSet
     * @param bool  $overwriteFacts
     *
     * @throws \Exception
     * @return User|null
     */
    public static function findUserByEmailAddress($email_address, $arrUserFactsToSet = array(), $overwriteFacts = false)
    {
        $retUser = null;
        try {
            // Search for this email address in the database
            // sort by the last created record first so that
            // we return the most recent match if multiple exist
            $data = UserQuery::create()
                ->filterByEmailAddress($email_address)
                ->orderByUserId(Criteria::DESC)
                ->find()
                ->getData();

            if (empty($data)) {
                return null;
            }

            if (countAssociativeArrayValues($data) > 1) {
                $retUser = $data[count($data) - 1];
            } else {
                $retUser = $data[0];
            }

            if ($retUser->isNew() || $overwriteFacts === true) {
                UserQuery::updateUserFacts($retUser, $arrUserFactsToSet);
            }

            $retUser->save();

            return $retUser;
        } catch (\Exception $ex) {
            // No user found
            return null;
        }
    }

    /**
     * @param \JobScooper\DataAccess\User $user
     * @param                             $arrUserFactsToSet
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public static function updateUserFacts(User $user, $arrUserFactsToSet)
    {
        if (!empty($arrUserFactsToSet) && is_array($arrUserFactsToSet)) {
            $user->fromArray($arrUserFactsToSet);
        } else {
            $thisKeys = UserTableMap::getFieldNames(TableMap::TYPE_PHPNAME);
            foreach ($arrUserFactsToSet as $keyNew => $valNew) {
                if (array_key_exists($keyNew, $thisKeys) && !empty(call_user_func_array([$user, 'get' . $keyNew], null))) {
                    call_user_func_array([$user, 'set' . $keyNew], $valNew);
                }
            }
        }
    }
}
