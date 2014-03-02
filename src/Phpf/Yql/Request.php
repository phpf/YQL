<?php
/**
 * @package Yql
 * @subpackage Request
 */

namespace Phpf\Yql;

use Exception;

class Request {
	
	public $baseUrl = 'http://query.yahooapis.com/v1/public/yql?q=';
	
	public $baseTableUrl = 'http://www.datatables.org/';
	
	public $env = 'store://datatables.org/alltableswithkeys';
	
	protected $diagnostics = false;
	
	protected $showErrors = false;
	
	protected $format = 'json';
	
	protected $query;
	
	protected $url;
	
	protected $response;
	
	protected $results;
	
	protected $execute;
	
	function __construct( $http_controller_callable ){
		
		if ( !is_callable($http_controller_callable) ){
			throw new Exception("Controller method must be callable.");
		}
		
		if ( $http_controller_callable instanceof Closure ){
			$this->execute = $http_controller_callable;
		} else {
			$this->execute = function ($url) use ($http_controller_callable){
				return call_user_func_array($http_controller_callable, array($url));
			};
		}
	}
	
	/**
	 *	Builds the YQL query and gets the response.
	 */
	public function execute( $query = null ){
		
		if ( !empty($query) ){
			$this->setQuery($query);
		}
		
		$this->url = $this->baseUrl .  urlencode($this->query) . "&format=" . $this->format;
		
		if ( $this->diagnostics ) {
			$this->url .= '&diagnostics=true';	
		}
		if ( !empty($this->env) ) {
			$this->url .= '&env=' . urlencode($this->env);
		}
		
		$reflect = new \Phpf\Util\Reflection\Callback($this->execute);
		
		$reflect->reflectParameters(array('url' => $this->url));
		
		$this->response = $reflect->invoke();
		
		return $this;
	}
	
	public function setEnv( $env ){
		$this->env = $env;
		return $this;
	}
	
	public function setDiagnostics( $val ){
		$this->diagnostics = (bool) $val;
		return $this;	
	}

	public function setShowErrors( $val ){
		$this->showErrors = (bool) $val;
		return $this;
	}
	
	public function setFormat( $format ){
		$format = strtolower($format);
		if ( 'json' === $format || 'xml' === $format )
			$this->format = $format;
		return $this;
	}
	
	public function setQuery( $query ){
		
		if ( $query instanceof Request\FluentInterface ) {
			$this->query = $query->__toString();
		} elseif ( !is_string($query) ){
			throw new Exception("Invalid YQL query - must be FluentInterface or string - " . gettype($query) . ' given.');
		} else {
			$this->query = $query;
		}
		
		return $this;
	}
	
	public function getQuery(){
		return $this->query;
	}
	
	public function getResponse(){
		return $this->response;
	}
	
	public function getResults( $part = 'body' ){
		
		if ( isset($this->results[$part]) )
			return $this->results[$part];
		
		if ( empty($this->response) )
			return null;
		
		if ( is_object($this->response) && isset($this->response->$part) ){
			$result = $this->response->$part;
		} else {
			$response = (array) $this->response;
			$result = $response[ $part ];
		}
		
		if ( 'body' === $part && 'json' === $this->format ){
				
			$phpObj = json_decode($result);
			
			if ( isset($phpObj->query->results) ){
				$results =& $phpObj->query->results;
			} elseif ( isset($phpObj->query) ){
				$results =& $phpObj->query;
			} else {
				$results =& $phpObj;
			}
		} else {
			$results =& $result;
		}
		
		if ( empty($results) )
			return $this->results[ $part ] = null;
		
		if ( is_string($results) )
			return $this->results[ $part ] = $results;
		
		if ( 'body' !== $part )
			return $this->results[ $part ] = $results;
		
		/*	Take 1st element of "results" (there is only ever 1). 
		*	This means we don't have to know the return element's name.
		* 	e.g. In "...->query->results->stats" this will get "stats"
		*/
		$results = (array) $results;
		return $this->results[ $part ] = array_shift($results);
	}
	
}
