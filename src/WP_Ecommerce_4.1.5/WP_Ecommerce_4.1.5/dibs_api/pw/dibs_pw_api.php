<?php
require_once dirname(__FILE__) . '/dibs_pw_helpers_interface.php';
require_once dirname(__FILE__) . '/dibs_pw_helpers_cms.php';
require_once dirname(__FILE__) . '/dibs_pw_helpers.php';

class dibs_pw_api extends dibs_pw_helpers {

    private static $sDibsTable   = 'dibs_pw_results';
    
    private static $sDefaultCurr = array(0 => 'EUR', 1 => '978');

    private static $aFormActions =  'https://sat1.dibspayment.com/dibspaymentwindow/entrypoint';
    
    private static $aRespFields  = array('orderid' => 'orderid', 'status' => 'status',
                                         'testmode' => 'test', 'transaction' => 'transaction', 
                                         'amount' => 'amount','currency' => 'currency',
                                         'fee' => 'fee', 'voucheramount' => 'voucherAmount',
                                         'paytype' => array(2 => 'cardTypeName', 3 => 'payTypeName'),
                                         'amountoriginal'=>'amountOriginal',
                                         'validationerrors'=>'validationErrors',
                                         'capturestatus' => 'captureStatus', 
                                         'actioncode' => array(2 => 'actionCode', 3 => 'approvalCode'), 
                                         'sysmod' => 's_sysmod');
    
    private static $aCurrency = array('ADP' => '020','AED' => '784','AFA' => '004','ALL' => '008',
                                      'AMD' => '051','ANG' => '532','AOA' => '973','ARS' => '032',
                                      'AUD' => '036','AWG' => '533','AZM' => '031','BAM' => '977',
                                      'BBD' => '052','BDT' => '050','BGL' => '100','BGN' => '975',
                                      'BHD' => '048','BIF' => '108','BMD' => '060','BND' => '096',
                                      'BOB' => '068','BOV' => '984','BRL' => '986','BSD' => '044',
                                      'BTN' => '064','BWP' => '072','BYR' => '974','BZD' => '084',
                                      'CAD' => '124','CDF' => '976','CHF' => '756','CLF' => '990',
                                      'CLP' => '152','CNY' => '156','COP' => '170','CRC' => '188',
                                      'CUP' => '192','CVE' => '132','CYP' => '196','CZK' => '203',
                                      'DJF' => '262','DKK' => '208','DOP' => '214','DZD' => '012',
                                      'ECS' => '218','ECV' => '983','EEK' => '233','EGP' => '818',
                                      'ERN' => '232','ETB' => '230','EUR' => '978','FJD' => '242',
                                      'FKP' => '238','GBP' => '826','GEL' => '981','GHC' => '288',
                                      'GIP' => '292','GMD' => '270','GNF' => '324','GTQ' => '320',
                                      'GWP' => '624','GYD' => '328','HKD' => '344','HNL' => '340',
                                      'HRK' => '191','HTG' => '332','HUF' => '348','IDR' => '360',
                                      'ILS' => '376','INR' => '356','IQD' => '368','IRR' => '364',
                                      'ISK' => '352','JMD' => '388','JOD' => '400','JPY' => '392',
                                      'KES' => '404','KGS' => '417','KHR' => '116','KMF' => '174',
                                      'KPW' => '408','KRW' => '410','KWD' => '414','KYD' => '136',
                                      'KZT' => '398','LAK' => '418','LBP' => '422','LKR' => '144',
                                      'LRD' => '430','LSL' => '426','LTL' => '440','LVL' => '428',
                                      'LYD' => '434','MAD' => '504','MDL' => '498','MGF' => '450',
                                      'MKD' => '807','MMK' => '104','MNT' => '496','MOP' => '446',
                                      'MRO' => '478','MTL' => '470','MUR' => '480','MVR' => '462',
                                      'MWK' => '454','MXN' => '484','MXV' => '979','MYR' => '458',
                                      'MZM' => '508','NAD' => '516','NGN' => '566','NIO' => '558',
                                      'NOK' => '578','NPR' => '524','NZD' => '554','OMR' => '512',
                                      'PAB' => '590','PEN' => '604','PGK' => '598','PHP' => '608',
                                      'PKR' => '586','PLN' => '985','PYG' => '600','QAR' => '634',
                                      'ROL' => '642','RUB' => '643','RUR' => '810','RWF' => '646',
                                      'SAR' => '682','SBD' => '090','SCR' => '690','SDD' => '736',
                                      'SEK' => '752','SGD' => '702','SHP' => '654','SIT' => '705',
                                      'SKK' => '703','SLL' => '694','SOS' => '706','SRG' => '740',
                                      'STD' => '678','SVC' => '222','SYP' => '760','SZL' => '748',
                                      'THB' => '764','TJS' => '972','TMM' => '795','TND' => '788',
                                      'TOP' => '776','TPE' => '626','TRL' => '792','TRY' => '949',
                                      'TTD' => '780','TWD' => '901','TZS' => '834','UAH' => '980',
                                      'UGX' => '800','USD' => '840','UYU' => '858','UZS' => '860',
                                      'VEB' => '862','VND' => '704','VUV' => '548','XAF' => '950',
                                      'XCD' => '951','XOF' => '952','XPF' => '953','YER' => '886',
                                      'YUM' => '891','ZAR' => '710','ZMK' => '894','ZWD' => '716');
    
    /**
     * Returns CMS order common information converted to standardized order information objects.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    private function api_dibs_commonOrderObject($mOrderInfo) {
        return (object)array(
            'order' => $this->helper_dibs_obj_order($mOrderInfo),
            'urls'  => $this->helper_dibs_obj_urls($mOrderInfo),
            'etc'   => $this->helper_dibs_obj_etc($mOrderInfo)
        );
    }

    /**
     * Returns CMS order invoice information converted to standardized order information objects.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    private function api_dibs_invoiceOrderObject($mOrderInfo) {
        return (object)array(
            'items' => $this->helper_dibs_obj_items($mOrderInfo),
            'ship'  => $this->helper_dibs_obj_ship($mOrderInfo),
            'addr'  => $this->helper_dibs_obj_addr($mOrderInfo),
            'discount' => $this->helper_dibs_obj_discount($mOrderInfo)
        );
    }


    /**
     * Returns form action URL of gateway.
     * 
     * @param bool $bResponse
     * @return string 
     */
    final public function api_dibs_get_formAction($bResponse = FALSE) {
        return self::$aFormActions;
    }
    
    /**
     * Collects API parameters to send in dependence of checkout type. API entry point.
     * 
     * @param mixed $mOrderInfo
     * @return array 
     */
    final public function api_dibs_get_requestFields($mOrderInfo) {
        $oOrder = $this->api_dibs_commonOrderObject($mOrderInfo);
        $this->api_dibs_prepareDB($oOrder->order->orderid);
        
        $this->api_dibs_commonFields($aData, $oOrder);
        $this->api_dibs_invoiceFields($aData, $mOrderInfo);
        if(count($oOrder->etc) > 0) {
            foreach($oOrder->etc as $sKey => $sVal) $aData['s_' . $sKey] = $sVal;
        }
        array_walk($aData, create_function('&$val', '$val = trim($val);'));
        $sMAC = $this->api_dibs_calcMAC($aData, $this->helper_dibs_tools_conf('hmac'));
        if(!empty($sMAC)) $aData['MAC'] = $sMAC;
        
        return $aData;
    }
    
    /**
     * Adds to $aData common DIBS integration parameters.
     * 
     * @param array $aData
     * @param object $oOrder 
     */
    private function api_dibs_commonFields(&$aData, $oOrder) {
        $aData['orderid']  = $oOrder->order->orderid;
        $aData['merchant'] = $this->helper_dibs_tools_conf('mid');
        $aData['amount']   = self::api_dibs_round($oOrder->order->amount);
        $aData['currency'] = $oOrder->order->currency;
        $aData['language'] = $this->helper_dibs_tools_conf('lang');
        if((string)$this->helper_dibs_tools_conf('fee') == 'yes') $aData['addfee'] = 1;
        if((string)$this->helper_dibs_tools_conf('testmode') == 'yes') $aData['test'] = 1;
        $sPaytype = $this->helper_dibs_tools_conf('paytype');
        if(!empty($sPaytype)) $aData['paytype'] = $sPaytype;
        $sAccount = $this->helper_dibs_tools_conf('account');
        if(!empty($sAccount)) $aData['account'] = $sAccount;
        $aData['acceptreturnurl'] = $this->helper_dibs_tools_url($oOrder->urls->acceptreturnurl);
        $aData['cancelreturnurl'] = $this->helper_dibs_tools_url($oOrder->urls->cancelreturnurl);
        $aData['callbackurl']     =  $oOrder->urls->callbackurl;
        if(strpos($aData['callbackurl'], '/5c65f1600b8_dcbf.php') === FALSE) {
            $aData['callbackurl'] = $this->helper_dibs_tools_url($aData['callbackurl']);
        }
    }

    
    /**
     * Adds Invoice API parameters specific for SAT PW.
     * 
     * @param array $aData
     * @param object $oOrder 
     */
    private function api_dibs_invoiceFields(&$aData, $mOrderInfo) {
        $oOrder = $this->api_dibs_invoiceOrderObject($mOrderInfo);
        foreach($oOrder->addr as $sKey => $sVal) {
            $sVal = trim($sVal);
            if(!empty($sVal)) $aData[$sKey] = self::api_dibs_utf8Fix($sVal);
        }
        $oOrder->items[] = $oOrder->ship;
        $oOrder->items[] = $oOrder->discount;
        
        if(isset($oOrder->items) && count($oOrder->items) > 0) {
            $aData['oitypes'] = 'QUANTITY;UNITCODE;DESCRIPTION;AMOUNT;ITEMID;' .
                                (self::$bTaxAmount ? 'VATAMOUNT' : 'VATPERCENT');
            $aData['oinames'] = 'Qty;UnitCode;Description;Amount;ItemId;' .
                                (self::$bTaxAmount ? 'VatAmount' : 'VatPercent');
           
            $i = 1;
            foreach($oOrder->items as $oItem) {
                $iTmpPrice = self::api_dibs_round($oItem->price);
                if(!empty($iTmpPrice)) {
                    $sTmpName = !empty($oItem->name) ? $oItem->name : $oItem->sku;
                    if(empty($sTmpName)) $sTmpName = $oItem->id;

                    $aData['oiRow' . $i++] = 
                        self::api_dibs_round($oItem->qty, 3) / 1000 . ";" . 
                        "pcs" . ";" . 
                        self::api_dibs_utf8Fix(str_replace(";","\;",$sTmpName)) . ";" .
                        $iTmpPrice . ";" .
                        self::api_dibs_utf8Fix(str_replace(";","\;",$oItem->id)) . ";" .
                        self::api_dibs_round($oItem->tax);
                }
                unset($iTmpPrice, $sTmpName);
            }
 	}
        if(!empty($aData['orderid'])) $aData['yourRef'] = $aData['orderid'];
        if((string)$this->helper_dibs_tools_conf('capturenow') == 'yes') $aData['capturenow'] = 1;
        $sDistribType = $this->helper_dibs_tools_conf('distr');
        if((string)$sDistribType != 'empty') $aData['distributiontype'] = strtoupper($sDistribType);
    }
    
    /**
     * Process DB preparations and adds empty transaction record before payment.
     * 
     * @param int $iOrderId 
     */
    private function api_dibs_prepareDB($iOrderId) {
        $this->api_dibs_checkTable();
        $sQuery = "SELECT COUNT(`orderid`) AS order_exists 
                   FROM `" . $this->helper_dibs_tools_prefix() . self::api_dibs_get_tableName() . "` 
                   WHERE `orderid` = '" . self::api_dibs_sqlEncode($iOrderId) . "' LIMIT 1;";
        if($this->helper_dibs_db_read_single($sQuery, 'order_exists') <= 0) {
            $this->helper_dibs_db_write("INSERT INTO `" . $this->helper_dibs_tools_prefix() . 
                                        self::api_dibs_get_tableName() . "`(`orderid`) 
                                        VALUES('" . $iOrderId."')");
        }
    }
    
    /**
     * Creates dibs_results DB if not exists.
     */
    public final function api_dibs_checkTable() {
        $this->helper_dibs_db_write(
            "CREATE TABLE IF NOT EXISTS `" . $this->helper_dibs_tools_prefix() . 
                self::api_dibs_get_tableName() . "` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `orderid` varchar(100) NOT NULL DEFAULT '',
                `status` varchar(10) NOT NULL DEFAULT '0',
                `testmode` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `transaction` varchar(100) NOT NULL DEFAULT '',
                `amount` int(10) unsigned NOT NULL DEFAULT '0',
                `currency` smallint(3) unsigned NOT NULL DEFAULT '0',
                `fee` int(10) unsigned NOT NULL DEFAULT '0',
                `paytype` varchar(32) NOT NULL DEFAULT '',
                `voucheramount` int(10) unsigned NOT NULL DEFAULT '0',
                `amountoriginal` int(10) unsigned NOT NULL DEFAULT '0',
                `ext_info` text,
                `validationerrors` text,
                `capturestatus` varchar(10) NOT NULL DEFAULT '0',
                `actioncode` varchar(20) NOT NULL DEFAULT '',
                `success_action` tinyint(1) unsigned NOT NULL DEFAULT '0' 
                    COMMENT '0 = NotPerformed, 1 = Performed',
                `cancel_action` tinyint(1) unsigned NOT NULL DEFAULT '0' 
                    COMMENT '0 = NotPerformed, 1 = Performed',
                `callback_action` tinyint(1) unsigned NOT NULL DEFAULT '0' 
                    COMMENT '0 = NotPerformed, 1 = Performed',
                `success_error` varchar(100) NOT NULL DEFAULT '',
                `callback_error` varchar(100) NOT NULL DEFAULT '',
                `sysmod` varchar(10) NOT NULL DEFAULT '',
                PRIMARY KEY (`id`),
                KEY `orderid` (`orderid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
        );
    }
    
    /**
     * Checks returned main fields.
     * 
     * @param mixed $mOrder
     * @param bool $bUrlDecode
     * @return int 
     */
    final public function api_dibs_checkMainFields($mOrder, $bUrlDecode = TRUE) {
        if(!isset($_POST['orderid'])) return 1;
        $mOrder = $this->helper_dibs_obj_order($mOrder, TRUE);
        if(!$mOrder->orderid) return 2;

        if(!isset($_POST['amount'])) return 3;
        $iAmount = (isset($_POST['voucherAmount']) && $_POST['voucherAmount'] > 0) ? 
                    $_POST['amountOriginal'] : $_POST['amount'];
        if(abs((int)$iAmount - (int)self::api_dibs_round($mOrder->amount)) >= 0.01) return 4;
	if(!isset($_POST['currency'])) return 5;
        if((int)$mOrder->currency != (int)$_POST['currency']) return 6;
           
        $sHMAC = $this->helper_dibs_tools_conf('hmac');
        if(!empty($sHMAC) && self::api_dibs_checkMAC($sHMAC, $bUrlDecode) !== TRUE) return 7;
        
        return 0;
    }
   
    /**
     * Processes success redirect from payment gateway.
     * 
     * @param mixed $mOrder 
     */
    final public function api_dibs_action_success($mOrder) {
        $iErr = $this->api_dibs_checkMainFields($mOrder);
        if($iErr != 1 && $iErr != 2) {
            $this->api_dibs_updateResultRow(array('success_action' => empty($iErr) ? 1 : 0,
                                                  'success_error' => $iErr));
        }
        
        return $iErr;
    }
    
    /**
     * Processes cancel from payment gateway.
     */
    final public function api_dibs_action_cancel() {
        if(isset($_POST['orderid']) && !empty($_POST['orderid'])) {
            $this->api_dibs_updateResultRow(array('cancel_action' => 1));
        }
    }
    
    /**
     * Processes callback from payment gateway.
     * 
     * @param mixed $mOrder 
     */
    final public function api_dibs_action_callback($mOrder) {
        $iErr = $this->api_dibs_checkMainFields($mOrder, FALSE);
        if(!empty($iErr)) {
            if($iErr != 1 && $iErr != 2) {
                $this->api_dibs_updateResultRow(array('callback_error' => $iErr));
            }
            exit((string)$iErr);
        }
         	$sQuery = "SELECT `status` FROM `" . $this->helper_dibs_tools_prefix() . 
                                             self::api_dibs_get_tableName() . "` 
                   WHERE `orderid` = '" . self::api_dibs_sqlEncode($_POST['orderid']) . "' 
                   LIMIT 1;";
                   
        $status= $this->helper_dibs_db_read_single($sQuery, 'status') ;
        
        if($status == "PENDING" || empty($status)) {
            $aFields = array('callback_action' => 1);
            $aResponse = $_POST;
            $iPayMethod = 2;
            foreach(self::$aRespFields as $sDbKey => $mPostKey) {
                if(is_array($mPostKey)) {
                    //$sPostKey = isset($mPostKey[$iPayMethod]) ? $mPostKey[$iPayMethod] : '';
                }
                else $sPostKey = $mPostKey;
                unset($mPostKey);
                
                if(!empty($sPostKey) && isset($_POST[$sPostKey])) {
                    unset($aResponse[$sPostKey]);
                    $aFields[$sDbKey] = $_POST[$sPostKey];
                }
            }
            $aFields['ext_info'] = serialize($aResponse);
            unset($aResponse, $sDbKey, $sPostKey);
            $this->api_dibs_updateResultRow($aFields);
        
            if(method_exists($this, 'helper_dibs_hook_callback') && $_POST['status']=="ACCEPTED") {
                $this->helper_dibs_hook_callback($mOrder);
            }
            
            $sTransac = $_POST['transaction']; 
             if($sTransac) {
                if( version_compare( get_option( 'wpsc_version' ), '3.8.9', '<' ) ) {
                $this->helper_dibs_db_write("UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "`
                SET `processed` = '" . $this->helper_dibs_tools_conf('status') . "',
                `transactid`='" . $sTransac . "' WHERE `id` = '" . $mOrder['id'] . "' LIMIT 1;");
               }               
                
             }
        }
        else {
            return false;
        }  
                
        return true;
    }
 
    /**
     * Updates from array one order row in dibs results table.
     * 
     * @param array $aFields
     */
    private function api_dibs_updateResultRow($aFields) {
        if(isset($_POST['orderid']) && !empty($_POST['orderid'])) {
            $sUpdate = "";
            foreach($aFields as $sCell => $sVal) {
                $sUpdate .= "`" . $sCell . "`=" . "'" . self::api_dibs_sqlEncode($sVal) . "',";
            }
            
            $this->helper_dibs_db_write(
                "UPDATE `" . $this->helper_dibs_tools_prefix() . self::api_dibs_get_tableName() . "`
                 SET " . rtrim($sUpdate, ",") . " 
                 WHERE `orderid` = '" . self::api_dibs_sqlEncode($_POST['orderid']) . "' 
                 LIMIT 1;"
            );
        }
    }
    
    /** DIBS API TOOLS START **/
    /**
     * Calculates MAC for given array of data.
     * 
     * @param array $aData
     * @param string $sHMAC
     * @param bool $bUrlDecode
     * @return string 
     */
    final public static function api_dibs_calcMAC($aData, $sHMAC, $bUrlDecode = FALSE) {
        $sMAC = "";
        if(!empty($sHMAC)) {
            $sData = "";
            if(isset($aData['MAC'])) unset($aData['MAC']);
            ksort($aData);
            foreach($aData as $sKey => $sVal) {
                $sData .= "&" . $sKey . "=" . (($bUrlDecode === TRUE) ? urldecode($sVal) : $sVal);
            }
            $sMAC = hash_hmac("sha256", ltrim($sData, "&"), self::api_dibs_hextostr($sHMAC));
        }
        return $sMAC;
    }
    
    
    /**
     * Compare calculated MAC with MAC from response urldecode response if second parameter is TRUE.
     * 
     * @param string $sHMAC
     * @param bool $bUrlDecode
     * @return bool 
     */
    final public static function api_dibs_checkMAC($sHMAC, $bUrlDecode = FALSE) {
        $_POST['MAC'] = isset($_POST['MAC']) ? $_POST['MAC'] : "";
        return ($_POST['MAC'] == self::api_dibs_calcMAC($_POST, $sHMAC, $bUrlDecode)) ? TRUE : FALSE;
    }
    
    /**
     * Returns ISO to DIBS currency array.
     * 
     * @return array 
     */
    final public static function api_dibs_get_currencyArray() {
        return self::$aCurrency;
    }

    /**
     * Getter for table name.
     * 
     * @return string
     */
    final public static function api_dibs_get_tableName() {
        return self::$sDibsTable;
    }
    
    /**
     * Gets value by code from currency array. Supports fliped values.
     * 
     * @param string $sCode
     * @param bool $bFlip
     * @return string 
     */
    final public static function api_dibs_get_currencyValue($sCode, $bFlip = FALSE) {
        $aCurrency = ($bFlip === TRUE) ? array_flip(self::api_dibs_get_currencyArray()) : 
                     self::api_dibs_get_currencyArray();
        return isset($aCurrency[$sCode]) ? $aCurrency[$sCode] : 
               $aCurrency[self::$sDefaultCurr[$bFlip === TRUE ? 1 : 0]];
    }
    
    /**
     * Convert hex HMAC to string.
     * 
     * @param string $sHex
     * @return string 
     */
    private static function api_dibs_hextostr($sHex) {
        $sRes = "";
        foreach(explode("\n", trim(chunk_split($sHex,2))) as $h) $sRes .= chr(hexdec($h));
        return $sRes;
    }
    
    /**
     * Replaces sql-service quotes to simple quotes and escapes them by slashes.
     * 
     * @param string $sVal
     * @return string 
     */
    public static function api_dibs_sqlEncode($sVal) {
        return addslashes(str_replace("`", "'",  trim(strip_tags((string)$sVal))));
    }
    
    /**
     * Returns integer representation of amont. Saves two signs that are
     * after floating point in float number by multiplication by 100.
     * E.g.: converts to cents in money context.
     * Workarround of float to int casting.
     * 
     * @param float $fNum
     * @return int 
     */
    public static function api_dibs_round($fNum, $iPrec = 2) {
        return empty($fNum) ? (int)0 : (int)(string)(round($fNum, $iPrec) * pow(10, $iPrec));
    }
    
    /**
     * Fixes UTF-8 special symbols if encoding of CMS is not UTF-8.
     * Main using is for wided latin alphabets.
     * 
     * @param string $sText
     * @return string 
     */
    public static function api_dibs_utf8Fix($sText) {
        return (mb_detect_encoding($sText) == "UTF-8" && mb_check_encoding($sText, "UTF-8")) ?
               $sText : utf8_encode($sText);
    }

   
    /** DIBS API TOOLS END **/
}
?>