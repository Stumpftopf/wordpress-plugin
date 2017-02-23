<?php
add_shortcode('warning-outdated', 'warning_outdated');

function warning_outdated()
{
    global $wpdb;
    $tstamp = $wpdb->get_var('SELECT tstamp FROM wp_weather_merkur2 ORDER BY tstamp DESC LIMIT 0, 1');
    $now = time();
    $diff = $now - $tstamp;
    $output = "";
    if ($diff < 600) //10 minutes
        return $output;
    else
    {   
        $minutes = $diff / 60;
        if ($minutes < 120) //2 hours
            $output = round( $minutes, 0) . " Minuten";
        else 
        {
            $hours = $minutes / 60;
            if ($hours < 48)
            {
                $output = $output = round($hours, 1) . " Stunden";
            }
            else 
            {
                $days = $hours/24;
                $output = round($days, 0) . " Tage";
            }
                    
        }
    }
     
    $output = '<div style="color: red; font-size: 1.5em; margin: 2em;">Achtung: Die letzte Messung der Wetterstation ist bereits ' . $output . ' alt!</div> ';
    return $output;
}
