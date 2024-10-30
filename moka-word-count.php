<?php

/*
    Plugin Name: MOKA Word Count
    Description: This plugin is used as a counter for the words and letters inside the article or post,
    and it also helps you know the time taken to read the article or post.
    Version: 1.0
    Author: Mohamed Yaghi
    Author URL: https://www.facebook.com/mohamed.yaghi.1/
    License: GPLv2 or later
`   License URL: https://www.gnu.org/licenses/gpl-2.0.html
    Text Domain:  mokadomain
    Domain Path: /lang
*/

if(!defined('ABSPATH')){
    exit;
};
if (!class_exists('MOKA_WordCountAndTimePlugin')){
class MOKA_WordCountAndTimePlugin {
    function __construct(){
        add_action('admin_menu',array($this,'MOKA_admin_page'));
        add_action('admin_init',array($this,'MOKA_settings'));
        add_filter('the_content',array($this,'MOKA_filter'));
        add_action('wp_enqueue_scripts',array($this,'MOKA_style'));
        add_action('init',array($this,'MOKA_lang'));
    }
    function MOKA_lang(){
        load_plugin_textdomain('mokadomain',false,dirname(plugin_basename(__FILE__)). '/lang');
    }
    function MOKA_style(){
        wp_enqueue_style('moka_test_style', plugin_dir_url(__FILE__).'/css/style.css');
    }
    function MOKA_filter($value)
    {
        if (is_main_query() && is_single() && (
                get_option('moka_wcp_wordcount', '1') ||
                get_option('moka_wcp_charactercount', '1') ||
                get_option('moka_wcp_readtime', '1')
            )) {
            return $this->MOKA_createHTML($value);
        }
        return $value;
    }

    function MOKA_createHTML($value){

        $data= '<div class="cont"><h3>'. esc_html(get_option('moka_wcp_headline',__('Post Statistics','mokadomain'))) . '</h3><p>';
        function MOKA_wordcount_utf8($value){
            return count(preg_split('/\W+/u', $value, -1, PREG_SPLIT_NO_EMPTY));
        }

       if(get_option('moka_wcp_wordcount','1') || get_option('moka_wcp_readtime','1')){
           $wordCount= MOKA_wordcount_utf8(strip_tags($value));

       }
        if(get_option('moka_wcp_wordcount','1')){
           $data .='<span class="words">'. __('This post has','mokadomain') .' ' .'<span class="wordcount">'. $wordCount . '</span>' . ' '. __('words','mokadomain') .'</span>'.'<br>' ;
        }
        if(get_option('moka_wcp_charactercount','1')){
            $data .='<span class="words">'. __('This post has','mokadomain') .' ' . '<span class="wordcount">'. iconv_strlen(strip_tags($value)). '</span>' . ' ' . __('characters','mokadomain') .'</span>' . '<br>' ;
        }
        if(get_option('moka_wcp_readtime','1')){
            $data .= '<span class="words">'. __('This post will take about','mokadomain') .' ' . '<span class="wordcount">' . round($wordCount/225) . '</span>' .' ' . __('minute(s) to read','mokadomain') .'</span>'. '<br>' ;
        }
        $data .= '</p></div>';

        if(get_option('moka_wcp_location','0')== '0'){
            return $data . $value;
        }
        return $value . $data;
    }

    function MOKA_settings(){
        add_settings_section('moka_wcp_first_section',null,null,'moka-word-count-setting-page');

        add_settings_field('moka_wcp_location',
            __('Display Location','mokadomain'),
            array($this,'MOKA_locationHTML'),
            'moka-word-count-setting-page',
            'moka_wcp_first_section');

        register_setting('moka_wordcountplugin',
            'moka_wcp_location',
            array(
                'sanitize_callback' => array($this,'MOKA_sanitizeLocation'),
                'default' => '0'
            )
        );

        add_settings_field('moka_wcp_headline',
            __('Headline Text','mokadomain'),
            array($this,'MOKA_headlineHTML',),
            'moka-word-count-setting-page',
            'moka_wcp_first_section',
            array(
                'type' => 'text',
            )
        );

        register_setting('moka_wordcountplugin',
            'moka_wcp_headline',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => __('Post Statistics','mokadomain')
            )
        );

        add_settings_field('moka_wcp_wordcount',
            __('Word Count','mokadomain'),
            array($this,'MOKA_checkboxHTML'),
            'moka-word-count-setting-page',
            'moka_wcp_first_section',
            array(
                 'theName'=>'moka_wcp_wordcount',
                'type' => 'checkbox'
            )
        );

        register_setting('moka_wordcountplugin',
            'moka_wcp_wordcount',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '1'
            )
        );

        add_settings_field('moka_wcp_charactercount',
            __('character Count','mokadomain'),
            array($this,'MOKA_checkboxHTML'),
            'moka-word-count-setting-page',
            'moka_wcp_first_section',
            array(
                 'theName'=>'moka_wcp_charactercount',
                'type' => 'checkbox'
            )
        );

        register_setting('moka_wordcountplugin',
            'moka_wcp_charactercount',
            array(
                 'sanitize_callback' => 'sanitize_text_field',
                'default' => '1'
            )
        );

        add_settings_field('moka_wcp_readtime',
            __('Read Time','mokadomain'),
            array($this,'MOKA_checkboxHTML'),
            'moka-word-count-setting-page',
            'moka_wcp_first_section',
            array(
                'theName'=>'moka_wcp_readtime',
                'type' => 'checkbox'
            )
        );

        register_setting('moka_wordcountplugin',
            'moka_wcp_readtime',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '1'
            )
        );
    }

    function MOKA_sanitizeLocation($input){
       if ($input != '0' && $input != '1' ){
          add_settings_error(
              'moka_wcp_location',
              'moka_wcp_location_error',
              __('Display location must be either beginning or end.','mokadomain')
          );
       return get_option('moka_wcp_location');
       }
       return $input;
    }

    function MOKA_checkboxHTML($args){
        ?>
        <input
                type="<?php esc_attr_e($args['type']); ?>"
                name="<?php esc_attr_e($args['theName']); ?>"
                value="1" <?php checked(get_option($args['theName']),'1')?>
        >
        <?php
    }

    function MOKA_headlineHTML($args){
        ?>
        <input
                type="<?php esc_attr_e($args['type']); ?>"
                name="<?php esc_attr_e('moka_wcp_headline'); ?>"
                value="<?php  esc_attr_e(get_option('moka_wcp_headline'))?>">
        <?php
    }

    function MOKA_locationHTML(){
        ?>
        <select name="moka_wcp_location">
            <option value="0" <?php selected(get_option('moka_wcp_location','0'))?> ><?php esc_html_e('Beginning of post','mokadomain')?></option>
            <option value="1" <?php selected(get_option('moka_wcp_location','1'))?> ><?php esc_html_e('End of post','mokadomain')?></option>
        </select>
        <?php

    }

    function MOKA_admin_page(){
        add_options_page(
            __('Word Count Setting','mokadomain'),
            __('Word Count','mokadomain'),
            'manage_options',
            'moka-word-count-setting-page',
            array($this,'MOKA_ourHTML')
        );
        add_menu_page(
            __('Word Count Setting','mokadomain'),
            __('Word Count','mokadomain'),
            'manage_options',
            'moka-word-count-setting-page',
            array($this,'MOKA_ourHTML'),
            'dashicons-filter'
        );
    }

    function MOKA_ourHTML(){
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Word Count Settings','mokadomain');?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('moka_wordcountplugin');
                do_settings_sections('moka-word-count-setting-page');
                submit_button(__('Save Changes','mokadomain'));
                ?>
            </form>
        </div>
        <?php
    }
}
}

$MOKA_WordCountAndTimePlugin =new MOKA_WordCountAndTimePlugin();


