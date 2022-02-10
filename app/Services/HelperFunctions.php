<?php

use App\Exceptions\NotFoundException;
use App\Services\CoreService;
use illuminate\Support\Facades\Route;

if (!function_exists('array_insert')) {
	/**
	 * Insert an array into another array before/after a certain key
	 *
	 * Merge the elements of the $array array after, or before, the designated $key from the $input array.
	 * It returns the resulting array.
	 *
	 * @param array  $input  The input array.
	 * @param mixed  $insert The value to merge.
	 * @param mixed  $key    The key from the $input to merge $insert next to.
	 * @param string $pos    Wether to splice $insert before or after the $key.
	 *
	 * @return array Returns the resulting array.
	 */
	function array_insert(array $input, $insert, $key, $pos = 'after')
	{
		if (!is_string($key) && !is_int($key)) {
			trigger_error('array_insert(): The key should be a string or an integer', E_USER_ERROR);
		}
		$offset = array_search($key, array_keys($input));
	
		if ('after' === $pos) {
			$offset++;
		} else {
			$offset--;
		}
	
		if (false !== $offset) {
			$result = array_slice($input, 0, $offset);
			$result = array_merge($result, (array)$insert, array_slice($input, $offset));
		}
		else {
			$result = array_merge($input, (array)$insert);
		}
	
		return $result;
	}
}

/**
 * Insert a value or key/value pair after a specific key in an array.  If key doesn't exist, value is appended
 * to the end of the array.
 *
 * @param array $array
 * @param string $key
 * @param array $new
 *
 * @return array
 */
function array_insert_after( array $array, $key, array $new ) {
	$keys = array_keys( $array );
	$index = array_search( $key, $keys );
	$pos = false === $index ? count( $array ) : $index + 1;
	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}

/**
 * Insert Array Before
 * @param array
 * @param string key
 * @param array new array
 * @return array
**/

function array_insert_before( $array, $key, $new ) {
    return array_insert( $array, $new, $key, $pos = 'before');
}

/**
 * generate Timezone list
 * @source https://stackoverflow.com/questions/1727077/generating-a-drop-down-list-of-timezones-with-php
 * @return array
**/

function generate_timezone_list()
{
    static $regions = array(
        DateTimeZone::AFRICA,
        DateTimeZone::AMERICA,
        DateTimeZone::ANTARCTICA,
        DateTimeZone::ASIA,
        DateTimeZone::ATLANTIC,
        DateTimeZone::AUSTRALIA,
        DateTimeZone::EUROPE,
        DateTimeZone::INDIAN,
        DateTimeZone::PACIFIC,
    );

    $timezones = array();
    foreach( $regions as $region )
    {
        $timezones = array_merge( $timezones, DateTimeZone::listIdentifiers( $region ) );
    }

    $timezone_offsets = array();
    foreach( $timezones as $timezone )
    {
        $tz = new DateTimeZone($timezone);
        $timezone_offsets[$timezone] = $tz->getOffset(new DateTime);
    }

    // sort timezone by offset
    asort($timezone_offsets);

    $timezone_list = array();
    foreach( $timezone_offsets as $timezone => $offset )
    {
        $offset_prefix = $offset < 0 ? '-' : '+';
        $offset_formatted = gmdate( 'H:i', abs($offset) );

        $pretty_offset = "UTC${offset_prefix}${offset_formatted}";

        $timezone_list[$timezone] = "(${pretty_offset}) $timezone";
    }

    return $timezone_list;
}

/**
 * Route Field
 * Return a hidden tag of the route field
 * @return string
 */
function route_field()
{
	echo '<input type="hidden" name="_route" value="' . Route::currentRouteName() . '">';
	echo '<input type="hidden" name="_previous" value="' . url()->current() . '">';
}

/**
 * Test if a string is a url
 * @param string
 * @return boolean
 */
function isUrl( $text )  
{  
    return filter_var( $text, FILTER_VALIDATE_URL ) !== false;  
}

class UseThisChain  {
    
    public function __construct( $class ) 
    {
        $this->class    =   $class;
    }

    /**
     * 
     * @param string method name
     * @return string result
     */
    public function method( $name ) {
        return $this->class . '@' . $name;
    }
}

/**
 * shorthand way to write 
 * class and method for route 
 * or hooks
 * @param string
 * @return stdClass class
 */
function useThis( string $class ): UseThisChain {
    return new UseThisChain( $class );
}

/**
 * Execute a class @ method
 * @param string class name with method
 * @return void
 */
function execThis( $className ) {
	$vars 	=	explode( $className, '@' );
	if ( count( $vars ) === 2 ) {
		$class 		=	$vars[0];
		$method 	=	$vars[1];
		return (new $class)->$method();
	}
	throw new Exception( sprintf( __( 'Unable to execute the following class callback string : %s' ), $className ) );
}

/**
 * Returns an instance of CoreService
 * @return CoreService
 */
function ns(): CoreService {
    return app()->make( CoreService::class );
}

/**
 * Returns a translated version for a string defined
 * under a module namespace.
 * @param string $key
 * @param string $namespace
 * @return string $result
 */
function __m( $key, $namespace = 'default' ) {
    if ( app( 'translator' )->has( $namespace . '.' . $key  ) ) {
        return app( 'translator' )->get( $namespace . '.' . $key );
    }
    
    return $key;
}

/**
 * Will load css assert
 * @param string $path
 * @return string 
 */
function loadcss( $path ) {
    if ( in_array( strtolower( env( 'NS_ENV' ) ), [ 'prod', 'production' ]) ) {
        $files                  =   json_decode( file_get_contents( base_path( 'public/css-manifest.json' ) ), true );
    
        if ( isset( $files[ $path ] ) ) {
            return asset( 'css/' . $files[ $path ] );
        }
    
        throw new NotFoundException( sprintf(
            __( 'Unable to find the requested asset file "%s".'),
            asset( 'css/' . $path )
        ) );
    } else {
        return asset( 'css/' . $path );
        
        throw new NotFoundException( sprintf(
            __( 'Unable to find the requested asset file "%s".'),
            asset( 'css/' . $path )
        ) );
    }
}