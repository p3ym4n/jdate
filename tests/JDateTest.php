<?php

namespace p3ym4n\JDate\Test;

use Carbon\Carbon;
use p3ym4n\JDate\JDate;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: p3ym4n
 * Date: 10/20/17
 * Time: 15:29
 */
class JDateTest extends TestCase {
    
    public function testCarbonTimeSameAsJalaliTime() {
        $Carbon = Carbon::now();
        $JDate  = JDate::now();
        
        $this->assertEquals($Carbon->toTimeString(), $JDate->toTimeString());
    }
    
    public function testCarbon2Jalali2CarbonNow() {
        $Carbon = Carbon::now();
        $JDate  = JDate::now();
        
        $this->assertEquals($Carbon, $JDate->carbon);
    }
    
    public function testCarbon2Jalali2Carbon() {
        $Carbon = Carbon::create(1988, 12, 22, 1, 10, 0);
        $JDate  = JDate::createFromCarbon($Carbon);
        
        $this->assertEquals($Carbon, $JDate->carbon);
    }
    
    public function testJalali2Carbon2Jalali() {
        $JDate          = JDate::create(1367, 10, 1, 1, 10, 0);
        $JDateFormatted = $JDate->carbon->format(Carbon::DEFAULT_TO_STRING_FORMAT);
        $Carbon         = Carbon::createFromFormat(Carbon::DEFAULT_TO_STRING_FORMAT, $JDateFormatted);
        
        $this->assertEquals($Carbon, $JDate->carbon);
    }
    
    public function testJalaliFormatSameAsCarbonFormat() {
        $JDate  = JDate::createFromFormat('Y-m-d', '1367-10-01');
        $Carbon = Carbon::createFromFormat('Y-m-d', '1988-12-22');
        
        $this->assertEquals($JDate->carbon->toFormattedDateString(), $Carbon->toFormattedDateString());
        
    }
    
    public function testIs1395LeapYear() {
        
        $this->assertTrue(JDate::isLeapYear(1395));
        
    }
}