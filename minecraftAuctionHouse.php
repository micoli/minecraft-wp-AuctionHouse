<?php
/*
Plugin Name: Wordpress Minecraft AuctionHouse
Plugin URI: http://craft.micoli.org
Description: Auction house
Author: o.michaud
Version: 0.1
Author URI: http://craft.micoli.org
*/
include_once dirname(__FILE__)."/../morg_wp_plugin/morg_wp_plugin.php";

class minecraft_auction_house extends morg_wp_plugin{
	var $prefix = "morgah";
	var $pluginName="";
	var $jsonapi = null;

	var $adminMenu = array(
		"MinecraftAuctionHouse"=>array(
			"page_title"=>"Minecraft Auction House",
			"menu_title"=>"Minecraft Auction House",
			"capability"=>'manage_options',
			"function"	=>"admin__main"
		)
	);
	
	function __construct(){
		parent::__construct();
		$this->jsonapi = new JSONAPI(
			get_option('morg_ah_jsonapi_host'		),
			get_option('morg_ah_jsonapi_port'		),
			get_option('morg_ah_jsonapi_user'		),
			get_option('morg_ah_jsonapi_password'	),
			get_option('morg_ah_jsonapi_salt'		)
		);
	}

	function wp_enqueue_script__jquerydatatables() {
		wp_enqueue_script( 'jquery-ui-core');
		//wp_enqueue_script( 'jquerynotmin','http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.js');
		wp_enqueue_style ( 'themejquery'				, 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/smoothness/jquery-ui.css');
		wp_enqueue_script( 'jquerydatatables'			, 'http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.js');
		wp_enqueue_style ( 'jquerydatatables'			, 'http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css');
		wp_enqueue_script( 'minecraft-wp-AuctionHouse'	, get_site_url().'/wp-content/plugins/minecraft-wp-AuctionHouse/minecraftAuctionHouse.js');
		//wp_enqueue_style ( 'jqueryvalidation'			, get_site_url().'/wp-content/plugins/morg_wp_plugin/css/validationEngine.jquery.css');
		//wp_enqueue_script( 'jqueryvalidation'			, get_site_url().'/wp-content/plugins/morg_wp_plugin/js/jquery.validationEngine.js');
		//wp_enqueue_script( 'jqueryvalidationi18n'		, get_site_url().'/wp-content/plugins/morg_wp_plugin/js/languages/jquery.validationEngine-fr.js');
		wp_enqueue_script( 'jqueryvalidation'			, 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.js');
		wp_enqueue_script( 'jqueryvalidationadd'		, 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/additional-methods.js');
		wp_enqueue_script( 'jqueryvalidationi18n'		, 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/localization/messages_fr.js');
		wp_enqueue_style ( 'jqueryqtip'					, get_site_url().'/wp-content/plugins/morg_wp_plugin/css/jquery.qtip.css');
		wp_enqueue_script( 'jqueryqtip'					, get_site_url().'/wp-content/plugins/morg_wp_plugin/js/jquery.qtip.js');
	}
	
	function get_current_user(){
		if(!is_user_logged_in()){
			return null;
		}
		return wp_get_current_user()->user_login;
	}
	
	private function getItemList(){
		return morg_wp_tools::object2array(json_decode(file_get_contents(get_option('morg_wi_export_folder').'/items/__allitems.json')));
	} 
	
	function wp_ajax_nopriv__auction_house_currentuser(){
		die(json_encode(array(
			'user'	=>$this->get_current_user()
		)));
	}
	
	function wp_ajax_nopriv__auction_house_list(){
		$result = $this->jsonapi->callMultiple(array(
			"auctionHouse.listAllAuctions",
			"econ.getBalance"
		),array(
			array($this->get_current_user()),
			array($this->get_current_user())
		));
		
		$allItems = $this->getItemList();
		//db($result);
		foreach($result['success'][0]['success'] as $k=>&$v){
			$v['img'] = '<img src="'.get_option('morg_wi_url_root').'items/'.$allItems[$v['itemId']]['name'].'_000.png" title="'.$allItems[$v['itemId']]['name'].'">';
			$v['itemName'] = ucwords( strtolower(str_replace('_',' ',$allItems[$v['itemId']]['name'])));
		}
		
		die(json_encode(array(
			'auctionList'	=>$result['success'][0]['success'],
			'currentUser'	=>$this->get_current_user(),
			'userBalance'	=>is_user_logged_in()?$result['success'][1]['success']:-1
		)));
	}

	function wp_ajax__auction_house_bid(){
		$result = $this->jsonapi->callMultiple(array(
			"auctionHouse.bid"
		),array(
			array(
				/*auctionId	*/$_REQUEST['auctionId'],
				/*buyerName	*/$this->get_current_user(),
				/*price"	*/$_REQUEST['price']
			)
		));
		
		die(json_encode($result['success'][0]['success']));
	}

	function wp_ajax__auction_house_buy(){
		$result = $this->jsonapi->callMultiple(array(
			"auctionHouse.buy"
		),array(
			array(
				/*auctionId	*/$_REQUEST['auctionId'],
				/*buyerName	*/$this->get_current_user(),
				/*price"	*/$_REQUEST['price'],
				/*quantity	*/$_REQUEST['quantity'],
			)
		));
		
		die(json_encode($result['success'][0]['success']));
	}
	
	function admin__main() {
		if($_POST['morg_ah_hidden'] == 'Y') {
			update_option('morg_ah_jsonapi_host'	, $_POST['morg_ah_jsonapi_host']);
			update_option('morg_ah_jsonapi_user'	, $_POST['morg_ah_jsonapi_user']);
			update_option('morg_ah_jsonapi_password', $_POST['morg_ah_jsonapi_password']);
			update_option('morg_ah_jsonapi_port'	, $_POST['morg_ah_jsonapi_port']);
			update_option('morg_ah_jsonapi_salt'	, $_POST['morg_ah_jsonapi_salt']);

			print sprintf('<div class="updated"><p><strong>%s</strong></p></div>', __('Options saved.' ));
		}
		$morg_ah_jsonapi_host		= get_option('morg_ah_jsonapi_host'		,"127.0.0.1");
		$morg_ah_jsonapi_port		= get_option('morg_ah_jsonapi_port'		,"20059");
		$morg_ah_jsonapi_user		= get_option('morg_ah_jsonapi_user'		,"user");
		$morg_ah_jsonapi_password	= get_option('morg_ah_jsonapi_password'	,"password");
		$morg_ah_jsonapi_salt		= get_option('morg_ah_jsonapi_salt'		,"salt goes here");
		
		$form = "
				<div class=\"wrap\">
				<h2>" . __( 'MineCraft Auction House', 'morg_ah_trdom' ) . "</h2>
				<form name=\"morg_ah_form\" method=\"post\" action=\"".str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) ."\">
				<input type=\"hidden\" name=\"morg_ah_hidden\" value=\"Y\">
				<h4>" . __( 'Settings', 'morg_wi_trdom' ) . "</h4>
				<p>". __("JsonApi server: "		)."<input type=\"text\" name=\"morg_ah_jsonapi_host\"		value=\""	.$morg_ah_jsonapi_host		."\" size=\"90\">". __(" ex: /var/www/....") ."</p>
				<p>". __("JsonApi port: "		)."<input type=\"text\" name=\"morg_ah_jsonapi_port\"		value=\""	.$morg_ah_jsonapi_port		."\" size=\"90\">". __(" ex: /var/www/....") ."</p>
				<p>". __("JsonApi user: "		)."<input type=\"text\" name=\"morg_ah_jsonapi_user\"		value=\""	.$morg_ah_jsonapi_user		."\" size=\"90\">". __(" ex: /var/www/....") ."</p>
				<p>". __("JsonApi password: "	)."<input type=\"text\" name=\"morg_ah_jsonapi_password\"	value=\""	.$morg_ah_jsonapi_password	."\" size=\"90\">". __(" ex: /var/www/....") ."</p>
				<p>". __("JsonApi salt: "		)."<input type=\"text\" name=\"morg_ah_jsonapi_salt\"		value=\""	.$morg_ah_jsonapi_salt		."\" size=\"90\">". __(" ex: /var/www/....") ."</p>
				<hr />
				<p class=\"submit\">
					<input type=\"submit\" name=\"Submit\" value=\"". __('Update Options', 'morg_ah_trdom' ) ."\" />
				</p>
				</form>
			</div>";
		print $form;
	}

	function shortcode__auctionhouse($atts, $content = null) {
		global $wp;
		extract(shortcode_atts(array(
		), $atts));
		
		if(array_key_exists('ah_mode',$wp->query_vars)){
			$ah_mode = $wp->query_vars['ah_mode'];
		}else{
			$ah_mode = 'main';
		}
		$smarty	= self::getSmarty(__FILE__);
		$data	= array(
			'morg_ah_url_root'		=>get_option('morg_ah_url_root'),
			'morg_ah_localplan_page'=>get_option('morg_ah_localplan_page')
		);
		switch ($ah_mode){
			case 'main';
				$result = $this->jsonapi->call(array(
					"auctionHouse.getPlayerInventory"
				),array(
					array($this->get_current_user())
				));
				
				$allItems = $this->getItemList();
				if(is_array($result['success'][0]['success']['itemStacks'])){
					foreach($result['success'][0]['success']['itemStacks'] as $k=>&$v){
						$v['img'] = '<img src="'.get_option('morg_wi_url_root').'items/'.$allItems[$v['type']]['name'].'_000.png" title="'.$allItems[$v['itemId']]['name'].'">';
						$v['itemName'] = ucwords( strtolower(str_replace('_',' ',$allItems[$v['type']]['name'])));
					}
				}
				$data['inventory']=$result['success'][0]['success']['itemStacks'];
				$template='templates/auctionhouse_main.tpl';
			break;
		}
		$smarty->assign('data',$data);
		print $smarty->fetch($template);
	}
}
$morg_minecraft_auction_house = new minecraft_auction_house();
?>