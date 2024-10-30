<?php
/*
Plugin Name: JS File Selector
Plugin URI: http://www.chrgiga.com/js-file-selector
Description: Add Javascript files and/or Javascript functions to any single page or post
Version: 1.0.3
Author: Christian Gil
Author URI: http://www.chrgiga.com
License: GPLv3 or later
Copyright 2014 Christian Gil
*/


/* Adds a box to the main column on the Post and Page edit screens */
function gil_js_file_selector_add_custom_box()
{
    $screens = array( 'post', 'page' );

    foreach ($screens as $screen) {
      add_meta_box(
        'js-file-selector',
        __('Select Javascript files and/or write your Javascript functions', 'jsfileselector'),
        'gil_js_file_selector_inner_custom_box',
        $screen
      );
    }
}
/* Get and list the js files */
function gil_get_js_file($jsfiles)
{
  // Recursive function for read directories and subdirectories
  function gil_read_js_directories($directory, &$files)
  {
  if (is_dir($directory)) {
    if ($open_dir = opendir($directory)) {
      while (($file = readdir($open_dir)) !== false) {
        if ($file != '.' AND $file != '..') {
          // Verify if is directory or file
          if (is_dir( $directory.'/'.$file)) {
            gil_read_js_directories($directory.'/'.$file , $files);
          } else {
            // Ready File
            $explodefile = explode('.', $file);

            if (is_file($directory.'/'.$file) && end($explodefile) == 'js') {
              $files[dirname($directory.'/'.$file)][] = $directory.'/'.$file;
            }
          }
        }
      }
      closedir($open_dir);
      }
    }
  }

  // Get path of actual template
  $path_template = get_template_directory();
  $files = array();
  gil_read_js_directories($path_template, $files);
  $select = '';

  foreach($jsfiles as $file) {
    $filedata = explode(':', $file);
    $jsfile = $filedata[0];
    $position = count($filedata) > 1 && $filedata[1] != '' ? $filedata[1] : 'head';
    $option_js = '';
    $option_group = '';
    $select .= '<div class="js-file-select-div"><select name="gil_js_file_selector_file[]">';

    foreach ($files as $js_dir => $js_list) {
      $name_dir = str_replace($path_template, '', $js_dir);
      $name_dir = $name_dir == '' ? '/' : $name_dir;

      if ($name_dir != '' && $option_group != $name_dir) {
        $option_js .= '<optgroup label="'.$name_dir.'">';
        $option_group = $name_dir;
      } else {
        $option_js .= '</optgroup>';
      }
      foreach ($js_list as $js) {
        $selected = $js == $jsfile ? 'selected="selected"' : '';
        $option_js .= '<option value="'.$js.'" '.$selected.'>'.basename($js).'</option>';
      }
    }
    $option_js = $option_js == '' ? '<option value="">Javascript files not found</option>' : '<option value="">Without Javascript file</option>'.$option_js.'</optgroup>';
    $select .= $option_js.
      '</select><br />
      <label class="radio-label">Place:</label>
      <input type="radio" name="js-file-selector-position-0" value="head" '.($position == 'head' ? 'checked' : '').' />Head
      <input type="radio" name="js-file-selector-position-0" value="footer" '.($position == 'footer' ? 'checked' : '').' />Footer
      </div>';
  }

  return $select;
}
/* Prints the box content */
function gil_js_file_selector_inner_custom_box($post)
{
  // Use nonce for verification
  wp_nonce_field(plugin_basename( __FILE__ ), 'gil_js_file_selector_chrgiga');

  // The actual fields for data entry
  // Use get_post_meta to retrieve an existing value from the database and use the value for the form
  $jsfiles = get_post_meta($post->ID, 'gil_js_file_selector_file', true);
  $jsfunctions = get_post_meta($post->ID, 'gil_js_file_selector_functions', true);
  $pos = strrpos($jsfunctions, ':');
  $pos = $pos !== false ? $pos : strlen($jsfunctions);
  $functions = substr($jsfunctions, 0, $pos);
  $position = substr($jsfunctions, $pos + 1) != 'footer' ? 'head' : 'footer';

  echo '
  <div class="row">
    <label>Select js files and choose the place of the script</label><br />'.
    gil_get_js_file(explode(',', $jsfiles)).' <button type="button" class="add-select-js button button-primary button-large">Add other file</button>
    <hr />
  </div>';
  echo '
  <div class="row js-file-selector-row">
    <label for="js-file-selector-functions">Write your Javascript functions and choose the place of the script</label><br />
    <textarea id="js-file-selector-functions" name="gil_js_file_selector_functions">'.esc_attr($functions).'</textarea>
    <label class="radio-label">Place:</label>
    <input type="radio" name="js-file-selector-functions-position" value="head" '.($position == 'head' ? 'checked' : '').' />Head
    <input type="radio" name="js-file-selector-functions-position" value="footer" '.($position == 'footer' ? 'checked' : '').' />Footer
  </div>';
}

function gil_js_file_selector_admin_scripts()
{
  wp_enqueue_style('jsfileselector.css', plugins_url('inc/css/jsfileselector.css', __FILE__));
  wp_enqueue_script('jsfileselector.js', plugins_url('inc/js/jsfileselector.js', __FILE__), array(), '1.0.0', true);
}

/* When the post is saved, saves our custom data */
function gil_js_file_selector_save_postdata($post_id)
{
  // First we need to check if the current user is authorised to do this action.
  if ('page' == $_POST['post_type']) {
    if (!current_user_can( 'edit_page', $post_id)) {
      return;
    }
  } else {
    if (!current_user_can('edit_post', $post_id)) {
      return;
    }
  }

  // Secondly we need to check if the user intended to change this value.
  if (!isset($_POST['gil_js_file_selector_chrgiga']) || !wp_verify_nonce($_POST['gil_js_file_selector_chrgiga'], plugin_basename( __FILE__ ))) {
    return;
  }

  // Thirdly we can save the value to the database
  $post_ID = $_POST['post_ID'];
  $files = array();

  foreach ($_POST['gil_js_file_selector_file'] as $index => $jsfile) {
    if (strlen($jsfile)) {
      $files[] = $jsfile.':'.$_POST['js-file-selector-position-'.$index];
    }
  }

  $jsfiles = implode(',', $files);
  $jsfunctions = strlen($_POST['gil_js_file_selector_functions']) ? $_POST['gil_js_file_selector_functions'].':'.$_POST['js-file-selector-functions-position'] : '';

  add_post_meta($post_ID, 'gil_js_file_selector_file', $jsfiles, true) or
  update_post_meta($post_ID, 'gil_js_file_selector_file', $jsfiles);
  add_post_meta($post_ID, 'gil_js_file_selector_functions', $jsfunctions, true) or
  update_post_meta($post_ID, 'gil_js_file_selector_functions', $jsfunctions);
}

function gil_js_file_selector_insert_js_file()
{
  global $post;

  if (is_single() || is_page()) {
    $jsfiles = get_post_meta($post->ID, 'gil_js_file_selector_file');

    if (count($jsfiles) && $jsfiles[0] != '') {
      foreach (explode(',', $jsfiles[0]) as $file) {
        $pos = strrpos($file, ':');
        $pos = $pos !== false ? $pos : strlen($file);
        $jsfile = substr($file, 0, $pos);
        $js_uri = str_replace(get_template_directory(), get_template_directory_uri(), $jsfile);
        $position = substr($jsfile, $pos + 1) == 'footer';
        wp_enqueue_script(str_replace('.min', '', basename($jsfile, '.js')), $js_uri, array(), false, $position);
      }
    }
  }
}

function gil_js_file_selector_insert_js_functions_head()
{
  global $post;

  if (is_single() || is_page()) {
    $jsfunctions = get_post_meta($post->ID, 'gil_js_file_selector_functions');
    if (count($jsfunctions) && $jsfunctions[0] != '') {
      $pos = strrpos($jsfunctions[0], ':');
      $pos = $pos !== false ? $pos : strlen($jsfunctions[0]);
      $functions = substr($jsfunctions[0], 0, $pos);

      if (substr($jsfunctions[0], $pos + 1) == 'head') { ?>
        <!-- js File Selector (Javascript functions) -->
        <script type="text/javascript">
        <?php echo $functions; ?>
        </script>
      <?php
      }
    }
  }
}

function gil_js_file_selector_insert_js_functions_footer()
{
  global $post;

  if (is_single() || is_page()) {
    $jsfunctions = get_post_meta($post->ID, 'gil_js_file_selector_functions');
    if (count($jsfunctions) && $jsfunctions[0] != '') {
      $pos = strrpos($jsfunctions[0], ':');
      $pos = $pos !== false ? $pos : strlen($jsfunctions[0]);
      $functions = substr($jsfunctions[0], 0, $pos);

      if (substr($jsfunctions[0], $pos + 1) == 'footer') { ?>
        <!-- js File Selector (Javascript functions) -->
        <script type="text/javascript">
        <?php echo $functions; ?>
        </script>
      <?php
      }
    }
  }
}

function gil_js_delete_post_meta()
{
  global $post;

  if ('trash' == get_post_status($post_id)) {
    delete_post_meta($post->ID, 'gil_js_file_selector_file');
    delete_post_meta($post->ID, 'gil_js_file_selector_functions');
  }
}

/* Define the custom box */
add_action('add_meta_boxes', 'gil_js_file_selector_add_custom_box');
/* backwards compatible (before WP 3.0) */
add_action('admin_init', 'gil_js_file_selector_add_custom_box', 1);
/* Save the selected js files and the custom js functions */
add_action('save_post', 'gil_js_file_selector_save_postdata');
/* Enqueue styles ans function in editor page/post */
add_action('admin_enqueue_scripts', 'gil_js_file_selector_admin_scripts');
/* Put the js files selected */
add_action('wp_enqueue_scripts', 'gil_js_file_selector_insert_js_file');
/* Add the Javascript functions to the head */
add_action('wp_head', 'gil_js_file_selector_insert_js_functions_head');
/* Add the Javascript functions to the footer */
add_action('wp_footer', 'gil_js_file_selector_insert_js_functions_footer');
/* Delete options when post is deleted */
add_action('delete_post', 'gil_js_delete_post_meta');
/* Delete all options when the plugin is uninstalling */
register_uninstall_hook(plugin_dir_path( __FILE__ ).'uninstall.php', 'uninstall');
?>