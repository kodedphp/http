<?php

use Koded\Http\StreamFactory;
use Koded\Http\UploadedFileFactory;
use Koded\Http\UriFactory;

error_reporting(E_ALL);

define('UPLOADED_FILE_FACTORY', UploadedFileFactory::class);
define('STREAM_FACTORY', StreamFactory::class);
define('URI_FACTORY', UriFactory::class);

require_once __DIR__ . '/../vendor/autoload.php';