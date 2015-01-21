<?php
/**
 * Dibs A/S
 * Dibs Payment Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Payments & Gateways Extensions
 * @package    Dibspw_Dibspw
 * @author     Dibs A/S
 * @copyright  Copyright (c) 2010 Dibs A/S. (http://www.dibs.dk/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define("DIBS_PAYMENT_GATEWAT_CODE", "dibspw");
$nzshpcrt_gateways[$num] = array('name'            => 'DIBS Payment Window',
                                 'internalname'    => DIBS_PAYMENT_GATEWAT_CODE,
                                 'function'        => 'dibspayment_paywin_gateway',
                                 'form'            => 'dibspayment_paywin_form',
                                 'submit_function' => 'dibspayment_paywin_submit',
                                 'payment_type'    => DIBS_PAYMENT_GATEWAT_CODE,
                                 //'display_name'    => 'DIBS Payment Window | Secured Payment Services',
                                 'image'           =>  "http://m.c.lnkd.licdn.com/media/p/2/005/023/302/3889cf5.png",
                                 'requirements'    => array(
                                    'php_version'      => 5.2,
                                    'extra_modules'    => array()
                                  ));

define("DIBS_ENTRYPOINT_D2_URL", "https://sat1.dibspayment.com/dibspaymentwindow/entrypoint");

define("DIBS_ENTRYPOINT_DX_URL", "https://payment.dibspayment.com/dpw/entrypoint");

define("DIBS_SYSMOD", "wp3e_4_1_8");
/**
 * Generate form for checkout.
 * 
 * 
 * @global object $wpdb
 * @global object $wpsc_cart
 * @param type $separator
 * @param string $sessionid 
 */
function dibspayment_paywin_gateway($separator, $sessionid) {
     global $wpdb, $wpsc_cart;
        
     $wpsc_cart->sessionid = $sessionid;
     
     if(get_option('dibspw_platform') == "D2") {
        $entrypoint_url = DIBS_ENTRYPOINT_D2_URL;
     } 
     
     if(get_option('dibspw_platform') == "DX") {
        $entrypoint_url = DIBS_ENTRYPOINT_DX_URL;
     } 
     $request_data = dibspayment_paywin_order_params($wpsc_cart);
     
     if(WPSC_GATEWAY_DEBUG == true ) {
        exit("<pre>".print_r($request_data,true)."</pre>");
     }
     
     $out = '<form id="dibspw_form" name="dibspw_form" method="post" action="' .
                   $entrypoint_url  . '">' . "\n";
     
     foreach($request_data as $key => $value) {
        $out .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
     }
     
     $out .= '<input type="submit" name="submit_to_dibs" value="Continue to DIBS..." />' . "\n";
     
     $out .= '</form>'. "\n";
     
     
    
     echo $out;
     echo "<script language=\"javascript\" type=\"text/javascript\">document.getElementById('dibspw_form').submit();</script>";
     exit();
} 
 
/**
 * Handle Response from DIBS server
 * 
 * 
 *  
 */
 function dibspayment_paywin_process() {
    global $wpdb;
    if(isset($_GET['dibspw_result']) && isset($_POST['s_pid'])) {
        array_walk($_POST, create_function('&$val', '$val = stripslashes($val);'));
       
        $hamc_key = get_option('dibspw_hmac');
        $order_id = $_POST['orderid'];             
        switch($_GET['dibspw_result']) { 
            case 'callback': 
            
            if( $hamc_key && !isset( $_POST['MAC'])) {
                             die("HMAC error!");
              }       
                
            if($hamc_key && isset( $_POST['MAC']) && $_POST['MAC']) {
                            if( $_POST['MAC'] != dibspayment_paywin_calc_mac($_POST, $hamc_key, $bUrlDecode = FALSE)) {
                                 die("MAC is incorrect, fraud attempt!");
                            } 
            }
            
            
            $dibsInvoiceFields = array("acquirerLastName",          "acquirerFirstName",
                                       "acquirerDeliveryAddress",   "acquirerDeliveryPostalCode",
                                       "acquirerDeliveryPostalPlace" );
            $dibsInvoiceFieldsString = "";
            foreach($_POST as $key=>$value) {
              if(in_array($key, $dibsInvoiceFields)) {
                   $dibsInvoiceFieldsString .= "{$key}={$value}\n";              
              }
            }
            
           
            // Email is not send automatically on a success transactio page 
            // from version '3.8.9 so we send email on callback from this version 
            if( version_compare(get_option( 'wpsc_version' ), '3.8.9', '>=' ) ) {
                        
                    if( $_POST['status'] == "ACCEPTED" ) {    
                        $purchaselog = new WPSC_Purchase_Log($order_id);
                        $purchaselog->set('processed', get_option('dibspw_status'));
                        $purchaselog->set('notes', $dibsInvoiceFieldsString);
                        $purchaselog->set('transactid', $_POST['transaction']);
                        
                        $purchaselog ->save();
                        $wpscmerch = new wpsc_merchant($order_id, false);
                        $wpscmerch->set_purchase_processed_by_purchid(get_option('dibspw_status'));
                    }
          
              }else {
                   
                    if( $_POST['status'] == "ACCEPTED" ) {
                         $purchase_log = $wpdb->get_results("SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= ".$_POST['s_pid']." LIMIT 1", ARRAY_A);    
                         $wpdb->query("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '". get_option('dibspw_status') ."', `notes`='".$dibsInvoiceFieldsString."'  WHERE `id` = '" . $purchase_log[0]['id'] . "' LIMIT 1;");
                         
                         // If it is the second callback with status ACCEPTED 
                         // we want to send an email to customer.
                         if( $purchase_log[0]['authcode'] == "PENDING" ) {
                             transaction_results( $_POST['s_pid'],false);
                         }
                    } else {
                         // we save not successed statuses it can be PENDING status..
                         $wpdb->query("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '1' , `authcode` = '".$_POST['status']."'  WHERE `id` = '" . $purchase_log[0]['id'] . "' LIMIT 1;");
                    } 
              }
                    
            break;  
     
            case 'success':
                if(!isset($_GET['page_id']) || get_permalink($_GET['page_id']) != get_option('transact_url')) {
                    $location = add_query_arg('sessionid', $_POST['s_pid'], get_option('transact_url'));
                    if( $_POST['status'] == "ACCEPTED" ) {
                         
                         if( $hamc_key && !isset( $_POST['MAC'])) {
                             die("HMAC error!");
                         }     
                         
                         if($hamc_key && isset( $_POST['MAC']) && $_POST['MAC']) {
                            if( $_POST['MAC'] != dibspayment_paywin_calc_mac($_POST, $hamc_key, $bUrlDecode = FALSE)) {
                                 die("HMAC is incorrect, fraud attempt!");
                            } 
                         }
                         
                    } else {
                         // Declined or PENDING
                         $purchase_log = $wpdb->get_results("SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= ".$_POST['s_pid']." LIMIT 1", ARRAY_A);
                         $wpdb->query("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '1' , `authcode` = '".$_POST['status']."'  WHERE `id` = '" . $purchase_log[0]['id'] . "' LIMIT 1;");
                     }
                      wp_redirect($location);
                      exit();
                }
            break;

            case 'cancel':
                if (isset($_POST['orderid'])) {
                       $purchase_log = $wpdb->get_results("SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= ".$_POST['s_pid']." LIMIT 1", ARRAY_A);
                       $wpdb->query("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '".get_option('dibspw_statusc')."' WHERE `id` = '" . $purchase_log[0]['id'] . "' LIMIT 1;");
                       wp_redirect(get_option( 'shopping_cart_url' ));
                       exit();
                }
            break;
        }
    }
}

/**
 * Saving of module settings.
 * 
 * @return bool 
 */
function dibspayment_paywin_submit() {

    foreach($_POST as $key => $value) {
        if(false !== strpos($key,"dibspw_") && $key != 'dibspw_form') {
            update_option($key, isset($_POST[$key]) ? $_POST[$key] : "");
        }
    }
   
    if(isset($_POST['dibspw_capturenow'])) {
         update_option('dibspw_capturenow', $_POST['dibspw_capturenow']);
    }else {
         update_option('dibspw_capturenow', "");
    }
    
    if(isset($_POST['dibspw_fee'])) {
         update_option('dibspw_fee', $_POST['dibspw_fee']);
    }else {
         update_option('dibspw_fee', "");
    }
    
    if(isset($_POST['dibspw_testmode'])) {
         update_option('dibspw_testmode', $_POST['dibspw_testmode']);
    }else {
         update_option('dibspw_testmode', "");
    }
    
    if (!isset($_POST['dibspw_form'])) $_POST['dibspw_form'] = array();
    
     if(isset($_POST['dibspw_cardslogo'])) {
        update_option('dibspw_cardslogo', str_replace('\\', '', $_POST['dibspw_cardslogo']));
    }
    
    if(isset($_POST['dibspw_invoicelogo'])) {
        update_option('dibspw_invoicelogo', str_replace('\\', '', $_POST['dibspw_invoicelogo']));
    }
    
    if(isset($_POST['dibspw_netbanklogo'])) {
        update_option('dibspw_netbanklogo', str_replace('\\', '', $_POST['dibspw_netbanklogo']));
    }
   
    
    foreach((array)$_POST['dibspw_form'] as $key => $value) {
        update_option(('dibspw_form_' . $key), $value);
    }
    return true;
}

function dibspayment_paywin_order_params($cart) {
    global $wpdb;
    $order_data = array();  
 
    $purchase_log  = $wpdb->get_results("SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . 
                                        "` WHERE `sessionid`= ".$cart->sessionid." LIMIT 1", ARRAY_A);
    $currency_code = $wpdb->get_results("SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . 
                                        "` WHERE `id`='" . get_option('currency_type') . "' LIMIT 1", ARRAY_A);
    
    // Set status on new order 
    $wpdb->query("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '". get_option('dibspw_statusp') ."' WHERE `sessionid` = '" . $cart->sessionid . "' LIMIT 1;"); 
    
    // collect data for request 
    $order_data['orderid']  = $purchase_log[0]['id'];
    $order_data['merchant'] = get_option('dibspw_mid');
    $order_data['amount']   = dibspayment_paywin_round($cart->total_price);
    $order_data['currency'] = $currency_code[0]['code'];
    $order_data['language'] = get_option('dibspw_lang');
    $order_data['oitypes']  = 'QUANTITY;UNITCODE;DESCRIPTION;AMOUNT;ITEMID;VATAMOUNT';
    $order_data['oinames']  = 'Qty;UnitCode;Description;Amount;ItemId;VatAmount';
    $order_data['oinames']  = 'Qty;UnitCode;Description;Amount;ItemId;VatAmount';
    
    $wpec_taxes_c = new wpec_taxes_controller;
    //$tax_total = $wpec_taxes_c->wpec_taxes_calculate_total();
    
    //get the rate for the country and region if set
    $tax_total = $wpec_taxes_c->wpec_taxes->wpec_taxes_get_rate($wpec_taxes_c->wpec_taxes_retrieve_selected_country(), 
                                                               $wpec_taxes_c->wpec_taxes_retrieve_region());
       
 
    // cart items 
    $i=1;
    foreach($cart->cart_items as $oitem) {
        $tmp_price = dibspayment_paywin_round($oitem->unit_price);
           if(!empty($tmp_price)) {
                   $unit_price = $oitem->unit_price; 
                   $tax['tax'] = 0;
                   if($wpec_taxes_c->wpec_taxes->wpec_taxes_get_enabled() && $tax_total['rate']) {
                        if($wpec_taxes_c->wpec_taxes_isincluded()) {
                            $tax = $wpec_taxes_c->wpec_taxes_calculate_included_tax($oitem);
                            $tax['tax'] =  $tax['tax']/$oitem->quantity;
                            $unit_price = $oitem->unit_price - $tax['tax'];
                        } else {
                            $tax['tax'] =  $unit_price * ($tax_total['rate'] / 100);
                        }
                   }
                    $tmp_name = !empty($oitem->product_name) ?  $oitem->product_name : $oitem->sku;
                    if(empty($tmp_name)) $tmp_name = $oitem->product_id;
                    $order_data['oiRow' . $i++] =  dibspayment_paywin_oirow_str($oitem->quantity,  dibspayment_paywin_utf8Fix(str_replace(";","\;",$tmp_name)), 
                               dibspayment_paywin_round($unit_price), dibspayment_paywin_utf8Fix(str_replace(";","\;",$oitem->product_id)), dibspayment_paywin_round($tax['tax']));
                }
               unset($tmp_price, $tmp_name);
     }

   
       // Shipping calculation 
        if($cart->calculate_total_shipping()) {
           $shipping_tax = 0;
           $fRate = $cart->calculate_total_shipping();
           if( $tax_total['shipping'] && $wpec_taxes_c->wpec_taxes->wpec_taxes_get_enabled()) {
              if($wpec_taxes_c->wpec_taxes_isincluded()) {
                   $shipping_tax = $wpec_taxes_c->wpec_taxes_calculate_tax( $cart->calculate_total_shipping(), $tax_total['rate'], false);
                   $fRate = $fRate - $shipping_tax; 
               } else {
                   $shipping_tax = $wpec_taxes_c->wpec_taxes_calculate_tax( $cart->calculate_total_shipping(), $tax_total['rate']);
               }
           }
          
          $order_data['oiRow' . $i++] = dibspayment_paywin_oirow_str(1, "Shipping" , dibspayment_paywin_round($fRate) , "shipping_0", dibspayment_paywin_round($shipping_tax));
      }
      
      // Cupone if it is avaliable
      if($cart->coupons_amount > 0) {
         $order_data['oiRow'.$i++]=dibspayment_paywin_oirow_str(1, "Coupon", -dibspayment_paywin_round($cart->coupons_amount) , "coupon_0", 0);      
      }
       // Address fields here..
       $aAddr = $_POST['collected_data'];
     
       $order_data['shippingfirstname']  = $aAddr[get_option('dibspw_form_first_name_d')];
       $order_data['shippinglastname']   = $aAddr[get_option('dibspw_form_last_name_d')];
       $order_data['shippingpostalcode'] = $aAddr[get_option('dibspw_form_post_code_d')];
       $order_data['shippingpostalplace']= $aAddr[get_option('dibspw_form_city_d')];
       //$order_data['shippingaddress2']   = $aAddr[get_option('dibspw_form_address_d')];
       $order_data['shippingaddress']    = $aAddr[get_option('dibspw_form_country_d')] . " " .
                                    $aAddr[get_option('dibspw_form_state_d')];
            
       $order_data['billingfirstname']   = $aAddr[get_option('dibspw_form_first_name_b')];
       $order_data['billinglastname']    = $aAddr[get_option('dibspw_form_last_name_b')];
       $order_data['billingpostalcode']  = $aAddr[get_option('dibspw_form_post_code_b')];
       $order_data['billingpostalplace'] = $aAddr[get_option('dibspw_form_city_b')];
       $order_data['billingaddress']    = $aAddr[get_option('dibspw_form_address_b')];
       //$order_data['billingaddress']     = $aAddr[get_option('dibspw_form_country_b')]. " " . 
       //                             $aAddr[get_option('dibspw_form_state_b')];
            
       $order_data['billingmobile']      = $aAddr[get_option('dibspw_form_phone_b')];
       $order_data['billingemail']       = $aAddr[get_option('dibspw_form_email_b')];
       $order_data['acceptreturnurl']    = get_option('siteurl') . "/?dibspw_result=success";
       $order_data['cancelreturnurl']    = site_url() . "/?dibspw_result=cancel";
       $order_data['callbackurl']        = site_url() . "/?dibspw_result=callback";
       $order_data['s_callbackfix']      = get_option('siteurl') . "/?dibspw_result=callback";
       $order_data['s_sysmod']           = DIBS_SYSMOD;
            
        if(get_option('dibspw_testmode')) { 
             $order_data['test'] = 1;
        }
        if(get_option('dibspw_capturenow')) {            
              $order_data['capturenow'] = 1;
        }
        if(get_option('dibspw_fee')) {            
              $order_data['addfee'] = 1;
        }
        $order_data['s_pid'] = $cart->sessionid;
       
        if(get_option('dibspw_account')) {
          $order_data['account'] = get_option('dibspw_account');
        }
        if(get_option('dibspw_paytype')) {
          $order_data['paytype'] = get_option('dibspw_paytype');
        }
        if(get_option('dibspw_pid')) {
          $order_data['s_partnerid'] = get_option('dibspw_pid');
        }
        
        
        if($hmac = get_option('dibspw_hmac')) {
             $order_data['MAC'] = dibspayment_paywin_calc_mac($order_data, $hmac, $bUrlDecode = FALSE); 
        }
    return $order_data;       
}


function dibspayment_paywin_round($fNum, $iPrec = 2) {
        return empty($fNum) ? (int)0 : (int)(string)(round($fNum, $iPrec) * pow(10, $iPrec));
 }


function checkbox_checked($value) {
    if($value == 'yes') {
        return 'checked';
    }
}

function dibspayment_paywin_build_select($resource, $stored_value) {
    $out = "";
    $checked = "";
    foreach($resource as $key => $value) {
       echo  $checked;
       if($key == $stored_value) $checked = "selected = \"selected\"";
       $out .= "<option value=\"$key\" $checked> $value </option>";
       $checked = "";
    }
    return $out;
}

function get_statuses($status) {
   global $wpsc_purchlog_statuses;
   $statuses = array();
   foreach($wpsc_purchlog_statuses as $key=>$value) {
       $statuses[$value['order']] = $value['label'];
   }
   return dibspayment_paywin_build_select($statuses, $status); 
}

    
    /** 
     * Calculates MAC for given array of data.
     * 
     * @param array $adata
     * @param string $hmac
     * @param bool $url_decode
     * @return string 
     */
    function dibspayment_paywin_calc_mac($adata, $hmac, $url_decode = FALSE) {
        $mac = "";
        if(!empty($hmac)) {
            $sdata = "";
            if(isset($adata['MAC'])) unset($adata['MAC']);
            ksort($adata);
            foreach($adata as $key => $val) {
                $sdata .= "&" . $key . "=" . (($url_decode === TRUE) ? urldecode($val) : $val);
            }
            $mac = hash_hmac("sha256", ltrim($sdata, "&"), dibspayment_paywin_hextostr($hmac));
        }
        return $mac;
    }

   /**
     * Convert hex HMAC to string.
     * 
     * @param string $shex
     * @return string 
     */
    function dibspayment_paywin_hextostr($shex) {
        $sres = "";
        foreach(explode("\n", trim(chunk_split($shex,2))) as $h) $sres .= chr(hexdec($h));
        return $sres;
    }
    
      /**
     * Fixes UTF-8 special symbols if encoding of CMS is not UTF-8.
     * Main using is for wided latin alphabets.
     * 
     * @param string $s_text
     * @return string 
     */
   function dibspayment_paywin_utf8Fix($s_text) {
        return (mb_detect_encoding($s_text) == "UTF-8" && mb_check_encoding($s_text, "UTF-8")) ?
               $s_text : utf8_encode($s_text);
    }
    
    function dibspayment_paywin_oirow_str( $qty = 1, $item_name, $item_price, $item_id, $item_tax = 0 ) {
        return "$qty;pcs;$item_name;$item_price;$item_id;$item_tax"; 
    }
  
/**
 * Generating module settings form.
 * 
 * @return string 
 */
function dibspayment_paywin_form() {
    $lang = array(
        'da_DK'  => 'Danish',
        'en_GB'  => 'English (GB)',
        'nb_NO'  => 'Norwegian',
        'sv_SE'  => 'Swedish',
        'de_DE'  => 'German',
        'en_US'  => 'English (US)',
        'es_ES'  => 'Spanish',
        'fi_FI'  => 'Finnish',
        'fr_FR'  => 'French',
        'it_IT' => 'Italian',
        'nl_NL' => 'Dutch',
        'pl_PL' => 'Polish',
        'pt_PT' => 'Portuguese',
    );
    
    $platform = array('D2' =>'D2', 
                      'DX' => 'DX');
    $dibs_params = '<tr> 
      <td>DIBS Integration ID:</td>
            <td><input type="text" name="dibspw_mid" value="'.get_option('dibspw_mid').'" /></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Your merchant ID in DIBS system.
                </span>
            </td>
        </tr>
        <tr>
          <td>DIBS platform:</td>
            <td><select name="dibspw_platform">'.
                  dibspayment_paywin_build_select($platform, get_option('dibspw_platform')) . 
            '</select></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                     Which platform to use ?. You can detect your platform here: <a style="color:#2E6E9E" 
                     href="http://tech.dibspayment.com/platformfinder" target="_blank">http://tech.dibspayment.com/platformfinder </a>
             </span>
            </td>
        </tr>
       
        <tr>
          <td>Partner ID:</td>
            <td><input type="text" name="dibspw_pid" value="'.get_option('dibspw_pid').'" /></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Partner ID.
                </span>
            </td>
        </tr><tr>
          <td>HMAC:</td>
            <td><input type="text" name="dibspw_hmac" value="'.get_option('dibspw_hmac').'" /></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Key for transactions security.
                </span>
            </td>
        </tr><tr>
          <td>Test mode:</td>
            <td><input type="checkbox" name="dibspw_testmode" value="yes"'.checkbox_checked(get_option('dibspw_testmode')).'/></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Run transactions in test mode.
                </span>
            </td>
        </tr><tr>
          <td>Add fee:</td>
            <td><input type="checkbox" name="dibspw_fee" value="yes" '.checkbox_checked(get_option('dibspw_fee')).'/></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Customer pays fee.
                </span>
            </td>
        </tr>
        <tr>
          <td>Capture now:</td>
            <td><input type="checkbox" name="dibspw_capturenow" value="yes"'.checkbox_checked(get_option('dibspw_capturenow')).'/></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Make attempt to capture the transaction upon a successful authorization. (DIBS PW only)
                </span>
            </td>
        </tr>
        <tr>
          <td>Cards logo:</td>
          <td>
            <textarea type="checkbox" style="width:60%" name="dibspw_cardslogo" rows="4" cols="40">'.get_option('dibspw_cardslogo').'</textarea>
          </td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Please choose and confire logo on our site <a href="http://tech.dibspayment.com/logos#check-out-logos" target="_blank">http://tech.dibspayment.com/logos#check-out-logos</a>
                    and paste html markup for Card logo here.
                </span>
            </td>
        </tr>
        
        <tr>
          <td>Netbank logo:</td>
          <td>
            <textarea type="checkbox" style="width:60%" name="dibspw_netbanklogo" rows="4" cols="20">'.get_option('dibspw_netbanklogo').'</textarea>
          </td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                             Please choose and confire logo on our site <a href="http://tech.dibspayment.com/logos#check-out-logos" target="_blank">http://tech.dibspayment.com/logos#check-out-logos</a>
                    and paste html markup for Netbank logo here.
          
                </span>
            </td>
        </tr>

         <tr>
          <td>Invoice logo:</td>
          <td>
            <textarea type="checkbox" style="width:60%" name="dibspw_invoicelogo" rows="4" cols="20">'.get_option('dibspw_invoicelogo').'</textarea>
          </td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                              Please choose and confire logo on our site <a href="http://tech.dibspayment.com/logos#check-out-logos" target="_blank">http://tech.dibspayment.com/logos#check-out-logos</a>
                    and paste html markup for Invoice logo here.
          
                </span>
            </td>
        </tr>
        <tr>
          <td>Paytype:</td>
            <td><input type="text" name="dibspw_paytype" value="'.get_option('dibspw_paytype').'" /></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Paytypes available to customer (e.g.: VISA,MC)
                </span>
            </td>
        </tr><tr>
          <td>Language:</td>
            <td><select style="width: 108px;" name="dibspw_lang">'.
                  dibspayment_paywin_build_select($lang, get_option('dibspw_lang')) . 
            '</select></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Language of payment window interface.
                </span>
            </td>
        </tr><tr>
          <td>Account:</td>
            <td><input type="text" name="dibspw_account" value="'.get_option('dibspw_account').'" /></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Account id used to visually separate transactions in merchant admin.
                </span>
            </td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    
                </span>
            </td>
        </tr><tr>
          <td>Success payment status:</td>
            <td><select name="dibspw_status">'.get_statuses(get_option('dibspw_status')).'</select></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Order status after success transaction.
                </span>
            </td>
        </tr><tr>
          <td>Pending payment status:</td>
            <td><select name="dibspw_statusp">
              '.get_statuses(get_option('dibspw_statusp')).'
            </select></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Order status before payment.
                </span>
            </td>
        </tr><tr>
          <td>Cancel payment status:</td>
            <td><select name="dibspw_statusc">
                '.get_statuses(get_option('dibspw_statusc')).'
            </select></td>
        </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
                <span class="small description">
                    Order status on cancellation.
                </span>
            </td>
        </tr>';
    $address_fields = '<tr class="update_gateway" >
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
                     <a href="http://tech.dibspayment.com/starter_guide" target="_blank">here</a>.
                 </span>
             </td>
         </tr>';
    return $dibs_params . $address_fields;
}    


function dibspayment_paywin_checkout_form_dibspw($output) {
    
     $output = '';
     $output .=  get_option('dibspw_cardslogo')  .  
                 get_option('dibspw_netbanklogo').
                 get_option('dibspw_invoicelogo') ;
     
     if( $output ) {
         $output = '<tr><td>' .$output .'</td></tr>'; 
     }
     return $output;
    
 }
 
 function dibspayment_paywin_user_log_after_order_status( $purchase) {
   var_dump($purchase); 
   if(DIBS_PAYMENT_GATEWAT_CODE == $purchase['gateway']) {  
   $dibsTransDetails = '<strong>DIBS transaction details:</strong>'
     . '<table class="customer_details">  <tbody><tr>'
     . '<td colspan="2">Transaction details:</td></tr>  '
     . '<tr><td>Transactionid:</td><td>'.$purchase['transactid'].'</td></tr>  '
            . '<tr><td>Status:</td><td>ACCEPTED</td></tr> '
    . '</table>';
     
     echo $dibsTransDetails;
   }
 }

 
 function _dibspayment_customer_notification_raw_message($message, $notification ) {
     $purchase_log = $notification->get_purchase_log();
     if( $purchase_log->get('gateway') == DIBS_PAYMENT_GATEWAT_CODE) {
        $transactid = $purchase_log->get('transactid');
        $message = $message . "DIBS Transaction id = $transactid \n";
     }
     return $message;
 }
 
function dibspayment_paywin_purchlogitem_metabox_end($log_id) {
     $log = new WPSC_Purchase_Log( $log_id );
     $log_data_arr = $log->get_data();
     if( $log_data_arr['gateway'] ==  DIBS_PAYMENT_GATEWAT_CODE 
                && $log_data_arr['transactid']) {
        
        echo 'DIBS transactionid =' . $log_data_arr['transactid'];
     }
}

add_action('init', 'dibspayment_paywin_process');
add_filter('wpsc_gateway_checkout_form_dibspw', 'dibspayment_paywin_checkout_form_dibspw');
add_action('wpsc_purchlogitem_metabox_end', 'dibspayment_paywin_purchlogitem_metabox_end');
add_action( 'wpsc_user_log_after_order_status' , 'dibspayment_paywin_user_log_after_order_status');
add_filter(
	'wpsc_purchase_log_customer_notification_raw_message',
	'_dibspayment_customer_notification_raw_message',
	10,
	2
);