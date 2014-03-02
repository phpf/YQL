<?php
/**
 * @package Phpf.Yql
 * @subpackage Request.FluentInterface
 */

namespace Phpf\Yql\Request;

use Phpf\Yql\Request;
use Exception;

class FluentInterface {
	
	public $statements = array();
	
	public $tables = array();
	
	protected $request;
	
	public function __construct( Request $yqlRequest ){
		$this->request = $yqlRequest;
	}
	
	/**
	 * Returns Yql\Request
	 */
	public function getRequest(){
		return $this->request;
	}
	
	/**
	 * Adds a statement to YQL query.
	 */
	public function addStatement( $type, $str ){
		
		if ( 'use' === $type || 'where' === $type ){
			$this->statements[$type][] = $str;
		} else {
			$this->statements[ $type ] = $str;
		}
		
		return $this;
	}
	
	/**
	 * Returns statement string.
	 */
	public function getStatement( $type ){
		
		$str = '';
				
		switch( strtolower($type) ){
			
			case 'select':
				return $this->statements['select'];
			
			case 'use':
			case 'where':
				
				if ( empty($this->statements[$type]) ){
					return '';
				}
				
				foreach($this->statements[$type] as $stmt){
					if ( 'where' === $type ){
						$strs[] = trim(str_ireplace('where', '', $stmt), ' ;');
					} else {
						$str .= $stmt;
					}
				}
				
				if ( 'where' === $type ){
					$str = 'WHERE ' . implode(' AND ', $strs) . ';';
				}
				
				return $str;
			
			case 'from':
				
				if ( !empty($this->statements['from']) ){
					return $this->statements['from'];
				}
				
				if ( empty($this->tables) ){
					throw new Exception("No table set - must specify table using from() or useTable() methods.");
				}
				
				$tables = array_values($this->tables);
				
				return $tables[0];
		}
		
	}
	
	/**
	 * Sets Select statement of the YQL query
	 */
	public function select( $select ){
		
		if ( is_array($select) ){
			$select = implode(', ', $select);
		}
		
		$this->addStatement('select', 'SELECT ' . $select . ' ');
		
		return $this;
	}

	
	/*
	 * Sets FROM statement of YQL query
	 * Not required if 'use_table' is set
	 */
	public function from( $from ){
		
		$this->addStatement('from', 'FROM ' . $from . ' ');
		
		return $this;
	}
		
	/**
	 * Sets WHERE statement of YQL query.
	 */
	public function where( $where ){
		
		$this->addStatement('where', 'WHERE ' . $where);
		
		return $this;
	}

	/**	
	 *	Sets USE statement (optional)
	 */
	public function useTable( $path, $table = null ){
		
		if ( !filter_var($path, FILTER_VALIDATE_URL) ){
			$url = $this->request->baseTableUrl . ltrim($path, '/');
		} else {
			$url = $path;
		}
		
		if ( empty($table) ){
			$table = trim( substr($path, strrpos($path, '/')), '/' );
			$table = trim( str_replace('.xml', '', $table) );
		}
		
		$this->addStatement('use', 'USE "' . $url . '" AS ' . $table . '; ');
		
		$this->tables[ $url ] = $table;
		
		return $this;
		
	}
	
	/**
	 *	Sets YQL environment (optional)
	 */
	public function env( $env ){
		$this->request->setEnv($env);
		return $this;
	}
	
	/**
	 * Returns concatenated query string.
	 */
	public function __toString(){
		return $this->getStatement('use') 
			. $this->getStatement('select') 
			. $this->getStatement('from') 
			. $this->getStatement('where');
	}
	
	/**
	 * Builds query and gets the response.
	 */
	public function query(){
		$this->request->setQuery($this);
		return $this->request->execute();
	}
				
	/**
	 *	Returns query results
	 */
	public function getResults( $part = 'body' ){
		return $this->request->getResults($part);
	}
		
}
