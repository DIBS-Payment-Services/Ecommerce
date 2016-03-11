<?php

/*
	Plugin Name: DIBS FlexWin Payment Gateway for WP e-Commerce
	Plugin URI: 
	Description: Payment plugin for Wordpress Ecommerce allow to use DIBS FlexWin.  
	Version: 3.0.2
	Author: dibs
*/

require_once str_replace("\\", "/", dirname(__FILE__)) . '/dibs_api/fw/dibs_fw_api.php';
require_once str_replace("\\", "/", dirname(__FILE__)) . '/dibs_api/sb/dibs_fw_sb.php';

$nzshpcrt_gateways[$num] = array('name'            => 'DIBS FlexWin',
                                 'internalname'    => 'dibsflex',
                                 'function'        => 'gateway_dibsflex',
                                 'form'            => 'form_dibsflex',
                                 'submit_function' => 'submit_dibsflex',
                                 'payment_type'    => 'dibsflex',
                                 'display_name'    => 'DIBS FlexWin',
                                 'image'           =>  'http://m.c.lnkd.licdn.com/media/p/2/005/023/302/3889cf5.png',
                                 'requirements'    => array(
                                    'php_version'      => 5.2,
                                    'extra_modules'    => array()
                                  ));
/**
 * Generate form for checkout.
 * 
 * 
 * @global object $wpdb
 * @global object $wpsc_cart
 * @param type $separator
 * @param string $sessionid 
 */
function gateway_dibsflex($separator, $sessionid) {
    global $wpsc_cart;
   
    $wpsc_cart->get_shipping_option();
    $wpsc_cart->get_shipping_quotes();
    $wpsc_cart->get_shipping_method();
    $wpsc_cart->calculate_subtotal();

    $oDIBS = new dibs_fw_api();
    
    $aProdFees = $oDIBS->cms_dibs_getFees();
    $sCurrency = $oDIBS->cms_dibs_getCurrency();
    $aPurchaseLog = $oDIBS->cms_dibs_getOrderById($sessionid);
    $aUserInfo = $_POST['collected_data'];
   
    
    $oDIBS->helper_dibs_db_write("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "`
                                           SET `processed` = '2'
                                           WHERE `id` = '" . $aPurchaseLog['id'] . "' LIMIT 1;");
    $mOrderInfo = array(
        'currency'   => $sCurrency,
        'user'       => $aUserInfo,
        'cart'       => $wpsc_cart,
        'totalprice' => $wpsc_cart->total_price,
        'shipping'   => $aProdFees['shipping'],
        'id'         => $aPurchaseLog['id'],
        'taxes'      => $aProdFees['items'],
        'additional' => array('pid' => $sessionid),
        'total_tax'  => $wpsc_cart->total_tax
    );
    
    $aData = $oDIBS->api_dibs_get_requestFields($mOrderInfo);

    $sOutput = '<form id="dibsflex_form" name="dibsflex_form" method="post" accept-charset="UTF-8" action="' .
                dibs_fw_api::api_dibs_get_formAction() . '">' . "\n";
    foreach($aData as $sKey => $sValue) {
        $sOutput .= '<input type="hidden" name="' . $sKey . '" value="' . $sValue . '" />' . "\n";
    }
    $sOutput .= '<input type="submit" name="submit_to_dibs" value="Continue with DIBS Payment..." />' . "\n";
  
    $sOutput .= '</form>'. "\n";
    
    echo $sOutput;
    echo "<script language=\"javascript\" type=\"text/javascript\">
             setTimeout('document.getElementById(\'dibsflex_form\').submit()', 5000);
          </script>";
  
    exit();
    
}

function nzshpcrt_dibsflex_process() {
    if(isset($_POST['s_pid'])) {
        array_walk($_POST, create_function('&$val', '$val = stripslashes($val);'));
        $oDIBS = new dibs_fw_api();
        $mOrder = $oDIBS->cms_dibs_getOrderById($_POST['s_pid']);
        if(isset($_REQUEST['dibsflex_success']) && $_REQUEST['dibsflex_success'] == 'true') {
            if(!isset($_GET['page_id']) || get_permalink($_GET['page_id']) != get_option('transact_url')) {
                $iCode = $oDIBS->api_dibs_action_success($mOrder);
                if(empty($iCode)) {
                    $sTransac = isset($_POST['transact']) ? 
                                dibs_fw_api::api_dibs_sqlEncode($_POST['transact']) : "";
                    $sLocation = add_query_arg('sessionid', $_POST['s_pid'], get_option('transact_url'));
                    wp_redirect($sLocation);
                    exit();
                }
                else {
                    echo $oDIBS->api_dibs_getFatalErrorPage($iCode);
                    exit();
                }
            }        
        }
        elseif(isset($_REQUEST['dibsflex_cancel']) && $_REQUEST['dibsflex_cancel'] == 'true') {
                $oDIBS->api_dibs_action_cancel();
                if (isset($_POST['orderid'])) {
                     wp_redirect(get_option( 'shopping_cart_url' ));
                     exit();
                }
               
        }
        elseif(isset($_REQUEST['dibsflex_callback']) && $_REQUEST['dibsflex_callback'] == 'true') {
            $oDIBS->api_dibs_action_callback($mOrder);
        }
    }
}

/*
 * Not realized yet!!
function nzshpcrt_dibsflex_cgi() {
    if(isset($_GET['dibsflex_cgi']) && $_GET['dibsflex_cgi'] == 'true') {
        $oDIBS = new dibs_fw_api();
        $oDIBS->api_dibs_cgi_process();
        exit();
    }
}

function nzshpcrt_dibsflex_cgibuttons() {
    global $wpdb, $purchlogitem;
    if($purchlogitem->extrainfo->gateway == "dibsflex") {
        $oDIBS = new dibs_fw_api();
        echo '<p><strong>' . $oDIBS->helper_dibs_tools_lang('controls_title', 'lbl') . '</strong></p>';
        
        echo $oDIBS->api_dibs_cgi_getAdminControls($purchlogitem);
        
    }
}
*/

/**
 * Saving of module settings.
 * 
 * @return bool 
 */
function submit_dibsflex() {
    $oDibsSb = new dibs_fw_settingsBuilder();
    $aParams = $oDibsSb->getParamsList();
    for($i=0; $i<count($aParams); $i++) {
        $sKey = 'dibsflex_' . strtolower($aParams[$i]);
        if(isset($_POST[$sKey])) update_option($sKey,  $_POST[$sKey]);
    }
    
    if (!isset($_POST['dibsflex_form'])) $_POST['dibsflex_form'] = array();
    foreach((array)$_POST['dibsflex_form'] as $sKey => $sValue) {
        update_option(('dibsflex_form_' . $sKey), $sValue);
    }

    return true;
}

/**
 * Generating module settings form.
 * 
 * @return string 
 */
function form_dibsflex() {
    $oDibsSb = new dibs_fw_settingsBuilder();
    
    $sFieldsSync = '<tr class="update_gateway" >
                        <td colspan="2">
                            <div class="submit">
                                <input type="submit" value="' . 
                                __('Update &raquo;', 'wpsc') . 
                                '" name="updateoption" />
                            </div>
                        </td>
                    </tr>
                    <tr class="firstrowth">
                        <td style="border-bottom: medium none;" colspan="2">
                            <strong class="form_group">Billing Form Sent to Gateway</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>First Name Field</td>
                        <td>
                            <select name="dibsflex_form[first_name_b]">' . 
                                nzshpcrt_form_field_list(get_option('dibsflex_form_first_name_b')) . 
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Last Name Field</td>
                        <td>
                            <select name="dibsflex_form[last_name_b]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_last_name_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Address Field</td>
                        <td>
                            <select name="dibsflex_form[address_b]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_address_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>City Field</td>
                        <td>
                            <select name="dibsflex_form[city_b]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_city_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>State Field</td>
                        <td>
                            <select name="dibsflex_form[state_b]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_state_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Postal/Zip code Field</td>
                        <td>
                            <select name="dibsflex_form[post_code_b]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_post_code_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Country Field</td>
                        <td>
                            <select name="dibsflex_form[country_b]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_country_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr class="firstrowth">
                        <td style="border-bottom: medium none;" colspan="2">
                            <strong class="form_group">Shipping Form Sent to Gateway</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>First Name Field</td>
                        <td>
                            <select name="dibsflex_form[first_name_d]">' . 
                                nzshpcrt_form_field_list(get_option('dibsflex_form_first_name_d')) . 
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Last Name Field</td>
                        <td>
                            <select name="dibsflex_form[last_name_d]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_last_name_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Address Field</td>
                        <td>
                            <select name="dibsflex_form[address_d]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_address_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>City Field</td>
                        <td>
                            <select name="dibsflex_form[city_d]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_city_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>State Field</td>
                        <td>
                            <select name="dibsflex_form[state_d]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_state_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Postal/Zip code Field</td>
                        <td>
                            <select name="dibsflex_form[post_code_d]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_post_code_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Country Field</td>
                        <td>
                            <select name="dibsflex_form[country_d]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_country_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr class="firstrowth">
                        <td style="border-bottom: medium none;" colspan="2">
                            <strong class="form_group">Contacts Form Sent to Gateway</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td>
                            <select name="dibsflex_form[email_b]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_email_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>
                            <select name="dibsflex_form[phone_b]">' .
                                nzshpcrt_form_field_list(get_option('dibsflex_form_phone_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span  class="wpscsmall description">
                                For more help configuring DIBS FlexWin, 
                                please read our documentation 
                                <a href="http://tech.dibs.dk/" target="_blank">here</a>.
                            </span>
                        </td>
                    </tr>';
    
    return $oDibsSb->render() . $sFieldsSync;
}

add_action('init', 'nzshpcrt_dibsflex_process');
add_action('wpsc_purchlogitem_metabox_end', 'dibspayment_paywin_purchlogitem_metabox_end_flexwin');
function dibspayment_paywin_purchlogitem_metabox_end_flexwin($log_id) {
     $log = new WPSC_Purchase_Log( $log_id );
     $log_data_arr = $log->get_data();
     if( $log_data_arr['gateway'] ==  'dibsflex'
                && $log_data_arr['transactid']) {

        echo "<b>DIBS transactionid</b> =<b>" . $log_data_arr['transactid'] . "</b>";
     }
}
