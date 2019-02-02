<!--Account List -->
<section class="col-sm-2 left-column">
    <!-- <div class="col-header">
        <?php //echo $listHeader; ?>
    </div> -->

    <ul class="account-list">
        <?php foreach ($accountQuery as $account) { ?>
                <li class="account-list-item">
                    <a href="<?php echo URL; ?>?lookup=<?php echo $account->crnt_acct; ?>"><?php echo $account->crnt_acct; ?></a>
                    <span class="account-list-bal-due">$<?php echo number_format($account->crnt_paymentdue, 2); ?></span>
            </li>
        <?php } ?>
    </ul>
</section>