<?php
/**
 * Copyright 2014 Wish.com, ContextLogic or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Wish\Model;

/**
 * Class WishAddress
 *
 * @package Wish\Model
 */
class WishAddress
{
    /**
     * WishAddress constructor.
     *
     * @param $addr1
     * @param $addr2
     * @param $city
     * @param $state
     * @param $zip
     * @param $country
     * @param $phone
     */
    public function __construct($addr1, $addr2, $city, $state, $zip, $country, $phone)
    {
        $this->street_address1 = $addr1;
        $this->street_address2 = $addr2;
        $this->city = $city;
        $this->state = $state;
        $this->zipcode = $zip;
        $this->country = $country;
        $this->phone_number = $phone;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        $params['street_address1'] = $this->street_address1;
        $params['street_address2'] = $this->street_address2;
        $params['city'] = $this->city;
        $params['state'] = $this->state;
        $params['zipcode'] = $this->zipcode;
        $params['country'] = $this->country;
        $params['phone_number'] = $this->phone_number;

        return $params;
    }
}
