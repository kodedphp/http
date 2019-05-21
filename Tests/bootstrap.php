<?php

use Koded\Http\HttpFactory;

error_reporting(E_ALL);

define('UPLOADED_FILE_FACTORY', HttpFactory::class);
define('STREAM_FACTORY', HttpFactory::class);
define('URI_FACTORY', HttpFactory::class);

require_once __DIR__ . '/../vendor/autoload.php';
