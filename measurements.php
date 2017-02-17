

<?php
class Measurement

{
    public $value;

    public $unit;

    public $trafficlight = array(

        "green" => "#C8F526",
        "yellow" => "#FFDB58",
        "red" => "#FFB093"
    );
}
class Wind_speed extends Measurement

{
    private $timmfactor = 1.3;
    public $original_value;

    public $color;

    public function __construct($windspeed)

    {
        $this->value = $windspeed;
        $this->unit = "km/h";
        $this->original_value = $windspeed * $this->timmfactor;
        $this->GetQuality();
    }
    private function GetQuality()
    {
        $val = $this->value;
        if ($val <= 18) $this->color = $this->trafficlight['green'];
        else if ($val <= 25) $this->color = $this->trafficlight['yellow'];
        else $this->color = $this->trafficlight['red'];
    }
}
class Wind_Direction extends Measurement

{
    public $name_short;

    public $name_long;

    public $svg_arrow;

    public $color;
 // suitability at best takeoff
    public $best_takeoff;

    public function __construct($direction)

    {
        $this->value = $direction;
        $this->unit = "°";
        $this->name_long = $this->getCompassPointName("long");
        $this->name_short = $this->GetCompassPointName('short');
        $this->svg_arrow = $this->GetWindDirectionArrow();
    }
    private function GetCompassPointName($type)
    {
        /*
        http://stackoverflow.com/a/7490772
        Divide the angle by 22.5 because 360deg/16 directions = 22.5deg/direction change.
        Add .5 so that when you truncate the value you can break the 'tie' between the change threshold.
        Truncate the value using integer division (so there is no rounding).
        Directly index into the array and print the value (mod 16).
        */
        $namesShort = array(
            "N",
            "NNO",
            "NO",
            "ONO",
            "O",
            "OSO",
            "SO",
            "SSO",
            "S",
            "SSW",
            "SW",
            "WSW",
            "W",
            "WNW",
            "NW",
            "NNW"
        );
        $namesLong = array(
            "Nord",
            "Nord-Nordost",
            "Nordost",
            "Ost-Nordost",
            "Ost",
            "Ost-Südost",
            "Südost",
            "Süd-Südost",
            "Süd",
            "Süd-Südwest",
            "Südwest",
            "West-Südwest",
            "West",
            "West-Südwest",
            "West",
            "West-Nordwest",
            "Nordwest",
            "Nord-Nordwest"
        );
        $val = ($this->value / 22.5) + 0.5;
        $val = intval($val);
        if ($type = "short") return $namesShort[$val % 16];
        else return $namesLong[$val % 16];
    }
    private function GetWindDirectionArrow()
    {
        $html = '<svg width="32px" height="32px" xmlns="http://www.w3.org/2000/svg">';
        $html.= '<g>';
        $html.= '<title>Windrichtung</title>';
        $html.= '<g transform="rotate(' . round($this->value, 0) . ' , 16, 16)" >';
        $html.= '<path fill="#000000" d="m21,4l-10,0l5,24"/>';
        $html.= '</g>';
        $html.= '</g>';
        $html.= '</svg>';
        return $html;
    }
    public function GetBestTakeoff($takeoffs)

    {
        foreach($takeoffs as $takeoff)
        {
            if ($this->value >= $takeoff->winddirection_levels[1][0] && $this->value <= $takeoff->winddirection_levels[1][1]); //return takeoff if value is within boundaries
            {
                return $takeoff;
            }
        }
        return -1; //no takeoff suitable;
    }
}
class Temperature extends Measurement

{
    public function __construct($temperature)

    {
        $this->value = $temperature;
        $this->unit = "°C";
    }
}
class Pressure extends Measurement

{
    public function __construct($pressure)

    {
        $this->value = $pressure;
        $this->unit = "hPa";
    }
}
class Wind_Chill extends Measurement

{
    public function __construct($windchill)

    {
        $this->value = $windchill;
        $this->unit = "°C";
    }
}
class Takeoff

{
    // array
    public $windspeed_levels;

    // 2d array
    public $winddirection_levels;

    public function __construct($speeds, $directions)

    {
        $this->windspeed_levels = $speeds;
        $this->winddirection_levels = $directions;
    }
}
?>


