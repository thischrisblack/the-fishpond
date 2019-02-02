<?php     

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;

class Model
{
    /**
     * @param object $db A PDO database connection
     */
    function __construct($db)
    {
        try {
            $this->db = $db;
        } catch (PDOException $e) {
            exit('Database connection could not be established.');
        }
    }


    /**
     * Sets session variables.
     */
    public function setSessionVariables()
    {
        // Set session variables for search limits
        session_start();

        // If user not logged in, go to login page.
        if ($_SESSION["loggedin"] != "Y") {
            header('location: ' . URL . 'login');
        } else {
            $_SESSION["loggedin"] = "Y";
        }

        // If no session variables set, set to defaults.
        if (!isset($_SESSION["minmax"])) {
            $_SESSION["minmax"] = "1-180";
        }
        if (!isset($_SESSION["apay"])) {
            $_SESSION["apay"] = "all";
        }
        if (!isset($_SESSION["ctac_lanid"])) {
            $_SESSION["ctac_lanid"] = "";
        }

        // If there is $_GET data, set the appropriate session avariable.
        if ($_GET) {
            if (isset($_GET["apay"])) {
                $_SESSION["apay"] = $_GET["apay"];
            }
            if (isset($_GET["minmax"])) {
                $_SESSION["minmax"] = $_GET["minmax"];
            }
        }

        // If there is $POST data, set the session variable.
        if (isset($_POST["ctac_lanid"])) {
            $_SESSION["ctac_lanid"] = $_POST["ctac_lanid"];
        }
       
        // Update session variables object for use by home.php
        $variables = (object) [
            'apay' => $_SESSION["apay"],
            'minmax' => $_SESSION["minmax"],
            'lanid' => $_SESSION["ctac_lanid"]
        ];        

        return $variables;
    }

    

    /**
    * Upload accounts CSV file to database
    */
    public function stockPond($data)
    {
        /**
         * Okay, let's talk about this. This seems like a laborious way to do it,
         * but here's why: We're not using LOAD DATA INFILE because if you, like
         * me, are on a shared hosting plan with cPanel, your MySQL user won't have 
         * global privileges to use that, so we have to go a line at a time.
         * 
         * The reason I don't use a file upload to upload and parse the exported Excel
         * (or CSV) file, is right now I don't know how to do that. Save that
         * for a future update. For now, it's copy paste.
         */

        // Get existing data out of the table.
        $sql = ("TRUNCATE TABLE fishes");
        $query = $this->db->prepare($sql);
        $query->execute();

        // Get those apostrophes out of there.
        $data = addslashes($data);

        // Start parsing.
        $fish = str_getcsv($data, "\n"); 
        foreach($fish as $account) {
            $account = str_getcsv($account, ";");            
            foreach($account as $item) { // $item = each field
                $item = str_getcsv($item, "\t");

                // Skip the first row if it's headers.
                if ($item[0] == "crnt_pk") {
                    continue;
                }

                $sql = ("INSERT INTO `fishes`(`crnt_pk`, `crnt_acct`, `crnt_sub`, `crnt_desc`, `crnt_student`, `crnt_lastdate`, `crnt_lastamt`, `crnt_amtdue`, `crnt_payoff`, `crnt_ndate`, `crnt_paymentdue`, `crnt_latedue`, `crnt_otherdue`, `crnt_payments`, `crnt_last`, `crnt_zip`, `crnt_qty`, `crnt_sku`, `crnt_email`, `crnt_emailmethod`, `crnt_addr1`, `crnt_addr2`, `crnt_addr3`, `crnt_addr4`, `crnt_addr5`, `crnt_name`, `crnt_hmphone`, `crnt_rntdesc`, `crnt_extra1`, `crnt_extra2`) VALUES ('$item[0]','$item[1]','$item[2]','$item[3]','$item[4]','$item[5]','$item[6]','$item[7]','$item[8]','$item[9]','$item[10]','$item[11]','$item[12]','$item[13]','$item[14]','$item[15]','$item[16]','$item[17]','$item[18]','$item[19]','$item[20]','$item[21]','$item[22]','$item[23]','$item[24]','$item[25]','$item[26]','$item[27]','$item[28]','$item[29]')");
                $query = $this->db->prepare($sql);
                if(!$query->execute()) {
                    printf("Errormessage: %s\n", $mysqli->error);
                }
            }
        }

        // Update the laststocked.txt with current timestamp
        $laststocked = fopen(ROOT . 'public/laststocked.txt', "w") or die("Unable to open file!");
        $timeNow = time();
        fwrite($laststocked, $timeNow);
        fclose($laststocked);

    }

    /**
     * Delete all rows where instrument is paid off.
     */
    public Function clearPaid()
    {
        // Delete all the accounts that are paid off, because AIMsi still has them in the statements export for some reason.
        $sql = ("DELETE FROM fishes WHERE crnt_payoff = '0.00'");
        $query = $this->db->prepare($sql);
        $query->execute();
    }

    /**
     * Clear out both 'crnt_extra' columns. We will use these later.
     */
    public Function clearExtras()
    {
        // Clear out both 'extra' fields
        $sql = ("UPDATE fishes SET crnt_extra2 = '', crnt_extra1 = ''");
        $query = $this->db->prepare($sql);
        $query->execute();
    }

    /**
     * Delete all rows where account is not past due.
     */
    public Function clearCurrent($accounts)
    {
        foreach($accounts as $account) {
            if (strtotime($account->crnt_ndate) > strtotime("today midnight")) {
                $sql = ("DELETE FROM fishes WHERE crnt_pk = $account->crnt_pk");
                $query = $this->db->prepare($sql);
                $query->execute();
            }
        }        
    }

    /**
     * Delete all rows where there is no contact info.
     */
    public Function clearNoContact()
    {

        $sql = ("DELETE FROM fishes WHERE (crnt_email = '' AND crnt_hmphone = '')");
        $query = $this->db->prepare($sql);
        $query->execute();
      
    }

    /**
     * Calculate days late and insert in crnt_extra1 field
     */
    public Function daysLate($accounts)
    {
        foreach ($accounts as $account) {
            $dueDate = strtotime($account->crnt_ndate);
            $today = strtotime('00:00:00');
            $daysLate = ($today - $dueDate)/60/60/24;
            $sql = ("UPDATE fishes SET crnt_extra1 = '$daysLate' WHERE crnt_pk = '$account->crnt_pk'");
            $dueQuery = $this->db->prepare($sql);
            if(!$dueQuery->execute()) {
                printf("Errormessage: %s\n", $mysqli->error);
            }
        }

    }

    /**
     * Update table with real current payment due
     */
    public Function dueCalc($accounts)
    {
        foreach ($accounts as $account) {
            $nowDue = $this->dueCalculator($account);
            $sql = ("UPDATE fishes SET crnt_paymentdue = '$nowDue' WHERE crnt_pk = '$account->crnt_pk'");
            $query = $this->db->prepare($sql);
            if(!$query->execute()) {
                printf("Errormessage: %s\n", $mysqli->error);
            }
        }
    }

    /**
     * Payment Calculator
    */
    public function dueCalculator($account) {
    
        /**
         * This whole situation is frustrating. AIMsi won't give a simple
         * amount due in any data export, and certainly not in the statements
         * export we use for this, so we have to calculate it here. It's not
         * 100% fool-proof, but it works most of the time.
         */

        // SET SOME DATE VARIABLES
        // Not needed?
        // $today = strtotime('00:00:00');
        // $dateToday = DATE('j', time());
        // $dateDue = DATE('j', strtotime($account->crnt_ndate));

        // SET THE LATE FEE
        $lateFee = 10;

        // TRY TO GET THE AMOUNT OF A SINGLE PAYMENT.
        // $numPays = explode(' ', $account->crnt_payments, 2);
        // $num = $numPays[0];
        // // HAS TO BE AT LEAST ONE PAYMENT DUE
        // if($num == 0) { $num = 1; }
        // // GET THE AMOUNT OF A SINGLE PAYMENT. SUCH A HASSLE
        // $onePayment = $account->crnt_paymentdue / $num;  

        // IF THE DUE DATE (AS IN DAY OF THE MONTH) IS GREATER THAN TODAY
        // NOTE: This may not be needed anymore because I have since added the 
        // clearCurrent() method above.
        // if ($dateDue > $dateToday) {
        //     // THEN AIMSI HAS OVERSHOT THE TRUE PAYMENT DUE BY ONE PAYMENT
        //     // SO WE NEED TO SUBTRACT THAT
        //     $amountDue = $account->crnt_paymentdue - $onePayment;
        // } else {
        //     // OTHERWISE THE AMT DUE IS THE PAYMENT DUE
        //     $amountDue = $account->crnt_paymentdue;
        // }
        $amountDue = $account->crnt_paymentdue;

        // SO FAR SO GOOD. LET'S GET THE DAYS LATE
        $daysLate = $account->crnt_extra1;

        // DETERMINE HOW MANY LATE FEES DUE
        $numberLateFees = 0;
        if ($daysLate > 10) {
            $numberLateFees = floor(($daysLate - 10) / 30) + 1;
        }

        // GET LATE FEE
        $totalLateFee = $numberLateFees * $lateFee;

        // THIS WHOLE CONVOLUTED THING TRIES TO ACCOUNT FOR THE "ASSESSED" FEES
        if (($account->crnt_latedue - $lateFee) > 0) {
            if ($daysLate > 0 AND $daysLate < 11) {
                $totalLateFee = $totalLateFee + ($account->crnt_latedue - $totalLateFee - $lateFee);
            } else if ($daysLate > 30 AND $daysLate < 41) {
                $totalLateFee = $totalLateFee + ($account->crnt_latedue - $totalLateFee - $lateFee);
            } else if ($daysLate > 60 AND $daysLate < 71) {
                $totalLateFee = $totalLateFee + ($account->crnt_latedue - $totalLateFee - $lateFee);
            } else {
                $totalLateFee = $totalLateFee + ($account->crnt_latedue - $totalLateFee);
            }
        }

        $totalAmtDue = $amountDue + $totalLateFee;

        // We don't want the amount due to be greater than the remaining payoff balance on the account.
        if($account->crnt_amtdue == $account->crnt_payoff) {
            $totalAmtDue = $account->crnt_payoff;
        }

        return $totalAmtDue;
    }

    /**
     * Get account list
     * $emailFilter defults to any dumb value, like "monkey", return all rows,
     * but if passed "" it will keep out accounts with no email address.
    */
    public function accountList($apay, $min, $max, $emailFilter = "monkey")
    {
        // The ORDER BY variable is '* 1' because crnt_extra1 must be stored as VARCHAR,
        // because its initial value is a string.
        $sql = "SELECT * FROM fishes WHERE (crnt_rntdesc <> '$apay' AND 
                                            crnt_extra1 >= $min AND 
                                            crnt_extra1 <= $max AND 
                                            crnt_extra2 <> 'Y' AND
                                            crnt_email <> '$emailFilter') ORDER BY crnt_extra1 * 1";
        $query = $this->db->prepare($sql);
        $query->execute();
        
        $result = $query->fetchAll();

        if (sizeof($result) > 0) {
            return $result;
        } else {
            header('location: ' . URL . 'home/done');
        }
    }

    /**
    * Get history
    */
    public function accountHistory($account)
    {
        $sql = "SELECT * FROM contact WHERE ctac_acct = $account ORDER BY ctac_date DESC";
        $query = $this->db->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    /**
    * Get online payments
    */
    public function getOnlinePayments()
    {
        $sql = "SELECT * FROM contact WHERE (ctac_action = 'Paid Online' AND ctac_seen <> 'Y') ORDER BY ctac_date";
        $query = $this->db->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    /**
     * Mark a payment as "seen"
     */
    public function markPaymentSeen($ID) {
        $sql = "UPDATE contact SET ctac_seen = 'Y' WHERE ID = $ID";
        $query = $this->db->prepare($sql);
        $query->execute();
    }

    /**
    * Get sticky notes
    */
    public function accountSticky($account)
    {
        $sql = "SELECT * FROM sticky WHERE sticky_acct = $account ORDER BY sticky_date DESC";
        $query = $this->db->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }    

    /**
     * Post a Sticky Note
     */
    public function postSticky($data)
    {
        $sql = "INSERT INTO sticky (sticky_acct,
                                    sticky_date,
                                    sticky_lanid,
                                    sticky_text) 
                                    VALUES 
                                    (:sticky_acct,
                                    :sticky_date,
                                    :sticky_lanid,
                                    :sticky_text)";
        $query = $this->db->prepare($sql);
        $parameters = array(':sticky_acct' => $data['ctac_acct'],
                            ':sticky_date' => $data['ctac_date'],
                            ':sticky_lanid' => $data['ctac_lanid'],
                            ':sticky_text' => $data['ctac_note'],
                            
                            );

        // useful for debugging: you can see the SQL behind above construction by using:
        // echo '[ PDO DEBUG ]: ' . Helper::debugPDO($sql, $parameters);  exit();

        $query->execute($parameters);
    }

    /**
     * Post a Contact Note
     */
    public function postContact($data)
    {
        $sql = "INSERT INTO contact (ctac_acct,
                                    ctac_date,
                                    ctac_lanid,
                                    ctac_action,
                                    ctac_note,
                                    ctac_daysLate) 
                                    VALUES 
                                    (:ctac_acct,
                                    :ctac_date,
                                    :ctac_lanid,
                                    :ctac_action,
                                    :ctac_note,
                                    :ctac_daysLate)";
        $query = $this->db->prepare($sql);
        $parameters = array(':ctac_acct' => $data['ctac_acct'],
                            ':ctac_date' => $data['ctac_date'],
                            ':ctac_lanid' => $data['ctac_lanid'],
                            ':ctac_action' => $data['ctac_action'],
                            ':ctac_note' => $data['ctac_note'],
                            ':ctac_daysLate' => $data['ctac_daysLate'],);

        // useful for debugging: you can see the SQL behind above construction by using:
        // echo '[ PDO DEBUG ]: ' . Helper::debugPDO($sql, $parameters);  exit();

        $query->execute($parameters);
    }

    /**
     * Update fishes list if called, sets extra2 = Y
     * if we don't want it on the list anymore.
     */
    public function delistAccount($acct, $seen)
    {
        $sql = "UPDATE fishes SET crnt_extra2 = :seen WHERE crnt_acct = :acct";        
        $query = $this->db->prepare($sql);
        $parameters = array(':seen' => $seen, ':acct' => $acct);
        $query->execute($parameters);
    }

    /**
     * Delete a sticky note
     */
    public function deleteSticky($id)
    {
        $sql = "DELETE FROM sticky WHERE sticky_ID = :id";
        $query = $this->db->prepare($sql);
        $parameters = array(':id' => $id);

        // useful for debugging: you can see the SQL behind above construction by using:
        // echo '[ PDO DEBUG ]: ' . Helper::debugPDO($sql, $parameters);  exit();

        $query->execute($parameters);
    }

    /**
     * Account search
    */
    public function displayAccount($search)
    {
        // FORMAT A PHONE NUMBER SEARCH, WITH HYPHENS
        if(preg_match( '/^(\d{3})\-(\d{3})\-(\d{4})$/', $search,  $matches ) ) {
            $search = "(" . $matches[1] . ') ' .$matches[2] . '-' . $matches[3];
        }
        // FORMAT A PHONE NUMBER SEARCH, WITHOUT HYPHENS
        if(preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $search,  $matches ) ) {
            $search = "(" . $matches[1] . ') ' .$matches[2] . '-' . $matches[3];
        }

        $sql = "SELECT * FROM fishes WHERE :search IN (crnt_acct,crnt_hmphone,crnt_email)";
        $query = $this->db->prepare($sql);      
        $parameters = array(':search' => $search);  
        $query->execute($parameters);
        $result = $query->fetchAll();

        if (sizeof($result) > 0) {
            return $result;
        } else {
            header('location: ' . URL . 'home/noresults');
        }
        
    }

    /**
     * If there are multiple sub accounts, this adds up the total payments due
     * and concatenates strings of the inventory and sub-account numbers.
     */
    public function addItUp($accountList) {
        // Set all variables initially to nothing.
        $totalPay = 0;
        $totalInventory = "";
        $totalSub = "";
        $totalStudent = "";
        // For first iteration, no divider.
        $divider = "";
        foreach ($accountList as $account) {
            if ($accountList[0]->crnt_acct == $account->crnt_acct) {
                $totalPay += $account->crnt_paymentdue;
                $totalInventory .= $divider . $account->crnt_desc;
                $totalSub .= " -" . $account->crnt_sub;
                $totalStudent .= $divider . $account->crnt_student . ": " . $account->crnt_desc;
            }
            // Now we have a divider for subsequent iterations.
            $divider = " | ";
        }
        // Create the object for use by home.php
        $addedItems = (object) [
            'payment' => $totalPay,
            'inventory' => $totalInventory,
            'sub' => $totalSub,
            'student' => $totalStudent
        ];
        return $addedItems;
    }

    /**
     * Gets the currently displayed customer data for inclusion in the
     * customer-data tag n the footer, used by JavaScript for boilerplate
     * text in the contact notes.
     * 
     * I'm not using the MySQL column header names for this, because I'm 
     * not sure I want them visible in the HTML. Better safe than sorry.
     */
    public function customerData($account, $addItUp) {
        $customerData =   '{"NAME": "' . htmlspecialchars($account->crnt_name) . 
                        '", "DAYSLATE": "' . $account->crnt_extra1 . 
                        '", "BALDUE": "' . number_format($addItUp->payment, 2) . 
                        '", "DUEDATE": "' . $account->crnt_ndate . 
                        '", "ACCT": "' . $account->crnt_acct . $addItUp->sub . 
                        '", "BUSPHONE": "' . BUS_PHONE . 
                        '", "BUSPAYMENT": "' . BUS_PAYMENT . 
                        '", "INSTRUMENT": "' . $account->crnt_desc . 
                        '", "STATEMENTDATE": "' . date('F j, Y') . 
                        '", "BUSNAME": "' . BUS_NAME . 
                        '"}';
        return $customerData;
    }


    /**
     * Single mailer
     * This configuration of PHPMailer assumes you're using a Gmail account.
     * You'll have to change this to settings for your own email situation.
     */
    public function singleMailer($custEmail, $custName, $custMessage, $custSubject = EMAIL_SUBJECT) {

        //Add line breaks for HTML message
        $htmlMessage = nl2br($custMessage);
    
        //Create a new PHPMailer instance
        $mail = new PHPMailer;
        //Tell PHPMailer to use SMTP
        $mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 0;
        //Set the hostname of the mail server
        $mail->Host = 'smtp.gmail.com';
        // use
        // $mail->Host = gethostbyname('smtp.gmail.com');
        // if your network does not support SMTP over IPv6
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = 587;
        //Set the encryption system to use - ssl (deprecated) or tls
        $mail->SMTPSecure = 'tls';
        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = EMAIL_ADDRESS;
        //Password to use for SMTP authentication
        $mail->Password = EMAIL_PASSWORD;
        //Set who the message is to be sent from
        $mail->setFrom(EMAIL_ADDRESS, EMAIL_NAME);
        //Set an alternative reply-to address
        $mail->addReplyTo(EMAIL_ADDRESS, EMAIL_NAME);
        //Set who the message is to be sent to
        $mail->addAddress($custEmail, $custName);
        //Set the subject line
        $mail->Subject = $custSubject;
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        $mail->msgHTML($htmlMessage);
        //Replace the plain text body with one created manually
        $mail->AltBody = $custMessage;

        //send the message, check for errors
        // if (!$mail->send()) {
        //     echo "Mailer Error: " . $mail->ErrorInfo;
        // } else {
        //     echo "Message sent!";
        // }
    }

    /**
     * Email personalizer, PHP version (there is an JavaScript version as well)
     */
    public function personalizer($text, $account) {
        // Turn the account object into an array
        $account = (array) $account;
        // Loop through the array keys (i.e. column headers) and replace boilerplate placeholders 
        // (which are the column header names in the data.json file)
        $newText = str_replace(array_keys($account), $account, $text);
        // Now replace store info placeholders with store info constants from the config file.
        $newText = str_replace(["BUS_NAME", "BUS_PHONE", "BUS_SITE", "BUS_PAYMENT"], 
                                [BUS_NAME, BUS_PHONE, BUS_SITE, BUS_PAYMENT], 
                                $newText);
        return $newText;
    }

    /**
     * Mass Mailer
     */
    public function massMailer($accountQuery, $storeData) {
        
        // Go through the store data "mass-mailer" keys one at a time
        foreach ($storeData->mass_mailer as $lateCategory => $elements) {

            // Loop through account list
            foreach ($accountQuery as $account) { 

                // See if days late is in JSON key
                if (in_array($account->crnt_extra1, $elements->dayslate)) {

                    // Personalize the boilerplate
                    $personalized = $this->personalizer($elements->body, $account);

                    // Send to emailer
                    $this->singleMailer($account->crnt_email, $account->crnt_name, $personalized, $elements->subject);

                    // Get the account off the list.
                    $this->delistAccount($account->crnt_acct, "Y");

                }            
            }
        }
    }

    /**
     * Return array of bojects with data about the batches of accounts at certain lateness levels.
     * This is basically the same as the massMailer() function, but returns an array instead of mailing anyone.
     */
    public function emailBatches($accountQuery, $storeData) {

        $emailBatches = [];

        // Go through the store data "mass-mailer" keys one at a time
        foreach ($storeData->mass_mailer as $lateCategory => $elements) {

            // Start a new array to hold accounts with days late matching the data key.
            $theseAccounts = [];

            // Loop through account list
            foreach ($accountQuery as $account) { 

                // See if days late is in JSON key
                if (in_array($account->crnt_extra1, $elements->dayslate)) {

                    // Add this account row to the array.
                    array_push($theseAccounts, $account);

                }

            }

            $thisBatch = new stdClass();

            $thisBatch->latenessCategory = $lateCategory;
            $thisBatch->daysLate = implode(", ", $elements->dayslate);
            $thisBatch->numAccounts = sizeof($theseAccounts);
            $thisBatch->emailSubject = $elements->subject;
            if (sizeof($theseAccounts) > 0) {
                $thisBatch->emailBody = $this->personalizer($elements->body, $theseAccounts[0]);
            } else {
                $thisBatch->emailBody = $elements->body;
            }
            

            array_push($emailBatches, $thisBatch);

        }

        return $emailBatches;

    }
    
    /**
     * All Aging Category Totals
     * Returns an object with the counts of each aging category
     */
    public function allAging() {

        $accounts = $this->accountList("all", 0, 180);

        $one = array_filter($accounts, function($account) {
            return ($account->crnt_extra1 > 0 && $account->crnt_extra1 <11);
        });
        $eleven = array_filter($accounts, function($account) {
            return ($account->crnt_extra1 > 10 && $account->crnt_extra1 <31);
        });
        $thirtyOne = array_filter($accounts, function($account) {
            return ($account->crnt_extra1 > 30 && $account->crnt_extra1 <61);
        });
        $sixtyOne = array_filter($accounts, function($account) {
            return ($account->crnt_extra1 > 60 && $account->crnt_extra1 <91);
        });
        $ninetyOne = array_filter($accounts, function($account) {
            return ($account->crnt_extra1 > 90 && $account->crnt_extra1 <121);
        });
        $oneTwentyPlus = array_filter($accounts, function($account) {
            return $account->crnt_extra1 > 120;
        });

        $aging = new stdClass();

        $aging->all = count($accounts);
        $aging->one = count($one);
        $aging->eleven = count($eleven);
        $aging->thirtyOne = count($thirtyOne);
        $aging->sixtyOne = count($sixtyOne);
        $aging->ninetyOne = count($ninetyOne);
        $aging->oneTwentyPlus = count($oneTwentyPlus);

        return $aging;
    }

















    

    /**
     * Get all songs from database
     */
    public function getAllSongs()
    {
        $sql = "SELECT id, artist, track, link FROM song";
        $query = $this->db->prepare($sql);
        $query->execute();

        // fetchAll() is the PDO method that gets all result rows, here in object-style because we defined this in
        // core/controller.php! If you prefer to get an associative array as the result, then do
        // $query->fetchAll(PDO::FETCH_ASSOC); or change core/controller.php's PDO options to
        // $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ...
        return $query->fetchAll();
    }

    /**
     * Add a song to database
     * TODO put this explanation into readme and remove it from here
     * Please note that it's not necessary to "clean" our input in any way. With PDO all input is escaped properly
     * automatically. We also don't use strip_tags() etc. here so we keep the input 100% original (so it's possible
     * to save HTML and JS to the database, which is a valid use case). Data will only be cleaned when putting it out
     * in the views (see the views for more info).
     * @param string $artist Artist
     * @param string $track Track
     * @param string $link Link
     */
    public function addSong($artist, $track, $link)
    {
        $sql = "INSERT INTO song (artist, track, link) VALUES (:artist, :track, :link)";
        $query = $this->db->prepare($sql);
        $parameters = array(':artist' => $artist, ':track' => $track, ':link' => $link);

        // useful for debugging: you can see the SQL behind above construction by using:
        // echo '[ PDO DEBUG ]: ' . Helper::debugPDO($sql, $parameters);  exit();

        $query->execute($parameters);
    }

    /**
     * Delete a song in the database
     * Please note: this is just an example! In a real application you would not simply let everybody
     * add/update/delete stuff!
     * @param int $song_id Id of song
     */
    public function deleteSong($song_id)
    {
        $sql = "DELETE FROM song WHERE id = :song_id";
        $query = $this->db->prepare($sql);
        $parameters = array(':song_id' => $song_id);

        // useful for debugging: you can see the SQL behind above construction by using:
        // echo '[ PDO DEBUG ]: ' . Helper::debugPDO($sql, $parameters);  exit();

        $query->execute($parameters);
    }

    /**
     * Get a song from database
     */
    public function getSong($song_id)
    {
        $sql = "SELECT id, artist, track, link FROM song WHERE id = :song_id LIMIT 1";
        $query = $this->db->prepare($sql);
        $parameters = array(':song_id' => $song_id);

        // useful for debugging: you can see the SQL behind above construction by using:
        // echo '[ PDO DEBUG ]: ' . Helper::debugPDO($sql, $parameters);  exit();

        $query->execute($parameters);

        // fetch() is the PDO method that get exactly one result
        return $query->fetch();
    }

    /**
     * Update a song in database
     * // TODO put this explaination into readme and remove it from here
     * Please note that it's not necessary to "clean" our input in any way. With PDO all input is escaped properly
     * automatically. We also don't use strip_tags() etc. here so we keep the input 100% original (so it's possible
     * to save HTML and JS to the database, which is a valid use case). Data will only be cleaned when putting it out
     * in the views (see the views for more info).
     * @param string $artist Artist
     * @param string $track Track
     * @param string $link Link
     * @param int $song_id Id
     */
    public function updateSong($artist, $track, $link, $song_id)
    {
        $sql = "UPDATE song SET artist = :artist, track = :track, link = :link WHERE id = :song_id";
        $query = $this->db->prepare($sql);
        $parameters = array(':artist' => $artist, ':track' => $track, ':link' => $link, ':song_id' => $song_id);

        // useful for debugging: you can see the SQL behind above construction by using:
        // echo '[ PDO DEBUG ]: ' . Helper::debugPDO($sql, $parameters);  exit();

        $query->execute($parameters);
    }

    /**
     * Get simple "stats". This is just a simple demo to show
     * how to use more than one model in a controller (see application/controller/songs.php for more)
     */
    public function getAmountOfSongs()
    {
        $sql = "SELECT COUNT(id) AS amount_of_songs FROM song";
        $query = $this->db->prepare($sql);
        $query->execute();

        // fetch() is the PDO method that get exactly one result
        return $query->fetch()->amount_of_songs;
    }
}
