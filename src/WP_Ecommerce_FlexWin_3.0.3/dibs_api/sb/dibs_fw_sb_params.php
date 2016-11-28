<?php
class dibs_fw_settingsBuilder_params {
    private $sApp = "FLEX";
    private $aClasses = array();
    private $sTmpl = '<tr>
			  <td>{DIBSFLEX_LABEL}</td>
                          <td>{DIBSFLEX_FIELD}</td>
                      </tr>
		      <tr>
                          <td>&nbsp;</td>
                          <td>
                              <small>
                                  {DIBSFLEX_DESCR}
                              </small>
                          </td>
                      </tr>';

    private $sContainer = '';
    
    private $aText = array(
        'MID'       => array('LABEL' => 'Merchant ID:',
                             'DESCR' => 'Your merchant ID in DIBS system.'),
        /*
        'APIUSER'   => array('LABEL' => 'API Login:',
                             'DESCR' => 'Your DIBS CGI API username.'),
        
        'APIPASS'   => array('LABEL' => 'API Password:',
                             'DESCR' => 'Your DIBS CGI API password.'),
        */
        
        'TESTMODE'  => array('LABEL' => 'Test mode:',
                             'DESCR' => 'Run transactions in test mode.'),
        'UNIQ'      => array('LABEL' => 'Unique order ID:',
                             'DESCR' => 'System checks if every order ID unique.'),
        'FEE'       => array('LABEL' => 'Add fee:',
                             'DESCR' => 'Customer pays fee.'),
        'VOUCHER'   => array('LABEL' => 'Enable vouchers:',
                             'DESCR' => 'Enable customer to pay with vouchers.'),
        'PAYTYPE'   => array('LABEL' => 'Paytype:',
                             'DESCR' => 'Paytypes available to customer (e.g.: VISA,MC)'),
        'MD51'      => array('LABEL' => 'MD5 Key 1:',
                             'DESCR' => 'Key 1 for transactions security.'),
        'MD52'      => array('LABEL' => 'MD5 Key 1:',
                             'DESCR' => 'Key 2 for transactions security.'),
        'LANG'      => array('LABEL' => 'Language:',
                             'DESCR' => 'Language of payment window interface.'),
        'ACCOUNT'   => array('LABEL' => 'Account:',
                             'DESCR' => 'Account id used to visually separate transactions in merchant admin.'),
        'CAPTURENOW'=> array('LABEL' => 'Capture now:',
                             'DESCR' => 'Make attempt to capture the transaction upon a successful authorization.'),
        'SKIPLAST'  => array('LABEL' => 'Skip last:',
                             'DESCR' => 'Skip last page after checkout and redirect customer to shop.'),
        'DECOR'     => array('LABEL' => 'Decorator:',
                             'DESCR' => 'FlexWin theme decorator.'),
        'COLOR'     => array('LABEL' => 'Color:',
                             'DESCR' => 'FlexWin theme color.'),
        'DISTR'     => array('LABEL' => 'Distribution type:',
                             'DESCR' => 'Invoices distribution type.'),
    );
        
    private $aSettingsBase = array(
        'MID'       => array('type'    => 'text',
                             'default' => ''),
        /*
        'APIUSER'   => array('type'    => 'text',
                             'default' => ''),
        'APIPASS'   => array('type'    => 'text',
                             'default' => ''),
        */
        'TESTMODE'  => array('type'    => 'checkbox',
                             'default' => 'yes'),
        'UNIQ'      => array('type'    => 'checkbox',
                             'default' => 'no'),
        'FEE'       => array('type'    => 'checkbox',
                             'default' => 'no'),
        'VOUCHER'   => array('type'    => 'checkbox',
                             'default' => 'no'),
        'PAYTYPE'   => array('type'    => 'text',
                             'default' => ''),
        'MD51'      => array('type'    => 'text',
                             'default' => ''),
        'MD52'      => array('type'    => 'text',
                             'default' => ''),
        'LANG'      => array('type'    => 'select',
                             'default' => 'en'),
        'ACCOUNT'   => array('type'    => 'text',
                             'default' => ''),
        'CAPTURENOW'=> array('type'    => 'checkbox',
                             'default' => 'no'),
        'SKIPLAST'  => array('type'    => 'checkbox',
                             'default' => 'no'),
        'DECOR'     => array('type'    => 'select',
                             'default' => 'default'),
        'COLOR'     => array('type'    => 'select',
                             'default' => 'blank'),
        'DISTR'     => array('type'    => 'select',
                             'default' => 'empty'),
       
    );
    
    private $aLang = array(
        'da' => 'Danish',
        'nl' => 'Dutch',
        'en' => 'English',
        'fo' => 'Faroese',
        'fi' => 'Finnish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'no' => 'Norwegian',
        'pl' => 'Polish',
        'es' => 'Spanish',
        'sv' => 'Swedish'
    );
    
    private $aDecor = array(
        'default' => 'Default',
        'basal'   => 'Basal',
        'rich'    => 'Rich',
        'responsive' => 'Responsive'
    );
    
    private $aColor = array(
        'blank' => 'None',
        'sand'  => 'Sand',
        'grey'  => 'Grey',
        'blue'  => 'Blue'
    );

    private $aDistr = array(
        'empty' => '-',
        'email' => 'Email',
        'paper' => 'Paper'
    );    

    
    private $aYesno = array(
        'yes' => 'Yes',
        'no'  => 'No'
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
    
    protected function cms_get_color() {
        return $this->aColor;
    }
    
    protected function cms_get_decor() {
        return $this->aDecor;
    }
    
    protected function cms_get_distr() {
        return $this->aDistr;
    }
    
    protected function cms_get_config($sKey, $sDefault, $sPrefix = "DIBS") {
        return get_option($sPrefix . $this->cms_get_app() . "_" .$sKey, $sDefault);
    }
    
    protected function cms_get_text($sKey, $sLabel) {
        return $this->aText[$sKey][$sLabel];
    }
    
    protected function cms_get_yesno() {
        return $this->aYesno;
    }
}
?>