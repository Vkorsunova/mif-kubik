<?php

// Формирование docx-файла из шаблона

defined( 'ABSPATH' ) || exit;

global $kubik_parser;
$kubik_parser = new kubik_parser();


class kubik_parser {
    
    function __construct()
    {
        add_action( 'init',  array( $this, 'download' ) );

    }


    function download()
    {
        global $kubik_template;

        if ( ! ( isset( $_REQUEST['mode'] ) && $_REQUEST['mode'] == 'download' ) ) return;

        // Получить данные о запрашиваемом шаблоне
        $tpl = $kubik_template->get_template( $_REQUEST['tpl'] );

        // Получить данные о каталоге, где хранятся шаблоны
        $upload_dir = wp_upload_dir();

        $upl_arr = explode( '/', trim( $upload_dir['basedir'], '/' ) );
        $upl = array_pop( $upl_arr );
        
        // Вычислить физический путь к файлу шаблона
        $path = explode( '/'. $upl . '/', $tpl->guid ) ;
        $file_tpl = trailingslashit( $upload_dir['basedir'] ) . array_pop( $path );
        //f($tpl->guid);
        //p($file_tpl);
        $arr_tmp = explode( '.', $file_tpl );
        $ext = array_pop( $arr_tmp );

        if ( $ext == 'docx' ) {

            $new_file = $this->create_docx( $file_tpl, $_REQUEST['id'] );
            
        } elseif ( $ext == 'pptx' ) {
            
            
        } elseif ( $ext == 'xlsx' || $ext == 'xls' ) {
            
            global $kubik_excel;
            $new_file = $kubik_excel->create_xls( $file_tpl, $_REQUEST['id'] );

        }

        $this->file_force_download( $new_file );
    }


    function create_docx( $file_tpl, $data_id ) 
    {
        // Получить данные о каталоге, где хранятся шаблоны
        $upload_dir = wp_upload_dir();

        // Сформировать временную папку для пользователя
        $current_user = wp_get_current_user();
        $tmp = trailingslashit( $upload_dir['basedir'] ) . 'tmp/' . $current_user->user_login;
        if ( ! file_exists( $tmp ) ) mkdir( $tmp, 0777, true );

        // Сформировать имя временного файла
        $new_file = trailingslashit( $tmp ) . basename( $file_tpl );

        // Получить данные для замены
        global $kubik_data;
        $data = $kubik_data->get_data( $data_id );

        $docx = new DOCXTemplate( $file_tpl );

        foreach ( (array) $data['data'] as $key => $value) {
            
            // $value = ( ! empty( $value ) ) ? $value : '-';
            $docx->set( $key, $value );
        
        }


        $result = $docx->saveAs( $new_file );    

        if ( $result ) {

            return $new_file;

        } else {

            return false;

        }
    }


    function file_force_download( $file ) 
    {
        if ( empty( $file ) ) return;

        if ( file_exists( $file ) ) {

            if ( ob_get_level() ) ob_end_clean();

        } else {

            return;

        }
    
        $content_types = array(
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip, application/x-compressed-zip',
        );
        
        $content_type = 'application/octet-stream';
        $extension_arr = explode( ".", $file );
        $extension = array_pop( $extension_arr );
        if ( isset( $content_types[$extension] ) ) $content_type = $content_types[$extension];
    
        header('Content-Description: File Transfer');
        header('Content-Type: ') . $content_type;
        header('Content-Disposition: attachment; filename="' . basename( $file ) ) . '"';
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize( $file ) );
    
        if ( $fd = fopen( $file, 'rb' ) ) {

            while ( !feof($fd) ) print fread( $fd, 1024 );
            fclose($fd);

        }
        
        exit;
    }
    


}




?>