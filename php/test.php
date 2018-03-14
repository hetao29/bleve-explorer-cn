<?php
require_once("bleve.class.php");
$bleve = new bleve("http://localhost:8095/");
/**
 *
 */
//var_dump($bleve->getIndex("cn"));
//print_r($bleve->deleteIndex("course"));
print_r($bleve->createIndex("goods"));
//print_r($bleve->getIndexCount("course"));
//print_r($bleve->getDocumentDebug("goods",1));
exit;
/**
 *
 */
print_r($bleve->createDocument("goods",1,array("name"=>"我是中国人","id"=>1,"time"=>"2013-12-10 01:01:01")));
print_r($bleve->createDocument("goods",2,array("name"=>"我是中国人","id"=>2,"time"=>"2010-12-10 01:01:01")));
print_r($bleve->createDocument("goods",3,array("name"=>"我是日本人, 你是哪国人","id"=>3,"time"=>"2019-12-10 01:01:01")));
print_r($bleve->getDocument("goods",1));
//var_dump($bleve->deleteDocument("course",1));
/**
 * search with facets and sort
 */
$sort=array("-id");
$facets = array(
	"f0"=>array(
		"field"=>"name",
		"size"=>1,
	),
	"f2"=>array(
		"field"=>"time",
		"size"=>2,
		"date_ranges"=>array(
			array("name"=>"yy1","start"=>"2010-10-12 00:00:00","end"=>"2027-10-10 00:00:00"),
			array("name"=>"yy2","start"=>"2018-10-12 00:00:00","end"=>"2027-10-10 00:00:00"),
		)
	),
	"f3"=>array(
		"field"=>"id",
		"size"=>2,
		"numeric_ranges"=>array(
			array("name"=>"xx","min"=>0,"max"=>10),
			array("name"=>"yy","min"=>2,"max"=>3),
		)
	),
);
print_r($bleve->search("goods","*",null,$sort,$facets));
