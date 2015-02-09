<?php
/*
Plugin Name: WooCommerce Order Status Delivery
Plugin URI: https://github.com/eltondev/WooCommerce-Order-Status-Delivery 
Description: Plugin para opção de escolha em modo de Retirada local, dando opção de dias da semana, no caso Quinta-Feira e Sexta-Feira.
Version: 1.0
Author: Elton Pereira e Leonardo Italo
Author URI: https://github.com/eltondev/WooCommerce-Order-Status-Delivery
*/


//Cria novo campo
add_filter( 'woocommerce_checkout_fields' , 'delivery_order_checkout_fields' );
function delivery_order_checkout_fields( $fields ) {
	$fields['billing']['billing_delivery'] = array(
			'label'     => __('Dia da Entrega', 'woocommerce'),
			'placeholder'   => _x('Selecione', 'placeholder', 'woocommerce'),
			'required'    => true,
			'clear'       => false,
			'type'        => 'select',
			'class'       => array('form-row-wide'),
			'options'     => array(
					'Quinta - Feira' => __('Quinta - Feira', 'woocommerce' ),
					'Sexta - Feira' => __('Sexta - Feira', 'woocommerce' )
			)
	);

	return $fields;
}

//Faz a validação
add_action('woocommerce_checkout_process', 'validate_delivery_order_checkout_fields');
function validate_delivery_order_checkout_fields() {
 
    if ( empty( $_POST['billing_delivery']) )
        wc_add_notice( __( 'Informe o dia de entrega.' ), 'error' );
}

//Salva 
add_action( 'woocommerce_checkout_update_order_meta', 'save_delivery_order_checkout_fields' );
function save_delivery_order_checkout_fields( $order_id ) {
    if ( ! empty( $_POST['billing_delivery'] ) ) {
        update_post_meta( $order_id, '_billing_delivery', sanitize_text_field( $_POST['billing_delivery'] ) );
    }
}
 
//Exibe no pedido
add_action( 'woocommerce_admin_order_data_after_billing_address', 'view_delivery_order_checkout_fields', 10, 1 );
function view_delivery_order_checkout_fields($order){
?>
    <p>
    	<h4><strong><?php echo __('Detalhes da entrega'); ?> </strong> </h4>
        <select name="billing_delivery" class="chosen_select">
            <option value="Quinta - Feira" <?php if(get_post_meta($order->id, '_billing_delivery', true )=="Quinta - Feira"){ echo 'selected="selected"'; } ?> >Quinta-feira</option>
            <option value="Sexta - Feira"  <?php if(get_post_meta($order->id, '_billing_delivery', true )=="Sexta - Feira"){ echo 'selected="selected"'; } ?> >Sexta-feira</option>
        </select>
	</p>
<?php
}
add_action( 'save_post', 'new_date_order', 10, 1 );
function new_date_order($order_id){
    if ( ! empty( $_POST['billing_delivery'] ) ) {
        update_post_meta( $order_id, '_billing_delivery', sanitize_text_field( $_POST['billing_delivery'] ) );
    }
}

//Envia via email
add_filter('woocommerce_email_order_meta_keys', 'send_mail_delivery_order_checkout_fields');
function send_mail_delivery_order_checkout_fields( $keys ) {
    $keys[] = 'Data da Entrega:';
    return $keys;
}

//Adiciona uma coluna do painel de pedido com o campo Dia de entrega
add_filter('manage_edit-shop_order_columns','show_billing_delivery',15);
function show_billing_delivery($columns){
   $columns['billing-delivery'] = __( 'Dia da entrega'); 
   return $columns; 
}
//Adiciona o conteúdo do campo Dia da entrega a coluna exibida no painel de pedido
add_action('manage_shop_order_posts_custom_column','show_value_billing_delivery',10,2);
function show_value_billing_delivery( $column ) {
	global $post, $woocommerce, $the_order;
	switch ($column) {
    	case 'billing-delivery' :
			echo get_post_meta($the_order->id, '_billing_delivery', true );
			break;
	}	
}
//Faz com que o campo Dia de entrega fique em ordem crescente ou decrescente
add_filter('manage_edit-shop_order_sortable_columns','sort_billing_delivery',15);
function sort_billing_delivery( $columns ) {
    $sort = array(
        'billing-delivery'    => '_billing_delivery',
    );
    return wp_parse_args( $sort, $columns );
}



add_action( 'restrict_manage_posts', 'shop_order_search_filter_manager' );
/**
 * Primeiro cria o dropdown
 * 
 * @author EltonDEV
 * 
 * @return void
 */
function shop_order_search_filter_manager(){
    $type = 'post';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }
    
	//adiciona o filtro para o tipo que você quiser
    if ('shop_order' == $type){      
	  //mude isso para a lista de valores que você quer mostrar
        $values = array(
            'Quinta - Feira' => 'Quinta - Feira', 
            'Sexta - Feira' => 'Sexta - Feira',
        );
        ?>
        <select name="Featured" class="chosen_select">
        <option value=""><?php _e('Escolha o dia de entrega', 'woocommerce_filter'); ?></option>
        <?php
            $current_v = isset($_GET['Featured'])? $_GET['Featured']:'';
            foreach ($values as $label => $value) {
                printf
                    (
                        '<option value="%s"%s>%s</option>',
                        $value,
                        $value == $current_v? ' selected="selected"':'',
                        $label
                    );
                }
        ?>
        </select>
        <?php
    }
}
add_filter( 'parse_query', 'woocommerce_filter_posts_shop_order' );
//add_action( 'parse_query', 'woocommerce_filter_posts_shop_order' );
/**
 *
 *submeter filtro por post meta
 * 
 * @author EltonDEV
 * @param  (wp_query object) $query
 * 
 * @return Void
 */
function woocommerce_filter_posts_shop_order( $query ){
    global $pagenow;
    $type = 'post';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }
    if ( 'shop_order' == $type && is_admin() && $pagenow=='edit.php' && isset($_GET['Featured']) && $_GET['Featured'] != '') {		
			$query->query_vars['meta_key'] = '_billing_delivery';
			$query->query_vars['meta_value'] = $_GET['Featured'];
			
			//Só exibe pedidos com status Processando
			$query->query_vars['post_status'] = 'wc-processing';
    }
}
