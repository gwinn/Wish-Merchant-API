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

namespace Wish;

use Wish\Exception\UnauthorizedRequestException;
use Wish\Exception\ServiceResponseException;
use Wish\Exception\OrderAlreadyFulfilledException;
use Wish\Model\WishProduct;
use Wish\Model\WishProductVariation;
use Wish\Model\WishOrder;
use Wish\Model\WishTracker;
use Wish\Model\WishTicket;
use Wish\Model\WishAddress;

/**
 * Class WishClient
 *
 * @package Wish
 */
class WishClient
{

    private $session;

    const LIMIT = 50;

    /**
     * WishClient constructor.
     *
     * @param        $access_token
     * @param string $session_type
     * @param null   $merchant_id
     */
    public function __construct($access_token, $session_type='prod', $merchant_id = null)
    {
        $this->session = new WishSession($access_token, $session_type, $merchant_id);
    }

    /**
     * @param       $type
     * @param       $path
     * @param array $params
     *
     * @return WishResponse
     */
    public function getResponse($type, $path, $params = [])
    {

        $request = new WishRequest($this->session, $type, $path, $params);
        $response = $request->execute();

        if ($response->getStatusCode() == 4000) {
            throw new UnauthorizedRequestException("Unauthorized access", $request, $response);
        }

        if ($response->getStatusCode() == 1015) {
            throw new UnauthorizedRequestException("Access Token expired", $request, $response);
        }

        if ($response->getStatusCode() == 1016) {
            throw new UnauthorizedRequestException("Access Token revoked", $request, $response);
        }

        if ($response->getStatusCode() == 1000) {
            throw new ServiceResponseException("Invalid parameter", $request, $response);
        }

        if ($response->getStatusCode() == 1002) {
            throw new OrderAlreadyFulfilledException("Order has been fulfilled", $request, $response);
        }

        if ($response->getStatusCode() != 0) {
            throw new ServiceResponseException("Unknown error", $request, $response);
        }

        return $response;
    }

    /**
     * @param       $method
     * @param       $uri
     * @param       $getClass
     * @param array $params
     *
     * @return array
     */
    public function getResponseIter($method, $uri, $getClass, $params = [])
    {
        $start = 0;
        $params['limit'] = static::LIMIT;
        $class_arr = [];

        do {
        $params['start'] = $start;
        $response = $this->getResponse($method, $uri, $params);

        foreach($response->getData() as $class_raw) {
            $class_arr[] = new $getClass($class_raw);
        }

        $start += static::LIMIT;

        } while($response->hasMore());

        return $class_arr;
    }

    /**
     * @return string
     */
    public function authTest()
    {
        $response = $this->getResponse('GET', 'auth_test');

        return $response->getData();
    }

    // PRODUCT

    /**
     * @param $id
     *
     * @return WishProduct
     */
    public function getProductById($id)
    {
        $params = ['id' => $id];
        $response = $this->getResponse('GET', 'product', $params);

        return new WishProduct($response->getData());
    }

    /**
     * @param $object
     *
     * @return WishProduct
     */
    public function createProduct($object)
    {
        $response = $this->getResponse('POST', 'product/add', $object);

        return new WishProduct($response->getData());
    }

    /**
     * @param WishProduct $product
     *
     * @return string
     */
    public function updateProduct(WishProduct $product)
    {
        $params = $product->getParams([
          'id',
          'name',
          'description',
          'tags',
          'brand',
          'landing_page_url',
          'upc',
          'main_image',
          'extra_images'
        ]);

        $response = $this->getResponse('POST', 'product/update', $params);

        return $response->getData();
    }

    /**
     * @param WishProduct $product
     */
    public function enableProduct(WishProduct $product)
    {
        $this->enableProductById($product->id);
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function enableProductById($id)
    {
        $params = ['id' => $id];
        $response = $this->getResponse('POST', 'product/enable', $params);

        return $response->getData();
    }

    /**
     * @param WishProduct $product
     */
    public function disableProduct(WishProduct $product)
    {
        $this->disableProductById($product->id);
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function disableProductById($id)
    {
        $params = ['id' => $id];
        $response = $this->getResponse('POST', 'product/disable', $params);

        return $response->getData();
    }

    /**
     * @return array
     */
    public function getAllProducts()
    {
        return $this->getResponseIter(
            'GET',
            'product/multi-get',
            "Wish\Model\WishProduct"
        );
    }

    /**
     * @param WishProduct $product
     *
     * @return string
     */
    public function removeExtraImages(WishProduct $product)
    {
        return $this->removeExtraImagesById($product->id);
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function removeExtraImagesById($id)
    {
        $params = ['id' => $id];
        $response = $this->getResponse('POST', 'product/remove-extra-images', $params);

        return $response->getData();
    }

    /**
     * @param $id
     * @param $country
     * @param $price
     *
     * @return string
     */
    public function updateShippingById($id, $country, $price)
    {
        $params = ['id' => $id, 'country' => $country, 'price' => $price];
        $response = $this->getResponse('POST', 'product/update-shipping', $params);

        return $response->getData();
    }

    /**
     * @param $id
     * @param $country
     *
     * @return string
     */
    public function getShippingById($id, $country)
    {
        $params = ['id' => $id, 'country' => $country];
        $response = $this->getResponse('GET', 'product/get-shipping', $params);

        return json_encode($response->getData());
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function getAllShippingById($id)
    {
        $params = ['id' =>$id];
        $response = $this->getResponse('GET', 'product/get-all-shipping', $params);

        return json_encode($response->getData());
    }

    // PRODUCT VARIATION

    /**
     * @param $object
     *
     * @return WishProductVariation
     */
    public function createProductVariation($object)
    {
        $response = $this->getResponse('POST', 'variant/add', $object);

        return new WishProductVariation($response->getData());
    }

    /**
     * @param $sku
     *
     * @return WishProductVariation
     */
    public function getProductVariationBySKU($sku)
    {
        $response = $this->getResponse('GET', 'variant', ['sku' => $sku]);

        return new WishProductVariation($response->getData());
    }

    /**
     * @param WishProductVariation $var
     *
     * @return string
     */
    public function updateProductVariation(WishProductVariation $var)
    {
        $params = $var->getParams([
            'sku',
            'inventory',
            'price',
            'shipping',
            'enabled',
            'size',
            'color',
            'msrp',
            'shipping_time',
            'main_image'
        ]);

        $response = $this->getResponse('POST', 'variant/update', $params);

        return $response->getData();
    }

    /**
     * @param $sku
     * @param $new_sku
     *
     * @return string
     */
    public function changeProductVariationSKU($sku, $new_sku)
    {
        $params = ['sku' => $sku, 'new_sku' => $new_sku];
        $response = $this->getResponse('POST', 'variant/change-sku', $params);

        return $response->getData();
    }

    /**
     * @param WishProductVariation $var
     */
    public function enableProductVariation(WishProductVariation $var)
    {
        $this->enableProductVariationBySKU($var->sku);
    }

    /**
     * @param $sku
     *
     * @return string
     */
    public function enableProductVariationBySKU($sku)
    {
        $params = ['sku' => $sku];
        $response = $this->getResponse('POST', 'variant/enable', $params);

        return $response->getData();
    }

    /**
     * @param WishProductVariation $var
     */
    public function disableProductVariation(WishProductVariation $var)
    {
        $this->disableProductVariationBySKU($var->sku);
    }

    /**
     * @param $sku
     *
     * @return string
     */
    public function disableProductVariationBySKU($sku)
    {
        $params = ['sku' => $sku];
        $response = $this->getResponse('POST', 'variant/disable', $params);

        return $response->getData();
    }

    /**
     * @param $sku
     * @param $newInventory
     *
     * @return string
     */
    public function updateInventoryBySKU($sku, $newInventory)
    {
        $params = ['sku' => $sku, 'inventory' => $newInventory];
        $response = $this->getResponse('POST', 'variant/update-inventory', $params);

        return $response->getData();
    }

    /**
     * @return array
     */
    public function getAllProductVariations()
    {
        return $this->getResponseIter('GET', 'variant/multi-get', "Wish\Model\WishProductVariation");
    }

    // ORDER

    /**
     * @param $id
     *
     * @return WishOrder
     */
    public function getOrderById($id)
    {
        $response = $this->getResponse('GET', 'order', ['id' => $id]);

        return new WishOrder($response->getData());
    }

    /**
     * @param null $time
     *
     * @return array
     */
    public function getAllChangedOrdersSince($time = null)
    {
        $params = [];
        if ($time) {
            $params['since'] = $time;
        }

        return $this->getResponseIter('GET', 'order/multi-get', "Wish\Model\WishOrder", $params);
    }

    /**
     * @param null $time
     *
     * @return array
     */
    public function getAllUnfulfilledOrdersSince($time = null)
    {
    $params = [];
    if($time){
      $params['since']=$time;
    }
    return $this->getResponseIter(
      'GET',
      'order/get-fulfill',
      "Wish\Model\WishOrder",
      $params);
    }

    /**
     * @param             $id
     * @param WishTracker $tracking_info
     *
     * @return string
     */
    public function fulfillOrderById($id, WishTracker $tracking_info)
    {
        $params = $tracking_info->getParams();
        $params['id'] = $id;
        $response = $this->getResponse('POST', 'order/fulfill-one', $params);

        return $response->getData();
    }

    /**
     * @param WishOrder   $order
     * @param WishTracker $tracking_info
     *
     * @return string
     */
    public function fulfillOrder(WishOrder $order, WishTracker $tracking_info)
    {
        return $this->fulfillOrderById($order->order_id, $tracking_info);
    }

    /**
     * @param      $id
     * @param      $reason
     * @param null $note
     *
     * @return string
     */
    public function refundOrderById($id, $reason, $note = null)
    {
        $params = [
            'id' => $id,
            'reason_code' => $reason
        ];

        if ($note) {
            $params['reason_note'] = $note;
        }

        $response = $this->getResponse('POST', 'order/refund', $params);

        return $response->getData();
    }

    /**
     * @param WishOrder $order
     * @param           $reason
     * @param null      $note
     *
     * @return mixed
     */
    public function refundOrder(WishOrder $order, $reason, $note = null)
    {
        return refundOrderById($order->order_id, $reason, $note);
    }

    /**
     * @param WishOrder   $order
     * @param WishTracker $tracker
     *
     * @return string
     */
    public function updateTrackingInfo(WishOrder $order, WishTracker $tracker)
    {
        return $this->updateTrackingInfoById($order->order_id, $tracker);
    }

    /**
     * @param             $id
     * @param WishTracker $tracker
     *
     * @return string
     */
    public function updateTrackingInfoById($id, WishTracker $tracker)
    {
        $params = $tracker->getParams();
        $params['id'] = $id;
        $response = $this->getResponse('POST', 'order/modify-tracking', $params);

        return $response->getData();
    }

    /**
     * @param WishOrder   $order
     * @param WishAddress $address
     *
     * @return string
     */
    public function updateShippingInfo(WishOrder $order, WishAddress $address)
    {
        return $this->updateShippingInfoById($order->order_id, $address);
    }

    /**
     * @param             $id
     * @param WishAddress $address
     *
     * @return string
     */
    public function updateShippingInfoById($id, WishAddress $address)
    {
        $params = $address->getParams();
        $params['id'] = $id;
        $response = $this->getResponse('POST', 'order/change-shipping', $params);

        return $response->getData();
    }

    // TICKET

    /**
     * @param $id
     *
     * @return WishTicket
     */
    public function getTicketById($id)
    {
        $params['id'] = $id;
        $response = $this->getResponse('GET', 'ticket', $params);

        return new WishTicket($response->getData());
    }

    /**
     * @return array
     */
    public function getAllActionRequiredTickets()
    {
        return $this->getResponseIter('GET', 'ticket/get-action-required', "Wish\Model\WishTicket");
    }

    /**
     * @param $id
     * @param $reply
     *
     * @return string
     */
    public function replyToTicketById($id, $reply)
    {
        $params['id'] = $id;
        $params['reply'] = $reply;
        $response = $this->getResponse('POST', 'ticket/reply', $params);

        return $response->getData();
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function closeTicketById($id)
    {
        $params['id'] = $id;
        $response = $this->getResponse('POST', 'ticket/close', $params);

        return $response->getData();
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function appealTicketById($id)
    {
        $params['id'] = $id;
        $response = $this->getResponse('POST', 'ticket/appeal-to-wish-support', $params);

        return $response->getData();
    }

    /**
     * @param $id
     * @param $reply
     *
     * @return string
     */
    public function reOpenTicketById($id, $reply)
    {
        $params['id'] = $id;
        $params['reply'] = $reply;
        $response = $this->getResponse('POST', 'ticket/re-open', $params);

        return $response->getData();
    }

    // NOTIFICATION
    /**
     * @return mixed
     */
    public function getAllNotifications()
    {
        $response = $this->getResponse('GET', 'noti/fetch-unviewed');

        return $response->getData();
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function markNotificationAsViewed($id)
    {
        $params['id'] = $id;
        $response = $this->getResponse('POST', 'noti/mark-as-viewed', $params);

        return $response->getData();
    }

    /**
     * @return mixed
     */
    public function getUnviewedNotiCount()
    {
        $response = $this->getResponse('GET', 'noti/get-unviewed-count');

        return $response->getData();
    }

    /**
     * @return mixed
     */
    public function getBDAnnouncemtns()
    {
        $response = $this->getResponse('GET', 'fetch-bd-announcement');

        return $response->getData();
    }


    /**
     * @return mixed
     */
    public function getSystemUpdatesNotifications()
    {
        $response = $this->getResponse('GET', 'fetch-sys-updates-noti');

        return $response->getData();
    }

    /**
     * @return mixed
     */
    public function getInfractionCount()
    {
         $response = $this->getResponse('GET', 'count/infractions');

         return $response->getData();
    }

    /**
     * @return mixed
     */
    public function getInfractionLinks()
    {
         $response = $this->getResponse('GET', 'get/infractions');

         return $response->getData();
    }

    /**
     * @return mixed
     */
    public function getShippingProviders()
    {
        $response = $this->getResponse('GET', 'get-shipping-carriers');

        return $response->getData();
    }
}
