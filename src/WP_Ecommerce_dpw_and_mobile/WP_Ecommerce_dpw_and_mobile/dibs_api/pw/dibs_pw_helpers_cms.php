<?php
class dibs_pw_helpers_cms extends wpsc_merchant{
    protected $aLang = array(
        'txt_err_fatal'    => 'A fatal error has occured.', 
        'txt_msg_toshop'   => 'Return to shop', 
        'txt_err_2'        => 'Unknown orderid was returned from DIBS payment gateway.', 
        'txt_err_1'        => 'No orderid was returned from DIBS payment gateway.', 
        'txt_err_4'        => 'The amount received from DIBS payment gateway 
                                 differs from original order amount.', 
        'txt_err_3'        => 'No amount was returned from DIBS payment gateway.', 
        'txt_err_6'        => 'The currency type received from DIBS payment gateway 
                                 differs from original order currency type.',
        'txt_err_5'        => 'No currency type was returned from DIBS payment 
                                 gateway.', 
        'txt_err_7'        => 'The fingerprint key does not match.', 
    );
    
    function cms_dibs_getInclusiveTax($mPrice, $mRate) {
        return $mPrice / (1 + $mRate / 100);
    }
    
    function cms_dibs_getOrderById($sSid) {
        global $wpdb;
        
        $aPurchaseLog = $wpdb->get_results("SELECT * 
                                            FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` 
                                            WHERE `sessionid`= ".$sSid." 
                                            LIMIT 1", ARRAY_A);
        
        if(isset($aPurchaseLog[0])) {
            $aOrder = $aPurchaseLog[0];
            $aOrder['currency'] = $this->cms_dibs_getCurrency();
            return $aOrder;
        }
        else return null;
    }
    
    function cms_dibs_getCurrency() {
        global $wpdb;
        $aCurrencyCode = $wpdb->get_results("SELECT `code` 
                                         FROM `" . WPSC_TABLE_CURRENCY_LIST . "` 
                                         WHERE `id`='" . get_option('currency_type') . "' 
                                         LIMIT 1", ARRAY_A);
        return $aCurrencyCode[0]['code'];
    }
    
    function cms_dibs_getFees() {
        global $wpsc_cart;
        
        $wpec_taxes_c = new wpec_taxes_controller;
        $aProdFees['shipping']['rate'] = $wpsc_cart->base_shipping;
    
        if($wpec_taxes_c->wpec_taxes->wpec_taxes_get_enabled() && $wpec_taxes_c->wpec_taxes_run_logic()) {
            $wpec_selected_country = $wpec_taxes_c->wpec_taxes_retrieve_selected_country();
            $region = $wpec_taxes_c->wpec_taxes_retrieve_region();
            $tax_rate = $wpec_taxes_c->wpec_taxes->wpec_taxes_get_rate( $wpec_selected_country, $region );
            $bTaxesIncl = $wpec_taxes_c->wpec_taxes_isincluded();

            foreach($wpsc_cart->cart_items as $cart_item) {
                $taxes = $bTaxesIncl ? $wpec_taxes_c->wpec_taxes_calculate_included_tax($cart_item) : 
                                       $wpec_taxes_c->wpec_taxes_calculate_excluded_tax($cart_item, $tax_rate);
                
                $aProdFees['items'][$cart_item->product_id]['rate'] = $taxes['rate'];
                $aProdFees['items'][$cart_item->product_id]['incl'] = $bTaxesIncl ? 1 : 0;
            }
        
            $free_shipping = false;
            if(isset($_SESSION['coupon_numbers'])) {
                $coupon = new wpsc_coupons($_SESSION['coupon_numbers']);
                $free_shipping = $coupon->is_percentage == '2';
            }
        
            if($tax_rate['shipping'] && !$free_shipping) {
                $aProdFees['shipping']['tax']['rate'] = $tax_rate['rate'];
                $aProdFees['shipping']['tax']['incl'] = $bTaxesIncl ? 1 : 0;
            }
            else {
                $aProdFees['shipping']['tax']['rate'] = '0';
                $aProdFees['shipping']['tax']['incl'] = '0';
            }
        }
        else {
            foreach($wpsc_cart->cart_items as $cart_item) {
                $aProdFees['items'][$cart_item->product_id]['rate'] = "0";
                $aProdFees['items'][$cart_item->product_id]['incl'] = "0";
            }
        
            $aProdFees['shipping']['tax']['rate'] = '0';
            $aProdFees['shipping']['tax']['incl'] = '0';
        }
        
        return $aProdFees;
    }
}
?>