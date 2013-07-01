<?php

//6 hr ttl
$definitions = array(
    'ilp_miscache' => array(
       'mode' => cache_store::MODE_APPLICATION,
       'persistent'=>true,
       'ttl'=>21600
    )
);