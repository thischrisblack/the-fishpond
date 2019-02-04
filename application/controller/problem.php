<?php

/**
 * Class Problem
 * Formerly named "Error", but as PHP 7 does not allow Error as class name anymore (as there's a Error class in the
 * PHP core itself) it's now called "Problem" *
 */
class Problem extends Controller
{
    /**
     * PAGE: index
     * This method handles the error page that will be shown when a page is not found
     */
    public function index()
    {

        // Set body class for javascript
        $bodyID="";

        // Get aging totals object for navbar.
        $aging = $this->model->allAging();
    
        // load views
        require APP . 'view/_templates/header.php';
        require APP . 'view/problem/index.php';
        require APP . 'view/_templates/footer.php';
    }
}
