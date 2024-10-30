<?php

/*
Plugin Name: Mobile PRwire Mobile News Importer
Plugin URI: http://wordpress.org/extend/plugins/mobileprwire-news-importer/
Description: Import posts from an MobilePRwire press release
Author: MobilePRwire
Version: 2.2
*/


require_once(dirname(__FILE__) . "/../../../wp-config.php");



if($_REQUEST['auto_cron']=='true')
{	
	require_once(dirname(__FILE__)."/../../../wp-includes/pluggable.php");
	require_once(dirname(__FILE__)."/../../../wp-admin/includes/post.php");
	
	if(get_option('mobilepr_auto_publish') == "publish" || get_option('mobilepr_auto_publish') == "draft"){
		cron_auto_import();
	}
} else {       
    add_action('init', 'mobilepr_news_start');
}

function mobilepr_news_start(){	
	add_action('admin_menu', 'mobilepr_news_settings');
}


function mobilepr_news_settings(){
	add_submenu_page('options-general.php', __('Mobile PRwire Mobile News Importer'), __('Mobile PRwire Mobile News Importer'), 'manage_options', 'mobilepr-news-config', 'mobilepr_news_conf');
	
}

function cron_automation_enabled(){
require_once(dirname(__FILE__)."/../../../wp-includes/pluggable.php");
	require_once(dirname(__FILE__)."/../../../wp-admin/includes/post.php");
	if(get_option('mobilepr_auto_publish') == "publish" || get_option('mobilepr_auto_publish') == "draft"){
		cron_auto_import();
	}
}
	
//update channel options
function updateChannels($post){
	
	$str = '';
	foreach($post as $ch){
		$str .= $ch.',';
	}
	$str = substr($str, 0, strlen($str)-1); 
	update_option('mobilepr_opted_channels', $str, "", false);

}

// get selected channels from option
function getOptedChannels(){

    $opted_channels = get_option('mobilepr_opted_channels');
	
	if(trim($opted_channels) != "" )
        $opted_channels = explode(',', $opted_channels);
	else
		$opted_channels = array();

	return $opted_channels;
}
	
function mobilepr_news_conf(){  
	
	if($_POST['generate_active_code']){
			$error = true;
			
		if(trim($_POST['user_fname']) != "" )
			$fname = trim($_POST['user_fname']);
		else {
			$fname = "";
			$error = false;
		}
		if(trim($_POST['user_lname']) != "" )
			$lname = trim($_POST['user_lname']);
        else{ 
			$lname = "";
			$error = false;
		}        
			
		if(trim($_POST['user_email']) != "" )
			$email = trim($_POST['user_email']);
		else {
			$email = "";
			$error = false;
		}
			
		if(trim($_POST['user_website']) != "" )
			$website = trim($_POST['user_website']);
		else{ 
			$website = "";
			$error = false;
		}        
		
		if($error ){
			update_option('mobilepr_user_fname', $fname, "", false);
			update_option('mobilepr_user_lname', $lname, "", false);
			update_option('mobilepr_user_email', $email, "", false);
			update_option('mobilepr_user_website', $website, "", false);
		
			$insertURL = "http://www.mobileprwire.com/mobilepr_insert.php?action=generate";
			$params 	= array("post" => serialize($_POST));

			if(get_option('mobilepr_user_activation_code') != ""){
				$code = get_option('mobilepr_user_activation_code');
				$insertURL = "http://www.mobileprwire.com/mobilepr_insert.php?action=generate&code=$code";
			}
			
			$response = fetch_contents($insertURL, $params);  

			if(get_option('mobilepr_user_activation_code') == ""){
				update_option('mobilepr_user_activation_code', $response, "", false);
                                
                                // fetch releases from mobileprwire prior 5 days
                                $selected_channels =getOptedChannels();            
                                if($selected_channels){
                                    
                                        $import_first_news = fetch_first_news();
                                        if(count($import_first_news) > 0){
                                             $result = import_news($import_first_news , 'import_releases');
                                             echo '<div id="message" class="updated fade"><p>'.count($import_first_news)." releases were successfully imported.</p></div>";
                                        }else{
                                            echo  '<div id="message" class="updated fade"><p>There aren\'t any new MobilePRwire releases which haven\'t been imported yet. </p></div>';
                                        }
                                }

                        }        
			
			if ( !empty($_POST) ){	//Activation Code Saved.However, your Activation code was invalid.
				echo '<div id="message" class="updated fade"><p><strong>Activation Code Saved.';
				echo '</strong></p></div>';
			}
		}else{
			echo '<div id="message" class="updated fade"><p><strong>Please Enter Required Fields </strong></p></div>';
		}
	}
	
	if (get_option('mobilepr_user_activation_code') == "") { 
		echo '<div id="message" class="updated fade"><p><strong> '; 
		_e('Activation Code not Found. Please Register.'); 
		echo '</strong></p></div>';
	} 
	
	if($_POST['update']){
	
		if($_POST['subchannel_62']){
			foreach($_POST['subchannel_62'] as $ch )
				$var[] = $ch;
		}
		
		if($_POST['subchannel_49']){
			foreach($_POST['subchannel_49'] as $ch )
				$var[] = $ch;
		}
		
		if($_POST['subchannel_90']){
			foreach($_POST['subchannel_90'] as $ch )
			$var[] = $ch;
		}
		
		if($_POST['ios_root_channel'])
			$var[] = $_POST['ios_root_channel'];
		
		if($_POST['android_root_channel'])
			$var[] = $_POST['android_root_channel'];
		
		if($_POST['mac_root_channel'])
			$var[] = $_POST['mac_root_channel'];
		
		//Update channel options
		if(count($var) > 0 )	
			updateChannels($var);
			
		update_option('mobilepr_auto_publish', $_POST['mobilepr_auto_publish'], "", false);
		update_option('mobilepr_post_date  ', $_POST['mobilepr_post_date'], "", false);
               
                update_option('mobilepr_release_tag  ', $_POST['mobilepr_release_tag'], "", false);
		
		update_option('mobilepr_ios_category', $_POST['ios_category'], "", false);
		update_option('mobilepr_android_category', $_POST['android_category'], "", false);
		update_option('mobilepr_mac_category', $_POST['mac_category'], "", false);
                
                
                // import releases
                if(get_option('mobilepr_user_activation_code') != ""){
                    
                     // fetch releases from mobileprwire prior 5 days
                     $selected_channels =getOptedChannels();            
                     if($selected_channels){

                            $import_first_news = fetch_first_news();
                            if(count($import_first_news) > 0){
                                 $result = import_news($import_first_news , 'import_releases');
                                 echo '<div id="message" class="updated fade"><p>'.count($import_first_news)." releases were successfully imported.</p></div>";
                            }else{
                                echo  '<div id="message" class="updated fade"><p>There aren\'t any new MobilePRwire releases which haven\'t been imported yet. </p></div>';
                            }
                     } 
                    
                }
		
		if ( !empty($_POST) ){
			echo '<div id="message" class="updated fade"><p><strong>Options Updated.</strong></p></div>';
		}
		
	}
		
        //if auto import option is selected	
        if(get_option('mobilepr_auto_publish') == "publish" || get_option('mobilepr_auto_publish') == "draft"){
                $reg = 'register';
                $result = release_auto_import($reg);

        }else{
                $reg = 'unregister';
                $result = release_auto_import($reg);
        }
		
?>

<link rel="stylesheet" href="<?php echo plugins_url('/css/style.css',__FILE__ ); ?>" type="text/css" media="screen" /> 
<script type="text/javascript" src="<?php echo plugins_url('/js/jquery.js',__FILE__ ); ?>"></script>   
<script type="text/javascript" src="<?php echo plugins_url('/js/validation.js',__FILE__ ); ?>"></script>  
      
<script type="text/javascript">

	function CheckAll(rootChannel, subChannel){

		if(rootChannel.checked){
			for (i = 0; i < subChannel.length; i++)
				subChannel[i].checked = true;
		}else{
			for (i = 0; i < subChannel.length; i++)
				subChannel[i].checked = false;
		}
	}
</script>
<div class="wrap">
  <h2>
    <?php _e('Mobile PRwire Mobile News Importer'); ?>
  </h2>
  <div class="narrow">
    <p> Mobile PRwire is a press release distribution service that covers iOS and Android related news. We are proud to announce that we now have a new WordPress Plugin you can use to immediately post the latest news directly from Mobile PRwire. This means that you will always have fresh content loaded to your site and you will no longer have to copy and paste a release which wastes a lot of time for busy bloggers.</p>
    <p><strong>Using this Plugin gives you the following benefits: </strong>    
    
    
    <ul style="list-style:inside;padding:0 0 0 10px;">
      <li>Select the news you want and that's relevant to your blog</li>
      <li>Select from iOS, Mac or Android News. Then dive even deeper into their channels.</li>
      <li>Automated posting of news stories so you can "set it and forget it"</li>
      <li>Easy configuration so you are up and running fast</li>
      <li>Saves countless hours weekly!!</li>
      <li>No hassle of first registering on our site. Do it right from the plugin</li>
    </ul>
    </p>   
    
    
    
    <p> Here's how to get the most out of the new Mobile Importer WordPress Plugin:</p>
    <p> Once installed, enter some basic information about yourself (First Name, Last Name, Email Address, and Website). Then generate your activation code. Next you can conveniently select the type of news you would like posted to your website. Mobile PRwire has three news categories to choose from iOS, Mac, and Android.  Each news category has multiple channels.  Select one channel or all channels either way our plugin gives you that flexibility.  </p>
    <p>At the bottom of each category the plugin gives you the ability to assign a WordPress category to store your news.  For example if you selected to receive all iOS and Android news you might not want both news types posted to the same area of your site.  Use WordPress categories to organize the news we send and where it gets posted on your site.</p>
    <p><strong>Import Settings</strong>
    <ul style="list-style:inside;padding:0 0 0 10px;">
      <li>4. Publish Settings - This setting automatically pulls releases and post them either as draft status or as a published article. </li>
	  <li>5. Date Settings - This setting will set the publish date either as Mobile PRwire's post date or the date you publish the article </li>
      <li>6. Tag Settings - Tag settings allows you to use the tags that are listed on Mobile PRwire or your own tags.  You will manually have to add tags to each article if you select the "use your own tags" option. </li>
    </ul>
    </p>
   
    <br />
    <table width="750" border="0">
        <tr><td align="left" colspan="3" style="color:#FF0000;">* Required fields </td></tr>
        <tr><td align="left" colspan="3">&nbsp;</td></tr>
      <form action="" method="post" id="mobilepr_user" name="mobilepr_user" >
        <tr>
          <td width="130">First Name * </td>
          <td width="15"> : </td>
          <td><input id="user_fname" name="user_fname" type="text" size="18" maxlength="15" value="<?php echo get_option('mobilepr_user_fname'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.25em;" />&nbsp; <span id="fname"></span></td>
        </tr>
        <tr>
          <td>Last Name *</td>
          <td> : </td>
          <td><input id="user_lname" name="user_lname" type="text" size="18" maxlength="15" value="<?php echo get_option('mobilepr_user_lname'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.25em;" />&nbsp; <span id="lname"></span></td>
        </tr>
        <tr>
          <td>Email Address * </td>
          <td> : </td>
          <td><input id="user_email" name="user_email" type="text" size="18" maxlength="40" value="<?php echo get_option('mobilepr_user_email'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.25em;" />&nbsp; <span id="email"></span></td>
        </tr>
        <tr>
          <td>Website *</td>
          <td> : </td>
          <td><input id="user_website" name="user_website" type="text" size="18" maxlength="50" value="<?php echo get_option('mobilepr_user_website'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.25em;" />&nbsp; <span id="web"></span></td>
        </tr>
        <tr>
          <td colspan="3">&nbsp;</td>
        </tr>
        <tr>
          <td colspan="3">Generate Activation Code &nbsp;&nbsp;
            <?php 
		if(get_option('mobilepr_user_activation_code') != '')
			$space = '&raquo;&raquo;';
	?>
            <input type="submit" id="generate_active_code" name="generate_active_code" value="<?php _e('Generate Activation Code'); ?>" <?php echo $disable; ?> />&nbsp; <span id="generate"></span>
            &nbsp;&nbsp;<?php echo $space; ?>&nbsp;&nbsp; 
            <label id="code" style="color:#FF0000;font-weight:bold;"><?php echo get_option('mobilepr_user_activation_code'); ?></label></td>
        </tr>
      </form>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
	  </table>
      <tr>
	  <form action="" method="post" id="mobilepr_options" name="mobilepr_options"> 
        <table width="100%" border="0" align="left">           
            <tr align="left" height="35">
              <th colspan="3"><h3>Select News You would like Posted To Your Site: </h3></th>
            </tr>
			<?php 
				$opted_channels = getOptedChannels();
				if(in_array(62, $opted_channels))
					$ios_sel = 'checked="checked"';
				else
					$ios_sel = '';
				
				if(in_array(49, $opted_channels))
					$android_sel = 'checked="checked"';
				else
					$android_sel = '';
					
				if(in_array(90, $opted_channels))
					$mac_sel = 'checked="checked"';
				else
					$mac_sel = '';
			
			?>
            <tr align="left" height="35">
              <th><input type="checkbox" name="ios_root_channel" id="ios_root_channel" value="62" <?php echo $ios_sel; ?> onclick="javascript: CheckAll(this, document.mobilepr_options['subchannel_62[]']);" />
                All iOS News</th>
              <th><input type="checkbox" name="android_root_channel" id="android_root_channel" value="49" <?php echo $android_sel; ?> onclick="javascript: CheckAll(this, document.mobilepr_options['subchannel_49[]']);"/>
                All Android News</th>
              <th><input type="checkbox" name="mac_root_channel" id="mac_root_channel" value="90"  <?php echo $mac_sel; ?> onclick="javascript: CheckAll(this, document.mobilepr_options['subchannel_90[]']);"/>
                All Mac News</th>
            </tr>		
            <tr align="left" valign="top">
              <td style="padding-left:10px;"><?php echo $androidchannels = getSubChannelList(62, $opted_channels); ?></td>
              <td style="padding-left:10px;"><?php echo $ioschannels = getSubChannelList(49, $opted_channels); ?></td>
              <td style="padding-left:10px;"><?php echo $macchannels = getSubChannelList(90, $opted_channels); ?></td>
            </tr>
			<tr align="left" height="10">
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
			<tr align="left" >
			<? $categories = get_categories('hierarchical=0&orderby=name&hide_empty=0'); ?>
              <th>Add iOS News to Category <br />
			  		<select name="ios_category">
						<option value="-1">Don't Set Category</option>
						<? $selectedCatID = get_option('mobilepr_ios_category'); ?>
						<?php foreach ((array)$categories as $cat) : ?>
							<option value="<?php echo $cat->cat_ID; ?>" <? if($cat->cat_ID == $selectedCatID) echo 'selected="selected"'?> ><?php echo attribute_escape($cat->name)?> </option>
						<? endforeach ?>
					</select></th>
              <th>Add Android News to Category<br />
			  		<select name="android_category">
						<option value="-1">Don't Set Category</option>
						<? $selectedCatID = get_option('mobilepr_android_category'); ?>
						<?php foreach ((array)$categories as $cat) : ?>
							<option value="<?php echo $cat->cat_ID?>" <? if($cat->cat_ID == $selectedCatID) echo 'selected="selected"'?> ><?php echo attribute_escape($cat->name)?> </option>
						<? endforeach ?>
					</select></th>
              <th>Add Mac News to Category<br />
			  		<select name="mac_category">
						<option value="-1">Don't Set Category</option>
						<? $selectedCatID = get_option('mobilepr_mac_category'); ?>
						<?php foreach ((array)$categories as $cat) : ?>
							<option value="<?php echo $cat->cat_ID?>" <? if($cat->cat_ID == $selectedCatID) echo 'selected="selected"'?> ><?php echo attribute_escape($cat->name)?> </option>
						<? endforeach ?>
					</select></th>
            </tr>
			<tr align="left" valign="top">
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
		    </tr>
			<tr align="left" valign="top">
              <td colspan="3"><strong>News Publishing Settings</strong><br />
				<input type="radio" name="mobilepr_auto_publish" id="mobilepr_auto_publish" value="publish" <?php if(get_option('mobilepr_auto_publish') == "publish" ) echo 'checked="checked"'; ?> checked="checked" />Automatically Publish News When It Becomes Available on Mobile PRwire<br />
				<input type="radio" name="mobilepr_auto_publish" id="mobilepr_auto_publish" value="draft" <?php if(get_option('mobilepr_auto_publish') == "draft" ) echo 'checked="checked"'; ?>/>Automatically Publish News To Draft Status When It Becomes Available on Mobile PRwire	<br /><br />
			</td>
            </tr>
			<tr align="left" valign="top">
			  <td colspan="3"><strong>Date Settings</strong><br />
				<input type="radio" name="mobilepr_post_date" id="mobilepr_post_date" value="post_date" <?php if(get_option('mobilepr_post_date') == "post_date") echo 'checked="checked"'; ?> checked="checked" />Use Mobile PRwire Date As The Post Date<br />
				<input type="radio" name="mobilepr_post_date" id="mobilepr_post_date" value="publish_date" <?php if(get_option('mobilepr_post_date') == "publish_date") echo 'checked="checked"'; ?>/>Use The Date Of When I Publish The Draft As The Post Date
			</td>
		    </tr>
                    <tr align="left" valign="top">
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
		    </tr>
                    <tr align="left" valign="top">
			  <td colspan="3"><strong>Tag Settings</strong><br />
				<input type="radio" name="mobilepr_release_tag" id="mobilepr_release_tag" value="mobilepr_tag" <?php if(get_option('mobilepr_release_tag') == "mobilepr_tag") echo 'checked="checked"'; ?> checked="checked" />Use Mobile PRwire Press Release Tags for my posting<br />
				<input type="radio" name="mobilepr_release_tag" id="mobilepr_release_tag" value="wp_tag" <?php if(get_option('mobilepr_release_tag') == "wp_tag") echo 'checked="checked"'; ?>/>I will manually enter my own tags for each post
			</td>
		    </tr>
                    
			<tr align="left" valign="top">
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
		    </tr>
			<tr align="left" valign="top">
			  <td><p class="submit"><input type="submit" name="update" value="<?php _e('Update options &raquo;'); ?>" /></p></td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
		    </tr>
        </table>
      </form>
   

  </div>
</div>
<?php

	 } 
	 
	 function cron_auto_import(){
		
		$auto_import_list	= fetch_latest_20_news(0);
                if($auto_import_list != 'Disabled'){
		   $result = import_news($auto_import_list , 'import_releases');
                }   

	 }

	 function release_auto_import($reg){ 	
		
		$user_code = get_option('mobilepr_user_activation_code');
		if($user_code == ""){
			echo '<br><h2>Mobile PRwire Mobile News Importer</h2>';
			echo '<br><p>Cannot Import Releases. User activation code not found.</p>';
			return;
		}	
		
		$requestURL = "http://www.mobileprwire.com/register_new.php?user_code={$user_code}&q=$reg";
		$params 	= array("url" => mobilepr_pingback_url());
		
		$response = fetch_contents($requestURL, $params);
	 }
	 
	 function mobilepr_pingback_url(){
	 	
		$docRootLength = strlen(realpath($_SERVER['DOCUMENT_ROOT']));
		$path = str_replace("\\", "/", substr(__FILE__, $docRootLength, strlen(__FILE__)-$docRootLength));
		return "http://{$_SERVER['SERVER_NAME']}" . ($path[0] == '/' ? '' : '/') . $path;
	
	 }
         
         
         // Firstly, fetch all releases available prior 5 day...
         function fetch_first_news(){  
	         
	 	$params = '';
                if(get_option('mobilepr_user_activation_code') != ""){		
			$selected_channels =getOptedChannels();            
                        if($selected_channels)
			     $params = array('channels' => serialize($selected_channels));	
		}else if(get_option('mobilepr_user_activation_code') == ""){
			echo '<br><h2>Mobile PRwire Mobile News Importer</h2>';
			echo '<br><p>Cannot Import Releases. User activation code not found.</p>';
			return false;                        
		}
		
                $act_code = get_option('mobilepr_user_activation_code');  
		$import_array = fetch_contents("http://www.mobileprwire.com/xml_news_feed_new.php?act_code=$act_code", $params);                  
		
		$xmlParser = new XMLParser($import_array);
		$xmlTree = $xmlParser->document;     
                
                if($xmlTree['USERSTATUS'][0]['data'] == 'Disabled'){                    
                        
			return 'Disabled';
			exit;
                }  
                
		$xmlTreeRelease	= $xmlTree['RELEASES'][0]['RELEASE'];  
                
		foreach ((array)$xmlTreeRelease as $char_xml){
                    
                        $exist = post_exists($char_xml['TITLE'][0]['data'], $content = '', $date = '');
			if( $exist == 0){ 
			    $import_list[] = $char_xml['XMLURL'][0]['data'];	
			} 

		}
                		
		return $import_list;
	 }
	  
	 
	 
	 //fetch latest 20 news
	  function fetch_latest_20_news($start){  
	
	 	
                $params = ''; 
                if(get_option('mobilepr_user_activation_code') != ""){		
			$selected_channels =getOptedChannels();
                        if($selected_channels)
			    $params = array('channels' => serialize($selected_channels));	
		}else if(get_option('mobilepr_user_activation_code') == ""){
			echo '<br><h2>Mobile PRwire Mobile News Importer</h2>';
			echo '<br><p>Cannot Import Releases. User activation code not found.</p>';
			return false;                        
		}
		
                $act_code = get_option('mobilepr_user_activation_code');  
		$import_array = fetch_contents("http://www.mobileprwire.com/xml_news_feed_new.php?start=$start&count=20&act_code=$act_code", $params);                  
		
		$xmlParser = new XMLParser($import_array);
		$xmlTree = $xmlParser->document;     
                
                if($xmlTree['USERSTATUS'][0]['data'] == 'Disabled'){                    
                        
			return 'Disabled';
			exit;
                }  
                
		$xmlTreeRelease	= $xmlTree['RELEASES'][0]['RELEASE'];  
		$import_list = already_imported_news($xmlTreeRelease);  

		if(count($import_list) <= 0 && count($xmlTreeRelease) > 0){
			$start = $start+20;  
			$import_list = fetch_latest_20_news($start); 
		}  
		return $import_list;
	 }
	 
	 //chack for already imported releases
	 function already_imported_news($xml_releases){

		$release_count = 0;
		foreach ((array)$xml_releases as $char_xml){
		
			$release_url = $char_xml['XMLURL'][0]['data'];
			$exist = post_exists($char_xml['TITLE'][0]['data'], $content = '', $date = ''); 

			if( $exist == 0){ 
				$import_list[$release_count] = $release_url;
				$release_count = $release_count+1;
			} 
		}
	
		return $import_list;
	 }
	 
	 function import_news($import_list, $importPost = ''){  
	 	                                                
		//submit import button						
		if($importPost != ""){

			$count = 0;
			
			
			for( $i = 0; $i < count($import_list); $i++){

				$release_array = get_news_releases($import_list[$i]);				
			}
			
				
			
		} 
	 }
	 
	 //get subchannels from url
	function getSubChannelList($pid, $selected_channels){

		$channelURL = "http://www.mobileprwire.com/xml_channel_feed.php";
		$params 	= array("parent_id" => $pid);
		$response = fetch_contents($channelURL, $params);

		//parse xml file
		$xmlParser = new XMLParser($response);	
		$xmlTree = $xmlParser->document;
		$xmlTreeChannel = $xmlTree['CHANNELS'][0]['CHANNEL'];

		$tbl_channel = '';
		foreach ((array)$xmlTreeChannel as $ch_xml){
			$id = $ch_xml['ID'][0]['data'];
			$title = $ch_xml['TITLE'][0]['data'];

			if( isset($selected_channels) ){
				if(in_array($id, $selected_channels))
					$selected = 'checked="checked"';
				else
					$selected = '';
			}else
				$selected = '';
				
			$tbl_channel .= '<div><input type="checkbox" name="subchannel_'.$pid.'[]" id="subchannel_'.$pid.'[]" value="'.$id.'" '.$selected.' />&nbsp; '.$title.'</div>';
		}
		return $tbl_channel;
	}
	
		 //get subchannels from url
	function getChannelSubId($pid){
		$channelURL = "http://www.mobileprwire.com/xml_channel_feed.php";
		$params 	= array("parent_id" => $pid);
		$response = fetch_contents($channelURL, $params);

		//parse xml file
		$xmlParser = new XMLParser($response);	
		$xmlTree = $xmlParser->document;
		$xmlTreeChannel = $xmlTree['CHANNELS'][0]['CHANNEL'];

		foreach ((array)$xmlTreeChannel as $ch_xml){
			$id[] = $ch_xml['ID'][0]['data'];
		}
		return $id;
	}
	
	 //read xml file content from url
	 function fetch_contents($url, $param = ''){

		if (is_callable("curl_init"))
		{	
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$data = curl_exec($ch);
			curl_close($ch);

			return $data;
		} else {
			print "Cannot connect to MobilePRwire server ( libcurl is disabled )";
			return false;
		}
	 }
	 
	 function get_news_releases($release_url){ 
	 	
		global $import_count, $import_result, $user_ID;
		$import_count = 0;
	 	$release_xml = fetch_contents($release_url);  
		
		//parse xml file
		$xmlParser = new XMLParser($release_xml);	
		$xmlTree = $xmlParser->document;
		
		$xmlTreeRelease = $xmlTree['RELEASE'][0];  

		//checking if publish date as post date option is set
		if(get_option('mobilepr_post_date') == "post_date" ){
			$post_date_gmt = gmdate('Y-m-d H:i:s', $xmlTreeRelease['TIMESTAMP'][0]['data'])." - ";
			$post_date = get_date_from_gmt($post_date_gmt);
		} else{
			$post_date = current_time('mysql');
			$post_date_gmt = "";
		}
		
		// get post status
		$post_status = get_option('mobilepr_auto_publish');
                
                $full_url = '<a href="'.$xmlTreeRelease['URL'][0]['data'].'">Read the full press release at MobilePRwire.com</a>';
		
		// get relases contents
		$post_content = "[MobilePRwire] - ".$xmlTreeRelease['SUMMARY'][0]['data']."<br /><br />".$xmlTreeRelease['BODY'][0]['data'].'<br /><br />'.$xmlTreeRelease['CORPORATEIDENTITY'][0]['data']."<br /><br />".$full_url;
		
		// check if import_image option is set added to post_contents
		/*if(get_option('import_image')){
			if($xmlTreeRelease['IMAGE'][0]['data'] != NULL )
				$post_content .= '<img alt="" src="'.$xmlTreeRelease['IMAGE'][0]['data'].'" title="'.$xmlTreeRelease['TITLE'][0]['data'].'" class="alignleft" width="257" height="280" />';
		}*/
		$ios_arr = getChannelSubId(62);
		$android_arr = getChannelSubId(49);
		$mac_arr = getChannelSubId(90);
		
		$postChannel = $xmlTreeRelease['CHANNEL'][0]['data'];
	
		if(in_array($postChannel, $ios_arr))
			$post_category = get_option('mobilepr_ios_category');
			
		if(in_array($postChannel, $android_arr))
			$post_category = get_option('mobilepr_android_category');
		
		if(in_array($postChannel, $mac_arr))
			$post_category = get_option('mobilepr_mac_category');	
			
		
		//define post array 	 	 	 	 	 	 	 	 	 	
		$add_post  = array(
				'post_author'  		=> $user_ID ? $user_ID : 1,
				'post_date' 		=> $post_date,
				'post_date_gmt'		=> $post_date_gmt,
				'post_modified'		=> $post_date,
				'post_modified_gmt'	=> $post_date_gmt,
				'post_content' 		=> $post_content,
				'post_title'  		=> $xmlTreeRelease['TITLE'][0]['data'],
				'post_excerpt' 		=> $xmlTreeRelease['SUMMARY'][0]['data'],
				'post_status'  		=> $post_status,
				'comment_status'	=> get_option('default_comment_status'),
				'ping_status'		=> get_option('default_ping_status'),
				'post_name' 		=> $xmlTreeRelease['TITLE'][0]['data'],
				'to_ping'  			=> $xmlTreeRelease['TRACKBACKURL'][0]['data'],
				'post_type' 		=> 'post',
				'comment_count'		=> 0, 
				'post_category'		=> array($post_category)
			);
		
		$new_post = wp_insert_post($add_post);
  
  
  
// Insert Tag if set to "Use Mobile PRwire Press Release Tags for my posting"
                if(get_option('mobilepr_release_tag') == "mobilepr_tag"){
                    
                    $tag = $xmlTreeRelease['TAG'][0]['data'];                       
                    wp_set_post_tags( $new_post, $tag);
                }
                
                

		//publish posts if publish option is selected
		if(get_option('mobilepr_auto_publish') == "publish" ){
			do_trackbacks($new_post);
			wp_publish_post($new_post);	
		}
		
		
	 }

class XMLParser
{
    var $parser;
    var $document;
    var $currTag;
    var $tagStack;
	var $data;
   
    function XMLParser($xmlContent)
    {
		$this->parser = xml_parser_create();
		$this->data = $xmlContent;
		$this->document = array();
		$this->currTag =& $this->document;
		$this->tagStack = array();
		$this->parse();
    }
   
    function parse()
    {
        xml_set_object($this->parser, $this);
        xml_set_character_data_handler($this->parser, 'dataHandler');
        xml_set_element_handler($this->parser, 'startHandler', 'endHandler');
      
        if(!xml_parse($this->parser, $this->data))
        {
            die(sprintf("XML error: %s at line %d. " , xml_error_string(xml_get_error_code($this->parser)), xml_get_current_line_number($this->parser),$this->data));
        }
    	xml_parser_free($this->parser);
   
        return true;
    }
   
    function startHandler($parser, $name, $attribs)
    {
        if(!isset($this->currTag[$name]))
            $this->currTag[$name] = array();
       
        $newTag = array();
        if(!empty($attribs))
            $newTag['attr'] = $attribs;
        array_push($this->currTag[$name], $newTag);
       
        $t =& $this->currTag[$name];
        $this->currTag =& $t[count($t)-1];
        array_push($this->tagStack, $name);
    }
   
    function dataHandler($parser, $data)
    {
        if(!empty($data))
        {
            if(isset($this->currTag['data']))
                $this->currTag['data'] .= $data;
            else
                $this->currTag['data'] = $data;
        }
    }
   
    function endHandler($parser, $name)
    {
        $this->currTag =& $this->document;
        array_pop($this->tagStack);
       
        for($i = 0; $i < count($this->tagStack); $i++)
        {
            $t =& $this->currTag[$this->tagStack[$i]];
            $this->currTag =& $t[count($t)-1];
        }
    }
}

?>
