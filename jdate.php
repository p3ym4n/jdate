<?php

/*
 * (c) p3ym4n <me@p3ym4n.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace p3ym4n\jdate;

use Carbon\Carbon;
use DateTimeZone;
use InvalidArgumentException;

/**
 * A simple php date converter from Jalali to Georgian calendar and vice versa.
 * the georgian date object holder is @package Carbon\carbon
 *
 * @property Carbon            $carbon
 * @property int               $year
 * @property int               $yearIso
 * @property int               $month
 * @property int               $day
 * @property int               $hour
 * @property int               $minute
 * @property int               $second
 * @property int               $timestamp   seconds since the Unix Epoch
 * @property-read DateTimeZone $timezone    the current timezone
 * @property-read DateTimeZone $tz          alias of timezone
 * @property-read integer      $micro
 * @property-read integer      $dayOfWeek   1 (for Saturday) through 7 (for Friday)
 * @property-read integer      $dayOfYear   1 through 366
 * @property-read integer      $weekOfMonth 1 through 4
 * @property-read integer      $weekOfYear  ISO-8601 week number of year, weeks starting on Monday
 * @property-read integer      $daysInMonth number of days in the given month
 * @property-read integer      $age         does a carbon->diffInYears() with default parameters
 * @property-read integer      $quarter     the quarter of this instance, 1 - 4
 * @property-read integer      $offset      the timezone offset in seconds from UTC
 * @property-read integer      $offsetHours the timezone offset in hours from UTC
 * @property-read boolean      $dst         daylight savings time indicator, true if DST, false otherwise
 * @property-read boolean      $local       checks if the timezone is local, true if local, false otherwise
 * @property-read boolean      $utc         checks if the timezone is UTC, true if UTC, false otherwise
 * @property-read string       $timezoneName
 * @property-read string       $tzName
 * @package p3ym4n\JalaliDate
 */
class jdate {
    
    /**
     * Default format to use for __toString method when type juggling occurs.
     *
     * @var string
     */
    const DEFAULT_TO_STRING_FORMAT = 'Y-m-d H:i:s';
    
    /**
     * days difference from the georgian to jalali date
     */
    const NORMAL_YEARS_DIFFERENCE_DAYS = 79;
    
    /**
     * difference between georgian and jalali years
     */
    const HEGIRA_STARTING_YEAR = 621;
    
    /**
     * the beginning year of the current 2820 grand cycle in Georgian
     */
    const GRAND_CYCLE_BEGINNING = 1096;
    
    /**
     * number of years in every grand cycle
     */
    const GRAND_CYCLE_LENGTH = 2820;
    
    /**
     * number of cycles in every @const GRAND_CYCLE
     */
    const TOTAL_CYCLES = 88;
    
    /**
     * total years that repeats 21 times as a quad cycle in every @const GRAND_CYCLE
     */
    const FIRST_QUAD_CYCLE = 128;
    
    /**
     * total years that repeats only once as a quad cycle in every @const GRAND_CYCLE
     */
    const SECOND_QUAD_CYCLE = 132;
    
    /**
     * the list of astrological breakpoints of jalali leap year calculation
     * @var array
     */
    private static $astrologicalBreakPoints = [
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
    ];
    
    /**
     * jalali year after @method updateJalaliFromGeorgian
     * @var
     */
    private $year;
    
    /**
     * jalali year month after @method updateJalaliFromGeorgian
     * @var
     */
    private $month;
    
    /**
     * jalali day after @method updateJalaliFromGeorgian
     * @var
     */
    private $day;
    
    /**
     * the Carbon object
     * @var
     */
    private $carbon;
    
    /**
     * @var bool tell the convert method has been called or not
     */
    private $converted = false;
    
    const MINUTE_IN_SECONDS  = 60;
    
    const HOUR_IN_SECONDS    = 3600;        // 60 * 60
    
    const DAY_IN_SECONDS     = 86400;       // 60 * 60 * 24
    
    const WEEK_IN_SECONDS    = 604800;      // 60 * 60 * 24 * 7
    
    const MONTH_IN_SECONDS   = 2592000;     // 60 * 60 * 24 * 30
    
    const YEAR_IN_SECONDS    = 31557600;    // 60 * 60 * 24 * 365.25 ( Actual Factor is 365.24219852 )
    
    const DECADE_IN_SECONDS  = 315576000;   // 60 * 60 * 24 * 365.25 * 10
    
    const CENTURY_IN_SECONDS = 3155760000;  // 60 * 60 * 24 * 365.25 * 10 * 10
    
    /**
     * Creates a DateTimeZone from a string or a DateTimeZone
     *
     * @param DateTimeZone|string|null $object
     *
     * @throws InvalidArgumentException
     *
     * @return DateTimeZone
     */
    protected static function safeCreateDateTimeZone($object) {
        
        if ($object === null) {
            // Don't return null... avoid Bug #52063 in PHP <5.3.6
            return new DateTimeZone(date_default_timezone_get());
        }
        
        if ($object instanceof DateTimeZone) {
            return $object;
        }
        
        $tz = @timezone_open((string) $object);
        
        if ($tz === false) {
            throw new InvalidArgumentException('Unknown or bad timezone (' . $object . ')');
        }
        
        return $tz;
    }
    
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    ////////////////////////// CONSTRUCTORS //////////////////////////
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    
    /**
     * Create a new JDate instance.
     *
     * @param string|null              $date
     * @param DateTimeZone|string|null $tz
     */
    protected function __construct($date = null, $tz = null) {
        
        $this->carbon = new Carbon($date, $tz);
        
        $this->updateJalaliFromGeorgian();
    }
    
    /**
     * Create a JDate instance from a Carbon one
     *
     * @param Carbon $carbon
     *
     * @return static
     */
    protected static function instance(Carbon $carbon) {
        
        return new static($carbon->format('Y-m-d H:i:s.u'), $carbon->getTimeZone());
    }
    
    /**
     * Get a JDate instance for current date and time
     *
     * @param DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function now($tz = null) {
        
        return new static(null, $tz);
    }
    
    /**
     * Create a JDate instance for today
     *
     * @param DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function today($tz = null) {
        
        return static::now($tz)->startOfDay();
    }
    
    /**
     * Create a JDate instance for tomorrow
     *
     * @param DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function tomorrow($tz = null) {
        
        return static::today($tz)->addDay();
    }
    
    /**
     * Create a JDate instance for yesterday
     *
     * @param DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function yesterday($tz = null) {
        
        return static::today($tz)->subDay();
    }
    
    /**
     * Create a JDate instance for the greatest supported date.
     *
     * @return JDate
     */
    public static function maxValue() {
        
        if (PHP_INT_SIZE === 4) {
            // 32 bit (and additionally Windows 64 bit)
            return static::createFromTimestamp(PHP_INT_MAX);
        }
        
        // 64 bit
        return static::create(9999, 12, 31, 23, 59, 59);
    }
    
    /**
     * Create a JDate instance for the lowest supported date.
     *
     * @return JDate
     */
    public static function minValue() {
        
        if (PHP_INT_SIZE === 4) {
            // 32 bit (and additionally Windows 64 bit)
            return static::createFromTimestamp(~PHP_INT_MAX);
        }
        
        // 64 bit
        return static::create(1, 1, 1, 0, 0, 0);
    }
    
    /**
     * Create a new instance from a specific date and time.
     *
     * If any of $year, $month or $day are set to null their now() values
     * will be used.
     *
     * If $hour is null it will be set to its now() value and the default values
     * for $minute and $second will be their now() values.
     * If $hour is not null then the default values for $minute and $second
     * will be 0.
     *
     * @param int|null                 $year
     * @param int|null                 $month
     * @param int|null                 $day
     * @param int|null                 $hour
     * @param int|null                 $minute
     * @param int|null                 $second
     * @param DateTimeZone|string|null $tz
     *
     * @return JDate
     */
    public static function create($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null) {
        
        $instance = static::now($tz);
        
        $instance->year  = $year === null ? $instance->year : $year;
        $instance->month = $month === null ? $instance->month : $month;
        $instance->day   = $day === null ? $instance->day : $day;
        
        //time parts in georgian and jalali are the same
        if ($hour === null) {
            $instance->hour   = date('G');
            $instance->minute = $minute === null ? date('i') : $minute;
            $instance->second = $second === null ? date('s') : $second;
        } else {
            $instance->minute = $minute === null ? 0 : $minute;
            $instance->second = $second === null ? 0 : $second;
        }
        
        return static::createFromFormat('Y-n-j G:i:s', sprintf('%s-%s-%s %s:%02s:%02s', $year, $month, $day, $hour, $minute, $second), $tz);
    }
    
    /**
     * Create an instance from just a date. The time portion is set to now.
     *
     * @param int|null                 $year
     * @param int|null                 $month
     * @param int|null                 $day
     * @param DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function createFromDate($year = null, $month = null, $day = null, $tz = null) {
        
        return static::create($year, $month, $day, null, null, null, $tz);
    }
    
    /**
     * Create an instance from just a time. The date portion is set to today.
     *
     * @param int|null                 $hour
     * @param int|null                 $minute
     * @param int|null                 $second
     * @param DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function createFromTime($hour = null, $minute = null, $second = null, $tz = null) {
        
        return static::create(null, null, null, $hour, $minute, $second, $tz);
    }
    
    /**
     * Create a JDate instance from a specific format
     *
     * @param string                   $format
     * @param string                   $date
     * @param DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function createFromFormat($format, $date, $tz = null) {
        
        if ($tz !== null) {
            $tz = static::safeCreateDateTimeZone($tz);
        }
        
        $instance = new static($tz);
        
        $instance->parseFormat($format, $date);
        
        return $instance;
    }
    
    /**
     * Create an instance from a timestamp
     *
     * @param int                      $timestamp
     * @param DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function createFromTimestamp($timestamp, $tz = null) {
        
        return static::createFromCarbon(Carbon::createFromTimestamp($timestamp, $tz));
        
    }
    
    /**
     * Create an instance from an UTC timestamp
     *
     * @param int $timestamp
     *
     * @return static
     */
    public static function createFromTimestampUTC($timestamp) {
        
        return static::createFromCarbon(Carbon::createFromTimestampUTC($timestamp));
    }
    
    /**
     * Get a copy of the instance
     *
     * @return static
     */
    public function copy() {
        
        return static::instance($this->carbon);
    }
    
    /**
     * Create an instance of JDate based an a Carbon instance
     * works the same as @method instance
     *
     * @param Carbon $carbon
     *
     * @return static
     */
    public static function createFromCarbon(Carbon $carbon) {
        
        return static::instance($carbon);
    }
    
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    ////////////////////// LEAP YEAR CALCULATION /////////////////////
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    
    /**
     * tells weather the given year is leap or not in jalali date .
     * by default uses the astrological algorithm (Khayam's algorithm)
     * but if the year is not in the range it uses the arithmetic algorithm
     *
     * @param int  $year the year in jalali date
     *
     * @param bool $yearIsInGeorgian
     *
     * @return bool
     */
    public static function isLeapYear($year, $yearIsInGeorgian = false) {
        
        return static::parseJalali($year, $yearIsInGeorgian)['leap'] == 0;
    }
    
    /**
     * check if the given year is leap based on arithmetic algorithm (ahmad birshak)
     *
     * @param int  $year the year in jalali date
     * @param bool $yearIsInGeorgian
     *
     * @return bool
     */
    protected static function isArithmeticLeapYear($year, $yearIsInGeorgian = false) {
        
        // a change for fit
        if ( ! $yearIsInGeorgian) {
            $year += static::HEGIRA_STARTING_YEAR;
        }
        
        $yearPositionInQuadCycle = static::calculateYearInQuadCycle($year) - 1;
        
        $arithmeticBreakPoints = [
            29,
            62,     // (29 + 33)
            95,     // (29 + 33 + 33)
            128,    // (29 + 33 + 33 + 33)
        ];
        
        foreach ($arithmeticBreakPoints as $i => $break) {
            if (isset($arithmeticBreakPoints[$i + 1])) {
                $nextBreak = $arithmeticBreakPoints[$i + 1];
                if ($yearPositionInQuadCycle >= $break && $yearPositionInQuadCycle < $nextBreak) {
                    $yearPositionInQuadCycle -= $break;
                    break;
                }
            }
        }
        
        // the first year is always a common year
        // other years whose ordinal numbers are divisible by 4 are leap years
        $isLeapYear = false;
        if ($yearPositionInQuadCycle != 0 && ($yearPositionInQuadCycle % 4) == 0) {
            $isLeapYear = true;
        }
        
        return $isLeapYear;
    }
    
    /**
     * check the quad cycle type & count
     *
     * @param int $year the year in jalali calendar
     *
     * @return int|number year in quad cycle
     *
     */
    private static function calculateYearInQuadCycle($year) {
        
        $yearInGrandCycle = static::calculateYearInGrandCycle($year);
        
        // static::FIRST_QUAD_CYCLE;
        $yearInQuadCycle = $yearInGrandCycle % static::FIRST_QUAD_CYCLE;
        
        if ((static::GRAND_CYCLE_LENGTH - static::SECOND_QUAD_CYCLE) < $yearInGrandCycle) {
            
            // static::SECOND_QUAD_CYCLE;
            $yearInQuadCycle = $yearInGrandCycle - (21 * static::FIRST_QUAD_CYCLE);
            
        }
        
        return $yearInQuadCycle;
    }
    
    /**
     * returns the number of year in current grand cycle
     *
     * @param int $year the year in jalali calendar
     *
     * @return number
     *
     */
    private static function calculateYearInGrandCycle($year) {
        
        $grandCycle = static::calculateGrandCycle($year);
        
        if ($grandCycle < 0) {
            
            $year = (static::GRAND_CYCLE_BEGINNING + ($grandCycle * static::GRAND_CYCLE_LENGTH)) - $year;
            
        } elseif ($grandCycle > 0) {
            
            $year = $year - (static::GRAND_CYCLE_BEGINNING + ($grandCycle * static::GRAND_CYCLE_LENGTH));
            
        } else {
            
            $year -= static::GRAND_CYCLE_BEGINNING;
        }
        
        $yearInGrandCycle = abs($year) + 1;
        
        return $yearInGrandCycle;
    }
    
    /**
     * calculate that which grand cycle we are in
     *
     * @param int $year the year in jalali calendar
     *
     * @return int
     *
     */
    private static function calculateGrandCycle($year) {
        
        $endOfFirstGrandCycle = static::GRAND_CYCLE_BEGINNING + static::GRAND_CYCLE_LENGTH;
        
        // by default we are in the first grand cycle
        $grandCycle = 0;
        if ($year < static::GRAND_CYCLE_BEGINNING) {
            
            $beginningYear = static::GRAND_CYCLE_BEGINNING;
            while ($year < $beginningYear) {
                $beginningYear -= static::GRAND_CYCLE_LENGTH;
                $grandCycle--;
            }
            
        } elseif ($year >= $endOfFirstGrandCycle) {
            
            $beginningYear = $endOfFirstGrandCycle;
            while ($year >= $beginningYear) {
                $beginningYear += static::GRAND_CYCLE_LENGTH;
                $grandCycle++;
            }
        }
        
        return $grandCycle;
    }
    
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    //////////// GETTERS & SETTERS & PUBLIC METHODS //////////////////
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    
    /**
     * this method converts georgian to jalali
     *
     * @param string $format
     *
     * @return string
     */
    public function format($format) {
        
        // first of all check for convention
        $this->updateJalaliFromGeorgian();
        
        // Find what to replace
        $characters = (preg_match_all('/([a-zA-Z]{1})/', $format, $characters)) ? $characters[0] : [];
        
        // making the replace array
        $replaces = [];
        foreach ($characters as $character) {
            
            switch ($character) {
                
                // Intact ones
                case 'B':
                case 'h':
                case 'H':
                case 'g':
                case 'G':
                case 'i':
                case 's':
                case 'I':
                case 'U':
                case 'u':
                case 'Z':
                case 'O':
                case 'P':
                case 'e':
                case 'T':
                    $replace = $this->carbon->format($character);
                    break;
                
                // Day
                case 'd':
                    $replace = sprintf("%02d", $this->day);
                    break;
                case 'D':
                    $replace = static::getDayName($this->carbon->format('D'), true);
                    break;
                case 'j':
                    $replace = $this->day;
                    break;
                case 'l':
                    $replace = static::getDayName($this->carbon->format('l'));
                    break;
                case 'N':
                    $replace = static::getDayName($this->carbon->format('l'), false, 1, true);
                    break;
                case 'S':
                    $replace = static::t('th');
                    break;
                case 'w':
                    $replace = static::getDayName($this->carbon->format('l'), false, 1, true) - 1;
                    break;
                case 'z':
                    $replace = static::getDayOfYear($this->day, $this->month);
                    break;
                
                // Week
                case 'W':
                    $replace = ceil(static::getDayOfYear($this->day, $this->month) / 7);
                    break;
                
                // Month
                case 'F':
                    $replace = static::getMonthName($this->month);
                    break;
                case 'm':
                    $replace = sprintf("%02d", $this->month);
                    break;
                case 'M':
                    $replace = static::getMonthName($this->month, true);
                    break;
                case 'n':
                    $replace = $this->month;
                    break;
                case 't':
                    $replace = static::getMonthLength($this->month, $this->year);
                    break;
                
                // Year
                case 'L':
                    $replace = static::isLeapYear($this->year, true) ? 1 : 0;
                    break;
                case 'o':
                case 'Y':
                    $replace = $this->year;
                    break;
                case 'y':
                    $replace = $this->year % 100;
                    break;
                
                // Time
                case 'a':
                    $replace = static::t($this->carbon->format('a'));
                    break;
                case 'A':
                    $replace = static::t($this->carbon->format('a'));
                    break;
                
                // Full Dates
                case 'c':
                    $replace = $this->year . '-' .
                               sprintf("%02d", $this->month) . '-' .
                               sprintf("%02d", $this->day) . 'T' .
                               $this->carbon->format('H') . ':' .
                               $this->carbon->format('i') . ':' .
                               $this->carbon->format('s') .
                               $this->carbon->format('P');
                    break;
                case 'r':
                    $replace = static::getDayName($this->carbon->format('D'), true) . ', ' .
                               sprintf("%02d", $this->day) . ' ' .
                               static::getMonthName($this->month, true) . ' ' .
                               $this->year . ' ' .
                               $this->carbon->format('H') . ':' .
                               $this->carbon->format('i') . ':' .
                               $this->carbon->format('s') . ' ' .
                               $this->carbon->format('P');
                    break;
                default:
                    $replace = $character;
            }
            
            //do the replace
            $replaces[$character] = $replace;
            
        }
        
        return strtr($format, $replaces);
    }
    
    /**
     * returns the relative time from/to now
     *
     * @param int  $limit
     * @param null $before
     * @param null $after
     *
     * @return string
     */
    public function relative($limit = 2, $before = null, $after = null) {
        
        $this->updateJalaliFromGeorgian();
        
        $diff = $this->carbon->diffInSeconds();
        if (abs($diff) < 10) {
            return static::t('right now');
        }
        
        $time    = static::scopes();
        $seconds = array_reverse(array_keys($time));
        $titles  = array_reverse(array_values($time));
        $stamp   = abs($diff);
        
        $out = [];
        foreach ($seconds as $k => $scope) {
            if ($stamp >= $scope) {
                $remainPart = (int) floor($stamp / $scope);
                if ($limit) {
                    $stamp -= ($remainPart * $scope);
                    $out[] = $remainPart . ' ' . $titles[$k];
                    $limit--;
                }
            }
        }
        
        $out = implode(' ' . static::t('and') . ' ', $out);
        
        if ($this->carbon->isFuture()) {
            if (empty($after)) {
                $after = static::t('after');
            }
            $out .= ' ' . $after;
        } else {
            if (empty($before)) {
                $before = static::t('before');
            }
            $out .= ' ' . $before;
        }
        
        return $out;
        
    }
    
    /**
     * if the date difference from/to now is less than $change the relative date returns
     * otherwise the date returns in $format format
     *
     * @param int         $limit
     * @param null|string $format
     * @param int|null    $change
     *
     * @return string
     */
    public function smart($limit = 3, $format = self::DEFAULT_TO_STRING_FORMAT, $change = self::WEEK_IN_SECONDS) {
        
        $diff = $this->carbon->diffInSeconds();
        
        if ($change <= abs($diff)) {
            
            return $this->format($format);
        }
        
        return $this->relative($limit);
    }
    
    /**
     * Get a part of the Carbon object
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return string|int|DateTimeZone
     */
    public function __get($name) {
        
        $this->updateJalaliFromGeorgian();
        
        switch (true) {
            case array_key_exists($name, $formats = [
                'year'        => 'Y',
                'yearIso'     => 'o',
                'month'       => 'n',
                'day'         => 'j',
                'hour'        => 'G',
                'minute'      => 'i',
                'second'      => 's',
                'micro'       => 'u',
                'dayOfWeek'   => 'w',
                'dayOfYear'   => 'z',
                'weekOfYear'  => 'W',
                'daysInMonth' => 't',
                'timestamp'   => 'U',
            ]):
                return (int) $this->format($formats[$name]);
            
            case $name === 'weekOfMonth':
                return (int) ceil($this->day / Carbon::DAYS_PER_WEEK);
            
            case $name === 'age':
                return (int) $this->carbon->diffInYears();
            
            case $name === 'quarter':
                return (int) ceil($this->month / 3);
            
            case $name === 'offset':
                return $this->carbon->getOffset();
            
            case $name === 'offsetHours':
                return $this->carbon->getOffset() / Carbon::SECONDS_PER_MINUTE / Carbon::MINUTES_PER_HOUR;
            
            case $name === 'dst':
                return $this->format('I') === '1';
            
            case $name === 'local':
                return $this->carbon->offset === $this->carbon->copy()->setTimezone(date_default_timezone_get())->offset;
            
            case $name === 'utc':
                return $this->carbon->offset === 0;
            
            case $name === 'timezone' || $name === 'tz':
                return $this->carbon->getTimezone();
            
            case $name === 'timezoneName' || $name === 'tzName':
                return $this->carbon->getTimezone()->getName();
            
            // the Carbon instance
            case $name === 'carbon' :
                return $this->carbon;
            
            default:
                throw new InvalidArgumentException(sprintf("Unknown getter '%s'", $name));
        }
    }
    
    /**
     * Format the instance as a string using the set format
     *
     * @return string
     */
    public function __toString() {
        
        return $this->format(static::DEFAULT_TO_STRING_FORMAT);
    }
    
    /**
     * Format the instance as date
     *
     * @return string
     */
    public function toDateString() {
        
        return $this->format('Y-m-d');
    }
    
    /**
     * Format the instance as a readable date
     *
     * @return string
     */
    public function toFormattedDateString() {
        
        return $this->format('M j, Y');
    }
    
    /**
     * Format the instance as time
     *
     * @return string
     */
    public function toTimeString() {
        
        return $this->format('H:i:s');
    }
    
    /**
     * Format the instance as date and time
     *
     * @return string
     */
    public function toDateTimeString() {
        
        return $this->format('Y-m-d H:i:s');
    }
    
    /**
     * Format the instance with day, date and time
     *
     * @return string
     */
    public function toDayDateTimeString() {
        
        return $this->format('D, M j, Y g:i A');
    }
    
    /**
     * Format the instance as ATOM
     *
     * @return string
     */
    public function toAtomString() {
        
        return $this->format(Carbon::ATOM);
    }
    
    /**
     * Format the instance as COOKIE
     *
     * @return string
     */
    public function toCookieString() {
        
        return $this->format(Carbon::COOKIE);
    }
    
    /**
     * Format the instance as ISO8601
     *
     * @return string
     */
    public function toIso8601String() {
        
        return $this->format(Carbon::ISO8601);
    }
    
    /**
     * Format the instance as RFC822
     *
     * @return string
     */
    public function toRfc822String() {
        
        return $this->format(Carbon::RFC822);
    }
    
    /**
     * Format the instance as RFC850
     *
     * @return string
     */
    public function toRfc850String() {
        
        return $this->format(Carbon::RFC850);
    }
    
    /**
     * Format the instance as RFC1036
     *
     * @return string
     */
    public function toRfc1036String() {
        
        return $this->format(Carbon::RFC1036);
    }
    
    /**
     * Format the instance as RFC1123
     *
     * @return string
     */
    public function toRfc1123String() {
        
        return $this->format(Carbon::RFC1123);
    }
    
    /**
     * Format the instance as RFC2822
     *
     * @return string
     */
    public function toRfc2822String() {
        
        return $this->format(Carbon::RFC2822);
    }
    
    /**
     * Format the instance as RFC3339
     *
     * @return string
     */
    public function toRfc3339String() {
        
        return $this->format(Carbon::RFC3339);
    }
    
    /**
     * Format the instance as RSS
     *
     * @return string
     */
    public function toRssString() {
        
        return $this->format(Carbon::RSS);
    }
    
    /**
     * Format the instance as W3C
     *
     * @return string
     */
    public function toW3cString() {
        
        return $this->format(Carbon::W3C);
    }
    
    /**
     * Check if an attribute exists on the object
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name) {
        
        try {
            $this->__get($name);
        } catch (InvalidArgumentException $e) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Set a part of the Carbon object
     *
     * @param string                  $name
     * @param string|int|DateTimeZone $value
     *
     * @throws InvalidArgumentException
     */
    public function __set($name, $value) {
        
        switch ($name) {
            case 'year':
                $this->setDate($value, $this->month, $this->day);
                break;
            
            case 'month':
                $this->setDate($this->year, $value, $this->day);
                break;
            
            case 'day':
                $this->setDate($this->year, $this->month, $value);
                break;
            
            case 'hour':
                $this->setTime($value, $this->minute, $this->second);
                break;
            
            case 'minute':
                $this->setTime($this->hour, $value, $this->second);
                break;
            
            case 'second':
                $this->setTime($this->hour, $this->minute, $value);
                break;
            
            case 'timestamp':
                $this->timestamp($value);
                break;
            
            case 'timezone':
            case 'tz':
                $this->timezone($value);
                break;
            
            default:
                throw new InvalidArgumentException(sprintf("Unknown setter '%s'", $name));
        }
    }
    
    /**
     * Update year in jalali date
     *
     * @param int $year
     */
    private function setYear($year) {
        
        //preventing duplication process
        if ($year == $this->year) {
            return;
        }
        $this->year          = (int) $year;
        $maximumDayAvailable = static::getMonthLength($this->year, $this->month);
        if ($this->day > $maximumDayAvailable) {
            $this->day = $maximumDayAvailable;
        }
    }
    
    /**
     * Update month in jalali date
     *
     * @param int $month
     */
    private function setMonth($month) {
        
        //preventing duplication process
        if ($month == $this->month) {
            return;
        }
        $yearToSet  = $this->year;
        $monthToSet = $month;
        
        if ($monthToSet < 1) {
            $monthToSet = abs($monthToSet);
            $yearToSet--;
            
            $yearToSet -= floor($monthToSet / 12);
            $monthToSet = 12 - ($monthToSet % 12);
            
        } elseif ($monthToSet > 12) {
            $yearToSet += floor($monthToSet / 12);
            $monthToSet = ($month % 12);
            if ($monthToSet == 0) {
                $monthToSet = 12;
                $yearToSet--;
            }
        }
        
        $this->month = (int) $monthToSet;
        $this->setYear($yearToSet);
    }
    
    /**
     * Update day in jalali date
     *
     * @param int $day
     */
    private function setDay($day) {
        
        //preventing duplication process
        if ($day == $this->day) {
            return;
        }
        $maximumDayOfMonth = static::getMonthLength($this->month, $this->year);
        $dayToSet          = $day;
        if ($dayToSet < 1) {
            
            $dayToSet = abs($dayToSet);
            
            while ($dayToSet > $maximumDayOfMonth) {
                $dayToSet -= $maximumDayOfMonth;
                $month = $this->month - 1;
                $this->setMonth($month);
                $maximumDayOfMonth = static::getMonthLength($this->month, $this->year);
            }
            
            $dayToSet = $maximumDayOfMonth - $dayToSet;
            $month    = $this->month - 1;
            $this->setMonth($month);
            
        } elseif ($dayToSet > $maximumDayOfMonth) {
            while ($dayToSet > $maximumDayOfMonth) {
                $dayToSet -= $maximumDayOfMonth;
                $month = $this->month + 1;
                $this->setMonth($month);
                $maximumDayOfMonth = static::getMonthLength($this->month, $this->year);
            }
        }
        
        $this->day = (int) $dayToSet;
    }
    
    /**
     * Set the instance's year
     *
     * @param int $value
     *
     * @return static
     */
    public function year($value) {
        
        $this->setDate($value, $this->month, $this->day);
        
        return $this;
    }
    
    /**
     * Set the instance's month
     *
     * @param int $value
     *
     * @return static
     */
    public function month($value) {
        
        $this->setDate($this->year, $value, $this->day);
        
        return $this;
    }
    
    /**
     * Set the instance's day
     *
     * @param int $value
     *
     * @return static
     */
    public function day($value) {
        
        $this->setDate($this->year, $this->month, $value);
        
        return $this;
    }
    
    /**
     * Set the instance's hour
     *
     * @param int $value
     *
     * @return static
     */
    public function hour($value) {
        
        $this->carbon->hour($value);
        
        return $this;
    }
    
    /**
     * Set the instance's minute
     *
     * @param int $value
     *
     * @return static
     */
    public function minute($value) {
        
        $this->carbon->minute($value);
        
        return $this;
    }
    
    /**
     * Set the instance's second
     *
     * @param int $value
     *
     * @return static
     */
    public function second($value) {
        
        $this->carbon->second($value);
        
        return $this;
    }
    
    /**
     * Sets the current date of the DateTime object to a different date.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return $this
     */
    public function setDate($year, $month, $day) {
        
        $this->setYear((int) $year);
        $this->setMonth((int) $month);
        $this->setDay((int) $day);
        
        $this->updateGeorgianFromJalali();
        
        return $this;
    }
    
    /**
     * Sets the current time of the DateTime object to a different time.
     *
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @return $this
     */
    public function setTime($hour, $minute, $second = 0) {
        
        $this->carbon->setTime($hour, $minute, $second);
        
        return $this;
    }
    
    /**
     * Set the date and time all together
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @return static
     */
    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0) {
        
        return $this->setDate($year, $month, $day)->setTime($hour, $minute, $second);
    }
    
    /**
     * Set the time by time string
     *
     * @param string $time
     *
     * @return static
     */
    public function setTimeFromTimeString($time) {
        
        $this->carbon->setTimeFromTimeString($time);
        
        return $this;
    }
    
    /**
     * Set the instance's timestamp
     *
     * @param int $value
     *
     * @return static
     */
    public function timestamp($value) {
        
        $this->carbon->setTimestamp($value);
        
        return $this;
    }
    
    /**
     * Set the instance's timezone
     *
     * @param DateTimeZone|string $value
     *
     * @return static
     */
    public function timezone($value) {
        
        $this->carbon->setTimezone($value);
        
        return $this;
    }
    
    /**
     * Alias for timezone()
     *
     * @param DateTimeZone|string $value
     *
     * @return static
     */
    public function tz($value) {
        
        return $this->timezone($value);
    }
    
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    /////////////////// ADDITIONS AND SUBTRACTIONS ///////////////////
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    
    /**
     * Add years to the instance. Positive $value travel forward while
     * negative $value travel into the past.
     *
     * @param int $value
     *
     * @return static
     */
    public function addYears($value) {
        
        $yearToSet = (int) $this->year + $value;
        
        return $this->setDate($yearToSet, $this->month, $this->day);
    }
    
    /**
     * Add a year to the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function addYear($value = 1) {
        
        return $this->addYears($value);
    }
    
    /**
     * Remove a year from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subYear($value = 1) {
        
        return $this->subYears($value);
    }
    
    /**
     * Remove years from the instance.
     *
     * @param int $value
     *
     * @return static
     */
    public function subYears($value) {
        
        return $this->addYears(-1 * $value);
    }
    
    /**
     * Add months to the instance. Positive $value travels forward while
     * negative $value travels into the past.
     *
     * @param int $value
     *
     * @return static
     */
    public function addMonths($value) {
        
        $monthToSet = (int) $this->month + $value;
        
        return $this->setDate($this->year, $monthToSet, $this->day);
    }
    
    /**
     * Add a month to the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function addMonth($value = 1) {
        
        return $this->addMonths($value);
    }
    
    /**
     * Remove a month from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subMonth($value = 1) {
        
        return $this->subMonths($value);
    }
    
    /**
     * Remove months from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subMonths($value) {
        
        return $this->addMonths(-1 * $value);
    }
    
    /**
     * Add days to the instance. Positive $value travels forward while
     * negative $value travels into the past.
     *
     * @param int $value
     *
     * @return static
     */
    public function addDays($value) {
        
        $dayToSet = (int) $this->day + $value;
        
        return $this->setDate($this->year, $this->month, $dayToSet);
    }
    
    /**
     * Add a day to the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function addDay($value = 1) {
        
        return $this->addDays($value);
    }
    
    /**
     * Remove a day from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subDay($value = 1) {
        
        return $this->subDays($value);
    }
    
    /**
     * Remove days from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subDays($value) {
        
        return $this->addDays(-1 * $value);
    }
    
    /**
     * Add hours to the instance. Positive $value travels forward while
     * negative $value travels into the past.
     *
     * @param int $value
     *
     * @return static
     */
    public function addHours($value) {
        
        $this->carbon->modify((int) $value . ' hour');
        
        return $this;
    }
    
    /**
     * Add an hour to the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function addHour($value = 1) {
        
        return $this->addHours($value);
    }
    
    /**
     * Remove an hour from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subHour($value = 1) {
        
        return $this->subHours($value);
    }
    
    /**
     * Remove hours from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subHours($value) {
        
        return $this->addHours(-1 * $value);
    }
    
    /**
     * Add minutes to the instance. Positive $value travels forward while
     * negative $value travels into the past.
     *
     * @param int $value
     *
     * @return static
     */
    public function addMinutes($value) {
        
        $this->carbon->modify((int) $value . ' minute');
        
        return $this;
    }
    
    /**
     * Add a minute to the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function addMinute($value = 1) {
        
        return $this->addMinutes($value);
    }
    
    /**
     * Remove a minute from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subMinute($value = 1) {
        
        return $this->subMinutes($value);
    }
    
    /**
     * Remove minutes from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subMinutes($value) {
        
        return $this->addMinutes(-1 * $value);
    }
    
    /**
     * Add seconds to the instance. Positive $value travels forward while
     * negative $value travels into the past.
     *
     * @param int $value
     *
     * @return static
     */
    public function addSeconds($value) {
        
        $this->carbon->modify((int) $value . ' second');
        
        return $this;
    }
    
    /**
     * Add a second to the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function addSecond($value = 1) {
        
        return $this->addSeconds($value);
    }
    
    /**
     * Remove a second from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subSecond($value = 1) {
        
        return $this->subSeconds($value);
    }
    
    /**
     * Remove seconds from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subSeconds($value) {
        
        return $this->addSeconds(-1 * $value);
    }
    
    ///////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////
    ////////////////// RELATIVE GETTERS & SETTERS /////////////////////
    ///////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////
    
    /**
     * Resets the time to 00:00:00
     *
     * @return static
     */
    public function startOfDay() {
        
        return $this->hour(0)->minute(0)->second(0);
    }
    
    /**
     * Resets the time to 23:59:59
     *
     * @return static
     */
    public function endOfDay() {
        
        return $this->hour(23)->minute(59)->second(59);
    }
    
    /**
     * Resets the date to the first day of the month and the time to 00:00:00
     *
     * @return static
     */
    public function startOfMonth() {
        
        return $this->day(1)->startOfDay();
    }
    
    /**
     * Resets the date to end of the month and time to 23:59:59
     *
     * @return static
     */
    public function endOfMonth() {
        
        $lastDay = static::getMonthLength($this->month, $this->year);
        
        return $this->day($lastDay)->endOfDay();
    }
    
    /**
     * Resets the date to the first day of the year and the time to 00:00:00
     *
     * @return static
     */
    public function startOfYear() {
        
        return $this->month(1)->startOfMonth();
    }
    
    /**
     * Resets the date to end of the year and time to 23:59:59
     *
     * @return static
     */
    public function endOfYear() {
        
        return $this->month(Carbon::MONTHS_PER_YEAR)->endOfMonth();
    }
    
    /**
     * Resets the date to the first day of the decade and the time to 00:00:00
     *
     * @return static
     */
    public function startOfDecade() {
        
        return $this->year($this->year - $this->year % Carbon::YEARS_PER_DECADE)->startOfYear();
    }
    
    /**
     * Resets the date to end of the decade and time to 23:59:59
     *
     * @return static
     */
    public function endOfDecade() {
        
        return $this->year($this->year - $this->year % Carbon::YEARS_PER_DECADE + Carbon::YEARS_PER_DECADE - 1)->endOfYear();
    }
    
    /**
     * Resets the date to the first day of the century and the time to 00:00:00
     *
     * @return static
     */
    public function startOfCentury() {
        
        return $this->year($this->year - $this->year % Carbon::YEARS_PER_CENTURY)->startOfYear();
    }
    
    /**
     * Resets the date to end of the century and time to 23:59:59
     *
     * @return static
     */
    public function endOfCentury() {
        
        return $this->endOfYear()->year($this->year - $this->year % Carbon::YEARS_PER_CENTURY + Carbon::YEARS_PER_CENTURY - 1);
    }
    
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    ///////////////////////////// MISC ///////////////////////////////
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    
    /**
     * @return array a list of time scopes in second
     */
    public static function scopes() {
        
        return [
            1                          => static::t('second'),
            static::MINUTE_IN_SECONDS  => static::t('minute'),
            static::HOUR_IN_SECONDS    => static::t('hour'),
            static::DAY_IN_SECONDS     => static::t('day'),
            static::WEEK_IN_SECONDS    => static::t('week'),
            static::MONTH_IN_SECONDS   => static::t('month'),
            static::YEAR_IN_SECONDS    => static::t('year'),
            static::DECADE_IN_SECONDS  => static::t('decade'),
            static::CENTURY_IN_SECONDS => static::t('century'),
        ];
        
    }
    
    /**
     * return a word from translations
     *
     * @param $word
     *
     * @return mixed
     */
    protected static function t($word) {
        
        $list = [
            'and'         => 'و',
            'after'       => 'دیگر',
            'before'      => 'پیش',
            'right now'   => 'همین الان',
            'never'       => 'هیچ وقت',
            'second'      => 'ثانیه',
            'minute'      => 'دقیقه',
            'hour'        => 'ساعت',
            'day'         => 'روز',
            'week'        => 'هفته',
            'month'       => 'ماه',
            'year'        => 'سال',
            'decade'      => 'دهه',
            'century'     => 'قرن',
            'saturday'    => 'شنبه',
            'sunday'      => 'یکشنبه',
            'monday'      => 'دوشنبه',
            'tuesday'     => 'سه شنبه',
            'wednesday'   => 'چهارشنبه',
            'thursday'    => 'پنج شنبه',
            'friday'      => 'جمعه',
            'farvardin'   => 'فروردین',
            'ordibehesht' => 'اردیبهشت',
            'khordad'     => 'خرداد',
            'tir'         => 'تیر',
            'mordad'      => 'مرداد',
            'shahrivar'   => 'شهریور',
            'mehr'        => 'مهر',
            'aban'        => 'آبان',
            'azar'        => 'آذر',
            'dey'         => 'دی',
            'bahman'      => 'بهمن',
            'esfand'      => 'اسفند',
            'am'          => 'ق.ظ',
            'pm'          => 'ب.ظ',
            'AM'          => 'قبل از ظهر',
            'PM'          => 'بعد از ظهر',
            'th'          => 'ام',
        
        ];
        
        if (array_key_exists($word, $list)) {
            return $list[$word];
        }
        throw new InvalidArgumentException("'{$word}' dosen\'t exists in translations.");
    }
    
    /**
     * @param int $month the month in jalali date
     * @param int $year  the year in jalali date
     *
     * @return int
     */
    protected static function getMonthLength($month, $year) {
        
        if ($month <= 6) {
            return 31;
        }
        if ($month <= 11) {
            return 30;
        }
        
        return static::isLeapYear($year) ? 30 : 29;
    }
    
    /**
     * @param int $day   day in jalali date
     * @param int $month month in jalali date
     *
     * @return int
     */
    protected static function getDayOfYear($day, $month) {
        
        $dayOfYear = $day;
        if ($month > 6) {
            $dayOfYear += (186 + (($month - 6 - 1) * 30));
        } else {
            $dayOfYear += (($month - 1) * 31);
        }
        
        return $dayOfYear;
    }
    
    /**
     * @param string $day
     * @param bool   $shorten
     * @param int    $len
     * @param bool   $numeric
     *
     * @return int|string
     */
    protected static function getDayName($day, $shorten = false, $len = 1, $numeric = false) {
        
        switch (strtolower($day)) {
            case 'sat':
            case 'saturday':
                $name   = static::t('saturday');
                $number = 1;
                break;
            case 'sun':
            case 'sunday':
                $name   = static::t('sunday');
                $number = 2;
                break;
            case 'mon':
            case 'monday':
                $name   = static::t('monday');
                $number = 3;
                break;
            case 'tue':
            case 'tuesday':
                $name   = static::t('tuesday');
                $number = 4;
                break;
            case 'wed':
            case 'wednesday':
                $name   = static::t('wednesday');
                $number = 5;
                break;
            case 'thu':
            case 'thursday':
                $name   = static::t('thursday');
                $number = 6;
                break;
            case 'fri':
            case 'friday':
                $name   = static::t('friday');
                $number = 7;
                break;
            default:
                throw new InvalidArgumentException("'{$day}' is not a week day.");
        }
        
        return ($numeric) ? $number : (($shorten) ? mb_substr($name, 0, $len, 'UTF-8') : $name);
    }
    
    /**
     * @param int  $month
     * @param bool $shorten
     * @param int  $len
     *
     * @return string
     */
    protected static function getMonthName($month, $shorten = false, $len = 3) {
        
        switch ((int) $month) {
            case 1:
                $name = static::t('farvardin');
                break;
            case 2:
                $name = static::t('ordibehesht');
                break;
            case 3:
                $name = static::t('khordad');
                break;
            case 4:
                $name = static::t('tir');
                break;
            case 5:
                $name = static::t('mordad');
                break;
            case 6:
                $name = static::t('shahrivar');
                break;
            case 7:
                $name = static::t('mehr');
                break;
            case 8:
                $name = static::t('aban');
                break;
            case 9:
                $name = static::t('azar');
                break;
            case 10:
                $name = static::t('dey');
                break;
            case 11:
                $name = static::t('bahman');
                break;
            case 12:
                $name = static::t('esfand');
                break;
            default:
                throw new InvalidArgumentException("'{$month}' is not a month name.");
        }
        
        return ($shorten) ? mb_substr($name, 0, $len, 'UTF-8') : $name;
    }
    
    /**
     * parses the given date through the given format and updates the properties
     *
     * @param $format
     * @param $date
     */
    private function parseFormat($format, $date) {
        
        $this->updateJalaliFromGeorgian();
        
        // reverse engineer date formats
        $keys = [
            'Y' => ['year', '\d{4}'],
            'y' => ['year', '\d{2}'],
            'm' => ['month', '\d{2}'],
            'n' => ['month', '\d{1,2}'],
            'M' => ['month', '[A-Z][a-z]{3}'],
            'F' => ['month', '[A-Z][a-z]{2,8}'],
            'd' => ['day', '\d{2}'],
            'j' => ['day', '\d{1,2}'],
            'D' => ['day', '[A-Z][a-z]{2}'],
            'l' => ['day', '[A-Z][a-z]{6,9}'],
            'u' => ['hour', '\d{1,6}'],
            'h' => ['hour', '\d{2}'],
            'H' => ['hour', '\d{2}'],
            'g' => ['hour', '\d{1,2}'],
            'G' => ['hour', '\d{1,2}'],
            'i' => ['minute', '\d{2}'],
            's' => ['second', '\d{2}'],
        ];
        
        // convert format string to regex
        $regex      = '';
        $characters = str_split($format);
        foreach ($characters as $n => $character) {
            $lastCharacter = isset($characters[$n - 1]) ? $characters[$n - 1] : '';
            $skipCurrent   = '\\' == $lastCharacter;
            if ( ! $skipCurrent && isset($keys[$character])) {
                $regex .= '(?P<' . $keys[$character][0] . '>' . $keys[$character][1] . ')';
            } else {
                if ('\\' == $character) {
                    $regex .= $character;
                } else {
                    $regex .= preg_quote($character);
                }
            }
        }
        
        $sections = [];
        // now try to match it
        if (preg_match('#^' . $regex . '$#', $date, $sections)) {
            foreach ($sections as $k => $v) {
                if (is_int($k)) {
                    unset($sections[$k]);
                }
            }
        } else {
            throw new InvalidArgumentException('the given date is not valid through the given format.');
        }
        
        $yearToSet = $this->year;
        if (isset($sections['year'])) {
            
            $yearToSet = $sections['year'];
            
            // checking to make the year absolute
            if (strlen($yearToSet) == 2) {
                $instance = static::now();
                $yearToSet += (int) ($instance->format('Y') - $instance->format('y'));
            }
        }
        $monthToSet = $this->month;
        if (isset($sections['month'])) {
            $monthToSet = $sections['month'];
        }
        $dayToSet = $this->day;
        if (isset($sections['day'])) {
            $dayToSet = $sections['day'];
            
            //there's an exception
            $max = static::getMonthLength($monthToSet, $yearToSet);
            if ($dayToSet > $max) {
                throw new InvalidArgumentException('the current month maximum days is ' . $max);
            }
        }
        $hourToSet = $this->hour;
        if (isset($sections['hour'])) {
            $hourToSet = $sections['hour'];
        }
        $minuteToSet = $this->minute;
        if (isset($sections['minute'])) {
            $minuteToSet = $sections['minute'];
        }
        $secondToSet = $this->second;
        if (isset($sections['second'])) {
            $secondToSet = $sections['second'];
        }
        
        $this->setDateTime($yearToSet, $monthToSet, $dayToSet, $hourToSet, $minuteToSet, $secondToSet);
        
        $this->updateGeorgianFromJalali();
        
    }
    
    /**
     * Update the Jalali Core from the Carbon As Georgian Core
     *
     * @param bool $force
     *
     * @return int
     */
    private function updateJalaliFromGeorgian($force = false) {
        
        if ($this->converted && ! $force) {
            return;
        }
        
        list($year, $month, $day) = self::julian2jalali(self::georgian2julian($this->carbon->year, $this->carbon->month, $this->carbon->day));
        
        $this->year  = $year;
        $this->month = $month;
        $this->day   = $day;
        
        // it tells that the jalali core is updated
        $this->converted = true;
        
    }
    
    /**
     * Update the Carbon instance as Georgian Core and obsoletes the Jalali core
     * so the Jalali Core have to be updated in the first __get call
     */
    private function updateGeorgianFromJalali() {
        
        list($gYear, $gMonth, $gDay) = static::julian2georgian(static::jalali2julian($this->year, $this->month, $this->day));
        
        $this->carbon->year($gYear);
        $this->carbon->month($gMonth);
        $this->carbon->day($gDay);
        
        // after modifying the methods we should re convert the date
        $this->converted = false;
        
    }
    
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    //////////// CONVERTS WITH HELP OF JULIAN DAY NUMBER /////////////
    //////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////
    
    /**
     * This function determines if the Jalali (Persian) year is
     * leap (366-day long) or is the common year (365 days), and
     * finds the day in March (Gregorian calendar) of the first
     * day of the Jalali year (jy).
     *
     * @param int  $year Jalali calendar year (-61 to 3177)
     *
     * @param bool $yearIsInGeorgian
     *
     * @return array leap: number of years since the last leap year (0 to 4)
     * leap: number of years since the last leap year (0 to 4)
     * gYear: Gregorian year of the beginning of Jalali year
     * march: the March day of Farvardin the 1st (1st day of jy)
     * @see: http://www.astro.uni.torun.pl/~kb/Papers/EMP/PersianC-EMP.htm
     * @see: http://www.fourmilab.ch/documents/calendar/
     */
    protected static function parseJalali($year, $yearIsInGeorgian = false) {
        
        $breaksCount = count(static::$astrologicalBreakPoints);
        
        // not in the static::$astrologicalBreakPoints range so we use the other algorithm
        $start = static::$astrologicalBreakPoints[0];
        $end   = static::$astrologicalBreakPoints[$breaksCount - 1];
        $gYear = $year + static::HEGIRA_STARTING_YEAR;
        if ($yearIsInGeorgian) {
            $start += static::HEGIRA_STARTING_YEAR;
            $end += static::HEGIRA_STARTING_YEAR;
            $gYear = $year;
        }
        
        //out of the astrological break points
        if ($year < $start || $end <= $year) {
            
            $jLeap = static::isArithmeticLeapYear($year, $yearIsInGeorgian);
            $march = 20;
            if ($jLeap) {
                $march--;
            }
            
            return [
                'leap'  => $jLeap,
                'gYear' => $gYear,
                'march' => $march,
            ];
        }
        
        //the year must be in jalali from here
        if ($yearIsInGeorgian) {
            $year -= static::HEGIRA_STARTING_YEAR;
        }
        
        $jLeap       = -14;
        $recentBreak = static::$astrologicalBreakPoints[0];
        $jump        = 0;
        for ($i = 1; $i < $breaksCount; $i += 1) {
            $mostRecentBreak = static::$astrologicalBreakPoints[$i];
            $jump            = $mostRecentBreak - $recentBreak;
            
            if ($year < $mostRecentBreak) {
                break;
            }
            
            $jLeap       = $jLeap + self::division($jump, 33) * 8 + self::division(($jump % 33), 4);
            $recentBreak = $mostRecentBreak;
        }
        
        $yearsPassed = $year - $recentBreak;
        
        $jLeap = $jLeap + self::division($yearsPassed, 33) * 8 + self::division(($yearsPassed % 33) + 3, 4);
        
        if (($jump % 33) === 4 && $jump - $yearsPassed === 4) {
            $jLeap += 1;
        }
        
        $gLeap = self::division($gYear, 4) - self::division((self::division($gYear, 100) + 1) * 3, 4) - 150;
        
        $march = 20 + $jLeap - $gLeap;
        
        if ($jump - $yearsPassed < 6) {
            $yearsPassed = $yearsPassed - $jump + self::division($jump + 4, 33) * 33;
        }
        
        $leap = (((($yearsPassed + 1) % 33) - 1) % 4);
        
        if ($leap === -1) {
            $leap = 4;
        }
        
        return [
            'leap'  => $leap,
            'gYear' => $gYear,
            'march' => $march,
        ];
    }
    
    /**
     * Calculates Gregorian and Julian calendar dates from the Julian Day number
     * (jdn) for the period since jdn=-34839655 (i.e. the year -100100 of both
     * calendars) to some millions years ahead of the present.
     *
     * @param $julianDayNumber
     *
     * @return array
     */
    protected static function julian2georgian($julianDayNumber) {
        
        $j = 4 * $julianDayNumber + 139361631;
        $j += self::division(self::division(4 * $julianDayNumber + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::division(($j % 1461), 4) * 5 + 308;
        
        $gDay   = self::division(($i % 153), 5) + 1;
        $gMonth = (self::division($i, 153) % 12) + 1;
        $gYear  = self::division($j, 1461) - 100100 + self::division(8 - $gMonth, 6);
        
        return [$gYear, $gMonth, $gDay];
    }
    
    /**
     * Calculates the Julian Day number from Gregorian or Julian
     * calendar dates. This integer number corresponds to the noon of
     * the date (i.e. 12 hours of Universal Time).
     * The procedure was tested to be good since 1 March, -100100 (of both
     * calendars) up to a few million years into the future.
     *
     * @param int $gYear  Calendar year (years BC numbered 0, -1, -2, ...)
     * @param int $gMonth Calendar month (1 to 12)
     * @param int $gDay   Calendar day of the month (1 to 28/29/30/31)
     *
     * @return int Julian Day number
     */
    protected static function georgian2julian($gYear, $gMonth, $gDay) {
        
        return (
                   self::division(($gYear + self::division($gMonth - 8, 6) + 100100) * 1461, 4)
                   + self::division(153 * (($gMonth + 9) % 12) + 2, 5)
                   + $gDay - 34840408
               ) - self::division(self::division($gYear + 100100 + self::division($gMonth - 8, 6), 100) * 3, 4) + 752;
        
    }
    
    /**
     * Converts a date of the Jalali calendar to the Julian Day number.
     *
     * @param int $year  Jalali year    (1 to 3100)
     * @param int $month Jalali month   (1 to 12)
     * @param int $day   Jalali day     (1 to 29/31)
     *
     * @return int  Julian Day number
     */
    protected static function jalali2julian($year, $month, $day) {
        
        $parsed = self::parseJalali($year);
        
        return self::georgian2julian($parsed['gYear'], 3, $parsed['march']) + ($month - 1) * 31 - self::division($month, 7) * ($month - 7) + $day - 1;
    }
    
    /**
     * Converts the Julian Day number to a date in the Jalali calendar.
     *
     * @param int $julianDayNumber Julian Day number
     *
     * @return array
     * 0: Jalali year   (1 to 3100)
     * 1: Jalali month  (1 to 12)
     * 2: Jalali day    (1 to 29/31)
     */
    protected static function julian2jalali($julianDayNumber) {
        
        $gYear  = self::julian2georgian($julianDayNumber)[0];
        $year   = $gYear - static::HEGIRA_STARTING_YEAR;
        $parsed = self::parseJalali($year);
        $jdn1f  = self::georgian2julian($gYear, 3, $parsed['march']);
        
        // Find number of days that passed since 1 Farvardin.
        $passed = $julianDayNumber - $jdn1f;
        
        if ($passed >= 0) {
            if ($passed <= 185) {
                $month = 1 + self::division($passed, 31);
                $day   = ($passed % 31) + 1;
                
                return [$year, $month, $day];
            } else {
                $passed -= 186;
            }
        } else {
            $year -= 1;
            $passed += 179;
            
            if ($parsed['leap'] === 1) {
                $passed += 1;
            }
        }
        
        $month = 7 + self::division($passed, 30);
        $day   = ($passed % 30) + 1;
        
        return [$year, $month, $day];
    }
    
    /**
     * return the division part without modulus
     *
     * @param $section
     * @param $divider
     *
     * @return int
     */
    protected static function division($section, $divider) {
        
        return ($section - ($section % $divider)) / $divider;
        
    }
}

