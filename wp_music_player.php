<?php
/*
Plugin Name: Wp Music Player

Plugin URI: http://sabdsoft.com/

Description: This wordpress plugin displays Music File on Front End

Version: 1.3

License: GPL

Author: Hiren Patel

*/
// Define Post Details Paths and Directories
define("MUSIC_PLAYER_FILENAME", basename(__FILE__));
$path = $_SERVER['REQUEST_URI'];
$path_length = strpos($path, MUSIC_PLAYER_FILENAME) + strlen(MUSIC_PLAYER_FILENAME);
	
$path = substr($path, 0, strpos($path, '?')) . '?page=' . MUSIC_PLAYER_FILENAME;

define("MP_ADMIN_PLUGIN_PATH", $path);

	
if ($IS_WINDOWS) {
	$temp = str_replace(MUSIC_PLAYER_FILENAME, "", __FILE__);
	$temp = str_replace("\\", "/", $temp);	//switch direction of slashes
	define("MUSIC_PLAYER_PLUGIN_PATH", $temp);
} else {
	define("MUSIC_PLAYER_PLUGIN_PATH", str_replace(MUSIC_PLAYER_FILENAME, "", __FILE__));
}


if ( ! defined( 'WP_CONTENT_URL' ) )
	  define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
	  define( 'WP_CONTENT_DIR', ABSOLUTE_PATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
	  define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
	  define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
	  
// Determine whether we're in HTTPS mode or not, and change URL's accordingly.
if(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] == 'on') 
{
	define('MUSIC_PLAYER_SITE_URL', str_replace('http://', 'https://', get_bloginfo('url')));
	define('MUSIC_PLAYER_BLOG_URL', str_replace('http://', 'https://', get_bloginfo('wpurl')));
}
else 
{
	define('MUSIC_PLAYER_SITE_URL', get_bloginfo('url'));
	define('MUSIC_PLAYER_BLOG_URL', get_bloginfo('wpurl'));
}
define("MUSIC_PLAYER_PLUGIN_URL", WP_PLUGIN_URL."/wp-music-player/");	
define("UPLOAD_PATH",WP_CONTENT_DIR.'/uploads/wp_music_player_songs');
define("UPLOAD_URL",WP_CONTENT_URL.'/uploads/wp_music_player_songs');


if (!class_exists('Wp_Music_Player')) {



	class Wp_Music_Player {

	

		function Wp_Music_Player() {			

			$this->addActions();

			register_activation_hook(__FILE__, array($this, 'createMusicPlayerPlugin'));

			register_deactivation_hook(__FILE__, array($this, 'removeMusicPlayerPlugin'));

		}

		

		function addActions() {

			add_action('admin_menu', array(&$this, 'addAdminInterfaceItems'));
			add_action('admin_menu', array(&$this,'wp_music_player_add_custom_box'));
			add_action( 'save_post', array(&$this,'wp_music_player_save_options'));
			add_action( 'edit_post', array(&$this,'wp_music_player_save_options'));
			add_action( 'publish_post',array(&$this, 'wp_music_player_save_options'));
			add_action( 'delete_post', array(&$this,'wp_music_player_delete_options'));
			add_shortcode('music-player',array(&$this,'wp_music_player_display'));
			add_filter('admin_head', array(&$this,'wp_music_player_admin_js'));

		}

		//If you want to insert table or query Put in that function
		//this will insert while installatoin time

		function createMusicPlayerPlugin() 

		{

			global $wpdb;
			
			global $jal_db_version;
			
			//create main table
			$table_name_main = $wpdb->prefix . "music_player_songs";
			
			if($wpdb->get_var("show tables like '$table_name_main'") != $table_name_main) 
			{
				
			  $sql="CREATE TABLE " . $table_name_main . " (
					id int(11) NOT NULL auto_increment,
					title varchar(255) NOT NULL,
					song_name varchar(255) NOT NULL,
					composer varchar(255) NOT NULL,
					published tinyint(4) NOT NULL default '0',
					PRIMARY KEY ( `id` )
					);";
			  
			  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			  
			  dbDelta($sql);
			  
			}
			
			//create reference table
			$table_name_ref = $wpdb->prefix . "music_player_pages";
			
			if($wpdb->get_var("show tables like '$table_name_ref'") != $table_name_ref) 
			{
				
			  
			  $sql = "CREATE TABLE " . $table_name_ref . " (
						id bigint(20) NOT NULL auto_increment,
						post_id bigint(20) NOT NULL default '0',
						post_music_player_id bigint(20) NOT NULL default '0',
						UNIQUE KEY id (id)
					);";
			  
			  dbDelta($sql);
			}
			
			add_option("jal_db_version", $jal_db_version);
			$newoptions = get_option('wp_music_player');
			$newoptions['record_per_page'] = 5;
			$newoptions['autoplay'] = '0';
			$newoptions['loop'] = '0';
			$newoptions['showstop'] = '1';
			$newoptions['showvolume'] = '1';
			$newoptions['bgcolor1'] = '696969';
			$newoptions['bgcolor2'] = '0c0c0c';
			$newoptions['slidercolor1'] = 'cccccc';
			$newoptions['slidercolor2'] = '999999';
			$newoptions['buttonovercolor'] = 'ffffff';
			$newoptions['sliderovercolor'] = 'efea1b';
			$newoptions['shuffle'] = '0';
			$newoptions['width'] = '200';
			$newoptions['height'] = '100';
			$newoptions['textcolor'] = 'ffffff';
			$newoptions['playlistcolor'] = '333333';
			$newoptions['playlistalpha'] = '25';
			$newoptions['currentmp3color'] = 'ffff00';
			$newoptions['showlist'] = '1';
			$newoptions['showplaylistnumbers'] = '1';
			$newoptions['scrollbarcolor'] = 'ffffff';
			$newoptions['scrollbarovercolor'] = 'efea1b';
			$newoptions['loadingcolor'] = 'efea1b';
			add_option('wp_music_player', $newoptions);
			
			if (!is_dir(WP_CONTENT_DIR.'/uploads/wp_music_player_songs'))
			{ 
			
				mkdir(WP_CONTENT_DIR.'/uploads/wp_music_player_songs');
				
			}

		}

		//If you want to insert table or query Put in that function
		//this will insert while installatoin time

		function removeMusicPlayerPlugin() 

		{

			global $wpdb;	//required global declaration of WP variable
			//delete main table
			$table_name_main = $wpdb->prefix . "music_player_songs";
			
			$sql = "DROP TABLE ". $table_name_main;
			
			$wpdb->query($sql);
			
			$table_name_main = $wpdb->prefix . "music_player_pages";
			
			$sql = "DROP TABLE ". $table_name_main;
			
			$wpdb->query($sql);
			
			$this->recursiveMusicFileDelete(WP_CONTENT_DIR.'/uploads/wp_music_player_songs');
			
		}

		//recursiv File Delete
		
		function recursiveMusicFileDelete($str)
		
		{
			
				if(is_file($str)){
					
					return @unlink($str);
					
				}
				elseif(is_dir($str))
				{
					
					$scan = glob(rtrim($str,'/').'/*');
					foreach($scan as $index=>$path){
						$this->recursiveMusicFileDelete($path);
					}
					
					return @rmdir($str);
				}
				
		}
		

		function addAdminInterfaceItems() 
		
		{

			$icon_path = get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/icon';
			
			
			add_menu_page(__('Wp Music Player'), __('Wp Music Player'), 'manage_options',MUSIC_PLAYER_FILENAME, null,$icon_path.'/generic.png');

			add_submenu_page('wp_music_player',__('Data Set'),__('Data Set'),'manage_options',MUSIC_PLAYER_FILENAME, array(&$this,'wp_music_player_options_page'));

		}
		
		
		//display admin navigation
		function wp_music_player_admin_nav()
		{
			$wp_music_player_admin_nav_options = array();
			$wp_music_player_admin_nav_options['new'] = __("Add New", 'wp_music_player');
			$wp_music_player_admin_nav_options['view'] = __("View All", 'wp_music_player');
			$wp_music_player_admin_nav_options['setting'] = __("Setting", 'wp_music_player');
			?>
		
		<div class="formbuilder-subnav">
		  <ul class="subsubsub">
			<?php
			$i=1;
			foreach( $wp_music_player_admin_nav_options as $key=>$value ) { ?>
			<li><a  href="<?php echo MP_ADMIN_PLUGIN_PATH; ?>&action=<?php echo $key; ?>"><?php echo $value; ?></a>
			  <? if ($i!=count($wp_music_player_admin_nav_options))
				{ ?>
			  |
			  <?
				} 
				$i++;
			 ?>
			</li>
			<?php } ?>
		  </ul>
		</div>
		<?php
		}

	

		function wp_music_player_options_page($action="")
		
		{
			
			global $wpdb;
			
			?>
		
            <div id="icon-tools" class="icon32"><br>
            </div>
            <div class="wrap">
              <h2>
                <?php _e('Song Management', 'wp_music_player'); ?>
              </h2>
              <?php
                if(!isset($_GET['action'])) $_GET['action'] = false;
                switch($_GET['action']) {
                    case "new":
                        $this->wp_music_player_new();
                    break;
                    case "edit":
                        $this->wp_music_player_edit($_GET['editid']);
                    break;
                    case "setting":
                        $this->wp_music_player_setting();
                    break;
                    case "view":
                    default:
                        $this->wp_music_player_list_page();
                    break;
            
                }
                ?>
            </div>
		<?php
		

		}
		
		//New details
		function wp_music_player_new() 
		{ 
		
			global $wpdb;
			
			//add edit record
			if(isset($_POST['Submit']))
			{
				// A form was added to the post.  Go ahead and add or modify it in the db.
				$song['title'] = addslashes($_POST['title']);
				$song['song_name'] = addslashes($_FILES['musicfile']['name']);
				$song['composer'] = addslashes($_POST['composer']);
				
				$error = "";
				if($_POST['title'] == "")
					$error .= "title Cannot be blank<br>";
				
				if($_POST['composer'] == '')
					$error .= "composer Cannot be blank<br>";
					
				if($_FILES['musicfile']['name'] == '')
					$error .= "Upload a File<br>";
				
				if($_FILES['musicfile']['name'] != '')
				{
				  $ext = substr(strchr($_FILES['musicfile']['name'],'.'),1);
				  if(!in_array(strtoupper($ext),array('MP3','WAVE','WAV')))
				  {
					$error .=  "Not valid file<br>";
				  }
				}
				if($error != "")
				{
					$_SESSION['message'] = $error;
				}	
				else
				{
					$wpdb->insert($wpdb->prefix."music_player_songs", $song);
					$id=$wpdb->insert_id;
					if($_FILES['musicfile']['name']!="")
					{
					   $ext = substr(strchr($_FILES['musicfile']['name'],'.'),1);
					   $imgname=$id.".".$ext;
					   $target_path = UPLOAD_PATH."/".$imgname;
						 move_uploaded_file($_FILES['musicfile']["tmp_name"], $target_path);
		
					}
					$_SESSION['message'] = "Record Inserted Successfuly";
					?>
					<script type="text/javascript">
					<!--
					window.location = "<?=MP_ADMIN_PLUGIN_PATH?>&action=edit&editid=<?=$id?>&msg=2"
					//-->
					</script>
					<?php
				}
			}	
		$this->wp_music_player_admin_nav(); ?>
		<div class="wrap">
		  <h2>
			<?php _e('Add Song'); ?>
		  </h2>
		  <div class="narrow">
			 <form name="form1" method="post" action="" enctype="multipart/form-data">
			  <table  width="400"  cellpadding="0"  cellspacing="0" border="0">
				<tr>
				  <td><table id="table" border="1" width="100%">
					  <tbody>
						<tr>
						  <td>Title</td>
						  <td><input type="text" name="title" id="title"  value="<?=$song['title']?>"/></td>
						</tr>
						<tr>
						  <td>Upload File</td>
						  <td><input type="file" name="musicfile" id="musicfile"  value=""/></td>
						</tr>
						<tr>
						  <td>Composer</td>
						  <td><input type="text" name="composer" id="composer"  value="<?=$song['composer']?>"/></td>
						</tr>
						
					  </tbody>
					</table></td>
				</tr>
				<tr>
				  <td> <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" /></td>
				</tr>
			  </table>
			</form>
		  </div>
		</div>
		<?
		
		}
		
		
		//edit details
		function wp_music_player_edit($id) 
		{ 
		
			global $wpdb,$_SESSION;
			//print_r($_SESSION);die;
			//unset($_SESSION['message']);
			$song_details = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."music_player_songs where id=".$id) );
			
			//add edit record
			if(isset($_POST['Submit']))
			{
				//print "<pre>";print_r($song_details);die;
				$sql = "SELECT * FROM ".$wpdb->prefix."music_player_songs WHERE id= '" . $id . "';";
				
				$song = $wpdb->get_row($sql, ARRAY_A);
		
				// A form was added to the post.  Go ahead and add or modify it in the db.
				$song['title'] = addslashes($_POST['title']);
				$song['song_name'] = (isset($_FILES['musicfile']['name']) && $_FILES['musicfile']['name']!='')?$_FILES['musicfile']['name']:$song_details->song_name;
				$song['composer'] = addslashes($_POST['composer']);
				
				//print_r($_FILES);print "<pre>";print_r($song);die;
				$error = "";
				if($_POST['title'] == "")
					$error .= "title Cannot be blank<br>";
				
				if($_POST['composer'] == '')
					$error .= "composer Cannot be blank<br>";
				
				if($_FILES['musicfile']['name'] != '')
				{
				  $ext = substr(strchr($_FILES['musicfile']['name'],'.'),1);
				  if(!in_array(strtoupper($ext),array('MP3','WAVE','WAV')))
				  {
					$error .=  "Not valid file<br>";
				  }
				}
				if($error != "")
				{
				   $_SESSION['message'] = $error;
				}	
				else
				{			
					if($_FILES['musicfile']['name']!="")
					{
					   $ext = substr(strchr($_FILES['musicfile']['name'],'.'),1);
					   $imgname=$id.".".$ext;
					   $target_path = UPLOAD_PATH."/".$imgname;
						 move_uploaded_file($_FILES['musicfile']["tmp_name"], $target_path);
		
					}
					$wpdb->update($wpdb->prefix."music_player_songs", 
						$song, 
						array('id'=>$song['id'])
					);
					$_SESSION['message'] = "Record Updated Successfuly";			
					?>
					<script type="text/javascript">
					<!--
					window.location = "<?=MP_ADMIN_PLUGIN_PATH?>&action=edit&editid=<?=$id?>&msg=1"
					//-->
					</script>
			
					<?
				}
			}
			$this->wp_music_player_admin_nav(); ?>
		<div class="wrap">
		  <h2>
			<?php _e('Add Song'); ?>
		  </h2>
		  <div class="narrow">
		  <?php 
		  
		  if($_GET['msg']==1)
		  {
			 ?>
			<div class="updated"><p><strong>Record Updated Successfuly</strong></p></div>
		  <?php
		  }
		  elseif($_GET['msg']==2)
		  {
			 
			  ?>
		  
			  <div class="updated"><p><strong>Record Inserted Successfuly</strong></p></div>
		  <?php } ?>
			<form name="form1" method="post" action="<?php echo MP_ADMIN_PLUGIN_PATH; ?>&action=edit&editid=<?php echo $id; ?>" enctype="multipart/form-data">
			  <table  width="400"  cellpadding="0"  cellspacing="0" border="0">
				<tr>
				  <td><table id="table" border="1" width="100%">
					  <tbody>
						<tr>
						  <td>Title</td>
						  <td><input type="text" name="title" id="title"  value="<?=$song_details->title?>"/></td>
						</tr>
						<tr>
						  <td>Upload File</td>
						  <td><input type="file" name="musicfile" id="musicfile"  value=""/><?=$song_details->song_name ?></td>
						</tr>
						<tr>
						  <td>Composer</td>
						  <td><input type="text" name="composer" id="composer"  value="<?=$song_details->composer ?>"/></td>
						</tr>
					  </tbody>
					</table></td>
				</tr>
				<tr>
				  <td><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" /></td>
				</tr>
			  </table>
			</form>
		  </div>
		</div>
		<?
		
		
		}
		
		//Display js files

		function wp_music_player_admin_js() 
		{
			
		// Display the admin related Js
		?>
        
		<script type='text/javascript' src='<?php echo MUSIC_PLAYER_PLUGIN_URL; ?>wp_music_player.js'></script>
		<link rel="stylesheet" href="<?php echo MUSIC_PLAYER_PLUGIN_URL; ?>pagination.css" />
		<?
		
		}
		
		
		//Display list page
		function wp_music_player_list_page()
		{ 
		
		
			global $wpdb;
			
			include_once(MUSIC_PLAYER_PLUGIN_PATH ."pagination.class.php");
			
			if($_GET['action']=='deleteAll')
			{
				for($i=0;$i<count($_POST['delId']);$i++)
				{
					$id=$_POST['delId'][$i];
					//delete file
					$filename=$wpdb->get_var("SELECT song_name FROM ".$wpdb->prefix."music_player_songs where id=".$id);
					$ext = substr(strchr($filename,'.'),1);
					$filename=$id.".".$ext;
					@unlink(UPLOAD_PATH."/".$filename);	
					$wpdb->query("DELETE FROM ".$wpdb->prefix."music_player_songs WHERE id = ".$id);  		  }
				$_SESSION['message'] = "Records deleted Successfuly";
			}
			if($_GET['action']=='delete')
			{
				$id=$_GET['delid'];
				//delete file
				$filename=$wpdb->get_var("SELECT song_name FROM ".$wpdb->prefix."music_player_songs where id=".$id);
				$ext = substr(strchr($filename,'.'),1);
				$filename=$id.".".$ext;
				
				@unlink(UPLOAD_PATH."/".$filename);	
				
				$wpdb->query("DELETE FROM ".$wpdb->prefix."music_player_songs WHERE id = ".$id);  
				
				$_SESSION['message'] = "Record delete Successfuly";
				
			}
				
			$options= get_option('wp_music_player');
			//paging logic
			$sql="SELECT * FROM ".$wpdb->prefix."music_player_songs";
			$items = mysql_num_rows(mysql_query($sql));
		
			if($items > 0) {
				$p = new pagination;
				$p->items($items);
				$p->limit($options['record_per_page']);
				$p->target("?page=wp_music_player.php");
				$p->parameterName("pg_no");
				if(!isset($_GET['pg_no'])) {
					$p->page = 1;
				} else {
					$p->page = $_GET['pg_no'];
				}
		 
				//Query for limit paging
				$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;		
			}
			else
			{
				
			}	
			$songs = $wpdb->get_results( $wpdb->prepare($sql." ".$limit) );
			$this->wp_music_player_admin_nav();
			?>
		<div class="wrap">
		  <h2>
			<?php _e('Song List'); ?>
		  </h2>
		  <? if($msg){ ?>
		   <div class="updated"><p><strong><?php echo $msg?></strong></p></div>
		  <? } ?> 
		  <form method="post" action="" id="songs_list" name="songs_list">
			<div class="tablenav">
			  <div class="alignleft actions">
				<input type="button" onclick="javascript:delete_songs();" value="Delete Songs" class="input-button">       
			  </div>      
			  <div class="clear"></div>
			</div>
			<div class="clear"></div>
			<table>
				<tr>
					<td><table cellspacing="0" class="widefat post fixed">
					  <thead>
						<tr>
						  <th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" onclick="check_all()" value="" name="post_details"></th>
						  <th width="5%">Id</th>
						  <th width="15%">Title</th>
						  <th>Song Name</th>
						  <th>Composer</th>
						  <th width="10%">Action</th>
						</tr>
					  </thead>
					  <tfoot>
						<tr>
						  <th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox" onclick="check_all()" value="" name="post_details"></th>
						  <th>Id</th>
						  <th>Title</th>
						  <th>Song Name</th>
						  <th>Composer</th>
						  <th>Action</th>
						</tr>
					  </tfoot>
					  <tbody>
					 <?php
					 if(!empty($songs))
					 {
						$x=0;
						foreach($songs as $song) { 
						$x++;
						  $editLink="options-general.php?page=".MUSIC_PLAYER_FILENAME."&action=edit&editid=".$song->id;
						  $deleteLink="options-general.php?page=".MUSIC_PLAYER_FILENAME."&action=delete&delid=".$song->id;
			
						  ?>
							<tr valign="top" class="alternate author-self status-publish iedit">
							  <th class="check-column" scope="row"><input type="checkbox" value="<?php echo $song->id?>" name="delId[]"></th>
							  <td><?php echo $x?></td>
							  <td><?php echo $song->title?></td>
							  <td><?php echo $song->song_name?></td>
							  <td><?php echo $song->composer?></td>
							  <td><a href="<?php echo $editLink?>">Edit</a>/<a href="<?php echo $deleteLink?>">delete</a></td>
							</tr>
							
							<?php } 
						}
						else
						{?>
						
						<tr><td colspan="6" align="center">No Record Found</td></tr>	
					   <?php }  
					 if(count($songs)>=$options['record_per_page'])
					 {?>
					   <tr>
					<td colspan="6"><table class="widefat post fixed"><tr><td  align="center"><?php echo $p->show();?></td></tr></table></td>
				</tr>  
				<?php } ?>
							
					  </tbody>
					</table></td>
				</tr>
				
				
			</table>
		  </form>
		</div>
		<?
		
		
		}
		
		
		//Music Player Setting
		function wp_music_player_setting()
		{
			
			global $wpdb;
			$this->wp_music_player_admin_nav();
			$options= $newoptions  = get_option('music_player');
			//print "<pre>";print_r($options);die;
			// if submitted, process results
			if ( $_POST["music_player_submit"] ) {
				$newoptions['record_per_page'] = strip_tags(stripslashes($_POST["record_per_page"]));
				$newoptions['autoplay'] = strip_tags(stripslashes($_POST["autoplay"]));
				$newoptions['loop'] = strip_tags(stripslashes($_POST["loop"]));
				$newoptions['showstop'] = strip_tags(stripslashes($_POST["showstop"]));
				$newoptions['showvolume'] = strip_tags(stripslashes($_POST["showvolume"]));
				$newoptions['bgcolor1'] = strip_tags(stripslashes($_POST["bgcolor1"]));
				$newoptions['bgcolor2'] = strip_tags(stripslashes($_POST["bgcolor2"]));
				$newoptions['slidercolor1'] = strip_tags(stripslashes($_POST["slidercolor1"]));
				$newoptions['slidercolor2'] = strip_tags(stripslashes($_POST["slidercolor2"]));
				$newoptions['buttonovercolor'] = strip_tags(stripslashes($_POST["buttonovercolor"]));
				$newoptions['sliderovercolor'] = strip_tags(stripslashes($_POST["sliderovercolor"]));
				$newoptions['shuffle'] = strip_tags(stripslashes($_POST["shuffle"]));
				$newoptions['width'] = strip_tags(stripslashes($_POST["width"]));
				$newoptions['height'] = strip_tags(stripslashes($_POST["height"]));
				$newoptions['textcolor'] = strip_tags(stripslashes($_POST["textcolor"]));
				$newoptions['playlistcolor'] = strip_tags(stripslashes($_POST["playlistcolor"]));
				$newoptions['playlistalpha'] = strip_tags(stripslashes($_POST["playlistalpha"]));
				$newoptions['currentmp3color'] = strip_tags(stripslashes($_POST["currentmp3color"]));
				$newoptions['showlist'] = strip_tags(stripslashes($_POST["showlist"]));
				$newoptions['showplaylistnumbers'] = strip_tags(stripslashes($_POST["showplaylistnumbers"]));
				$newoptions['scrollbarcolor'] = strip_tags(stripslashes($_POST["scrollbarcolor"]));
				$newoptions['scrollbarovercolor'] = strip_tags(stripslashes($_POST["scrollbarovercolor"]));
				$newoptions['loadingcolor'] = strip_tags(stripslashes($_POST["loadingcolor"]));
			}
			
			// any changes? save!
			if ( $options != $newoptions ) {
				$options = $newoptions;
				update_option('music_player', $options);
			}
			// options form
			echo '<form method="post">';
			echo "<div class=\"wrap\"><h2>Display options</h2>";
			echo '<table class="form-table">';
			// Display Record at Admin side
			echo '<tr valign="top"><th scope="row">Display Record Admin Side</th>';
			echo '<td><input type="text" name="record_per_page" value="'.$options['record_per_page'].'" size="8"></input></td></tr>';
			// Autoplay
			echo '<tr valign="top"><th scope="row">Autoplay</th>';
			echo '<td><input type="radio" name="autoplay" value="0"';
			if( $options['autoplay'] == 0 ){ echo ' checked="checked" '; }
			echo '></input> NO&nbsp;<input type="radio" name="autoplay" value="1"';
			if( $options['autoplay'] == 1){ echo ' checked="checked" '; }
			echo '></input> Yes</td></tr>';
			
			// Loop
			echo '<tr valign="top"><th scope="row">Loop</th>';
			echo '<td><input type="radio" name="loop" value="0"';
			if( $options['loop'] == 0 ){ echo ' checked="checked" '; }
			echo '></input> NO&nbsp;<input type="radio" name="loop" value="1"';
			if( $options['loop'] == 1){ echo ' checked="checked" '; }
			echo '></input> Yes</td></tr>';
		
			//Show Stop Button
			echo '<tr valign="top"><th scope="row">Show Stop Button</th>';
			echo '<td><input type="radio" name="showstop" value="0"';
			if( $options['showstop'] == 0 ){ echo ' checked="checked" '; }
			echo '></input> NO&nbsp;<input type="radio" name="showstop" value="1"';
			if( $options['showstop'] == 1){ echo ' checked="checked" '; }
			echo '></input> Yes</td></tr>';
		
			//Show Volume Button
			echo '<tr valign="top"><th scope="row">Show Volume Button</th>';
			echo '<td><input type="radio" name="showvolume" value="0"';
			if( $options['showvolume'] == 0 ){ echo ' checked="checked" '; }
			echo '></input> NO&nbsp;<input type="radio" name="showvolume" value="1"';
			if( $options['showvolume'] == 1){ echo ' checked="checked" '; }
			echo '></input> Yes</td></tr>';
			
			//Bar Gradient Colour 1
			echo '<tr valign="top"><th scope="row">Bar Gradient Colour 1</th>';
			echo '<td><input type="text" name="bgcolor1" value="'.$options['bgcolor1'].'" size="8"></input></td></tr>';
			
			//Bar Gradient Colour 2
			echo '<tr valign="top"><th scope="row">Bar Gradient Colour 2</th>';
			echo '<td><input type="text" name="bgcolor2" value="'.$options['bgcolor2'].'" size="8"></input></td></tr>';
			
			//Slider Gradient Colour 1
			echo '<tr valign="top"><th scope="row">Slider Gradient Colour 1</th>';
			echo '<td><input type="text" name="slidercolor1" value="'.$options['slidercolor1'].'" size="8"></input></td></tr>';
			
			//Slider Gradient Colour 2
			echo '<tr valign="top"><th scope="row">Slider Gradient Colour 2</th>';
			echo '<td><input type="text" name="slidercolor2" value="'.$options['slidercolor2'].'" size="8"></input></td></tr>';
			
			//Button Colour
			echo '<tr valign="top"><th scope="row">Button Colour</th>';
			echo '<td><input type="text" name="buttonovercolor" value="'.$options['buttonovercolor'].'" size="8"></input></td></tr>';
			
			//slider over color
			echo '<tr valign="top"><th scope="row">Slider Over Color</th>';
			echo '<td><input type="text" name="sliderovercolor" value="'.$options['sliderovercolor'].'" size="8"></input></td></tr>';
			
			//Shuffle
			echo '<tr valign="top"><th scope="row">Shuffle</th>';
			echo '<td><input type="radio" name="shuffle" value="0"';
			if( $options['shuffle'] == 0 ){ echo ' checked="checked" '; }
			echo '></input> NO&nbsp;<input type="radio" name="shuffle" value="1"';
			if( $options['shuffle'] == 1){ echo ' checked="checked" '; }
			echo '></input> Yes</td></tr>';
			
			//Width
			echo '<tr valign="top"><th scope="row">Width</th>';
			echo '<td><input type="text" name="width" value="'.$options['width'].'" size="8"></input></td></tr>';
			
			//Height
			echo '<tr valign="top"><th scope="row">Height</th>';
			echo '<td><input type="text" name="height" value="'.$options['height'].'" size="8"></input></td></tr>';
			
			//Text color
			echo '<tr valign="top"><th scope="row">Text Color</th>';
			echo '<td><input type="text" name="textcolor" value="'.$options['textcolor'].'" size="8"></input></td></tr>';
			
			//Playlistcolor
			echo '<tr valign="top"><th scope="row">Play List Color</th>';
			echo '<td><input type="text" name="playlistcolor" value="'.$options['playlistcolor'].'" size="8"></input></td></tr>';
			
			//Playlist Transparency
			echo '<tr valign="top"><th scope="row">Playlist Transparency</th>';
			echo '<td><input type="text" name="playlistalpha" value="'.$options['playlistalpha'].'" size="8"></input></td></tr>';
			
			
			echo '<tr valign="top"><th scope="row">Width of the Flash tag cloud</th>';
			echo '<td><input type="text" name="currentmp3color" value="'.$options['currentmp3color'].'" size="8"></input></td></tr>';
			
			//Show List
			echo '<tr valign="top"><th scope="row">Show List</th>';
			echo '<td><input type="radio" name="showlist" value="0"';
			if( $options['showlist'] == 0 ){ echo ' checked="checked" '; }
			echo '></input> NO&nbsp;<input type="radio" name="showlist" value="1"';
			if( $options['showlist'] == 1){ echo ' checked="checked" '; }
			echo '></input> Yes</td></tr>';
			
			// close stuff
			echo '<input type="hidden" name="music_player_submit" value="true"></input>';
			echo '</table>';
			echo '<p class="submit"><input type="submit" value="Update Options &raquo;"></input></p>';
			echo "</div>";
			echo '</form>';
		
		}
		
		
		//display on post page
		function wp_music_player_add_custom_box()
		{

			  if( function_exists( 'add_meta_box' )) 
			  
			  {
		
					add_meta_box( 'wp_music_player_sectionid', __( 'Music Player Title', 'wp_music_player_textdomain' , 'test'),array(&$this,'wp_music_player_post_options'), 'post', 'normal', 'high' );
			
					add_meta_box( 'wp_music_player_sectionid', __( 'Music Player Title', 'wp_music_player_textdomain' , 'test'),array(&$this,'wp_music_player_post_options'), 'page', 'normal', 'high' );
		
			   } 
			   
			   else 
			   {
		
					// Otherwise just use the old functions
					add_action( 'simple_edit_form', array(&$this,'wp_music_player_post_options'));
					add_action( 'edit_form_advanced', array(&$this,'wp_music_player_post_options'));
					add_action( 'edit_page_form', array(&$this,'wp_music_player_post_options'));
		
			  }
			
		}
		
		//post option		
		function wp_music_player_post_options()
		{
			
			global $post, $wpdb;
		
			// Load the available forms
			$sql = "SELECT * FROM ".$wpdb->prefix."music_player_songs ORDER BY title ASC";
			$songs = $wpdb->get_results($sql, ARRAY_A);
		//print_r($posts);die;
			// If the post already has an id, determine whether or not there is a form already linked to it.
			if($post->ID)
			{
				// Determine if the post/page has a linked form.
				$sql = "SELECT * FROM ".$wpdb->prefix."music_player_pages WHERE post_id = " . $post->ID;
				$pageDetails = $wpdb->get_row($sql, ARRAY_A);
				//print_r($pageDetails);die;
			}
		
			echo "<div id='musicPlayerFormOptions'>\n" .
					"<p>" . __("If you wish to display a Tag that you have created using the Post Detail plugin, please select it from the following options.", 'wp_music_player') . "</p>\n" .
					"<select name='songSelection'>\n" .
					"<option value=''>" . __("Select Title", 'wp_music_player') . "</option>\n";
			
			foreach($songs as $song)
			{
				$song_id = $song['id'];
				
				if($song_id == $pageDetails['post_music_player_id']) 
				{
					$selected = "selected";
					$post_data = $song;
				}
				else 
					$selected = "";
		
				echo "<option value='$song_id' $selected>" . $song['title'] . "</option>\n";
			}
			echo "</select>";
			
			echo "</div>\n";
			
		
		}
		
		
		//save option on post page
		function wp_music_player_save_options($id)
		{
			
			global $wpdb;
			if(isset($_POST['songSelection']))
			{
				//print "<pre>";print_r($_POST);die;	
				$id = $_POST['post_ID'];
				$sql = "SELECT * FROM ".$wpdb->prefix."music_player_pages WHERE post_id = " . $id;
				
				
				$pageDetails= $wpdb->get_row($sql);
				//print_r($pageDetails);die;
				$page = intval($pageDetails->id);
				
		
				// Determine if the selected PostDetail ID is the same as the old PostDetail ID.
				if($_POST['songSelection'] != $pageDetails->post_music_player_id )
				{
				// A form was added to the post.  Go ahead and add or modify it in the db.
					$postDetails['post_id'] = addslashes($id);
					$postDetails['post_music_player_id'] = addslashes($_POST['songSelection']);
					
					if($page==0)
					{
						$wpdb->insert($wpdb->prefix."music_player_pages", $postDetails);
					}
					else
					{
						$wpdb->update($wpdb->prefix."music_player_pages", $postDetails,array('id'=>$pageDetails->id));
					}
				}
			}
		}
		
		//delete option while delete file
		function wp_music_player_delete_options()
		{
			
			global $wpdb;
			$pid=$_GET['post'];
			if ($wpdb->get_var($wpdb->prepare("SELECT post_id FROM ".$wpdb->prefix."post_detail_pages WHERE post_id = %d", $pid))) {
				return $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."post_detail_pages WHERE post_id = %d", $pid));
			}
			return true;
		}
	
		// template function
		function wp_music_player_display($content = '') 
		{
			
			global $post, $_SERVER, $wpdb;
			
			$id = $post->ID;
			
			$file = $wpdb->get_row( $wpdb->prepare("SELECT mp.post_id, mps.*
		FROM ".$wpdb->prefix."music_player_pages mp INNER JOIN ".$wpdb->prefix."music_player_songs mps ON mp.post_music_player_id  = mps.id WHERE mp.post_id =".$id) );
			if(!empty($file))
			{
				$titlestr="";
				$filenamestr="";
				$titlestr.="|".$file->title;
				$ext = substr(strchr($file->song_name,'.'),1);
				$filenamestr.="|".UPLOAD_URL."/".$file->id.".".$ext;
				
				$titlestr=substr($titlestr,1) ;
				$filenamestr=substr($filenamestr,1) ;
			
				$options= $newoptions  = get_option('wp_music_player');
		
				$player = '<object type="application/x-shockwave-flash" data="'.MUSIC_PLAYER_PLUGIN_URL.'player2.swf" width="'.$options["width"].'" height="'.$options["height"].'">
						<param name="wmode" value="transparent" />
						<param name="movie" value="'.MUSIC_PLAYER_PLUGIN_URL.'player2.swf" />
						<param name="FlashVars" value="mp3='.$filenamestr.'&amp;title='.$titlestr.'&amp;autoplay='.$options["autoplay"].'&amp;loop='.$options["loop"].'&amp;showstop='.$options["showstop"].'&amp;showvolume='.$options["showvolume"].'&amp;bgcolor1='.$options["bgcolor1"].'&amp;bgcolor2='.$options["bgcolor2"].'&amp;slidercolor1='.$options["slidercolor1"].'&amp;slidercolor2='.$options["slidercolor2"].'&amp;buttoncolor='.$options["buttonovercolor"].'&amp;buttonovercolor='.$options["hvclr"].'&amp;sliderovercolor='.$options["sliderovercolor"].'&amp;shuffle='.$options["shuffle"].'&amp;width='.$options["width"].'&amp;height='.$options["height"].'&amp;textcolor='.$options["textcolor"].'&amp;playlistcolor='.$options["playlistcolor"].'&amp;playlistalpha='.$options["playlistalpha"].'&amp;currentmp3color='.$options["currentmp3color"].'&amp;showlist='.$options["showlist"].'&amp;showplaylistnumbers='.$options["showplaylistnumbers"].'&amp;scrollbarcolor='.$options["scrollbarcolor"].'&amp;scrollbarovercolor='.$options["scrollbarovercolor"].'&amp;loadingcolor='.$options["loadingcolor"].'" />			
					</object>';
			}
			else
			{
				$player="There is no audio file regarding this post";
			}
			echo $player;	
			
		}

		

	}

}

$Wp_Music_Player = new Wp_Music_Player;