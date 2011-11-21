<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Septa extends CI_Controller {

	/* Get latest detours for a given route */
	public function route($route_short_name, $type='detours')
	{
		$format = 'table';
		if(isset($_GET['format'])) $format=$_GET['format'];
		
		switch($type)
		{
			case 'detours':
				$this->load->database();
				$route_short_name = strtoupper($route_short_name);
				$this->db->select('route_long_name');
				$this->db->where('route_short_name', $route_short_name);
				$this->db->where('agency_id', '1');
				$result = $this->db->get('gtf_routes', 1)->result();
				if(count($result)>0) {
					$route_long_name = $result[0]->route_long_name;
					
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
						<table>
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
						$this->load->view('template', $data);
					}
				}
				else
				{
					if($format=='json')
					{
						header('Content-type: application/json');
						echo json_encode(
							array(
								'detours' => array() ,
								'info' => array('Route not found.')
							)/*, JSON_PRETTY_PRINT*/); // new param only works in PHP 5.4+
					}
					else
					{
						$content= '<h1>SEPTA Route '.$route_short_name.' not found</h1>';
						$data['content'] = $content;
						$this->load->view('template', $data);
					}
				}
				break;
				
			default:
				echo 'Request not understood.';
		}
	}
	
}