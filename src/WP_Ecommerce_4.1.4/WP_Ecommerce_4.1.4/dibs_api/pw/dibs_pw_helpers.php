<?php
class dibs_pw_helpers extends dibs_pw_helpers_cms implements dibs_pw_helpers_interface {

    public static $bTaxAmount = true;
    
    /**
     * Process write SQL query (insert, update, delete) with build-in CMS ADO engine.
     * 
     * @param string $sQuery 
     */
    function helper_dibs_db_write($sQuery) {
        global $wpdb;
        return $wpdb->query($sQuery);
    }
    
    /**
     * Read single value ($sName) from SQL select result.
     * If result with name $sName not found null returned.
     * 
     * @param string $sQuery
     * @param string $sName
     * @return mixed 
     */
    function helper_dibs_db_read_single($sQuery, $sName) {
        global $wpdb;
        
        $mResult = $wpdb->get_results($sQuery);
        if(isset($mResult[0]->$sName)) return $mResult[0]->$sName;
        else return null;
    }
    
    /**
     * Return settings with CMS method.
     * 
     * @param string $sVar
     * @param string $sPrefix
     * @return string 
     */
    function helper_dibs_tools_conf($sVar, $sPrefix = 'dibspw_') {
        return get_option($sPrefix . $sVar);
    }
    
    /**
     * Return CMS DB table prefix.
     * 
     * @return string 
     */
    function helper_dibs_tools_prefix() {
        global $wpdb;
        return $wpdb->prefix;
    }
    
    /**
     * Returns text by key using CMS engine.
     * 
     * @param type $sKey
     * @return type 
     */
    function helper_dibs_tools_lang($sKey, $sType = 'msg') {
        $sName = 'txt_' . $sType . "_" . $sKey;
        return isset($this->aLang[$sName]) ? $this->aLang[$sName] : "";
    }

    /**
     * Get full CMS url for page.
     * 
     * @param string $sLink
     * @return string 
     */
    function helper_dibs_tools_url($sLink) {
        return get_option('siteurl') . $sLink;
    }
    
    /**
     * Build CMS order information to API object.
     * 
     * @param mixed $mOrderInfo
     * @param bool $bResponse
     * @return object 
     */
    function helper_dibs_obj_order($mOrderInfo, $bResponse = FALSE) {
        return (object)array(
            'orderid'  => $mOrderInfo['id'],
            'amount'   => $mOrderInfo['totalprice'],
            'currency' => dibs_pw_api::api_dibs_get_currencyValue($mOrderInfo['currency'])
        );
    }
    
    /**
     * Build CMS each ordered item information to API object.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_items($mOrderInfo) {
        $aItems = array();
        
        $total = 0;
        foreach($mOrderInfo['cart']->cart_items as $oItem) {

            $aTax = $mOrderInfo['taxes'][$oItem->product_id];
            if(isset($oItem->meta[0]['wpec_taxes_taxable_amount']) && 
                              !empty($oItem->meta[0]['wpec_taxes_taxable_amount']) &&
                              $oItem->meta[0]['wpec_taxes_taxable_amount'] != 0.00) {
                $sTaxableAmount = $aTax['incl'] == 1 ? 
                                  $this->cms_dibs_getInclusiveTax($oItem->meta[0]['wpec_taxes_taxable_amount'], 
                                  $aTax['rate']) : $oItem->meta[0]['wpec_taxes_taxable_amount'];
                $fPrice = $oItem->unit_price + ($sTaxableAmount - $oItem->meta[0]['wpec_taxes_taxable_amount']);
            }
            else {
                $fPrice = $aTax['incl'] == 1 ? $this->cms_dibs_getInclusiveTax($oItem->unit_price, $aTax['rate']) : 
                                               $oItem->unit_price;
                $sTaxableAmount = $fPrice;
            }
            
            $aItems[] = (object)array(
                'id'    => $oItem->product_id,
                'name'  => $oItem->product_name,
                'sku'   => $oItem->sku,
                'price' => $fPrice,
                'qty'   => $oItem->quantity,
                'tax'   => (dibs_pw_api::api_dibs_round($aTax['rate']) *
                            dibs_pw_api::api_dibs_round($sTaxableAmount)) / 1000000
            );
            
            $total += $fPrice * $oItem->quantity;
            
        }
        
        
        
        return $aItems;
    }
    
    /**
     * Build CMS shipping information to API object.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_ship($mOrderInfo) {
        $aTax = $mOrderInfo['shipping']['tax'];
        $fRate = $aTax['incl'] == 1 ? $mOrderInfo['shipping']['rate'] / (1 + $aTax['rate'] / 100) : 
        $mOrderInfo['shipping']['rate'];
        if( $shipPerItem =  $mOrderInfo['cart']->total_item_shipping )
           $fRate += $shipPerItem;
        return (object)array(
            'id'    => "shipping0",
            'name'  => "Shipping Cost",
            'sku'   => "",
            'price' => $fRate,
            'qty'   => 1,
            'tax'   => (dibs_pw_api::api_dibs_round($fRate) * 
                        dibs_pw_api::api_dibs_round($aTax['rate'])) / 1000000
        );
    }
    
    function helper_dibs_obj_discount($mOrderInfo) {
         return (object)array(
            'id'    => "discount0",
            'name'  => "Discount",
            'sku'   => "",
            'price' => -$mOrderInfo['cart']->coupons_amount,
            'qty'   => 1,
            'tax'   => 0
        );
    }
    
    /**
     * Build CMS customer addresses to API object.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_addr($mOrderInfo) {
        $aAddr = $mOrderInfo['user'];
        
        return (object)array(
            'shippingfirstname'  => $aAddr[$this->helper_dibs_tools_conf('form_first_name_d')],
            'shippinglastname'   => $aAddr[$this->helper_dibs_tools_conf('form_last_name_d')],
            'shippingpostalcode' => $aAddr[$this->helper_dibs_tools_conf('form_post_code_d')],
            'shippingpostalplace'=> $aAddr[$this->helper_dibs_tools_conf('form_city_d')],
            'shippingaddress2'   => $aAddr[$this->helper_dibs_tools_conf('form_address_d')],
            'shippingaddress'    => $aAddr[$this->helper_dibs_tools_conf('form_country_d')][0] . " " . 
                                    $aAddr[$this->helper_dibs_tools_conf('form_state_d')],
            
            'billingfirstname'   => $aAddr[$this->helper_dibs_tools_conf('form_first_name_b')],
            'billinglastname'    => $aAddr[$this->helper_dibs_tools_conf('form_last_name_b')],
            'billingpostalcode'  => $aAddr[$this->helper_dibs_tools_conf('form_post_code_b')],
            'billingpostalplace' => $aAddr[$this->helper_dibs_tools_conf('form_city_b')],
            'billingaddress2'    => $aAddr[$this->helper_dibs_tools_conf('form_address_b')],
            'billingaddress'     => $aAddr[$this->helper_dibs_tools_conf('form_country_b')][0] . " " . 
                                    $aAddr[$this->helper_dibs_tools_conf('form_state_b')],
            
            'billingmobile'      => $aAddr[$this->helper_dibs_tools_conf('form_phone_b')],
            'billingemail'       => $aAddr[$this->helper_dibs_tools_conf('form_email_b')]
        );
    }
    
    /**
     * Returns object with URLs needed for API, 
     * e.g.: callbackurl, acceptreturnurl, etc.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_urls($mOrderInfo = null) {
        return (object)array(
                    'acceptreturnurl' => "/?dibspw_result=success",
                    'callbackurl'     => "/?dibspw_result=callback",
                    'cancelreturnurl' => "/?dibspw_result=cancel",
                    'carturl'         => "/"
                );
    }
    
    /**
     * Returns object with additional information to send with payment.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    function helper_dibs_obj_etc($mOrderInfo) {
        return (object)array(
                    'sysmod'      => 'wp3e_4_1_4',
                    'callbackfix' => $this->helper_dibs_tools_url("/?dibspw_result=callback"),
                    'pid'         => $mOrderInfo['additional']['pid'],
        );
    }
    
    function helper_dibs_hook_callback($oOrder) {
        transaction_results($oOrder->sessionid, false, $_POST['transaction']);
    }
}
?>