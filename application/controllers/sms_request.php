<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sms_request extends CI_Controller {

	//TODO: pull out as separate function
	private function add_phone_from_request()
	{
		$from_phone = str_replace('+','',$_REQUEST['From']);
		$this->db->where('phone', $from_phone);
		$this->db->from('phones');
		if($this->db->count_all_results() === 0):
			//TODO: look up address info w/ whitepages API
			$this->db->set('phone', $from_phone);
			$this->db->set('city', $_REQUEST['FromCity']);
			$this->db->set('state', $_REQUEST['FromState']);
			$this->db->set('zip', $_REQUEST['FromZip']);
			$this->db->set('country', $_REQUEST['FromCountry']);
			$this->db->set('source_id', 1);
			$this->db->set('fetch_ts', time());
			$this->db->insert('phones');//put phone no into db
		endif;
	}

	public function index()
	{
		$this->load->library('transitfone');
		//error_reporting(0);
		$this->load->database();
		if(!$_REQUEST['Body'] || !$_REQUEST['From']):
			#TODO: verify that this is actually twilio
			header("Status: 400 Bad Request");#not from twilio
			exit;
		endif;
		$body = strtoupper(strip_tags(trim($_REQUEST['Body'])));
		$from_phone = str_replace('+','',$_REQUEST['From']);// +12155551234 is set as 12155551234
		if(preg_match('/u (.*) *$/i', $body, $matches)):
			if($matches[1]=='ALL'):
				//count how many rows will be deleted
				$this->db->from('subscriptions_routes');
				$this->db->where('phone', $from_phone);
				$this->db->where('alert_type', 's');
				//$this->db->where('deactivate_ts > '.time().' OR deactivate_ts IS NULL');
				$count = $this->db->count_all_results();
				//disable all SMS alerts for this phone
				//$this->db->set('deactivate_ts', time());
				$this->db->where('phone', $from_phone)->where('alert_type', 's')->where('deactivate_ts > '.time().' OR deactivate_ts IS NULL');
				//$this->db->update('subscriptions_routes');
				$this->db->delete('subscriptions_routes');
				$response_sms = 'Successfully unsubscribed from '.$count.' alert'.($count===1?'':'s').'.';
			else:
				$routes = explode(' ',$matches[1]);
				$routes_unsubscribed = array();
				foreach($routes as $route):
					$this->db->from('subscriptions_routes');
					$this->db->where('phone', $from_phone);
					$this->db->where('alert_type', 's');
					//$this->db->where('deactivate_ts > '.time().' OR deactivate_ts IS NULL');
					$this->db->where('route_short_name', $route);
					$count = $this->db->count_all_results();
					if($count>0):
						//$this->db->set('deactivate_ts', time());
						$this->db->where('phone', $from_phone);
						$this->db->where('alert_type', 's');
						$this->db->where('deactivate_ts > '.time().' OR deactivate_ts IS NULL');
						$this->db->where('route_short_name', $route);
						//$this->db->update('subscriptions_routes');
						$this->db->delete('subscriptions_routes');
						array_push($routes_unsubscribed, $route);
					endif;
				endforeach;
				//TODO: make as many full alerts that fit into one text
				if(count($routes_unsubscribed)>0):
					$response_sms = 'Successfully stopped alerts for route'.(count($routes_unsubscribed)===1?'':'s').' '.implode(', ', $routes_unsubscribed).'.';//TODO: add 'and'
				else:
					$response_sms = 'No subscriptions found for route'.(count($routes)===1?'':'s').' '.implode(', ', $routes).'.';//TODO: add 'and'
				endif;
			endif;
		elseif(preg_match('/s (.*) *$/i', $body, $matches)):
			$this->add_phone_from_request();
			$routes = explode(' ',$matches[1]);//TODO: add a maximum
			$insert_rows = array();//declare array for batch insert
			foreach($routes as $route):
				//TODO: check if route is in here more than once
				//check if an active record exists for this route
				//TODO: make separate function in model
				$this->db->from('subscriptions_routes');
				$this->db->where('phone', $from_phone);
				$this->db->where('alert_type', 's');
				$this->db->where('agency_id', (string) 1);
				//$this->db->where('deactivate_ts > '.time().' OR deactivate_ts IS NULL');
				$this->db->where('route_short_name', $route);
				$count = $this->db->count_all_results();
				if($count===0)://if an active record is not there already
					$insert_row = '';
					$insert_row['alert_type'] = 's';
					$insert_row['phone'] = $from_phone;
					$insert_row['agency_id'] = (string) 1;
					$insert_row['route_short_name'] = $route;
					$insert_row['activate_ts'] = time();
					array_push($insert_rows, $insert_row);
				endif;
			endforeach;
			//batch insert
			if(count($insert_rows)>0):
				$this->db->insert_batch('subscriptions_routes', $insert_rows);
				$response_sms = 'Subscribed to Rt'.(count($routes)===1?'':'s').' '.implode(', ', $routes);
				//send alerts
				foreach($routes as $route_short_name):
					$response_sms .= $this->transitfone->get_route_alert_text($route_short_name); #append our response SMS
				endforeach;
			else:
				$response_sms = 'Already subscribed to Rt'.(count($routes)===1?'':'s').'. '.implode(', ', $routes);
			endif;
		else:
			$response_sms = 'I didn\'t understand that.';
		endif;
		
		# make XML SMS tags
		$data['response'] = $this->transitfone->get_sms_xml($response_sms);

		$this->load->view('twilio_response', $data);
	}
	
}
?>