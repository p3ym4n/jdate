# JDate

Date converter from Jalali to Georgian and vice versa. It has Carbon instance inside and it's Laravel friendly.

This package is using [carbon](https://github.com/briannesbitt/carbon) as core for Georgian calendar.

## Installation

```
$ composer require p3ym4n/jdate
```

### how to use
 
```php
<?php

require 'vendor/autoload.php';
 
use p3ym4n\JDate\JDate;

JDate::now();                                   //1395-11-08 16:51:08
JDate::today();                                 //1395-11-08 00:00:00
JDate::tomorrow();                              //1395-11-09 00:00:00
JDate::createFromTimestamp(1485523813);         //1395-11-08 17:00:13
JDate::create(1395, 11, 11, 12, 13, 36);        //1395-11-11 12:13:36
JDate::createFromFormat('Y/n/j', '1395/12/30'); //1395-12-30 17:01:21

$carbon = new Carbon\Carbon();
$jdate = JDate::createFromCarbon($carbon);      //1395-11-08 17:32:43

//Some Relative Modifiers...
$jdate->startOfDay();           //1395-11-08 00:00:00
$jdate->startOfMonth();         //1395-11-01 00:00:00
$jdate->startOfYear();          //1395-01-01 00:00:00
$jdate->startOfDecade();        //1390-01-01 00:00:00
$jdate->startOfCentury();       //1300-01-01 00:00:00

$jdate->endOfDay();             //1395-11-08 23:59:59
$jdate->endOfMonth();           //1395-11-30 23:59:59
$jdate->endOfYear();            //1395-12-30 23:59:59 (remember that 1395 is a leap year)
$jdate->endOfDecade();          //1399-12-30 23:59:59
$jdate->endOfCentury();         //1399-12-30 23:59:59

//Other Modifiers...
$jdate->addDay(2);              //1395-11-10 17:32:43
$jdate->subMonth();             //1395-10-10 17:32:43
$jdate->addHours(3);            //1395-10-10 20:32:43
 ```

### info

- This package is compatible with laravel 5 .
 
