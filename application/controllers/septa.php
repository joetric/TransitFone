<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Septa extends CI_Controller {
	public function perks($spp_id=-1)
	{
		$this->load->database();
		if($spp_id>0) {
			// give full details for one perk
			$this->db->where('spp_id', $spp_id);
		} else {
			// only give id, timestamp, title, business, lat, and long
			$this->db->select('spp_id, spp_title, spp_org, spp_lat, spp_lon');
		}
		$perks = array(
			#'info' => 'ESRI requires attribution if you are using coordinates. Geocoder metadata is available at http://www.arcgis.com/home/item.html?id=919dd045918c42458f30d2c85d566d68.',
			'perks' => $this->db->get('septa_pass_perks')->result()
		);
		header('Content-type: application/json');
		echo json_encode($perks);
	}
	
	/* Get latest detours for a given route */
	public function route($route_short_name, $type='detours')
	{	
		$this->load->database();
		$format = 'table';
		if(isset($_GET['format'])) $format=$_GET['format'];
		
		switch($type)
		{
			case 'detours':
				
				$route_short_name = strtoupper($route_short_name);
				$this->db->select('route_long_name');
				$this->db->where('route_short_name', $route_short_name);
				$this->db->where('agency_id', '1');
				$result = $this->db->get('gtf_routes', 1)->result();
				if(count($result)>0) {
					$route_long_name = $result[0]->route_long_name;
				} else {
					$route_long_name = 'Route info not found';
				}
				$this->db->select('route_short_name, route_direction, detour_start_ts, detour_end_ts,  detour_reason, detour_start_loc, detour_message, is_active');
				$this->db->where('route_short_name', $route_short_name);
				$this->db->order_by('is_active desc, detour_id desc');
				$query = $this->db->get('detours', 50); 
				$detours = array(
					'detours' => $query->result() 
				);
				if($format=='json')
				{
					// begin output
					header('Content-type: application/json');
					echo json_encode($detours/*, JSON_PRETTY_PRINT*/); // new param only works in PHP 5.4+
				}
				elseif($format=='xml')
				{/*
					header('Content-type: application/xml');
					$xml = new SimpleXMLElement('<detours/>');
					array_walk_recursive($detours, array($xml, 'addChild'));
					echo $xml->asXML();
					*/
				}
				else
				{
					$content= '
					<h1>SEPTA Route '.$route_short_name.' Detours</h1>
					<h2>'.$route_long_name.'</h2>
					<div style="width:100%;height:20px;"></div>
					<table style="width:100%;">
					<tr>
						<th>Rt.</th>
						<th>Dir.</th>
						<th>From</th>
						<th>Until</th>
						<th>Due to</th>
						<th>Starting at</th>
						<th>New Route</th>
						<th>Status</th>
					</tr>';
					foreach($detours['detours'] as $detour)
					{
						$date_format = 'm/d/y h:ia';
						$detour->detour_start_ts = substr(date($date_format, $detour->detour_start_ts),0,-1);
						$detour->detour_end_ts = substr(date($date_format, $detour->detour_end_ts),0,-1);
						$row_color = $detour->is_active=='t'?'#ff9':'#fff';
						$detour->is_active = $detour->is_active=='t'?'<b>Active</b>':'Expired';
						$content.= '<tr style="background-color:'.$row_color.';">';
						foreach($detour as $value)
						{
							$content.= '<td>'.$value.'</td>';
						}
						$content.= '</tr>';
					}
					$content.= '</table>';
					$data['content'] = $content;
					$data['title'] = 'Route '.$route_short_name.' Detours ['.$route_long_name.']';
					$this->load->view('template', $data);
				}
				break;
				
			default:
				echo 'Request not understood.';
		}
	}
	
}