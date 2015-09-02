# Aliyun OSS

This is wrapper for Aliyun OSS SDK and make it more easy to use.

Supporting internal and external endpoints for better operations.

## Installation

This package can be installed through `composer`.

```bash
composer require ripples/aliyun-oss
```

## Usage

See [examples/example.php](examples/example.php)

Your can give both external and internal endpoints when constructing.
The last parameter of each method represent whether to use the internal endpoint or the other one.

By default, we used the internal endpoint except the methods to generate signatured url.

We use the value of external endpoint as the default value of internal endpoint.
So if you don't want to use two endpoints, just omit the internal endpoint when constructing.
Then, no matter what parameter you use when call the methods, we will all use the external endpoint.

## LICENSE
GPL