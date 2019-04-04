<!-- Account Area -->
<section class="col-sm-7 middle-column">

    <!-- Top row: Account Info -->
    <section class="row account-info">

        <!-- This div made visible by JavaScript if pond has not been stocked in X number of hours -->
        <div ID="stock-pond-warning" class="alert alert-danger">
            <b>WARNING!</b> The pond hasn't been stocked since <?php echo  date('l, F jS \a\t g:i a', $lastStocked)?>. This information is out of date. You must stock the pond or risk annoying our customers with calls after they've paid!
        </div>

        <!-- Account info: Left column, name, phone, etc -->
        <section class="col-sm-8 account-left">

            <div class="account-detail">
                <h1><?php echo ($accountQuery[0]->crnt_hmphone != "(   )    -") ? $accountQuery[0]->crnt_hmphone : "NO PHONE"; ?></h1>
                <p>    
                <h3 id="crnt_name"><?php echo htmlspecialchars($accountQuery[0]->crnt_name)?> </h3>                    
                    <strong><?php echo $accountQuery[0]->crnt_acct . $addItUp->sub;?></strong> <?php echo $addItUp->student;?><br>
                    <?php echo ($accountQuery[0]->crnt_email != "") ? htmlspecialchars($accountQuery[0]->crnt_email) : "NO EMAIL"; ?>
                </p>
                
            </div>

            <!--Sticky notes-->
            <div class="sticky-notes-area">
                <?php if ($accountSticky) { 
                    foreach ($accountSticky as $sticky) { ?>  
                        <div class="sticky-note">
                            <!-- Delete note via JS/Ajax -->
                            <div class="sticky-delete">
                                <a href="home/deleteSticky/<?php echo $sticky->sticky_ID?>">&times</a>
                            </div>
                            <!-- Note content -->
                            <p>
                                <?php echo htmlspecialchars($sticky->sticky_text, ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <!-- Employee, date --> 
                            <div class="sticky-note-info">
                                <?php echo date("m-d-y", $sticky->sticky_date) . " | " . 
                                           htmlspecialchars($sticky->sticky_lanid, 
                                           ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
            <?php   }
                } 
            ?>
            </div>
            
        </section>

        <!-- Account info: Right Column. Balance and payment info. -->
        <section class="col-sm-4 account-right">
            <h1>$<?php echo number_format($addItUp->payment, 2)?></h1>
            due <?php echo $accountQuery[0]->crnt_ndate; ?><br>
            <?php echo $accountQuery[0]->crnt_extra1;?> days late<br>
            <br>
            Last Paid: $<?php echo $accountQuery[0]->crnt_lastamt;?><br>
            on <?php echo $accountQuery[0]->crnt_lastdate;?><br>
            <div class="autopay-alert">
                <?php if ($accountQuery[0]->crnt_rntdesc == "Rental (Autopay)") {
                echo "AUTOPAY";
                }?>
            </div>                
        </section>

    </section>
    <!-- End Account Info row -->

    <!-- Contact Action Input Area--> 
    
    <form action="<?php echo URL; ?>" method="POST">

    <!-- Action Row --->
    <section class="row action">
        
        <!--Action Row Left Column: Employee selector-->
        <section class="col-sm-4">
            <select class="form-control form-control-sm" name="ctac_lanid" required>

                <?php
                // If $ctac_lanid not set, ask to set employee
                if ($session->lanid == "") echo "<option value=\"\">Select Employee</option>"; 

                // Get list of store employees from data.json and populate options
                foreach ($storeData->employees as $caller) {
                    echo "<option value=\"$caller\"";
                    // If ctac_lanid is set, make it selected
                    if ($session->lanid == $caller) echo " selected=\"selected\"";
                    echo ">$caller</option>";
                }
                ?>
            </select>
        </section>

        <!-- Action Row Middle Column: Action selector -->
        <section class="col-sm-4">
            <select class="form-control form-control-sm" name="ctac_action" id="ctac_action" autofocus>
                <option value="CC Declined">CC Declined</option>
                <?php 
                // If no email, no email option.
                if ($accountQuery[0]->crnt_email != "") { ?>
                    <option value="Emailed">Emailed</option> <!-- Add script-->
                <?php } ?>
                <!-- Leaving the phone options in case there is alternate phone info in sticky note -->
                <option value="Left VM" selected>Left VM</option>
                <option value="LM with ...">LM with ...</option>
                <option value="No Answer / No VM">No Answer / No VM</option>
                <option value="Other">Other</option>
                <option value="Paid">Paid</option>
                <option value="Promised-Payment">Promised Payment</option>
                <option value="Sticky Note">Sticky Note</option> 
            </select>

        </section>

        <!-- Action Row Right Column: Additioanl Action Dropdowns, all hidden until revealed by action selector -->
        <section class="col-sm-4">

            <!--Emailed-->
            <select class="form-control form-control-sm action-options" id="Emailed">
                <option>Choose Template</option>
                <option value="email-blank">Blank</option>
                <option value="email-past-due">Past Due</option>
                <option value="email-severely-past-due">Severely Past Due</option>
                <option value="email-card-declined">Card Declined</option>
                <option value="email-send-statement">Send Statement</option>
            </select>

            <!--Paid-->
            <select class="form-control form-control-sm action-options" id="Paid">
                <option>Payment Method</option>
                <option value="paid">Customer Paid</option>
                <option value="card-on-file">Ran Card</option>
            </select>

            <!--Promised Payment-->
            <input class="form-control form-control-sm action-calendar action-options" type="date" id="Promised-Payment">

        </section>
    
    </section>

    <!--Contact Note text area row-->
    <section class="row">

        <section class="col-sm-12">
            <textarea name="ctac_note" placeholder="Notes." ID="ctac_note" class="form-control"></textarea>
        </section>        
    
    </section>

    <!-- Action Save Row --->
    <section class="row action-save">
        
        <section class="col-sm-6 action-checkboxes">
            <div class="form-check form-check-inline">
                <input name="crnt_extra2" class="form-check-input" type="checkbox" value="N">
                <label class="form-check-label" for="inlineCheckbox1">Keep account open.</label>
            </div>
        </section>

        <!--Save Button-->
        <section class="col-sm-6 contact-save">
            <!-- Automatic variables -->
            <input type="hidden" name="ctac_acct" value="<?php echo $accountQuery[0]->crnt_acct?>">
            <input type="hidden" name="ctac_email" value="<?php echo ($accountQuery[0]->crnt_email != '') ? htmlspecialchars($accountQuery[0]->crnt_email) : "NO EMAIL"; ?>">
            <input type="hidden" name="ctac_name" value="<?php echo $accountQuery[0]->crnt_name?>">
            <input type="hidden" name="ctac_daysLate" value="<?php echo $accountQuery[0]->crnt_extra1?>">
            <input type="hidden" name="ctac_date" value="<?php echo time();?>">
            
            <!-- Skip this account -->
            <input type="submit" name="contact" value="SKIP" class="btn btn-danger btn-sm">
            <!-- Save action -->
            <input type="submit" name="contact" value="SAVE" class="btn btn-success btn-sm">
        </section>

    </section>
    <!-- End Contact Action Input Area.-->
    </form>

</section>
<!-- End account area (middle column) -->
