<?php
/*
Plugin Name: MIF Kubik
Plugin URI: http://mif.vspu.ru
Description: Плагин автоматического создания текстовых документов из Excel-шаблонов
Author: Вероника Корсунова
Version: 1.0
Author URI: https://vk.com/veronica.korsunova
*/

defined( 'ABSPATH' ) || exit;

include_once dirname( __FILE__ ) . '/inc/schema.php';
include_once dirname( __FILE__ ) . '/inc/data.php';
include_once dirname( __FILE__ ) . '/inc/template.php';
include_once dirname( __FILE__ ) . '/inc/parser.php';
include_once dirname( __FILE__ ) . '/inc/excel.php';
include_once dirname( __FILE__ ) . '/classes/docxtemplate.class.php';
include_once dirname( __FILE__ ) . '/classes/PHPExcel.php';



// Функция запускается после загрузки WordPress и перед выводом чего-либо на экран
// Здесь можно определять свои типы записей, назначить шорткоды и др. 
// Подробнее (все хуки и фильтры): http://wp-kama.ru/hooks

add_action( 'init', 'kubik_init' );

function kubik_init() 
{

    // В примере - создается новый тип записей "Документы" (Docs)
    // Подробнее - http://wp-kama.ru/function/register_post_type

    register_post_type( 'doc', array(
        'labels' => array(
        'name'            => __( 'Docs' ), // основное название для типа записи
        'singular_name'   => __( 'Doc' ),  // название для одной записи этого типа
        'add_new'         => __( 'Add docs' ), // для добавления новой записи
        'add_new_item'    => __( 'Add doc item' ), // заголовка у вновь создаваемой записи в админ-панели.
        'edit'            => __( 'Edit doc' ), // редактировать элемент
        'edit_item'       => __( 'Edit doc item' ), // для редактирования типа записи
        'new_item'        => __( 'Single doc' ), // текст новой записи
        'all_items'       => __( 'All docs' ), //Все элементы
        'view'            => __( 'View docs' ), //Посмотреть
        'view_item'       => __( 'View single doc' ), // для просмотра записи этого типа
        'search_items'    => __( 'Search docs' ), // для поиска по этим типам записи
        'not_found'       => __( 'Docs not found' ), // если в результате поиска ничего не было найдено
    ),
    'public' => true, 
    'menu_position' => 20,
    'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
    'taxonomies' => array( '' ),
    'has_archive' => true,
    'capability_type' => 'post',
    'menu_icon'   => 'dashicons-book',
    'rewrite' => array( 'slug' => 'docs' ),
    'show_in_admin_bar'   => false, 
    ));    

    register_post_type( 'doc_data', array(
        'labels' => array(
        'name'            => __( 'Docs data' ), // основное название для типа записи
        'singular_name'   => __( 'Doc data' ),  // название для одной записи этого типа
        'add_new'         => __( 'Add docs data' ), // для добавления новой записи
        'add_new_item'    => __( 'Add doc data item' ), // заголовка у вновь создаваемой записи в админ-панели.
        'edit'            => __( 'Edit doc data' ), // редактировать элемент
        'edit_item'       => __( 'Edit doc data item' ), // для редактирования типа записи
        'new_item'        => __( 'Single doc data' ), // текст новой записи
        'all_items'       => __( 'All docs data' ), //Все элементы
        'view'            => __( 'View docs data' ), //Посмотреть
        'view_item'       => __( 'View single doc data' ), // для просмотра записи этого типа
        'search_items'    => __( 'Search docs data' ), // для поиска по этим типам записи
        'not_found'       => __( 'Docs data not found' ), // если в результате поиска ничего не было найдено
    ),
    'public' => true, 
    'menu_position' => 21,
    'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
    'taxonomies' => array( '' ),
    'has_archive' => true,
    'capability_type' => 'post',
    'menu_icon'   => 'dashicons-book',
    'rewrite' => array( 'slug' => 'docs-data' ),
    'show_in_admin_bar'   => false, 
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

    if ( ! is_user_logged_in() ) {

        $content .= '<div class="error">Доступ ограничен</div>';
        return $content;

    }

    
    global $post;
    // global $kubik_schema;
    global $kubik_data;
    global $kubik_template;
    global $kubik_excel;
    
    if ( $post->post_type == 'doc' && is_single() ) {

        // Делаем изменения только для зарегистрированного нами типа записей
        
        $content .= '<ul class="menu">';
        $content .= '<li><a href="' . get_permalink() . '">Главная</a></li>';
        $content .= $kubik_data->menu_add();
        $content .= $kubik_excel->menu_add();
        $content .= $kubik_template->menu_add();
        $content .= $kubik_data->menu_schema();
        $content .= '</ul>';

        $content .= $kubik_data->data_edit();
        $content .= $kubik_excel->upload_excel();
        $content .= $kubik_template->templates_show();
        $content .= $kubik_data->schema_show();

        $content .= $kubik_data->data_show();


    }
    
    return $content;
}



if ( ! function_exists( 'p' ) ) {

    function p( $data )
    {
        print_r( '<pre>' );
        print_r( $data );
        print_r( '</pre>' );
    }

}


if ( ! function_exists( 'f' ) ) {
    
        function f( $data )
        {
            file_put_contents( '/usr/local/www/sandbox.fizmat.vspu.ru/kubik/wp-content/uploads/tmp/log.txt', date( "D M j G:i:s T Y - " ), FILE_APPEND | LOCK_EX );
            file_put_contents( '/usr/local/www/sandbox.fizmat.vspu.ru/kubik/wp-content/uploads/tmp/log.txt', print_r( $data, true ), FILE_APPEND | LOCK_EX );
            file_put_contents( '/usr/local/www/sandbox.fizmat.vspu.ru/kubik/wp-content/uploads/tmp/log.txt', "\n", FILE_APPEND | LOCK_EX );
        }
    
}
    
    

function strim( $st = '' )
{
    // Удаляет двойные пробелы, а также пробелы в начале и в конце строки

    $st = preg_replace( '/\s+/', ' ', $st );
    $st = trim( $st );
    
    return $st;
}


?>
