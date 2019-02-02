<?php 
/**
 * Class Stock
 *
 */
class Stock extends Controller
{

    /**
     * PAGE: index
     */
    public function index()
    {

        // Set body class for javascript
        $bodyID="stock";

        // Get online payment data
        $onlinePayments = $this->model->getOnlinePayments();

        // Get aging totals object.
        $aging = $this->model->allAging();

        // load views. 
        require APP . 'view/_templates/header.php';
        require APP . 'view/stock/index.php';
        require APP . 'view/_templates/footer.php';
    }

    /**
     * ACTION: Mark an online payment as seen and get it out of the list
     */
    public function paymentSeen($paymentID)
    {
        $this->model->markPaymentSeen($paymentID);
    }
    
    /**
     * ACTION: pondStock
     * This is the procedure for uploading the AIMsi datafile to the Fishpond,
     * and cleaning it up for our use.
     */
    public function pondStock()
    {

        // Increase the time limit, just in case
        set_time_limit(150);

        // if we have POST data to stock the pond.
        if (isset($_POST["stock_the_pond"])) {
            // Upload CSV file to database
            $this->model->stockPond($_POST["fishes"]);       
        }

        // Delete all accounts that are paid off.
        $this->model->clearPaid();

        // Clear both 'extra' fields for future use
        $this->model->clearExtras();

        // Delete accounts where there is no contact info
        $this->model->clearNoContact();

        // Get the acccount list object for next functions
        $accounts = $this->model->accountList("all", -1, 1000);

        // Delete all accounts that aren't past due.
        $this->model->clearCurrent($accounts);

        // Calculate days late and add it to the database in the extra1 field
        $this->model->daysLate($accounts);

        // Update the paymentdue field with correctly calculated amount
        $this->model->dueCalc($accounts);

        // where to go after pond has been stocked
        header('location: ' . URL . 'emailer');
    }
    
}