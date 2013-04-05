<?php


/*
Copyright (C) 2011 by Ryan Tallmadge

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

ini_set('memory_limit', '512M');
//Config options, update with your info
//$phish_tank_app_link = 'http://data.phishtank.com/data/online-valid.php_serialized'; 

/** 
 * Once you have an api key from phish tank comment out above and uncomment below 
 * and replace <api-key-here> with the api key, http://www.phishtank.com/register.php
 */
$phish_tank_app_link = 'http://data.phishtank.com/data/<api-key-here>/online-valid.php_serialized'; 

//File and directory to upload the phish tank file to , and call it from
$phish_tank_app_temp_file = './phish_tank_data_temp.txt';
$phish_tank_app_file = './phish_tank_data.txt';

//Delete the current phish tank file
if(file_exists ($phish_tank_app_temp_file)) unlink($phish_tank_app_temp_file);

//Go get and save the new phish tank file
$ch = curl_init($phish_tank_app_link);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
$data = curl_exec($ch); 
curl_close($ch); 
file_put_contents($phish_tank_app_link, $phish_tank_app_temp_file);


////Start function, no need to edit below here/////////

    //We dont have a phish tank lets create one
    $phish_tank_data = unserialize($phish_tank_app_temp_file);//Get the data of the file
	//We only want to save the URL's , so loop through grab only them	
	foreach($phish_tank_data as $k=>$v){
		$phish_tank_save_data[] =  $v['url'];//Grab the URL
	}
    //Set the new URL only array to our data holder
    $phish_tank_data = serialize($phish_tank_save_data);//Set and serialize the data of the file
    file_put_contents($phish_tank_app_file, $phish_tank_data);//Save the data from the file
