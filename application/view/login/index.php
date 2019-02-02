<!-- Narrow empty left col-->
<section class="col-sm-2"></section>

<!-- Main col -->
<section class="col-sm-8" ID="login-column">

    <h1>YOU MUST LOG IN</h1>

    <p>Please log in with the Fishpond password.</p>

    <?php if (isset($wrongPassword)) { ?>

    <div class="alert alert-danger">
        Incorrect password. Please try again. The password may have changed. Please check with your Fishpond admin.
    </div>

    <?php } ?>

    <form action="<?php echo URL; ?>login" method="POST">

        <input type="password" name="password" class="form-control" id="exampleInputPassword1" placeholder="Password" autofocus>

    </form>

</section>

<!-- Narrow empty right col-->
<section class="col-sm-2"></section>