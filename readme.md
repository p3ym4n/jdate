# jdate

A simple php date converter from Jalali to Georgian calendar and vice versa.

This package is using [carbon](https://github.com/briannesbitt/carbon) as core for Georgian calendar.

## Installation

```
$ composer require p3ym4n/jdate
```

### how to use
 
```php
<?php
require 'vendor/autoload.php';
 
use p3ym4n\jdate\jdate;
 
jdate::now();                                   //1395-11-08 16:51:08

jdate::today();                                 //1395-11-08 00:00:00

jdate::tomorrow();                              //1395-11-09 00:00:00

jdate::createFromTimestamp(1485523813);         //1395-11-08 17:00:13

jdate::create(1395, 11, 11, 12, 13, 36);        //1395-11-11 12:13:36

jdate::createFromFormat('Y/n/j', '1395/12/30'); //1395-12-30 17:01:21

$carbon = new Carbon\Carbon();
jdate::createFromCarbon($carbon);




 ```

### info

- This package is compatible with laravel 5 .
 