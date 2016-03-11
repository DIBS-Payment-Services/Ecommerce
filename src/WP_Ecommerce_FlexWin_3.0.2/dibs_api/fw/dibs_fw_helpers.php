<?php
class dibs_fw_helpers extends dibs_fw_helpers_cms implements dibs_fw_helpers_interface {

    public static $bTaxAmount = true;

    /**
     * Process write SQL query (insert, update, delete) with build-in CMS ADO engine.
     * 
     * @param string $sQuery 
     */
    public function helper_dibs_db_write($sQuery) {
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
    public function helper_dibs_db_read_single($sQuery, $sName) {
        global $wpdb;
        $mResult = $wpdb->get_results($sQuery);
        return isset($mResult[0]->$sName) ? $mResult[0]->$sName : null;
    }

    /**
     * Return settings with CMS method.
     * 
     * @param string $sVar
     * @param string $sPrefix
     * @return string 
     */
    public function helper_dibs_tools_conf($sVar, $sPrefix = 'dibsflex_') {
        return get_option($sPrefix . $sVar);
    }

    /**
     * Return CMS DB table prefix.
     * 
     * @return string 
     */
    public function helper_dibs_tools_prefix() {
        global $wpdb;
        return $wpdb->prefix;
    }

    /**
     * Returns text by key using CMS engine.
     * 
     * @param type $sKey
     * @return type 
     */
    public function helper_dibs_tools_lang($sKey, $sType = 'msg') {
        $sName = 'dibsflex_' . $sType . "_" . $sKey;
        return isset($this->aLang[$sName]) ? $this->aLang[$sName] : "";
    }

    /**
     * Get full CMS url for page.
     * 
     * @param string $sLink
     * @return string 
     */
    public function helper_dibs_tools_url($sLink) {
        return get_option('siteurl') . $sLink;
    }

    /**
     * Redirect with CMS method (used in CGI API methods)
     * 
     * @param string $sLink 
     */
    public function helper_dibs_tools_redirect($sLink) {
        wp_redirect($sLink);
    }
    
    /**
     * Build CMS order information to API object.
     * 
     * @param mixed $mOrderInfo
     * @param bool $bResponse
     * @return object 
     */
    public function helper_dibs_obj_order($mOrderInfo, $bResponse = FALSE) {
        return (object) array(
                    'orderid'  => $mOrderInfo['id'],
                    'amount'   => $mOrderInfo['totalprice'],
                    'currency' => dibs_fw_api::api_dibs_get_currencyValue($mOrderInfo['currency'])
        );
    }

    /**
     * Build CMS each ordered item information to API object.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    public function helper_dibs_obj_items($mOrderInfo) {
        $aItems = array();
        foreach($mOrderInfo['cart']->cart_items as $oItem) {
            $aTax = $mOrderInfo['taxes'][$oItem->product_id];
            $fPrice = isset($mOrderInfo['isincl']) && $mOrderInfo['isincl'] == 1 ? 
                      dibs_fw_helpers_cms::cms_dibs_getInclTax($oItem->unit_price, $aTax['rate']) :
                      $oItem->unit_price;

            $aItems[] = (object)array(
                        'id'    => $oItem->product_id,
                        'name'  => $oItem->product_name,
                        'sku'   => $oItem->sku,
                        'price' => $fPrice,
                        'qty'   => $oItem->quantity,
                        'tax'   => 0
            );
        }
        
        $aItems[] = (object)array(
            'id'    => 'tax0',
            'name'  => $this->helper_dibs_tools_lang('total_tax'),
            'sku'   => '',
            'price' => $mOrderInfo['total_tax'],
            'qty'   => 1,
            'tax'   => 0
        );
        
        return $aItems;
    }

    /**
     * Build CMS shipping information to API object.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    public function helper_dibs_obj_ship($mOrderInfo) {
        $aTax = $mOrderInfo['shipping']['tax'];
        $fRate = isset($mOrderInfo['isincl']) && $mOrderInfo['isincl'] == 1 ? 
                 dibs_fw_helpers_cms::cms_dibs_getInclTax($mOrderInfo['shipping']['rate'], $aTax['rate']) :
                 $mOrderInfo['shipping']['rate'];
        return (object) array(
                    'id'    => "shipping0",
                    'name'  => $this->helper_dibs_tools_lang('total_shipping'),
                    'sku'   => "",
                    'price' => $fRate,
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
    public function helper_dibs_obj_addr($mOrderInfo) {
        $aAddr = $mOrderInfo['user'];
        
        $addressInfo = array();
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_first_name_d')])) {
            $addressInfo['shippingfirstname'] = $aAddr[$this->helper_dibs_tools_conf('form_first_name_d')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_last_name_d')])) {
            $addressInfo['shippinglastname'] = $aAddr[$this->helper_dibs_tools_conf('form_last_name_d')];
        }
   
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_post_code_d')])) {
            $addressInfo['shippingpostalcode'] = $aAddr[$this->helper_dibs_tools_conf('form_post_code_d')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_city_d')])) {
            $addressInfo['shippingpostalplace'] = $aAddr[$this->helper_dibs_tools_conf('form_city_d')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_address_d')])) {
            $addressInfo['shippingaddress2'] = $aAddr[$this->helper_dibs_tools_conf('form_address_d')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_address_d')])) {
            $addressInfo['shippingaddress'] = $aAddr[$this->helper_dibs_tools_conf('form_address_d')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_first_name_b')])) {
             $addressInfo['billingfirstname'] = $aAddr[$this->helper_dibs_tools_conf('form_first_name_b')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_last_name_b')])) {
             $addressInfo['billinglastname'] = $aAddr[$this->helper_dibs_tools_conf('form_last_name_b')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_post_code_b')])) {
             $addressInfo['billingpostalcode'] = $aAddr[$this->helper_dibs_tools_conf('form_post_code_b')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_city_b')])) {
             $addressInfo['billingpostalplace'] = $aAddr[$this->helper_dibs_tools_conf('form_city_b')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_address_b')])) {
             $addressInfo['billingaddress'] = $aAddr[$this->helper_dibs_tools_conf('form_address_b')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_address_b')])) {
             $addressInfo['billingaddress2'] = $aAddr[$this->helper_dibs_tools_conf('form_address_b')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_phone_b')])) {
             $addressInfo['billingmobile'] = $aAddr[$this->helper_dibs_tools_conf('form_phone_b')];
        }
        
        if( isset($aAddr[$this->helper_dibs_tools_conf('form_email_b')])) {
             $addressInfo['billingemail'] = $aAddr[$this->helper_dibs_tools_conf('form_email_b')];
        }
        
        return (object) $addressInfo;
    }

    /**
     * Returns object with URLs needed for API, 
     * e.g.: callbackurl, acceptreturnurl, etc.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    public function helper_dibs_obj_urls($mOrderInfo = null) {
        return (object) array(
            'acceptreturnurl' => '/?dibsflex_success=true',
            'callbackurl'     => '/?dibsflex_callback=true',
            'cancelreturnurl' => '/?dibsflex_cancel=true',
            'cgiurl'          => '/?dibsflex_cgi=true',
            'carturl'         => '/'
        );
    }

    /**
     * Returns object with additional information to send with payment.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    public function helper_dibs_obj_etc($mOrderInfo) {
        return (object) array(
            'sysmod'      => 'wp3e_3_0_2',
            'callbackfix' => $this->helper_dibs_tools_url('/?dibsflex_callback=true'),
            'pid'         => $mOrderInfo['additional']['pid'],
        );
    }

    public function helper_dibs_hook_callback($oOrder) {
        if(isset($_POST['realorderid']) && $_POST['realorderid'] ) {
                $orderid = $_POST['realorderid'];
                /*$comment = "Callback was received form DIBS, "
                         . "transaction={$_POST['transact']}, orderid={$orderid}";*/
                $purchase_log = new WPSC_Purchase_Log($orderid);
                $purchase_log->set('processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT);
                $purchase_log->set('transactid',  $_POST['transact']);
                //$purchase_log->set('notes', $comment);
                $purchase_log->save();
            }
    }
}