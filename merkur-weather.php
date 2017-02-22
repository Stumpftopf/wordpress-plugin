<?php
/*
Plugin Name: Wetterstation Merkur
Plugin URI: https://www.schwarzwaldgeier.de
Description: Display data obtained from merkur weather station with shortcodes
Version: 1.0
Author: Sebastian Schmied
License: CC BY 4.0
*/
defined('ABSPATH') or die('Must be run as a wordpress plugin');

require_once ('measurements.php');
require_once('records.php');
require_once('windrose.php');
