<?php



$data= array( "region"=> array("name"=>"Africa", "avgAge"=>19.7, "avgDailyIncomeInUSD"=>5, "avgDailyIncomePopulation"=>0.71),"periodType"=> "days",
"timeToElapse"=> 58,
"reportedCases"=> 674,
"population"=> 66622705,
"totalHospitalBeds"=> 1380614);



function periodType($data)
{
  switch ($data["periodType"]) {
    case "days":
        return intdiv($data["timeToElapse"],3);
    break;
    case "weeks":
        return intdiv($data["timeToElapse"]*7, 3);
    break;
    case "months":
        return intdiv($data["timeToElapse"]*30, 3);
    break;
}
}

function periodDay($data)
{
  switch ($data["periodType"]) {
    case "days":
        return $data["timeToElapse"];
    break;
    case "weeks":
        return $data["timeToElapse"]*7;
    break;
    case "months":
        return $data["timeToElapse"]*30;
    break;
}
}

function covid19ImpactEstimator($data)
{

  $factor1=10;

$factor2=50;
  $impact["currentlyInfected"]=$data["reportedCases"]*$factor1;

  $severeImpact["currentlyInfected"]=$data["reportedCases"]*$factor2;

  $impact["infectionsByRequestedTime"]=$impact["currentlyInfected"] * (pow(2,periodType($data)));
  
  $severeImpact["infectionsByRequestedTime"]=$severeImpact["currentlyInfected"]*pow(2,periodType($data));

  $impact["severeCasesByRequestedTime"]=$impact["infectionsByRequestedTime"]*0.15;

  $severeImpact["severeCasesByRequestedTime"]=$severeImpact["infectionsByRequestedTime"]*0.15;
  $impact["hospitalBedsByRequestedTime"]=intval(($data["totalHospitalBeds"]*0.35)-$impact["severeCasesByRequestedTime"]);
  $severeImpact["hospitalBedsByRequestedTime"]=intval(($data["totalHospitalBeds"]*0.35)-$severeImpact["severeCasesByRequestedTime"]);
  $impact["casesForICUByRequestedTime"]=$impact["infectionsByRequestedTime"]*0.05;
  $severeImpact["casesForICUByRequestedTime"]=$severeImpact["infectionsByRequestedTime"]*0.05;
  $impact["casesForVentilatorsByRequestedTime"]=intval($impact["infectionsByRequestedTime"]*0.02);
  $severeImpact["casesForVentilatorsByRequestedTime"]=intval($severeImpact["infectionsByRequestedTime"]*0.02);

  $impact["dollarsInFlight"]=intval(($impact["infectionsByRequestedTime"]*$data["region"]["avgDailyIncomePopulation"]*$data["region"]["avgDailyIncomeInUSD"])/periodDay($data));
  $severeImpact["dollarsInFlight"]=intval(($severeImpact["infectionsByRequestedTime"]*$data["region"]["avgDailyIncomePopulation"]*$data["region"]["avgDailyIncomeInUSD"])/periodDay($data));

  $output= array("data"=>$data,"impact"=>$impact,"severeImpact"=>$severeImpact);
 
  


  // die(var_dump($output,periodDay($data)));
   return $output;
 

}

covid19ImpactEstimator($data);

//BACKEND WORK
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri_path 			= array_filter(explode( '/', $uri ));
$content_type 	= end($uri_path);
$requestMethod 	= $_SERVER["REQUEST_METHOD"];


$time_pre_exec = microtime(true);
if ( $content_type == 'xml' ) {

	header("Content-Type: application/xml; charset=UTF-8");
	$input = trim(file_get_contents("PHP://input"));
	$input = json_decode($input, true);

	$estimate = covid19ImpactEstimator($input);
	$xml 			= new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><data/>');
	to_xml($xml, $estimate);
	$result = $xml->asXML();

	header("HTTP/1.1 200 OK");
	echo $result;
	
} elseif ( $content_type == 'logs' ) {

	header("Content-Type: text/plain; charset=UTF-8");
	$file = "logs.txt";
	$logs = file_get_contents($file);

	header("HTTP/1.1 200 OK");
	echo $logs;

} else {

	header("Content-Type: application/json; charset=UTF-8");
	$input = trim(file_get_contents("PHP://input"));
	$input = json_decode($input, true);

	$estimate	= covid19ImpactEstimator($input);
	header("HTTP/1.1 200 OK");
	$response = json_encode($estimate);
	echo $response;
}

logRequest( $requestMethod, $uri, $time_pre_exec );

function to_xml(SimpleXMLElement $object, array $data) {   

  foreach ($data as $key => $value) {
      if (is_array($value)) {
          $new_object = $object->addChild($key);
          to_xml($new_object, $value);
      } else {
          // if the key is an integer, it needs text with it to actually work.
          if ($key == (int) $key) {
              $key = "$key";
          }

          $object->addChild($key, $value);
      }   
  }   
} 

function logRequest( $requestMethod, $uri, $time_pre_exec ) {

	$responseCode = http_response_code(); 
	$time_post_exec = microtime(true);
	$exec_time 			= $time_post_exec - $time_pre_exec;
	$exec_time 			= strtok($exec_time, ".");
	$length 				= strlen($exec_time);

	if ($length < 2) {
		$exec_time = '0' . $exec_time;
	}

	$log_txt = "$requestMethod" . "\t\t" . "$uri" .  "\t\t" . $responseCode . "\t\t" . $exec_time . 'ms';
	file_put_contents( 'logs.txt', $log_txt.PHP_EOL , FILE_APPEND );
}

?>