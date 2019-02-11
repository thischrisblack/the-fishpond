<?php

/**
 * Class Home
 */
class Home extends Controller
{
    /**
     * PAGE: index
     * This is the default page.
     */
    public function index()
    {

        // Set body class for javascript
        $bodyID="list";
        
        // Set session variables
        $session = $this->model->setSessionVariables(); 
        
        // Check for new contact note.
        if (isset($_POST["contact"])) {
            $this->newContact();
        }

        /**
         * Determine whether to display single account or the account list, depending upon if
         * a search was made from the search field. 
         */
        if (isset($_GET["lookup"])) {
            // If it's a search, run account query on the lookup value (account#, phone#, email)
            $accountQuery = $this->model->displayAccount($_GET["lookup"]);
            // Text for left column header in it's a lookup rather than the full list.
            $listHeader = "Account:";
        } else {
            // If no search, get the full account list
            $minmax = explode("-", $session->minmax);
            $min = $minmax[0];
            $max = $minmax[1];
            $accountQuery = $this->model->accountList($session->apay, $min, $max);
            // If no results, go to done page
            if (sizeof($accountQuery) < 1) {
                header('location: ' . URL . 'home/done');
            }
            // Text for left column header
            $listHeader = ($min) . "-" . ($max) . " (" . count($accountQuery) . ")";
            // $listHeader = count($accountQuery) . " ACCOUNTS";
        }

        // Get aging totals object for the navbar.
        $aging = $this->model->allAging();

        // Account history for the first account in the result set (i.e. the one displayed)
        $accountHistory = $this->model->accountHistory($accountQuery[0]->crnt_acct);

        // Sticky notes for that account.
        $accountSticky = $this->model->accountSticky($accountQuery[0]->crnt_acct); 

        // Get an object of totalled things (payment due, rented inventory, etc.) for when there are multiple subs on one account
        $addItUp = $this->model->addItUp($accountQuery);

        // Get the data JSON
        $storeData = json_decode(file_get_contents(ROOT . 'public/data.json'));

        // Get the laststocked.txt value
        $lastStocked = file_get_contents(ROOT . 'public/laststocked.txt');

        // This customer data (for the data tag in the right column, used by JavaScript)
        $customerData = htmlspecialchars($this->model->customerData($accountQuery[0], $addItUp));

        // load views
        require APP . 'view/_templates/header.php';
        require APP . 'view/home/left-column.php';
        require APP . 'view/home/middle-column.php';
        require APP . 'view/home/right-column.php';
        require APP . 'view/_templates/footer.php';
    }

    /**
     * ACTION: New contact note
     */
    public function newContact()
    {

        // Check if it's a sticky note
        if ($_POST["ctac_action"] == "Sticky Note") {
            // Use postSticky();
            $this->model->postSticky($_POST);
        } else {
            // If the SKIP button was used, change action to "Skipped."
            if ($_POST["contact"] == "SKIP") $_POST["ctac_action"] = "Skipped";

            // Use postContact() to save the contact note.;
            $this->model->postContact($_POST);

            // If it's an email, send the email
            if ($_POST["ctac_action"] == "Emailed") {
                $this->model->singleMailer($_POST["ctac_email"], $_POST["ctac_name"], $_POST["ctac_note"]);
            }             

            // Sets crnt_extra2 to Y if account to be removed (i.e. the "Keep Account Open" box is unchecked).
            $_POST["crnt_extra2"] = isset($_POST["crnt_extra2"]) ? $_POST["crnt_extra2"] : "Y";
            $this->model->delistAccount($_POST["ctac_acct"], $_POST["crnt_extra2"]);        
        }

        // where to go after contact note has been posted
        header('location: ' . URL . '');
    }

    /**
     * ACTION: deleteSticky
     */
    public function deleteSticky($id)
    {
        // if we have an id of a sticky note that should be deleted
        if (isset($id)) {
            $this->model->deleteSticky($id);
        }

        // where to go after sticky note has been deleted
        header('location: ' . URL . '');
    }

    /**
     * PAGE: No results
     */
    public function noResults()
    {
        // Set session variables
        $session = $this->model->setSessionVariables(); 

        // Get aging object for navbar
        $aging = $this->model->allAging();
        
        // load views
        require APP . 'view/_templates/header.php';
        require APP . 'view/home/noresults.php';
        require APP . 'view/_templates/footer.php';
    }

    /**
     * PAGE: No more fish
     */
    public function done()
    {
        // Set session variables
        $session = $this->model->setSessionVariables(); 
        
        // Get aging object for navbar and to see if there are any accounts left at all.
        $aging = $this->model->allAging();
        
        // load views
        require APP . 'view/_templates/header.php';
        require APP . 'view/home/done.php';
        require APP . 'view/_templates/footer.php';
    }
}
