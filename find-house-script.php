<?php

    require  __DIR__ . '/vendor/autoload.php';

    $query = $argv[1];

    $search = new Savey\FindYourHouse\FindYourHouse();

    echo $search->findByRegion($query);
