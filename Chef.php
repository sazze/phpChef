<?php

/**
 * Defines the Chef class
 *
 * A wrapper for the Chef HTTP API.
 *
 * PHP version 5
 *
 * @package framework.php
 * @author Daniel Aharon <daharon@sazze.com>
 * @license http://www.gnu.org/licenses/lgpl.html  LGPL
 * @copyright 2011 Sazze, Inc.
 */

namespace chef {

    class Chef {

        private $host;
        private $port;
        private $privateKey;
        private $userId;
        private $version;

        /**
         * Chef::__construct()
         *
         * Set the variables used for every request.
         *
         * @param string $hostname FQDN or IP address of the Chef server.
         * @param integer $port The port that the Chef server is listening on.
         * @param string $userId The client name associated with the private key.
         * @param string $privateKey Either the key itself, or the filename containing the key.
         * @param string $chefVersion The Chef server version.
         */
        public function __construct($host, $port, $userId, $privateKey, $chefVersion = '0.9.12') {

            $this->host = $host;
            $this->port = $port;
            $this->userId = $userId;
            $this->version = $chefVersion;

            if (file_exists($privateKey)) {
                $this->privateKey = file_get_contents($privateKey);
            } else {
                $this->privateKey = $privateKey;
            }
        }

        /**
         * Chef::factory()
         *
         * Return a Chef instance.
         *
         * @param string $hostname FQDN or IP address of the Chef server.
         * @param integer $port The port that the Chef server is listening on.
         * @param string $privateKey Either the key itself, or the filename containing the key.
         * @param string $userId The client name associated with the private key.
         * @param string $chefVersion The Chef server version.
         *
         * @return Chef An instance of the Chef class.
         */
        public static function factory($hostname, $port, $privateKey, $userId, $chefVersion = '0.9.12') {
            return new static($hostname, $port, $privateKey, $userId, $chefVersion);
        }

        /**
         * Chef::searchIndexes()
         *
         * @return array A list of the indexes available for search on the Chef server.
         */
        public function searchIndexes() {
            $uri = '/search';
            return $this->httpGet($uri);
        }

        /**
         * Chef::search()
         *
         * Search a Chef server index.
         *
         * @param string $indexName The index to search.
         * @param string $searchString A valid search string.  eg: 'recipes:"php::fpm"'
         * @param string $sort Sort the results by the specified attribute.
         * @param integer $start The result number to start from.
         * @param integer $rows How many rows to return.
         *
         * @return array The result set.
         */
        public function search($indexName, $searchString, $sort = '', $start = 0, $rows = 9999) {

            $uri = '/search/' . $indexName;
            $queryString = array(
                'q=' . urlencode($searchString),
                'rows=' . $rows
            );

            if ($start > 0) {
                $queryString[] = 'start=' . $start;
            }

            // Perform the search.
            $result = $this->httpGet($uri, join('&', $queryString));

            // Sort the results.
            if (!empty($sort)) {
                static::sortSearchResult($result, $sort);
            }

            return $result;
        }

        /**
         * Chef::getNodes()
         *
         * Retrieve the list of nodes serviced by the Chef server.
         *
         * @return array The list of nodes known to the Chef server.
         */
        public function getNodes() {
            $uri = '/nodes';
            return $this->httpGet($uri);
        }

        /**
         * Chef::getNode()
         *
         * Retrieve the information associated with a specific node.
         *
         * @param string $nodeName The name of the node.
         *
         * @return array An associative array containing all known info about the node.
         */
        public function getNode($nodeName) {
            $uri = '/nodes/' . $nodeName;
            return $this->httpGet($uri);
        }

        /**
         * Chef::getNodeRunList()
         *
         * Retrieve the run list of a specific node.
         *
         * @param string $nodeName The name of the node.
         *
         * @return array A list of the roles and recipes in the node's run list.
         */
        public function getNodeRunList($nodeName) {
            $uri = '/nodes/' . $nodeName . '/cookbooks';
            return $this->httpGet($uri);
        }

        /**
         * Chef::getRoles()
         *
         * Retrieve the list of roles.
         *
         * @return array The list of roles.
         */
        public function getRoles() {
            $uri = '/roles';
            return $this->httpGet($uri);
        }

        /**
         * Chef::getRole()
         *
         * Retrieve the specified role information.
         *
         * @param string $roleName The name of the role.
         *
         * @return array The roles and recipes contained in the specified role.
         */
        public function getRole($roleName) {
            $uri = '/roles/' . $roleName;
            return $this->httpGet($uri);
        }

        /**
         * Chef::getCookbooks()
         *
         * @return array A list of cookbooks.
         */
        public function getCookbooks() {
            $uri = '/cookbooks';
            return $this->httpGet($uri);
        }

        /**
         * Chef::getCookbook()
         *
         * Get the specified cookbook's data.
         *
         * @param string $cookbookName The name of the cookbook.
         *
         * @return array An array containing all the cookbook data.
         */
        public function getCookbook($cookbookName) {
            $uri = '/cookbooks/' . $cookbookName;
            return $this->httpGet($uri);
        }

        /**
         * Chef::getDataBags()
         *
         * Get a list of the data bags on the Chef server.
         *
         * @return array A list of the data bags on the Chef Server.
         */
        public function getDataBags() {
            $uri = '/data';
            return $this->httpGet($uri);
        }

        /**
         * Chef::getDataBagItems()
         *
         * Retrieve a list of the items in a data bag.
         *
         * @param string $dataBagName The name of the data bag.
         *
         * @return array A list of the items stored in the specified data bag.
         */
        public function getDataBagItems($dataBagName) {
            $uri = '/data/' . $dataBagName;
            return $this->httpGet($uri);
        }

        /**
         * Chef::deleteDataBagItem()
         *
         * Delete a data bag item.
         *
         * @param string $dataBagName The name of the data bag containing the item.
         * @param string $itemName The name of the data bag item.
         *
         * @return array The contents of the data bag item.
         */
        public function deleteDataBagItem($dataBagName, $itemName) {
            $uri = '/data/' . $dataBagName . '/' . $itemName;
            return $this->httpDelete($uri);
        }

        /**
         * Chef::getDataBagItem()
         *
         * Get the contents of a data bag item.
         *
         * @param string $dataBagName The name of the data bag containing the item.
         * @param string $itemID The ID of the data bag item.
         *
         * @return array The contents of the data bag item.
         */
        public function getDataBagItem($dataBagName, $itemID) {
            $uri = '/data/' . $dataBagName . '/' . $itemID;
            return $this->httpGet($uri);
        }
        
        /**
         * Chef::postDataBagItem()
         *
         * Creates a data bag item.
         *
         * @param string $dataBagName The name of the data bag containing the item.
         * @param array $itemContents The JSON formatted contents of the data bag item. Must contain an "id" field.
         *
         * @return array Response code
         */
        public function postDataBagItem($dataBagName, $itemContents) {
            $uri = '/data/'. $dataBagName;
            return $this->httpPost($uri, $itemContents);
        }

        /**
         * Chef::putDataBagItem()
         *
         * Updates the contents of a data bag item.
         *
         * @param string $dataBagName The name of the data bag containing the item.
         * @param string $itemID The ID of the data bag item.
         * @param array $itemContents The contents of the data bag item.
         *
         * @return array The contents of the data bag item.
         */
        public function putDataBagItem($dataBagName, $itemID, $itemContents) {
            $uri = '/data/'. $dataBagName . '/' . $itemID;
            return $this->httpPut($uri, $itemContents);
        }

        /**
         * Chef::httpDelete()
         *
         * Perform a DELETE request to the Chef server.
         *
         * @param string $uri The request URI.
         * @param string $queryString The query string.
         *
         * @return array An associative array generated from the JSON response.
         */
        private function httpDelete($uri) {
            $headers = $this->requestHeaders($uri, 'DELETE', '', $this->userId, $this->privateKey);
            $headers['X-Chef-Version'] = $this->version;
            $headers['Accept'] = 'application/json';

            $request = new \HttpRequest(
                'http://' . $this->host . ':' . $this->port . $uri,
                HTTP_METH_DELETE,
                array(
                    'headers' => $headers
                )
            );

            $response = $request->send();
            return json_decode($response->getBody(), true);
        }

        /**
         * Chef::httpGet()
         *
         * Perform a GET request to the Chef server.
         *
         * @param string $uri The request URI.
         * @param string $queryString The query string (for searching).
         *
         * @return array An associative array generated from the JSON response.
         */
        private function httpGet($uri, $queryString = '') {

            $headers = $this->requestHeaders($uri, 'GET', '', $this->userId, $this->privateKey);
            $headers['X-Chef-Version'] = $this->version;
            $headers['Accept'] = 'application/json';

            if (!empty($queryString)) {
                $uri .= '?' . $queryString;
            }

            $request = new \HttpRequest(
                'http://' . $this->host . ':' . $this->port . $uri,
                HTTP_METH_GET,
                array(
                    'headers' => $headers
                )
            );

            $response = $request->send();
            return json_decode($response->getBody(), true);
        }

        /**
         * Chef::httpPut()
         *
         * Perform a PUT request to the Chef server.
         *
         * @param string $uri The request URI.
         * @param string $queryString The query string.
         * @param array $requestBody A JSON object
         *
         * @return array An associative array generated from the JSON response.
         */
        private function httpPut($uri, $requestBody) {

            $headers['X-Chef-Version'] = $this->version;
            $headers = $this->requestHeaders($uri, 'PUT', $requestBody, $this->userId, $this->privateKey);
            $headers['Accept'] = 'application/json';

            $request = new \HttpRequest(
                'http://' . $this->host . ':' . $this->port . $uri,
                HTTP_METH_PUT,
                array(
                    'headers' => $headers
                )
            );
            $request->setContentType('application/json');
            $request->addPutData($requestBody);

            $response = $request->send();

            return json_decode($response->getBody(), true);
        }

        /**
         * Chef::httpPost()
         *
         * Perform a POST request to the Chef server.
         *
         * @param string $uri The request URI.
         * @param array $requestBody A JSON object
         *
         * @return array An associative array generated from the JSON response.
         */
        private function httpPost($uri, $requestBody) {

            $headers['X-Chef-Version'] = $this->version;
            $headers = $this->requestHeaders($uri, 'POST', $requestBody, $this->userId, $this->privateKey);
            $headers['Accept'] = 'application/json';

            $request = new \HttpRequest(
                'http://' . $this->host . ':' . $this->port . $uri,
                HTTP_METH_POST,
                array(
                    'headers' => $headers
                )
            );
            $request->setContentType('application/json');
            $request->addBody($requestBody);
            
            $response = $request->send();

            return json_decode($response->getBody(), true);
        }

        /**
         * Chef::requestHeaders()
         *
         * Generate the encrypted header entries for the Chef request.
         *
         * @param string $uri The request URI.
         * @param string $httpMethod The HTTP request method GET, or PUT.  PUT not implemented yet.
         * @param string $body The body of the request.
         * @param string $userId The Chef server client name.
         * @param string $privateKey The RSA private key for the client.
         *
         * @return array The headers specific to Chef server requests.
         */
        private static function requestHeaders($uri, $httpMethod, $body, $userId, $privateKey) {

            $timestamp = new \DateTime(null, new \DateTimeZone('UTC'));
            $timestamp = substr($timestamp->format(\DateTime::ISO8601), 0, -5) . 'Z';
            $hashedBody = base64_encode(sha1($body, true));

            $headers = array(
                'X-Ops-Sign' => 'version=1.0',
                'X-Ops-UserId' => $userId,
                'X-Ops-Timestamp' => $timestamp,
                'X-Ops-Content-Hash' => $hashedBody
            );

            $reqSig = static::requestSignature($uri, $httpMethod, $hashedBody, $timestamp, $userId, $privateKey);

            for ($index = 0; $index < strlen($reqSig); $index += 60) {
                $key = 'X-Ops-Authorization-' . (string) (($index / 60) + 1);
                $headers[$key] = substr($reqSig, $index, 60);
            }

            return $headers;
        }

        /**
         * Chef::requestSignature()
         *
         * @param string $uri The request URI.
         * @param string $httpMethod The HTTP request method GET, or PUT.  PUT not implemented yet.
         * @param string $hashedBody The hashed (base64 encoded SHA1) body of the request.
         * @param string $timestamp ISO 8601 format using T as the separator. The timezone must be UTC, using Z as the indicator.  eg:  2010-12-04T15:47:49Z
         * @param string $userId The Chef server client name.
         * @param string $privateKey The RSA private key for the client.
         */
        private static function requestSignature($uri, $httpMethod, $hashedBody, $timestamp, $userId, $privateKey) {

            $httpMethod = strtoupper($httpMethod);
            $hashedUri = base64_encode(sha1($uri, true));

            $reqSig =
                "Method:$httpMethod\n" .
                "Hashed Path:$hashedUri\n" .
                "X-Ops-Content-Hash:$hashedBody\n" .
                "X-Ops-Timestamp:$timestamp\n" .
                "X-Ops-UserId:$userId";

            openssl_private_encrypt($reqSig, $crypted, $privateKey);
            return base64_encode($crypted);
        }

        /**
         * Chef::arrayFlatten()
         *
         * Create a flattened copy of an array.
         *
         * @param array $arr The array to flatten. Duplicate keys will be overwritten.
         *
         * @return array A copy of the array that has been flattened.
         */
        private static function arrayFlatten(array $arr) {
            $output = array();

            array_walk_recursive(
                $arr,
                function($value, $key) use (&$output) {
                    $output[$key] = $value;
                }
            );

            return $output;
        }

        private static function arrayKeySearch($key, $arr) {

            $arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arr));

            foreach ($arrIt as $sub) {
                $subArray = $arrIt->getSubIterator();

                if (array_key_exists($key, $subArray)) {
                    return $subArray[$key];
                }
            }

            return null;
        }

        private static function sortSearchResult(&$arr, $sortBy) {

            $values = array();

            foreach ($arr['rows'] as $element) {
                $values[] = static::arrayKeySearch($sortBy, $element);
            }

            array_multisort($values, $arr['rows']);
        }

    }
}

?>
