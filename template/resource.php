<?php
/*
Template Name: Best Practices Detail
*/

global $bp_service_url, $bp_plugin_slug, $similar_docs_url;

$bp_config         = get_option('bp_config');
$bp_initial_filter = $bp_config['initial_filter'];
$bp_addthis_id     = $bp_config['addthis_profile_id'];
$bp_about          = $bp_config['about'];
$bp_tutorials      = $bp_config['tutorials'];
$alternative_links = (bool)$bp_config['alternative_links'];

$referer = wp_get_referer();
$path = parse_url($referer);
if ( array_key_exists( 'query', $path ) ) {
    $path = parse_str($path['query'], $output);
    // echo "<pre>"; print_r($output); echo "</pre>";
    if ( array_key_exists( 'q', $output ) && !empty( $output['q'] ) ) {
        $query = $output['q'];
        $q = ( strlen($output['q']) > 10 ? substr($output['q'],0,10) . '...' : $output['q'] );
        $ref = ' / <a href="'. $referer . '">' . $q . '</a>';
    }
}

$filter = '';
$user_filter = stripslashes($output['filter']);
if ($bp_initial_filter != ''){
    if ($user_filter != ''){
        $filter = $bp_initial_filter . ' AND ' . $user_filter;
    }else{
        $filter = $bp_initial_filter;
    }
}else{
    $filter = $user_filter;
}

$request_uri   = $_SERVER["REQUEST_URI"];
$request_parts = explode('/', $request_uri);
$resource_id   = $_GET['id'];

$site_language = strtolower(get_bloginfo('language'));
$lang = substr($site_language,0,2);

// likert options
$likert = array(
    "A" => __("I fully agree",'bp'),
    "B" => __("I agree",'bp'),
    "C" => __("I can't say",'bp'),
    "D" => __("I disagree",'bp'),
    "E" => __("I totally disagree",'bp')
);

// $bp_service_request = $bp_service_url . 'api/bibliographic/search/?id=' . $resource_id . '&op=related&lang=' . $lang;

$bp_service_request = $bp_service_url . '/api/bp/' . $resource_id;

// echo "<pre>"; print_r($bp_service_request); echo "</pre>"; die();

$response = @file_get_contents($bp_service_request);

if ($response){
    $response_json = json_decode($response);
    $resource = $response_json[0]->main_submission;

    // echo "<pre>"; print_r($response_json); echo "</pre>"; die();

    // create param to find similars
    $similar_text = $resource->title;
    if (isset($resource->mj)){
        $similar_text .= ' ' . implode(' ', $resource->mj);
    }

    $similar_docs_url = $similar_docs_url . '?adhocSimilarDocs=' . urlencode($similar_text);
    $similar_docs_request = ( $bp_config['default_filter_db'] ) ? $similar_docs_url . '&sources=' . $bp_config['default_filter_db'] : $similar_docs_url;
    $similar_query = urlencode($similar_docs_request);
    $related_query = urlencode($similar_docs_url);

    // create param to find publication language
    if (isset($resource->publication_language[0])){
        $publication_language = explode('|', $resource->publication_language[0]);
        $publication_language = get_publication_language($publication_language, $lang);
    }
}

$feed_url = real_site_url($bp_plugin_slug) . 'best-practices-feed?q=' . urlencode($query) . '&filter=' . urlencode($filter);

$home_url = isset($bp_config['home_url_' . $lang]) ? $bp_config['home_url_' . $lang] : real_site_url();
$plugin_breadcrumb = isset($bp_config['plugin_title_' . $lang]) ? $bp_config['plugin_title_' . $lang] : $bp_config['plugin_title'];
?>

<?php get_header('best-practices');?>

    <div id="content" class="row-fluid">
        <div class="ajusta2">
            <div class="row-fluid breadcrumb">
                <a href="<?php echo $home_url ?>"><?php _e('Home','bp'); ?></a> >
                <a href="<?php echo real_site_url($bp_plugin_slug); ?>"><?php echo $plugin_breadcrumb; ?> </a> >
                <?php echo ( strlen($resource->title) > 90 ) ? substr($resource->title,0,90) . '...' : $resource->title; ?>
            </div>

            <section class="header-search">
                <form role="search" method="get" name="searchForm" id="searchForm" action="<?php echo real_site_url($bp_plugin_slug); ?>">
                    <input type="hidden" name="lang" id="lang" value="<?php echo $lang; ?>">
                    <input type="hidden" name="sort" id="sort" value="">
                    <input type="hidden" name="format" id="format" value="summary">
                    <input type="hidden" name="count" id="count" value="10">
                    <input type="hidden" name="page" id="page" value="1">
                    <input value="" name="q" class="input-search" id="s" type="text" placeholder="<?php _e('Enter one or more words', 'bp'); ?>">
                    <input id="searchsubmit" value="<?php _e('Search', 'bp'); ?>" type="submit">
                    <a href="#" title="<?php _e('Tip! You can do your search using boolean operators.', 'bp'); ?>" class="help ketchup tooltip"><i class="fa fa-question-circle fa-2x"></i></a>
                </form>
            </section>
            <div class="content-area detail">
                <section id="conteudo">
                    <?php if ( $resource ) : ?>
                    <div class="row-fluid">
                        <!-- AddThis Button BEGIN -->
                        <div class="addthis_toolbox addthis_default_style">
                            <a class="addthis_button_facebook"></a>
                            <a class="addthis_button_delicious"></a>
                            <a class="addthis_button_google_plusone_share"></a>
                            <a class="addthis_button_favorites"></a>
                            <a class="addthis_button_compact"></a>
                        </div>
                        <script type="text/javascript">var addthis_config = {"data_track_addressbar":false};</script>
                        <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=<?php echo $bp_addthis_id; ?>"></script>
                        <!-- AddThis Button END -->
                    </div>
                    <div class="row-fluid">
                        <article class="conteudo-loop">
                            <h2 class="h2-loop-tit">
                                <a href="#"><?php echo $resource->title; ?></a>
                                <div class="altLang"><?php echo $resource->$title; ?></div>
                            </h2>

                            <?php if ( $resource->type ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Type', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->type->name; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->introduction ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Introduction', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->introduction; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->objectives ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Objectives', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->objectives; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->activities ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Activities', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->activities; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->main_results ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Main Results', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->main_results; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->factors ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Factors', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->factors; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->other_role ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Role', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->other_role; ?>
                                </div>
                            <?php elseif ( $resource->role ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Role', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->role->name; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->other_institution ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Institution', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->other_institution; ?>
                                </div>
                            <?php elseif ( $resource->institution ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Institution', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->institution->name; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->other_stakeholder ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Stakeholder', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->other_stakeholder; ?>
                                </div>
                            <?php elseif ( $resource->stakeholder ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Stakeholder', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->stakeholder->name; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( 'paho-who-technical-cooperation' == $resource->type->slug ): ?>
                                <?php if ( $resource->entity ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Entity', 'bp') . ': '; ?></h2>
                                        <?php echo $resource->entity->name; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->reference_number ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Reference Number', 'bp') . ': '; ?></h2>
                                        <?php echo $resource->reference_number; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ( $resource->technical_matter ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Technical Matters', 'bp') . ': '; ?></h2>
                                    <?php $technical_matters = wp_list_pluck( $resource->technical_matter, 'name' ); ?>
                                    <?php echo implode('; ', $technical_matters); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->intervention ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Interventions', 'bp') . ': '; ?></h2>
                                    <?php $interventions = wp_list_pluck( $resource->intervention, 'name' ); ?>
                                    <?php echo implode('; ', $interventions); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->start_date ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Start Date', 'bp') . ': '; ?></h2>
                                    <?php echo date('Y-m-d', strtotime($resource->start_date)); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->end_date ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('End Date', 'bp') . ': '; ?></h2>
                                    <?php echo date('Y-m-d', strtotime($resource->end_date)); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->country ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Country', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->country->name; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->subregion ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Sub Region', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->subregion->name; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->target ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Targets', 'bp') . ': '; ?></h2>
                                    <?php $targets = wp_list_pluck( $resource->target, 'name' ); ?>
                                    <?php echo implode('; ', $targets); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->other_population_group ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Population Group', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->other_population_group; ?>
                                </div>
                            <?php elseif ( $resource->population_group ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Population Group', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->population_group->name; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->resources_assigned ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Resources Assigned', 'bp') . ': '; ?></h2>
                                    <?php echo $likert[$resource->resources_assigned]; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->outcome_information ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Outcome Information', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->outcome_information; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->scalability ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Scalability', 'bp') . ': '; ?></h2>
                                    <?php echo $likert[$resource->scalability]; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->adaptability_replicability ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Adaptability/Replicability', 'bp') . ': '; ?></h2>
                                    <?php echo $likert[$resource->adaptability_replicability]; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->other_contexts_demo ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Other Contexts', 'bp') . ': '; ?></h2>
                                    <?php echo $likert[$resource->other_contexts_demo]; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->describe_how ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Describe How', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->describe_how; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( 'paho-who-technical-cooperation' == $resource->type->slug ): ?>
                                <?php if ( $resource->health_system_contribution ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Health System Contribution', 'bp') . ': '; ?></h2>
                                        <?php echo $likert[$resource->health_system_contribution]; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->value_chain_organization ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __("Organization's Value Chain", 'bp') . ': '; ?></h2>
                                        <?php echo $likert[$resource->value_chain_organization]; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->public_health_issue ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Public Health Issue', 'bp') . ': '; ?></h2>
                                        <?php echo $resource->public_health_issue; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->planning_information ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Planning Information', 'bp') . ': '; ?></h2>
                                        <?php echo $resource->planning_information; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->relevance_information ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Relevance Information', 'bp') . ': '; ?></h2>
                                        <?php echo $resource->relevance_information; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->counterpart_recognized ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Counterpart Recognized', 'bp') . ': '; ?></h2>
                                        <?php echo $likert[$resource->counterpart_recognized]; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->catalytic_role ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Catalytic Role', 'bp') . ': '; ?></h2>
                                        <?php echo $likert[$resource->catalytic_role]; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->neutral_role ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Neutral Role', 'bp') . ': '; ?></h2>
                                        <?php echo $likert[$resource->neutral_role]; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->recognition_information ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Recognition Information', 'bp') . ': '; ?></h2>
                                        <?php echo $resource->recognition_information; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->cross_cutting_approach ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Cross Cutting Approach', 'bp') . ': '; ?></h2>
                                        <?php echo $likert[$resource->cross_cutting_approach]; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $resource->engagement_information ): ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Engagement Information', 'bp') . ': '; ?></h2>
                                        <?php echo $resource->engagement_information; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ( $resource->products_information ) : $products_information = explode("\r\n", $resource->products_information); ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Products Information', 'bp') . ': '; ?></h2>
                                    <?php foreach ($products_information as $link): ?>
                                        <a href="<?php echo $link; ?>" target="_blank">
                                            <i class="fa fa-external-link-square-alt" aria-hidden="true"> </i>
                                            <?php echo $link; ?>
                                            <br />
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->other_sources_information ) : $other_sources_information = explode("\r\n", $resource->other_sources_information); ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Other Sources Information', 'bp') . ': '; ?></h2>
                                    <?php foreach ($other_sources_information as $link): ?>
                                        <a href="<?php echo $link; ?>" target="_blank">
                                            <i class="fa fa-external-link-square-alt" aria-hidden="true"> </i>
                                            <?php echo $link; ?>
                                            <br />
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->challenges_information ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Challenges Information', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->challenges_information; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->lessons_information ): ?>
                                <div class="row-fluid">
                                    <h2 class="field-label"><?php echo __('Lessons Information', 'bp') . ': '; ?></h2>
                                    <?php echo $resource->lessons_information; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $resource->attachments ) : ?>
                                <?php $bp_images = get_bp_images($response_json[0]); ?>
                                <?php if ( $bp_images ) : ?>
                                    <div class="row-fluid">
                                        <h2 class="field-label"><?php echo __('Pictures', 'bp') . ': '; ?></h2>
                                        <?php foreach ($bp_images as $img): ?>
                                            <a href="<?php echo $img; ?>" target="_blank">
                                                <i class="fa fa-external-link-square-alt" aria-hidden="true"> </i>
                                                <?php $img_name = explode('_', basename($img)); ?>
                                                <?php echo $img_name[1]; ?>
                                                <br />
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </article>
                    </div>
                    <?php else : ?>
                    <div class="row-fluid">
                        <article class="conteudo-loop" style="text-align: center;">
                            <?php echo strtoupper(__('Document not found','bp')); ?>
                        </article>
                    </div>
                    <?php endif; ?>
                    <div class="row-fluid">
                        <header class="row-fluid border-bottom marginbottom15">
                            <h1 class="h1-header"><?php _e('More related','bp'); ?></h1>
                        </header>
                        <div id="loader" class="loader" style="display: inline-block;"></div>
                    </div>
                    <div class="row-fluid">
                        <div id="async" class="related-docs">

                        </div>
                    </div>
<?php
$sources = ( $bp_config['extra_filter_db'] ) ? $bp_config['extra_filter_db'] : '';
$url = BP_PLUGIN_URL.'template/related.php?query='.$related_query.'&sources='.$sources.'&lang='.$lang;
?>
<script type="text/javascript">
    show_related("<?php echo $url; ?>");
</script>
                </section>
                <aside id="sidebar">
                    <section class="row-fluid marginbottom25 widget_categories">
                        <header class="row-fluid border-bottom marginbottom15">
                            <h1 class="h1-header"><?php _e('Related','bp'); ?></h1>
                        </header>
                        <ul id="ajax">

                        </ul>
                    </section>
<?php
$url = BP_PLUGIN_URL.'template/similar.php?query='.$similar_query.'&lang='.$lang;
?>
<script type="text/javascript">
    show_similar("<?php echo $url; ?>");
</script>
                </aside>
                <div class="spacer"></div>
            </div> <!-- close DIV.detail-area -->
        </div> <!-- close DIV.detail-area -->
    </div>
<?php get_footer(); ?>
