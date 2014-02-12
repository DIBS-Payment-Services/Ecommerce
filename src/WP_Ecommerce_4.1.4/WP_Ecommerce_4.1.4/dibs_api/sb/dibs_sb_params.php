<?php
class dibs_pw_settingsBuilder_params {
    private $sApp = "PW";
    private $aClasses = array();
    private $sTmpl = '<tr>
			  <td>{DIBSPW_LABEL}</td>
                          <td>{DIBSPW_FIELD}</td>
                      </tr>
		      <tr>
                          <td>&nbsp;</td>
                          <td>
                              <span class="small description">
                                  {DIBSPW_DESCR}
                              </span>
                          </td>
                      </tr>';

    private $sContainer = '';
    
    private $aText = array(
        'MID'       => array('LABEL' => 'Merchant ID:',
                             'DESCR' => 'Your merchant ID in DIBS system.'),
        'TESTMODE'  => array('LABEL' => 'Test mode:',
                             'DESCR' => 'Run transactions in test mode.'),
        'UNIQ'      => array('LABEL' => 'Unique order ID:',
                             'DESCR' => 'System checks if every order ID unique.'),
        'CAPTURENOW'=> array('LABEL' => 'Capture now:',
                             'DESCR' => 'Make attempt to capture the transaction upon a successful authorization. (DIBS PW only)'),
      
        'FEE'       => array('LABEL' => 'Add fee:',
                             'DESCR' => 'Customer pays fee.'),
        'PAYTYPE'   => array('LABEL' => 'Paytype:',
                             'DESCR' => 'Paytypes available to customer (e.g.: VISA,MC)'),
        
        
        'HMAC'      => array('LABEL' => 'HMAC:',
                             'DESCR' => 'Key for transactions security.'),
        'LANG'      => array('LABEL' => 'Language:',
                             'DESCR' => 'Language of payment window interface.'),
        'ACCOUNT'   => array('LABEL' => 'Account:',
                             'DESCR' => 'Account id used to visually separate transactions in merchant admin.'),
        'DISTR'     => array('LABEL' => 'Distribution type:',
                             'DESCR' => ''),
        'STATUS'    => array('LABEL' => 'Success payment status:',
                             'DESCR' => 'Order status after success transaction.'),
        'STATUSP'   => array('LABEL' => 'Pending payment status:',
                             'DESCR' => 'Order status before payment.'),
        'STATUSC'   => array('LABEL' => 'Cancel payment status:',
                             'DESCR' => 'Order status on cancellation.'),
    );
    
    private $aSettingsBase = array(
        'MID'       => array('type'    => 'text',
                             'default' => ''),
      
        'HMAC'      => array('type'    => 'text',
                             'default' => ''),
        'TESTMODE'  => array('type'    => 'checkbox',
                             'default' => 'yes'),
        'FEE'       => array('type'    => 'checkbox',
                             'default' => ''),
        'CAPTURENOW'=> array('type'    => 'checkbox',
                             'default' => ''),
        'UNIQ'      => array('type'    => 'checkbox',
                             'default' => ''),
        'PAYTYPE'   => array('type'    => 'text',
                             'default' => ''), 
        'LANG'      => array('type'    => 'select',
                             'default' => 'en_UK'),
        'ACCOUNT'   => array('type'    => 'text',
                             'default' => ''),
        'DISTR'     => array('type'    => 'select',
                             'default' => 'empty'),
        'STATUS'    => array('type'    => 'select',
                             'default' => '3'),
        'STATUSP'   => array('type'    => 'select',
                             'default' => '2'),
        'STATUSC'   => array('type'    => 'select',
                             'default' => '5'),
    );
    
    private $aLang = array(
        'da_DK'  => 'Danish',
        'en_UK'  => 'English',
        'nb_NO'  => 'Norwegian',
        'sv_SE'  => 'Swedish',
    );

    private $aMethod = array(
        '1' => 'Auto',
        '2' => 'DIBS Payment Window',
        '3' => 'Mobile Payment Window',
    );    

    
    private $aDistr = array(
        'empty' => '-',
        'email' => 'Email',
        'paper' => 'Paper',
    );    

    protected function cms_get_app() {
        return $this->sApp;
    }
    
    protected function cms_get_classes() {
        return $this->aClasses;
    }
    
    protected function cms_get_tmpl() {
        return $this->sTmpl;
    }

    protected function cms_get_container() {
        return $this->sContainer;
    }
    
    protected function cms_get_baseSettings() {
        return $this->aSettingsBase;
    }
    
    protected function cms_get_lang() {
        return $this->aLang;
    }
    
    protected function cms_get_method() {
        return $this->aMethod;
    }
    
    protected function cms_get_distr() {
        return $this->aDistr;
    }
    
    protected function cms_get_status() {
        global $wpsc_purchlog_statuses;
        
        $aStatuses = array();
        for($i=0; $i<count($wpsc_purchlog_statuses); $i++) {
            $aStatuses[$wpsc_purchlog_statuses[$i]['order']] = $wpsc_purchlog_statuses[$i]['label'];
        }
        return $aStatuses;
    }
    
    protected function cms_get_statusp() {
        return $this->cms_get_status();
    }
    
    protected function cms_get_statusc() {
        return $this->cms_get_status();
    }
    
    protected function cms_get_config($sKey, $sDefault, $sPrefix = "DIBS") {
        return get_option($sPrefix . $this->cms_get_app() . "_" .$sKey, $sDefault);
    }
    
    protected function cms_get_text($sKey, $sLabel) {
        return $this->aText[$sKey][$sLabel];
    }
}
?>