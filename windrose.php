<?php
/*
Plugin Name: Windrose
Plugin URI: https://www.schwarzwaldgeier.de
Description: Display wind data as polar wind rose
Version: 1.0
Author: Sebastian Schmied
License: CC BY 4.0
*/
//global $wp_query;
//echo $wp_query->post->post_title;

add_filter('wp_enqueue_scripts','insert_jquery',1);
add_action( 'wp_enqueue_scripts', 'enqeue_highcharts_scripts');
add_shortcode('windrose', 'windrose_wrapper');

function windrose_wrapper()
{
    add_action( 'wp_footer', 'write_windrose_javascript');
    return write_windrose_container();
}


function write_windrose_container()
{
      
	return '<div id="container"></div>';
}


    
function insert_jquery(){
wp_enqueue_script('jquery', false, array(), false, false);
}


function enqeue_highcharts_scripts()
{
        wp_enqueue_script("highcharts", "https://code.highcharts.com/highcharts.js");
        wp_enqueue_script("highcharts-more", "https://code.highcharts.com/highcharts-more.js", "highcharts");   
        wp_enqueue_script("json2", "https://cdnjs.cloudflare.com/ajax/libs/json2/20130526/json2.min.js");
}

function write_windrose_javascript()
{
        global $wpdb;
        $num_minutes = 30;
        $query = 'SELECT wind_speed, wind_maxspeed, wind_direction FROM wp_weather_merkur2 WHERE record_datetime >= NOW() - INTERVAL '.  $num_minutes .' Minute ORDER BY record_datetime DESC';
        $query_debug = 'SELECT wind_speed, wind_maxspeed, wind_direction FROM wp_weather_merkur2 ORDER BY record_datetime DESC LIMIT 0, '. $num_minutes; //use this when testing locally
        
        $values = $wpdb->get_results($query, "ARRAY_A");
              
        $yMax =  max(array_column($values, 'wind_maxspeed'));
        if ($yMax == 0)
            $yMax = 1; //avoid division by zero
        $yMax += $yMax/10;
        $yMax = intval($yMax);
        ?>
  

    <script>
    jQuery(function () {
        var categories = ['N', 'NNO', 'NO', 'ONO', 'O', 'OSO', 'SO', 'SSO', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
        jQuery('#container').highcharts({
            series: [{
                data:
                [
                <?php
                    $size = count($values);
                    if ($size <= 0)
                        $size += 1; //divisions ahead
                    for ($i=0; $i<$size; $i++)
                    {
                        $hue = 1 - round($i/$size, 2);
                        echo "{";
                            echo "x: ". $values[$i]['wind_direction'].",";
                            echo "y: ". $values[$i]['wind_speed'].",";
                            echo "marker: {";
                                echo "fillColor: 'rgba(0,0,255," . $hue . ")',";
                                echo "radius: " . round($i/$size ,2)*7;
                            echo "}";
                            
                        echo "},";
                    }
                ?>
                ],
                name: 'Windgeschwindigkeit (Durchschnitt)',
                color: 'rgba(0,0,255, 1)',
              
                    marker: 
                    {
                        radius: 10
                    }
            },
            {
                data:
                [
                <?php
                    $size = count($values);
                    if ($size <= 0)
                        $size += 1; //divisions ahead
                    for ($i=0; $i<$size; $i++)
                    {   
                       
                        $hue = 1 - round($i/$size, 2);
                        echo "{";
                            echo "x: ". $values[$i]['wind_direction'].",";
                            echo "y: ". $values[$i]['wind_maxspeed'].",";
                            echo "marker: {";
                                echo "fillColor: 'rgba(255,0,0," . $hue . ")',";
                                echo "radius: " . round($i/$size,2)*4;
                                echo "}";
                        echo "},";
                        
                    
                    }
                ?>
                ],
                name: "Windgeschwindigkeit (Böe)",
                marker: 
                    {
                        radius: 2,
                        symbol: "circle",
                        
                    },
                color: 'rgba(255,0,0,1',
                shadow: true, 
            },
           
            
            ],
            chart: {
                polar: true,
                type: 'scatter'
            },
            title: {
                text: 'Letzte <?php echo $num_minutes; ?> Minuten'
            },
            pane: {
            
            size: '85%'
                
          
            },
           
            xAxis: {
                min: 0,
                max: 360,
                type: "linear",
                tickInterval: 22.5,
                tickmarkPlacement: 'on',
                labels: {
                    formatter: function () {
                        return categories[this.value / 22.5];
                    }
                }
            },
            yAxis: {
                min: 0,
                max: <?php echo $yMax; ?>,
                endOnTick: false,
                showLastLabel: true,
              
             
            
                labels: {
                    formatter: function () {
                        return this.value + ' km/h';
                    }
                },
                reversedStacks: false,
                
        
        plotBands: 
        [
            {
            color: 
                {
                    radialGradient:  {cx: 0.5, cy: 0.5, r: 0.5 },
                    stops: 
                    [
                    <?php 
                        
                        $yellow = 23;
                        $red = 30;
                        echo "[0, '#00e000'],"; //green
                        
                        //Apply yellow and red gradients only if needed. So we get a green circle if wind is OK and a mostly red one if stormy.
                        if ($yellow/$yMax < 1)
                            echo "[". round($yellow/$yMax, 2) .", '#ffff00'],"; //yellow
                            
                        if ($red/$yMax < 1) 
                            echo "[". round($red/$yMax, 2) .", '#eeaaaa'],"; //red
                     ?>
                        
                    ]
                },
                
                from: 0,
                to: <?php echo $yMax; ?>
            },

        ],
                
            },
            tooltip: {
                valueSuffix: ' km/h'
            },
            plotOptions: {
                series: 
                {
                    showInLegend: true,
                    groupPadding: 0,
                    pointPlacement: 'on', 
                    lineColor: '#ffffff',
                    
                    symbol: 'circle',
                },
            }
        });
    });
    </script>

<?php
}?>
