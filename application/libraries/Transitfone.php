<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:   Transitfone
*
* Author: Joe Tricarico
*		  joetric@gmail.com
*         @joetric
*
* Created:  2011-10-21
*
*/

class Transitfone
{
	protected $ci;
	
	function __construct()
	{
		//initialize the CI super-object
		$this->ci =& get_instance(); 
		$this->ci->load->database(); #load DB class from super
	}
	
	/* Returns $date_format string to use based on how close time is to now */
	public function get_relative_date_format($datetime, $compare_to=NULL)
	{
		$compare_to = $compare_to===NULL?time():$compare_to;
		if(date('i', $datetime) === '00'):
			$time_format = 'ga';# 5:00pm -> 5pm
		else:
			$time_format = 'g:ia'; #5:15pm
		endif;
		if(date('n/j/y', $datetime) == date('n/j/y', $compare_to)):#if same day, dont show date
			$date_format = $time_format ;
		else:
			if(date('y', $datetime) == date('y', $compare_to)):
				$date_format = 'n/j ' . $time_format;#same year so don't show
			else:
				$date_format = 'n/j/y ' . $time_format;
			endif;
		endif;
		return $date_format;
	}
	
	public function get_sms_xml($long_message)
	{
		if(strlen($long_message)<=160)
			return '<sms>'.$long_message.'</sms>';
		#keep executing if message is > 160 chars	
		$chunks = explode('||||',wordwrap($long_message,155,'||||',true));
		$total = count($chunks); //See how many chunks there are
		$return_xml = '';
		foreach ($chunks as $page=>$chunk):
			$return_xml .= sprintf("<sms>(%d/%d) %s</sms>\n",$page+1,$total,$chunk);
		endforeach;
		return $return_xml;
	}
	
	public function get_detour_text($detour)
	{
		$detour_times = '';//reset for each iteration
		if($detour->detour_start_ts > time()):
			$detour_times .= 'from '.substr(date("n/j/y g:ia", $detour->detour_end_ts), 0, -1).' to ';#substr turns am to a,
		else:
			$detour_times .= 'until ';
		endif;
		$detour_times .= substr(date($this->get_relative_date_format($detour->detour_end_ts), $detour->detour_end_ts), 0, -1);
		$detour_message_patterns = array('/street/i', '/avenue/i', '/reg(ular)?/i', '/ro?u?te/i', '/L *- */', '/R *- */', '/ *\n+\s*L:/', '/ *\n+\s*R:/', '/ *\n+\s*reg/', '/express/i', '/Park/');
		$detour_message_replacements = array('St', 'Av', 'reg', 'rt', 'L:', 'R:', ', L:', ', R:', ', reg', 'Exp', 'Pk');
		$detour_message = preg_replace($detour_message_patterns, $detour_message_replacements, $detour->detour_message);
		
		$str_format = '>>%s-%s DETOUR %s: %s';
		return sprintf($str_format, $detour->route_short_name, $detour->route_direction, $detour_times, $detour_message);
	}
	
	public function get_route_alert_text($route_short_name)
	{
		#Return detour info
		#TODO: make model
		$this->ci->db->where('agency_id = 1'); #TODO: make dynamic
		$this->ci->db->where('route_short_name', (string) $route_short_name);
		$this->ci->db->where('is_active', 'true');
		$active_detours = $this->ci->db->get('detours')->result();
		
		$response_sms = '';
		foreach ($active_detours as $detour)
		{
			$response_sms .= $this->get_detour_text($detour).' ';
		}
		return $response_sms;
	}
}
?>