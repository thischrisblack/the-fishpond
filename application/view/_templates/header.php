<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>The Fishpond</title>
    <!-- BOOTSTRAP Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css?family=IBM+Plex+Mono|Roboto+Mono|Source+Sans+Pro" rel="stylesheet">
    <link href="<?php echo URL; ?>css/style.css" rel="stylesheet">
</head>
<body ID="<?php echo (isset($bodyID)) ? $bodyID : ''?>">

    <div class="container">

        <!-- NAVIGATION-->
        <nav class="row navbar bg-dark">

            <!-- Brand -->
            <a class="navbar-brand" href="<?php echo URL; ?>">
                <img src="<?php echo URL; ?>img/fish-white.svg" width="65" alt="Refresh List">
            </a>

            <!-- Aging -->
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?minmax=1-180" ID="1-180">ALL<div class="aging-total"><?php echo $aging->all;?></div></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?minmax=1-10" ID="1-10">1-10<div class="aging-total"><?php echo $aging->one;?></div></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?minmax=11-30" ID="11-30">11-30<div class="aging-total"><?php echo $aging->eleven;?></div></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?minmax=31-60" ID="31-60">31-60<div class="aging-total"><?php echo $aging->thirtyOne;?></div></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?minmax=61-90" ID="61-90">61-90<div class="aging-total"><?php echo $aging->sixtyOne;?></div></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?minmax=91-120" ID="91-120">91-120<div class="aging-total"><?php echo $aging->ninetyOne;?></div></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?minmax=121-180" ID="121-180">121-180<div class="aging-total"><?php echo $aging->oneTwentyPlus;?></div></a>
                </li>
            </ul>   

            <!-- Autopay filter-->
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?apay=all" ID="all">ALL</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?apay=Rental+(Monthly)" ID="Rental (Monthly)">APAYS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URL; ?>?apay=Rental+(Autopay)" ID="Rental (Autopay)">NO APAYS</a>
                </li>
            </ul> 

            <!-- SEARCH -->            
            <form class="form-inline" action="<?php echo URL; ?>" method="get">
                <input class="form-control" type="search" placeholder="Search" aria-label="Search" name="lookup">
            </form>

            <span class="nav navbar-text">
              <a href="<?php echo URL; ?>stock" class="nav-stock-link">
                <img src="<?php echo URL; ?>img/stock-white.svg" height="30" alt="Refresh List" ID='stock-link-img'>
              </a>
            </span>

        </nav>
        
    <!-- MAIN -->
    <section class="row">
