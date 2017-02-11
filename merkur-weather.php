<?php
   /*
   Plugin Name: Wetterstation Merkur
   Plugin URI: https://www.schwarzwaldgeier.de
   Description: Display data obtained from merkur weather station with shortcodes
   Version: 1.0
   Author: Sebastian Schmied
   License: CC BY 4.0
   */
defined( 'ABSPATH' ) or die( 'Must be run as a wordpress plugin' );
//echo "Test ;)";

class MWS
{
		
		public $table = "wp_weather_merkur2";
        public $record_type;
        public $unit;
        public $value;
        public $begin;
        public $end;
        public $query;
        public $sql_where = "1";	
        public $sql_orderby = "tstamp DESC";
        
        public function __construct($record_type, $unit)
        {
                $this->record_type = $record_type;
                $this->unit = $unit;
                
                
        }
        public function getData()
        {
                
                
                $this->value = $wpdb->get_var($this->query);
        }

}

class MWS_SingleValue extends MWS
{
		
		
		
		public function __construct($record_type, $unit, $end)
		{
		  	global $wpdb;
		  	$this->record_type = $record_type;
		  	$this->unit = $unit;
		  	$this->end = $end;
		  	$this->sql_orderby= "ABS(record_datetime - FROM_UNIXTIME(". 
		  			$this->end->getTimestamp() .")) ";
		  	$this->query = 	"SELECT " .
							$this->record_type .
							" FROM " .
							$this->table .
							" WHERE " .
							$this->sql_where .
							" ORDER BY ".
							$this->sql_orderby .
							" LIMIT 0,1";
			$this->value = $wpdb->get_var($this->query);			
		}
		
		
}

class MWS_AverageValue extends MWS
{
	//date objects for avg values:
		public $begin; 
		public $end;	
	
	public function __construct($record_type, $unit, $begin, $end)
		{
		  	global $wpdb;
		  	$this->record_type = $record_type;
		  	$this->unit = $unit;
		  	$this->begin = $begin;
		  	$this->end = $end;
		  	$this->sql_where = "record_datetime BETWEEN FROM_UNIXTIME(". 
		  					$this->begin->getTimestamp() . 
		  					") AND FROM_UNIXTIME(" .
		  					 $this->end->getTimestamp() . 
		  					")";
		  	$this->query = 	"SELECT AVG(" .
							$this->record_type .
							") FROM " .
							$this->table .
							" WHERE " .
							$this->sql_where .
							" ORDER BY ".
							$this->sql_orderby;
			$this->value = $wpdb->get_var($this->query);			
		}

}

function MWS_lastWindSpeed()
{
		$now = new DateTime('now');
        $wind = new MWS_SingleValue("wind_speed", "km/h", $now);
       	return $wind->query." ".$wind->value. " " . $wind->unit;
		


}

function MWS_lastAverage()
{
	
	
	$begin = 	new DateTime('@1486765140');//dummy
	$end = 		new DateTime('@1486766402');//dummy
	$avg = new MWS_AverageValue("wind_speed", "km/h", $begin, $end);
	return $avg->value . " " . $avg->unit;
}

function MWS_printSet($number, $begin)
{
	$header="<table><tr><th>Windgeschwindigkeit</th><th>Zeitpunkt</th></tr>";
	$m;
	$footer="</table>";
	//$records = array();
	$when = $begin;
	$interval = new DateInterval('PT120S'); //2 minutes
	for ($i=0; $i<$number; $i++)
	{
			
			$record = new MWS_SingleValue ("wind_speed", "km/h", $when);
			$m .= "<tr>";
			$m .= "<td>";
			$m .= $record->value;
			
			$m .= "</td>";
			$m .= "<td>";
			$m .= $when->format('d.m.Y H:i:s');
			$m .= "</td>";
			$m .= "</tr>";
			$when->sub($interval);
			//echo $record->query;
			//exit();
			
			
	}
	//echo $header . $m . $footer;
	$html = $header .$m . $footer;
	return $html;
	
	
}
function lastTenRecords()
{
	$now = new DateTime('@1486766402'); //dummy value
	return(MWS_printSet(10, $now));
}



add_shortcode('last_windspeed', 'MWS_lastWindSpeed');
add_shortcode('last_average', 'MWS_lastAverage');
add_shortcode('lasttentable', 'lastTenRecords');

?>