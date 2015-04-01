<?php
class dibs_fw_helpers_cms {

    protected $aLang = array(
        'dibsflex_msg_toshop' => 'Return to shop',
        'dibsflex_msg_todibs' => 'Securely proceed with DIBS ->',
        'dibsflex_msg_redir_toshop' => 'If your browser didn\'t redirect you to shop automatically, please click the button bellow:',
        'dibsflex_msg_redirtitle_toshop' => 'Redirecting to shop...',
        'dibsflex_msg_redir_todibs' => 'If your browser didn\'t redirect you to DIBS automatically, please click the button bellow:',
        'dibsflex_msg_redirtitle_todibs' => 'Redirecting to DIBS...',
        'dibsflex_msg_errcode' => "Error code:",
        'dibsflex_msg_errmsg' => "Error message:",
        'dibsflex_err_0' => "Error has occurred during payment verification",
        'dibsflex_err_2' => "Unknown orderid was returned from DIBS payment gateway.",
        'dibsflex_err_1' => "No orderid was returned from DIBS payment gateway.",
        'dibsflex_err_4' => "The amount received from DIBS payment gateway 
                                                          differs from original order amount.",
        'dibsflex_err_3' => "No amount was returned from DIBS payment gateway.",
        'dibsflex_err_6' => "The currency type received from DIBS payment gateway 
                                                     differs from original order currency type.",
        'dibsflex_err_5' => "No currency type was returned from DIBS payment 
                                                      gateway.",
        'dibsflex_err_7' => "The fingerprint key does not match.",
        'dibsflex_err_8' => "No curl or socket connection available with your PHP configuration.",
        'dibsflex_err_9' => "Empty API credentials.",
        'dibsflex_err_10' => "Transaction ID is empty.",
        'dibsflex_err_11' => "Incorrect API credentials. Please, add correct and try again in 30 minutes.",
        'dibsflex_err_12' => "Error during CGI API request. Can't receive order status.",
        'dibsflex_msg_button_capture' => 'Capture',
        'dibsflex_msg_button_cancel' => 'Cancel',
        'dibsflex_msg_button_refund' => 'Refund',
        'dibsflex_lbl_cgistatus' => 'Status:',
        'dibsflex_lbl_cgiactions' => 'Actions:',
        'dibsflex_lbl_controls_title' => 'DIBS Controls:',
        'dibsflex_sts_0' => 'Transaction inserted (not approved)',
        'dibsflex_sts_1' => 'Declined',
        'dibsflex_sts_2' => 'Authorization approved',
        'dibsflex_sts_3' => 'Capture sent to acquirer',
        'dibsflex_sts_4' => 'Capture declined by acquirer',
        'dibsflex_sts_5' => 'Capture completed',
        'dibsflex_sts_6' => 'Canceled',
        'dibsflex_sts_9' => 'Refund shipped',
        'dibsflex_sts_10' => 'Refund rejected',
        'dibsflex_sts_11' => 'Refund approved',
        'dibsflex_msg_total_tax'      => 'Tax Total',
        'dibsflex_msg_total_shipping' => 'Shipping Total'
    );

    public static function cms_dibs_getInclTax($mPrice, $mRate) {
        return $mPrice - $mRate;
    }

    public function cms_dibs_getOrderById($sSid) {
        global $wpdb;

        $aPurchaseLog = $wpdb->get_results("SELECT * 
                                            FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` 
                                            WHERE `sessionid`= " . $sSid . " 
                                            LIMIT 1", ARRAY_A);

        if(isset($aPurchaseLog[0])) {
            $aOrder = $aPurchaseLog[0];
            $aOrder['currency'] = $this->cms_dibs_getCurrency();
            return $aOrder;
        }
        else
            return null;
    }

    public function cms_dibs_getCurrency() {
        global $wpdb;
        $aCurrencyCode = $wpdb->get_results("SELECT `code` 
                                         FROM `" . WPSC_TABLE_CURRENCY_LIST . "` 
                                         WHERE `id`='" . get_option('currency_type') . "' 
                                         LIMIT 1", ARRAY_A);
        return $aCurrencyCode[0]['code'];
    }

    public function cms_dibs_getFees() {
        global $wpsc_cart;

        $aFees = array();
        $wpec_taxes_c = new wpec_taxes_controller;
        $aFees['shipping']['rate'] = $wpsc_cart->base_shipping + $wpsc_cart->total_item_shipping;

        if($wpec_taxes_c->wpec_taxes->wpec_taxes_get_enabled() && $wpec_taxes_c->wpec_taxes_run_logic()) {
            $wpec_selected_country = $wpec_taxes_c->wpec_taxes_retrieve_selected_country();
            $region = $wpec_taxes_c->wpec_taxes_retrieve_region();
            $tax_rate = $wpec_taxes_c->wpec_taxes->wpec_taxes_get_rate($wpec_selected_country, $region);
            $aFees['incl'] = $wpec_taxes_c->wpec_taxes_isincluded();

            foreach($wpsc_cart->cart_items as $cart_item) {
                $taxes = $aFees['incl'] ? $wpec_taxes_c->wpec_taxes_calculate_included_tax($cart_item) :
                        $wpec_taxes_c->wpec_taxes_calculate_excluded_tax($cart_item, $tax_rate);

                $aFees['items'][$cart_item->product_id]['rate'] = $taxes['tax'];
            }

            $free_shipping = false;
            if(isset($_SESSION['coupon_numbers'])) {
                $coupon = new wpsc_coupons($_SESSION['coupon_numbers']);
                $free_shipping = $coupon->is_percentage == '2';
            }

            $aFees['shipping']['tax']['rate'] = ($tax_rate['shipping'] && !$free_shipping) ?
                $wpec_taxes_c->wpec_taxes_calculate_tax($aFees['shipping']['rate'], 
                                                        $tax_rate['rate'], !$aFees['incl']) : 0;
        }
        else {
            foreach($wpsc_cart->cart_items as $oItem) $aFees['items'][$oItem->product_id]['rate'] = '0';
            $aFees['shipping']['tax']['rate'] = '0';
        }

        return $aFees;
    }
}
?>