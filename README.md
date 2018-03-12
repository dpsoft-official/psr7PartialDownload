# PSR-7 Partial download
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

PSR-7 Partial file download package based on [HTTP 206 Partial Content In Node.js](https://www.codeproject.com/Articles/813480/HTTP-Partial-Content-In-Node-js)

### Install

Install latest version using [composer](https://getcomposer.org/).

``` bash
$ composer require dpsoft/psr7partial-download
```
### Usage
```php
/** @var Psr\Http\Message\ServerRequestInterface */
$request = ;
/** @var Psr\Http\Message\ResponseInterface */
$response = ;
 $serve = new Dpsoft\psr7PartialDownload\Serve($request,$response);
 
 /** @var Psr\Http\Message\ResponseInterface */
 $response = $serve->download($filePath,$fileName);
```

### Test
with 93% code coverage!