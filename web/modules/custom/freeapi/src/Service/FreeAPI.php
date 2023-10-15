<?php

namespace Drupal\freeapi\Service;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;

class FreeAPI {
	function GetWeather($location, $forecast_type) {
        $config = \Drupal::config('apiconfig.settings');
        $api_host = $config->get('apiconfig.api_host');
        $api_key = $config->get('apiconfig.api_key');

        if($forecast_type == 'current') {
            $curl_url = "https://ai-weather-by-meteosource.p.rapidapi.com/current?place_id=".$location."&timezone=auto&language=en&units=metric";
        }
        if($forecast_type == 'minute') {
            $curl_url = "https://ai-weather-by-meteosource.p.rapidapi.com/minutely?place_id=".$location."&timezone=auto&language=en&units=metric";
        }
        if($forecast_type == 'hourly') {
            $curl_url = "https://ai-weather-by-meteosource.p.rapidapi.com/hourly?place_id=".$location."&timezone=auto&language=en&units=metric";
        }
        if($forecast_type == 'daily') {
            $curl_url = "https://ai-weather-by-meteosource.p.rapidapi.com/daily?place_id=".$location."&timezone=auto&language=en&units=metric";
        }
        if($forecast_type == 'alerts') {
            $curl_url = "https://ai-weather-by-meteosource.p.rapidapi.com/alerts?place_id=".$location."&timezone=auto&language=en&units=metric";
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $curl_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "X-RapidAPI-Host: ".$api_host,
                "X-RapidAPI-Key: ".$api_key
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return "cURL Error #: ".$err;
        } else {
           return Json::decode($response, true);
        }
	}
}