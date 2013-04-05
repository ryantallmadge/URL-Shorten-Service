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
    
    
    session_start();
    
    //error_reporting(E_ALL);
    //ini_set("display_errors", true);

    //See if we are on a subdomain, if so pass the flag
    $sub_flag = (preg_match('/^([^.]+)\.example\.com$/', $_SERVER['HTTP_HOST'], $match)) ? $match[1] : 'none';
    
    
    //Config for the database for the script
    $database_host = 'localhost';
    $database_name = 'xxxxx';
    $database_username = 'xxxxx';
    $database_password = 'xxxxx';
    //Config for your site name, used in the script for redirects, leave off any ending '/'
    $your_site_url = 'http://example.com';
    
	    //If the above checks out start Mysql
    $con = mysql_connect($database_host,$database_username,$database_password);
           mysql_select_db($database_name, $con);
    
    //Get Request and IP and start $i
    $site =  $_SERVER['REQUEST_URI'];//get the site from the url
    $ip   =  $_SERVER['REMOTE_ADDR'];//Get the users ip address
    $i    =  0;//Set i to false
    $error = '';//Set the Error Flag

    //if the site is set through a POST convert to URL
    if(isset($_POST['site']))$site = "/".$_POST['site'];

    //If no site passed render a view and die
	$set_cookie = (isset($_COOKIE["userid"])) ? $_COOKIE["userid"] : false;
    if($site == "/") loadstartscreen(false, $site, $set_cookie);
    
    //Set the views for the page == 0
    if(!isset($_SESSION['views'])) $_SESSION['views'] = 0;

    //Check if site is same as shortener
    if(stristr($site, $your_site_url)) loadstartscreen('Already Shortened This', $site);

    //Clean the site string and check for twitter or text return
    $site = strip_tags($site);
    $site_return = (stristr($site,"-i/") OR $sub_flag == 'i') ? 1 : 0;
    $site_twitter = (stristr($site,"-t/") OR $sub_flag == 't') ? 1 : 0;
    $site_facebook = (stristr($site,"-f/") OR $sub_flag == 'f') ? 1 : 0;

    //Clean Site of twitter and text return arrgs
    $check_for = array("-i/","-t/","-f/");
    $site = str_replace($check_for,"",$site);

    //Get first 3 characters of the site
    $site_first_three = substr($site, 1 , 3);
    
    //Check the first 3 character have a URL
    if($site_first_three != 'www' AND $site_first_three != 'htt' AND $site_first_three != 'ftp' AND stristr($site,'.')){
        $site = '/' . 'http://' . substr($site, 1);//Make a proper URL with http://
    }elseif($site_first_three == 'ftp'){
        loadstartscreen('Sorry We are currently not accepting FTP URLS', $site);//We were passed a FTP URL, show error
    }

    //Check if the URL has proper format and has not been viewed
    if(stristr($site,'.') AND $_SESSION['views'] != 1){
        //Run the checks on the site we are passed, update errors if any
        if(!runchecks(&$site, &$error)){
            loadstartscreen($error, $site);
        }
        
	    //First check to see if we already have this URL
	    $check_url_result = mysql_query("SELECT * FROM url WHERE url = '".mysql_real_escape_string($site)."';");
	    $check_url_num_result = mysql_num_rows($check_url_result);
        //Do we have the url already, if so set flag no to reinsert and get the URL code
	    if($check_url_num_result != 0)
	    {
            $rand_num = mysql_result($check_url_result,0,"url_code");//URL code
            $i = 1;//Set flag to not insert
	    }

        //Process Insert
        while($i == 0){
                //Get Random Number
                if(!$rand_num = randomness())die('no random');

                //Check If We have Random number all ready
                $result = mysql_query("SELECT * FROM url WHERE url_code = '$rand_num';");
                $num_result = mysql_num_rows($result);

                //If Random Number is good insert and set $i = 1
                if($num_result == 0){
            		if (!isset($_COOKIE["userid"])){
						$expire=time()+60*60*24*30*12;
						setcookie("userid", $rand_num, $expire);
						$userid = $rand_num;
					}else{
						$userid = $_COOKIE["userid"];
					}
					$insertresult = mysql_query("INSERT INTO url (url_code, url, ip, time, userid) VALUES('".$rand_num."','".mysql_real_escape_string($site)."','".$_SERVER['REMOTE_ADDR']."','".time()."','".$userid."');");
					$i++;
                }
        }


        //Check for modifiers
        if($site_return == 1){//get a text result back
            echo $your_site_url . "/$rand_num";
        }
        elseif($site_twitter == 1){//Send the link to twitter
            redirect("Location: http://twitter.com/home?status=Hey,%20Check%20this%20out:%20".$your_site_url."/$rand_num");

        }elseif($site_facebook == 1){//Send the link to facebook
            redirect("Location: https://www.facebook.com/sharer.php?u=".$your_site_url."/$rand_num&t=Hey,%20Check%20this%20out");
        }
        else{//redirect to the link 
            $_SESSION['views'] = 1;//Set the sesssion to show a view in iframe
            $_SESSION['site'] = $site;//Set the Site for the iframe
            redirect($your_site_url."/$rand_num");//Redirect to the iframe

        }
    }
    elseif($_SESSION['views']==1){//if we set the view run the iframe
        echo "<style>body{margin:0px;}</style>";//No margin for iframe
        $iframsite = (!stristr($_SESSION['site'],'http')) ? 'http://'.$_SESSION['site'] : $_SESSION['site'];       
        echo "<iframe src='{$_SESSION['site']}' width=100% height=100% frameborder=0></iframe>";//Show the iframe
        unset($_SESSION['views']);//Unset iframe view
        unset($_SESSION['site']);//Unset iframe site
        exit;//Exit the script
    }
    else{//No view set, redirect the site
        //Redirect to Site
        $site = substr($site,1);//get the code
        $result = mysql_query("SELECT url FROM url WHERE url_code = '".mysql_real_escape_string($site)."';");//Look up the code
        //Get the number of rows returned
        $num_result = mysql_num_rows($result);
            //If we have no rows the URL code is wrong show error
            if($num_result == 0){
                loadstartscreen('Could Not Find URL', $site);
            }
        //Set the location based on the URL code    
        $location = mysql_result($result,0,'url');
        //format the location for redirect, this is so legacy urls still work
        $location = ($location[0] == '/') ? substr($location,1) : $location;
        //make sure to add the http to the front of the URL
        $location = (!stristr($location,'http')) ? 'http://'.$location : $location;
        //Redirect the browser
        redirect($location);
    }
    
    
    
    
    
//Check and see if the url passes all of our checks
function runchecks(&$site, &$error){
        $site = substr($site,1);//Make a proper URL  
        //Is the link being passed a good one or a 404, if 404 return false
        //Also gets real URL incase a redirect is posted
        if(!is_available(&$site)){
            $error ="The URL is giving a 404 error, Please try agian later.";
            return false;
	    }
        //Checks the domain name to make sure we have valid DNS
	    if(!checkdomainname($site)){
            $error = "We could not validate the domain name.";
            return false;
	    }
        //Makes sure the URL is not on the Phishing list at Phish Tank dot Com
	    if(!is_phish($site)){
            $error = "Sorry, This site has been classified as Phishing.";
            return false;
	    }
    //we passed all the test, return true
    return true;
}
    
    
    
/**
 * Function is_phish(). 
 * Will check if a url is a documented phishing URL by Phish Tank
 * Will return bool true|false
 * @access public
 * @var check_for_data      int
 * @var phish_tank_app_link string
 * @var phish_tank_app_file string
 */

//Start the function is_phish
function is_phish($url){
   //File and directory to upload the phish tank file to , and call it from
   $phish_tank_app_file = './data/phish_tank_data.txt';
   //Get the phish tank array, and unserialize it. If we for it from above use that.
   $phish_tank_array = unserialize(file_get_contents($phish_tank_app_file));   
   //Look into the array and see if the url is there, if so set output to false, if not set output to true
   $output = (in_array($url,$phish_tank_array)) ? false : true;
   //return the output
   return $output;

}//End funtion is_phish


//Create a random code for the url
function randomness(){
        //Number and letters
        $randomness = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        //Randomize the string
        $randomness = str_shuffle($randomness);
        //Grab the first 5 random chars
        $randomness = substr($randomness,0,5);
        //Return the 5 char random string
        return $randomness;
}//End function randomness


//function to make sure we are not getting a 404 link, and get final redirect links
function is_available(&$url, $timeout = 30) {
        $ch = curl_init(); // get cURL handle
        // set cURL options
        $opts = array(CURLOPT_RETURNTRANSFER              => true, // do not output to browser
                                  CURLOPT_URL             => $url, // set URL
                                  CURLOPT_NOBODY          => true, // do a HEAD request only
                                  CURLOPT_FOLLOWLOCATION  => true,   //follow redirects
                                  CURLOPT_HEADER          => true,   //get header information
                                  CURLOPT_TIMEOUT         => $timeout);   // set timeout
        curl_setopt_array($ch, $opts); 
        curl_exec($ch); // do it!
        $retval = curl_getinfo($ch, CURLINFO_HTTP_CODE); // check if HTTP OK
        $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // check if HTTP OK
        curl_close($ch); // close handle
        if($final_url != $url) $url = $final_url;//If the final link is different then the passed link update it
        if($retval == '404') return false;//Did this link give us a 404 error? return false
        //Link passed return true
        return true;
}//end function is_available

//check if the domain name being passed is a real one
function checkdomainname($site){	
	$url_parts = parse_url($site);//Geting the parts of the domain
	$ip = gethostbyname($url_parts['host']);//Get the ip address for the domain
	if(@inet_pton($ip)){return true;}else {return false;}//Check if the ip we get is true if so the domain is valid
}//end function checkdomainname

//If we get an error, or need the front page, load this function
function loadstartscreen($error, $site, $cookie = false){
    include('start.php');//Get the front page
    exit;//Exit the script
}//end function loadstartscreen

//Send the browser to the URL
function redirect($url){
    header( "HTTP/1.1 301 Moved Permanently" );//Set redirect header
    header("Location: $url");//Set location
    exit;//leave the script
}//end function redirect
