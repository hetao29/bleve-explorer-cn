<?php
/**
 * https://github.com/hetao29/bleve-explorer-cn
 * https://github.com/blevesearch/bleve-explorer
 */
class bleve{
	static $ch=null;
	var $gateway;
	var $options=array(
		"default_mapping"	=>array("enabled"=>true,"display_order"=>0),
		"type_field"		=>"_type",
		"default_type"		=>"_default",
		"default_analyzer"	=>"cn",
		"default_datetime_parser"=>"dateTimeOptional",
		"default_field"=>"_all",
		"byte_array_converter"=>"json",
		"store_dynamic"		=>true,
		"index_dynamic"		=>true,
	);

	public function __construct($gateway){
		$slash = $gateway{strlen($gateway)-1};
		$this->gateway = $slash!="/"?$gateway:chop($gateway,"/");
	}
	/**
	 * create index
	 */
	public function createIndex($indexName,$options=null){
		if(!$options){
			$options=$this->options;
		}
		return $this->put($this->gateway."/api/$indexName",$options);
	}
	public function getIndex($indexName){
		return $this->get($this->gateway."/api/$indexName");
	}
	public function getIndexCount($indexName){
		return $this->get($this->gateway."/api/$indexName/_count");
	}
	public function deleteIndex($indexName){
		return $this->delete($this->gateway."/api/$indexName");
	}
	public function listIndex(){
		return $this->get($this->gateway."/api");
	}

	/**
	 * document
	 */
	public function createDocument($indexName,$docID,$data){
		return $this->put($this->gateway."/api/$indexName/$docID",$data);
	}
	public function getDocumentCount($indexName){
		return $this->get($this->gateway."/api/$indexName/_count");
	}
	public function getDocument($indexName,$docID){
		return $this->get($this->gateway."/api/$indexName/$docID");
	}
	public function getDocumentDebug($indexName,$docID){
		return $this->get($this->gateway."/api/$indexName/$docID/_debug");
	}
	public function deleteDocument($indexName,$docID){
		return $this->delete($this->gateway."/api/$indexName/$docID");
	}
	/**
	 * @params string $indexName
	 * @params string $query
	 * @params array $field, * is all field
	 * @params array $sort , filed, + prefix is asc, - is desc,like array("-id","+time")
	 * @params array $facets 
	 * $fields = array(
		 "$name"=>array(
			"field"=>"$field",
			"size"=>$size,
			//options 
			"numeric_ranges"=>array(
				array("name"=>"$name_x1","min"=>$min,"max"=>$max),
				array("name"=>"$name_x1","min"=>$min,"max"=>$max),
			),
			//options
			"date_ranges"=>array(
				array("name"=>"$name_y1","start"=>"2010-10-12 00:00:00","end"=>"2027-10-10 00:00:00"),
				array("name"=>"$name_y2","start"=>"2010-10-12 00:00:00","end"=>"2027-10-10 00:00:00"),
			)
		)
	 */
	public function search($indexName,$query,$fields=null,$sort=null,$facets=null,$from=0,$size=10){
		$request= array(
			"size"=>$size,
			"from"=>$from,
			"sort"=>$sort,
			"explain"=>false,
			"includeLocations"=>false,
			"highlight"=>new stdclass,
			"query"=>array("boost"=>1,"query"=>$query),
			"fields"=>array("*"),
		);
		if($fields && is_array($fields)){
			$request['fields']=$fields;
		}
		if($facets){
			$request['facets']=$facets;
		}
		if($sort){
			$request['sort']=$sort;
		}
		return $this->post($this->gateway."/api/$indexName/_search",$request);
	}
	public function searchFacets($indexName,$query,$from=0,$size=10){
		$data = array(
			"size"=>$size,
			"from"=>$from,
			"sort"=>array("-id","time"),
			"explain"=>false,
			"includeLocations"=>false,
			"highlight"=>new stdclass,
			"query"=>array("boost"=>1,"query"=>$query),
			"fields"=>array("*"),
			"facets"=>new stdclass
		);
		//
		$data['facets']->xx=array(
			"field"=>"id",
			"size"=>1,
		);
		//
		$data['facets']->styles=array(
			"field"=>"id",
			"size"=>1,
			"numeric_ranges"=>array(
				array("name"=>"xx","min"=>1,"max"=>3),
			)
		);
		$data['facets']->t=array(
			"field"=>"time",
			"size"=>1,
			"date_ranges"=>array(
				array("name"=>"yy","start"=>"2010-10-12 00:00:00","end"=>"2027-10-10 00:00:00"),
			)
		);
		print_r($data);
		return $this->post($this->gateway."/api/$indexName/_search",$data);
	}
	/**
	 * http method
	 */
	private function delete($url,$params=array()){
		return $this->exec($url,$params,"DELETE");
	}
	private function post($url,$params=array()){
		return $this->exec($url,$params,"POST");
	}
	private function put($url,$params=array()){
		return $this->exec($url,$params,"PUT");
	}
	private function get($url,$params=array()){
		return $this->exec($url,$params);
	}
	private function exec($url,$post_data,$method="GET"){
		if(self::$ch==null){
			self::$ch = curl_init();
		}
		$method=strtoupper($method);
		if($method=="GET"){
			$url .= empty($params)?"":"?".http_build_query($params);
			curl_setopt(self::$ch, CURLOPT_POSTFIELDS, "");
		}else{
			if(is_array($post_data) || is_object($post_data)){
				$post_data=json_encode($post_data);
			}
			curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $post_data);
		}
		curl_setopt(self::$ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt(self::$ch, CURLOPT_URL, $url);
		curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt(self::$ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt(self::$ch, CURLOPT_TIMEOUT, 10);
		$data = curl_exec(self::$ch);
		$httpcode = curl_getinfo(self::$ch, CURLINFO_HTTP_CODE);
		$json_data = json_decode($data);
		if($json_data == NULL){
			print($data."\n");
		}
		return ($httpcode>=200 && $httpcode<300) ? ($json_data == NULL?$data:$json_data): false;
	}
}
