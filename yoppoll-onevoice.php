<?php
/*
Plugin Name: yop poll extras
Version: 1.0
Description: Yop Poll addon for making the poll role specific and for adding rare related taxonamies for polls.
Author: Subair T C
Author URI:
Plugin URI:
Text Domain: onevoice-yoppoll-extras
Domain Path: /languages
*/


/* Runs when plugin is activated */
register_activation_hook(__FILE__,'onevoice_yoppoll_extras_install'); 
function onevoice_yoppoll_extras_install() {
    global $wpdb;
	
	// CREATE the settings table
	$table_name = $wpdb->prefix.'onevoice_yop_poll';
	$query  = "CREATE TABLE IF NOT EXISTS $table_name ( `ID` INT(11) NOT NULL AUTO_INCREMENT , `poll_id` INT(11) NOT NULL,  `poll_tags` TEXT NOT NULL,`poll_roles` TEXT NOT NULL, PRIMARY KEY (`ID`))";
	$wpdb->query($query);
}

/*
*	Function to Enqueue required Styles.
*/
function add_onevoice_yoppoll_extras_style() {
	
	wp_register_style( 'custom-css', plugins_url( '/css/custom.css', __FILE__ ) );
	wp_enqueue_style( 'custom-css' );
	
	wp_register_script( 'custom-js', plugins_url( '/js/custom.js', __FILE__ ), true );
	wp_enqueue_script( 'custom-js' );
	
	wp_localize_script('custom-js', 'Ajax', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	));
}
add_action( 'admin_enqueue_scripts', 'add_onevoice_yoppoll_extras_style' );


add_action( 'admin_menu', 'onevoice_yoppoll_add_admin_menu' );
function onevoice_yoppoll_add_admin_menu(  ) { 
	add_submenu_page( 
		'yop-polls',
		'onevoice-yop-poll',
		'onevoice yop poll',
		'manage_options',
		'onevoice-yop-poll',
		'onevoice_yoppoll_options'
	);
	 add_submenu_page(
      null, 
      'onevoice-yop-poll-single',
      'onevoice yop poll Detail Page', 
      'manage_options', 
      'onevoice-yop-poll-single', 
      'onevoice_yop_poll_single'
     );

}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'onevoice_yoppoll_add_action_links' );
function onevoice_yoppoll_add_action_links ( $links ) {
	$mylinks = array(
	'<a href="' . admin_url( 'admin.php?page=onevoice-yop-poll' ) . '">Settings</a>',
	);
	return array_merge( $links, $mylinks );
}



function onevoice_yoppoll_options(  ) { 
	echo '<div id="poststuff">
<div class="postbox">
<div class="inside">
<div class="tickets-container">';
	
	
	global $wpdb;
	$polls = $wpdb->get_results("SELECT `ID`,`poll_title`,`poll_name` FROM `wp_yop2_polls` ");
	if ( $polls ) { ?>
		<div class="onevoice-poll">
		<h2> Poll Details</h2>
		<table border="0" class="form t-style" id="dataTable1" width="100%" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th>SL.NO</th>
					<th>Poll ID</th>
					<th>Poll Title</th>
					<th>Poll Name</th>
					<th>Roles </th>
					<th>Tags</th>
					<th>Edit</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>SL.NO</th>
					<th>Poll ID</th>
					<th>Poll Title</th>
					<th>Poll Name</th>
					<th>Roles </th>
					<th>Tags</th>
					<th>Edit</th>
				</tr>
			</tfoot>
			
			<tbody>
				
			<?php
			$i =1;
			foreach( $polls as $poll ) { ?>
				<tr>
					<td><?php echo $i++; ?></td>
					<td><?php echo $poll->ID; ?></td>
					<td><?php echo $poll->poll_title; ?></td>
					<td><?php echo $poll->poll_name; ?></td>
					<td><?php echo str_replace( ',' ,'<br/>', rtrim( get_rol_names_for_poll( $poll->ID ), ',') ); ?></td>
					<td><?php echo str_replace( ',' ,'<br/>', rtrim( get_taxonamies_for_poll( $poll->ID ) , ',') ); ?></td>
					<td><a href="admin.php?page=onevoice-yop-poll-single&poll_id=<?php echo $poll->ID; ?>" title="add roles/tags" >roles/tags</a></td>
				</tr>			
			<?php
			} ?>
			</tbody>
		</table>
		</div>
	<?php
	}
	
	echo '</div></div></div></div>';
}


function get_taxonamies_for_poll( $poll_id, $output='COMMA' ) {
	if( $poll_id ) {
		global $wpdb;
		$table = $wpdb->prefix.'onevoice_yop_poll';
		$polldetails = $wpdb->get_row( "SELECT `poll_tags` FROM $table WHERE `poll_id` = $poll_id" );
		if( $output == 'ARRAY' ){
			return explode( ',',rtrim ( $polldetails->poll_tags,',' ) );
		}
		return rtrim($polldetails->poll_tags, ',');
	} else {
		return null;
	}
	
}

function get_rol_names_for_poll( $poll_id ) {
	if( $poll_id ) {
		global $wpdb;
		$table = $wpdb->prefix.'onevoice_yop_poll';
		$polldetails = $wpdb->get_row( "SELECT `poll_roles` FROM $table WHERE `poll_id` = $poll_id" );
		return $polldetails->poll_roles;
	} else {
		return null;
	}
	
}


function get_rare_related_tags($taxonomies, $args,$poll_id=false){
    $myterms = get_terms($taxonomies, $args);
	
	if( $poll_id ) {
		$taxonamies = rtrim( get_taxonamies_for_poll( $poll_id ),',');
		$taxonmay_array = explode(',',$taxonamies );
	}
	
    foreach($myterms as $term){
        $term_taxonomy=$term->taxonomy;
        $term_slug=$term->slug;
        $term_name =$term->name;
		$checked = ' ';
		if( $poll_id ){
			if( in_array( $term_slug, $taxonmay_array ) ){
				$checked = 'checked';
			}
		}
		$output .="<input id='".$term_slug."' type='checkbox' name='taxonamies[]' value='".$term_slug."' ".$checked."/>
		<label for='".$term_slug."'>".$term_name."</label><br/>";
    }
	
	return $output;
}

function get_user_roles( $poll_id = false ) {
	$user_roles = wp_roles();
	
	if( $poll_id ){
		$user_roles_forpoll = rtrim( get_rol_names_for_poll( $poll_id ), ',' );
		$role_array = explode(',',$user_roles_forpoll );
	}
	
	foreach( $user_roles->roles as $user_role ) {
		$role_name = $user_role['name'];
		$role_slug = key($user_role);
		$checked = '';
		if( $poll_id ){
			if( in_array( $role_name, $role_array ) ){
				$checked = 'checked';
			}
		}
		$output .="<input id='".$role_name."' type='checkbox' name='user_roles[]' value='".$role_name."' ".$checked." />
		<label for='".$role_name."'>".$role_name."</label><br/>";
	}
	
	return $output;
}






function onevoice_yop_poll_single(){
	$poll_id = $_GET['poll_id'];
	if( ! $poll_id ) {
		echo 'you dont have permission to access this page!!.';
		exit;
	}
	global $wpdb;
	$table = $wpdb->prefix.'yop2_polls';
	$polldetails = $wpdb->get_row("SELECT `ID`,`poll_title`,`poll_name` FROM $table WHERE `ID` = $poll_id");
	
	echo '<div class="poll_section_loading" style="display:none"></div>';
	echo '<div class="poll_section_success" style="display:none"></div>';
	echo '<div class="poll_section">';
	echo '<div class="poll_section_title">';
	echo '<a href="admin.php?page=onevoice-yop-poll">Back to poll detail page.</a>';
	echo '<h1>'.$polldetails->poll_title.'</h1>';
	echo '</div>';
	
	echo '<form name="update_polls" method="get" action="" id="update_polls">';
	echo '<input type="hidden" name="poll_id" value="'.$polldetails->ID.'" />';
	
	// Addding taxonamy selection option
	$taxonomies = array('rarerelated-tag');
	$args = array('orderby'=>'name','order'=>'ASC','hierarchical');
	echo '<div class="choose-relates-outer">';
	echo '<h2>Choose rareRelated Tags</h2>';
	echo '<p>Please choose a rare related tag!!</p>';
	echo '<div class="choose-relates">';
	echo get_rare_related_tags($taxonomies, $args, $poll_id);
	echo '</div></div>';
	
	// Addding Roel selection option
	echo '<div class="choose-user-role-outer">';
	echo '<h2>Choose Roles</h2>';
	echo '<p>if not selected any role, means poll is common for all roles!!</p>';
	echo '<div class="choose-user-roles">';	
	echo get_user_roles( $poll_id );
	echo '</div></div>';
	
	// Submit button
	echo '<div class="poll-section-footer">';
	echo '<button type="submit" id="poll_data_update" class="poll-btn btn-info"> Submit</button>';
	echo '</div>';
	echo '</form>';
	echo '</div>';

	
}
function onevoice_update_poll_details(){
	$items = $_POST['items'];
	if($items){
		$taxonamies = '';
		$user_roles = '';
		foreach( $items as $item ){
			$key = $item['name'];
			if( $key == 'taxonamies[]' ){
				$taxonamies.=$item['value'].',';
			} elseif( $key == 'user_roles[]' ) {
				$user_roles.=$item['value'].',';
			} elseif( $key == 'poll_id' ){
				$poll_id = $item['value'];
			}
			
		}
		//print_r($taxonamies);
		//print_r($user_roles);
		global $wpdb;
		$table = $wpdb->prefix.'onevoice_yop_poll';
		$data = array(
			'poll_id' 		=>	$poll_id,
			'poll_tags'		=>	$taxonamies,
			'poll_roles'	=>	$user_roles
		);
		$poll_exist = $wpdb->get_row("SELECT ID FROM $table WHERE poll_id = $poll_id");
		
		if( $wpdb->num_rows > 0 ) {
			
			$where = array(
				'ID' => $poll_exist->ID
			);
			if( $wpdb->update( $table, $data, $where ) ) {
				echo 'success';
			} else {
				echo 'error';
			}
		} else {
			
			if( $wpdb->insert( $table, $data ) ){
				echo 'success';
			} else {
				echo 'error';
			}
			
		}
		exit;
	}
	echo 'error';
	exit;
}
add_action( 'wp_ajax_onevoice_update_poll_details', 'onevoice_update_poll_details' );

/* function to check wether a poll is accessible to a user. */
function is_poll_accessible( $poll_id,$user_id = false ){

	$roles = rtrim( get_rol_names_for_poll( $poll_id ), ',');
	if( $roles ) {
		
		$roles_arr = explode( ',',$roles );
		
		if( $user_id ) {
			global $wp_roles;
			$u = get_userdata( $user_id );
			
			$role_array = $u->roles;
			foreach( $role_array as $role_item) {
				$user_role_name = $wp_roles->roles[$role_item]['name'];

				if( in_array( $user_role_name,$roles_arr) ){
					return true;
				}
			}
			return false;
			
		} else {
			return false;
		}
		
	}
	return true;
}





// function to handle the random poll.
function get_user_random_rarepoll( $user_id ) {
	global $wpdb;
	//get the polls answered as , seperated.
	$polls = $wpdb->get_row("SELECT GROUP_CONCAT( DISTINCT ans.poll_id) as poll_ids FROM `wp_yop2_poll_logs` ans WHERE ans.`user_id` = $user_id  AND ans.`message` = 'Success' ");
	
	// get expired and to begin polls.
	$current_date = current_time('Y-m-d h:i:s' );
	$poll_items = $wpdb->get_row("SELECT GROUP_CONCAT( DISTINCT polls.ID) as poll_items FROM `wp_yop2_polls` polls WHERE CURDATE() NOT BETWEEN polls.poll_start_date AND polls.poll_end_date" );

	if ($polls->poll_ids || $poll_items->poll_items ) {
		if( $polls->poll_ids && $poll_items->poll_items ){
			$not_in_items = $polls->poll_ids . ','.$poll_items->poll_items;
		} elseif( $polls->poll_ids ) {
			$not_in_items = $polls->poll_ids;
		} else {
			$not_in_items = $poll_items->poll_items;
		}
		$query = "SELECT qs.`poll_id` FROM `wp_yop2_poll_questions` qs WHERE qs.`poll_id` NOT IN ( $not_in_items ) ORDER BY poll_id DESC ";
	} else {
		$query = "SELECT qs.`poll_id`,qs.`question` FROM `wp_yop2_poll_questions` qs WHERE qs.`poll_id` ORDER BY poll_id DESC";
	}
	
	$polls = $wpdb->get_results($query);
	foreach( $polls as $poll ) {
		if( function_exists( 'is_poll_accessible' ) && is_poll_accessible( $poll->poll_id,$user_id ) ){
					return $poll->poll_id;
			}
	}
	return $polls[0]->poll_id;
}

/* function for updating the poll need display (in dashboard) on user meta*/
function set_the_yop_poll_id( $user_login, $user ) {
   $user_id = $user->id;
   if( $user_id ) {
	   $yop_poll_id = get_user_random_rarepoll( $user_id );
	   return update_user_meta( $user_id, 'one_random_yop_poll', $yop_poll_id );
   }
   return false;
}
add_action('wp_login', 'set_the_yop_poll_id', 10, 2);

/* Function to check wether a user attended a poll */
function check_user_attended_a_poll( $poll_id, $user_id ) {
	global $wpdb;
	$table 		= $wpdb->prefix.'yop2_poll_results';
	$poll_log 	= $wpdb->get_row( $wpdb->prepare( "SELECT result_details FROM {$table} WHERE poll_id = %d AND user_id = %d ", $poll_id,$user_id  ) );
	if ( $poll_log->result_details ) {
		return 1;
	}
	
	return 0;
}
