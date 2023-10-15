<?php

/**
 * @file
 * Contains \Drupal\freeapi\Form\WeatherForm.
*/

namespace Drupal\freeapi\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class WeatherForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'weather_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form['location'] = [
			'#type' => 'textfield',
			'#title' => 'Location',
			'#required' => TRUE,
			'#default_value' => '',
		];
		$form['forecast_type'] = [
			'#type' => 'select',
			'#title' => 'Forecast Type',
			'#required' => TRUE,
			'#options' => [
				'' => '-- Select --',
				'current' => 'Current',
				'minute' => 'Minute',
				'hourly' => 'Hourly',
				'daily' => 'Daily',
				'alerts' => 'Alerts',
			],
			'#default_value' => '',
		];
		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = [
			'#type' => 'submit',
			'#value' => 'Get Results',
			'#button_type' => 'primary',
		];

		$form['search_results'] = [
			'#weight' => 900,
			'#markup' => ''
		];
		if ($form_state->getTriggeringElement()) {
			$location = strtolower($form_state->getValue('location'));
			$forecast_type = $form_state->getValue('forecast_type');

			$weather_query = \Drupal::service('freeapi_service')->GetWeather($location, $forecast_type);
			$summary = $weather_query['current']['summary'];
			$current_temperature = $weather_query['current']['temperature'];
			$feels_like = $weather_query['current']['feels_like'];
			$humidity = $weather_query['current']['humidity'];
			$wind_speed = $weather_query['current']['wind']['speed'];
			$wind_direction = $weather_query['current']['wind']['dir'];
			$form['search_results'] = array(
				'#markup' => '
				<strong>Current Temperature</strong>: '.$current_temperature.' °C<br>
				<strong>Feels Like</strong>: '.$feels_like.' °C<br>
				<strong>Humidity</strong>: '.$humidity.' %<br>
				<strong>Wind Speed</strong>: '.$wind_speed.' km/h<br>
				<strong>Wind Direction</strong>: '.$wind_direction
			);
		}
		return $form;
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$form_state->setRebuild(TRUE);
	}
}