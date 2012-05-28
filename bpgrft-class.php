<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BPGRFT extends BP_Group_Extension{

    // some default values
    var $bpgrft_name            = '';
    var $bpgrft_slug            = 'rss-posts';
    var $bpgrft_item_position   = 99;
    var $enable_edit_item       = false;
    var $enable_create_step     = false; // @TODO: will set to true in future version to define RSS on group create

    function BPGRFT(){
        $this->bpgrft_name = __('RSS','bpgrft');
        $this->name = $this->bpgrft_name;
        $this->slug = $this->bpgrft_slug;
        $this->nav_item_position = $this->bpgrft_item_position;
        add_action('bp_after_group_settings_admin', array( &$this,'add_rss_input'));
        add_action('groups_group_settings_edited', array( &$this,'save_rss_url'));
    }

    /**
     * Modify the time RSS results are cached for
     */
    function rss_cache(){
        global $bp;
        $group_id = $bp->groups->current_group->id;
        $rss_cache = groups_get_groupmeta($group_id, 'bpgrft_rss_cache');
        if(empty($rss_cache))
            $rss_cache = '12_hrs';

        switch ($rss_cache) {
            case '1_hr':
                $time = 3600;
                break;
            case '2_hr':
                $time = 3600 * 2;
                break;
            case '6_hr':
                $time = 3600 * 6;
                break;
            case '12_hr':
                $time = 3600 * 12;
                break;
            case '1_day':
                $time = 3600 * 24;
                break;
            case '7_days':
                $time = 3600 * 24 * 7;
                break;
        }

        return $time;
    }

    /**
     * The main method that displays everything in a group on RSS tab
     */
    function display(){
        global $bp;
        include_once(ABSPATH . WPINC . '/feed.php');
        $group_id = $bp->groups->current_group->id;
        $rss_url = groups_get_groupmeta($group_id,'bpgrft_rss_url');

        if(empty($rss_url)){
            _e('Nothing to display', 'bpgrft');
            return;
        }

        $num_elem = groups_get_groupmeta($group_id,'bpgrft_rss_paged');
        if(empty($num_rss))
            $num_elem = 10;

        $links_open = groups_get_groupmeta($group_id,'bpgrft_links_open');
        if(empty($links_open))
            $links_open = 'blank';

        add_filter( 'wp_feed_cache_transient_lifetime', array( &$this,'rss_cache') );
        $rss = fetch_feed($rss_url);
        remove_filter( 'wp_feed_cache_transient_lifetime', array( &$this,'rss_cache') );

        if (is_wp_error($rss)){
            _e('Nothing to display', 'bpgrft');
            return;
        }

        $maxitems   = $rss->get_item_quantity(0);
        $pagin      = $this->rss_pagination($num_elem, $maxitems);
        $rss_items  = $rss->get_items($pagin['start_elem'], $pagin['max_elem']);
        ?>

        <div id="rss_box">
            <?php if(($maxitems - $num_elem) > 0){ ?>
                <div id="nav-below" class="navigation pagination no-ajax">
                    <div class="nav-next"><?php if($pagin['prev']){ ?><a href="<?php echo $pagin['prev']; ?>"><span class="meta-nav">&larr;</span> <?php  _e( 'Prev', 'bpgrft' ); ?></a><?php } ?></div>
                    <div class="nav-previous"><?php if($pagin['next']){ ?><a href="<?php echo $pagin['next']; ?>"><?php _e( 'Next', 'bpgrft' ); ?> <span class="meta-nav">&rarr;</span></a><?php } ?></div>
                </div><!-- #nav-below -->
            <?php } ?>
            <ul id="list_rss">
                <?php
                if ($maxitems == 0){
                    echo '<li>No items.</li>';
                }else{
                    // Loop through each feed item and display each item as a hyperlink.
                    foreach ( $rss_items as $item ) : ?>
                        <li>
                            <a <?php echo ($links_open == 'blank')?'target="_blank"':''; ?> href='<?php echo $item->get_permalink(); ?>'>
                            <h4><?php echo $item->get_title(); ?></h4>
                            </a>
                            <span><?php echo $item->get_date(get_option('date_format', 'M jS'));?></span>
                            <p><?php echo $item->get_description();?></p>
                        </li>
                    <?php endforeach;
                } ?>
            </ul>

            <?php if(($maxitems - $num_elem) > 0){ ?>
                <div id="nav-below" class="navigation pagination no-ajax">
                    <div class="nav-next"><?php if($pagin['prev']){ ?><a href="<?php echo $pagin['prev']; ?>"><span class="meta-nav">&larr;</span> <?php  _e( 'Prev', 'bpgrft' ); ?></a><?php } ?></div>
                    <div class="nav-previous"><?php if($pagin['next']){ ?><a href="<?php echo $pagin['next']; ?>"><?php _e( 'Next', 'bpgrft' ); ?> <span class="meta-nav">&rarr;</span></a><?php } ?></div>
                </div><!-- #nav-below -->
             <?php } ?>
        </div>
    <?php
    }

    function add_rss_input(){
        global $bp;
        $group_id   = $bp->groups->current_group->id;
        $rss_url    = groups_get_groupmeta($group_id, 'bpgrft_rss_url');
        $num_rss    = groups_get_groupmeta($group_id, 'bpgrft_rss_paged');
        $links_open = groups_get_groupmeta($group_id, 'bpgrft_links_open');
        $rss_cache  = groups_get_groupmeta($group_id, 'bpgrft_rss_cache');
        if(empty($links_open))
            $links_open = 'blank';
        if(empty($rss_cache))
            $rss_cache = '12_hrs';
        ?>

        <h4><?php _e('RSS Options','bpgrft'); ?></h4>

        <label>
            <span style="display:inline-block;width:100px;"><?php _e('URL','bpgrft'); ?></span>
            <input id="rss_url" type="text" name="rss_url" value="<?php if(!empty($rss_url)){echo $rss_url;} ?>" style="width:50%;margin:0 10px 10px 6px;" />
        </label>

        <label>
            <span style="display:inline-block;width:100px;"><?php _e('Posts per page','bpgrft'); ?></span>
            <input id="num_rss" type="text" name="num_rss" value="<?php echo !empty($num_rss)?$num_rss:10; ?>" style="width:20px;margin:0 10px 10px 6px;" />
        </label>

        <label>
            <span style="display:inline-block;"><?php _e('RSS timeline will be updated every:','bpgrft'); ?></span><br />
            <input id="rss_cache" type="radio" <?php checked($rss_cache, '1_hr'); ?> name="rss_cache" value="1_hr" /> <?php _e('1 hour', 'bpgrft')?><br />
            <input id="rss_cache" type="radio" <?php checked($rss_cache, '3_hrs'); ?> name="rss_cache" value="3_hrs" /> <?php _e('2 hours', 'bpgrft')?><br />
            <input id="rss_cache" type="radio" <?php checked($rss_cache, '6_hrs'); ?> name="rss_cache" value="6_hrs" /> <?php _e('6 hours', 'bpgrft')?><br />
            <input id="rss_cache" type="radio" <?php checked($rss_cache, '12_hrs'); ?> name="rss_cache" value="12_hrs" /> <?php _e('12 hours', 'bpgrft')?><br />
            <input id="rss_cache" type="radio" <?php checked($rss_cache, '1_day'); ?> name="rss_cache" value="1_day" /> <?php _e('1 day', 'bpgrft')?><br />
            <input id="rss_cache" type="radio" <?php checked($rss_cache, '7_days'); ?> name="rss_cache" value="7_days" /> <?php _e('7 days', 'bpgrft')?>
        </label>

        <label>
            <span style="display:inline-block;"><?php _e('Open links:','bpgrft'); ?></span><br />
            <input id="links_open" type="radio" <?php checked($links_open, 'blank') ?> name="links_open" value="blank" /> <?php _e('in a new window', 'bpgrft')?><br />
            <input id="links_open" type="radio" <?php checked($links_open, 'current'); ?> name="links_open" value="current" /> <?php _e('in a current window', 'bpgrft')?>
        </label>

        <hr />
    <?php
    }

    /**
     * Save all the RSS options
     */
    function save_rss_url(){
        // do not save if data is invalid
        if(!is_numeric($_POST['group-id']))
            return;

        // update all the group specific data
        groups_update_groupmeta($_POST['group-id'], 'bpgrft_rss_url',    strip_tags(trim($_POST['rss_url'])));
        groups_update_groupmeta($_POST['group-id'], 'bpgrft_rss_paged',  strip_tags(trim($_POST['num_rss'])));
        groups_update_groupmeta($_POST['group-id'], 'bpgrft_links_open', $_POST['links_open']);
        groups_update_groupmeta($_POST['group-id'], 'bpgrft_rss_cache',  $_POST['rss_cache']);
    }

    function rss_pagination($num_elem,$maxitems){
        preg_match('/.*paged\/([0-9]*)/',previous_posts( false ),$res);
        $paged = $res[1];
        if(empty($paged)){
            $pagin['next'] = bp_get_group_permalink().$this->slug.'/paged/2';
            $pagin['prev'] = false;
            if($num_elem <= $maxitems){
                $pagin['start_elem'] = 0;
                $pagin['max_elem'] = $num_elem;
            }else{
                $pagin['start_elem'] = 0;
                $pagin['max_elem'] = $maxitems;
            }
        }else{
            if(($paged-1)>0){
                if(($paged-1) == 1){
                    $pagin['prev'] = bp_get_group_permalink().$this->slug;
                }else{
                    $pagin['prev'] = bp_get_group_permalink().$this->slug.'/paged/'.($paged-1);
                }
                $pagin['start_elem'] = ($paged-1) * $num_elem;
                $check = $pagin['start_elem'] + $num_elem;
                if($check < $maxitems){
                    $pagin['max_elem'] = $num_elem;
                    $pagin['next'] = bp_get_group_permalink().$this->slug.'/paged/'.($paged+1);
                }else{
                    $pagin['max_elem'] = $maxitems - $start_elem;
                    $pagin['next'] = false;
                }
            }
        }
        return $pagin;
    }

    static function getInstance(){
        if(!self::$instance)
            self::$instance = new BPTWG;

        return self::$instance;
    }

}

bp_register_group_extension('BPGRFT');

?>
