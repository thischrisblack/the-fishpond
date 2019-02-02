<?php 
/**
 * Class Login
 *
 */
class Login extends Controller
{
    /**
     * PAGE: index
     */
    public function index()
    {

        // Set body class for javascript
        $bodyID="login";

        // Get aging totals object.
        $aging = $this->model->allAging();

        if (isset($_POST["password"])) {

            if ($_POST["password"] == FISHPOND_PASSWORD) {
                session_start();
                $_SESSION["loggedin"] = "Y";
                header('location: ' . URL . '');
            } else {
                $wrongPassword = "Y";
            }

        }

        require APP . 'view/_templates/header.php';
        require APP . 'view/login/index.php';
        require APP . 'view/_templates/footer.php';

    }

}