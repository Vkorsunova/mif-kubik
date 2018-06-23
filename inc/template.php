<?php

// Управление шаблонами

defined( 'ABSPATH' ) || exit;

global $kubik_template;
$kubik_template = new kubik_template();

class kubik_template {



    function __construct()
    {

    }


    // Вывести строку меню

    public function menu_add()
    {
        global $post;
        if ( ! current_user_can( 'edit_post', $post->ID ) ) return false;

        $out = '';
        $out .= '<li><a href="?mode=template">Управление шаблонами</a></li>';

        return $out;
    }

    
    
    // Вывести шаблоны

    public function templates_show()
    {
        global $post;
        if ( ! current_user_can( 'edit_post', $post->ID ) ) return false;

        if ( ! ( isset( $_REQUEST['mode'] ) && $_REQUEST['mode'] == 'template' ) ) return;
        
        $out = '';

        if ( isset( $_REQUEST['action'] ) ) {

            $action = sanitize_key( $_REQUEST['action'] );

            if ( $action == 'delete' ) {
            
                // Удаляем шаблон
    
                if ( $this->template_delete( $_REQUEST['id'] ) ) {
                    
                    $out .= '<div class="note">Шаблон успешно удален</div>';
    
                } else {
    
                    $out .= '<div class="error">Ошибка удаления</div>';
    
                };
            
            } elseif ( $action == 'save' ) {

                foreach ( (array) $_REQUEST['tpl'] as $key => $value ) {

                    $id = (int) $key;
                    $order = (int) $value;
                    
                    $args = array(

                        'ID' => $id,
                        'menu_order' => $order,

                    );

                    wp_update_post( $args );
                    
                }


            }

        }




        $out .= '<div class="part"><h3>Управление шаблонами</h3>';
        $out .= $this->upload_template();

        $out .= '<form method="POST">';
       
        $out .= '<p><table>';
        $out .= '<th>№</th><th>Название</th><th>Порядок</th><th>Ссылка</th><th></th>';

        $num = 1;

        $arr = $this->get_template();

        foreach ( (array) $arr as $item ) {
           
            $out .= '<tr>';
            // p($item);
            $out .= '<td>';
            $out .= $num++;
            $out .= '</td>';
            
            $out .= '<td>';
            $out .= $item->post_title;
            $out .= '</td>';
            
             $out .= '<td>';
             $out .= '<input type="text" name="tpl[' . $item->ID . ']" value="' . $item->menu_order . '" size="2">';
             $out .= '</td>';

            $out .= '<td>';
            // $out .= $item->guid;
            $out .= '<a href="' . $item->guid . '"><i class="fa fa-file-word-o fa-lg"></i></a>';
            $out .= '</td>';
           
            // $out .= '<td>';
            // $out .= '<a href="?mode=template&action=edit&id=' . $item->ID . '"><i class="fa fa-trash fa-lg"></i></a>';
            // $out .= '</td>';
            
            $out .= '<td>';
            $out .= '<a href="?mode=template&action=delete&id=' . $item->ID . '"><i class="fa fa-trash fa-lg"></i></a>';
            $out .= '</td>';

            
            
           
            $out .= '</tr>';    
        }


        $out .= '</table></div>';

        $out .= '<input type="hidden" name="mode" value="template">';
        $out .= '<input type="hidden" name="action" value="save">';
        $out .= '<input type="submit" value="Сохранить">';

        $out .= '</form>';
        

        return $out;
    }


    // Форма загрузки

    public function upload_template()
    {
       
        $out = '';
        
        if ( isset( $_REQUEST['template_submit'] ) ) {
        
            global $post;
             
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $attachment_id = media_handle_upload( 'template_file', $post->ID, array( 'post_title' => $_REQUEST['template_name'] ) );
           
            if ( is_wp_error( $attachment_id ) ) {
                $out .= '<div class="error">Ошибка загрузки файла</div>';
            } else {
                $out .= '<div class="note">Файл успешно загружен</div>';
            }
           
        } else {
          
            $out .= '<div class="upload"><p>Загрузка шаблона';
            
            $out .= '<form method="post" action="#" enctype="multipart/form-data">
            <p><input type="text" name="template_name" placeholder="Название шаблона">
            <p><input type="file" name="template_file" multiple="false">
            <p><input type="submit" name="template_submit" value="Загрузить">       
            </form>';
         
            $out .= '</div>';
        }

        return $out;
            
    }

           
    // Получить шаблоны

    public function get_template( $id = NULL )
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
            
            
            // Удалить из ответа лишние файлы 
            
            foreach ( $arr as $key => $item ) {
                $arr_tmp = explode( '.', $item->guid );
                $ext = array_pop( $arr_tmp );
                if ( ! in_array( $ext, array( 'docx', 'pptx' ) ) ) unset( $arr[$key] );
            }
            
        }
        
        if ( $id ) { $out = $arr[0]; } else { $out = $arr; }
        
        return $out;
    }
    
    
    // Удалить данные

    public function template_delete( $id )
    {
        global $post;

        $ret = wp_trash_post( $id );

        return $ret;
    }
   
}



?>