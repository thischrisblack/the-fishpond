<?php

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




  /** -------------------------------------------------------------------------
   * GENERAL FUNCTIONS
   ** ------------------------------------------------------------------------*/

  /**
   * Sets session variables for list filters like aging categories and autopays.
   * Also checks if user is logged in.
   */
  public function setSessionVariables()
  {

    session_start();

    // If user not logged in, go to login page.
    if ($_SESSION["loggedin"] != "Y") {
      header('location: ' . URL . 'login');
    } else {
      $_SESSION["loggedin"] = "Y";
    }

    // If no session variables set, set defaults.
    if (!isset($_SESSION["minmax"])) {
      $_SESSION["minmax"] = "1-180";
    }
    if (!isset($_SESSION["apay"])) {
      $_SESSION["apay"] = "all";
    }
    if (!isset($_SESSION["ctac_lanid"])) {
      $_SESSION["ctac_lanid"] = "";
    }

    // If there is $_GET data, set the appropriate session variable.
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





  /** -------------------------------------------------------------------------
   * POND STOCKING FUNCTIONS
   * This is a LONG series of functions getting the data into the database.
   * -------------------------------------------------------------------------*/

  /**
   * Load account data into database
   */
  public function stockPond($data)
  {
    /**
     * Okay, let's talk about this. This seems like a laborious way to do it,
     * but here's why: We're not using LOAD DATA INFILE because if you, like
     * me, are on a shared hosting plan with cPanel, your MySQL user won't have 
     * global privileges to use that, so we have to go a line at a time.
     */

    // Remove existing data from database.
    $sql = ("TRUNCATE TABLE fishes");
    $query = $this->db->prepare($sql);
    $query->execute();

    // Get those apostrophes and quotes escaped.
    $data = addslashes($data);

    // Start parsing the data, which has been copied from an Excel file and pasted into a textarea.
    $fish = str_getcsv($data, "\n");
    foreach ($fish as $account) {
      $account = str_getcsv($account, ";");   // $account = each row         
      foreach ($account as $item) {
        $item = str_getcsv($item, "\t");    // $item = each field

        // Skip the first row if it's headers.
        if ($item[0] == "crnt_pk") {
          continue;
        }

        $sql = ("INSERT INTO `fishes`(`crnt_pk`,
                                              `crnt_acct`, 
                                              `crnt_sub`, 
                                              `crnt_desc`, 
                                              `crnt_student`, 
                                              `crnt_lastdate`, 
                                              `crnt_lastamt`, 
                                              `crnt_amtdue`, 
                                              `crnt_payoff`, 
                                              `crnt_ndate`, 
                                              `crnt_paymentdue`, 
                                              `crnt_latedue`, 
                                              `crnt_otherdue`, 
                                              `crnt_payments`, 
                                              `crnt_last`, 
                                              `crnt_zip`, 
                                              `crnt_qty`, 
                                              `crnt_sku`, 
                                              `crnt_email`, 
                                              `crnt_emailmethod`, 
                                              `crnt_addr1`, 
                                              `crnt_addr2`, 
                                              `crnt_addr3`, 
                                              `crnt_addr4`, 
                                              `crnt_addr5`, 
                                              `crnt_name`, 
                                              `crnt_hmphone`, 
                                              `crnt_rntdesc`, 
                                              `crnt_extra1`, 
                                              `crnt_extra2`) 
                                      VALUES ('$item[0]',
                                              '$item[1]',
                                              '$item[2]',
                                              '$item[3]',
                                              '$item[4]',
                                              '$item[5]',
                                              '$item[6]',
                                              '$item[7]',
                                              '$item[8]',
                                              '$item[9]',
                                              '$item[10]',
                                              '$item[11]',
                                              '$item[12]',
                                              '$item[13]',
                                              '$item[14]',
                                              '$item[15]',
                                              '$item[16]',
                                              '$item[17]',
                                              '$item[18]',
                                              '$item[19]',
                                              '$item[20]',
                                              '$item[21]',
                                              '$item[22]',
                                              '$item[23]',
                                              '$item[24]',
                                              '$item[25]',
                                              '$item[26]',
                                              '$item[27]',
                                              '$item[28]',
                                              '$item[29]')
                  ");
        $query = $this->db->prepare($sql);
        if (!$query->execute()) {
          printf("Errormessage: %s\n", $query->error);
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
  public function clearPaid()
  {
    $sql = ("DELETE FROM fishes WHERE crnt_payoff = '0.00'");
    $query = $this->db->prepare($sql);
    $query->execute();
  }

  /**
   * Clear out both 'crnt_extra' columns. We will use these later.
   */
  public function clearExtras()
  {
    $sql = ("UPDATE fishes SET crnt_extra2 = '', crnt_extra1 = ''");
    $query = $this->db->prepare($sql);
    $query->execute();
  }

  /**
   * Delete all rows where account is not past due.
   */
  public function clearCurrent($accounts)
  {
    foreach ($accounts as $account) {
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
  public function clearNoContact()
  {

    $sql = ("DELETE FROM fishes WHERE (crnt_email = '' AND crnt_hmphone = '(   )    -')");
    $query = $this->db->prepare($sql);
    $query->execute();
  }

  /**
   * Calculate days late and insert in crnt_extra1 field
   */
  public function daysLate($accounts)
  {
    foreach ($accounts as $account) {
      $dueDate = strtotime($account->crnt_ndate);
      $today = strtotime('00:00:00');
      $daysLate = ($today - $dueDate) / 60 / 60 / 24;
      $sql = ("UPDATE fishes SET crnt_extra1 = '$daysLate' WHERE crnt_pk = '$account->crnt_pk'");
      $dueQuery = $this->db->prepare($sql);
      if (!$dueQuery->execute()) {
        printf("Errormessage: %s\n", $dueQuery->error);
      }
    }
  }

  /**
   * Update table with real current payment due
   */
  public function dueCalc($accounts)
  {
    foreach ($accounts as $account) {
      $nowDue = $this->dueCalculator($account); // dueCalculator() is right below
      $sql = ("UPDATE fishes SET crnt_paymentdue = '$nowDue' WHERE crnt_pk = '$account->crnt_pk'");
      $query = $this->db->prepare($sql);
      if (!$query->execute()) {
        printf("Errormessage: %s\n", $query->error);
      }
    }
  }

  /**
   * Payment due calculator
   */
  public function dueCalculator($account)
  {

    /**
     * This function calculates the balance due for each account, which is a value
     * not availble in the AIMsi exported data. We must work through a number of conditions
     * and do a little math to get the proper amount. There is one edge case where this 
     * formula fails, and that is when the customer has made a *partial* payment previously. 
     * There is nothing in the data export from AIMsi to indicate when this condition applies,
     * so right now I can't find a way around this. For our customers, however, it is rare.
     */

    // First, let's do the easy one, if the payment due equals the total payoff.
    if ($account->crnt_amtdue == $account->crnt_payoff) {
      $totalAmtDue = $account->crnt_payoff;
      return $totalAmtDue;
    }

    // Get numeric dates of today and due date
    $dateToday = DATE('j', time());
    $dateDue = DATE('j', strtotime($account->crnt_ndate));

    // Get the number of payments due
    $numPays = explode(' ', $account->crnt_payments, 2);
    $num = $numPays[0];

    // The basic total due is the sum of these two columns
    $totalAmtDue = $account->crnt_paymentdue + $account->crnt_latedue;

    // A single payment is the crnt_paymentdue value divided by the number of payments due.
    // NOTE: If the account has a partial payment in the mix, this will give a wrong answer.
    $onePayment = $account->crnt_paymentdue / $num;

    // Conditional further total due calculation
    if ($dateDue > $dateToday) {
      $totalAmtDue = $totalAmtDue - $onePayment; // Subtract one payment.
    } else if ($dateToday - $dateDue > 10) {
      $totalAmtDue += 10; // Add the $10 late fee
    }

    return $totalAmtDue;
  }









  /** -------------------------------------------------------------------------
   * FUNCTIONS FOR HANDLING ONLINE PAYMENTS (if your store does that)
   * -------------------------------------------------------------------------*/

  /**
   * Get online payments
   * These payments will be entered in your contact database by your script that handles the online payments.
   */
  public function getOnlinePayments()
  {
    $sql = "SELECT * FROM contact WHERE (ctac_action = 'Paid Online' AND ctac_seen <> 'Y') ORDER BY ctac_date";
    $query = $this->db->prepare($sql);
    $query->execute();

    return $query->fetchAll();
  }

  /**
   * Mark a payment as "seen", when the user deletes a payment from the list.
   */
  public function markPaymentSeen($ID)
  {
    $sql = "UPDATE contact SET ctac_seen = 'Y' WHERE ID = $ID";
    $query = $this->db->prepare($sql);
    $query->execute();
  }








  /** -------------------------------------------------------------------------
   * FUNCTIONS TO GET ACCOUNT LISTS AND HISTORY
   * -------------------------------------------------------------------------*/

  /**
   * Get account list
   * $emailFilter defaulting to "monkey" returns all rows, because no email address is
   * "monkey", but if passed "" it will remove accounts with no email address.
   * Passing "" is used by the massMailer() function.
   */
  public function accountList($apay, $min, $max, $emailFilter = "monkey")
  {
    /**
     * The ORDER BY variable is '* 1' because crnt_extra1 is stored as VARCHAR,
     * because its initial value is a string. This converts it to a number.
     */
    $sql = "SELECT * FROM fishes WHERE (crnt_rntdesc <> '$apay' AND 
                                            crnt_extra1 >= $min AND 
                                            crnt_extra1 <= $max AND 
                                            crnt_extra2 <> 'Y' AND
                                            crnt_email <> '$emailFilter') ORDER BY crnt_extra1 * 1";
    $query = $this->db->prepare($sql);
    $query->execute();

    $result = $query->fetchAll();

    return $result;
  }

  /**
   * All Aging Category Totals
   * Returns an object with the counts of each aging category.
   * This data is used in the navbar, and in the "done" page.
   */
  public function allAging()
  {

    $accounts = $this->accountList("all", 1, 180);

    $one = array_filter($accounts, function ($account) {
      return ($account->crnt_extra1 > 0 && $account->crnt_extra1 < 11);
    });
    $eleven = array_filter($accounts, function ($account) {
      return ($account->crnt_extra1 > 10 && $account->crnt_extra1 < 31);
    });
    $thirtyOne = array_filter($accounts, function ($account) {
      return ($account->crnt_extra1 > 30 && $account->crnt_extra1 < 61);
    });
    $sixtyOne = array_filter($accounts, function ($account) {
      return ($account->crnt_extra1 > 60 && $account->crnt_extra1 < 91);
    });
    $ninetyOne = array_filter($accounts, function ($account) {
      return ($account->crnt_extra1 > 90 && $account->crnt_extra1 < 121);
    });
    $oneTwentyPlus = array_filter($accounts, function ($account) {
      return ($account->crnt_extra1 > 120 && $account->crnt_extra1 < 181);
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
   * If there are multiple sub accounts, this adds up the total payments due
   * and concatenates strings of the inventory and sub-account numbers. This prevents
   * duplicate account views.
   */
  public function addItUp($accountList)
  {
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
   * Get history, for the right column.
   */
  public function accountHistory($account)
  {
    $sql = "SELECT * FROM contact WHERE ctac_acct = $account ORDER BY ctac_date DESC";
    $query = $this->db->prepare($sql);
    $query->execute();

    return $query->fetchAll();
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
   * Gets the currently displayed customer data for inclusion in the
   * customer-data tag in the right column, used by JavaScript for boilerplate
   * text in the contact notes.
   * 
   * I'm not using the MySQL column header names for this, because I'm 
   * not sure I want them visible in the HTML. 
   */
  public function customerData($account, $addItUp)
  {
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
   * Account search
   */
  public function displayAccount($search)
  {
    // FORMAT A PHONE NUMBER SEARCH, WITH HYPHENS
    if (preg_match('/^(\d{3})\-(\d{3})\-(\d{4})$/', $search,  $matches)) {
      $search = "(" . $matches[1] . ') ' . $matches[2] . '-' . $matches[3];
    }
    // FORMAT A PHONE NUMBER SEARCH, WITHOUT HYPHENS
    if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $search,  $matches)) {
      $search = "(" . $matches[1] . ') ' . $matches[2] . '-' . $matches[3];
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








  /** -------------------------------------------------------------------------
   * FUNCTIONS TO POST NEW CONTACT NOTES
   * -------------------------------------------------------------------------*/

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
    $parameters = array(
      ':sticky_acct' => $data['ctac_acct'],
      ':sticky_date' => $data['ctac_date'],
      ':sticky_lanid' => $data['ctac_lanid'],
      ':sticky_text' => $data['ctac_note'],

    );

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
    $parameters = array(
      ':ctac_acct' => $data['ctac_acct'],
      ':ctac_date' => $data['ctac_date'],
      ':ctac_lanid' => $data['ctac_lanid'],
      ':ctac_action' => $data['ctac_action'],
      ':ctac_note' => $data['ctac_note'],
      ':ctac_daysLate' => $data['ctac_daysLate'],
    );

    $query->execute($parameters);
  }

  /**
   * Remove account from the list by setting crnt_extra2 to "Y"
   */
  public function delistAccount($acct, $seen)
  {
    $sql = "UPDATE fishes SET crnt_extra2 = :seen WHERE crnt_acct = :acct";
    $query = $this->db->prepare($sql);
    $parameters = array(':seen' => $seen, ':acct' => $acct);
    $query->execute($parameters);
  }










  /** -------------------------------------------------------------------------
   * FUNCTIONS FOR EMAILING AND MASS MAILING
   * -------------------------------------------------------------------------*/
  /**
   * Single mailer
   * This configuration of PHPMailer assumes you're using a Gmail account.
   * You'll have to change this to settings for your own email situation.
   */
  public function singleMailer($custAcct, $custEmail, $custName, $custMessage, $custSubject = EMAIL_SUBJECT)
  {

    //Add account number to subject so Gmail will thread converstaions separately
    $custSubject = $custSubject . " - " . $custAcct;

    $to = $custEmail;
    $subject = $custSubject;
    $message = $custMessage;
    $headers = "From: " . EMAIL_NAME . " <" . EMAIL_ADDRESS . ">\r\n" .
      "Reply-To: " . EMAIL_REPLY_TO . "\r\n" .
      'X-Mailer: PHP/' . phpversion();

    $sendThatMail = mail($to, $subject, $message, $headers);

    if (!$sendThatMail) {
      $errorMessage = error_get_last()['message'];
      echo $errorMessage;
    }
  }

  /**
   * Mass Mailer
   */
  public function massMailer($accountQuery, $storeData)
  {

    // Go through the data.json "mass-mailer" keys one at a time
    foreach ($storeData->mass_mailer as $lateCategory => $elements) {

      // Loop through account list
      foreach ($accountQuery as $account) {

        // See if days late is in JSON key
        if (in_array($account->crnt_extra1, $elements->dayslate)) {

          // Personalize the boilerplate
          $personalized = $this->personalizer($elements->body, $account);

          // Send to emailer
          $this->singleMailer($account->crnt_acct, $account->crnt_email, $account->crnt_name, $personalized, $elements->subject);

          // Get the account off the list.
          $this->delistAccount($account->crnt_acct, "Y");
        }
      }
    }
  }

  /**
   * Return array of objects with data about the batches of accounts at certain lateness levels. Used by the emailer.php page.
   * This is basically the same as the massMailer() function, but returns an array instead of mailing anyone.
   */
  public function emailBatches($accountQuery, $storeData)
  {

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
        // If there's no account, I still want to se the boilerplate text.
        $thisBatch->emailBody = $elements->body;
      }

      array_push($emailBatches, $thisBatch);
    }

    return $emailBatches;
  }

  /**
   * Email personalizer, PHP version (there is an JavaScript version as well)
   */
  public function personalizer($text, $account)
  {
    // Turn the account object into an array
    $account = (array) $account;
    // Loop through the array keys (i.e. MySQL column headers) and replace boilerplate placeholders 
    // (which are the column header names in the data.json boilerplate text)
    $newText = str_replace(array_keys($account), $account, $text);
    // Now replace store info placeholders with store info constants from the config file.
    $newText = str_replace(
      ["BUS_NAME", "BUS_PHONE", "BUS_SITE", "BUS_PAYMENT"],
      [BUS_NAME, BUS_PHONE, BUS_SITE, BUS_PAYMENT],
      $newText
    );
    return $newText;
  }
}
