<!-- Narrow empty left col-->
<section class="col-sm-1"></section>

<!--Main col-->
<section class="col-sm-10 pond-stock">

    <h1>Pond Mailer</h1>

    <p>These emails will go out when you press the button. The accounts will be removed from the call list until the pond is stocked again.</p>

    <?php

    foreach ($emailBatches as $batch) { ?>

    <div class="mass-mailer-batch">

        
        <h3><?php echo $batch->daysLate . " days: \"" . $batch->latenessCategory . "\""?></h3>

        <p><?php echo $batch->numAccounts ?> will receive this email.</p>

        <div class="mass-mailer-email">

                <p>Subject: <strong><?php echo $batch->emailSubject?></strong></p>

                <?php echo nl2br($batch->emailBody)?>

        </div>

    </div>

    <?php } ?>



    <form action="<?php echo URL; ?>emailer" method="POST">

        <input type="submit" name="submit" value="EMAIL THEM" class="btn btn-danger btn-sm form-control" ID="emailer-submit">
        
    </form>

    <br>

    <div class="alert alert-danger">
        NOTE: This will take a long time. If this page times out and gives an error, you can go to the home page and start fishing. It should be fine.
    </div>

    <br><br>
    <br><br>
    <br><br>
    <br><br>

</section>

<!-- Narrow empty right col-->
<section class="col-sm-1"></section>