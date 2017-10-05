<?php
/**
 * Created by PhpStorm.
 * User: moyo
 * Date: 10/4/17
 * Time: 19:24
 */

require_once './classes/Import.php';
$im = new Import();
//$im->import_users_data();
//$im->import_clients();
$im->import_products();

