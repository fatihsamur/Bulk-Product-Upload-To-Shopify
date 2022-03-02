<?php

require __DIR__ . '/vendor/autoload.php';



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
  new FileSessionStorage('C:/Users/Fatih/Desktop/project/vendor/shopify/shopify-api/src/Auth/tmp/shopify_api_sessions'),
  '2021-10',
  true,
  false,
);



$accessToken = $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN'];
$client = new Graphql("halukrugs.myshopify.com", $accessToken);



$staged_upload_query = '
mutation {
  stagedUploadsCreate(input:{
    resource: BULK_MUTATION_VARIABLES,
    filename: "products.jsonl",
    mimeType: "text/jsonl",
    httpMethod: POST
  }){
    userErrors{
      field,
      message
    },
    stagedTargets{
      url,
      resourceUrl,
      parameters {
        name,
        value
      }
    }
  }
}';


$response = $client->query(['query' => $staged_upload_query], ['verify' => false]);
$graphql_variables = $response->getBody()->getContents();

file_put_contents('graphql_variables.json', $graphql_variables);



$curl_opt_url = json_decode($graphql_variables)->data->stagedUploadsCreate->stagedTargets[0]->url;
$curl_key = json_decode($graphql_variables)->data->stagedUploadsCreate->stagedTargets[0]->parameters[0]->value;
$curl_policy = json_decode($graphql_variables)->data->stagedUploadsCreate->stagedTargets[0]->parameters[4]->value;
$curl_x_amz_credentials = json_decode($graphql_variables)->data->stagedUploadsCreate->stagedTargets[0]->parameters[5]->value;
$curl_x_amz_algorithm = json_decode($graphql_variables)->data->stagedUploadsCreate->stagedTargets[0]->parameters[6]->value;
$curl_x_amz_date = json_decode($graphql_variables)->data->stagedUploadsCreate->stagedTargets[0]->parameters[7]->value;
$curl_x_amz_signature = json_decode($graphql_variables)->data->stagedUploadsCreate->stagedTargets[0]->parameters[8]->value;

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $curl_opt_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
$post = array(
  'key' => $curl_key,
  'x-amz-credential' => $curl_x_amz_credentials,
  'x-amz-algorithm' => $curl_x_amz_algorithm,
  'x-amz-date' => $curl_x_amz_date,
  'x-amz-signature' => $curl_x_amz_signature,
  'policy' => $curl_policy,
  'acl' => 'private',
  'Content-Type' => 'text/jsonl',
  'success_action_status' => '201',
  'file' => new \CURLFile('C:\Users\Fatih\Desktop\halukrugs\fake_products.jsonl')
);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

$result = curl_exec($ch);
if (curl_errno($ch)) {
  echo 'Error:' . curl_error($ch);
}
curl_close($ch);


$arr_result = simplexml_load_string(
  $result,
);


$str_result = (string) $arr_result->Key;


$product_create_query =
  'mutation {
    bulkOperationRunMutation(
    mutation: "mutation call($input: ProductInput!) { productCreate(input: $input) { product {title productType vendor} userErrors { message field } } }",
    stagedUploadPath: "' . (string)$arr_result->Key . '") {
    bulkOperation {
      id
      url
      status
    }
    userErrors {
      message
      field
    }
  }
}';


$pr_cr_resp = $client->query(["query" => $product_create_query]);

$bulk_op_id = json_decode($pr_cr_resp->getBody()->getContents())->data->bulkOperationRunMutation->bulkOperation->id;

function check_bulk_op_status($client)
{
  $op_finish_query = 'query {
 currentBulkOperation(type: MUTATION) {
    id
    status
    errorCode
    createdAt
    completedAt
    objectCount
    fileSize
    url
    partialDataUrl
 }
}';

  $op_finish_resp = $client->query(["query" => $op_finish_query]);
  $str_op_finish_resp = $op_finish_resp->getBody()->getContents();
  $status_op_finish_resp = json_decode($str_op_finish_resp)->data->currentBulkOperation->status;
  return $status_op_finish_resp;
}

$bulk_op_status = check_bulk_op_status($client);

while ($bulk_op_status != 'COMPLETED') {
  $bulk_op_status = check_bulk_op_status($client);
  var_dump('bulk operation status: ' . $bulk_op_status);
  sleep(3);
}