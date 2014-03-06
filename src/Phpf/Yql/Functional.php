<?php

namespace Phpf\Yql{

	class Functional {
		// dummy class
	}
}

namespace {
	
	use Phpf\Yql\Request;
		
	function yql_query( $query ){
		
		$yql = new Request(array('Requests', 'get'));
		
		$yql->setQuery($query);
		
		$yql->execute();
		
		return $yql->getResults();
	}
	
	function yql_query_select( $select, $from, $where ){
		
		$yql = new Request(array('Requests', 'get'));
		
		$fyql = new \Phpf\Yql\Request\FluentInterface($yql);
		
		$fyql->select($select)
			->from($from);
		
		if ( is_array($where) ){
			foreach($where as $whr)
				$fyql->where($whr);
		} else {
			$fyql->where($where);
		}
		
		$fyql->query();
		
		return $fyql->getResults();
	}
	
}
