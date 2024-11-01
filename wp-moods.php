<?php
/*
    Plugin Name: WP-Moods
    Plugin URI: http://www.driczone.net/blog/plugins/wp-moods
    Description: Calculate your general mood with several criteria. Get a timeline of all your moods. Don't forget to be happy !
    Author: Dric
    Version: 0.2
    Author URI: http://www.driczone.net
*/

/*  Copyright 2011 Dric  (email : cedric@driczone.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// let's initializing all vars

$wpmoods_plugin_version = "0.2"; //Don't change this, of course.
$options_wpmoods = get_site_option('wpmoods_settings');
define('WPMOODS_DIR', dirname(plugin_basename(__FILE__)));
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	if ( defined( 'WP_SITEURL' ) ) {
    define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
  }else {
    define( 'WP_CONTENT_URL', get_bloginfo('wpurl') . '/wp-content' );
  }
}
define('WPMOODS_URL', WP_CONTENT_URL . '/plugins/' . WPMOODS_DIR);

//Plugin can be translated, just put the .mo language file in the /lang directory
load_plugin_textdomain('wpmoods', WPMOODS_URL . '/lang/', WPMOODS_DIR . '/lang/');

//Plugin activation
function wpmoods_install()
{
    global $wpdb, $options_wpmoods, $wpmoods_plugin_version;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE `".$wpdb->prefix."users_moods` (
      `id` int(9) NOT NULL auto_increment,
      `user_id` bigint(20) NOT NULL,
      `mood_id` int(5) NOT NULL,
      `mood_level` int(2) NOT NULL,
      `mood_date` datetime NOT NULL,
      `mood_coeff` int(2),
      `mood_total` float(5,3),
      UNIQUE KEY `id` (`id`),
      KEY `user_id` (`user_id`),
      KEY `mood_id` (`mood_id`),
      KEY `mood_date` (`mood_date`)
      );";
    dbDelta($sql);

    $sql = "CREATE TABLE `".$wpdb->prefix."mood_criteria` (
      `mood_id` int(5) NOT NULL auto_increment,
      `mood_label` varchar(20) NOT NULL,
      `mood_default_coeff` int(2),
      UNIQUE KEY `mood_id` (`mood_id`)
      );";
    dbDelta($sql);
    
    switch (WP_LANG){
      case "fr_FR" :
        $opt_new_wpmoods['wpmoods_date_format'] = 'dd/mm/yyyy';
        break;
      default :
        $opt_new_wpmoods['wpmoods_date_format'] = 'yyyy/mm/dd';
    }
    $opt_new_wpmoods['wpmoods_date_relative'] = true;
    $opt_new_wpmoods['wpmoods_display_footer'] = true;
    $opt_new_wpmoods['wpmoods_coeff'] = true;
    $opt_new_wpmoods['wpmoods_version'] = $wpmoods_plugin_version;
    $opt_new_wpmoods['wpmoods_emo_set'] = 'Lin-048';
    add_option('wpmoods_settings', $opt_new_wpmoods);
    
    if ($options_wpmoods['wpmoods_version'] != $wpmoods_plugin_version){
      $options_wpmoods['wpmoods_version'] = $wpmoods_plugin_version;
      update_option('wpmoods_settings', $options_wpmoods);
    }
}

function wpmoods_data_install(){
  global $wpdb;
  $wpdb->query("INSERT INTO ".$wpdb->prefix."mood_criteria (mood_label, mood_default_coeff) VALUES('".__('Job', 'wpmoods')."', '2')");
  $wpdb->query("INSERT INTO ".$wpdb->prefix."mood_criteria (mood_label, mood_default_coeff) VALUES('".__('Love', 'wpmoods')."', '2')");
  $wpdb->query("INSERT INTO ".$wpdb->prefix."mood_criteria (mood_label, mood_default_coeff) VALUES('".__('Health', 'wpmoods')."', '2')");
}

register_activation_hook( __FILE__, 'wpmoods_install' );
register_activation_hook( __FILE__, 'wpmoods_data_install' );

function wpmoods_update_db_check() {
    global $wpmoods_plugin_version, $options_wpmoods;
    if ($options_wpmoods['wpmoods_version'] != $wpmoods_plugin_version) {
        wpmoods_install();
    }
}
add_action('plugins_loaded', 'wpmoods_update_db_check');

//admin panel
if ( is_admin() ) {

function wpmoods_settings_scripts(){
  wp_enqueue_script('flot', WPMOODS_URL .'/js/jquery.flot.min.js', 'jQuery');
}

function wpmoods_menu() {
  $wpmoods_timeline_page = add_menu_page('WP-Moods', 'WP-Moods', 'publish_posts', 'wpmoods_timeline', 'wpmoods_admin_timeline');
  add_submenu_page( 'wpmoods_timeline' , __('Moods timeline', 'wpmoods'), __('Moods timeline', 'wpmoods'), 'publish_posts', 'wpmoods_timeline', 'wpmoods_admin_timeline');
  $wpmoods_settings_page = add_submenu_page( 'wpmoods_timeline' , __('Wp-Moods Settings', 'wpmoods'), __('Wp-Moods Settings', 'wpmoods'), 'manage_options', 'wpmoods_admin', 'wpmoods_admin_settings');
  add_submenu_page( 'wpmoods_timeline' , __('Categories management', 'wpmoods'), __('Categories management', 'wpmoods'), 'manage_options', 'wpmoods_cat', 'wpmoods_admin_categories');
  add_action('admin_print_styles-'.$wpmoods_timeline_page, 'wpmoods_settings_scripts');
}
add_action('admin_menu', 'wpmoods_menu');

function wpmoods_admin_scripts(){
  wp_enqueue_script('jquery-ui-tabs');
  wp_enqueue_script('easyslider', WPMOODS_URL .'/js/easySlider1.7.js', 'jQuery');
  wp_enqueue_style('wpmoods_tabs', WPMOODS_URL .'/jquery.ui.tabs.css', false, '2.5.0', 'screen');
  wp_enqueue_style('wpmoods_admin', WPMOODS_URL .'/wp-moods.css', false, '2.5.0', 'screen');
}
add_action('admin_init','wpmoods_admin_scripts');
add_action('admin_head', 'wpmoods_js_header');

function wpmoods_admin_timeline(){
  wpmoods_mood_timeline();
}

function wpmoods_footer() {
  wpmoods_display_mood('',true);
}
if ($options_wpmoods['wpmoods_display_footer']){
  add_filter('update_footer', 'wpmoods_footer');
}

function wpmoods_admin_settings(){
  global $wpdb, $wpmoods_plugin_version;
  ?>
  <div class="wrap">
    <div id="icon-options-general" class="icon32"></div>
    <h2>WP-Moods</h2>
  <?php
  if (isset($_POST['submit']) and check_admin_referer('wpmoods-submit','wpmoods_admin_settings')){
    $options_wpmoods['wpmoods_date_format']=esc_html($_POST['wpmoods_date_format']);
    $options_wpmoods['wpmoods_date_relative']=$_POST['wpmoods_date_relative'];
    $options_wpmoods['wpmoods_display_footer']=$_POST['wpmoods_display_footer'];
    $options_wpmoods['wpmoods_coeff']=$_POST['wpmoods_coeff'];
    $options_wpmoods['wpmoods_emo_set']=esc_html($_POST['wpmoods_emo_set']);
    if (update_option('wpmoods_settings', $options_wpmoods)){
      echo '<div id="message" class="updated fade"><p><strong>'.__('Options saved.').'</strong></div>';
    }
  }elseif(isset($_POST['wpmoods-reset']) and check_admin_referer('wpmoods-reset','wpmoods_admin_reset')){
    $sql="DELETE FROM ".$wpdb->prefix."users_moods";
		if ( $results = $wpdb->query( $sql ) ){
		  echo '<div id="message" class="updated highlight fade"><p><strong>'.__('Moods deleted. The mood categories are still in database.', 'wpmoods').'</strong></div>';
		}
	}elseif(isset($_POST['wpmoods-uninst']) and check_admin_referer('wpmoods-uninst','wpmoods_admin_uninst')){
    delete_option('wpmoods_settings'); //delete activity settings
    $sql="DROP TABLE ".$wpdb->prefix."users_moods, ".$wpdb->prefix."mood_criteria";
		if ( $results = $wpdb->query( $sql ) ){
		  echo '<div id="message" class="updated highlight fade"><p><strong>'.sprintf(__('WP-Moods Plugin has been uninstalled. You can now desactivate this plugin : <a href="%s">Plugins Page</a>', 'wpmoods'),get_bloginfo('wpurl').'/wp-admin/plugins.php').'</strong></div>';
		}
  }
  $wpmoods_opt=get_option('wpmoods_settings');
  if (!is_array($wpmoods_opt)){
    echo '<span class="wpmoods_warning">'.sprintf(__('WP-Moods Plugin has been uninstalled. You can now desactivate this plugin : <a href="%s">Plugins Page</a>', 'wpmoods'),get_bloginfo('wpurl').'/wp-admin/plugins.php').'</span>';
  }else{
    extract($wpmoods_opt);
    ?>
    <script type="text/javascript">
      jQuery(function() {
          jQuery('#slider').tabs({ fxFade: true, fxSpeed: 'fast' });
      });
    </script>
    <div id="slider">    
      <ul id="tabs">
        <li><a href="#wpmoods_display"><?php _e('Display', 'wpmoods') ;?></a></li>
        <li><a href="#wpmoods_emoticon"><?php _e('Emoticons', 'wpmoods') ;?></a></li>
        <li><a href="#wpmoods_criteria"><?php _e('Categories') ;?></a></li>
        <li><a href="#wpmoods_reset"><?php _e('Reset/uninstall', 'wpmoods') ;?></a></li>
      </ul>
      <form action='' method='post'>
      <div id="wpmoods_display">
          <h2><?php _e('Display','wpmoods') ?></h2>
          <table class="form-table">
            <tr valign="top">
              <th scope="row"><?php _e('Date format','wpmoods') ?> : </th>
              <td>
                <select name="wpmoods_date_format">
                  <option <?php if($wpmoods_date_format == 'yyyy/mm/dd') {echo"selected='selected' ";} ?>value ="yyyy/mm/dd">yyyy/mm/dd</option>
                  <option <?php if($wpmoods_date_format == 'mm/dd/yyyy') {echo"selected='selected' ";} ?>value ="mm/dd/yyyy">mm/dd/yyyy</option>
                  <option <?php if($wpmoods_date_format == 'dd/mm/yyyy') {echo"selected='selected' ";} ?>value ="dd/mm/yyyy">dd/mm/yyyy</option>
                </select>&nbsp;
                <span class="description"><?php _e('For events that are more than a month old only, or if you dont use relative dates.','wpmoods') ?></span>
              </td>
    	       </tr><tr>
              <th><?php _e('Use relative dates', 'wpmoods') ?> : </th>
              <td>
                <input type="checkbox" <?php if($wpmoods_date_relative){echo 'checked="checked"';} ?> name="wpmoods_date_relative" />&nbsp;
                <span class="description"><?php _e('Relatives dates exemples : 1 day ago, 22 hours and 3 minutes ago, etc.','wpmoods') ?></span>
              </td>
    	       </tr><tr>
              <th><?php _e('Display mood in admin panel footer', 'wpmoods') ?> : </th>
              <td>
                <input type="checkbox" <?php if($wpmoods_display_footer){echo 'checked="checked"';} ?> name="wpmoods_display_footer" />&nbsp;
              </td>
    	       </tr>
    	       
    	     </table>
          <div class="submit"><input type='submit' class='button-primary' name='submit' value='<?php _e('Update options &raquo;') ?>' /></div>
        </div>
        <div id="wpmoods_emoticon">
          <h2><?php _e('Emoticons','wpmoods') ?></h2>
          <table class="form-table">
            <tr valign="top">
              <th scope="row"><?php _e('Emoticons set','wpmoods') ?> : </th>
              <td>
                <table div="wpmoods_table">
                  <?php
                  $rep = __DIR__."/img/icons/";
                  $dir = opendir($rep);
                  $desc = $wpmoods_txt = array();
                  while (false !== ($f = readdir($dir))) {
                   if(is_dir($rep.$f) and $f !="." and $f !=".."){
                    $dir2 = opendir($rep.$f."/");
                    while ($f2 = readdir($dir2)) {
                      $dir_ok = false;
                      if(is_file($rep.$f."/".$f2) and ($f2 == "readme.txt")) {
                        $fp = fopen($rep.$f."/".$f2,'r');
                        while (!feof($fp)) {
                          $desc[$f] .= fgets($fp)."<br />";
                        }
                        $desc[$f] = rtrim($desc[$f], "<br />");
                      }
                      if(is_dir($rep.$f."/".$f2) and $f2 !="." and $f2 !=".."){
                        $dir3 = opendir($rep.$f."/".$f2);
                        while ($f3 = readdir($dir3) and !$dir_ok) {
                          if(is_file($rep.$f."/".$f2."/".$f3) and ($f3 == '0.png')){
                            $dir_ok = true;
                            
                            for ($i=0;$i<5;$i++){
                              $wpmoods_txt[$f][$f2] .= '<img class="wpmoods_img" alt="'.wpmoods_mood_label($i).'" title="'.wpmoods_mood_label($i).'" src="'.WPMOODS_URL.'/img/icons/'.$f.'/'.$f2.'/'.$i.'.png" /> ';
                            }
                            $wpmoods_txt[$f][$f2] .= '</label><br />';
                          }
                        }
                      }
                    }
                   }
                  }
                  $txt = "";
                  foreach ($wpmoods_txt as $f=>$wpmoods_items){
                    foreach ($wpmoods_items as $f2=>$wpmoods_item){
                      if ($wpmoods_emo_set == $f."-".$f2){
                        $wpmoods_emo_selected = 'checked="checked"';
                      }else{
                        $wpmoods_emo_selected = '';
                      }
                      $txt .= '<tr><td><input type="radio" name="wpmoods_emo_set" '.$wpmoods_emo_selected.' value="'.$f.'-'.$f2.'" /></td><td>'.$desc[$f].'<br />'.__('Size', 'wpmoods').' : '.ltrim($f2, "0").'px</td>';
                      $txt .= '<td>'.$wpmoods_item.'</td></tr>';
                    }
                  }
                  echo $txt;
                  ?>
                </table>
              </td>
    	       </tr>
    	     </table>
          <div class="submit"><input type='submit' class='button-primary' name='submit' value='<?php _e('Update options &raquo;') ?>' /></div>
        </div>
        <div id="wpmoods_criteria">
          <h2><?php _e('Categories') ?></h2>
          <table class="form-table">
            <tr valign="top">
              <th><?php _e('Use Categories Importance', 'wpmoods') ?> : </th>
              <td>
                <input type="checkbox" <?php if($wpmoods_coeff){echo 'checked="checked"';} ?> name="wpmoods_coeff" />&nbsp;
                <span class="description"><?php _e('Adds the possibility of setting how a category affects your mood','wpmoods') ?></span>
              </td>
    	       </tr>
    	     </table>
          <div class="submit"><input type='submit' class='button-primary' name='submit' value='<?php _e('Update options &raquo;') ?>' /></div>
        </div>
        <?php wp_nonce_field('wpmoods-submit','wpmoods_admin_settings'); ?>
      </form>
      <div id="wpmoods_reset">
        <h2><?php _e('Reset/uninstall', 'wpmoods') ?></h2>
        <table class="form-table">
          </tr><tr>
            <th><?php _e('Empty moods data : ', 'wpmoods') ?></th>
            <td>
              <form name="wpmoods_form_reset" method="post">
                <?php
                  if ( function_exists('wp_nonce_field') )
  	                wp_nonce_field('wpmoods-reset','wpmoods_admin_reset');
                ?>
                <input type="submit" class="button" name="wpmoods-reset" value="<?php _e('Reset data', 'wpmoods') ?>" onclick="javascript:check=confirm('<?php _e('Empty moods table ? All your moods logs will be deleted.\n\nChoose [Cancel] to Stop, [OK] to proceed.\n', 'wpmoods') ?>');if(check==false) return false;" />
                <br /><span class="wpmoods_warning"><?php _e('Warning : cleaning wpmoods table erase all moods logs, but not the categories.', 'wpmoods') ?></span>
              </form>
            </td>
          </tr><tr>
            <th><?php _e('Uninstall plugin', 'wpmoods') ?> : </th>
            <td>
              <form name="wpmoods_form_uninst" method="post">
                <?php
                  if ( function_exists('wp_nonce_field') )
                    wp_nonce_field('wpmoods-uninst','wpmoods_admin_uninst');
                ?>
                <input type="submit" class="button" name="wpmoods-uninst" value="<?php _e('Uninstall plugin', 'wpmoods') ?>" onclick="javascript:check=confirm('<?php _e('Uninstall plugin ? Settings and moods logs will be deleted.\n\nChoose [Cancel] to Stop, [OK] to proceed.\n', 'wpmoods') ?>');if(check==false) return false;" />
                <br /><span class="wpmoods_warning"><?php _e('Warning : This will delete settings and moods table.', 'wpmoods') ?></span>
              </form>
            </td>
          </tr>
        </table>
      </div>
    </div>
  <?php
  } 
  ?>
    <br />
    <h4><?php echo sprintf(__('WP-Moods is a plugin by <a href="http://www.driczone.net">Dric</a>. Version <strong>%s</strong>.', 'wpmoods'), $wpmoods_plugin_version ) ?></h4>
  </div>
  <?php
}
function wpmoods_admin_categories(){
  global $wpdb, $options_wpmoods;
  if ($_GET['mode'] == 'edit_mood' && is_numeric($_GET['mood_id'])){
    $wpmoods_mood = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."mood_criteria WHERE mood_id = '".$_GET['mood_id']."'");
    ?>
    <div class="wrap">
      <div class="form-wrap">
        <div id="icon-edit" class="icon32"></div>
        <h2><?php _e('Edit Category') ?></h2>
        <form id="wpmoods_edit" action="?page=wpmoods_cat" method="post">
          <input type="hidden" name="wp_moods_action" value="edit_mood"/>
          <input type="hidden" name="mood_id" value="<?php echo $_GET['mood_id'] ?>"/>
          <?php wp_nonce_field('wpmoods_edit', 'wpmoods_admin_settings') ?>
          <table class="form-table">
            <tbody>
              <tr class="form-field form-required">
                <th scope="row" valign="top">
                  <label for="mood_label"><?php _e('Name') ?></label>
                </th>
                <td>
                  <input name="mood_label" id="mood_label" type="text" value="<?php echo $wpmoods_mood->mood_label ?>" size="40" aria-required="true"/>
                  <p class="description"><?php _e("The name of the category. Should be short.", 'wpmoods'); ?></p>
                </td>
              </tr>
              <?php if ($options_wpmoods['wpmoods_coeff'] == true){ ?>
              <tr class="wpmoods_form-field">
                <th scope="row" valign="top">
                  <label for="wpmoods_default_coeff"><?php _e("Default importance", 'wpmoods'); ?></label>
                </th>
                <td>
                  <?php wpmoods_coeff_radio_form("wpmoods_default_coeff", $wpmoods_mood->mood_default_coeff) ?>
                  <p class="description"><?php _e("Default importance to set to this category.", 'wpmoods'); ?></p>
                </td>
              </tr>
              <?php }else{ ?>
                <input type="hidden" name="wpmoods_default_coeff" value="2"/>
              <?php } ?>
            </tbody>
          </table>
          <p class="submit">
            <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Update') ?>"/>
          </p>
        </form>
      </div>
    </div>
    <?php
  }else{
    if ( $_POST['wp_moods_action'] == 'edit_mood' && isset($_POST['mood_id']) && is_numeric($_POST['wpmoods_default_coeff']) && check_admin_referer('wpmoods_edit', 'wpmoods_admin_settings')) {
      if ($wpdb->query("UPDATE ".$wpdb->prefix."mood_criteria SET mood_label = '".esc_html($_POST['mood_label'])."', mood_default_coeff='".$_POST['wpmoods_default_coeff']."' WHERE mood_id = '".$_POST['mood_id']."'")){
        echo '<div id="message" class="updated fade"><p><strong>'.__('Item updated.').'</strong></div>';
      }
    }
    if ( isset($_POST['wpmoods_list_action']) && isset($_POST['wpmoods_check']) && check_admin_referer('wpmoods-list', 'wpmoods_filter')) {
    	$doaction = $_POST['wpmoods_list_action'];
    	if ( 'delete' == $doaction ) {
        $wpmoods_list_del = implode(",", $_POST['wpmoods_check']);
        if ($wpdb->query("DELETE FROM ".$wpdb->prefix."mood_criteria WHERE mood_id IN(".$wpmoods_list_del.")")){
          if ($wpdb->query("DELETE FROM ".$wpdb->prefix."users_moods WHERE mood_id IN(".$wpmoods_list_del.")")){
            echo '<div id="message" class="updated fade"><p><strong>'.__('Items deleted.').'</strong></div>';
          }
        }
    	}
    }
    if ( isset($_POST['wpmoods_cat_name']) && isset($_POST['wpmoods_default_coeff']) && check_admin_referer('wpmoods_add_cat', 'wpmoods_admin_settings')) {
      if ($wpdb->query("INSERT INTO ".$wpdb->prefix."mood_criteria (mood_label, mood_default_coeff) VALUES('".$_POST['wpmoods_cat_name']."', '".$_POST['wpmoods_default_coeff']."')")){
        echo '<div id="message" class="updated fade"><p><strong>'.__('Item added.').'</strong></div>';
      }
    }
    ?>
    <div class="wrap">
      <div id="icon-options-general" class="icon32"></div>
      <h2><?php _e('WP-Moods Categories', 'wpmoods') ?></h2>
      <div id="col-container">
        <div id="col-right">
          <div class="col-wrap">
            <form id="wpmoods_filter" action="?page=wpmoods_cat" method="post">
              <?php wp_nonce_field('wpmoods-list', 'wpmoods_filter') ?>
              <div class="tablenav">
                <div class="alignleft actions">
                  <select name="wpmoods_list_action">
                    <option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
                    <option value="delete"><?php _e('Delete'); ?></option>
                  </select>
                  <input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
                </div>
                <br class="clear" />
              </div>
              <table id="activity-admin" class="widefat">
                <thead>
                  <tr>
                    <th scope="col" id="cb" class="manage-column column-cb check-column"><input type="checkbox" /></th>
                    <th scope="col" class="manage-column"><?php _e("Name"); ?></th>
                    <?php if ($options_wpmoods['wpmoods_coeff'] == true){ ?>
                    <th scope="col" class="manage-column" colspan="2"><?php _e("Default importance", 'wpmoods'); ?></th>
                    <?php } ?>
                    <th scope="col" class="manage-column"><?php _e("Id"); ?></th>                
                  </tr>
                </thead>
                <tfoot>
                  <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
                    <th scope="col" class="manage-column"><?php _e("Name"); ?></th>
                    <?php if ($options_wpmoods['wpmoods_coeff'] == true){ ?>
                    <th scope="col" class="manage-column" colspan="2"><?php _e("Default importance", 'wpmoods'); ?></th>
                    <?php } ?>
                    <th scope="col" class="manage-column"><?php _e("Id"); ?></th>
                  </tr>
                </tfoot>
                <tbody>
              <?php
              $wpmoods_alt = 0;
              $sql  = "SELECT * FROM ".$wpdb->prefix."mood_criteria";
              if ( $moods = $wpdb->get_results( $sql)){
                foreach ( (array) $moods as $mood ){
                  if ($wpmoods_alt == 1){$wpmoods_alt_class = 'class="alternate"';}else{$wpmoods_alt_class = '';}
                  $mood_coeff = wpmoods_coeff_label($mood->mood_default_coeff);
                  echo '<tr '.$wpmoods_alt_class.'>';
                  ?>
                  <th scope="row" class="check-column">
                    <input type="checkbox" name="wpmoods_check[]" value="<?php echo $mood->mood_id ?>" />
                  </th>
                  <td>
                    <a class="row-title" title="<?php _e('Edit Category')?> <?php echo $mood->mood_label ?>" href="?page=wpmoods_cat&mode=edit_mood&mood_id=<?php echo $mood->mood_id ?>"><?php echo $mood->mood_label ?></a>
                  </td>
                  <?php if ($options_wpmoods['wpmoods_coeff'] == true){ ?>
                  <td><?php echo $mood_coeff ?></td>
                  <td>
                    <img class="wpmoods_img" alt="<?php echo $mood_coeff ?>" src="<?php echo WPMOODS_URL.'/img/icons/admin/025/'.$mood->mood_default_coeff ?>.png" />
                  </td>
                  <?php } ?>
                  <td><?php echo $mood->mood_id ?></td>
                  <?php
                  if ($wpmoods_alt == 1){$wpmoods_alt = 0;}else{$wpmoods_alt = 1;}
                }
              }
              ?>
                </tbody>
              </table>
            </form>
          </div>
        </div>
        <div id="col-left">
          <div class="col-wrap">
            <div class="form-wrap">
              <h3><?php _e("Add New Category"); ?></h3>
              <form id="wpmoods_add_cat" method="post" action="?page=wpmoods_cat" class="validate">
                <?php wp_nonce_field('wpmoods_add_cat', 'wpmoods_admin_settings'); ?>
                <div class="form-field form-required">
                  <label for="wpmoods_cat_name"><?php _e("Name"); ?></label>
                  <input name="wpmoods_cat_name" id="wpmoods_cat_name" type="text" value="" size="40" aria-required="true"/>
                  <p><?php _e("The name of the category. Should be short.", 'wpmoods'); ?></p>
                </div>
                <?php if ($options_wpmoods['wpmoods_coeff'] == true){ ?>
                <div class="wpmoods_form-field">
                  <?php _e("Default importance", 'wpmoods'); ?>
                  <?php wpmoods_coeff_radio_form("wpmoods_default_coeff") ?>
                  <p><?php _e("Default importance to set to this category.", 'wpmoods'); ?></p>
                </div>
                <?php } ?>
                <br class="clear" />
                <p class="submit">
                <input type="submit" name="submit" id="submit" class="button" value="<?php _e("Add New Category"); ?>"/>
                </p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php
  }
}
function wpmoods_dashboard_widget() {
  wpmoods_save_mood();
	wpmoods_add_mood();
}

function wpmoods_add_dashboard_widgets() {
	wp_add_dashboard_widget('wpmoods_dashboard_widget', 'Add a mood', 'wpmoods_dashboard_widget');	
} 
add_action('wp_dashboard_setup', 'wpmoods_add_dashboard_widgets' );

} //-- admin functions end

//front and backend functions
function wpmoods_display_mood($mood_user='', $wpmoods_footer = false){
  global $wpdb, $user_ID, $options_wpmoods;
  if ($mood_user == '' and isset($user_ID)){
    $mood_user = $user_ID;
  }elseif($mood_user == ''){
    $mood_user = 1;
  }
  $no_mood = false;
  $wpmoods_emo_set = explode("-",$options_wpmoods['wpmoods_emo_set']);
  $wpmoods_row = $wpdb->get_row("SELECT mood_date, mood_total FROM ".$wpdb->prefix."users_moods WHERE user_id = ".$mood_user." ORDER BY id DESC LIMIT 1");
  $total_mood = $wpmoods_row->mood_total;
  $last_date = $wpmoods_row->mood_date;
  if($total_mood == ""){  //if a category has been deleted and the last mood category has the total mood, recalculate the total mood.
    $sql  = "SELECT * FROM ".$wpdb->prefix."mood_criteria";
    if ( $moods = $wpdb->get_results( $sql)){
      $total_mood = $coeff_mood = $last_date = 0;
      foreach ( (array) $moods as $mood ){
        $last_mood = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users_moods WHERE user_id = '".$mood_user."' AND mood_id = '".$mood->mood_id."' ORDER BY mood_date DESC LIMIT 1");
        if (strtotime($last_mood->mood_date) > strtotime($last_date)){
          $last_date = $last_mood->mood_date;
        }
        $total_mood = $total_mood + ($last_mood->mood_level*$last_mood->mood_coeff);
        $coeff_mood = $coeff_mood + $last_mood->mood_coeff;
      }
      if ($coeff_mood != 0 ){
        $total_mood = $total_mood / $coeff_mood;
      }else{
        $no_mood = true;
      }
    }
  }
  $tot_mood_int = number_format($total_mood, 0);
  if (!$no_mood){
    if (!$wpmoods_footer){
      echo '<p>'.__('Mood', 'wpmoods').' : '.wpmoods_mood_label($tot_mood_int).' ('.number_format($total_mood, 1).'/4) <img class="wpmoods_img" title="'.wpmoods_mood_label($tot_mood_int).'" alt="'.wpmoods_mood_label($tot_mood_int).'" src="'.WPMOODS_URL.'/img/icons/'.$wpmoods_emo_set[0].'/'.$wpmoods_emo_set[1].'/'.$tot_mood_int.'.png" /></p>';
      echo '<p>'.__('Last updated', 'wpmoods').' : '.wpmoods_nicetime($last_date).'</p>';
    }else{
      echo '<span id="wpmoods_footer">'.__('Mood', 'wpmoods').' : <img class="wpmoods_img" title="'.wpmoods_mood_label($tot_mood_int).' : '.number_format($total_mood, 1).'/4" alt="'.wpmoods_mood_label($tot_mood_int).'" src="'.WPMOODS_URL.'/img/icons/admin/025/'.$tot_mood_int.'.png" /></span> • ';
    }
  }else{
    if (!$wpmoods_footer){
      echo '<p>'.__('Mood', 'wpmoods').' : '.__('No mood yet', 'wpmoods').' <img class="wpmoods_img" title="'.__('No mood yet', 'wpmoods').'" alt="'.wpmoods_mood_label(0).'" src="'.WPMOODS_URL.'/img/icons/'.$wpmoods_emo_set[0].'/'.$wpmoods_emo_set[1].'/0.png" /></p>';
    }else{
      echo '<span id="wpmoods_footer">'.__('Mood', 'wpmoods').' : <img class="wpmoods_img" title="'.__('No mood yet', 'wpmoods').'" alt="'.wpmoods_mood_label(0).'" src="'.WPMOODS_URL.'/img/icons/admin/025/0.png" /></span> • ';
    }
  }
}

function wpmoods_mood_timeline($mood_user=''){
  global $wpdb, $user_ID;
  if ($mood_user == ''){
    $mood_user = $user_ID;
  }
  $wpmoods_disp_array=array(
                            "Day" => '86400', 
                            "Week" => '604800', 
                            "Month" => '2592000', 
                            "Year" => '31536000', 
                            "All time" => '0'
                            );
  if ($_POST['wpmoods_list_action'] and check_admin_referer('wpmoods_timeline', 'wpmoods_timeline')){
    $wpmoods_display = esc_html($_POST['wpmoods_list_action']);
  }else{
    $wpmoods_display = "Week";
  }
  $sql  = "SELECT * FROM ".$wpdb->prefix."mood_criteria ORDER BY mood_id ASC";
  if ( $moods = $wpdb->get_results( $sql)){
    $wpmoods_cat_text = $wpmoods_cat_data = $wpmoods_mood_text = "";
    $wpmoods_now = time();
    $wpmoods_mood_text .='var m1 = [';
    $wpmoods_cat_text .= 'var datasets = {';
    foreach ( (array) $moods as $mood ){
      $wpmoods_cat_text .='c'.$mood->mood_id.': {';
      $wpmoods_cat_text .='label: "'.$mood->mood_label.'", data: ['; 
      $sql  = "SELECT * FROM ".$wpdb->prefix."users_moods WHERE user_id = '".$mood_user."' AND mood_id = '".$mood->mood_id."' ORDER BY mood_id ASC, mood_date ASC";
      if ( $users_moods = $wpdb->get_results( $sql)){
        foreach ( (array) $users_moods as $user_mood ){
          if ($user_mood->mood_total){
            $wpmoods_mood_total = $user_mood->mood_total;
            $wpmoods_mood_text .='['.(strtotime($user_mood->mood_date)*1000).','.$wpmoods_mood_total.'],';
          }
          $wpmoods_cat_text .= '['.(strtotime($user_mood->mood_date)*1000).','.$user_mood->mood_level.'],';
        }
      }
      $wpmoods_cat_text .= '['.($wpmoods_now*1000).','.$user_mood->mood_level.']';
      $wpmoods_cat_text .=']},';
    }
    
    $wpmoods_mood_text .= '['.($wpmoods_now*1000).','.$wpmoods_mood_total.']];';    
    $wpmoods_cat_text = rtrim($wpmoods_cat_text, ",");
    $wpmoods_cat_text .='};';
    
    ?>
  <div class="wrap">
    <div id="icon-stats" class="icon32"></div>
    <h2><?php _e('WP-Moods Timeline', 'wpmoods') ?></h2>
    <br />
    <div class="tablenav">
      <form id="wpmoods_timeline" method="post" action="?page=wpmoods_timeline">
        <p class="search-box">
          <?php _e("Display", 'wpmoods'); ?> : 
          <select name="wpmoods_list_action">
            <?php
            $wpmoods_disp_time = '604800';
            foreach ($wpmoods_disp_array as $wpmoods_disp_label => $wpmoods_disp_value){
              if ($wpmoods_disp_label == $wpmoods_display){
                $selected = 'selected="selected"';
                $wpmoods_disp_time = $wpmoods_disp_value;
                if ($wpmoods_disp_value == '0'){
                  $wpmoods_min = '';
                }else{
                  $wpmoods_min = ($wpmoods_now*1000) - ($wpmoods_disp_time*1000);
                  $wpmoods_min = 'min : '.$wpmoods_min.',';
                }
              }else{
                $selected = '';
              }
              echo '<option value="'.$wpmoods_disp_label.'" '.$selected.'>'.__($wpmoods_disp_label, 'wpmoods').'</option>';
            }
            ?>
          </select>
          <input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
          <?php wp_nonce_field('wpmoods_timeline', 'wpmoods_timeline') ?>
        </p>
      </form>
      <br class="clear" />
    </div>
    <div id="wpmoods_wrap">
      <div class="metabox-holder">
        <div class="postbox">
          <h3><?php _e('Categories Timeline', 'wpmoods') ?></h3>
          <div class="wpmoods_inside">
            <div id="wpmoods_cat_graphs" style="width:650px;height:250px;"></div>
            <p id="choices"><?php _e('Show category','wpmoods') ?> : </p>
          </div>
        </div>
        <div class="postbox">
          <h3><?php _e('Mood Timeline', 'wpmoods') ?></h3>
          <div class="wpmoods_inside">
            <div id="wpmoods_mood_graphs" style="width:650px;height:250px;"></div>
          </div>
        </div>
      </div>
    </div>
  <script type="text/javascript">
  
  function moodLabel(moodId){
      var mood = '';
      moodId = moodId.toFixed(0)
      switch (moodId){
        case '0' :
          mood = '<?php _e("So Bad", 'wpmoods'); ?>';
          break;
        case '1' :
          mood = '<?php _e("Not good", 'wpmoods'); ?>';
          break;
        case '2' :
          mood = '<?php _e("Average", 'wpmoods'); ?>';
          break;
        case '3' :
          mood = '<?php _e("Pretty good", 'wpmoods'); ?>';
          break;
        case '4' :
          mood = '<?php _e("Top !", 'wpmoods'); ?>';
          break;
      }
      return mood;
    }
  jQuery().ready(function ($) {
    <?php echo $wpmoods_cat_text ?>
    <?php echo $wpmoods_mood_text ?>
    var options = {
      series: {
        lines: { show: true },
        points: { show: true }
      },
      grid: {
        hoverable: true,
        clickable: true
      },
      xaxis: { 
        mode: "time",
        <?php echo ($wpmoods_min) ?>
        max: <?php echo ($wpmoods_now*1000) ?>
      },
      yaxis: {
        min: -1,
        max: 5,
        autoscaleMargin: 0.2,
        tickFormatter: function formatter(val, axis) {
                        return moodLabel(val);
                        }
      }
    };
    
    var i = 0;
    $.each(datasets, function(key, val) {
        val.color = i;
        ++i;
    });
    
    // insert checkboxes 
    var choiceContainer = $("#choices");
    choiceContainer.append('<br />');
    $.each(datasets, function(key, val) {
        choiceContainer.append('<br /><input type="checkbox" name="' + key +
                               '" checked="checked" id="id' + key + '">' +
                               '<label for="id' + key + '">'
                                + val.label + '</label>');
    });
    choiceContainer.find("input").click(plotAccordingToChoices);

    
    function plotAccordingToChoices() {
        var data = [];

        choiceContainer.find("input:checked").each(function () {
            var key = $(this).attr("name");
            if (key && datasets[key])
                data.push(datasets[key]);
        });

        if (data.length > 0)
            $.plot($("#wpmoods_cat_graphs"), data, options);
    }
    plotAccordingToChoices();
    
    $.plot($("#wpmoods_mood_graphs"), [ { label: '<?php _e("Mood", "wpmoods") ?>', data: m1} ], options);

    function showTooltip(x, y, contents) {
      $('<div id="tooltip">' + contents + '</div>').css( {
        position: 'absolute',
        display: 'none',
        top: y + 5,
        left: x + 5,
        border: '1px solid #fdd',
        padding: '2px',
        'background-color': '#fee',
        opacity: 0.80
      }).appendTo("body").fadeIn(200);
    }
    var previousPoint = null;
    $("#wpmoods_cat_graphs").bind("plothover", function (event, pos, item) {
      $("#x").text(pos.x.toFixed(2));
      $("#y").text(pos.y.toFixed(2));
      var moodDate = new Date()
      //moodDate.setTime(x.toFixed(0));
      if (item) {
        if (previousPoint != item.dataIndex) {
          previousPoint = item.dataIndex;
          $("#tooltip").remove();
          var x = item.datapoint[0].toFixed(2),
              y = item.datapoint[1].toFixed(2);
          moodDate.setTime(x) ;
          showTooltip(item.pageX, item.pageY, item.series.label + " <?php _e("feeling at", "wpmoods") ?> " + moodDate.toLocaleString() + " : " + moodLabel(parseFloat(y)));
        }
      } else {
        $("#tooltip").remove();
        previousPoint = null;            
      }
    });
    $("#wpmoods_mood_graphs").bind("plothover", function (event, pos, item) {
      $("#x").text(pos.x.toFixed(2));
      $("#y").text(pos.y.toFixed(2));
      var moodDate = new Date();
      if (item) {
        if (previousPoint != item.dataIndex) {
          previousPoint = item.dataIndex;
          $("#tooltip").remove();
          var x = item.datapoint[0].toFixed(2),
              y = item.datapoint[1].toFixed(2);
          moodDate.setTime(x) ;
          showTooltip(item.pageX, item.pageY, item.series.label + " <?php _e("feeling at", "wpmoods") ?> " + moodDate.toLocaleString() + " : " + moodLabel(parseFloat(y)));
        }
      } else {
        $("#tooltip").remove();
        previousPoint = null;            
      }
    });
  });
  </script>
  <?php 
  }else{
    echo "error !";
  }
}

function wpmoods_add_mood(){
  global $wpdb, $options_wpmoods;
  $sql  = "SELECT * FROM ".$wpdb->prefix."mood_criteria";
  ?>
	<form id="wpmoods_add_mood" method="post" action="">
    <div id="wpmoods">
      <ul>
  <?php
  $i=0;
  if ( $moods = $wpdb->get_results( $sql)){
    foreach ( (array) $moods as $mood ){
      $i++;
      echo '<li>';
      echo '<h2>'.$mood->mood_label.'</h2>';
      if ($options_wpmoods['wpmoods_coeff'] == true){
        wpmoods_coeff_radio_form("wpmoods_coeff_".$i, $mood->mood_default_coeff, __("Importance", "wpmoods")." : ");
      }
      wpmoods_mood_radio_form("wpmoods_mood_".$i);
      echo '<input type="hidden" name="wpmoods_mood_id_'.$i.'" value="'.$mood->mood_id.'"/>';
      echo '</li>';
    }
  }
  ?>
      </ul>
    </div>
    <input type="hidden" name="wpmoods_nb" value="<?php echo $i ?>"/>
    <?php wp_nonce_field('wpmoods_add_mood', 'wpmoods'); ?>
  </form>
  <br class="clear" />
  <?php
}

function wpmoods_save_mood(){
  global $wpdb, $user_ID, $options_wpmoods;
  if (isset($_POST["wpmoods_nb"]) && check_admin_referer('wpmoods_add_mood', 'wpmoods')){
    $f = true;
    $wpmoods_level_total = $wpmoods_coeff_total = 0;
    $wpmoods_date = date("Y-m-d H:i:s", time());
    for ($i=1;$i<=$_POST["wpmoods_nb"];$i++){
      $wpmoods_level = $_POST["wpmoods_mood_".$i];
      $wpmoods_id = $_POST["wpmoods_mood_id_".$i];
      if ($wpmoods_level != "-999"){
        if ($options_wpmoods['wpmoods_coeff'] == true){ 
          $wpmoods_coeff = $_POST["wpmoods_coeff_".$i];
        }else{
          $wpmoods_coeff = '2';
        }
        if (!$wpdb->query("INSERT INTO ".$wpdb->prefix."users_moods (user_id, mood_id, mood_level, mood_date, mood_coeff) VALUES('".$user_ID."', '".$wpmoods_id."', '".$wpmoods_level."', '".$wpmoods_date."', '".$wpmoods_coeff."')")){
          $f = false;
        }
        $wpmoods_level_total = $wpmoods_level_total + ($wpmoods_level*$wpmoods_coeff);
        $wpmoods_coeff_total = $wpmoods_coeff_total + $wpmoods_coeff;
      }else{
        $last_mood = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."users_moods WHERE user_id = '".$user_ID."' AND mood_id = '".$wpmoods_id."' AND mood_date < '".$wpmoods_date."' ORDER BY mood_date DESC LIMIT 1");
        $wpmoods_level_total = $wpmoods_level_total + ($last_mood->mood_level * $last_mood->mood_coeff);
        $wpmoods_coeff_total = $wpmoods_coeff_total + $last_mood->mood_coeff;
      }
    }
    if ($f == true){
      $wpmoods_level_total = $wpmoods_level_total / $wpmoods_coeff_total;
      $wpdb->query("UPDATE ".$wpdb->prefix."users_moods SET mood_total = '".$wpmoods_level_total."' WHERE user_id = '".$user_ID."' AND mood_date = '".$wpmoods_date."' ORDER BY id DESC, mood_date DESC LIMIT 1");
      echo '<div id="message" class="updated fade"><p><strong>'.__('Mood Added. Calculated mood : ', "wpmoods").wpmoods_mood_label($wpmoods_level_total).' ('.number_format($wpmoods_level_total, 3).'/4)</strong></div>';
      unset($_POST["wpmoods_nb"]);
    }
  }
}

function wpmoods_header(){
  $altcss = TEMPLATEPATH.'/wp-moods.css';
  echo '<link type="text/css" rel="stylesheet" href="';
  if (@file_exists($altcss)){
    echo get_bloginfo('stylesheet_directory');
  }else{
    echo WPMOODS_URL;
  }
  echo '/wp-moods.css" />';
  wp_enqueue_script('flot', WPMOODS_URL .'/js/jquery.flot.min.js', 'jQuery');
}
add_action('wp_head', 'wpmoods_header');
function wpmoods_js_header(){
  ?>
  <script type="text/javascript">
  (function($) {$(document).ready(function(){	
	   $("#wpmoods").easySlider({
	     prevText: '<?php _e("Previous") ?>',
	     nextText: '<?php _e("Next") ?>',
	     endText:  '<?php _e("Add Mood") ?>',
	   });
    });	
  })(jQuery);
  </script>
  <?php
}

function wpmoods_coeff_label($wpmoods_coeff){
  $wpmoods_coeff = number_format($wpmoods_coeff, 0);
  switch ($wpmoods_coeff){
    case "0" :
      $mood_coeff = __("None", 'wpmoods');
      break;
    case "1" :
      $mood_coeff = __("Small", 'wpmoods');
      break;
    case "2" :
      $mood_coeff = __("Average", 'wpmoods');
      break;
    case "3" :
      $mood_coeff = __("High", 'wpmoods');
      break;
    case "4" :
      $mood_coeff = __("Essential", 'wpmoods');
      break;
  }
  return $mood_coeff;
}

function wpmoods_mood_label($wpmoods_mood){
  $wpmoods_mood = number_format($wpmoods_mood, 0);
  switch ($wpmoods_mood){
    case "0" :
      $mood = __("So Bad", 'wpmoods');
      break;
    case "1" :
      $mood = __("Not good", 'wpmoods');
      break;
    case "2" :
      $mood = __("Average", 'wpmoods');
      break;
    case "3" :
      $mood = __("Pretty good", 'wpmoods');
      break;
    case "4" :
      $mood = __("Top !", 'wpmoods');
      break;
  }
  return $mood;
}

function wpmoods_coeff_radio_form($wpmoods_form_name="wpmoods_coeff", $wpmoods_coeff="2", $title = ""){
  echo '<fieldset class="wpmoods_coeff">';
  if ($title != ""){
    echo '<p>'.$title.'</p>';
  }
  for ($i=4; $i>=0; $i--){
    if ($i==$wpmoods_coeff){
      $selected='checked="checked"';
    }else{
      $selected='';
    }
    echo '<label><input type="radio" name="'.$wpmoods_form_name.'" value="'.$i.'" '.$selected.' />'.wpmoods_coeff_label($i).' </label>';
  }
  echo '</fieldset>';
}

function wpmoods_mood_radio_form($wpmoods_form_name="wpmoods_mood", $wpmoods_last="2"){
  echo '<fieldset class="wpmoods_mood">';
  echo '<p>'.__("I'm feeling", "wpmoods").' : </p>';
  for ($i=4; $i>=0; $i--){
    if ($i==$wpmoods_last){
      $selected='checked="checked"';
    }else{
      $selected='';
    }
    echo '<label><input type="radio" name="'.$wpmoods_form_name.'" value="'.$i.'" '.$selected.' />'.wpmoods_mood_label($i).' </label>';
  }
  echo '<label class="wpmoods_warning"><input type="radio" name="'.$wpmoods_form_name.'" value="-999" />'.__("Ignore this", "wpmoods").'</label>';
  echo '</fieldset>';
}

function wpmoods_stream_shortcode ($attr='') {
    /*$attr = shortcode_atts(array('number'   => '-1',
                                 'title'    => '',), $attr);*/
    //$wpmoods_return = wpmoods_display_mood().wpmoods_mood_timeline();
    $wpmoods_return = wpmoods_display_mood();
    return $wpmoods_return;
}
add_shortcode('WPMOODS', 'wpmoods_stream_shortcode');

//other functions
function wpmoods_nicetime($posted_date, $admin=false) {
    // Adapted for something found on Internet, but I forgot to keep the url...
    $wpmoods_opt=get_option('wpmoods_settings');
    $date_relative = $wpmoods_opt['wpmoods_date_relative'];
    $date_format = $wpmoods_opt['wpmoods_date_format'];
    $posted_date = date("Y-m-d H:i:s", strtotime($posted_date) + ( get_option( 'gmt_offset' ) * 3600 ));
    $in_seconds = strtotime($posted_date);
    $diff = strtotime(date("Y-m-d H:i:s", time() + ( get_option( 'gmt_offset' ) * 3600 )));   
    $relative_date = '';
    $diff = $diff - $in_seconds;
    //echo "time function : ".time()." mysqldate : ".date("Y-m-d H:i:s", time())." - time : ".date_i18n("j F Y G \h i \m\i\n",( time() + ( get_option( 'gmt_offset' ) * 3600 ) ))." - in_seconds : ".date_i18n("j F Y G \h i \m\i\n",$in_seconds)." = diff : $diff - gmt_option : ".get_option('gmt_offset')."<br />";
    $months = floor($diff/2592000);
    $diff -= $months*2419200;
    $weeks = floor($diff/604800);
    $diff -= $weeks*604800;
    $days = floor($diff/86400);
    $diff -= $days*86400;
    $hours = floor($diff/3600);
    $diff -= $hours*3600;
    $minutes = floor($diff/60);
    $diff -= $minutes*60;
    $seconds = $diff;
    if ($months>0 or !$date_relative or $admin) {
        // over a month old, just show date
        if (!$date_relative or $admin){
          $h = substr($posted_date,10);
        } else {
          $h = '';
        }
        switch ($date_format){
          case 'dd/mm/yyyy':
            return substr($posted_date,8,2).'/'.substr($posted_date,5,2).'/'.substr($posted_date,0,4).$h;
            break;
          case 'mm/dd/yyyy':
            return substr($posted_date,5,2).'/'.substr($posted_date,8,2).'/'.substr($posted_date,0,4).$h;
            break;
          case 'yyyy/mm/dd':
          default:
            return substr($posted_date,0,4).'/'.substr($posted_date,5,2).'/'.substr($posted_date,8,2).$h;
            break;
        }
    } else {
        if ($weeks>0) {
            // weeks and days
            $relative_date .= ($relative_date?', ':'').$weeks.' '.($weeks>1? __('weeks', 'wpmoods'):__('week', 'wpmoods'));
            $relative_date .= $days>0?($relative_date?', ':'').$days.' '.($days>1? __('days', 'wpmoods'):__('day', 'wpmoods')):'';
        } elseif ($days>0) {
            // days and hours
            $relative_date .= ($relative_date?', ':'').$days.' '.($days>1? __('days', 'wpmoods'):__('day', 'wpmoods'));
            $relative_date .= $hours>0?($relative_date?', ':'').$hours.' '.($hours>1? __('hours', 'wpmoods'):__('hour', 'wpmoods')):'';
        } elseif ($hours>0) {
            // hours and minutes
            $relative_date .= ($relative_date?', ':'').$hours.' '.($hours>1? __('hours', 'wpmoods'):__('hour', 'wpmoods'));
            $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' '.($minutes>1? __('minutes', 'wpmoods'):__('minute', 'wpmoods')):'';
        } elseif ($minutes>0) {
            // minutes only
            $relative_date .= ($relative_date?', ':'').$minutes.' '.($minutes>1? __('minutes', 'wpmoods'):__('minute', 'wpmoods'));
        } else {
            // seconds only
            $relative_date .= ($relative_date?', ':'').$seconds.' '.($seconds>1? __('seconds', 'wpmoods'):__('second', 'wpmoods'));
        }
    }
    // show relative date and add proper verbiage
    return sprintf(__('%s ago', 'wpmoods'), $relative_date);
}

//Widget :

function wpmoods_load_widgets() {
	register_widget( 'wpmoods_Widget' );
}
class wpmoods_Widget extends WP_Widget {

	function wpmoods_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'wpmoods', 'description' => __('Display my mood', 'wpmoods') );

		/* Widget control settings. */
		$control_ops = array( 'height' => 350, 'id_base' => 'wpmoods' );

		/* Create the widget. */
		$this->WP_Widget( 'wpmoods', __('Wp-Moods Widget', 'wpmoods'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );

		echo $before_widget;
		if ( $title )
			$title =  $before_title . $title . $after_title;
		echo $title;
    wpmoods_display_mood();
	  echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {

		$defaults = array( 'title' => __('My Mood', 'wpmoods'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title :', 'wpmoods'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
	<?php
	}
}
add_action( 'widgets_init', 'wpmoods_load_widgets' );
?>
