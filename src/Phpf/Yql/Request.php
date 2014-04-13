<?php
/**
 * @package Yql
 * @subpackage Request
 */

namespace Phpf\Yql;

use InvalidArgumentException;
use Closure;

class Request
{

	public $baseUrl = 'http://query.yahooapis.com/v1/public/yql';

	public $baseTableUrl = 'http://www.datatables.org/';

	public $env = 'store://datatables.org/alltableswithkeys';

	public $format = 'json';

	protected $diagnostics = false;

	protected $showErrors = false;

	protected $query;

	protected $url;

	protected $response;

	protected $execute;

	public function __construct($http_controller_callable) {

		if (! is_callable($http_controller_callable)) {
			throw new InvalidArgumentException("HTTP controller callback must be callable.");
		}

		if ($http_controller_callable instanceof Closure) {
			$this->execute = $http_controller_callable;
		} else {
			$this->execute = function ($url) use ($http_controller_callable) {
				return call_user_func_array($http_controller_callable, array($url));
			};
		}
	}

	/**
	 *	Builds the url and gets the response.
	 */
	public function execute($query = null) {

		if (! empty($query)) {
			$this->setQuery($query);
		}

		$this->url = $this->baseUrl.'?q='.urlencode($this->query)."&format=".$this->format;

		if ($this->diagnostics) {
			$this->url .= '&diagnostics=true';
		}
		if (! empty($this->env)) {
			$this->url .= '&env='.urlencode($this->env);
		}
		
		$result = invoke($this->execute, array('url' => $this->url));

		$this->response = new Response($result, $this);

		return $this;
	}

	public function setEnv($env) {
		$this->env = $env;
		return $this;
	}

	public function setDiagnostics($val) {
		$this->diagnostics = (bool)$val;
		return $this;
	}

	public function setShowErrors($val) {
		$this->showErrors = (bool)$val;
		return $this;
	}

	public function setFormat($format) {
		$format = strtolower($format);
		if ('json' === $format || 'xml' === $format)
			$this->format = $format;
		return $this;
	}

	public function setQuery($query) {

		if ($query instanceof Request\FluentInterface) {
			$this->query = $query->__toString();
		} elseif (is_string($query)) {
			$this->query = $query;
		} else {
			throw new InvalidArgumentException("Invalid YQL query - must be FluentInterface or string - ".gettype($query).' given.');
		}

		return $this;
	}

	public function getQuery() {
		return $this->query;
	}

	public function getResponse() {
		return $this->response;
	}

	public function getResults() {

		if (isset($this->response)) {
			return $this->response->getResults();
		}
		
		return null;
	}

}
