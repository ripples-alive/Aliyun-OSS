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

> If you want to update meta data when copy file, remember to set `x-oss-metadata-directive` header to `REPLACE`.
> 
> Be cautious when update meta data.
> Assume that the init headers are `['a' => 1, 'b' => 2]`.
> 
> After `modifyMeta('bucket', 'key', ['b' => 3])`, the headers would be `['a' => 1, 'b' => 3]`.
> 
> After `setMeta('bucket', 'key', ['b' => 3])`, the headers would be `['b' => 3]`.

Notice that we'll automatic check the response status. And you can modify the attribute `ok_status` to intervene it.

##### Finally, we define the clients as public. So it's ok to call the method of Aliyun SDK directly.

## LICENSE
GPL