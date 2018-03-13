<?php
/**
 * https://github.com/hetao29/bleve-explorer-cn
 * https://github.com/blevesearch/bleve-explorer
 */
class bleve{
	var $gateway;
	var $options=array(
		"default_mapping"	=>array("enabled"=>true,"display_order"=>0),
		"type_field"		=>"_type",
		"default_type"		=>"_default",
		"default_analyzer"	=>"scws",
		"default_datetime_parser"=>"dateTimeOptional",
		"default_field"=>"_all",
		"byte_array_converter"=>"json",
		"store_dynamic"		=>true,
		"index_dynamic"		=>true,
		"analysis"=>array(
			"analyzers"=>array(
				"scws"=>array(
					"type"=>"custom",
					"tokenizer"=>"scws"
				)
			),
			"tokenizers"=>array(
				"scws"=>array(
					"dict"=>"/Users/hetal/dict/dict.utf8.xdb",
					"type"=>"scws"
				)
			)
		)
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
	public function getIndexFields($indexName){
		return $this->get($this->gateway."/api/$indexName/_fields");
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
	public function getDocumentFields($indexName,$docID){
		return $this->get($this->gateway."/api/$indexName/$docID/_fields");
	}
	public function getDocumentDebug($indexName,$docID){
		return $this->get($this->gateway."/api/$indexName/$docID/_debug");
	}
	public function deleteDocument($indexName,$docID){
		return $this->delete($this->gateway."/api/$indexName/$docID");
	}
	public function search($indexName,$query,$from=0,$size=10){
		$data = array(
			"size"=>$size,
			"from"=>$from,
			"explain"=>true,
			"highlight"=>new stdclass,
			"query"=>array("boost"=>1,"query"=>$query),
			"fields"=>array("*")
		);

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
		$ch = curl_init();
		$method=strtoupper($method);
		if($method=="GET"){
			$url .= empty($params)?"":"?".http_build_query($params);
		}else{
			if(is_array($post_data) || is_object($post_data)){
				$post_data=json_encode($post_data);
			}
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$json_data = json_decode($data);
		curl_close($ch);
		return ($httpcode>=200 && $httpcode<300) ? ($json_data == NULL?$data:$json_data): false;
	}
}
