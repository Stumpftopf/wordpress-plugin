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
    if (wp_is_mobile())          
	return '<div id="container"></div>';
    else 
        return '<div id="container" style="width: 600px; height: 600px; margin: 0; float: left"></div>';
        
        

}


    
function insert_jquery(){
wp_enqueue_script('jquery', false, array(), false, false);
}


function enqeue_highcharts_scripts()
{
        
        wp_enqueue_script("highcharts", "https://code.highcharts.com/highcharts.js");
        wp_enqueue_script("highcharts-more", "https://code.highcharts.com/highcharts-more.js", "highcharts");
        wp_enqueue_script("highcharts-exporting", "https://code.highcharts.com/modules/exporting.js", "highcharts");
        wp_enqueue_script("json2", "https://cdnjs.cloudflare.com/ajax/libs/json2/20130526/json2.min.js");
        
        
}

function write_windrose_javascript()
{
        global $wpdb;
        $num_records = 20;
        $speeds = $wpdb->get_col("select wind_speed from wp_weather_merkur2 order by uid desc limit 0, " . $num_records . "", 0);
        $directions = $wpdb->get_col("select wind_direction from wp_weather_merkur2 order by uid desc limit 0, " . $num_records . "", 0);
        $gusts = $wpdb->get_col("select wind_maxspeed from wp_weather_merkur2 order by uid desc limit 0, " . $num_records . "", 0);
        
        $yMax =  max($speeds);
        $yMax += $yMax/10;
        ?>
    <script language="javascript">
        
        var windDirection, windSpeed, windGust, windDirectionJSON, windSpeedJSON, windGustJSON, windDataJSON;
        windData = 
        <?php
        echo '"[';
        for ($i=0; $i<count($directions); $i++)
        {
            echo $directions[$i];
            if ($i<(count($directions)-1))
                    echo ',';

        }
        echo ']";';

        ?>
        
        windSpeed = <?php
        echo '"[';
        for ($i=0; $i<count($speeds); $i++)
        {
            echo $speeds[$i];
            if ($i<(count($speeds)-1))
                    echo ',';

        }
        echo ']";';

        ?>
        
        windGust = <?php
        echo '"[';
        for ($i=0; $i<count($gusts); $i++)
        {
            echo $gusts[$i];
            if ($i<(count($gusts)-1))
                    echo ',';

        }
        echo ']";';

        ?>
        
        windDirectionJSON = JSON.parse(windData);
        windSpeedJSON = JSON.parse(windSpeed);
        windGustJSON = JSON.parse(windGust);
        windDataJSON = [];
        for (i = 0; i < windDirectionJSON.length; i++) 
        {
            windDataJSON.push([ windDirectionJSON[i], windSpeedJSON[i], windGustJSON[i] ]);
        }
        console.log(windDataJSON);
        //windDataJSON.sort(function(a,b) { return a[0] - b[0]; });
    </script>

    <script>
    jQuery(function () {
        var categories = ['N', 'NNO', 'NO', 'ONO', 'O', 'OSO', 'SO', 'SSO', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
        jQuery('#container').highcharts({
            series: [{
                data: windDataJSON
            },
           
            
            ],
            chart: {
                polar: true,
                type: 'column'
            },
            title: {
                text: 'Windmessungen der letzten <?php echo $num_records; ?> Minuten'
            },
            pane: {
            <?php 
            if (wp_is_mobile())
                echo "size: '80%'";
            else
                echo "size: '85%'";
                
            ?>
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
                        [0, '#00E000'], //green
                        [0.65, '#FFFF00'], //yellow
                        [1, '#eeaaaa'] //red    
                    ]
                },
                
                from: 0,
                to: 36
            },
            
            {
            color: 
                {
                    radialGradient:  {cx: 0.5, cy: 0.5, r: 0.5 },
                    stops: 
                    [
                        [0, '#eeaaaa'], //red
                        
                        [1, '#eeaaa0'] //slightly redder
                    ]
                },
                
                from: 36,
                to: <?php echo $yMax; ?>
            },
        ],
                
            },
            tooltip: {
                valueSuffix: ' km/h'
            },
            plotOptions: {
                series: {
                    showInLegend: false,
                    name: 'Windgeschwindigkeit',
                    shadow: true,
                    groupPadding: 0,
                    pointPlacement: 'off',
                    pointWidth: 0.03,
                    color: {
                        linearGradient: { cx: 0.5, cy: 0.5, r: 0.5 },
                        stops: [
                           [0, '#0000ff'],
                           [1, '#ffffff']
                        ]
                        },
                    marker: {
                        radius: 5
                        
                    }
                    
                    
                    
                     
                }
            }
        });
    });
    </script>

<?php
}?>
