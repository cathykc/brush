<?php

    /*
    initialize.php
    The purpose of this script is to receive texts of "BEAM-" or "BRUSH"
    Then the number will be inputted into the csv number file(s).

    Input: pre_numbers.csv
    [4-digit brush ID], [morning reminder time], [night reminder time]
    
    Output: study_numbers.csv
    (all on same line)
    [10-digit number with '1' prefix], [start date], [end date],
                [morning reminder time], [night reminder time], [BEAM number / BRUSH]
    */

    // set time zone
    date_default_timezone_set('America/New_York');

    // get this person's number
    $this_num = substr($_REQUEST['From'], 1);
    // get brush id, which consists of last four digits
    $id = substr(strtoupper($_REQUEST['Body']), -4, 4);
    // get their first letter of answer, caps
    $ans = substr(strtoupper($_REQUEST['Body']), 0, 1);

    //CONSTANTS
    $FILEDEST = "study_numbers.csv"; //output file
    $FROM = "From: thomlee@wharton.upenn.edu"; //sender signature
    $ERROR_EMAIL = $this_num.' sent this message: '.$_REQUEST['Body'];

    //flag of whether the brush ID matches an entry in pre_numbers
    $found = false;
    //default message
    $reply = "";
    // check if this is an initialize message
    if ($ans == "B") {
        $time1 = "TIME_ERROR";
        $time2 = "TIME_ERROR";
        $end = "TIME_ERROR";
        // check this person's morning/night times from the pre-study info csv
        $a = array();
        $file = fopen("pre_numbers.csv","r");
        // if file exists, traverse each line in csv to find this person
        if ($file != null) {
            while(! feof($file)) {
                array_push($a, fgetcsv($file));
            } fclose($file);
            for($x=0; $x < count($a); $x++){
                // in pre_numbers, brush ID is first item in each line
                if ($a[$x][0] == $id) {
                    $found = true;
                    // get morning and night times
                    $time1 = date("h:ia", strtotime($a[$x][1]));
                    $time2 = date("h:ia", strtotime($a[$x][2]));
                }
            }
        }
        //now check if person is already in study_numbers
        $duplicate = false;
        $a = array();
        $file = fopen($FILEDEST,"r");
        if ($file != null) {
            while(! feof($file)) {
                array_push($a, fgetcsv($file));
            } fclose($file);
            for($x=0; $x < count($a); $x++){
                // brush ID is 6th field in the line
                if ($a[$x][5] == $id) {
                    // person answered this quiz already
                    $duplicate = true;
                }
            }
        }

        //ERROR 1: person's id is not found
        if (!$found) {
            $reply = "Sorry, but we were unable to match your brush ID. Please try again.";
            //send emails to alert of error
            mail('thomlee@wharton.upenn.edu','ERROR: Brush ID not found for '.$this_num,
                $ERROR_EMAIL, $FROM);
            mail('barankay@wharton.upenn.edu','ERROR: Brush ID not found for '.$this_num,
                $ERROR_EMAIL, $FROM);
        }
        //ERROR 2: duplicate
        else if ($duplicate) {
            $reply = "Sorry, this brush code has been registered before already.";
            mail('thomlee@wharton.upenn.edu','ERROR: Duplicate Brush ID from '.$this_num,
                $ERROR_EMAIL, $FROM);
            mail('barankay@wharton.upenn.edu','ERROR: Duplicate Brush ID from '.$this_num,
                $ERROR_EMAIL, $FROM);
        }
        //otherwise, write to file and reply
        else {
            // write response onto output csv file
            $handle = fopen($FILEDEST, "a");
            // calculate start and end dates
            $today = date('ymd', time());
            $start = date('m/d/Y',strtotime($date1 . "+1 days"));
            $end = date('m/d/Y',strtotime($date1 . "+84 days"));
            $line = array ($this_num, $start, $end, $time1, $time2, $id);
            fwrite($handle, "\r\n"); // new line for viewing in notepad
            fputcsv($handle, $line);
            fclose($handle);

            $reply = "Welcome to the study. Thank you for confirming receipt of the brush. We will now process payment for this study step and mail you a check. Your Upennbrush team.";
       
            //now check whether this participant is in quiz group
            $quiz = false;
            $file = fopen("quiz_ids_list.csv", "r");
            $a = array();
            if ($file != null) {
                while(! feof($file)) {
                    array_push($a, fgetcsv($file));
                } fclose($file);
                for($x=0; $x < count($a); $x++){
                    if ($a[$x][0] == $id) {
                        $quiz = true;
                    }
                }
            }
            //write to quiz numbers
            if ($quiz) {
                $handle = fopen("quiz_numbers.csv", "a");
                $line = array ($this_num, $end);
                fputcsv($handle, $line);
                fclose($handle);
            }
            

        }
    }
    //ERROR 3: text does not start with B
    else {
        $reply = "Sorry, the message you sent did not start with 'B'. Please try again.";
        //send emails to alert of error
        mail('thomlee@wharton.upenn.edu','ERROR: Text did not begin with B for '.$this_num,
            $ERROR_EMAIL, $FROM);
        mail('barankay@wharton.upenn.edu','ERROR: Text did not begin with B for '.$this_num,
            $ERROR_EMAIL, $FROM);
    }
    
    //--------------------------------------------------------
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Message><?php echo $reply?></Message>
</Response>