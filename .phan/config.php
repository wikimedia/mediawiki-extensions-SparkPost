<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

// needs composer packages not part of mediawiki/vendor
$cfg['directory_list'][] = 'vendor/sparkpost/';
$cfg['directory_list'][] = 'vendor/php-http/';
$cfg['exclude_analysis_directory_list'][] = 'vendor/';

return $cfg;
