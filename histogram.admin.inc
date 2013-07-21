<?php

/**
 * Just some help page. Gives you an overview over the available tags
 * @return string html
 */
function histogram_admin_settings() {
  $filepath = drupal_get_path('module', 'histogram') . '/sample.jpg';
  $url = url($filepath);

  $output = '';
  $output .= '<h3>' . t('About') . '</h3>';
  $output .= '<p>';
  $output .= t('The Histogram module allows you to generate a histogram for images in nodes.');
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('The module creates a new field type and widget called "histogram". Once attached to a node, it will automatically create a histogram for the image field on the node when the node is saved. Currently, it only supports one image field per node per histogram field.');
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('It can create stacked three color histograms as well as merged histograms. The background color can be set to any hexadecimal value. These options can be selected on an image by image basis.');
  $output .= '</p>';
  $output .= '<h3>' . t('Installation and Usage') . '</h3>';
  $output .= '<p>';
  $output .= t('To begin, go to the <a href="@url">Histogram Settings</a> tab.', array('@url' => url('admin/config/media/histogram/settings')));
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('Select the checkboxes for the nodetypes you wish to create histograms with.');
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('Next, on the manage fields page for the selected nodetype, add a new field with the field type "Histogram."');
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('If your node has multiple image fields, select which one you want to generate a histogram with on the settings page.');
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('The "Histogram Type" option determines whether your histogram will be three seperate channels stacked on top of each other or a single merged histogram. On black and white images, a single channel histogram will be generated regardless of the value of this option.');
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('"Force Single Channel Histogram" allows you to generate a single channel histogram by forcing the histogram generator to process the image as if it were black and white. This is useful on monochrome images which are tinted because they fail to automatically be seen as black and white by the processor.');
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('The background color field allows you to specifiy a hexadecimal background color for your histograms.');
  $output .= '</p>';
  $output .= '<p>';
  $output .= t('Samples of the different histogram options and their results can be seen below.');
  $output .= '</p>';
  $output .= '<div class="sample-image">';
  $output .= '<h3 class="sample-image">';
  $output .= t('Example of sample image and histogram');
  $output .= '</h3>';
  $output .= '<img class="sample-image" src="' . $url . '"/>';
  $output .= '</div>';
  drupal_add_css(drupal_get_path('module', 'histogram') . '/histogram.admin.css');
  return $output;
}


/**
 * The form definition for the admin settings
 * @return array
 *  form definition
 */
function histogram_admin_settings_form() {
  $forms = array();

  $all_nodetypes = node_type_get_types();
  $all_nt = array();
  foreach ($all_nodetypes as $item) {
    $all_nt[$item->type] = $item->name;
  }
  $forms['histogram_nodetypes'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Nodetypes'),
    '#options' => $all_nt,
    '#default_value' => variable_get('histogram_nodetypes', array()),
    '#description' => t('Select nodetypes which should be enabled for histogram generation.'),
  );

  return system_settings_form($forms);
}

?>
