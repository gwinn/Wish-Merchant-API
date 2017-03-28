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
 * Class WishProductVariation
 *
 * @package Wish\Model
 */
class WishProductVariation
{

    /**
     * WishProductVariation constructor.
     *
     * @param $variant
     */
    public function __construct($variant)
    {

        $v = $variant->Variant;
        $vars = get_object_vars($v);

        foreach ($vars as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * @param $keys
     *
     * @return array
     */
    public function getParams($keys)
    {
        $params = [];

        foreach($keys as $key) {
            if(isset($this->$key)) {
                $params[$key] = $this->$key;
            }
        }

        return $params;
    }
}
