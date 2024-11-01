<?php
/**
 * @package ShareTimetable
 * @version 1.0
 */ 
/*
Plugin Name: ShareTimetable Booking
Plugin URI: http://www.sharetimetable.com/demo/plugin/
Description: On-site Booking using the common resource scheduling tool from ShareTimetable.com.
Author: Share Timetable.
Version: 1.2
Author URI: ShareTimetable.com
License: GPLv2
*/

require_once( 'iam_calendar.php') ;

$sst = new ShareTimetablePlugIn();



class ShareTimetablePlugIn
{

	var $o; // options
	var $url;
	var $api_url = "http://app.sharetimetable.com/api";
	var $debug = 0;

	/**
	 * Constructor
	 */	
	function ShareTimetablePlugIn()
	{
	
		// for first time load, default options:
		$defualt_options = array(
					"progress_bar_en"		=> 1,
					"progress_bar_background_color" 	=> '#8A92DA',
					"progress_bar_selected_color" 		=> '#010400',
					"progress_bar_not_selected_color" 	=> '#C0C0C0',					
					"p1_progress_bar_str"	=> "Resources",
					"p2_progress_bar_str"	=> "Time Slot",
					"p3_progress_bar_str"	=> "Details",
					"p4_progress_bar_str"	=> "End",
					"completion_text_str"	=> "Thank you for your booking.",
					"next_str"				=> "Next",
					"name_str"				=> "Name",
					"phone_en"				=> 1,
					"phone_str"				=> "Phone Number",
					"email_en"				=> 1,
					"email_str"				=> "Email",
					"comment_en"			=> 1,
					"comment_str"			=> "Additional Comments",
					"submit_str"			=> "Submit",
					"time_str"				=> 'g:i a',
					"date_str"				=> 'j-M-Y',
					"calendar_syle"			=> 'full',
					"cal_show_today" 		=> 1,
					"cal_allow_sat" 		=> 1, 
					"cal_allow_sun" 		=> 1,
					"cal_num_of_days"		=> 7,
					
					"show_link"				=> 1 );
	
	
		// get options from DB
		$this->o = get_option('sharetimetable', $defualt_options );
		
		
		// options page in menu
		add_action('admin_menu', array( &$this, 'addOptionsPage'));

		add_shortcode('Share-Timetable-booking', array( &$this, 'ShareTimetableShortcode'));
		// add stylesheet
		add_action('wp_head', array( &$this, 'addStyle'));
		
		/* Register our stylesheet. */
        wp_register_style( 'CalendarStylesheet', plugins_url('iam_styles.css', __FILE__) );

		add_action( 'admin_enqueue_scripts', 'mw_enqueue_color_picker' );
		function mw_enqueue_color_picker( $hook_suffix ) 
		{
			// first check that $hook_suffix is appropriate for your admin page
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'my-script-handle', plugins_url('my-script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
		}
	}


	/**
	 * parses parameters
	 *
	 * @param string $atts parameters
	 */
	function ShareTimetableShortcode( $atts )
	{
	/*	extract(shortcode_atts(array(
			'item' => 'none',		
		), $atts));
		*/
		
		$type = array_key_exists('type',$_GET) ? $_GET['type'] : $_POST['type'];

		$this->url = get_permalink();
		if( $this->url === false )
		{
			$url = explode( '?', $_SERVER["REQUEST_URI"] );
			$this->url = $url[0];
		}
		else
		{
			if ( strpos( $this->url, '?' ) === false )
			{
				$this->url .= '?';
			}
			else
			{
				$this->url .= '&';
			}
		}
		
		// make sure require fiels exist.
		if( (int)$type == 3 && 
			( $_POST['Name'] == '' || 
				( $_POST['Phone'] == '' && $this->o['phone_en'] > 0 ) 
			) )			
		{
			$type = 2;
		}		
		
		switch( (int)$type )
		{
			case 0:
			return $this->TimetableResources( $this->progress_bar( $type ) );
			case 1:
			return $this->ResourceOpennings( $this->progress_bar( $type ) );
			case 2:
			return $this->BookingDetails( $this->progress_bar( $type ) );
			case 3:
			return $this->submit( $this->progress_bar( $type ) );
			default:
			return "Failure $type";
		}
		
		$progress_bar = $this->o["progress_bar_en"] ? $this->progress_bar( $type ) : '' ;
		
		return '<div id="sharetimetable">'.
				$progress_bar . 
				$out .
				'</div>';
		
	}
	
	///////////////////////////////////////////////////////////////////////////////////
	function progress_bar( $type )
	{
		$phase1 = '<span class="grayed">'.$this->o["p1_progress_bar_str"].'</span>';
		$phase2 = '<span class="grayed">'.$this->o["p2_progress_bar_str"].'</span>';
		$phase3 = '<span class="grayed">'.$this->o["p3_progress_bar_str"].'</span>';
		$phase4 = '<span class="grayed">'.$this->o["p4_progress_bar_str"].'</span>';
		
		switch( $type )
		{
			case 3:
			$phase4 = '<span class="selected">'.$this->o["p4_progress_bar_str"].'</span>';
			break;			
			case 2:
			$phase3 = '<span class="selected">'.$this->o["p3_progress_bar_str"].'</span>';
			break;
			case 1:
			$phase2 = '<span class="selected">'.$this->o["p2_progress_bar_str"].'</span>';
			break;
			case 0:
			$phase1 = '<span class="selected">'.$this->o["p1_progress_bar_str"].'</span>';
			break;			
		}
			
		$out = '<p class="progress_bar">';
		
		$out.= $phase1.$phase2.$phase3.$phase4;
		$out.= "</p>
		";
		return $out;
	
	}
			
	/////////////////////////////////////////////////////////////////////////////////////		
	function TimetableResources()
	{
		$xmlstr = file_get_contents( $this->api_url."/table_information/".$this->o["public_id_str"]."/" );
		$data = new SimpleXMLElement($xmlstr);
		/* TODO:
		if( $data->result == '0' )
		{
			return '<div>'.$data->error.'</div>';
		}
		*/
		
		$cal = "<span id=\"calendar\">".$this->calendar( $data->timezone, $data->DLS, $this->o["calendar_syle"] ).'</span>';
		
		$string = '
		<h2>%s</h2>
		<h3>%s</h3>
		';
		
		$title = sprintf( $string, $data->name, $data->info ); 
		$power = '';
		if( $this->o["show_link"] ) 
		{
			$power = '<strong style="font-size: smaller;">Powered by <a href="http://www.sharetimetable.com" target="_blank">
					<span style="color: #75A54B;">share</span> 
					<span style="color: #F18359;">Timetable</span></a><strong>';
		}
		
		$out = '<span id="resources_list">
		<ul style="list-style-type: none;">
		';
		
		$resource_str = '
		<li>
		<input type="radio" name="res_id" value="%s"%s/><strong>%s</strong>
		%s 
		</li>'; 
		
		$first = true;
		foreach( $data->resources->resource as $res )
		{
			$cheched = ' checked';
			if( false == $first )
			{
				$cheched = '';
			}
			$first = false;
			$out .= sprintf( $resource_str, $res->id, $cheched, $res->name, $res->info);
		}
		$out .= '
		</ul>
		</span>
		';
		
		$content = 
		'		
		<table><tr><td>
		<form action="'.get_permalink().'" method="post">
		<input type="hidden" name="type" value="1"/>'."
		$cal
		</td><td>
		$out
		</td></tr>
		<tr><td>
		$power
		</td><td>".'
		<input type="submit" value="'.$this->o["next_str"].'"/>
		</form>
		</td></tr></table>
		';
		


		return  $title . $content .
				( $this->debug ? "<pre>$xmlstr</pre>" : '' );
	}
	
	/////////////////////////////////////////////////////////////////////////////////////
	function ResourceOpennings()
	{
		$res_id = $_POST['res_id'];
		
		if( array_key_exists('calendar',$_POST) )
		{
			$date = explode('-', $_POST['calendar'] );
			
							//		monthe,   day,      year
			$time = mktime( 0,0,0, $date[1], $date[2], $date[0] );
		}
		else
		{
			$time = time();
			$time = $time - ($time % ( 24*60*60)); 
		}
		
		$url = $this->api_url."/timeslots/".$this->o["public_id_str"]."/$res_id/$time/";
		$xmlstr = file_get_contents( $url );
		$data = new SimpleXMLElement($xmlstr);
		
		$string = "
		<h3>%s</h3>
		<p>%s</p>
		";
		
		$title = sprintf( $string, $data->name, $data->info ); 
		$out = '<h4>'.date( $this->o["date_str"], (int)$time).'</h4>';
		$out .= '<span id="timeslot_list">
		<ul style="list-style-type: none;">';
	
		foreach( $data->timeslots->time as $time )
		{
			$link = date( $this->o["time_str"], (int)$time->start);
			if ( (int)$time->available != 0 )
			{			
				$form =	'<form action="'.$_SERVER["REQUEST_URI"].'" method="post">
						<input type="hidden" name="type" value="2"/>
						<input type="hidden" name="res_id" value="'.$res_id.'"/>
						<input type="hidden" name="start" value="'.(int)$time->start.'"/>
						<input type="hidden" name="end" value="'.(int)$time->end.'"/>
						<input type="submit" value="'. date( $this->o["time_str"], (int)$time->start).'"/>
						</form>' ;
				$link = '<strong><a href="'.$this->url."type=2&amp;res_id=$res_id&amp;start=".
							$time->start."&amp;end=".$time->end.'">'.
							date( $this->o["time_str"], (int)$time->start)."</a></strong>";
			}
			
			$out .= "<li>
					$link
					</li>
			";
		}
		
		$out .= "
		</ul>
		</span>";
		
		return $title. $out . ( $this->debug ? $url."<pre>$xmlstr</pre>" : '' );
	}
	
	function BookingDetails()
	{
	
		$res_id = array_key_exists('res_id',$_GET) ? $_GET['res_id'] : $_POST['res_id'];
		$start = array_key_exists('start',$_GET) ? $_GET['start'] : $_POST['start'];
		$end = array_key_exists('end',$_GET) ? $_GET['end'] : $_POST['end'];
		$name = array_key_exists('Name', $_POST) ? $_POST['Name'] : '';
		$name_err = ( array_key_exists('Name', $_POST) &&  $_POST['Name'] == '' );
		$phone = array_key_exists('Phone', $_POST) ? $_POST['Phone'] : '';
		$phone_err = ( array_key_exists('Phone', $_POST) &&  $_POST['Phone'] == '' );
		$email = array_key_exists('Email', $_POST) ? $_POST['Email'] : '';
		$comment = array_key_exists('Comment', $_POST) ? $_POST['Comment'] : '';
		
		$form =	'<form action="'.get_permalink().'" method="post">
						<input type="hidden" name="type" value="3"/>
						<input type="hidden" name="res_id" value="'.$res_id.'"/>
						<input type="hidden" name="start" value="'.$start.'"/>
						<input type="hidden" name="end" value="'.$end.'"/>
						<p>
						<label for="Name"'.($name_err?' class="error"':'').'>'. $this->o["name_str"] .'*</label>
						</br>
						<input type="text" name="Name" value="'.$name.'"/>
						</p>';
						
		if( $this->o["phone_en"])
		{
			$form .= '	
						<p>
						<label for="Phone"'.($phone_err?' class="error"':'').'>'. $this->o["phone_str"] .'*</label>
						</br>
						<input type="text" name="Phone" value="'.$phone.'"/>
						</p>';
		}
		if( $this->o["email_en"])
		{
			$form .= '	
						<p>
						<label for="Email">'. $this->o["email_str"] .'</label>
						</br>
						<input type="text" name="Email" value="'.$email.'"/>
						</p>';
		}
		if( $this->o["comment_en"])
		{
			$form .= '	
						<p>
						<label for="Comment">'. $this->o["comment_str"] .'</label>
						</br>
						<textarea rows="4" cols="50" name="Comment" >'.$comment.'</textarea>
						</p>';
		}
				
		$form .= '		
						<p>
						<input type="submit" value="'. $this->o["submit_str"] .'"/>
						</p>
						</form>' ;
		
		return $form;
	}
	
	
	function submit()
	{
		$url = $this->api_url."/submit/".$this->o["public_id_str"].'/';

		$params = array(
			'method' => 'POST',
			'timeout' => 450,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' =>  $_POST,
			'cookies' => array()
		);
	
		$ret = wp_remote_post( $url, $params); 
		
		if(  $this->debug )
		{
			return print_r($ret);
		}
		$data = new SimpleXMLElement($ret['body']);
		
		
		$out = "
				<h3>";
		
		if( 1 == $data->result )
		{
			$out .= $this->o["completion_text_str"];
		}
		else
		{
			$out .= '<span class="error">Error: '.$data->error.'</span>';
		}
		$out .= '</h3>';
		
		return $out.( $this->debug ? "<pre>".print_r($ret)."</pre>" : '' );
	
	}
	
	function calendar($timezone, $DLS, $style )
	{
		$timetable_time = time() + ($timezone * 3600) + ($DLS * 3600);
	
		if( $style == 'full' )
		{
			/**
			* Include the IAM_Calendar Class definition   
			*/
			$month = array_key_exists('Month',$_GET) ? $_GET['Month'] : date('n', $timetable_time);
			$year = array_key_exists('Year',$_GET) ? $_GET['Year'] : date('Y', $timetable_time);
			$calendar = new IAM_Calendar();
			$calendar->SetMonth($month); 
			$calendar->SetYear($year);
			$calendar->SetUrl($this->url);
			$calendar->addHoliday(date('Y', $timetable_time), date('n', $timetable_time),  date('j', $timetable_time), 'Today');
			return $calendar->drawMonth(false,true,true,true,true,true);
		}
		else
		{
			$first = 1;
			$out = "\n";
			for( $i=0; $i < $this->o["cal_num_of_days"]; $i++, $timetable_time += 60*60*24 )
			{
				$disabled = '';
				$checked = '';
				if( $this->o["cal_show_today"] == 0 && $i == 0 ) continue;
				if( $this->o["cal_allow_sat"] == 0 && date('D', $timetable_time) == 'Sat' ) $disabled = 'disabled';
				if( $this->o["cal_allow_sun"] == 0 && date('D', $timetable_time) == 'Sun' ) $disabled = 'disabled';
				if( 1 == $first && '' == $disabled )	
				{ 
					$checked = 'checked';
				}
				$out .= '<li><input type="radio" class="inputStyle" name="calendar" value="'.date('Y-n-j', $timetable_time).'"'." $disabled$checked/>&nbsp;".
						str_replace( ' ','&nbsp;',date( $this->o["date_str"], $timetable_time)).
						"</li>\n";
				if( $checked == 'checked')
				{
					$first = 0;
				}
			}
			return '<ul style="list-style-type: none;">'.$out.'</ul>';
		}
	}
	
	function update_if_exist( $o_id )
	{
		if( array_key_exists( $o_id, $_POST ) )
		{
			$this->o[ $o_id ] = $_POST[ $o_id ];
		}
	}
	
	
	/*********************************************************************************8**
	 * shows options page
	 */
	function optionsPage()
	{	

		if (!current_user_can('manage_options'))
			wp_die(__('Sorry, but you have no permissions to change settings.'));
			
			
		// save data
		if ( isset($_POST['stt_save']) )
		{
			$this->update_if_exist( "public_id_str" );
			$this->update_if_exist( "progress_bar_en" );
			$this->update_if_exist( "progress_bar_background_color" );
			$this->update_if_exist( "progress_bar_selected_color" );
			$this->update_if_exist( "progress_bar_not_selected_color" );
			$this->update_if_exist( "p1_progress_bar_str" );
			$this->update_if_exist( "p2_progress_bar_str" );
			$this->update_if_exist( "p3_progress_bar_str" );
			$this->update_if_exist( "p4_progress_bar_str" );
			$this->update_if_exist( "completion_text_str" );
			$this->update_if_exist( "next_str" );
			$this->update_if_exist( "name_str" );
			$this->update_if_exist( "phone_en" );
			$this->update_if_exist( "phone_str" );
			$this->update_if_exist( "email_en" );
			$this->update_if_exist( "email_str" );
			$this->update_if_exist( "comment_en" );
			$this->update_if_exist( "comment_str" );
			$this->update_if_exist( "submit_str" );
			$this->update_if_exist( "time_str" );
			$this->update_if_exist( "date_str" );
			$this->update_if_exist( "calendar_syle" );
			$this->update_if_exist( "cal_show_today" );
			$this->update_if_exist( "cal_allow_sat" );
			$this->update_if_exist( "cal_allow_sun" );
			$this->update_if_exist( "cal_num_of_days" );
			$this->update_if_exist( "show_link" );
			update_option('sharetimetable', $this->o);
		}
		
		$fake_tm = (int)mktime( 18, 34, 20, 11, 23, 2013 );
		$time_selection = array (
			'g:i a' => date( 'g:i a', $fake_tm ),
			'G:i' => date( 'G:i', $fake_tm )
		);
		
		$time_option = "<select name=\"time_str\" id=\"time_str\">\n";
		foreach( $time_selection as $value => $opt )
		{
			$time_option .= "<option value=\"$value\"".( $value==$this->o['time_str']?' selected':'').
							">$opt</option>\n";
		}
		$time_option .= "</select>";
		
		$date_selection = array ( 
			'm.d.y' =>   date( 'm.d.y', $fake_tm ),
			'd.m.y' =>   date( 'd.m.y', $fake_tm ),
			'm/d/y' =>   date( 'm/d/y', $fake_tm ),
			'd/m/y' =>   date( 'd/m/y', $fake_tm ),
			'Y-m-d' =>   date( 'Y-m-d', $fake_tm ),
			'j, n, Y' => date( 'j, n, Y', $fake_tm ),
			'F j, Y' =>  date( 'F j, Y', $fake_tm ),
			'l M jS, Y' =>date( 'l M jS, Y', $fake_tm ),
			'j-M-Y' =>  date( 'j-M-Y', $fake_tm ),
			'D j M, y' =>date( 'D j M, y', $fake_tm ),
			'l F j, Y' =>date( 'l F j, Y', $fake_tm )
		);
		
		$date_option = "<select name=\"date_str\" id=\"date_str\">\n";
		foreach( $date_selection as $value => $opt )
		{
			$date_option .= "<option value=\"$value\"".($value==$this->o['date_str']?' selected':'').
							">$opt</option>\n";
		}
		$date_option .= "</select>";
		
		
		$start = time();
		
		$url = $this->api_url."/test/".$this->o["public_id_str"]."/";
	
		$xmlstr = file_get_contents( $url );
		$data = new SimpleXMLElement($xmlstr);
		
		$sub_page = array_key_exists( 'sub_page', $_GET ) ? $_GET['sub_page'] : 'settings';
			
		// show page
		?>
		<div class="wrap">
			<h2>Share Timetable plug-in</h2>
			<h4>Steps to activate this booking plugin</h4>
			<ol>
				<li>Register to <a href="http://www.sharetimetable.com" target="_blank">ShareTimetable.com</a> and create a timetable.</li>
				<li>Place the following short code in any page or post in your site<pre>[Share-Timetable-booking]</pre></li>
				<li>Fill your Timetable Public ID in the folloing box and save.</li>
			</ol>
			<form action="<?php menu_page_url('sharetimetable'); echo "&amp;sub_page=$sub_page"; ?>" method="post">
			<table class="form-table">
			<tr valign="top">
			<th scope="row"><label for="public_id_str">Timetable Public ID.</label></th>
			<td><input name="public_id_str" type="text" value="<?php echo $this->o["public_id_str"] ?>" /></td>
			</tr>
			<tr valign="top">
			<th scope="row" colspan="2">
			Timetable Name: <strong><a href="http://app.sharetimetable.com/timetable/set_default/<? echo $this->o["public_id_str"] ?>" target="_blank">
			<?php echo $data->name ?></a></strong>. External API is <strong><?php echo ($data->active==0)?'not ':'' ?>active.</strong><br />
			Link to <a href="http://app.sharetimetable.com/schedule/index/<? echo $this->o["public_id_str"] ?>" target="_blank">Timetable manager page.</a><br />
			API query time: 
		<?php  
			echo (time() - $start). 'ms.<br />';
		if( $data->active==0 )
			{
				echo '<br /> Goto the Timetable <a href="http://app.sharetimetable.com/schedule/index/'.$this->o["public_id_str"].'" target="_blank">
						Manage</a> page and set External API: On.<br />';
			}
		?>
			</th>
			</tr>
			<tr valign="top">
			<th scope="row" colspan="2">
			<h2 class="nav-tab-wrapper">
			&nbsp;
			<a class="nav-tab<?php if($sub_page == 'settings') echo ' nav-tab-active'; ?>" 
				href="<?php menu_page_url('sharetimetable')?>&amp;sub_page=settings">Settings</a>
			<a class="nav-tab<?php if($sub_page == 'progress_bar') echo ' nav-tab-active'; ?>" 
				href="<?php menu_page_url('sharetimetable')?>&amp;sub_page=progress_bar">Progress Bar</a>
			<a class="nav-tab<?php if($sub_page == 'calendar') echo ' nav-tab-active'; ?>" 
				href="<?php menu_page_url('sharetimetable')?>&amp;sub_page=calendar">Calendar</a>
			</h2>
			</th>
			</tr>
		<?php
			if( $sub_page == 'progress_bar' )
			{
		?>			
			<tr valign="top">
			<th scope="row"><strong><label for="p1_progress_bar_str">Show progress bar</label></strong></th>
			<td>
			<input type="radio" name="progress_bar_en" value="1" <?php if ( $this->o["progress_bar_en"] == 1 ) echo 'checked' ?>/> Yes 
			<input type="radio" name="progress_bar_en" value="0" <?php if ( $this->o["progress_bar_en"] == 0 ) echo 'checked' ?>/> No 
			</td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="p1_progress_bar_str">Phase 1: Resourcs List</label></th>
			<td><input name="p1_progress_bar_str" type="text" value="<?php echo $this->o["p1_progress_bar_str"] ?>" /></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="p2_progress_bar_str">Phase 2: Time selection</label></th>
			<td><input name="p2_progress_bar_str" type="text" value="<?php echo $this->o["p2_progress_bar_str"] ?>" /></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="p3_progress_bar_str">Phase 3: Client details</label></th>
			<td><input name="p3_progress_bar_str" type="text" value="<?php echo $this->o["p3_progress_bar_str"] ?>" /></td>
			</tr>
			<th scope="row"><label for="p4_progress_bar_str">Phase 4: Done</label></th>
			<td><input name="p4_progress_bar_str" type="text" value="<?php echo $this->o["p4_progress_bar_str"] ?>" /></td>
			</tr>
			<tr>
			<td scope="row" colspan="2">
				<div> <!-- progress bar -->
				<p> Progress Bar Example</p>
			<?php
				echo $this->EchoStyle();
				echo $this->progress_bar(2);
			?>
				</div>
			</td>
			</tr>			
			<tr valign="top">
			<th scope="row" colspan="2"><h3>Style</h3>
			
			</th>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="progress_bar_background_color">background Color</label></th>
			<td><input type="text" name="progress_bar_background_color" value="<?php echo $this->o["progress_bar_background_color"] ?>" class="my-color-field" /></td> 
			</tr>
			<tr valign="top">
			<th scope="row"><label for="progress_bar_selected_color">Current Phase Color</label></th>
			<td><input type="text" name="progress_bar_selected_color" value="<?php echo $this->o["progress_bar_selected_color"] ?>" class="my-color-field" /></td> 
			</tr>
			<tr valign="top">
			<th scope="row"><label for="progress_bar_not_selected_color">Other Phase Color</label></th>
			<td><input type="text" name="progress_bar_not_selected_color" value="<?php echo $this->o["progress_bar_not_selected_color"] ?>" class="my-color-field" /></td> 
			</tr>
			
		<?php
		
			}
			if( $sub_page == 'settings' )
			{
		?>			
			<tr valign="top">
			<th scope="row"" colspan="2"><h4>Phase 1: Resourcs List</h4></th>
			</tr>			
			<tr valign="top">
			<th scope="row"><label for="next_str">Next Button label</label></th>
			<td><input name="next_str" type="text" value="<?php echo $this->o["next_str"] ?>" /></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="phone_str">Show "Power By" link</label></th>
			<td>
			<input type="radio" name="show_link" value="1" <?php if ( $this->o["show_link"]==1 ) echo 'checked' ?>/> Yes 
			<input type="radio" name="show_link" value="0" <?php if ( $this->o["show_link"]==0 ) echo 'checked' ?>/> No 
			<div style="font-size: smaller;">We provide this valuable service for FREE and ask you to help us by presenting a small link on your site next to the booking form.<div>
			</td>
			</tr>
			<th scope="row"" colspan="2">
			<h4>Phase 3: Client details</h4>
			Labels for the form entry boxes:
			</th>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="name_str">Name</label></th>
			<td><input name="name_str" type="text" value="<?php echo $this->o["name_str"] ?>" /></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="phone_str">Phone number</label></th>
			<td>
			<input name="phone_str" type="text" value="<?php echo $this->o["phone_str"] ?>" />
			Show: 
			<input type="radio" name="phone_en" value="1" <?php if ( $this->o["phone_en"]==1 ) echo 'checked' ?>/> Yes 
			<input type="radio" name="phone_en" value="0" <?php if ( $this->o["phone_en"]==0 ) echo 'checked' ?>/> No 
			</td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="email_str">Email Address</label></th>
			<td>
			<input name="email_str" type="text" value="<?php echo $this->o["email_str"] ?>" />
			Show: 
			<input type="radio" name="email_en" value="1" <?php if ( $this->o["email_en"]==1 ) echo 'checked' ?>/> Yes 
			<input type="radio" name="email_en" value="0" <?php if ( $this->o["email_en"]==0 ) echo 'checked' ?>/> No 
			</tr>
			<tr valign="top">
			<th scope="row"><label for="comment_str">Comment</label></th>
			<td>
			<input name="comment_str" type="text" value="<?php echo $this->o["comment_str"] ?>" />
			Show: 
			<input type="radio" name="comment_en" value="1" <?php if ( $this->o["comment_en"]==1 ) echo 'checked' ?>/> Yes 
			<input type="radio" name="comment_en" value="0" <?php if ( $this->o["comment_en"]==0 ) echo 'checked' ?>/> No 
			</tr>
			<tr valign="top">
			<th scope="row"><label for="submit_str">Submit buttom</label></th>
			<td><input name="submit_str" type="text" value="<?php echo $this->o["submit_str"] ?>" /></td>
			</tr>			
			<tr valign="top">
			<th scope="row"" colspan="2"><h4>Phase 4: Done</h4></th>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="completion_text_str">Completion text</label></th>
			<td><input name="completion_text_str" type="text" value="<?php echo $this->o["completion_text_str"] ?>" /></td>
			</tr>			
			
			<tr valign="top">
			<th scope="row"" colspan="2"><h4>Time and Date format</h4></th>
			</tr>			
			<tr valign="top">
			<th scope="row"><label for="time_str">Time format</label></th>
			<td><?php echo $time_option; ?></td>
			</tr>			

			<tr valign="top">
			<th scope="row"><label for="date_str">Date format</label></th>
			<td><?php echo $date_option; ?></td>
			</tr>			
		<?php
		
			}
			if( $sub_page == 'calendar' )
			{
		?>
			
			<tr valign="top">
			<th>
			<h3><input type="radio" name="calendar_syle" value="full" <?php if ( $this->o["calendar_syle"]=="full" ) echo 'checked' ?>/> Full Calendar</h3>
			<div class="postbox" style="display: block;">
			<h3 class="hndle">Example</h3>
			<div class="inside">
			<img src="<?php echo plugins_url('full_calendar.jpg', __FILE__ ); ?>" alt="full calendar">
			</div>
			</th>
			<td>
			<h3><input type="radio" name="calendar_syle" value="simple" <?php if ( $this->o["calendar_syle"]=="simple" ) echo 'checked' ?>/> Simple Days select</h3>
			<div class="postbox" style="display: block;">
			<h3 class="hndle">Example</h3>
			<div class="inside">
			<?php echo $this->calendar( 0,0, 'simple' ); ?>
			</div>
			</div>
			<table class="form-table">
			<tr valign="top">
			<th scope="row"><strong>Show Today: </strong></th>
			<td>
			<input type="radio" name="cal_show_today" value="1" <?php if ( $this->o["cal_show_today"]==1 ) echo 'checked' ?>/> Yes 
			<input type="radio" name="cal_show_today" value="0" <?php if ( $this->o["cal_show_today"]==0 ) echo 'checked' ?>/> No 
			</td>
			</tr>
			<tr valign="top">
			<th scope="row"><strong>Allow Saturday: </strong></th>
			<td>
			<input type="radio" name="cal_allow_sat" value="1" <?php if ( $this->o["cal_allow_sat"]==1 ) echo 'checked' ?>/> Yes 
			<input type="radio" name="cal_allow_sat" value="0" <?php if ( $this->o["cal_allow_sat"]==0 ) echo 'checked' ?>/> No 
			</td>
			</tr>
			<tr valign="top">
			<th scope="row"><strong>Allow Sunday: </strong></th>
			<td>
			<input type="radio" name="cal_allow_sun" value="1" <?php if ( $this->o["cal_allow_sun"]==1 ) echo 'checked' ?>/> Yes 
			<input type="radio" name="cal_allow_sun" value="0" <?php if ( $this->o["cal_allow_sun"]==0 ) echo 'checked' ?>/> No 
			</td>
			</tr>
			<tr valign="top">
			<th scope="row"><strong>Number of days: </strong></th>
			<td>
				<select name="cal_num_of_days">
				<?php 
				$days = '';
				for( $i = 5; $i < 31; $i++ )
				{
					$days .= "<option value=\"$i\"".($i==$this->o['cal_num_of_days']?' selected':'').
							">$i</option>\n";
				}
				echo $days ;
				?>
				</select>
			</td>
			</tr>
			<tr valign="top">
			<th scope="row"><strong><label for="date_str">Date format</label></strong></th>
			<td><?php echo $date_option; ?></td>
			</tr>			

			</table>

			</td>
			</tr>	
			
			
		<?php
		
			}
		?>
			<tr valign="top">
			<th scope="row">
			<p class="submit">
				<input name="stt_save" class="button-primary" value="<?php _e('Save Changes'); ?>" type="submit" />
			</p></th></tr>
			</table>
			</form>
						
		
		
		</div>
		<p>This Plugin is in Beta development stage. Please 
		<a href="http://www.sharetimetable.com/contact/" target="_blank">contact us</a> with any issue.
		
		<?php
	}


	/**
	 * adds admin menu
	 */
	function addOptionsPage()
	{
		$menutitle = 'Share Timetable';
		add_plugins_page('Share Timetable', $menutitle, 9, 'sharetimetable', array( &$this, 'optionsPage'));
		
	}

	/**
	 * adds custom style to page
	 */
	function addStyle()
	{
		wp_enqueue_style( 'CalendarStylesheet' );
		echo $this->EchoStyle();
	}

	function EchoStyle()
	{
		return "\n<style type=\"text/css\" id=\"sharetimetable-style\">\n". 
				$this->defaultStyle()."
				.selected {	color: ".$this->o["progress_bar_selected_color"]."; }
				.grayed {   color: ".$this->o["progress_bar_not_selected_color"]."; }
				.progress_bar {	background-color: ".$this->o["progress_bar_background_color"]."; }
				</style>\n";	

	}
	function defaultStyle()
	{
		return "
#resources_list INPUT
{
	border: 1px solid #808080;
	background-color: #FEFEFE;
}
.selected {	margin: 7px; }
.grayed { margin: 7px; }
.progress_bar
{
	margin: 6px;
	padding: 8px;
	font-size: 12pt;
}
.error { color: red; }
";
	}
	
	
}
