<?php

/**
 
 * Trivago code challenge for Data validation and data format conversion in PHP

 * by Hammed Olalekan Osanyinpeju

 * osanyinpejuhammed35@gmail.com

 * 2347061855688
 
 */
class Process{

    public function validate_uri($uri){

        /**
        
        * Validate if a uri is valid, not only the uri parameters but load the uri header to comfirm if the uri is online with 200 response code. 

        * Note, this require internet connection and will significantly increase process time of your script when handling large data as every uri will be checked if it is actually online. 
        
        */
    
        if (filter_var($uri, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) != false){ //Validate uri parameters

            $check_header = @get_headers($uri);     //Load uri headers

            if ($check_header){
            
                //If header loads successfully, confirm if response code is 200 and not 301, 404 or other response code.
                if (substr($check_header[0],9,3) == "200"){
                
                    return true;
                }
                else{
                    return false;
                }
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }
    
    public function validate_arr($data, $confirm_uri=null){

        /**
        
        *  Validate each of the multidimensional array values to determine which of the data are valid or not.

        *  In this block, the names are validated if they are ASCII character,the stars are validated if they are integeter and value in the range 0 to 5.
        
        *  By default, the uri parameters are checked if they are written in the general acceptable standard to term it as valid but with option to test if the link actually exist based on user preference.
        
        */

        for ($i=0; $i < count($data); $i++) { //Loop through all the data 

            //Validate if hotel name conform with ASCII standard
             $val_name = !preg_match( '/[\\x80-\\xff]+/' ,$data[$i]["name"]);

            //Validate if hotel star is an integer and in the range 0 to 5
            $val_stars = filter_var($data[$i]["stars"],FILTER_VALIDATE_INT, array("options" => array("min_range"=>0, "max_range"=>5)));
            
            //Check if user request to confirm every uri is Live online
            if ($confirm_uri === "yes"){

                //Validate uri parameters and also confirm if the uri is Live online 
               $val_uri = $this -> validate_uri($data[$i]["uri"]);
            }
            else{
            
                //Default option to check uri parameters if it is a standard uri
                $val_uri = filter_var($data[$i]["uri"], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
            }

            //Check if name validation, stars validation, and uri validation return true
            if ($val_name && $val_stars && $val_uri){ 

                $validated_data[] = $data[$i];      //Add as valid data
           }    
        }

        return (is_array($validated_data)) ? $validated_data : false;   //return validated data else false for no data
        
    }

    public function read_data($path){

        /* Read data in the CSV file and return as multidimensional array */

        $file_arr = file($path);  //Read all data in the CSV file as an array, with each line having an array index 

        //Confirm if CSV file exist and in the expected format
        if (trim($file_arr[0]) === "name,address,stars,contact,phone,uri"){   

            //Loop through all the array index excluding first array index and further break each line to sub array
            for ($i=1; $i < count($file_arr); $i++) { 
            
                //Extract name, address, stars, contact, phone, and uri from each line of the CSV file
                $name = strtok($file_arr[$i],',');
                $address = '"'.trim(strtok('"')).'"';
                $stars = intval(strtok(','));
                $contact = trim(strtok(','));
                $phone = trim(strtok(','));
                $uri = trim(strtok(','));

                //Use the values extracted to create a sub array 
                $output_arr[] = array('name' => $name, 'address' => $address, 'stars' => $stars, 'contact' => $contact, 'phone' => $phone, 'uri' => $uri);

            }

            return $output_arr;
        }
        else{
            return false;
        }
    }

    public function json_output($path, $sort=null, $confirm_uri=null){

        /* Process request for data in JSON format */

        $data = $this -> read_data($path);  //Read CSV file into multidimensional array

        if ($data != false){

            //Validate data based on name, stars, uri and user preference
            $valid_data = $this -> validate_arr($data, $confirm_uri);
            
            if ($valid_data != false){

                //If user request output data to be sorted. Sort according to name
                if ($sort === "yes") {
                
                    asort($valid_data);
                }

                //Convert the multidimentional array to json format
                $result = '{"hotels":[';

                foreach ($valid_data as $valid_data_chunk) {
   
                    $result .= PHP_EOL.json_encode($valid_data_chunk, JSON_PRETTY_PRINT).",";
                }

                //Get rid of the last comma and add closing curly bracket
                $result = str_replace("},?", "}]", $result."?").'}';    

                //write the json output to a file
                $path = dirname(__FILE__)."/my_validated_data.json";

                if ($file = fopen($path,"w")){  //Open a file or clear existing file

                    fwrite($file,$result);      //Write json data to file
                    
                    fclose($file);      //Close file
                
                    $message = "JSON file generated succesfully, <a href='my_validated_data.json' target='_blank'>Click </a>here to view file.";
                }
                else{
                    $message = "Fail to write in the current directory, Kindly change permission of the current directory and try again.";
                }

                return $message;
            }
            else{

                return "All data seems to be invalid based on the option you selected. Ensure your machine is connected to the internet.";
            }
        }
        else{
            return "Unable to read file or CSV not in expected format";
        }
    }

    public function xml_output($path, $sort=null, $confirm_uri=null){

        /* Process request for data in XML format */

        $data = $this -> read_data($path);  //Read CSV file into multidimensional array

        if ($data != false){

            //Validate data based on name, stars, uri and user preference
            $valid_data = $this -> validate_arr($data, $confirm_uri);
            

            if ($valid_data != false){

                //If user request output data to be sorted. Sort according to name
                if ($sort === "yes") {
                
                    asort($valid_data);
                }

                //Convert the multidimentional array to xml format
                $result = '<?xml version="1.0" encoding="UTF-8"?>  '.PHP_EOL.' <hotels>';

                foreach ($valid_data as $valid_data_chunk) {
   
                    $result .= PHP_EOL.'<hotel>
                        <name>'.$valid_data_chunk['name'].'</name>
                        <address>'.$valid_data_chunk['address'].'</address>
                        <stars>'.$valid_data_chunk['stars'].'</stars>
                        <contact>'.$valid_data_chunk['contact'].'</contact>
                        <phone> '.$valid_data_chunk['phone'].'</phone>
                        <uri>'.$valid_data_chunk['uri'].'</uri>
                    </hotel>';
                }

                $result = $result.PHP_EOL.'</hotels>';

                //write the xml output to a file
                $path = dirname(__FILE__)."/my_validated_data.xml";

                if ($file = fopen($path,"w")){  //Open a file or clear existing file

                    fwrite($file,$result);      //Write xml data to file
                    
                    fclose($file);      //Close file
                
                    $message = "XML file generated succesfully, <a href='my_validated_data.xml' target='_blank'>Click </a>here to view file.";
                }
                else{
                    $message = "Fail to write in the current directory, Kindly change permission of the current directory and try again.";
                }

                return $message;
            }
            else{

                return "All data seems to be invalid based on the option you selected. Ensure your machine is connected to the internet.";
            }
        }
        else{
            return "Unable to read file or CSV not in expected format";
        }
    }
    public function other_output($path, $sort=null, $confirm_uri=null){

        /* Process request for data in other format */

        $data = $this -> read_data($path);  //Read CSV file into multidimensional array

        if ($data != false){

            //Validate data based on name, stars, uri and user preference
            $valid_data = $this -> validate_arr($data, $confirm_uri);
            
            if ($valid_data != false){

                //If user request output data to be sorted. Sort according to name
                if ($sort === "yes") {
                
                    asort($valid_data);
                }


                /** Converting to other formats goes here
                
                //Convert the multidimentional array to other format

                foreach ($valid_data as $valid_data_chunk) {
                    
                    
                    //$result .= ;
                }
                
                **/

                //write the other output to a file
                $path = dirname(__FILE__)."/my_validated_data.other";

                if ($file = fopen($path,"w")){  //Open a file or clear existing file

                    fwrite($file,$result);      //Write other data to file
                    
                    fclose($file);      //Close file
                
                    $message = "OTHER file generated succesfully, <a href='my_validated_data.other' target='_blank'>Click </a>here to view file.";
                }
                else{
                    $message = "Fail to write in the current directory, Kindly change permission of the current directory and try again.";
                }

                return $message;
            }
            else{

                return "All data seems to be invalid based on the option you selected. Ensure your machine is connected to the internet.";
            }
        }
        else{
            return "Unable to read file or CSV not in expected format";
        }
    }

}


/**  Request process starts here  **/

//Check if any of the GET request is set
$type = (isset($_REQUEST['type'])) ? $_REQUEST['type'] : "";
$sort = (isset($_REQUEST['sort'])) ? $_REQUEST['sort'] : "";
$uri = (isset($_REQUEST['uri'])) ? $_REQUEST['uri'] : "";

//CSV file path
$file_path = dirname(__FILE__)."/hotels.csv";

//Default welcome message
$message = "Welcome to the Challenge Solution Page";

    if ($type === "json"){  //Enter here for JSON output

        $task = new Process();      //New instance of the class Process

        $message = $task -> json_output($file_path, $sort, $uri);   //JSON process request

    }
    elseif ($type === "xml") {  //Enter here for xml output
        
        $task = new Process();      //New instance of the class Process

        $message = $task -> xml_output($file_path, $sort, $uri);   //XML process request
    }

    /* Provision for other file format */
    elseif ($type === "other") {  //Enter here for other output
        
        $task = new Process();      //New instance of the class Process

        $message = $task -> other_output($file_path, $sort, $uri);   //other process request
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Trivago Challenge</title>

    <style>
        .container{
            max-width:700px;
            margin: auto;
            text-align: center;
            padding: 20px;
        }
        .container section{
            
            margin: 10px;
        }
        .container a{
            display: inline-block;
            text-decoration: none;
            margin: 10px
        }
        .container section div div{
            display: inline-block;
            max-width: 300px;
            margin: 10px 30px;
            text-align: left;


        }

    </style>

</head>
<body>
    <div class="container">
        <header>
            <h1>Trivago Challenge</h1>
        </header>
        <section>
            <p>Generate Validated data Output in JSON and XML format</p>
            <div>
                <div>
                    <form action="index.php" method="GET" >
                        <input type="hidden" name="type" value="json">
                        <p>
                            <input type="checkbox" name="sort" id="jsonsort" value="yes" /><label for="jsonsort">Sort output by name</label>
                        </p>
                        <p>

                            <input type="checkbox" name="uri" id="jsonuri" value="yes" /><label for="jsonuri">Confirm if uri is Live online</label>
                        </p>
                        <p>
                            <input type="submit" value="JSON"/>
                        </p> 
                    </form>
                </div>

                <div>
                    <form action="index.php" method="GET" >
                        <input type="hidden" name="type" value="xml">
                        <p>
                            <input type="checkbox" name="sort" id="xmlsort" value="yes" /><label for="xmlsort">Sort output by name</label>
                        </p>
                        <p>

                            <input type="checkbox" name="uri" id="xmluri" value="yes" /><label for="xmluri">Confirm if uri is Live online</label>
                        </p>
                        <p>
                            <input type="submit" value="XML"/>
                        </p> 
                    </form>
                </div>

                <!-- Provision for another output format

                <div>
                    <form action="index.php" method="GET" >
                        <input type="hidden" name="type" value="other">
                        <p>
                            <input type="checkbox" name="sort" id="othersort" value="yes" /><label for="othersort">Sort output by name</label>
                        </p>
                        <p>

                            <input type="checkbox" name="uri" id="otheruri" value="yes" /><label for="otheruri">Confirm if uri is Live online</label>
                        </p>
                        <p>
                            <input type="submit" value="OTHER FORMAT"/>
                        </p> 
                    </form>
                </div>
                -->

                <p>
                    <strong>
                        <?php echo $message; ?>
                    </strong>
                </p>

                <p>
                    <strong>NOTE:</strong> If you select <i> comfirm if uri is Live online</i>, ensure you are connected to the internet as every uri in the document will be checked if they actually point to a live server and the webpage is valid with 200 as the response code. You may have to adjust your server maximum execution time as the process will take a longer time when large data such as in this demo is being processed.
                </p>

            </div>
            
        </section>
    
    
    </div>


</body>
</html>