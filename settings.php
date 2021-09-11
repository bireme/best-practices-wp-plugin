<?php
function bp_page_admin() {
    global $bp_texts;

    $config = get_option('bp_config');

    if ($bp_texts['filter']){
        $available_filter_list = $bp_texts['filter'];
    }else{
        $available_filter_list = array(
                                    'type' =>  __('Type','bp'),
                                    'subregion' => __('Sub Region','bp') ,
                                    'country' =>  __('Country', 'bp'),
                                    'institution' =>  __('Institution','bp'),
                                    'stakeholder' =>  __('Stakeholder','bp'),
                                    'population_group' =>  __('Population Group','bp'),
                                    'intervention' =>  __('Intervention','bp'),
                                    'target' =>  __('Target','bp'),
        );
        $bp_texts['filter'] = $available_filter_list;
    }

    if ( $config['available_filter'] ){
        $config_filter_list = explode(';', $config['available_filter']);
    }else{
        $config_filter_list = array_keys($available_filter_list);
    }
?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"></div>
        <h2><?php _e('Best Practices record settings', 'bp'); ?></h2>
        <form method="post" action="options.php">

            <?php settings_fields('bp-settings-group'); ?>

            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row"><?php _e('Plugin page', 'bp'); ?>:</th>
                        <td><input type="text" name="bp_config[plugin_slug]" value="<?php echo ($config['plugin_slug'] != '' ? $config['plugin_slug'] : 'best-practices'); ?>" class="regular-text code"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Filter query', 'bp'); ?>:</th>
                        <td><input type="text" name="bp_config[initial_filter]" value='<?php echo $config['initial_filter'] ?>' class="regular-text code"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('AddThis profile ID', 'bp'); ?>:</th>
                        <td><input type="text" name="bp_config[addthis_profile_id]" value="<?php echo $config['addthis_profile_id'] ?>" class="regular-text code"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Google Analytics code', 'bp'); ?>:</th>
                        <td><input type="text" name="bp_config[google_analytics_code]" value="<?php echo $config['google_analytics_code'] ?>" class="regular-text code"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Fulltext', 'bp'); ?>:</th>
                        <td>
                            <label for="present_alternative_links">
                                <input type="checkbox" name="bp_config[alternative_links]" value="true" id="present_alternative_links" <?php echo (isset($config['alternative_links']) ?  " checked='true'" : '') ;?> ></input>
                                <?php _e('Present alternative fulltext links', 'bp'); ?>
                            </label>
                        </td>
                    </tr>
                    <?php
                        if ( function_exists( 'pll_the_languages' ) ) {
                            $available_languages = pll_languages_list();
                            $available_languages_name = pll_languages_list(array('fields' => 'name'));
                            $count = 0;

                            foreach ($available_languages as $lang) {
                                $key_name = 'plugin_title_' . $lang;
                                $home_url = 'home_url_' . $lang;

                                echo '<tr valign="top">';
                                echo '    <th scope="row"> ' . __("Home URL", "bp") . ' (' . $available_languages_name[$count] . '):</th>';
                                echo '    <td><input type="text" name="bp_config[' . $home_url . ']" value="' . $config[$home_url] . '" class="regular-text code"></td>';
                                echo '</tr>';

                                echo '<tr valign="top">';
                                echo '    <th scope="row"> ' . __("Page title", "bp") . ' (' . $available_languages_name[$count] . '):</th>';
                                echo '    <td><input type="text" name="bp_config[' . $key_name . ']" value="' . $config[$key_name] . '" class="regular-text code"></td>';
                                echo '</tr>';
                                $count++;
                            }
                        } else {
                            echo '<tr valign="top">';
                            echo '   <th scope="row">' . __("Page title", "bp") . ':</th>';
                            echo '   <td><input type="text" name="bp_config[plugin_title]" value="' . $config["plugin_title"] . '" class="regular-text code"></td>';
                            echo '</tr>';
                        }
                    ?>
                    <tr valign="top">
                        <th scope="row"><?php _e('Related Documents filter', 'bp'); ?>:</th>
                        <td>
                            <input type="text" name="bp_config[default_filter_db]" value='<?php echo $config['default_filter_db']; ?>' class="regular-text code">
                            <small style="display: block;">* <?php _e('The filters must be separated by commas.', 'bp'); ?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('More Related filter', 'bp'); ?>:</th>
                        <td>
                            <input type="text" name="bp_config[extra_filter_db]" value='<?php echo $config['extra_filter_db']; ?>' class="regular-text code">
                            <small style="display: block;">* <?php _e('The filters must be separated by commas.', 'bp'); ?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Search filters', 'bp');?>:</th>
                        <td>
                            <table border=0>
                                <tr>
                                    <td>
                                        <p align="left"><?php _e('Available', 'bp');?><br>
                                            <ul id="sortable1" class="connectedSortable">
                                                <?php
                                                    foreach ($available_filter_list as $filter_field => $filter_title){
                                                        if ( !in_array($filter_field, $config_filter_list) ) {
                                                            echo '<li class="ui-state-default" id="' .  $filter_field .'">' . $filter_title . '</li>';
                                                        }
                                                    }
                                                ?>
                                            </ul>
                                        </p>
                                    </td>
                                    <td>
                                        <p align="left"><?php _e('Selected', 'bp');?> <br>
                                          <ul id="sortable2" class="connectedSortable">
                                              <?php
                                                foreach ($config_filter_list as $selected_filter) {
                                                    $filter_title = $bp_texts['filter'][$selected_filter];
                                                    if ($filter_title != ''){
                                                        echo '<li class="ui-state-default" id="' . $selected_filter . '">' . $filter_title . '</li>';
                                                    }
                                                }
                                              ?>
                                          </ul>
                                          <input type="hidden" id="available_filter_aux" name="bp_config[available_filter]" value="<?php echo $config['available_filter']; ?>" >
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save changes') ?>" />
            </p>
        </form>
    </div>
    <script type="text/javascript">
        var $j = jQuery.noConflict();

        $j( function() {
            $j("#sortable1, #sortable2").sortable({
                connectWith: ".connectedSortable"
            });

            $j("#sortable2").sortable({
                update: function(event, ui) {
                    var changedList = this.id;
                    var selected_filter = $j(this).sortable('toArray');
                    var selected_filter_list = selected_filter.join(';');
                    $j('#available_filter_aux').val(selected_filter_list);
                }
            });

        } );
    </script>

    <?php
}
?>
