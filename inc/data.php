<?php

// Управление данными

defined( 'ABSPATH' ) || exit;

global $kubik_data;
$kubik_data = new kubik_data();

class kubik_data extends kubik_schema {

    public $data_key = 'kubik_data';


    function __construct()
    {

    }


    // Вывести строку меню

    public function menu_add()
    {
        $out = '';
        $out .= '<li><a href="?mode=data&action=add">Добавить данные</a></li>';

        return $out;
    }


    // Показать данные 

    public function data_show()
    {
        if ( isset( $_REQUEST['mode'] ) && in_array( $_REQUEST['mode'], array( 'template', 'schema' ) ) ) return;   


        // Определяем перечень допустимых шаблонов

        global $kubik_template;
        $tpl_arr = $kubik_template->get_template();


        $out = '';

        $scheme = $this->get_schema();
        $arr = $this->get_data();

        $out .= '<div class="part"><h3>Список документов</h3>';
        $out .= '<table>';
        $i = 1;
        
        $xls_flag = false;
        global $kubik_excel;
        $xls_tpl = $kubik_excel->get_xls_template();

        if ( isset( $xls_tpl[0] ) ) {

            $xls_flag = true;
            $xls_id = $xls_tpl[0]->ID;
            ///!!!

        }


        // Рисуем шапку
        $out .= '<tr><th>№</th>';

        foreach ( $scheme as $key => $elem ) {
            
            if ( $elem['flag'] != 'menu' ) continue;

            $out .= '<th>';
            $out .= $elem['desc'];
            $out .= '</th>';
            
        }             
        
        if ( $xls_flag ) $out .= '<th>X</th>';

        // !!! Оформляем шапку - столбцы для скачивающихся файлов
        $n = 1;
        foreach ( (array) $tpl_arr as $tpl ) $out .='<th>' . $n++ . '</th>'; 
        
        $out .= '</tr>';
                
        foreach ( (array) $arr as $item ) {

             $out .= '<tr>';
             $out .= '<td>'.$i.'</td>';
             $i++;

            foreach ( $scheme as $key => $elem ) {
    
                if ( $elem['flag'] != 'menu' ) continue;

                $out .= '<td>';
                $out .= $item['data'][$key];
                $out .= '</td>';
                                            
            }                         

            if ( $xls_flag ) $out .= '<th><a href="?mode=download&id=' . $item['ID'] . '&tpl=' . $xls_id . '" title="Excel-шаблон"><i class="fa fa-file-excel-o fa-lg"></i></a></th>';

            // !!! Делаем ссылки на все документы по имеющимся шаблонам
            foreach ( (array) $tpl_arr as $tpl ) $out .='<td><a href="?mode=download&id=' . $item['ID'] . '&tpl=' . $tpl->ID . '" title="' . $tpl->post_title . '"><i class="fa fa-file-word-o fa-lg"></i></a></td>'; 
            
            
            $out .='<td><a href="?mode=data&action=edit&id=' . $item['ID'] . '"><i class="fa fa-pencil fa-lg"></i></a></td>'; 
            $out .='<td><a href="?mode=data&action=delete&id=' . $item['ID'] . '"><i class="fa fa-trash fa-lg"></i></a></td>'; 

            $out .= '</tr>';        
            
        }

        $out .= '</table></div>';
        
        // p($data);

        // $out .= '123';
        
        return $out;
        
    }

    
    // Показать данные 

    public function get_data( $id = NULL )
    {
        global $post;

        if ( $id ) {
            
            $data_raw[] = get_post( $id );
            
        } else {
            
            $args = array(
                'post_parent' => $post->ID, 
                'post_type' => 'doc_data',
                'post_status' => 'publish',
            );

            if ( ! current_user_can( 'edit_post', $post->ID ) ) $args['author'] = get_current_user_id();

            $data_raw = get_posts( $args );
            
        }
        
        $arr = array();

        foreach ( (array) $data_raw as $item ) {
            // p($item);

            $arr[] = array(
                'ID' => $item->ID,
                'author' => $item->post_author,
                'doc_id' => $item->post_parent,
                'data' => unserialize( $item->post_content ),


            );

        }

        // $out = get_post_meta( $post->ID, $this->data_key, false );
        // p($out);

        if ( $id ) { $out = $arr[0]; } else { $out = $arr; }

        return $out;
    }


    // Редактировать данные 

    public function data_edit( $action = 'edit' )
    {
        if ( ! ( isset( $_REQUEST['mode'] ) && $_REQUEST['mode'] == 'data' ) ) return;

        $action = 'add';
        if ( isset( $_REQUEST['action'] ) ) $action = $_REQUEST['action'];  

        $out = '';
        // $out .= '123';

        $scheme = $this->get_schema();

        if ( $action == 'add' || $action == 'edit' ) {

            if ( $action == 'edit' && isset( $_REQUEST['id'] ) ) {
                
                $data = $this->get_data( $_REQUEST['id'] );

            }

            $out .= '<div class="part"><h3>Анкета</h3>';
            $out .= '<form method="POST">';
            $out .= '<table>';

            foreach ( $scheme as $key => $item ) {

                $val = '';
                if ( isset( $data['data'][$key] ) ) $val = $data['data'][$key];

                $out .= '<tr>';
                $out .= '<td class="desc">' . $item['desc'] . '</td>';

                if ( $item['flag'] == 'textarea' ) {

                    $out .= '<td class="data"><textarea name="data[' . $key . ']">' . $val . '</textarea></td>';

                } else {

                    $out .= '<td class="data"><input type="text" name="data[' . $key . ']" value="' . $val . '"></td>';

                }

                $out .= '</tr>';

            }

            $out .= '</table>';
            
            if ( isset( $data['ID'] ) ) $out .= '<input type="hidden" name="id" value="' . $data['ID'] . '">';

            $out .= '<input type="hidden" name="action" value="save">';
            $out .= '<input type="submit" value="Сохранить">';

            $out .= '</form></div>';

        } elseif ( $action == 'save' ) {

            if ( $this->data_save( $_REQUEST ) ) {
                
                $out .= '<div class="note">Информация успешно сохранена</div>';

            } else {

                $out .= '<div class="error">Ошибка сохранения</div>';

            };

        } elseif ( $action == 'delete' ) {
            
            if ( $this->data_delete( $_REQUEST['id'] ) ) {
                
                $out .= '<div class="note">Информация успешно удалена</div>';

            } else {

                $out .= '<div class="error">Ошибка удаления</div>';

            };

        }


        return $out;
    }

    // Удалить данные

    public function data_delete( $id )
    {
        global $post;

        $ret = wp_trash_post( $id );

        // f( $id );
        // $current_user = wp_get_current_user();
        // f( $current_user->user_login );

        return $ret;
    }

    // Сохранить данные

    public function data_save( $data )
    {
        global $post;

        $args = array(
            'post_content' => serialize( $data['data'] ),
            'post_title' => 'Данные для документов ' . $post->ID, 
            'post_parent' => $post->ID, 
            'post_type' => 'doc_data',
            'post_status' => 'publish',
        );
        
        if ( isset( $data['id' ] ) ) $args['ID'] = $data['id' ];

        $ret = wp_insert_post( $args );

        return $ret;
    }

    // public function data_save( $data, $data_old = NULL )
    // {
    //     global $post;
       
    //     if ( $data_old == NULL ) {
            
    //         $ret = add_post_meta( $post->ID, $this->data_key, $data, false );

    //     } else {

    //         $ret = update_post_meta( $post->ID, $this->data_key, $data, $data_old );

    //     }
        

    //     return $ret;
    // }



}



?>