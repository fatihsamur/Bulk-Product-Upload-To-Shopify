<?php

require __DIR__ . '/vendor/autoload.php';

use Shopify\Clients\Graphql;


use Shopify\Context;
use Shopify\Auth\FileSessionStorage;
use Shopify\Clients\Graphql;


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

Context::initialize(
  $_ENV['SHOPIFY_API_KEY'],
  $_ENV['SHOPIFY_API_SECRET'],
  $_ENV['SHOPIFY_APP_SCOPES'],
  $_ENV['SHOPIFY_APP_HOST_NAME'],
  new FileSessionStorage('C:/Users/Fatih/Desktop/halukrugs/vendor/shopify/shopify-api/src/Auth/tmp/shopify_api_sessions'),
  '2021-10',
  true,
  false,
);
