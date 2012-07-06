<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once(APPPATH . "libraries/core/ImpulseController.php");

class Zones extends ImpulseController {

	public function __construct() {
		parent::__construct();
		$this->_setNavHeader("DNS");
		$this->_addScript('/js/dns.js');
	}

	public function index($zone=null) {
		// Decode
		$zone = rawurldecode($zone);

		try {
			// Get the zone info
			$z = $this->api->dns->get->zoneByName($zone);
			$zInfo = $this->load->view('dns/zone/detail',array('zone'=>$z),true);

			// SOA
			try {
				$soaRec = $this->api->dns->get->soa($z->get_zone());
				$soaInfo = $this->load->view('dns/soa/detail',array('soaRec'=>$soaRec),true);
			}
			catch(ObjectNotFoundException $e) { $soaInfo = $this->_error($e); }
			catch(Exception $e) { $this->_exit($e); return; }

			// A/AAAA Records
			try {
				$aRecs = $this->api->dns->get->zoneAddressesByZone($z->get_zone());
				$aRecInfo = $this->_renderDnsTable($aRecs, "Zone A/AAAA");
			}
			catch(ObjectNotFoundException $e) { $aRecInfo = $this->_error($e); }
			catch(Exception $e) { $this->_exit($e); return; }

			// TXT Records
			try {
				$tRecs = $this->api->dns->get->zoneTextsByZone($z->get_zone());
				$tRecInfo = $this->_renderDnsTable($tRecs, "Zone TXT");
			}
			catch(ObjectNotFoundException $e) { $tRecInfo = $this->_error($e); }
			catch(Exception $e) { $this->_exit($e); return; }

			// View Data
			$viewData['zone'] = $z;
			$viewData['zoneInfo'] = $zInfo;
			$viewData['soaInfo'] = $soaInfo;
			$viewData['aRecInfo'] = $aRecInfo;
			$viewData['tRecInfo'] = $tRecInfo;

			// Sidebar
			$this->_addSidebarItem("Zone","#zone");
			$this->_addSidebarItem("SOA","#soa");
			$this->_addSidebarItem("A/AAAA Records","#a");
			$this->_addSidebarItem("TXT Records","#txt");

			// Actions
			$this->_addAction("Modify","#");
			$this->_addAction("Remove","#");

			// Content
			$content = $this->load->view('dns/zone/overview',$viewData,true);
		}	
		catch(Exception $e) { $this->_exit($e); return; }

		// Render
		$this->_render($content);
	}
}
/* End of file zones.php */
/* Location: ./application/controllers/dns/zones.php */