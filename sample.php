<?php
/*
Plugin Name: Sample Plugin
Plugin URI: http://mif.vspu.ru
Description: Пример простого плагина для WordPress. Можно использовать как основу для новых плагинов.
Author: Алексей Н. Сергеев
Version: 1.1
Author URI: https://vk.com/alexey_sergeev
*/



// Функция запускается после загрузки WordPress и перед выводом чего-либо на экран
// Здесь можно определять свои типы записей, назначить шорткоды и др. 
// Подробнее (все хуки и фильтры): http://wp-kama.ru/hooks

add_action( 'init', 'sample_init' );

function sample_init() 
{

    // В примере - создается новый тип записей "Анкета" (questionnaire)
    // Подробнее - http://wp-kama.ru/function/register_post_type

    register_post_type( 'questionnaire', array(
        'labels' => array(
        'name'            => __( 'Questionnaire' ),
        'singular_name'   => __( 'Questionnaire' ),
        'add_new'         => __( 'Add questionnaire' ),
        'add_new_item'    => __( 'Add questionnaire item' ),
        'edit'            => __( 'Edit questionnaire' ),
        'edit_item'       => __( 'Edit questionnaire item' ),
        'new_item'        => __( 'Single questionnaire' ),
        'all_items'       => __( 'All questionnaires' ),
        'view'            => __( 'View questionnaires' ),
        'view_item'       => __( 'View single questionnaire' ),
        'search_items'    => __( 'Search questionnaires' ),
        'not_found'       => __( 'Questionnaires not found' ),
    ),
    'public' => true, 
    'menu_position' => 20,
    'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
    'taxonomies' => array( '' ),
    'has_archive' => true,
    'capability_type' => 'post',
    'menu_icon'   => 'dashicons-smiley',
    'rewrite' => array('slug' => 'questionnaires'),
    ));    
    // !!! Изменения вступают в силу после нажания кнопки "Сохранить" на странице /wp-admin/options-permalink.php  

}


// Подключаем свой файл CSS
// Подробнее: http://wp-kama.ru/function/wp_register_style

add_action( 'wp_enqueue_scripts', 'add_sample_plugin_styles' );

function add_sample_plugin_styles() {
	wp_register_style( 'sample-plugin-styles', plugins_url( 'styles.css', __FILE__ ) );
	wp_enqueue_style( 'sample-plugin-styles' );
}


// Функция получает текст записи, который можно изменить перед выводом на экран

add_filter ('the_content', 'add_custom_content');

function add_custom_content( $content ) 
{
    global $post;

    if ( $post->post_type == 'questionnaire' ) {
        // Делаем изменения только для зарегистрированного нами типа записей


        // Проверяем, не пытается ли пользователь сохранить новые данные. Если да, то сохраняем их.
        if ( isset($_POST['name']) && is_user_logged_in() && wp_verify_nonce( $_POST['save_questionnaire_nonce'], 'save_questionnaire' ) ) 
            add_post_meta( $post->ID, 'questionnaire_data', array(  'name' => $_POST['name'], 
                                                                    'city' => $_POST['city'], 
                                                                    'question' => $_POST['question'] ) );

        // Читаем все сохранённые данные анкет
        $questionnaire_data_arr = get_post_meta( $post->ID, 'questionnaire_data' );

        // Выводим анкетную форму, но только для зарегистрированных пользователей
        if ( is_user_logged_in() ) {

            $content .= '<form method="POST">
            <p>Ваше имя?<br /> <input type="text" name="name">
            <p>В каком городе вы живете?<br /> <input type="text" name="city">
            <p>Какой вопрос вы хотите задать?<br /> <textarea name="question"></textarea>
            <p><input type="submit" value="Сохранить">';
            $content .= wp_nonce_field( 'save_questionnaire','save_questionnaire_nonce' );
            $content .= '</form>';

        } else {

            $content .= '<p class="warning">Пройдите регистрацию, если хотите задать вопрос';

        }

        // Выводим данные, которые были сохранены ранее
        foreach ( (array)$questionnaire_data_arr as $item ) 
            $content .= '<p class="custom"><em>' . $item['name'] . ' (' . $item['city'] . ')</em><br />' . $item['question']; 
        
    }
    

    return $content;
}



?>
