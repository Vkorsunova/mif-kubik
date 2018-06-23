<?php

// Управление источниками данных Excel

defined( 'ABSPATH' ) || exit;

global $kubik_excel;
$kubik_excel = new kubik_excel();

class kubik_excel {



    function __construct()
    {

    }


    // Вывести строку меню

    public function menu_add()
    {
        $out = '';
        $out .= '<li><a href="?mode=excel">Загрузить данные</a></li>';

        return $out;
    }

    

    // Форма загрузки

    public function upload_excel()
    {

        if ( ! ( isset( $_REQUEST['mode'] ) && $_REQUEST['mode'] == 'excel' ) ) return;       


        $out = '';
        
        if ( isset( $_REQUEST['excel_submit'] ) ) {
        
            $file = $_FILES['excel_file']['tmp_name'];
            $name = $_FILES['excel_file']['name'];
            // p($_FILES);
            $out .= $this->parse_excel( $file, $name );
            
        } else {

            $xls_tpl = $this->get_xls_template();

            // p($xls_tpl);

            $txt = '';
            if ( isset($xls_tpl[0]) ) $txt = ' (<a href="' . $xls_tpl[0]->guid . '">скачать шаблон</a>)';

            $out .= '<div class="part"><h3>Загрузить данные</h3>';
            $out .='<table>';
            $out .='<tr><td>'; 
                                  
            $out .= '<div class="upload"><p>Загрузите один или несколько файлов Excel' . $txt;
            
            $out .= '<form method="post" action="#" enctype="multipart/form-data">
            <p><input type="file" name="excel_file" multiple="false">
            <p><input type="submit" name="excel_submit" value="Загрузить">       
            </form>';
            
            $out .= '</div>';
            $out .='</td></tr>';
            $out .= '</table></div>';
            
        }

        return $out;
            
    }

    function parse_excel( $file, $name )
    {
        global $kubik_schema;
        global $kubik_data;

        $arr = explode( ".", $name );
        $ext = array_pop( $arr );

        if ( $ext == 'xls' ) {
            $type = 'Excel5';
        } elseif ( $ext == 'xlsx' ) {
            $type = 'Excel2007';
        } else { 
            return;
        }  
        
        $objPHPExcel = new PHPExcel();
     
        $objReader = PHPExcel_IOFactory::createReader( $type );
        $objPHPExcel = $objReader->load( $file );
        $page = $objPHPExcel->setActiveSheetIndex( 0 );
            
        // $val = $page->getValue('A1');
        // $val = $page->getCellByColumnAndRow( 0, 1 )->getValue();

        $schema = $kubik_schema->get_schema();

        $data = array();
        foreach ( (array) $schema as $key => $value ) 
        {
            $val = $page->getCell( $value['cell'] )->getValue();
            // p( $value['desc'] . ' - ' . $val );
            $data['data'][$key] = $val;

        }

        if ( $kubik_data->data_save( $data ) ) {

            return '<div class="note">Данные успешно сохранены</div>';

        };


    }
   
    // Получить шаблоны

    public function get_xls_template( $id = NULL )
    {
        global $post;

        if ( $id ) {
            
            $arr[] = get_post( $id );
            
        } else {
            
            $args = array(
                'numberposts' => -1,
                'post_parent' => $post->ID, 
                'post_type' => 'attachment',
                'order' => 'ASC',
                'post_status' => 'inherit',
                'orderby' => 'menu_order',
            
            );
            
            $arr = get_posts( $args );
            
        }
       
        // Удалить из ответа лишние файлы 

        foreach ( $arr as $key => $item ) {
            $arr_tmp = explode( '.', $item->guid );
            $ext = array_pop( $arr_tmp );
            if ( ! in_array( $ext, array( 'xls', 'xlsx' ) ) ) unset( $arr[$key] );
        }

        $arr = array_values($arr);

        if ( $id ) { $out = $arr[0]; } else { $out = $arr; }
        
        return $out;
    }


    function create_xls( $file_tpl, $data_id ) 
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
        $scheme = $kubik_data->get_schema( 'array', $data['doc_id'] );

        $arr_tmp = explode( '.', $file_tpl );
        $ext = array_pop( $arr_tmp );
        
        if ( $ext == 'xls' ) {
            $type = 'Excel5';
        } elseif ( $ext == 'xlsx' ) {
            $type = 'Excel2007';
        } else { 
            return;
        }  
        
        $objPHPExcel = new PHPExcel();
        
        $objReader = PHPExcel_IOFactory::createReader( $type );
        $objPHPExcel = $objReader->load( $file_tpl );
    
        $page = $objPHPExcel->setActiveSheetIndex( 0 );
            
        // Заполнить таблицу данными
        
        foreach ( $scheme as $key => $item ) {
            
            if ( isset( $data['data'][$key] ) ) $page->setCellValue( $item['cell'], $data['data'][$key] ); 

        }
            
        $objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, $type );
        $objWriter->save( $new_file );
                
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);

        return $new_file;
    }

}



?>