<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Import extends CI_Controller {
	private function get_page_contents($url)
	{
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$contents = curl_exec($ch);
		curl_close($ch);
		return $contents;
	}
	
	private function get_perk_ids($contents)
	{
		preg_match_all("/<a href='.+id=([^']*)'.*>.*View Details.*<\/a>/", $contents, $matches);
		return $matches[1];		 
	}
	
	public function septa_perks()
	{
		$this->load->database();
		$start_time = time();
		// get contents of main page
		$contents = $this->get_page_contents('http://www.septapassperks.org/results.php');
		preg_match("/(\d+) Perks Found/", $contents, $matches);
		$pages = ceil($matches[1]/10); //pgs is ceil of num of perks div by 10
		// page 1 is the same as the main page - get detail links and push to array
		$perk_ids = $this->get_perk_ids($contents);
		// get links for the rest of the pages
		if($pages>1)
		{
			for($i=2; $i<=$pages; $i++)
			{
				//curl each page to get links
				$contents = $this->get_page_contents('http://www.septapassperks.org/results.php?page='.$i);
				$more_perk_ids = $this->get_perk_ids($contents);
				$perk_ids = array_merge($perk_ids, $more_perk_ids);
			}
		}
		$perks = array();
		foreach($perk_ids as $perk_id) 
		{
			$perk['spp_id'] = $perk_id; // put ID into our array
			$perk['spp_scrape_ts'] = time();
			// get contents of detail page
			$contents = $this->get_page_contents('http://www.septapassperks.org/perkview.php?id='.$perk_id);
			
			// get title
			preg_match('/<div.*bluehead[^>]*>(.+)<\/div>/', $contents, $matches);
			$perk['spp_title'] = $matches?utf8_encode(trim($matches[1])):'';
			
			// get subtitle (business name)
			preg_match('/<.*orangesubhead[^>]*>(.+)<\/.+>/', $contents, $matches);
			$perk['spp_org'] = $matches?utf8_encode(trim($matches[1])):'';

			// description
			preg_match('/Description:.*bodycopy">.*<div style=\'padding-right:10px;\'>(.*)<\/div>.*bodycopybold.*Offer:/s', $contents, $matches);
			$perk['spp_desc'] = $matches?utf8_encode(trim($matches[1])):'';
			
			// offer
			preg_match('/Offer:.*bodycopy">.*<div style=\'padding-right:10px;\'>(.*)<\/div>.*bodycopybold.*Location:/s', $contents, $matches);	
			$perk['spp_offer'] = $matches?utf8_encode(trim($matches[1])):'';
			
			// location
			preg_match('/Location:<\/span><span class="bodycopy">(.*)<\/span>/sU', $contents, $matches);
			$perk['spp_location'] = $matches?utf8_encode(trim($matches[1])):'';
			
			// address ( state code + space + ZIP code or ZIP+4 code )
			preg_match('/.*[A-Z]{2}\s+\d{5}(-\d{4})?/', strip_tags(str_replace('<br>',' ',$perk['spp_location'])), $matches);
			$perk['spp_address'] = $matches?utf8_encode(trim($matches[0])):'';
			
			// geocode address
			$gc_response = $this->get_page_contents('http://tasks.arcgisonline.com/ArcGIS/rest/services/Locators/TA_Address_NA_10/GeocodeServer/findAddressCandidates?SingleLine='.urlencode($perk['spp_address']).'&f=pjson');
			$gc_array = json_decode($gc_response);
			if(count($gc_array->candidates)>0) {
				$perk['spp_lon'] = $gc_array->candidates[0]->location->x;
				$perk['spp_lat'] = $gc_array->candidates[0]->location->y;
				$perk['spp_geocode_service'] = 'ESRI';
				$perk['spp_geocode_precision'] = (int)$gc_array->candidates[0]->score;
			}
			echo 'Added '.$perk['spp_title'].' at '.$perk['spp_org'].'<hr/>';
			$perks[] = $perk; //add perk to insert array
		}
		
		// add perks to the database 
		$this->db->insert_batch('septa_pass_perks', $perks);
		$time = time()-$start_time;
		echo 'Perks added in ' . $time . ' seconds.';
	}
	
	public function septa_detours()
	{
		$this->load->library('twilio');#need this to send texts
		$this->load->library('transitfone');
		$this->load->database();
		/*
		$replacements['en'] = array('Northbound', 'Southbound', 'Eastbound', 'Westbound');
		$replacements['es'] = array('que va hacia el norte', 'que va hacia el sur', 'que va hacia el este', 'que va hacia el oueste');
		$replacements['fr'] = array('vers le nord', 'vers le sud', 'vers l\'est', 'vers l\'ouest');
		$insert_row['route_direction_verbal_en'] = preg_replace($patterns, $replacements['en'], $sp[1]);
		$insert_row['route_direction_verbal_es'] = preg_replace($patterns, $replacements['es'], $sp[1]);
		$insert_row['route_direction_verbal_fr'] = preg_replace($patterns, $replacements['fr'], $sp[1]);
		*/
		$ch = curl_init();
		$timeout = 5; // set to zero for no timeout
		curl_setopt ($ch, CURLOPT_URL, 'http://www2.septa.org/rss/detours/index.xml');
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$file_contents = curl_exec($ch);
		curl_close($ch);
		$xml = simplexml_load_string($file_contents); 
		$insert_rows = array();
		$still_active_detour_ids = array();
		foreach($xml->channel->item as $detour):
			$route_short_name = str_replace('Route ','',html_entity_decode(strip_tags($detour->title)));
			$description = html_entity_decode(strip_tags($detour->description));
			$route_directions = preg_split('/Route: /', $description);
			foreach($route_directions as $k => $v):
				if($k > 0):
					$insert_row = '';
					$insert_row['agency_id'] = 1;
					$insert_row['route_short_name'] = $route_short_name;
					# parse direction
					preg_match('/.* - (.*)/', $v, $sp);
					$insert_row['route_direction'] = $sp[1];
					# $patterns = array('/NB/', '/SB/', '/EB/', '/WB/');
					# parse start date, convert to ts
					preg_match('/Start Date: (.*)/', $v, $sp);
					$insert_row['detour_start_ts'] = strtotime($sp[1]);
					# parse end date, convert to ts
					preg_match('/End Date: (.*)/', $v, $sp);
					$insert_row['detour_end_ts'] = strtotime($sp[1]);
					# parse start location
					preg_match('/Start Location: (.*)/', $v, $sp);
					$insert_row['detour_start_loc'] = $sp[1];
					# parse reason
					preg_match('/Reason: (.*)/', $v, $sp);
					$insert_row['detour_reason'] = $sp[1];
					# parse detour message
					preg_match('/Detour Message: (.*)/s', $v, $sp);
					$insert_row['detour_message'] = $sp[1];
					
					$insert_row['is_active'] = 'true';
					//if it does not already exist
					$this->db->select('detour_id');
					$this->db->where('agency_id', $insert_row['agency_id']);
					$this->db->where('route_short_name', $insert_row['route_short_name']);
					$this->db->where('route_direction', $insert_row['route_direction']);
					$this->db->where('detour_start_ts', (string) $insert_row['detour_start_ts']);
					$this->db->where('detour_end_ts', (string) $insert_row['detour_end_ts']);
					$this->db->where('detour_start_loc', $insert_row['detour_start_loc']);
					$this->db->where('detour_reason', $insert_row['detour_reason']);
					$this->db->where('detour_message', $insert_row['detour_message']);
					$result = $this->db->get('detours')->result();
					if(count($result) === 0): //detour does not already exist
						$insert_row['is_new'] = 'true' ;
						array_push($insert_rows, $insert_row);
					else://detour exists with all the same information
						array_push($still_active_detour_ids, $result[0]->detour_id);
					endif;
				endif;
			endforeach;//route direciton
		endforeach;//route
		$new_detours = count($insert_rows);
		/*
		if($new_detours > 0):
			$this->db->insert_batch('detours', $insert_rows);
		endif;
		*/
		$this->db->trans_start();
		$this->db->update('detours', array('is_active' => 'false'));#set all active to zero
		foreach($still_active_detour_ids as $still_active_detour_id):
			$this->db->where('detour_id', $still_active_detour_id);
			$this->db->update('detours', array('is_active' => 'true'));
		endforeach;
		if($new_detours > 0):
			$this->db->insert_batch('detours', $insert_rows);
			echo 'Loaded '.$new_detours.' new detour(s) to db, '.date('F j Y h:i:sa');
		else:
			echo 'No detours added to db ('.count($still_active_detour_ids).' remain active), '.date('F j Y h:i:s a');
		endif;
		$this->db->trans_complete();#let people use the table again
		
		#send alerts for new ones
		$this->db->where('is_new', 'true');
		$this->db->where('is_active', 'true');
		$new_detours = $this->db->get('detours')->result();
		$sms_calls_success = 0;
		$sms_calls_errors = 0;
		foreach($new_detours as $new_detour):
			$this->db->select('alert_type, phone');
			$this->db->where('agency_id', $new_detour->agency_id);#match agency
			$this->db->where('route_short_name', $new_detour->route_short_name);#match route
			$this->db->where('(deactivate_ts > '.time().' OR deactivate_ts IS NULL)');#make sure subscription is still active
			$subscriptions = $this->db->get('subscriptions_routes')->result();
			#if there are any subscriptions for this route, get the SMS text to use
			if(count($subscriptions)>0):
				$sms_detour = $this->transitfone->get_detour_text($new_detour);
			endif;
			#TODO: split sms and voice
			#TODO: combine SMS texts
			foreach($subscriptions as $subscription):
				switch($subscription->alert_type):
					case 's':
						# send a text to phone
						$response = $this->twilio->sms($this->twilio->number, $subscription->phone, $sms_detour);
						if($response->IsError):
							$sms_calls_errors++;
							error_log('Could not send alert text to '.$subscription->phone.': '.$response->ErrorMessage);
						else:
							$sms_calls_success++;
						endif;
						break;
					default:
						die('Not a valid alert type');
				endswitch;
			endforeach;	
		endforeach;
		echo "\n".$sms_calls_success.' SMS calls made successfully. '.$sms_calls_errors.' SMS calls resulted in errors.';
		
		#make the new ones old # update new to false where new is true
		$this->db->update('detours', array('is_new'=>'false'), array('is_new'=>'true')); #table, data, where
		
		#TODO:archive the inactive ones
	}
}

class route
{
	public function verbalize($lang='en'){
	}
}
class detour
{
	public function verbalize($lang='en'){
		switch($lang):
			case 'es':
				return 'El HXH, que va hacia el sur, se desvía. Solo viajes rapidos. Hacia el sur en manheim, a la izquierda en Wayne, a la derecha en Roberts, a la izquierda en Wissahickon, a la izquierda en Hunting Park, y a la derecha en pulaski, para continuar la ruta normal. El desvia esta en vigor hasta el 23 de agosto, 2012, debido a construccion de puentes.';
			default:
				return 'The southbound HXH is detoured. Express trips only. Southbound via manheim, left on wayne, right on roberts, left on wissahickon, left on hunting park, right on pulaski to continue regular route. Detour is in effect through August 23, 2012 due to Bridge Work.';
		endswitch;
	}
}

?>