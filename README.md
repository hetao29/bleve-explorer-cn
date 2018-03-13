# bleve-explorer-cn
bleve explorer support chinese
支持中文分词的 bleve 工具
## 参考连接
https://github.com/blevesearch/bleve-explorer

## 安装方法
### 下载代码
```bash
git clone https://github.com/hetao29/bleve-explorer-cn.git bleve
cd bleve
```
### 安装依赖
```bash
go get github.com/blevesearch/bleve
go get github.com/syndtr/goleveldb
go get github.com/elazarl/go-bindata-assetfs
go get github.com/blevesearch/bleve/analysis
go get github.com/blevesearch/snowballstem
go get github.com/couchbase/moss
go get github.com/xgdapg/daemon
go get github.com/willf/bitset
go get github.com/gorilla/mux
go get github.com/hetao29/bleve-mapping-ui
go get github.com/hetao29/blevesearch-cn/scws
#如果中国用户打不开 golang.org，用如下方法
cd thirdpart/ 
tar xzf golang.org.tgz
cp -r golang.org $GOPATH/src
cd ..
```
### 安装scws
```bash
wget http://www.xunsearch.com/scws/down/scws-1.2.1.tar.bz2 
tar xjf scws-1.2.1.tar.bz2 
cd scws-1.2.1
./configure 
make 
sudo make install
sudo ln -s  /usr/local/lib/libscws.so.1 /usr/lib/libscws.so.1
cd ..
```
### 编译
```bash
make
```
### 配置
```bash
mkdir log
mkdir data
```
### 启动
```bash
make start
cat log/*
```
## 使用PHP API
### 参考方法
打开 php 目录下的文件
