# bleve-explorer-cn
bleve explorer support chinese
## 
https://github.com/blevesearch/bleve-explorer

##
create index 带上如下参数
```json
{
    "default_mapping":{
        "enabled":true,
        "display_order":"0"
    },
    "type_field":"_type",
    "default_type":"_default",
    "default_analyzer":"scws",
    "default_datetime_parser":"dateTimeOptional",
    "default_field":"_all",
    "byte_array_converter":"json",
    "analysis":{
        "analyzers":{
            "scws":{
                "type":"custom",
                "tokenizer":"scws"
            }
        },
        "tokenizers":{
            "scws":{
                "dict":"/Users/hetal/dict/dict.utf8.xdb",
                "type":"scws"
            }
        }
    },
    "store_dynamic":true,
    "index_dynamic":true
}
```
