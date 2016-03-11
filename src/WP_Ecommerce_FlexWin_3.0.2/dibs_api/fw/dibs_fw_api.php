<?php
require_once str_replace("\\", "/", dirname(__FILE__)) . '/dibs_fw_helpers_interface.php';
require_once str_replace("\\", "/", dirname(__FILE__)) . '/dibs_fw_helpers_cms.php';
require_once str_replace("\\", "/", dirname(__FILE__)) . '/dibs_fw_helpers.php';

class dibs_fw_api extends dibs_fw_helpers {

    private static $sDibsTable   = 'dibs_fw_results';
    
    private static $aTemplates   = array('folder' => 'tmpl',
                                         'marker' => '#',
                                         'autotranslate' => array('lbl','msg', 'sts', 'err'),
                                         'tmpls' => array(
                                             'error'   => 'dibs_fw_error',
                                             'buttons' => 'dibs_fw_cgi'
                                         ));
    
    private static $sDefaultCurr = array(0 => 'EUR', 1 => '978');

    private static $sFormAction  = 'https://payment.architrade.com/paymentweb/start.action';
    
    private static $aRespFields  = array('orderid' => 'orderid', 'testmode' => 'test', 
                                         'transaction' => 'transact', 'amount' => 'amount',
                                         'currency' => 'currency', 'capturestatus' => 'statuscode',
                                         'voucheramount' => 'voucher_amount', 'paytype' => 'paytype',
                                         'amountoriginal'=>'amount_original', 'fee' => 'fee', 
                                         'actioncode' => 'approvalcode', 'sysmod' => 's_sysmod');
    
    private static $sCgiHost     = 'payment.architrade.com'; // Exactly host, without prefix/suffix
    
    private static $sCgiPort     = 443;
    
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
     * Returns CMS order information converted to standardized order information objects.
     * 
     * @param mixed $mOrderInfo
     * @return object 
     */
    final public function api_dibs_orderObject($mOrderInfo) {
        return (object)array(
            'order' => $this->helper_dibs_obj_order($mOrderInfo),
            'urls'  => $this->helper_dibs_obj_urls($mOrderInfo),
            'items' => $this->helper_dibs_obj_items($mOrderInfo),
            'ship'  => $this->helper_dibs_obj_ship($mOrderInfo),
            'addr'  => $this->helper_dibs_obj_addr($mOrderInfo),
            'etc'   => $this->helper_dibs_obj_etc($mOrderInfo)
        );
    }
 
    /**
     * Collects API parameters to send in dependence of checkout type. API entry point.
     * 
     * @param mixed $mOrderInfo
     * @return array 
     */
    final public function api_dibs_get_requestFields($mOrderInfo) {
        $oOrder = $this->api_dibs_orderObject($mOrderInfo);
        $aData = array();
        $this->api_dibs_prepareDB($oOrder->order->orderid);
        $this->api_dibs_commonFields($aData, $oOrder);
        $this->api_dibs_specificFields($aData);
        $this->api_dibs_invoiceFields($aData, $oOrder);
        if(count($oOrder->etc) > 0) {
            foreach($oOrder->etc as $sKey => $sVal) $aData['s_' . $sKey] = $sVal;
        }
        array_walk($aData, create_function('&$val', '$val = trim($val);'));
        $sFingerprint = self::api_dibs_calcHash($aData, 
                                                $this->helper_dibs_tools_conf('md51'),
                                                $this->helper_dibs_tools_conf('md52'));
        if(!empty($sFingerprint)) $aData['md5key'] = $sFingerprint;
        
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
        $aData['lang'] = $this->helper_dibs_tools_conf('lang');
        if((string)$this->helper_dibs_tools_conf('fee') == 'yes') $aData['calcfee'] = 1;
        if((string)$this->helper_dibs_tools_conf('testmode') == 'yes') $aData['test'] = "yes";
        $sPaytype = $this->helper_dibs_tools_conf('paytype');
        if(!empty($sPaytype)) $aData['paytype'] = $sPaytype;
        $sAccount = $this->helper_dibs_tools_conf('account');
        if(!empty($sAccount)) $aData['account'] = $sAccount;
        $aData['accepturl'] = $this->helper_dibs_tools_url($oOrder->urls->acceptreturnurl);
        $aData['cancelurl'] = $this->helper_dibs_tools_url($oOrder->urls->cancelreturnurl);
        
        $aData['callbackurl']     = $oOrder->urls->callbackurl;
        if(strpos($aData['callbackurl'], '/5c65f1600b8_dcbf.php') === FALSE) {
            $aData['callbackurl'] = $this->helper_dibs_tools_url($aData['callbackurl']);
        }
    }
    
    /**
     * Adds to $aData integration method specific parameters.
     * 
     * @param array $aData 
     */
    private function api_dibs_specificFields(&$aData) {
        $sSkiplastpage = $this->helper_dibs_tools_conf('skiplast');
        if((string)$sSkiplastpage == 'yes') {
            $aData['doNotShowLastPage'] = "true"; /* For PBB at Gothia */
            $aData['skiplastpage'] = 1;
        }
        $sSendCookies = $this->helper_dibs_tools_conf('sendcookies');
        if((string)$sSendCookies == 'yes') {
            $sHttpCookie = getenv('HTTP_COOKIE');
            if(!empty($sHttpCookie)) {
                $aData['HTTP_COOKIE'] = self::api_dibs_fixCookie($sHttpCookie);
            }
        }
        $sDecorator = $this->helper_dibs_tools_conf('decor');
        if((string)$sDecorator != 'default') $aData['decorator'] = $sDecorator;
        $sColor = $this->helper_dibs_tools_conf('color');
        if((string)$sColor != 'blank') $aData['color'] = $sColor;
        $sCapturenow = $this->helper_dibs_tools_conf('capturenow');
        if((string)$sCapturenow == 'yes') $aData['capturenow'] = 1;
        $sUid = $this->helper_dibs_tools_conf('uniq');
        $aData['realorderid'] = $aData['orderid'];
        if((string)$sUid == 'yes') {
            $aData['uniqueoid'] = 1;
            $aData['orderid'] =$aData['orderid'] . time();
        }
        
        $sVoucher = $this->helper_dibs_tools_conf('voucher');
        if((string)$sVoucher == 'yes') $aData['voucher'] = 'yes';
    }
    

    /**
     * Adds to $aData invoice API parameters.
     *
     * @param array $aData
     * @param object $oOrder 
     */
    private function api_dibs_invoiceFields(&$aData, $oOrder) {
        foreach($oOrder->addr as $sKey => $sVal) {
            $sVal = trim($sVal);
            if(!empty($sVal)) $aData[$sKey] = self::api_dibs_utf8Fix($sVal);
        }
        
        $oOrder->items[] = $oOrder->ship;
        $aData['structuredOrderInformation'] = $this->api_dibs_getInvoiceXml($oOrder->order->orderid,
                                                                             $oOrder->items);
        $sDistribType = $this->helper_dibs_tools_conf('distr');
        if((string)$sDistribType != 'empty') $aData['distributionType'] = $sDistribType;
    }
    
    /**
     * Generates structuredOrderInformation XML for invoice API.
     * 
     * @param int $iOrderId
     * @param int $iTotal
     * @param array $aItems
     * @param array $aShippingInfo
     * @return string 
     */
    private function api_dibs_getInvoiceXml($iOrderId, $aItems) {
        $doc = new DomDocument("1.0","UTF-8");
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = true;
 
        $root = $doc->appendChild($doc->createElement("orderInformation"));
        $root->appendChild($doc->createElement("yourRef"))
             ->appendChild($doc->createTextNode($iOrderId));

        $i = 1;
        foreach($aItems as $oItem) {
            $oItem->price = isset($oItem->price) ? self::api_dibs_round($oItem->price) : (int)0;
            if(!empty($oItem->price)) {
                if(!empty($oItem->name)) $oItem->name = $this->api_dibs_utf8Fix($oItem->name);
                elseif(!empty($oItem->sku)) $oItem->name = $this->api_dibs_utf8Fix($oItem->sku);
                else $oItem->name = $oItem->id;
                
                $aAttrs = array('itemID' => $oItem->id,
                                'itemDescription' => $oItem->name,
                                'comments' => 'SKU: ' . $oItem->sku,
                                'orderRowNumber' => $i,
                                'quantity' => self::api_dibs_round($oItem->qty, 3) / 1000,
                                'price' => $oItem->price,
                                'unitCode' => 'pcs',
                                self::$bTaxAmount ? 'VATAmount' : 'VATPercent' => 
                                                    self::api_dibs_round($oItem->tax));

                $occ = $root->appendChild($doc->createElement("orderItem"));            
                foreach($aAttrs as $sKey => $sVal) {
                    $occ->appendChild($doc->createAttribute($sKey))
                        ->appendChild($doc->createTextNode($sVal));
                }
                $i++;
            }
        }

        return htmlspecialchars($doc->saveXML(), ENT_COMPAT, "UTF-8");
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
    private function api_dibs_checkTable() {
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
    private function api_dibs_checkMainFields($mOrder) {
        if(!isset($_POST['orderid'])) return 1;
        $mOrder = $this->helper_dibs_obj_order($mOrder, TRUE);
        if(!$mOrder->orderid) return 2;

        if(!isset($_POST['amount'])) return 3;
        if(isset($_POST['voucher_amount']) && $_POST['voucher_amount'] > 0) {
            $iAmount = ($_POST['amount'] == 0) ? $_POST['voucher_amount'] : $_POST['amount_original'];
        }
        else $iAmount = $_POST['amount'];
        $iFeeAmount = (isset($_POST['fee']) && $_POST['fee'] > 0) ? 
                      $iAmount - $_POST['fee'] : $iAmount;

        if(abs((int)$iAmount - (int)self::api_dibs_round($mOrder->amount)) >= 0.01 &&
           abs((int)$iFeeAmount - (int)self::api_dibs_round($mOrder->amount)) >= 0.01) return 4;
	if(!isset($_POST['currency'])) return 5;
        if((int)$mOrder->currency != (int)$_POST['currency']) return 6;
           
        if(self::api_dibs_checkHash($this->helper_dibs_tools_conf('md51'), 
                                    $this->helper_dibs_tools_conf('md52')) !== TRUE) return 7;
        
        return 0;
    }

    /**
     * Give fallback verification error page 
     * if module has no ability to use CMS for error displaying.
     * 
     * @param int $iCode
     * @return string 
     */
    public function api_dibs_getFatalErrorPage($iCode) {
        return $this->api_dibs_renderTemplate(self::$aTemplates['tmpls']['error'],
                   array('errname_err' => 0,
                         'errcode_msg' => 'errcode',
                         'errcode'     => $iCode,
                         'errmsg_msg'  => 'errmsg',
                         'errmsg_err'  => $iCode,
                         'link_toshop' => $this->helper_dibs_obj_urls()->carturl,
                         'toshop_msg'  => 'toshop'));
    }
    
    /**
     * Processes success redirect from payment gateway.
     * 
     * @param mixed $mOrder
     * @return int 
     */
    final public function api_dibs_action_success($mOrder) {
        $iErr = $this->api_dibs_checkMainFields($mOrder);
        if($iErr != 1 && $iErr != 2) {
            $this->api_dibs_updateResultRow(array('success_action' => empty($iErr) ? 1 : 0,
                                                  'cancel_action'  => 0,
                                                  'success_error'  => $iErr));
        }
        
        return (int)$iErr;
    }
    
    /**
     * Processes cancel from payment gateway.
     */
    final public function api_dibs_action_cancel() {
        if(isset($_POST['orderid']) && !empty($_POST['orderid'])) {
            $this->api_dibs_updateResultRow(array('cancel_action' => '1'));
        }
        // On cancellation close order 
        $orderid = $_POST['realorderid'];
        $comment = "Customer cancelled payment in DIBS FlexWin";
        $purchase_log = new WPSC_Purchase_Log($orderid);
        $purchase_log->set('processed', WPSC_Purchase_Log::CLOSED_ORDER);
        $purchase_log->set('notes', $comment);
        $purchase_log->save();
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
                   WHERE `orderid` = '" . self::api_dibs_sqlEncode($_POST['realorderid']) . "' 
                   LIMIT 1;";
        if($this->helper_dibs_db_read_single($sQuery, 'status') == 0) {
            $aFields = array('callback_action' => 1, 'status' => 1);
            $aResp = $_POST;
            foreach(self::$aRespFields as $sDbKey => $sPostKey) {
                if(!empty($sPostKey) && isset($_POST[$sPostKey])) {
                    unset($aResp[$sPostKey]);
                    if($sPostKey == 'test') $_POST[$sPostKey] = ($_POST[$sPostKey] == 'yes') ? 1 : 0;
                    $aFields[$sDbKey] = $_POST[$sPostKey];
                }
            }
            $aFields['ext_info'] = serialize($aResp);
            unset($aResp, $sDbKey, $sPostKey);
            $aFields['orderid'] = $_POST['realorderid'];
            $this->api_dibs_updateResultRow($aFields);
           
            if(is_callable(array($this, 'helper_dibs_hook_callback')) &&
                    method_exists($this, 'helper_dibs_hook_callback')) {
                $this->helper_dibs_hook_callback($mOrder);
            }
        }
        else $this->api_dibs_updateResultRow(array('callback_error' => 'Err: status not 0.'));
        exit();
    }
    
    /**
     * Updates from array one order row in dibs results table.
     * 
     * @param array $aFields
     */
    private function api_dibs_updateResultRow($aFields) {
        if(isset($_POST['realorderid']) && !empty($_POST['realorderid'])) {
            $sUpdate = "";
            foreach($aFields as $sCell => $sVal) {
                $sUpdate .= "`" . $sCell . "`=" . "'" . self::api_dibs_sqlEncode($sVal) . "',";
            }
            
            $this->helper_dibs_db_write(
                "UPDATE `" . $this->helper_dibs_tools_prefix() . self::api_dibs_get_tableName() . "`
                 SET " . rtrim($sUpdate, ",") . " 
                 WHERE `orderid` = '" . self::api_dibs_sqlEncode($_POST['realorderid']) . "' 
                 LIMIT 1;"
            );
        }
    }

    /**
     * Simple template loader and renderer. Used to load fallback error template.
     * 
     * @param string $sTmplName
     * @param array $sParams
     * @return string 
     */
    public function api_dibs_renderTemplate($sTmplName, $sParams = array()) {
        $sTmpl = file_get_contents(str_replace("\\", "/", dirname(__FILE__)) . "/" . 
                                   self::$aTemplates['folder'] . "/" . $sTmplName);
        if($sTmpl !== FALSE) {
            foreach($sParams as $sKey => $sVal) {
                $sValueType = substr($sKey, -3);
                if(in_array($sValueType, self::$aTemplates['autotranslate'])) {
                    $sVal = $this->helper_dibs_tools_lang($sVal, $sValueType);
                }
                $sTmpl = str_replace(self::$aTemplates['marker'] . $sKey . self::$aTemplates['marker'], 
                                     $sVal, $sTmpl);
            }
        }
        else $sTmpl = "";
        
        return $sTmpl;
    }
    
    /** DIBS API TOOLS START **/
    /**
     * Calculates MD5 for FlexWin API
     * 
     * @param array $aData
     * @return string 
     */
    private static function api_dibs_calcHash($aData, $sMD51, $sMD52, $bResponse = FALSE) {
        if($bResponse === TRUE) {
            $sDataStr = 'transact='  . $aData['transact'] . 
                        '&amount='   . ($aData['amount'] + (!isset($aData['fee']) ? 0 : $aData['fee'])) . 
                        '&currency=' . $aData['currency'];
        }
        else {
            $sDataStr = 'merchant='  . $aData['merchant'] . 
                        '&orderid='  . $aData['orderid'] . 
                        '&currency=' . $aData['currency'] .
                        '&amount='   . $aData['amount'];
        }

        return self::api_dibs_calcAnyHash($sMD51, $sMD52, $sDataStr);
        
    }
    
    private static function api_dibs_calcAnyHash($sMD51, $sMD52, $sDataStr) {
        $sMD51 = trim($sMD51, " ,\t,\r,\n");
        $sMD52 = trim($sMD52, " ,\t,\r,\n");
        return (!empty($sMD51) && !empty($sMD52)) ? md5($sMD52 . md5($sMD51 . $sDataStr)) : "";
    }
    
    /**
     * Compare calculated MD5 with MD5 from response
     * 
     * @param array $aReq
     * @return bool 
     */
    private static function api_dibs_checkHash($sMD51, $sMD52) {
        return (empty($sMD51) || empty($sMD52) || 
               $_POST['authkey'] == self::api_dibs_calcHash($_POST, $sMD51, $sMD52, TRUE)) ? 
               TRUE : FALSE;
    }
   
    /**
     * Returns form action URL of gateway.
     * 
     * @param bool $bResponse
     * @return string 
     */
    final public static function api_dibs_get_formAction() {
        return self::$sFormAction;
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
        $aCurrency = self::api_dibs_get_currencyArray();
        if($bFlip === TRUE) $aCurrency = array_flip($aCurrency);
        return isset($aCurrency[$sCode]) ? $aCurrency[$sCode] : 
               $aCurrency[self::$sDefaultCurr[$bFlip === TRUE ? 1 : 0]];
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
    
    /**
     * Fixes cookie
     * 
     * @param string $sCookie
     * @return string 
     */
    private static function api_dibs_fixCookie($sCookie) {
        $aNewCookies = array();
	if(strpos($sCookie,"%") !== FALSE) $sCookie = urldecode($sCookie);
        $aCookie = explode("; ", $sCookie);
        unset($sCookie);
	for($i=0; $i<count($aCookie); $i++) {
            if(preg_match("/^[^\s;=]+=[^;=]+$/is", $aCookie[$i])) $aNewCookies[] = $aCookie[$i];
        }
        $sNewCookies = implode("; ", $aNewCookies);
        unset($aNewCookies, $aCookie);
        return $sNewCookies;
    }
    
    /** START OF CGI API **/
    /**
     * Process order id and display status and controls if avaliable, error message otherwise.
     * Entrypoint to display buttons.
     * 
     * @param int $sOrderId
     * @return string 
     */
    public function api_dibs_cgi_getAdminControls($purchlogitem) {
        $sApiUser = $this->helper_dibs_tools_conf("apiuser");
        $sApiPass = $this->helper_dibs_tools_conf("apipass");
        if(!empty($sApiUser) && !empty($sApiPass)) {
            $sTransac = $purchlogitem->extrainfo->transactid;
         
            if(!empty($sTransac)) {
       
                $mInfo = $this->api_dibs_cgi_getTransacInfo($sOrderId, $sTransac, $sApiUser, $sApiPass);
                $mInfo = $this->api_dibs_cgi_prepareInfo($mInfo);
                
                return (is_array($mInfo)) ? $this->api_dibs_cgi_renderControlsBlock($mInfo, $sTransac) : $mInfo;
            }
            else return $this->helper_dibs_tools_lang(10, 'err');
        }
        else return $this->helper_dibs_tools_lang(9, 'err');
    }
    
    /**
     * Makes payinfo.cgi request to get transaction detailes and status.
     * 
     * @param string $sOrderId
     * @param int $iTransac
     * @param string $sApiUser
     * @param string $sApiPass
     * @return string 
     */
    private function api_dibs_cgi_getTransacInfo($sOrderId, $iTransac, $sApiUser, $sApiPass) {
        return $this->api_dibs_cgi_makeRequest($sApiUser . ":" . $sApiPass, "/cgi-adm/payinfo.cgi",
                                               array('transact' => $iTransac));
    }
    
    /**
     * Detects correct data in state response or returns an error message.
     * 
     * @param string $sInfo
     * @return mixed 
     */
    private function api_dibs_cgi_prepareInfo($sInfo) {
        if(strpos($sInfo, "&") !== FALSE && strpos($sInfo, "=") !== FALSE) {
            $aState = array();
            parse_str($sInfo, $aState);
            
            return (isset($aState['status']) && isset($aState['currency']) && 
                    isset($aState['amount']) && isset($aState['orderid'])) ? 
                    $aState : $this->helper_dibs_tools_lang(12, 'err');
        }
        elseif(strlen(trim($sInfo, " ,\n,\r,\t")) <= 100) return "Error: " . $sInfo;
        elseif(strpos($sInfo, "Login problems?") !== FALSE) return  $this->helper_dibs_tools_lang(11, 'err');
        else return $this->helper_dibs_tools_lang(12, 'err');
    }

    /**
     * Generates admin controls view from templates.
     * 
     * @param array $aState
     * @param int $iTransac
     * @return string 
     */
    private function api_dibs_cgi_renderControlsBlock($aState, $iTransac) {
        $sCgiBlock = self::api_dibs_renderTemplate(self::$aTemplates['tmpls']['buttons'] . "Status", 
                         array('status_lbl' => 'cgistatus', 'status_sts' => $aState['status']));
                
        $sBtnDecor = (method_exists($this, 'helper_dibs_cgi_getButtonsClass') &&
                      is_callable(array($this, 'helper_dibs_cgi_getButtonsClass'))) ?
                     ' class="' . $this->helper_dibs_cgi_getButtonsClass() . '"' : "";
        
        if((int)$aState['status'] == 2 || (int)$aState['status'] == 5) { 
            $sActions = ((int)$aState['status'] ==  2) ?
                        $this->api_dibs_cgi_renderButton('cancel', $sBtnDecor) . "&nbsp;" .
                        $this->api_dibs_cgi_renderButton('capture', $sBtnDecor) :
                        $this->api_dibs_cgi_renderButton('refund', $sBtnDecor);
                
            $sCgiBlock .= self::api_dibs_renderTemplate(self::$aTemplates['tmpls']['buttons'] . "Actions", 
                              array('actions_lbl' => 'cgiactions',
                                    'transac'     => $iTransac,
                                    'currency'    => $aState['currency'],
                                    'amount'      => $aState['amount'],
                                    'orderid'     => $aState['orderid'],
                                    'return_url'  => self::api_dibs_cgi_getFullUrl(),
                                    'cgi_url'     => get_option('siteurl') . '?dibsflex_cgi=true',  //$this->helper_dibs_obj_urls()->cgiurl,
                                    'controls'    => $sActions));
        }
               
        return self::api_dibs_renderTemplate(self::$aTemplates['tmpls']['buttons'], 
                                             array('cgiblock'  => $sCgiBlock));
    }

    /**
     * Fills input tempalte.
     * 
     * @param string $sCapture
     * @param string $sBtnDecor
     * @return string 
     */
    private function api_dibs_cgi_renderButton($sCapture, $sBtnDecor) {
        return $this->api_dibs_renderTemplate(self::$aTemplates['tmpls']['buttons'] . "ActionsExt", 
                                             array('decorator'   => $sBtnDecor, 
                                                   'name'        => 'cgi' . $sCapture, 
                                                   'capture_msg' => 'button_' . $sCapture));
    }
    
    /**
     * Performs requested API query and redirects user back.
     */
    public function api_dibs_cgi_process() {
        $sApiLogin = $this->helper_dibs_tools_conf("apiuser");
        $sApiPass = $this->helper_dibs_tools_conf("apipass");

        if(!empty($sApiLogin) && !empty($sApiPass)) {
            if(isset($_POST['cgicancel'])) $sAPI = 'cancel';
            elseif(isset($_POST['cgicapture'])) $sAPI = 'capture';
            elseif(isset($_POST['cgirefund'])) $sAPI = 'refund';
            else $this->helper_dibs_tools_redirect($_POST['dibsflexreturn']);
    
            
            
            $aParams = array('orderid'   => $_POST['orderid'],
                            'merchant'  => $this->helper_dibs_tools_conf("mid"),
                            'transact'  => $_POST['transact'], 
                            'textreply' => 'yes',
                            'currency'  => $_POST['currency'], 
                            'amount'    => $_POST['amount']);
            $sPath = ($sAPI == 'capture') ? '/cgi-bin/' : '/cgi-adm/';
            $sAccount = $this->helper_dibs_tools_conf("account");
            if(!empty($sAccount)) $aParams['account'] = $sAccount;
    
            $sMD5 = $this->api_dibs_cgi_calcHash($aParams, $sAPI);
            if($sMD5 != "") $aParams['md5key'] = $sMD5;
            
            $sRes = self::api_dibs_cgi_makeRequest($sApiLogin . ':' . $sApiPass, 
                                                   $sPath . $sAPI . ".cgi", $aParams);
        }
        $this->helper_dibs_tools_redirect($_POST['dibsflexreturn']);
    }

    /**
     * Process all API queries with cURL lib or sockets (in fallback mode).
     * 
     * @param string $sURL
     * @param array $aParams
     * @return string 
     */
    private function api_dibs_cgi_makeRequest($sAuthStr, $sQuery, $aParams) {
        self::$sCgiPort = !function_exists('openssl_verify') ? 80 : 443;
        if(extension_loaded("curl") && is_callable("curl_init")) {
            return self::api_dibs_cgi_makeRequestCurl($sAuthStr, $sQuery, $aParams);
        }
        elseif(is_callable('fsockopen')) {
            return self::api_dibs_cgi_makeRequestSockect($sAuthStr, $sQuery, $aParams);
        }
        else return  $this->helper_dibs_tools_lang(8, 'err');
    }
    
    /**
     * Curl request processors. Used by default in curl extension is enabled.
     * 
     * @param string $sURL
     * @param array $aParams
     * @return string 
     */
    private static function api_dibs_cgi_makeRequestCurl($sAuthStr, $sQuery, $aParams) {
        $sProtocol = (self::$sCgiPort == 443) ? "s" : "";
        $ch = curl_init('http' . $sProtocol . '://' . $sAuthStr . "@" . self::$sCgiHost . $sQuery);
        
        if($ch) {
            curl_setopt($ch, CURLOPT_PORT, self::$sCgiPort);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $aParams);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            return $result;
            curl_close($ch);
        }
        
        return "";
    }
    
    /**
     * Fallback request processor based on fsockopen. Used if curl is unavailable.
     * 
     * @param type $sAuthStr
     * @param string $sQuery
     * @param array $aParams
     * @return string 
     */
    private static function api_dibs_cgi_makeRequestSockect($sAuthStr, $sQuery, $aParams) {
        $sParams = "";
        $sResult = "";
        foreach($aParams as $sName => $sVal) $sParams .= $sName . "=" . $sVal . "&";
        unset($sName, $sVal, $aParams);
        $sParams = trim($sParams, "&");
        $sProtocol = (self::$sCgiPort == 443) ? "tls://" : "";
        $fp = fsockopen($sProtocol . self::$sCgiHost, self::$sCgiPort);
        if($fp) {
            fputs($fp, "POST " . $sQuery . " HTTP/1.1\r\nHost: " . self::$sCgiHost . "\r\n" .
                       "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:11.0) " .
                       "Gecko/20100101 Firefox/11.0\r\nAccept: text/html,application/xhtml+xml," . 
                       "application/xml;q=0.9,*/*;q=0.8\r\nAccept-Language: en-us,en;q=0.5\r\n" .
                       "Accept-Encoding: gzip, deflate\r\nReferer: " . 
                       self::api_dibs_cgi_getFullUrl() . "\r\n" .
                       "Authorization: Basic ".base64_encode($sAuthStr)."\r\n" .
                       "Content-Type: application/x-www-form-urlencoded\r\n" .
                       "Content-Length: " . strlen($sParams) . "\r\n\r\n" .
                       $sParams);
            while(!feof($fp)) $sResult .= fgets($fp, 8192);
            fclose($fp);
        }
        
        return $sResult;
    }
    
    /**
     * Generate URL to redirect user back after API query performed.
     * 
     * @return string 
     */
    private static function api_dibs_cgi_getFullUrl() {
        return "http" . ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 
                "s" : "") . "://" . $_SERVER["SERVER_NAME"] . 
                ((isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") ? 
                ":" . $_SERVER["SERVER_PORT"] : "") . $_SERVER["REQUEST_URI"];
    }

    /**
     * Calculate MD5 checksum for each type of API requests.
     * 
     * @param array $aData
     * @param string $sAPI
     * @return string 
     */
    private function api_dibs_cgi_calcHash($aData, $sAPI) {
        $sMD51 = $this->helper_dibs_tools_conf("md51");
        $sMD52 = $this->helper_dibs_tools_conf("md52");
        $sDataStr = ($sAPI == 'capture' || $sAPI == 'refund') ? '&amount=' . $aData['amount'] : "";
        return self::api_dibs_calcAnyHash($sMD51, $sMD52, 'merchant=' . $aData['merchant'] . 
              '&orderid=' . $aData['orderid'] . '&transact=' . $aData['transact'] . $sDataStr);
    }
    /** EOF CGI API **/
}
?>