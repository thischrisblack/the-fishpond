<section class="col-sm-12">

    <?php

    if (count($aging) == "0") { ?>
        <br>
        <h2>No more fish!</h2>
        <p>So happy for you.</p>
    <?php } else { ?>
        <br>
        <h2>The <?php echo $session->minmax?> category has no more fish in it.</h2>
        <p>Good job! Try another one.</p>
    <?php } ?>
    
</section>
