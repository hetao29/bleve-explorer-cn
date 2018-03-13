//  Copyright (c) 2014 Couchbase, Inc.
//  Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file
//  except in compliance with the License. You may obtain a copy of the License at
//    http://www.apache.org/licenses/LICENSE-2.0
//  Unless required by applicable law or agreed to in writing, software distributed under the
//  License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
//  either express or implied. See the License for the specific language governing permissions
//  and limitations under the License.

package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"io/ioutil"
	"log"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"

	"github.com/gorilla/mux"
	"github.com/xgdapg/daemon"

	"github.com/blevesearch/bleve"
	bleveHttp "github.com/blevesearch/bleve/http"
	// import general purpose configuration
	_ "github.com/blevesearch/bleve/config"

	bleveMappingUI "github.com/hetao29/bleve-mapping-ui"
	_ "github.com/hetao29/blevesearch-cn/scws/bleve"
)

//var bindAddr = flag.String("addr", ":8095", "http listen address")
//var dataDir = flag.String("dataDir", "data", "data directory")
var staticEtag = flag.String("staticEtag", "", "optional static etag value.")
var staticPath = flag.String("static", "",
	"optional path to static directory for web resources")
var staticBleveMappingPath = flag.String("staticBleveMapping", "",
	"optional path to static-bleve-mapping directory for web resources")

var configFile = flag.String("c", "etc/config.json", "config filename")
var cpuprofile = flag.String("cpuprofile", "", "write cpu profile to file")

func getFilePath(filename string) string {
	file, _ := exec.LookPath(os.Args[0])
	path, _ := filepath.Abs(filepath.Base(file))
	curdir := filepath.Dir(path)
	return fmt.Sprintf("%s/%s", curdir, filename)
}

type Config struct {
	BindAddr string `json:"addr"`
	DataDir  string `json:"dataDir"`
	Daemon   bool   `json:"daemon"`
}

func LoadConfig(configFile string) Config {
	var config = Config{}
	data, err := ioutil.ReadFile(configFile)
	if err != nil {
		log.Fatal(err)
	}
	fmt.Printf("load config file %s \n%s\n", configFile, string(data))
	if err := json.Unmarshal(data, &config); err != nil {
		log.Println("config file parse error!")
		log.Fatal(err)
	}
	log.Println(config.Daemon)
	if config.BindAddr == "" {
		log.Fatal("addr can not empty!")
	}
	if config.DataDir == "" {
		log.Fatal("DataDir can not empty!")
	}
	return config
}

func main() {
	flag.Parse()

	log.SetFlags(log.LstdFlags | log.Lshortfile)

	var realConfig = getFilePath(*configFile)
	config := LoadConfig(realConfig)
	log.Println("Config filename is ", realConfig)
	defer func() { // 必须要先声明defer，否则不能捕获到panic异常
		if err := recover(); err != nil {
			fmt.Println(err) // 这里的err其实就是panic传入的内容，55
		}
	}()
	runtime.GOMAXPROCS(runtime.NumCPU())
	if config.Daemon {
		daemon.Exec(daemon.Daemon)
	}

	// walk the data dir and register index names
	dirEntries, err := ioutil.ReadDir(config.DataDir)
	if err != nil {
		log.Fatalf("error reading data dir: %v", err)
	}

	for _, dirInfo := range dirEntries {
		indexPath := config.DataDir + string(os.PathSeparator) + dirInfo.Name()

		// skip single files in data dir since a valid index is a directory that
		// contains multiple files
		if !dirInfo.IsDir() {
			log.Printf("not registering %s, skipping", indexPath)
			continue
		}

		i, err := bleve.Open(indexPath)
		if err != nil {
			log.Printf("error opening index %s: %v", indexPath, err)
		} else {
			log.Printf("registered index: %s", dirInfo.Name())
			bleveHttp.RegisterIndexName(dirInfo.Name(), i)
			// set correct name in stats
			i.SetName(dirInfo.Name())
		}
	}


	router := mux.NewRouter()
	router.StrictSlash(true)

	// default to bindata for static-bleve-mapping resources.
	staticBleveMapping := http.FileServer(bleveMappingUI.AssetFS())
	if *staticBleveMappingPath != "" {
		fi, err := os.Stat(*staticBleveMappingPath)
		if err == nil && fi.IsDir() {
			log.Printf("using static-bleve-mapping resources from %s",
				*staticBleveMappingPath)
			staticBleveMapping = http.FileServer(http.Dir(*staticBleveMappingPath))
		}
	}

	router.PathPrefix("/static-bleve-mapping/").
		Handler(http.StripPrefix("/static-bleve-mapping/", staticBleveMapping))

	// default to bindata for static resources.
	static := http.FileServer(assetFS())
	if *staticPath != "" {
		fi, err := os.Stat(*staticPath)
		if err == nil && fi.IsDir() {
			log.Printf("using static resources from %s",
				*staticPath)
			static = http.FileServer(http.Dir(*staticPath))
		}
	}

	staticFileRouter(router, static)

	// add the API
	bleveMappingUI.RegisterHandlers(router, "/api")

	createIndexHandler := bleveHttp.NewCreateIndexHandler(config.DataDir)
	createIndexHandler.IndexNameLookup = indexNameLookup
	router.Handle("/api/{indexName}", createIndexHandler).Methods("PUT")

	getIndexHandler := bleveHttp.NewGetIndexHandler()
	getIndexHandler.IndexNameLookup = indexNameLookup
	router.Handle("/api/{indexName}", getIndexHandler).Methods("GET")

	deleteIndexHandler := bleveHttp.NewDeleteIndexHandler(config.DataDir)
	deleteIndexHandler.IndexNameLookup = indexNameLookup
	router.Handle("/api/{indexName}", deleteIndexHandler).Methods("DELETE")

	listIndexesHandler := bleveHttp.NewListIndexesHandler()
	router.Handle("/api", listIndexesHandler).Methods("GET")

	docIndexHandler := bleveHttp.NewDocIndexHandler("")
	docIndexHandler.IndexNameLookup = indexNameLookup
	docIndexHandler.DocIDLookup = docIDLookup
	router.Handle("/api/{indexName}/{docID}", docIndexHandler).Methods("PUT")

	docCountHandler := bleveHttp.NewDocCountHandler("")
	docCountHandler.IndexNameLookup = indexNameLookup
	router.Handle("/api/{indexName}/_count", docCountHandler).Methods("GET")

	docGetHandler := bleveHttp.NewDocGetHandler("")
	docGetHandler.IndexNameLookup = indexNameLookup
	docGetHandler.DocIDLookup = docIDLookup
	router.Handle("/api/{indexName}/{docID}", docGetHandler).Methods("GET")

	docDeleteHandler := bleveHttp.NewDocDeleteHandler("")
	docDeleteHandler.IndexNameLookup = indexNameLookup
	docDeleteHandler.DocIDLookup = docIDLookup
	router.Handle("/api/{indexName}/{docID}", docDeleteHandler).Methods("DELETE")

	searchHandler := bleveHttp.NewSearchHandler("")
	searchHandler.IndexNameLookup = indexNameLookup
	router.Handle("/api/{indexName}/_search", searchHandler).Methods("POST")

	listFieldsHandler := bleveHttp.NewListFieldsHandler("")
	listFieldsHandler.IndexNameLookup = indexNameLookup
	router.Handle("/api/{indexName}/_fields", listFieldsHandler).Methods("GET")

	debugHandler := bleveHttp.NewDebugDocumentHandler("")
	debugHandler.IndexNameLookup = indexNameLookup
	debugHandler.DocIDLookup = docIDLookup
	router.Handle("/api/{indexName}/{docID}/_debug", debugHandler).Methods("GET")

	aliasHandler := bleveHttp.NewAliasHandler()
	router.Handle("/api/_aliases", aliasHandler).Methods("POST")

	// start the HTTP server
	http.Handle("/", router)
	log.Printf("Listening on %v", config.BindAddr)
	log.Fatal(http.ListenAndServe(config.BindAddr, nil))
}
