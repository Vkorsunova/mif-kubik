<?php
/*
Plugin Name: MIF Kubik
Plugin URI: http://mif.vspu.ru
Description: Плагин автоматического создания текстовых документов из Excel-шаблонов
Author: Вероника Корсунова
Version: 1.0
Author URI: https://vk.com/veronica.korsunova
*/



// Функция запускается после загрузки WordPress и перед выводом чего-либо на экран
// Здесь можно определять свои типы записей, назначить шорткоды и др. 
// Подробнее (все хуки и фильтры): http://wp-kama.ru/hooks

add_action( 'init', 'sample_init' );

function sample_init() 
{

    // В примере - создается новый тип записей "Документ" (doc)
    // Подробнее - http://wp-kama.ru/function/register_post_type

    register_post_type( 'doc', array(
        'labels' => array(
        'name'            => __( 'Docs' ), //Имя 
        'singular_name'   => __( 'Doc' ),  //Единственное имя
        'add_new'         => __( 'Add docs' ), //Добавить новое
        'add_new_item'    => __( 'Add doc item' ), //Добавить новый элемент
        'edit'            => __( 'Edit doc' ), //Редактировать
        'edit_item'       => __( 'Edit doc item' ), //Редактировать элемент
        'new_item'        => __( 'Single doc' ), //Новый элемент
        'all_items'       => __( 'All docs' ), //Все элементы
        'view'            => __( 'View docs' ), //Посмотреть
        'view_item'       => __( 'View single doc' ), //Посмотреть элемент
        'search_items'    => __( 'Search docs' ), //Поиск элементов
        'not_found'       => __( 'Docs not found' ), //Не найдено
    ),
    'public' => true, 
    'menu_position' => 20,
    'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
    'taxonomies' => array( '' ),
    'has_archive' => true,
    'capability_type' => 'post',
    'menu_icon'   => 'dashicons-book',
    'rewrite' => array( 'slug' => 'docs' ),
    ));    
    // !!! Изменения вступают в силу после нажания кнопки "Сохранить" на странице /wp-admin/options-permalink.php  

}


// Подключаем свой файл CSS
// Подробнее: http://wp-kama.ru/function/wp_register_style

add_action( 'wp_enqueue_scripts', 'add_custom_styles' );

function add_custom_styles() {
	wp_register_style( 'custom-kubik-styles', plugins_url( 'styles.css', __FILE__ ) );
	wp_enqueue_style( 'custom-kubik-styles' );
}


// Функция получает текст записи, который можно изменить перед выводом на экран

add_filter ('the_content', 'add_custom_content');

function add_custom_content( $content ) 
{
    global $post;

    if ( $post->post_type == 'doc' ) {
        // Делаем изменения только для зарегистрированного нами типа записей

        $content .= '<p>Привет!';



    }
    

    return $content;
}



?>
