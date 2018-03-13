<?php
require_once("bleve.class.php");

$bleve = new bleve("http://localhost:8095/");
//var_dump($bleve->getIndex("cn"));
//print_r($bleve->deleteIndex("course"));
//print_r($bleve->createIndex("course"));
print_r($bleve->getIndexFields("course"));
print_r($bleve->getIndexCount("course"));
//print_r($bleve->getDocumentFields("course",1));
print_r($bleve->getDocument("course",1));
//print_r($bleve->getDocumentDebug("course",1));
//print_r($bleve->createDocument("course",1,array("name"=>"我是中国人")));
////var_dump($bleve->deleteDocument("course",1));
//print_r($bleve->search("course","中国"));
//print_r($bleve->listIndex());

