<?php

// Управление схемой данных

defined( 'ABSPATH' ) || exit;

global $kubik_schema;
$kubik_schema = new kubik_schema();


class kubik_schema {

    public $schema_key = 'kubik_schema';

    function __construct()
    {


    }

    // Вывести строку меню

    public function menu_schema()
    {
        global $post;
        if ( ! current_user_can( 'edit_post', $post->ID ) ) return false;

        $out = '';
        $out .= '<li><a href="?mode=schema">Схема данных</a></li>';



        return $out;
    }

    // Вывести описание схемы 

    public function schema_show()
    {
        global $post;
        if ( ! current_user_can( 'edit_post', $post->ID ) ) return false;

        if ( ! ( isset( $_REQUEST['mode'] ) && $_REQUEST['mode'] == 'schema' ) ) return;
        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit' ) return $this->schema_edit();  

        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'save' ) $this->schema_edit( 'save' );  

        $out = '';

        $out .= '<h3>Схема данных</h3>';
        $out .= $this->show_xls_template();

        $schema = $this->get_schema();

        // p($schema);

        $out .= '<table>';
        $out .= '<th>Имя</th><th>Ячейка</th><th>Описание</th>';

        foreach ( $schema as $key => $value ) {

            $out .= '<tr>';
            
            $out .= '<td>' . $key . '</td>';
            $out .= '<td>' . $value['cell'] . '</td>';
            $out .= '<td>' . $value['desc'] . '</td>';

            $out .= '</tr>';

        }

        $out .= '</table>';

        $out .= '<a href="?mode=schema&action=edit">Редактировать</a>';

        return $out;
        
    }



    // Вывести редактор схемы 

    public function schema_edit( $action = 'edit' )
    {
        global $post;
        if ( ! current_user_can( 'edit_post', $post->ID ) ) return false;

        $out = '';

        if ( $action == 'edit' ) {

            $out .= '<form action="" method="POST">';
            $out .= '<textarea name="schema">';

            $out .= $this->get_schema( 'raw' );

            $out .= '</textarea>';
            $out .= '<p><input type="submit" value="Сохранить">';
            $out .= '<input type="hidden" name="mode" value="schema">';
            $out .= '<input type="hidden" name="action" value="save">';
            $out .= '</form>';

        } elseif ( $action == 'save' ) {

            global $post;
            
            $ret = update_post_meta( $post->ID, $this->schema_key, $_REQUEST['schema'] );

            if ( $ret ) $out .= '<div class="note">Информация успешно сохранена</div>';

        }

        return $out;        
    }


    // Получить описание схемы

    public function get_schema( $type = 'array', $id = NULL )
    {
        global $post;

        if ( $id == NULL ) $id = $post->ID;

        $out = array();

        $schema_raw = get_post_meta( $id, $this->schema_key, true );

        if ( $type == 'raw' ) return $schema_raw; 

        $arr = explode( "\n", $schema_raw );

        foreach ( $arr as $key => $value ) {
            // $arr[$key] = strim( $value );
            $flag = '';

            $st = strim( $value );

            if ( preg_match( '/^\/\//', $st ) ) continue;
            if ( preg_match( '/^\#/', $st ) ) continue;

            // Флаг + (меню)
            
            if ( preg_match( '/^\+/', $st ) ) {

                $flag = 'menu';
                $st = preg_replace( '/^\+/', '', $st );
                $st = trim( $st );

            };


            // Флаг * (многострочное текстовое поле)
            
            if ( preg_match( '/^\*/', $st ) ) {

                $flag = 'textarea';
                $st = preg_replace( '/^\*/', '', $st );
                $st = trim( $st );

            };



            $st_arr = explode( " ", $st );

            if ( ! ( isset( $st_arr[0] ) && $st_arr[0] ) ) continue;
            if ( ! ( isset( $st_arr[1] ) && $st_arr[1] ) ) continue;

            $k = $st_arr[0]; 
            $c = $st_arr[1]; 

            unset( $st_arr[0] );
            unset( $st_arr[1] );

            $d = implode( ' ', $st_arr );
            if ( $d == '' ) $d = $k;

            $out[$k] = array( 'cell' => $c, 'desc' => $d, 'flag' => $flag );

        }

        return $out;

    }


    // Форма загрузки

    public function show_xls_template()
    {
        $out = '';

        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'delete' ) {

            // Шаблон надо удалить

            $id = (int) $_REQUEST['id'];
            $ret = wp_trash_post( $id );

            if ($ret) $out .= '<div class="note">Шаблон успешно удален</div>';

        }

        if ( isset( $_REQUEST['xls_template_submit'] ) ) {
            
            // Шаблон только что загружен

            global $post;
                
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $attachment_id = media_handle_upload( 'xls_template_file', $post->ID, array( 'post_title' => 'Excel-шаблон' ) );
            
            if ( is_wp_error( $attachment_id ) ) {
                $out .= '<div class="error">Ошибка загрузки файла</div>';
            } else {
                $out .= '<div class="note">Файл успешно загружен</div>';
            }
                
        }

        global $kubik_excel;
        $xls_tpl = $kubik_excel->get_xls_template();
        
        if ( isset( $xls_tpl[0] ) ) {

            // Есть шаблон - показать его

            $url = $xls_tpl[0]->guid;
            $id = $xls_tpl[0]->ID;
            // p($xls_tpl[0]);
            // $out .='<table><tr><th>Общий Excel-шаблон</th></tr><tr><td><a href="' . $url . '" title="скачать"><i class="fa fa-2x fa-file-excel-o fa-lg"></i></a></td></tr></table>';  
            $out .='<p>Excel-шаблон: <a href="' . $url . '">скачать</a>, <a href="?mode=schema&action=delete&id=' . $id . '">удалить</a>';  

        } else {
            
            $out .= '<div class="upload"><p>Загрузка Excel-шаблона';
            
            $out .= '<form method="post" action="#" enctype="multipart/form-data">
            <p><input type="file" name="xls_template_file" multiple="false">
            <p><input type="submit" name="xls_template_submit" value="Загрузить">       
            </form>';
            
            $out .= '</div><br />';
        }

        return $out;
            
    }

}




?>