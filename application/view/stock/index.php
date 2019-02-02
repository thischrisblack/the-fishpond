<!-- Narrow empty left col-->
<section class="col-sm-1"></section>

<!-- Main col-->
<section class="col-sm-10 pond-stock">

    <h1>Stock the Pond</h1>

    <p>Let's stock the pond with fresh fish.</p>

    <?php if (BUS_APAY == "Y" ) { ?>

        <section class="pond-stock-section">

            <h4>Run autopays.</h4>

            <p>If your store uses the AIMsi autopay feature, you must run your autopays to get them current before proceeding. If the payments aren't posted before the pond is stocked, you'll wind up calling customers for no reason.</p>

        </section>

    

    <?php } ?>    

    <?php if (BUS_PAYMENT != "" ) { ?>

        <section class="pond-stock-section">

            <h4>Enter online payments.</h4>

            <p>If your store takes payments online, they will appear here. You must enter these payments in AIMsi before proceeding.</p>

        </section>



        <div class="alert alert-success" id="no-more-payments">
            There are no payments to process! You're good to go.
        </div>

        <ul class="payment-list">

            <?php

                foreach($onlinePayments as $payment) {
                    echo "<li class=\"payment-list-item\">Account " . $payment->ctac_acct . 
                                " - " . date('d/m/y, g:i a', $payment->ctac_date) . 
                                " - " . $payment->ctac_note . 
                                "<span class=\"payment-delete\" ID=\"" . 
                                $payment->ID . "\">&times;</span></li>";
                }

            ?>
        
        </ul>

    <?php } ?>  

    <section class="pond-stock-section">

        <h4>Upload the AIMsi data.</h4>

        <p>Copy the data from your AIMsi exported Excel sheet and paste it in the box below. (See <span id="get-aimsi-instructions">instructions</span>)</p>

    </section>
    

    <section class="alert alert-info" ID="aimsi-instructions">

        <h4>Getting the AIMsi data</h4>

        <ol>
            <li>In AIMsi, go to REPORTS > CATALOG > CONTRACTS > CONTRACT STATEMENTS.</li>
            <li>Select the appropriate contract defaults and store locations for export.</li>
            <li>In "Date Range," select the first and last days of the current month. For example, if today is some time in June, we'd go from 06/01/2019 to 06/30/2019.</li>
            <li>Check the box for "Print if Autopay" if your store uses that feature.</li>
            <li>Click <code>OK</code></li>
            <li>In the "Select Output Destination," select "File" and "Excel (XLS)" and then click on the <code>...</code> button. </li>    
        </ol>

        <img src="<?php echo URL?>img/statements-output.PNG">

        <ol start="7">
            <li>Save the Excel file where you can find it.</li>
            <li>Open the Excel file you just created.</li>
            <li>Select any cell and then press CTRL-A to select the entire sheet, and CTRL-C to copy it.</li>
            <li>Come back to this page, put the cursor in the box below, and press CTRL-V to paste the data. It will look weird, but leave it as it is!</li>
            <li>Click "Upload Fishes!"</li>
        </ol>

    
    
    </section>

    <form enctype="multipart/form-data" action="<?php echo URL; ?>stock/pondStock" method="POST">
        <textarea name="fishes" placeholder="Paste Excel data here." ID="fishes-pastebox" class="form-control"></textarea>
        <input class="form-control btn btn-danger" type="submit" value="Upload Fishes" name="stock_the_pond" >
    </form>

    <br>

    <p class="alert alert-danger">
        NOTE: Depending upon how much data you have, this may take a while. Do NOT hit the upload button again. 
    </p>


</section>

<!-- Narrow empty right col-->
<section class="col-sm-1"></section>