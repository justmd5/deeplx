# DeeplX

deeplx is a simple and fast free deepl library for PHP.

## Installing

```shell
$ composer require justmd5/deeplx -vvv
```

## Usage

```php
<?php
require __DIR__.'/vendor/autoload.php';
$deeplx=new \Justmd5\DeeplX\DeepLTranslator();

try {
//translate chinese to english
    var_dump($deeplx->zh2en("你好"));
    //output:
        //    array(4) {
        //    ["status"]=>
        //    int(1000)
        //    ["code"]=>
        //    int(1000)
        //    ["message"]=>
        //    string(2) "ok"
        //    ["data"]=>
        //    string(12) "How are you?"
        //    }
//translate english to chinese
    var_dump($deeplx->en2zh("hello my friend."));
    //output:
        //array(4) {
        //  ["status"]=>
        //  int(1000)
        //  ["code"]=>
        //  int(1000)
        //  ["message"]=>
        //  string(2) "ok"
        //  ["data"]=>
        //  string(24) "你好，我的朋友。"
        //}
        
        
//normal translate func demo
    $result = $translator->translate('hello', 'ja')->result();
    var_dump($result);
    //output:
        //array(4) {
        //  ["status"]=>
        //  int(1000)
        //  ["code"]=>
        //  int(1000)
        //  ["message"]=>
        //  string(2) "ok"
        //  ["data"]=>
        //  string(12) "こんにちわ"
        //}

} catch (Exception $e) {
    var_dump($e);
}
```

## without verify

```shell
\Justmd5\DeeplX\DeepLTranslator::withoutVerifying()->en2zh('hello');
```


## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/justmd5/deeplx/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/justmd5/deeplx/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT
