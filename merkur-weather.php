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
require_once('measurements.php');
global $wpdb;

$table_name = $wpdb->prefix . "weather_merkur2"; 
$directions_nordost=array();
$directions_nordost[0] = array(20, 50); //NO easy
$directions_nordost[1] = array(10, 60); //NO moderate

$speeds = array(18,25);

$directions_west=array();
$directions_west[0] = array(220, 300); //W easy
$directions_west[1] = array(181, 330); //W moderate


$nordost = new Takeoff($speeds, $directions_nordost);
$west=new Takeoff($speeds, $directions_west);
date_default_timezone_set('Europe/Berlin');








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

class MWS_LastRecordsTable
{
	
	public $numRows;
	public $columns; //=array
	private $data; 
	public $html;
	public $table = "wp_weather_merkur2";
	
	public function buildHTML()
	{
		
		$h='<table class="MWS_lastRecords">';
		$h.="<tr>";
		foreach ($this->columns as $header)
		{
			$h.="<th>";
			switch($header)
			{
				case "wind_speed":
				$h.="Geschwindigkeit"; 
				break;
				
				case "wind_maxspeed":
				$h.="Böe"; 
				break;
				
				case "wind_direction":
				$h.="Richtung"; 
				break;
				
				
				case "record_datetime":
				$h.="Zeit"; 
				break;
				
				default: 
				$h.=$header;
				break;
			}
			
			$h.="</th>";
		}
		
		$h.="</tr>";
		
		for ($i=0; $i<$this->numRows; $i++)
		{
			$h.="<tr>";
			
			foreach ($this->data[$i] as $key => $value)
			{
				//$h.="<td>";
				if ($key == 'wind_direction')
				{
					$h.="<td>";
					$dir = new Wind_direction($value);
					
					$h.=$dir->svg_arrow."&nbsp;";
					$h.=$dir->value.$dir->unit." ";
					$h.=$dir->name_short;
					
				} 
				
				else if ($key == 'wind_speed' or $key == 'wind_maxspeed')
				{
					
					$speed = new Wind_speed($value);
					$h.='<td style="background-color: '.$speed->color.';">';
					$h.=$speed->value." ";
					$h.=$speed->unit;
				
				}
				else if ($key == 'record_datetime')
				{
                    $h.="<td>";
                    $time =  new DateTime($value, new DateTimeZone('Europe/Berlin'));
					$h.= date("d.m. H:i", $time);
				}
				else
				{
					$h.="<td>";
					$h.=$value;
					
				}$h.="</td>";
			}
			
			$h.="</tr>";
			
		}
	
	
		$h.="</table>";
		$this->html = $h;
	}
	
	public function pollDatabase()
	{
		global $wpdb;
		$select="";
		$query="SELECT ";
		
		foreach ($this->columns as $col)
		{
			$select.=$col;
			$select.=", ";
		}
		$select=rtrim($select, ', ')." "; //remove last ',' from previous loop
		
		$query.=$select;
		$query.="FROM " . $this->table . " WHERE 1 ORDER BY uid DESC LIMIT 0,";
		$query.=$this->numRows;
		
		$this->data = $wpdb->get_results($query);
		
		
		
		
	}
	public function __construct($columns, $numRows)
	{
		$this->columns=$columns;
		$this->numRows=$numRows;
		$this->pollDatabase();
		$this->buildHTML();
	}
}



function MWS_lastWindSpeed()
{
		$now = new DateTime('now');
        $wind = new MWS_SingleValue("wind_speed", "km/h", $now);
       	return $wind->value. " " . $wind->unit;
		


}

function MWS_lastAverage()
{
	
	
	$begin = 	new DateTime('@1486765140');//dummy
	$end = 		new DateTime('@1486766402');//dummy
	$avg = new MWS_AverageValue("wind_speed", "km/h", $begin, $end);
	return $avg->value . " " . $avg->unit;
}


function lastTenRecords()
{
	$recs = new MWS_LastRecordsTable(array("wind_direction", "wind_speed", "wind_maxspeed", "record_datetime"), 10);
    return $recs->html;
}



add_shortcode('last_windspeed', 'MWS_lastWindSpeed');
add_shortcode('last_average', 'MWS_lastAverage');
add_shortcode('lasttentable', 'lastTenRecords');

?>
