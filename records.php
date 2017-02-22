<?php
//TODO use parameters and less shortcodes
add_shortcode('last_windspeed', 'MWS_lastWindSpeed');

//TODO use parameters and less shortcodes
add_shortcode('WindspeedLastTwentyMinutes', 'MWS_20MAverage');
add_shortcode('WindspeedLastHour', 'MWS_60MAverage');
add_shortcode('WindspeedLastTwoHours', 'MWS_120MAverage');


//TODO parameter for # of records
add_shortcode('lastrecords', 'lastTenRecords');


function lastTenRecords()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "weather_merkur2";
    $directions_nordost = array();
    $directions_nordost[0] = array(
        20,
        50
    ); //NO easy
    $directions_nordost[1] = array(
        10,
        60
    ); //NO moderate
    $speeds = array(
        18,
        25
    );
    $directions_west = array();
    $directions_west[0] = array(
        220,
        300
    ); //W easy
    $directions_west[1] = array(
        180,
        330
    ); //W moderate

    $nordost = new Takeoff("Nordost", $speeds, $directions_nordost);
    $west = new Takeoff("West", $speeds, $directions_west);
    $takeoffs = array($nordost, $west);
    date_default_timezone_set('Europe/Berlin');
        $recs = new MWS_LastRecordsTable(array(
            "wind_direction",
            "wind_speed",
            "wind_maxspeed",
            "record_datetime"
        ) , 10, $takeoffs);
        return $recs->html;
}

function MWS_20MAverage()
{
    $begin = new DateTime(); 
    
    $begin->sub(new DateInterval('PT20M'));
    $end = new DateTime(); 
    $avg = new MWS_AverageValue("wind_speed", "km/h", $begin, $end);
    if ($avg->value == "")
        return ("Keine Daten verfügbar");
    else
    return $avg->value . " " . $avg->unit;
}

function MWS_60MAverage() 
{
    $begin = new DateTime(); 
    
    $begin->sub(new DateInterval('PT60M'));
    $end = new DateTime(); 
    $avg = new MWS_AverageValue("wind_speed", "km/h", $begin, $end);
    if ($avg->value == "")
        return ("Keine Daten verfügbar");
    else
    return $avg->value . " " . $avg->unit;
}

function MWS_120MAverage()
{
    $begin = new DateTime(); 
    
    $begin->sub(new DateInterval('PT120M'));
    $end = new DateTime(); 
    $avg = new MWS_AverageValue("wind_speed", "km/h", $begin, $end);
    if ($avg->value == "")
        return ("Keine Daten verfügbar");
    else
    return $avg->value . " " . $avg->unit;
}











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

    public

    function __construct($record_type, $unit)
    {
        $this->record_type = $record_type;
        $this->unit = $unit;
        
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
        $this->sql_orderby = "ABS(record_datetime - FROM_UNIXTIME(" . $this->end->getTimestamp() . ")) ";
        $this->query = "SELECT " . $this->record_type . " FROM " . $this->table . " WHERE " . $this->sql_where . " ORDER BY " . $this->sql_orderby . " LIMIT 0,1";
        $this->value = $wpdb->get_var($this->query);
    }
}

class MWS_AverageValue extends MWS

{

    // date objects for avg values:

    public $begin;

    public $end;

    public function __construct($record_type, $unit, $begin, $end)
    {
        global $wpdb;
        $this->record_type = $record_type;
        $this->unit = $unit;
        $this->begin = $begin;
        $this->end = $end;
        $this->sql_where = "record_datetime BETWEEN FROM_UNIXTIME(" . $this->begin->getTimestamp() . ") AND FROM_UNIXTIME(" . $this->end->getTimestamp() . ")";
        $this->query = "SELECT AVG(" . $this->record_type . ") FROM " . $this->table . " WHERE " . $this->sql_where . " ORDER BY " . $this->sql_orderby;
        
        $this->value = round($wpdb->get_var($this->query), 0);
    }
}

class MWS_LastRecordsTable

{
    public $numRows;

    public $columns;
   
    public $html;

    public $table = "wp_weather_merkur2";

    public $takeoffs;
    
    private $data;

    public function buildHTML()
    {
        $h = '<table class="MWS_lastRecords">';
        $h.= "<tr>";
        foreach($this->columns as $header)
        {
            $h.= "<th>";
            switch ($header)
            {
            case "wind_speed":
                if (wp_is_mobile()) $h.= '&#x2300;'; //"average" sign
                else $h.= "Windgeschwindigkeit";
                break;

            case "wind_maxspeed":
                $h.= "Böe";
                break;

            case "wind_direction":
                if (wp_is_mobile()) $h.= '°';
                else $h.= "Richtung";
                break;

            case "record_datetime":
                $h.= "Zeit";
                break;

            default:
                $h.= $header;
                break;
            }

            $h.= "</th>";
        }

        $h.= "</tr>";
        for ($i = 0; $i < $this->numRows; $i++)
        {
            $h.= "<tr>";
            foreach($this->data[$i] as $key => $value)
            {

                // $h.="<td>";

                if ($key == 'wind_direction')
                {
                    
                    
                    $dir = new Wind_direction($value); 
                    $dir->GetBestTakeoff($this->takeoffs);
                    $h.= '<td style="background-color: ' . $dir->color . ';"> '; 
                    $h.= $dir->svg_arrow . "&nbsp;";
                    $h.= $dir->value . $dir->unit . " ";
                    $h.= $dir->name_short;
                    
                }
                else
                if ($key == 'wind_speed' or $key == 'wind_maxspeed')
                {
                    $speed = new Wind_speed($value);
                    $h.= '<td style="background-color: ' . $speed->color . ';">';
                    $h.= $speed->value . " ";
                    $h.= $speed->unit;
                }
                else
                if ($key == 'record_datetime')
                {
                    
                    $time = new DateTime($value);
  
                    $time->setTimeZone(new DateTimeZone('Europe/Berlin'));
                    
                    if (date('I') == 0) 
                        $time->add(new DateInterval('PT1H'));
                    
                    $h.= "<td>";
                   
                    if (wp_is_mobile())
                        $h.="vor ". floor((time() - $time->getTimestamp()) / 60) . " Min.";
                    else
                        $h.=$time->format('m.d. H:i') . "<br />(vor ". floor((time() - $time->getTimestamp()) / 60) . " Minuten)";
                }
                else
                {
                    $h.= "<td>";
                    $h.= $value;
                }

                $h.= "</td>";
            }

            $h.= "</tr>";
        }

        $h.= "</table>";
        $this->html = $h;
    }

    public function pollDatabase()
    {
        global $wpdb;
        $select = "";
        $query = "SELECT ";
        foreach($this->columns as $col)
        {
            $select.= $col;
            $select.= ", ";
        }

        $select = rtrim($select, ', ') . " "; //remove last ',' from previous loop
        $query.= $select;
        $query.= "FROM " . $this->table . " WHERE 1 ORDER BY uid DESC LIMIT 0,";
        $query.= $this->numRows;
        $this->data = $wpdb->get_results($query);
    }

    public function __construct($columns, $numRows, $takeoffs)
    {
        $this->columns = $columns;
        $this->numRows = $numRows;
        $this->takeoffs = $takeoffs;
        $this->pollDatabase();
        $this->buildHTML();
        
    }
    
}


function MWS_lastWindSpeed()
{
    $now = new DateTime('now');
    $wind = new MWS_SingleValue("wind_speed", "km/h", $now);
    return $wind->value . " " . $wind->unit;
}
