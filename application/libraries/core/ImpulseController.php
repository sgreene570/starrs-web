<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * The replacement IMPULSE controller class. All controllers should extend from this rather than the builtin
 */
class ImpulseController extends CI_Controller {

	protected static $user;
	private $trail;
	private $sidebarItems;

	private $actions;
	private $navheader;
	private $contentList;

	protected $forminfo;

	private $js = array();

	public function __construct() {
		parent::__construct();

		
		// Initialize the database connection
		try {
			$this->api->initialize($this->impulselib->get_username());
		}
		catch(ObjectNotFoundException $onfE) {
			$this->_error("Unable to find your username (".$this->impulselib->get_username().") Make sure the LDAP server is functioning properly.");
		}
		catch(DBException $dbE) {
			$this->_error("Database connection error: ".$dbE->getMessage());
		}
		
		// Instantiate the user
		$this->user = new User(
			$this->impulselib->get_username(),
			$this->impulselib->get_name(),
			$this->api->get->current_user_level(),
			$this->input->cookie('impulse_viewUser',TRUE)
		);

		// Base JS
		$this->_addScript('/js/impulse.js');

		// Forminfo
		$this->forminfo = $this->load->view('core/forminfo',null,true);
	}

	public function index() {
		header("Location: /systems/view/".$this->user->getActiveUser());
	}

	protected function _render($content=null) {
		
		// Page title
		$title = "IMPULSE: ".ucfirst($this->uri->segment(1))."/".ucfirst($this->uri->segment(2));
	
		// Basic information about the user should be displayed
		$userData['userName'] = $this->user->get_user_name();
		$userData['displayName'] = $this->user->get_display_name();
		$userData['userLevel'] = $this->user->get_user_level();
		$userData['userLevel'] = $this->user->get_user_level();
		$userData['viewUser'] = $this->user->getActiveUser();
		$userData['header'] = $this->navheader;

		// If the user is an admin then they have the ability to easily switch "viewing" users
		if($this->user->isadmin()) {
			try {
				$userData['users'] = $this->api->get->users();
			}
			catch(ObjectNotFoundException $e) { $userData['users'] = array($this->user->getActiveUser()); }
			catch(Exception $e) {
				$content = $this->load->view('exceptions/exception',array("exception"=>$e),true);
			}
		}

		// Load navbar view
		$navbar = $this->load->view('core/navbar',$userData,true);

		// Load breadcrumb trail view
		$breadcrumb = $this->load->view('core/breadcrumb',array('segments'=>$this->trail),true);

		// Sidebar
		$sidebar = $this->load->view('core/sidebarblank',array('sideContent'=>$this->sidebarItems),true);

		// Content
		$content.= $this->_renderActions();

		// Error Handling
		$content .= $this->load->view('core/modalerror',null,true);

		// Confirmation
		$content .= $this->load->view('core/modalconfirm',null,true);

		// Info
		$content .= $this->load->view('core/modalinfo',null,true);

		// JS
		$scripts = "";
		foreach($this->js as $js) {
			$scripts .= "<script src=\"$js\"></script>";
		}

		// Send the data to the browser
		$this->load->view('core/main',array('title'=>$title,'navbar'=>$navbar,'breadcrumb'=>$breadcrumb,'sidebar'=>$sidebar,'content'=>$content,'scripts'=>$scripts));
	}

	protected function _addAction($action,$link,$class=null) {
		if(!$class) {
			switch($action) {
				case "Create":
					$class="success";
					break;
				case "Modify":
					$class="warning";
					break;
				case "Remove":
					$class="danger";
					break;
				default:
					$class="info";
					break;
			}
		}

		$id = strtolower(str_replace(" ",null,$action));

		$this->actions[] = $this->load->view('core/actionbutton',array("action"=>$action,"link"=>$link,"class"=>$class,"id"=>$id),true);
	}

	protected function _addTrail($name,$link) {
		$this->trail[$name] = $link;
	}

	protected function _renderActions() {
		if($this->actions) {
			$actionCode = "<div class=\"span2 well pull-right\">";
			foreach($this->actions as $action) {
				$actionCode .= $action;
			}
			$actionCode .= "</div>";
			return $actionCode;
		}
	}

	protected function _addSidebarItem($text, $link, $icon=null) {
		if($icon) {
			$this->sidebarItems .= "<li><a href=\"$link\"><i class=\"icon-$icon icon-black\"></i> $text</a></li>";
		}
		else {
			$this->sidebarItems .= "<li><a href=\"$link\">$text</a></li>";
		}
	}

	protected function _addSidebarHeader($text,$link=null) {
		if($link) {
			$this->sidebarItems .= "<li class=\"nav-header\"><a href=\"$link\">$text</a></li>";
		}	
		else {
			$this->sidebarItems .= "<li class=\"nav-header\">$text</li>";
		}
	}

	protected function _setNavHeader($header) {
		$this->navheader = $header;
	}

	protected function _sendClient($url,$return=null) {
		if(!$return) {
			print "<script>window.location.href = '$url';</script>";
		}
		else {
			return "<script>window.location.href = '$url';</script>";
		}
	}

	protected function _error($e) {
		$this->load->view('exceptions/modalerror',array('exception'=>$e));
	}

	protected function _addSidebarDnsRecords($recs) {
		foreach($recs as $rec) {
			switch(get_class($rec)) {
				case 'AddressRecord':
					$this->_addSidebarItem($rec->get_hostname().".".$rec->get_zone(),"/dns/records/view/".rawurlencode($rec->get_address())."#A/AAAA","font");
					break;
				case 'CnameRecord':
					$this->_addSidebarItem($rec->get_alias().".".$rec->get_zone(),"/dns/records/view/".rawurlencode($rec->get_address())."#CNAME","hand-right");
					break;
				case 'MxRecord':
					$this->_addSidebarItem($rec->get_hostname().".".$rec->get_zone(),"/dns/records/view/".rawurlencode($rec->get_address())."#MX","envelope");
					break;
				case 'SrvRecord':
					$this->_addSidebarItem($rec->get_alias().".".$rec->get_zone(),"/dns/records/view/".rawurlencode($rec->get_address())."#SRV","wrench");
					break;
				case 'TextRecord':
					$this->_addSidebarItem($rec->get_hostname().".".$rec->get_zone(),"/dns/records/view/".rawurlencode($rec->get_address())."#TXT","list-alt");
					break;
				case 'NsRecord':
					$this->_addSidebarItem($rec->get_nameserver(),"/dns/records/view/".rawurlencode($rec->get_address())."#NS","file");
					break;
				default:
					throw new Exception("WTF?");
					break;
			}
		}
	}

	protected function _exit($e) {
		$content = $this->load->view('exceptions/exception',array("exception"=>$e),true);
		$this->_render($content);
	}

	protected function _addContentToList($content, $cols) {
		$this->contentList[$cols][] = $content;
	}

	protected function _renderContentList($cols) {
		$content = "<div class=\"container span7\">";
		$rowCounter = 0;
		foreach($this->contentList[$cols] as $view) {
			if($rowCounter == 0) {
				$content .= "<div class=\"row-fluid\">";
			}
			elseif($rowCounter % $cols == 0) {
				$content .= "</div><div class=\"row-fluid\">";
			}
			$content .= $view;
			$rowCounter++;
		}

		$content .= "</div></div>";
		return $content;
	}

	protected function _addScript($path) {
		$this->js[] = $path;
	}

	protected function _renderSimple($content) {
		// JS
		foreach($this->js as $js) {
			$content .= "<script src=\"$js\"></script>";
		}

		$this->output->set_output($content);
	}

	protected function _postToNull($var) {
		if(!$this->input->post($var)) {
			return null;
		}
		elseif($this->input->post($var) == "") {
			return null;
		}
		else {
			return $this->input->post($var);
		}
	}

	protected function _post($var) {
		return $this->_postToNull($var);
	}

	protected function _renderSubnetOptionTable($opts) {
		$html = "<table class=\"table table-striped table-bordered imp-dnstable\">";
		$html .= "<tr><th>Option</th><th>Value</th><th style=\"width: 157px\">Actions</th></tr>";
		
		foreach($opts as $opt) {
			// Links
			$detLink = "/dhcp/subnetoption/view/".rawurlencode($opt->get_subnet())."/".rawurlencode($opt->get_option())."/".rawurlencode(md5($opt->get_value()));
			$modLink = "/dhcp/subnetoption/modify/".rawurlencode($opt->get_subnet())."/".rawurlencode($opt->get_option())."/".rawurlencode(md5($opt->get_value()));
			$remLink = "/dhcp/subnetoption/remove/".rawurlencode($opt->get_subnet())."/".rawurlencode($opt->get_option())."/".rawurlencode(md5($opt->get_value()));

			// Table
			$html .= "<tr><td>".htmlentities($opt->get_option())."</td><td>".htmlentities($opt->get_value())."</td><td>";
			$html .= "<a href=\"$detLink\"><button class=\"btn btn-mini btn-info\"\">Detail</button></a>";
			$html .= "<span> </span>";
			$html .= "<a href=\"$modLink\"><button class=\"btn btn-mini btn-warning\">Modify</button></a>";
			$html .= "<span> </span>";
			$html .= "<a href=\"$remLink\"><button class=\"btn btn-mini btn-danger\">Remove</button></a>";
			$html .= "</td></tr>";
		}

		$html .= "</table>";

		return $html;
	}

}
/* End of file ImpulseController.php */
/* Location: ./application/libraries/core/ImpulseController.php */
