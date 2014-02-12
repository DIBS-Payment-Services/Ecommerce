<?php

require_once(WPSC_FILE_PATH . '/wpsc-merchants/dibs_api/pw/dibs_pw_api.php');
require_once(WPSC_FILE_PATH . '/wpsc-merchants/dibs_api/sb/dibs_sb.php');

if( version_compare( get_option( 'wpsc_version' ), '3.8.9', '>=' ) ) {
    require_once(WPSC_FILE_PATH . '/wpsc-includes/purchase-log.class.php');
 }

$nzshpcrt_gateways[$num] = array('name'            => 'DIBS Payment Window',
                                 'internalname'    => 'dibspw',
                                 'function'        => 'gateway_dibspw',
                                 'form'            => 'form_dibspw',
                                 'submit_function' => 'submit_dibspw',
                                 'payment_type'    => 'dibspw',
                                 'display_name'    => 'DIBS Payment Window | Secured Payment Services',
                                 'image'           =>  WPSC_URL . '/wpsc-merchants/dibs_api/imgs/dibspw.gif',
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
function gateway_dibspw($separator, $sessionid) {
    global $wpdb, $wpsc_cart, $wpsc_purchlog_statuses;

    $wpsc_cart->get_shipping_option();
    $wpsc_cart->get_shipping_quotes();
    $wpsc_cart->get_shipping_method();
    $subtotal = $wpsc_cart->calculate_subtotal();

    $oDIBS = new dibs_pw_api();

    $aProdFees = $oDIBS->cms_dibs_getFees();
    $sCurrency = $oDIBS->cms_dibs_getCurrency();
    $aPurchaseLog = $oDIBS->cms_dibs_getOrderById($sessionid);
    $aUserInfo = $_POST['collected_data'];

    $oDIBS->helper_dibs_db_write("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "`
                                           SET `processed` = '" . 
                                           $oDIBS->helper_dibs_tools_conf('statusp') . "'
                                           WHERE `id` = '" . $aPurchaseLog['id'] . "' LIMIT 1;");

    $mOrderInfo = array(
        'currency'   => $sCurrency,
        'user'       => $aUserInfo,
        'cart'       => $wpsc_cart,
        'totalprice' => $wpsc_cart->total_price,
        'shipping'   => $aProdFees['shipping'],
        'id'         => $aPurchaseLog['id'],
        'taxes'      => $aProdFees['items'],
        'additional' => array('pid' => $sessionid)
    );

    $aData = $oDIBS->api_dibs_get_requestFields($mOrderInfo);

    $sOutput = '<form id="dibspw_form" name="dibspw_form" method="post" action="' .
                    $oDIBS->api_dibs_get_formAction() . '">' . "\n";
    foreach($aData as $sKey => $sValue) {
        $sOutput .= '<input type="hidden" name="' . $sKey . '" value="' . $sValue . '" />' . "\n";
    }
    $sOutput .= '</form>'. "\n";
    echo $sOutput;
    echo "<script language=\"javascript\" type=\"text/javascript\">
             document.getElementById('dibspw_form').submit();
          </script>";

    exit();
}

function nzshpcrt_dibspw_process() {
    global $wpdb;
    
    if(isset($_GET['dibspw_result']) && isset($_POST['s_pid'])) {
        array_walk($_POST, create_function('&$val', '$val = stripslashes($val);'));
        $oDIBS = new dibs_pw_api();
        $mOrder = $oDIBS->cms_dibs_getOrderById($_POST['s_pid']);
        $orderId = $_POST['orderid'];
        switch($_GET['dibspw_result']) {
            case 'callback': 
             if( version_compare( get_option( 'wpsc_version' ), '3.8.9', '>=' ) )
              {
                  if ($oDIBS->api_dibs_action_callback($mOrder)) {
                    $purchaselog = new WPSC_Purchase_Log($orderId);
                    $purchaselog->set('processed', 3);$purchaselog ->save();
                    $wpscmerch = new wpsc_merchant($orderId, false);
                    $wpscmerch->set_purchase_processed_by_purchid($oDIBS->helper_dibs_tools_conf('status'));
                  } 
              }else {
                     $oDIBS->api_dibs_action_callback($mOrder);
              }
                      
            break;
            case 'success':
               $sResult = $oDIBS->api_dibs_action_success($mOrder);
                if(!isset($_GET['page_id']) || get_permalink($_GET['page_id']) != get_option('transact_url')) {
                 
                    if(empty($sResult)) {
                        $sLocation = add_query_arg('sessionid', $_POST['s_pid'], get_option('transact_url'));
                        wp_redirect($sLocation);
                        exit();
                    }
                    else {
                        echo "<p>Error code: " . $sResult . "<br />";
                        echo "Error message: " . $oDIBS->helper_dibs_tools_lang($sResult, "err") . "</p>";
                        echo '<a href="' . get_option('transact_url') . '"><button>Return to shop</button></a>';
                        exit();
                    }
                }
            break;
            case 'cancel':
                $oDIBS->api_dibs_action_cancel();
                if (isset($_POST['orderid'])) {
                    $oOrder = $oDIBS->helper_dibs_obj_order($mOrder);
                    if(isset($oOrder->orderid) && $oOrder->orderid > 0) {
                        transaction_results($_POST['s_pid'], false);
                        $oDIBS->helper_dibs_db_write("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "`
                                                      SET `processed` = '" . 
                                                      $oDIBS->helper_dibs_tools_conf('statusc') . "'
                                                      WHERE `id` = '" . $oOrder->orderid . "' LIMIT 1;");
                        wp_redirect(get_option('transact_url'));
                        exit();
                    }
                }
                wp_redirect($oDIBS->helper_dibs_obj_urls()->carturl);
            break;
        }
    }
}

/**
 * Saving of module settings.
 * 
 * @return bool 
 */
function submit_dibspw() {
    $oDibsSb = new dibs_pw_settingsBuilder();
    $aParams = $oDibsSb->getParamsList();
    for($i=0; $i<count($aParams); $i++) {
        $sKey = 'dibspw_' . strtolower($aParams[$i]);
        update_option($sKey, isset($_POST[$sKey]) ? $_POST[$sKey] : "");
    }
    
    if (!isset($_POST['dibspw_form'])) $_POST['dibspw_form'] = array();
    foreach((array)$_POST['dibspw_form'] as $sKey => $sValue) {
        update_option(('dibspw_form_' . $sKey), $sValue);
    }

    return true;
}

/**
 * Generating module settings form.
 * 
 * @return string 
 */
function form_dibspw() {
    $oDibsSb = new dibs_pw_settingsBuilder();
    
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
                            <select name="dibspw_form[first_name_b]">' . 
                                nzshpcrt_form_field_list(get_option('dibspw_form_first_name_b')) . 
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Last Name Field</td>
                        <td>
                            <select name="dibspw_form[last_name_b]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_last_name_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Address Field</td>
                        <td>
                            <select name="dibspw_form[address_b]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_address_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>City Field</td>
                        <td>
                            <select name="dibspw_form[city_b]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_city_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>State Field</td>
                        <td>
                            <select name="dibspw_form[state_b]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_state_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Postal/Zip code Field</td>
                        <td>
                            <select name="dibspw_form[post_code_b]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_post_code_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Country Field</td>
                        <td>
                            <select name="dibspw_form[country_b]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_country_b')) .
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
                            <select name="dibspw_form[first_name_d]">' . 
                                nzshpcrt_form_field_list(get_option('dibspw_form_first_name_d')) . 
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Last Name Field</td>
                        <td>
                            <select name="dibspw_form[last_name_d]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_last_name_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Address Field</td>
                        <td>
                            <select name="dibspw_form[address_d]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_address_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>City Field</td>
                        <td>
                            <select name="dibspw_form[city_d]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_city_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>State Field</td>
                        <td>
                            <select name="dibspw_form[state_d]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_state_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Postal/Zip code Field</td>
                        <td>
                            <select name="dibspw_form[post_code_d]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_post_code_d')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Country Field</td>
                        <td>
                            <select name="dibspw_form[country_d]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_country_d')) .
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
                            <select name="dibspw_form[email_b]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_email_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>
                            <select name="dibspw_form[phone_b]">' .
                                nzshpcrt_form_field_list(get_option('dibspw_form_phone_b')) .
                           '</select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span  class="wpscsmall description">
                                For more help configuring DIBS Payment Window, 
                                please read our documentation 
                                <a href="http://tech.dibs.dk/integration_methods/dibs_payment_window/">here</a>.
                            </span>
                        </td>
                    </tr>';
    
    return $oDibsSb->render() . $sFieldsSync;
}

add_action('init', 'nzshpcrt_dibspw_process');
?>