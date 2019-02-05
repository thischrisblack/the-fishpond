
<!-- History Area, contains previous contact history for current account -->
<section class="col-sm-3 right-column">

    <div class="col-header">
        HISTORY
    </div>

    <?php 
    
    // If no history results
    if (sizeof($accountHistory) == 0) {

        echo "<div class=\"history-action\">This account has no previous contact history.</div>";

    } else {
    
        foreach ($accountHistory as $contact) { ?>

            <div class="history-item" ID="<?php echo $contact->ID; ?>"> 
                <div class="history-action">
                    <?php if (isset($contact->ctac_action)) echo htmlspecialchars($contact->ctac_action, ENT_QUOTES, 'UTF-8'); ?>
                    <span class="history-date">
                        <?php if (isset($contact->ctac_date)) echo date("m-d-y", $contact->ctac_date); ?>
                    </span>
                </div>
                <!--This should be display-none at first, toggle on click -->
                <div class="history-note">
                    <?php
                    // Check if there is a contact note
                    if ($contact->ctac_note != "") {
                        // If it's an email, truncate it so it won't take up the whole column
                        if ($contact->ctac_action == "Emailed" || $contact->ctac_action == "Auto-emailed") {
                            $contact->ctac_note = substr($contact->ctac_note, 0, 100) . " ...";
                        }
                        echo htmlspecialchars(stripslashes($contact->ctac_note), ENT_QUOTES, 'UTF-8') . "<br>";
                    } 
                    ?> 
                    - <?php echo htmlspecialchars($contact->ctac_lanid, ENT_QUOTES, 'UTF-8'); ?>
                </div>  
            </div>

    <?php }
    } ?>

    <!-- Session variable (aging and autopay filters) data for JavaScript to update nav with 'active' classes -->
    <data ID="filters" value="<?php echo $session->minmax . "%" . $session->apay;?>"></data>

    <!-- The customer data for JavaScript access -->
    <data ID="customer-data" value='<?php echo $customerData?>'></data>

    <!-- The laststocked timestamp -->
    <data ID="laststocked" value="<?php echo $lastStocked?>"></data>

</section>