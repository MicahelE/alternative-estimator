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
        return intdiv($data["timeToElapse"], 3)*7;
    break;
    case "months":
        return intdiv($data["timeToElapse"], 3)*30;
    break;
}
}

function covid19ImpactEstimator($data)
{
  $impact["currentlyInfected"]=$data["reportedCases"]*10;

  $severeImpact["currentlyInfected"]=$data["reportedCases"]*50;

  $impact["infectionsByRequestedTime"]=$impact["currentlyInfected"] * (pow(2,periodType($data)));
  ;
  $severeImpact["infectionsByRequestedTime"]=$severeImpact["currentlyInfected"]*pow(2,periodType($data));

  $impact["severeCasesByRequestedTime"]=$impact["infectionsByRequestedTime"]*0.15;

  $severeImpact["severeCasesByRequestedTime"]=$severeImpact["infectionsByRequestedTime"]*0.15;
  $impact["hospitalBedsByRequestedTime"]=intval(($data["totalHospitalBeds"]*0.35)-$impact["severeCasesByRequestedTime"]);
  $severeImpact["hospitalBedsByRequestedTime"]=intval(($data["totalHospitalBeds"]*0.35)-$severeImpact["severeCasesByRequestedTime"]);
  $impact["casesForICUByRequestedTime"]=$impact["infectionsByRequestedTime"]*0.05;
  $severeImpact["casesForICUByRequestedTime"]=$impact["infectionsByRequestedTime"]*0.05;
  $impact["casesForVentilatorsByRequestedTime"]=intval($impact["infectionsByRequestedTime"]*0.02);
  $severeImpact["casesForVentilatorsByRequestedTime"]=intval($impact["infectionsByRequestedTime"]*0.02);

  $impact["dollarsInFlight"]=$impact["infectionsByRequestedTime"]*0.65*1.5*30;
  $severeImpact["dollarsInFlight"]=($severeImpact["infectionsByRequestedTime"]*0.65*1.5*30);

  $output= array("data"=>$data,"impact"=>$impact,"severeImpact"=>$severeImpact);
 
  


  // die(var_dump($output));
  return $output;
 

}

covid19ImpactEstimator($data);

?>