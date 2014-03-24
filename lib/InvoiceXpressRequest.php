<?php
require_once('XmlDomConstruct.php');
/**
 * A simple PHP API wrapper for the InvoiceXpress API.
 * All post vars can be found on the developer site: http://en.invoicexpress.com/api/
 * Stay up to date on Github: https://github.com/nunomorgadinho/InvoiceXpressRequest-PHP-API
 *
 * PHP version 5
 *
 * @author     Nuno Morgadinho <nuno@widgilabs.com>
 * @license    MIT
 * @version    1.0
 */
class InvoiceXpressRequestException extends Exception {}
class InvoiceXpressRequest {
	/*
	 * The domain you need when making a request
	*/
	protected static $_domain = '';
	
	/*
	 * The API token you need when making a request
	*/
	protected static $_token = '';
	
	/*
	 * The API url we're hitting. {{ DOMAIN }} will get replaced with $domain
	* when you set InvoiceXpressRequest::init($domain, $token)
	*/
	protected $_api_url = 'https://{{ DOMAIN }}.invoicexpress.net/{{ CLASS }}.xml';
	
	/*
	 * Stores the current method we're using. Example:
	* new InvoiceXpressRequest('client.create'), 'client.create' would be the method
	*/
	protected $_method = '';
	
	/*
	 * Any arguments to pass to the request
	*/
	protected $_args = array();
	
	/*
	 * Determines whether or not the request was successful
	*/
	protected $_success = false;
	
	/*
	 * Holds the error returned from our request
	*/
	protected $_error = '';
	
	/*
	 * Holds the response after our request
	*/
	protected $_response = array();
	
	/*
	 * Initialize the and store the domain/token for making requests
	*
	* @param string $domain The subdomain like 'yoursite'.freshbooks.com
	* @param string $token The token found in your account settings area
	* @return null
	*/
	public static function init($domain, $token)
	{
		self::$_domain = $domain;
		self::$_token = $token;
	}
	
	/*
	 * Set up the request object and assign a method name
	*
	* @param array $method The method name from the API, like 'client.update' etc
	* @return null
	*/
	public function __construct($method)
	{
		$this->_method = $method;
	}
	
	/*
	 * Set the data/arguments we're about to request with
	*
	* @return null
	*/
	public function post($data)
	{
		$this->_args = $data;
	}
	
	/*
	 * Determine whether or not it was successful
	*
	* @return bool
	*/
	public function success()
	{
		return $this->_success;
	}
	
	/*
	 * Get the error (if there was one returned from the request)
	*
	* @return string
	*/
	public function getError()
	{
		return $this->_error;
	}
	
	/*
	 * Get the response from the request we made
	*
	* @return array
	*/
	public function getResponse()
	{
		return $this->_response;
	}
	
	/*
	 * Get the generated XML to view. This is useful for debugging
	 * to see what you're actually sending over the wire. Call this
	 * after $ie->post() but before your make your $ie->request()
	 *
	 * @return array
	 */
	public function getGeneratedXML()
	{
	
		$dom = new XmlDomConstruct('1.0', 'utf-8');
		$dom->fromMixed($this->_args);
		$post_data = $dom->saveXML();
	
		return $post_data;
	
	}
	
    /**
     * invoiceMethods
     *
     * Handle all Invoice & Simplified invoices requests
     *
     * 
     * @param bool      $ch         cURL Handle
     * @param array     $class      InvoiceXpress Method to run exploded
     * @param string    $url        Built URL so far      
     * @param int       $id         InvoiceXpress invoice ID
     * 
     * @return  string
     */
    public function invoiceMethods($ch, $class, $url, $id) {

        switch ($class[1]) {
            case 'create':
            case 'list':
                $url = str_replace('{{ CLASS }}', $class[0], $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                break;
            case 'change-state':
            case 'email-invoice':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $url = str_replace('{{ CLASS }}', $class[0] . "/" . $id . "/" . $class[1], $url);
                break;
            case 'get':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', $class[0] . "/" . $id, $url);
                break;
            case 'update':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $url = str_replace('{{ CLASS }}', $class[0] . "/" . $id, $url);
                break;
        }

        $url .= "?api_key=" . self::$_token;


        return $url;
    }

    /**
     * clientMethods
     *
     * Handle all client requests
     *
     * 
     * @param bool      $ch         cURL Handle
     * @param array     $class      InvoiceXpress Method to run exploded
     * @param string    $url        Built URL so far      
     * @param int       $id         InvoiceXpress invoice ID
     * @param string    $extra      Special case usage for adding Extra parameter GET before API_KEY
     * 
     * @return  string
     */
    public function clientMethods($ch, $class, $url, $id, $extra = '') {

        switch ($class[1]) {
            case 'create':
            case 'list':
                $url = str_replace('{{ CLASS }}', $class[0], $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                $url .= "?api_key=" . self::$_token;
                break;
            case 'get':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', "clients/" . $id, $url);
                $url .= "?api_key=" . self::$_token;
                break;
            case 'update':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $url = str_replace('{{ CLASS }}', "clients/" . $id, $url);
                $url .= "?api_key=" . self::$_token;
                break;
            case 'invoices':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', "clients/" . $id . "/" . $class[1], $url);
                $url .= "?api_key=" . self::$_token;
                break;
            case 'find-by-name':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', "clients/" . $class[1], $url);
                $url .= "?client_name=" . $extra . "&api_key=" . self::$_token;
                break;
            case 'find-by-code':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $url = str_replace('{{ CLASS }}', "clients/" . $class[1], $url);
                $url .= "?client_code=" . $extra . "&api_key=" . self::$_token;
                break;
            case 'create-invoice':
            case 'create-cash-invoice':
            case 'create-credit-note':
            case 'create-debit-note':
                list($before, $after) = explode('-', $class[1], 2);
                $url = str_replace('{{ CLASS }}', "clients/" . $id . "/" . $before . "/" . $after, $url);
                $url .= "?api_key=" . self::$_token;
                break;
        }


        return $url;
    }

    /**
     * request
     *
     * Send the request over the wire
     *
     * 
     * @param int       $id         InvoiceXpress invoice ID
     * @param array     $extra      Special case usage for adding Extra parameter GET before API_KEY (ex: https://:screen-name.invoicexpress.net/clients/find-by-code.xml?client_code=Ni+Hao&API_KEY=XXX)
     * 
     * 
     * @return  array
     */
    public function request($id = '') {
        if (!self::$_domain || !self::$_token) {
            throw new InvoiceXpressRequestException('You need to call InvoiceXpressRequest::init($domain, $token) with your domain and token.');
        }

        $post_data = $this->getGeneratedXML();
        $p = print_r($post_data, true);
        error_log("post = " . $p);

        $url = str_replace('{{ DOMAIN }}', self::$_domain, $this->_api_url);

        $class = explode(".", $this->_method);

        $ch = curl_init();    // initialize curl handle
        //Filter correct method to run and return $url
        switch ($class[0]) {
            case 'invoices':
            case 'simplified_invoices':
                $url = $this->invoiceMethods($ch, $class, $url, $id);
                break;
            case 'clients':
                $url = $this->clientMethods($ch, $class, $url, $id, $extra);
                break;
        }

        error_log("URL = " . $url);
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s
        if ($class[1] != "get")
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // add POST fields
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml; charset=utf-8"));

        $result = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->_error = 'A cURL error occured: ' . curl_error($ch);
            return;
        } else {
            curl_close($ch);
        }

        // if weird simplexml error then you may have the a user with
        // a user_meta wc_ie_client_id defined that not exists in InvoiceXpress
        if ($result && $result != " ") {
            $res = print_r($result, true);
            error_log("result = {" . $res . "}");

            $response = json_decode(json_encode(simplexml_load_string($result)), true);
            $r = print_r($response, true);
            error_log("response = " . $r);

            $this->_response = $response;
        }

        $this->_success = (($http_status == '201 Created') || ($http_status == '200 OK'));
        error_log("http status = " . $http_status);

        if (isset($response['error'])) {
            $this->_error = $response['error'];
        }
    }
}
