<?php 
/**
 * Class Emailer
 *
 */
class Emailer extends Controller
{
    /**
     * PAGE: index
     */
    public function index()
    {

        // Set body class for javascript
        $bodyID="emailer";

        // Get the account list, except for autopay accounts
        $accountQuery = $this->model->accountList("Rental (Autopay)", 0, 180, "");

        // Get the data JSON
        $storeData = json_decode(file_get_contents(ROOT . 'public/data.json'));

        // Get the different mass email batch info
        $emailBatches = $this->model->emailBatches($accountQuery, $storeData);

        // Get aging totals object for the navbar.
        $aging = $this->model->allAging();

        if (isset($_POST["submit"])) {

            // Increase the time limit, because those emails take forever
            set_time_limit(150);

            // Email them!
            $this->model->massMailer($accountQuery, $storeData);

            // where to go after accounts are mailed
            header('location: ' . URL . 'emailer/startfishing');

        }

        require APP . 'view/_templates/header.php';
        require APP . 'view/emailer/index.php';
        require APP . 'view/_templates/footer.php';

    }

    /**
     * PAGE: Start fishing
     */
    public function startfishing()
    {

        // Get aging totals object for the navbar.
        $aging = $this->model->allAging();

        require APP . 'view/_templates/header.php';
        require APP . 'view/emailer/startfishing.php';
        require APP . 'view/_templates/footer.php';

    }

}