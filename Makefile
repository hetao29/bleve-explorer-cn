all:
	go build -o bin/bleve-explorer-cn
start:
	./bin/bleve-explorer-cn >> log/explorer.log 2>&1 &
stop:
	killall bleve-explorer-cn
