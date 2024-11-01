<?php
/*
Plugin Name: TwitterBrandSponsors
Plugin URI: http://mashable.com/sociableads/twitterbrandsponsors/
Description: A WordPress Plugin to integrate Twitter Brand Sponsors.
Version: 1.1
Author: Dan Zarrella
Author URI: http://danzarrella.com
*/

$tbs_version = 1.1;
$table_name = $table_name = $wpdb->prefix .'TwitterBrandSponsors';
if ( !defined('WP_CONTENT_DIR') )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
$TwitterBrandSponsors_dir = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';
add_action('widgets_init', 'TwitterBrandSponsors_widget_innit');
add_action('admin_menu', 'TwitterBrandSponsors_menu');
add_action( 'TwitterBrandSponsors_cron_hook', 'TwitterBrandSponsors_updateTweets' );
add_filter('cron_schedules', 'more_reccurences');
add_action('wp_head', 'TwitterBrandSponsors_header');
add_action('wp_footer', 'TwitterBrandSponsors_footer');
$update_time = get_option('TwitterBrandSponsors_update_time');	
if (!wp_next_scheduled('TwitterBrandSponsors_cron_hook')) {
	wp_schedule_event( time(), $update_time, 'TwitterBrandSponsors_cron_hook' );
}

function TwitterBrandSponsors_footer() {
	echo '<script src="/wp-content/plugins/'.plugin_basename(dirname(__FILE__)).'/tbs-js.php"></script>';
}

function TwitterBrandSponsors_header() {

	$style = get_option('TwitterBrandSponsors_css_style');
	
	if( ($style!='none') and ($style) ) {
		echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/'.plugin_basename(dirname(__FILE__)).'/tbs-'.$style.'.css" />' . "\n";
	}
	else {		
	}
}

function TwitterBrandSponsors_install() {
	global $wpdb, $tbs_version, $table_name;
    	 
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
   $sql = "CREATE TABLE `".$table_name."` (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  dt int(100) NOT NULL,
  link varchar(255) NOT NULL,
  avatar varchar(255) NOT NULL,
  tweet varchar(255) NOT NULL,
  author varchar(255) NOT NULL,
   primary KEY id (id),
   UNIQUE KEY author (link),
);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);	
   }   

   $count_name = $wpdb->prefix .'TwitterBrandSponsors_count';
      if($wpdb->get_var("SHOW TABLES LIKE '$count_name'") != $count_name) {
   $sql = "CREATE TABLE `".$count_name."` (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  dt int(100) NOT NULL,
  link varchar(255) NOT NULL,
  sponsor varchar(255) NOT NULL,
  ip varchar(100),
  primary KEY id (id)
);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);	
   }   
}
function TwitterBrandSponsors_out($args) {
	global $wpdb, $table_name, $TwitterBrandSponsors_dir;
	$count_name = $wpdb->prefix .'TwitterBrandSponsors_count';
	$link =  $args['url'];
	$sponsor = $args['sponsor'];
	$ip = $_SERVER['REMOTE_ADDR'];
	$dt = mktime();
	$insert_sql = "insert into $count_name (link, sponsor, ip, dt) values ('$link', '$sponsor', '$ip', '$dt')";
	wp_redirect ( $link );
	$wpdb->query( $insert_sql );		
}

function TwitterBrandSponsors_display() {
	global $wpdb, $table_name, $TwitterBrandSponsors_dir;
	echo '<div id="TwitterBrandSponsors_div">';
	echo '<span id="TwitterBrandSponsors_title">Twitter Brand Sponsors</span>';	
	echo "<p id='TwitterBrandSponsors_display_text'>". get_option('TwitterBrandSponsors_display_text')."</p>";	
	TwitterBrandSponsors_displaySponsors();
	echo "</div>";
}

function TwitterBrandSponsors_displaySponsors() {
	global $wpdb, $table_name, $TwitterBrandSponsors_dir;
	$jump_url = get_bloginfo('wpurl').'/wp-content/plugins/'.plugin_basename(dirname(__FILE__)).'/'.'tbs-out.php?url=';
	$sponsors = getAllSponsors();
	shuffle($sponsors);
	
	echo "<table cellspacing='0' id='TwitterBrandSponsors'>\n";
	foreach($sponsors as $sponsor) {
		if(strlen($sponsor)>2) {
			$query = "select * from $table_name where author='$sponsor' order by dt desc limit 1";
			$buff = $wpdb->get_results($query);		
			foreach($buff as $line) {
				$line->tweet = strip_tags($line->tweet);
				$words = split(' ', $line->tweet);
				for($x=0; $x<count($words); $x++) {
					$word = $words[$x];
					if(stristr($word, 'http://')) {
			
						$words[$x] = "<a  onclick=\"return trackclick(this.href, this.name, '$sponsor', '$jump_url');\" href='$word' target='_blank'>$word</a>";						
					}
				}
				$line->tweet = join(' ', $words);
			
				echo "<tr><td><img src='$line->avatar' width='48'></td><td><a onclick=\"return trackclick(this.href, this.name, '$sponsor', '$jump_url');\" href='http://twitter.com/$line->author'><b>$line->author:</b></a> $line->tweet</td></tr>\n";
				}
		}	
	}
	echo "</table><span style='font-size:10px;' id='TwitterBrandSponsors_credit'><a href='http://mashable.com/sociableads'>Sociable Ads</a> by <a href='http://twitter.com/mashable'>Mashable</a> and <A href='http://twitter.com/danzarrella'>Dan Zarrella</a></span>\n";
}

function TwitterBrandSponsors_updateTweets() {
	global $table_name, $wpdb, $TwitterBrandSponsors_dir;
	
	$sponsors = getAllSponsors();
	if(count($sponsors)>0) {
		foreach($sponsors as $sponsor) {
			$tweet = array_shift(TwitterBrandSponsors_fetchTweet($sponsor));
			$insert_sql = "replace into $table_name (tweet, link, avatar, author, dt) values ('".mysql_real_escape_string($tweet['tweet'])."', '".mysql_real_escape_string($tweet['link'])."', '".mysql_real_escape_string($tweet['avatar'])."', '".mysql_real_escape_string($tweet['author'])."', $tweet[dt])";			
			 $wpdb->query( $insert_sql );		
		}
	}
}


function tbs_get($file) {
	if(function_exists('curl_init')) { 
    $curl_handle = curl_init();
    curl_setopt($curl_handle,CURLOPT_URL,"$file");
    curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);	
    $data = curl_exec($curl_handle);
    $error = curl_error($curl_handle);
    curl_close($curl_handle);
	if(empty($error)) {
		return $data;
	  }
	  else {
	  	return $error;	
	  }
	 }
	 else {
	 	return tb_get1($url);
	 }
}


function getAvatar($sponsor) {
	$data = tbs_get('http://twitter.com/users/show/'.$sponsor.'.xml');		
	$data = split('profile_image_url', $data);	
	$data = str_replace('>', '', $data[1]);
	$data = str_replace('</', '', $data);	
	return $data;
}

function TwitterBrandSponsors_fetchTweet($sponsor) {
	include_once(ABSPATH . WPINC . '/rss.php');
	$no_replies = get_option('TwitterBrandSponsors_no_replies');
	$single_hide = get_option('TwitterBrandSponsors_hide_'.$sponsor);
	if( ($no_replies!='true') and ($single_hide!='true') ) {
		$no_replies = false;
		$url = "http://search.twitter.com/search.atom?q=from:$sponsor&rpp=1";
	}
	else {
		$no_replies = true;
		$url = "http://search.twitter.com/search.atom?q=from:$sponsor&rpp=50";
	}
	$rss = fetch_rss($url);	
	$rss = $rss->items;	
	foreach($rss as $line) {	
		$ret['tweet'] = str_replace("\n", ' ', strip_tags($line['atom_content']));
		$ret['tweet'] = trim(str_replace('  ', ' ', $ret['tweet']));
		$ret['link'] = $line['link'];	
		$ret['author'] = split("\(", $line['author_name']);
		$ret['author'] = $ret['author'][0];
		$ret['avatar'] = $line['link_image'];
		if(stristr(tbs_get($ret['avatar']), 'NoSuchKey')) {		
			$ret['avatar'] = getAvatar($ret['author']);
		}		
		list($date, $time) = split('T', $line['published']);
		$time = str_replace('Z', '', $time);
		list($hour, $minute, $second) = split(':', $time);
		list($year, $month, $day) = split('-', $date);
		$year = trim(str_replace('<published>', '', $year));
		$ret['dt'] = mktime($hour, $minute, $second, $month, $day, $year);	
		$check_tweet = strip_tags($ret['tweet']);
				
		if( ($check_tweet[0]!='@') and ($no_replies=='true') ) {
			$return[] = $ret;
			return $return;
		}
		elseif($no_replies!='true') {
			$return[] = $ret;
			return $return;
		}
	}
	if(!is_array($return)) {
		$return = TwitterBrandSponsors_fetchTweetTimeline($sponsor);
	}
	return $return;
}

function TwitterBrandSponsors_fetchTweetTimeline($sponsor) {
	include_once(ABSPATH . WPINC . '/rss.php');
	$no_replies = get_option('TwitterBrandSponsors_no_replies');	
	$single_hide = get_option('TwitterBrandSponsors_hide_'.$sponsor);
	$url = "http://twitter.com/statuses/user_timeline/$sponsor.atom";
	if( ($no_replies!='true') and ($single_hide!='true') ) {
		$no_replies = false;		
	}
	else {
		$no_replies = true;		
	}
	$rss = fetch_rss($url);	
	$rss = $rss->items;	
	foreach($rss as $line) {	
		$ret['tweet'] = str_replace("\n", ' ', strip_tags($line['atom_content']));
		$ret['tweet'] = trim(str_replace('  ', ' ', $ret['tweet']));
		$ret['tweet'] = str_replace($sponsor.": ", '', $ret['tweet']);
		$ret['link'] = $line['link'];	
		$ret['author'] = split("\(", $line['author_name']);
		$ret['author'] = $sponsor;
		$ret['avatar'] = $line['link_image'];
		if(stristr(tbs_get($ret['avatar']), 'NoSuchKey')) {		
			$ret['avatar'] = getAvatar($ret['author']);
		}		
		list($date, $time) = split('T', $line['published']);
		$time = str_replace('Z', '', $time);
		list($hour, $minute, $second) = split(':', $time);
		list($year, $month, $day) = split('-', $date);
		$year = trim(str_replace('<published>', '', $year));
		$ret['dt'] = mktime($hour, $minute, $second, $month, $day, $year);	
		$check_tweet = strip_tags($ret['tweet']);	
		if( ($check_tweet[0]!='@') and ($no_replies=='true') ) {
			$return[] = $ret;
			return $return;
		}
		elseif($no_replies!='true') {
			$return[] = $ret;
			return $return;
		}
	}

	return $return;
}


function TwitterBrandSponsors_widget_innit() { if ( !function_exists('register_sidebar_widget') ) return;
	global $post, $wpdb, $table_name;
	
	
	
function TwitterBrandSponsors_widget_control() {			
	if($_POST['TwitterBrandSponsors_display_text']) {
		update_option('TwitterBrandSponsors_display_text', $_POST['TwitterBrandSponsors_display_text']);
	}
	$display_text = get_option('TwitterBrandSponsors_display_text');
	?><p>
	<label for='ts_favorite_max'>Display Text:
	<textarea name='TwitterBrandSponsors_display_text'><?php echo $display_text; ?></textarea></label></p><?php
}

	
function TwitterBrandSponsors_widget($args)
{ 
	global $wpdb, $table_name;
    extract($args);
	TwitterBrandSponsors_display();
}
register_widget_control(array('TwitterBrandSponsors', 'widgets'), 'TwitterBrandSponsors_widget_control', 200, 150);
register_sidebar_widget(array('TwitterBrandSponsors','widgets'), 'TwitterBrandSponsors_widget');

}

function TwitterBrandSponsors_menu() {
	add_options_page('TwitterBrandSponsors Options', 'TwitterBrandSponsors', 8, __FILE__, 'TwitterBrandSponsors_options');
}

function getAllSponsors() {
	for($y=1; $y<=10; $y++) {	
		$sp = get_option('TwitterBrandSponsors_sponsor_'.$y);
		if(strlen($sp)>2) {
			$sponsors[$y] = $sp;
		}
	}
	return $sponsors;
}


function TwitterBrandSponsors_options() {	
	global $wpdb;
	if(count($_POST)>0) { 	
		TwitterBrandSponsors_install(); 		
		for($y=1; $y<=10; $y++) {		
			update_option('TwitterBrandSponsors_sponsor_'.$y, $_POST['sponsor'.$y]);
		}			
		update_option('TwitterBrandSponsors_update_time', $_POST['update_time']);
		update_option('TwitterBrandSponsors_css_style', $_POST['css_style']);		
		wp_clear_scheduled_hook('TwitterBrandSponsors_cron_hook');
		wp_schedule_event( time(), $_POST['update_time'], 'TwitterBrandSponsors_cron_hook' );
				
		$tbs_display_text = get_option('TwitterBrandSponsors_display_text');
		update_option('TwitterBrandSponsors_display_text', $_POST['TwitterBrandSponsors_display_text']);
		
		if($_POST['no_replies']=='on') {
			update_option('TwitterBrandSponsors_no_replies', 'true');
		}
		else {
			update_option('TwitterBrandSponsors_no_replies', 'false');
		}
	}
	$css_style = get_option('TwitterBrandSponsors_css_style');
	$count_name = $wpdb->prefix .'TwitterBrandSponsors_count';
	$query = "select * from $count_name";
	$week = mktime()-604800;
			$buff = $wpdb->get_results($query);		
			foreach($buff as $line) {
				if(strlen($line->sponsor)>1) {
					if( ($click_dates[$line->sponsor]['start']==0) or ($click_dates[$line->sponsor]['start']>$line->dt) ) {
						$click_dates[$line->sponsor]['start']=$line->dt;
					}
					if( ($click_dates[$line->sponsor]['end']==0) or ($click_dates[$line->sponsor]['end']<$line->dt) ) {
						$click_dates[$line->sponsor]['end']=$line->dt;
					}
					if($line->dt>$week) {
						$weekly_clicks[$line->sponsor]++;
					}			
					$click_counts[$line->sponsor]++;
				}
			}
	
	$update_time = get_option('TwitterBrandSponsors_update_time');		
	
	
	if (!wp_next_scheduled('TwitterBrandSponsors_cron_hook')) {
		wp_schedule_event( time(), $update_time, 'TwitterBrandSponsors_cron_hook' );
	}
	
	$sponsors = getAllSponsors();
?>
<div class="wrap">
<h2>TwitterBrandSponsors</h2>
<form method="post" action="">
<?php wp_nonce_field('update-options'); ?>
<table class="form-table">
<tr valign="top">
<th scope="row">Update Sponsor Tweets Every:</th>
<td>
<select name='update_time'>
	<option value='10mins' <?php if($update_time=='10mins') { echo "SELECTED "; } ?>>10 Minutes</option>
	<option value='30mins'<?php if($update_time=='30mins') { echo "SELECTED "; } ?>>30 Minutes</option>
	<option value='hourly'<?php if($update_time=='hourly') { echo "SELECTED "; } ?>>Hour</option>
</select>
</td>
</tr>
	<tr valign="top">
<th scope="row">Text to display above Tweets:</th>
<td>
<textarea name='TwitterBrandSponsors_display_text'><?php echo get_option('TwitterBrandSponsors_display_text'); ?></textarea>
</td>
</tr>
	<tr valign="top">
<th scope="row">CSS Styling:</th>
<td>
<select name='css_style'>
	<option value='none' <?php if($css_style=='none') { echo "SELECTED "; } ?>>Default</option>
	<option value='gray'<?php if($css_style=='gray') { echo "SELECTED "; } ?>>Gray</option>
</select>
</td>
</tr>
	<tr valign="top">
<th scope="row">Hide all replies?</th>
<td>
<INPUT TYPE=CHECKBOX NAME="no_replies" <?php if(get_option('TwitterBrandSponsors_no_replies')=='true') { echo ' checked="yes" '; }?>>
</td>
</tr>

<?php
//print_r($click_dates);
for($x=1; $x<=10; $x++) {

if($_POST['hide_'.$sponsors[$x]]=='on') {
	$hide = 'true';
}
else {
	$hide = 'false';
}
update_option('TwitterBrandSponsors_hide_'.$sponsors[$x], $hide);
	
$since = date('m/d/Y', $click_dates[$sponsors[$x]]['start']);
$weekly = $weekly_clicks[$sponsors[$x]];
?>
<tr valign="top">
<th scope="row">Sponsor #<?php echo $x; ?></th>
<td><input type="text" name='sponsor<?php echo $x; ?>' value='<?php echo $sponsors[$x]; ?>'/>
&nbsp;&nbsp;Hide Replies? <INPUT TYPE=CHECKBOX NAME="hide_<?php echo $sponsors[$x]; ?>" <?php if(get_option('TwitterBrandSponsors_hide_'.$sponsors[$x])=='true') { echo ' checked="yes" '; }?>>
&nbsp;&nbsp;&nbsp;
<span style="font-size:10px;"><?php if($click_counts[$sponsors[$x]])  { echo $click_counts[$sponsors[$x]]." clicks since $since, $weekly in the past 7 days."; } else {echo 'No Clicks Yet'; } ?></span>
</td>
</tr>
<?php } ?>
</table>
<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>
<?php
}

function more_reccurences() {
	return array(
		'10mins' => array('interval' => 600, 'display' => 'Every 10 Minutes'),
		'30mins' => array('interval' => 1800, 'display' => 'Every 30 Minutes'),		
	);
}

?>
