<?php
/**
 * UserAvatarsApi
 * PHP version 5
 *
 * @category Class
 * @package  Swagger\Client
 * @author   http://github.com/swagger-api/swagger-codegen
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link     https://github.com/swagger-api/swagger-codegen
 */

/**
 * user-avatar
 *
 * No descripton provided (generated by Swagger Codegen https://github.com/swagger-api/swagger-codegen)
 *
 * OpenAPI spec version: 0.1.44-SNAPSHOT
 * 
 * Generated by: https://github.com/swagger-api/swagger-codegen.git
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace Swagger\Client\User\Avatars\Api;

use \Swagger\Client\Configuration;
use \Swagger\Client\ApiClient;
use \Swagger\Client\ApiException;
use \Swagger\Client\ObjectSerializer;

/**
 * UserAvatarsApi Class Doc Comment
 *
 * @category Class
 * @package  Swagger\Client
 * @author   http://github.com/swagger-api/swagger-codegen
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link     https://github.com/swagger-api/swagger-codegen
 */
class UserAvatarsApi
{

    /**
     * API Client
     *
     * @var \Swagger\Client\ApiClient instance of the ApiClient
     */
    protected $apiClient;

    /**
     * Constructor
     *
     * @param \Swagger\Client\ApiClient|null $apiClient The api client to use
     */
    public function __construct(\Swagger\Client\ApiClient $apiClient = null)
    {
        if ($apiClient == null) {
            $apiClient = new ApiClient();
            $apiClient->getConfig()->setHost('https://localhost/user-avatar');
        }

        $this->apiClient = $apiClient;
    }

    /**
     * Get API client
     *
     * @return \Swagger\Client\ApiClient get the API client
     */
    public function getApiClient()
    {
        return $this->apiClient;
    }

    /**
     * Set the API client
     *
     * @param \Swagger\Client\ApiClient $apiClient set the API client
     *
     * @return UserAvatarsApi
     */
    public function setApiClient(\Swagger\Client\ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        return $this;
    }

    /**
     * Operation deleteUserAvatar
     *
     * Delete the users avatar
     *
     * @param string $user_id  (required)
     * @return void
     * @throws \Swagger\Client\ApiException on non-2xx response
     */
    public function deleteUserAvatar($user_id)
    {
        list($response) = $this->deleteUserAvatarWithHttpInfo($user_id);
        return $response;
    }

    /**
     * Operation deleteUserAvatarWithHttpInfo
     *
     * Delete the users avatar
     *
     * @param string $user_id  (required)
     * @return Array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws \Swagger\Client\ApiException on non-2xx response
     */
    public function deleteUserAvatarWithHttpInfo($user_id)
    {
        // verify the required parameter 'user_id' is set
        if ($user_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $user_id when calling deleteUserAvatar');
        }
        // parse inputs
        $resourcePath = "/user/{userId}/avatar";
        $httpBody = '';
        $queryParams = array();
        $headerParams = array();
        $formParams = array();
        $_header_accept = $this->apiClient->selectHeaderAccept(array('application/json'));
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(array());

        // path params
        if ($user_id !== null) {
            $resourcePath = str_replace(
                "{" . "userId" . "}",
                $this->apiClient->getSerializer()->toPathValue($user_id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // this endpoint requires API key authentication
        $apiKey = $this->apiClient->getApiKeyWithPrefix('X-Wikia-AccessToken');
        if (strlen($apiKey) !== 0) {
            $headerParams['X-Wikia-AccessToken'] = $apiKey;
        }
        // this endpoint requires API key authentication
        $apiKey = $this->apiClient->getApiKeyWithPrefix('X-Wikia-UserId');
        if (strlen($apiKey) !== 0) {
            $headerParams['X-Wikia-UserId'] = $apiKey;
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'DELETE',
                $queryParams,
                $httpBody,
                $headerParams,
                null,
                '/user/{userId}/avatar'
            );

            return array(null, $statusCode, $httpHeader);
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }

            throw $e;
        }
    }

    /**
     * Operation getAvatarForUser
     *
     * Get an user avatar
     *
     * @param string $user_id  (required)
     * @param string $if_none_match  (optional)
     * @return void
     * @throws \Swagger\Client\ApiException on non-2xx response
     */
    public function getAvatarForUser($user_id, $if_none_match = null)
    {
        list($response) = $this->getAvatarForUserWithHttpInfo($user_id, $if_none_match);
        return $response;
    }

    /**
     * Operation getAvatarForUserWithHttpInfo
     *
     * Get an user avatar
     *
     * @param string $user_id  (required)
     * @param string $if_none_match  (optional)
     * @return Array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws \Swagger\Client\ApiException on non-2xx response
     */
    public function getAvatarForUserWithHttpInfo($user_id, $if_none_match = null)
    {
        // verify the required parameter 'user_id' is set
        if ($user_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $user_id when calling getAvatarForUser');
        }
        // parse inputs
        $resourcePath = "/user/{userId}/avatar";
        $httpBody = '';
        $queryParams = array();
        $headerParams = array();
        $formParams = array();
        $_header_accept = $this->apiClient->selectHeaderAccept(array('application/json'));
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(array());

        // header params
        if ($if_none_match !== null) {
            $headerParams['If-None-Match'] = $this->apiClient->getSerializer()->toHeaderValue($if_none_match);
        }
        // path params
        if ($user_id !== null) {
            $resourcePath = str_replace(
                "{" . "userId" . "}",
                $this->apiClient->getSerializer()->toPathValue($user_id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // this endpoint requires API key authentication
        $apiKey = $this->apiClient->getApiKeyWithPrefix('X-Wikia-AccessToken');
        if (strlen($apiKey) !== 0) {
            $headerParams['X-Wikia-AccessToken'] = $apiKey;
        }
        // this endpoint requires API key authentication
        $apiKey = $this->apiClient->getApiKeyWithPrefix('X-Wikia-UserId');
        if (strlen($apiKey) !== 0) {
            $headerParams['X-Wikia-UserId'] = $apiKey;
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'GET',
                $queryParams,
                $httpBody,
                $headerParams,
                null,
                '/user/{userId}/avatar'
            );

            return array(null, $statusCode, $httpHeader);
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }

            throw $e;
        }
    }

    /**
     * Operation postUserAvatar
     *
     * Creates an avatar for a user
     *
     * @param string $user_id  (required)
     * @param \SplFileObject $file  (optional)
     * @return \Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult
     * @throws \Swagger\Client\ApiException on non-2xx response
     */
    public function postUserAvatar($user_id, $file = null)
    {
        list($response) = $this->postUserAvatarWithHttpInfo($user_id, $file);
        return $response;
    }

    /**
     * Operation postUserAvatarWithHttpInfo
     *
     * Creates an avatar for a user
     *
     * @param string $user_id  (required)
     * @param \SplFileObject $file  (optional)
     * @return Array of \Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult, HTTP status code, HTTP response headers (array of strings)
     * @throws \Swagger\Client\ApiException on non-2xx response
     */
    public function postUserAvatarWithHttpInfo($user_id, $file = null)
    {
        // verify the required parameter 'user_id' is set
        if ($user_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $user_id when calling postUserAvatar');
        }
        // parse inputs
        $resourcePath = "/user/{userId}/avatar";
        $httpBody = '';
        $queryParams = array();
        $headerParams = array();
        $formParams = array();
        $_header_accept = $this->apiClient->selectHeaderAccept(array('application/json'));
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(array('multipart/form-data'));

        // path params
        if ($user_id !== null) {
            $resourcePath = str_replace(
                "{" . "userId" . "}",
                $this->apiClient->getSerializer()->toPathValue($user_id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // form params
        if ($file !== null) {
            // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
            // See: https://wiki.php.net/rfc/curl-file-upload
            if (function_exists('curl_file_create')) {
                $formParams['file'] = curl_file_create($this->apiClient->getSerializer()->toFormValue($file));
            } else {
                $formParams['file'] = '@' . $this->apiClient->getSerializer()->toFormValue($file);
            }
        }
        
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // this endpoint requires API key authentication
        $apiKey = $this->apiClient->getApiKeyWithPrefix('X-Wikia-AccessToken');
        if (strlen($apiKey) !== 0) {
            $headerParams['X-Wikia-AccessToken'] = $apiKey;
        }
        // this endpoint requires API key authentication
        $apiKey = $this->apiClient->getApiKeyWithPrefix('X-Wikia-UserId');
        if (strlen($apiKey) !== 0) {
            $headerParams['X-Wikia-UserId'] = $apiKey;
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'POST',
                $queryParams,
                $httpBody,
                $headerParams,
                '\Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult',
                '/user/{userId}/avatar'
            );

            return array($this->apiClient->getSerializer()->deserialize($response, '\Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult', $httpHeader), $statusCode, $httpHeader);
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }

    /**
     * Operation putUserAvatar
     *
     * Create an avatar for a user, potentially overwriting any existing one
     *
     * @param string $user_id  (required)
     * @param \SplFileObject $file  (optional)
     * @return \Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult
     * @throws \Swagger\Client\ApiException on non-2xx response
     */
    public function putUserAvatar($user_id, $file = null)
    {
        list($response) = $this->putUserAvatarWithHttpInfo($user_id, $file);
        return $response;
    }

    /**
     * Operation putUserAvatarWithHttpInfo
     *
     * Create an avatar for a user, potentially overwriting any existing one
     *
     * @param string $user_id  (required)
     * @param \SplFileObject $file  (optional)
     * @return Array of \Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult, HTTP status code, HTTP response headers (array of strings)
     * @throws \Swagger\Client\ApiException on non-2xx response
     */
    public function putUserAvatarWithHttpInfo($user_id, $file = null)
    {
        // verify the required parameter 'user_id' is set
        if ($user_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $user_id when calling putUserAvatar');
        }
        // parse inputs
        $resourcePath = "/user/{userId}/avatar";
        $httpBody = '';
        $queryParams = array();
        $headerParams = array();
        $formParams = array();
        $_header_accept = $this->apiClient->selectHeaderAccept(array('application/json'));
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(array('multipart/form-data'));

        // path params
        if ($user_id !== null) {
            $resourcePath = str_replace(
                "{" . "userId" . "}",
                $this->apiClient->getSerializer()->toPathValue($user_id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // form params
        if ($file !== null) {
            // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
            // See: https://wiki.php.net/rfc/curl-file-upload
            if (function_exists('curl_file_create')) {
                $formParams['file'] = curl_file_create($this->apiClient->getSerializer()->toFormValue($file));
            } else {
                $formParams['file'] = '@' . $this->apiClient->getSerializer()->toFormValue($file);
            }
        }
        
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // this endpoint requires API key authentication
        $apiKey = $this->apiClient->getApiKeyWithPrefix('X-Wikia-AccessToken');
        if (strlen($apiKey) !== 0) {
            $headerParams['X-Wikia-AccessToken'] = $apiKey;
        }
        // this endpoint requires API key authentication
        $apiKey = $this->apiClient->getApiKeyWithPrefix('X-Wikia-UserId');
        if (strlen($apiKey) !== 0) {
            $headerParams['X-Wikia-UserId'] = $apiKey;
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'PUT',
                $queryParams,
                $httpBody,
                $headerParams,
                '\Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult',
                '/user/{userId}/avatar'
            );

            return array($this->apiClient->getSerializer()->deserialize($response, '\Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult', $httpHeader), $statusCode, $httpHeader);
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\User\Avatars\Models\UserAvatarCreatedResult', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }

}
