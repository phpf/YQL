<?php

namespace Phpf\Yql;

class Response {
	
	public $query;
	
	public $raw;
	
	protected $request;
	
	public function __construct($data, Request $request) {
		
		$this->request = $request;
		
		if (is_object($data)) {
			
			if (isset($data->body) && 'json' === $this->request->format) {
				$body = json_decode($data->body);
				$this->query = $body->query;
			}	
		}
		
		$this->raw = $data;
	}
	
	public function getResults() {
		if (isset($this->query)) {
			return $this->query->results;
		}
		return null;
	}
	
	public function getFromResults($key) {
		
		if (null !== $results = $this->getResults()) {
			return isset($results->$key) ? $results->$key : null;
		}
		
		return null;
	}
	
}
