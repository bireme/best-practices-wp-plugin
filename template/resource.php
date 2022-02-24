<?php
/*
Template Name: Best Practices Detail
*/

global $bp_service_url, $bp_plugin_slug, $similar_docs_url, $solr_service_url;

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
$locale = array(
    'pt' => 'pt_BR',
    'es' => 'es_ES',
    'fr' => 'fr_FR',
    'en' => 'en'
);

// likert options
$likert = array(
    "A" => __("I fully agree",'bp'),
    "B" => __("I agree",'bp'),
    "C" => __("I can't say",'bp'),
    "D" => __("I disagree",'bp'),
    "E" => __("I totally disagree",'bp')
);

// $bp_service_request = $solr_service_url . '/solr/best-practices/select/?q=id:' . $resource_id . '&wt=json';

$bp_service_request = $bp_service_url . '/api/bp/' . $resource_id . '?lang=' . $locale[$lang];

// echo "<pre>"; print_r($bp_service_request); echo "</pre>"; die();

$response = @file_get_contents($bp_service_request);

if ($response){
    $response_json = json_decode($response);
    $resource = $response_json[0]->main_submission;
    // $resource = $response_json->response->docs[0];

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

$home_url = ( $bp_config['home_url_' . $lang] ) ? $bp_config['home_url_' . $lang] : real_site_url();
$plugin_breadcrumb = isset($bp_config['plugin_title_' . $lang]) ? $bp_config['plugin_title_' . $lang] : $bp_config['plugin_title'];
if ( empty($plugin_breadcrumb) ) $plugin_breadcrumb = get_bloginfo('name');

?>

<?php get_header('best-practices');?>

<section id="sectionSearch" class="padding2">
	<div class="container">
		<div class="col-md-12">
            <form role="search" method="get" name="formHome" id="searchForm" action="<?php echo real_site_url($bp_plugin_slug); ?>">
				<div class="row g-3">
					<div class="col-9 offset-1 text-right">
                        <input type="hidden" name="lang" id="lang" value="<?php echo $lang; ?>">
                        <input type="hidden" name="sort" id="sort" value="">
                        <input type="hidden" name="format" id="format" value="summary">
                        <input type="hidden" name="count" id="count" value="10">
                        <input type="hidden" name="page" id="page" value="1">
                        <input value='' name="q" class="form-control input-search" id="fieldSearch" type="text" autocomplete="off" placeholder="<?php _e('Enter one or more words', 'bp'); ?>">
						<a id="speakBtn" href="#"><i class="fas fa-microphone-alt"></i></a>
					</div>
					<div class="col-1 float-end">
						<button type="submit" id="submitHome" class="btn btn-warning">
							<i class="fas fa-search"></i>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</section>

<section class="padding1">
	<div class="container viewBt">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $home_url ?>"><?php _e('Home','bp'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo real_site_url($bp_plugin_slug); ?>"><?php echo $plugin_breadcrumb; ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo ( strlen($resource->title) > 90 ) ? substr($resource->title,0,90) . '...' : $resource->title; ?></li>
          </ol>
        </nav>

        <?php if ( $resource ) : ?>
            <h1><?php echo $resource->title; ?></h1>
        <?php endif; ?>

        <div class="row">
            <?php if ( !$resource ) : ?>
                <div class="col-md-12 text-center">
                    <div class="alert alert-secondary" role="alert">
                        <?php echo mb_strtoupper(__('Document not found','bp')); ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="col-md-9">
    				<div class="bpBtAction">
                        <!-- AddThis Button BEGIN -->
                        <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                            <a class="addthis_button_facebook"></a>
                            <a class="addthis_button_whatsapp"></a>
                            <a class="addthis_button_twitter"></a>
                            <a class="addthis_button_linkedin"></a>
                            <a class="addthis_button_email"></a>
                            <a class="addthis_button_print"></a>
                            <a class="addthis_button_compact"></a>
                        </div>
                        <script type="text/javascript">var addthis_config = {"data_track_addressbar":false};</script>
                        <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=<?php echo $bp_addthis_id; ?>"></script>
                        <!-- AddThis Button END -->
    				</div>
    				<div class="bp-data">
    					<h3><i class="fas fa-caret-right"></i><b><?php echo __('Basic Information', 'bp'); ?></b></h3><br />
                        <?php if ( $resource->introduction ): ?>
        					<h5><i class="fas fa-chevron-right"></i><b><?php echo __('Brief Introduction', 'bp') . ':'; ?></b></h5>
        					<p><?php echo $resource->introduction; ?></p>
        					<hr />
                        <?php endif; ?>

                        <?php if ( $resource->objectives ): ?>
        					<h5><i class="fas fa-chevron-right"></i><b><?php echo __('Main Objectives', 'bp') . ':'; ?></b></h5>
        					<p><?php echo $resource->objectives; ?></p>
        					<hr />
                        <?php endif; ?>

                        <?php if ( $resource->activities ): ?>
        					<h5><i class="fas fa-chevron-right"></i><b><?php echo __('Implementation', 'bp') . '/' . __('Activities', 'bp') . ':'; ?></b></h5>
        					<p><?php echo $resource->activities; ?></p>
        					<hr />
                        <?php endif; ?>

                        <?php if ( $resource->main_results ): ?>
        					<h5><i class="fas fa-chevron-right"></i><b><?php echo __('Main Results', 'bp') . ':'; ?></b></h5>
        					<p><?php echo $resource->main_results; ?></p>
        					<hr />
                        <?php endif; ?>

                        <?php if ( $resource->factors ): ?>
        					<h5><i class="fas fa-chevron-right"></i><b><?php echo __('Limitations and Hindrances', 'bp') . ':'; ?></b></h5>
        					<p><?php echo $resource->factors; ?></p>
        					<hr />
                        <?php endif; ?>

                        <?php if ( $resource->technical_matter ): ?>
        					<h5><i class="fas fa-chevron-right"></i><b><?php echo __('Main Topics', 'bp') . '/' . __('Themes', 'bp') . ':'; ?></b></h5>
                            <?php $technical_matters = wp_list_pluck( $resource->technical_matter, 'name' ); ?>
        					<p><?php echo implode('; ', $technical_matters); ?></p>
        					<hr />
                        <?php endif; ?>

                        <?php if ( $resource->outcome_information ): ?>
                            <h5><i class="fas fa-chevron-right"></i><b><?php echo __('Effectiveness & Efficiency', 'bp') . ':'; ?></b></h5>
                            <p><?php echo $resource->outcome_information; ?></p>
                            <hr />
                        <?php endif; ?>

                        <?php if ( $resource->describe_how ): ?>
                            <h5><i class="fas fa-chevron-right"></i><b><?php echo __('Adaptability & Replicability', 'bp') . ':'; ?></b></h5>
                            <p><?php echo $resource->describe_how; ?></p>
                            <hr />
                        <?php endif; ?>

                        <?php if ( 'paho-who-technical-cooperation' == $resource->type->slug ): ?>
                            <h3><i class="fas fa-caret-right"></i><b><?php echo __('Technical Cooperation', 'bp'); ?></b></h3><br />
                            <?php if ( $resource->public_health_issue ): ?>
                                <h5><i class="fas fa-chevron-right"></i><b><?php echo __('What public health issue (or opportunity) led PAHO to participate on this Technical Cooperation project/initiative', 'bp') . '?'; ?></b></h5>
                                <p><?php echo $resource->public_health_issue; ?></p>
                                <hr />
                            <?php endif; ?>

                            <?php if ( $resource->planning_information ): ?>
                                <h5><i class="fas fa-chevron-right"></i><b><?php echo __('Was the TC planned considering the health situation of the target population', 'bp') . '?'; ?></b></h5>
                                <p><?php echo $resource->planning_information; ?></p>
                                <hr />
                            <?php endif; ?>

                            <?php if ( $resource->recognition_information ): ?>
                                <h5><i class="fas fa-chevron-right"></i><b><?php echo __("Recognition of PAHO's Technical Cooperation Importance by the Counterpart", 'bp') . ':'; ?></b></h5>
                                <p><?php echo $resource->recognition_information; ?></p>
                                <hr />
                            <?php endif; ?>

                            <?php if ( $resource->engagement_information ): ?>
                                <h5><i class="fas fa-chevron-right"></i><b><?php echo __("Engagement with the Priorities Organization's Cross-Cutting Themes", 'bp') . ':'; ?></b></h5>
                                <p><?php echo $resource->engagement_information; ?></p>
                                <hr />
                            <?php endif; ?>
                        <?php endif; ?>

                        <h3><i class="fas fa-caret-right"></i><b><?php echo __('Conclusion', 'bp'); ?></b></h3><br />
                        <?php if ( $resource->challenges_information ): ?>
                            <h5><i class="fas fa-chevron-right"></i><b><?php echo __('What were the obstacles or challenges faced during the implementation of this best practice/initiative', 'bp') . '?'; ?></b></h5>
                            <p><?php echo $resource->challenges_information; ?></p>
                            <hr />
                        <?php endif; ?>

                        <?php if ( $resource->lessons_information ): ?>
                            <h5><i class="fas fa-chevron-right"></i><b><?php echo __('What were the lessons learned for that will improve our expertise and add value to the Organization', 'bp') . '?'; ?></b></h5>
                            <p><?php echo $resource->lessons_information; ?></p>
                            <hr />
                        <?php endif; ?>

                        <h3><i class="fas fa-caret-right"></i><b><?php echo __('Multimedia', 'bp'); ?></b></h3><br />
                        <?php if ( $resource->attachments ) : ?>
                            <?php $bp_images = get_bp_images($response_json[0]); ?>
                            <?php if ( $bp_images ) : ?>
                                <div class="bpImg clearfix">
                                    <?php foreach ($bp_images as $img): ?>
                                        <div class="bp-thumb">
                                            <a href="<?php echo $img; ?>" target="_blank">
                                                <img src="<?php echo $img; ?>" alt="" class="img-fluid" />
                                                <?php $img_name = explode('_', basename($img)); ?>
                                                <?php // echo $img_name[1]; ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <hr />

                        <h3><i class="fas fa-caret-right"></i><b><?php echo __('Sources', 'bp'); ?></b></h3><br />
                        <?php if ( $resource->products_information ) : $products_information = explode("\r\n", $resource->products_information); ?>
                            <?php foreach ($products_information as $link): ?>
                                <a href="<?php echo $link; ?>" target="_blank">
                                    <i class="fa fa-external-link-square-alt" aria-hidden="true"> </i>
                                    <?php echo $link; ?>
                                    <br />
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ( $resource->other_sources_information ) : $other_sources_information = explode("\r\n", $resource->other_sources_information); ?>
                            <?php foreach ($other_sources_information as $link): ?>
                                <a href="<?php echo $link; ?>" target="_blank">
                                    <i class="fa fa-external-link-square-alt" aria-hidden="true"> </i>
                                    <?php echo $link; ?>
                                    <br />
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <hr />
    				</div>
    			</div>

                <div class="col-md-3 bp-filters">
    				<div class="box1 title1">
    					<h4><?php echo mb_strtoupper(__('Dates', 'bp')); ?></h4>
                        <?php if ( $resource->start_date ): ?>
                            <i class="fas fa-calendar-alt"></i> <?php echo __('Start', 'bp') . ': ' . date('Y-m-d', strtotime($resource->start_date)); ?><br />
                        <?php endif; ?>
                        <?php if ( $resource->end_date ): ?>
                            <i class="fas fa-calendar-alt"></i> <?php echo __('End', 'bp') . ': ' . date('Y-m-d', strtotime($resource->end_date)); ?><br />
                        <?php endif; ?>
    				</div>
                    <?php if ( $resource->type ): ?>
                        <div class="box1 title1">
                            <h4><?php echo mb_strtoupper(__('Type', 'bp')); ?></h4>
        					<?php echo $resource->type->name; ?>
        				</div>
                    <?php endif; ?>
                    <?php if ( $resource->subregion ): ?>
                        <div class="box1 title1">
                            <h4><?php echo mb_strtoupper(__('Sub Region', 'bp')); ?></h4>
        					<?php echo $resource->subregion->name; ?>
        				</div>
                    <?php endif; ?>
                    <?php if ( $resource->country ): ?>
                        <div class="box1 title1">
                            <h4><?php echo mb_strtoupper(__('Country', 'bp')); ?></h4>
        					<img src="https://www.countryflags.io/<?php echo $resource->country->code; ?>/shiny/32.png" alt="" style="width: 30px;">
                            <?php echo $resource->country->name; ?>
        				</div>
                    <?php endif; ?>
                    <?php if ( $resource->other_institution ): ?>
                        <div class="box1 title1">
                            <h4><?php echo mb_strtoupper(__('Institution', 'bp')); ?></h4>
        					<?php echo $resource->other_institution; ?>
        				</div>
                    <?php elseif ( $resource->institution ): ?>
                        <div class="box1 title1">
                            <h4><?php echo mb_strtoupper(__('Institution', 'bp')); ?></h4>
        					<?php echo $resource->institution->name; ?>
        				</div>
                    <?php endif; ?>
                    <?php if ( $resource->other_stakeholder ): ?>
                        <div class="box1 title1">
                            <h4><?php echo mb_strtoupper(__('Stakeholder', 'bp')); ?></h4>
        					<?php echo $resource->other_stakeholder; ?>
        				</div>
                    <?php elseif ( $resource->stakeholder ): ?>
                        <div class="box1 title1">
                            <h4><?php echo mb_strtoupper(__('Stakeholder', 'bp')); ?></h4>
        					<?php echo $resource->stakeholder->name; ?>
        				</div>
                    <?php endif; ?>
                    <?php if ( $resource->population_group ): ?>
                        <div class="box1 title1 text-center">
                            <h4><?php echo mb_strtoupper(__('Population Group', 'bp')); ?></h4>
                            <?php foreach ($resource->population_group as $population_group) : ?>
                                <a href="javascript:void(0)" class="aSpan" data-toggle="tooltip" data-placement="top"><?php echo $population_group->name; ?></a>
                            <?php endforeach; ?>
        				</div>
                    <?php endif; ?>
                    <?php if ( $resource->intervention ): ?>
                        <div class="box1 title1 text-center">
                            <h4><?php echo mb_strtoupper(__('Intervention', 'bp')); ?></h4>
                            <?php foreach ($resource->intervention as $intervention) : ?>
                                <a href="javascript:void(0)" class="aSpan" data-toggle="tooltip" data-placement="top"><?php echo $intervention->name; ?></a>
                            <?php endforeach; ?>
        				</div>
                    <?php endif; ?>
                    <?php if ( $resource->target ): ?>
                        <div class="box1 title1 text-center">
                            <h4><?php echo mb_strtoupper(__('SDG', 'bp')); ?></h4>
                            <?php foreach ($resource->target as $target) : ?>
                                <a href="javascript:void(0)" class="aSpan" data-toggle="tooltip" data-placement="top" title="<?php echo $target->subtext; ?>"><?php echo $target->name; ?></a>
                            <?php endforeach; ?>
        				</div>
                    <?php endif; ?>
    			</div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php get_footer(); ?>
