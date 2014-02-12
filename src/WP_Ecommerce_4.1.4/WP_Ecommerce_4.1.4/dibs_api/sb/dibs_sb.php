<?php
require_once dirname(__FILE__) . '/dibs_sb_params.php';

class dibs_pw_settingsBuilder extends dibs_pw_settingsBuilder_params {
    
    private $aSettingsBase = array();
    private $sApp = "";
    private $sTmpl = "";
    private $sContainer = "";
    private $aClasses = array();
    private $bLowerCaseNames = TRUE;

    private $sCurrent = "";
    private $sRenderBuffer = "";
    
    function __construct($sTmpl = "", $aSettings = array(), $sApp = "", $sContainer = "", $aClasses = array(), $bLCN = TRUE) {
        $this->sApp = $this->cms_get_app();
        $this->aClasses   = $this->cms_get_classes();
        $this->sContainer = $this->cms_get_container();
        if(count($aSettings) > 0) $this->aSettingsBase = $aSettings;
        else $this->aSettingsBase = $this->cms_get_baseSettings();
        if(empty($sTmpl)) $this->sTmpl = $this->cms_get_tmpl();
        else $this->sTmpl = $sTmpl;
    }
    
    function render($bDisplay = FALSE) {
        $this->clear();
        $this->build();
        if($bDisplay === TRUE) echo $this->sCurrent;
        else return $this->sCurrent;
    }
    
    function getCurrent() {
        return $this->sCurrent;
    }
    
    function setCurrent($sNewCurrent) {
        $this->sCurrent = $sNewCurrent;
    }
    
    function clear() {
        $this->sCurrent = "";
    }
    
    function getParamsList() {
        return array_keys($this->aSettingsBase);
    }
    
    private function flag($sType) {
        return "{DIBS" . $this->sApp . "_" . $sType . "}";
    }
    
    private function lang($sKey, $sType) {
        return $this->cms_get_text($sKey, $sType);
    }
    
    private function langLabel($sKey) {
        return $this->lang($sKey, "LABEL");
    }
    
    private function langDescr($sKey) {
        return $this->lang($sKey, "DESCR");
    }
    
    private function build() {
        $sTemplateReady = $this->process();
        
        if(!empty($this->sContainer)) {
            $this->sCurrent = str_replace($this->flag("TMPL"),
                                        $sTemplateReady,
                                        $this->sContainer);
        }
        else $this->sCurrent = $sTemplateReady;
    }
    

    
    private function process() {
        $sTmp = "";
        foreach($this->aSettingsBase as $sKey => $sOptions) {
            $this->sRenderBuffer = $this->sTmpl;
            $this->applyForm($sKey, $sOptions);
            $this->applyLang($sKey);
            $this->applyMarkup();
            $sTmp .= $this->sRenderBuffer;
        }
        
        return $sTmp;
    }
    
    private function applyForm(&$sKey, &$sOptions) {
        $sField = $this->field($sKey, 
                               $sOptions['type'], 
                               $this->cms_get_config($sKey, 
                                                     $sOptions['default']));
     
        $this->sRenderBuffer = str_replace($this->flag("FIELD"), 
                                           $sField, 
                                           $this->sRenderBuffer);
    }
    
    private function applyLang(&$sKey) {
        $this->sRenderBuffer = str_replace($this->flag("LABEL"), 
                                           $this->langLabel($sKey), 
                                           $this->sRenderBuffer);
        $this->sRenderBuffer = str_replace($this->flag("DESCR"), 
                                           $this->langDescr($sKey), 
                                           $this->sRenderBuffer);
    }
    
    private function applyMarkup() {
        foreach($this->aClasses as $sPlaceholder=>$sClass) {
            $this->sRenderBuffer = str_replace($this->flag($sPlaceholder), 
                                               $sClass, 
                                               $this->sRenderBuffer);
        }
    }
    
    private function field($sKey, $sType, $sValue) {
        $sFuncName = "cms_get_" . strtolower($sKey);
        $sName = ($this->bLowerCaseNames === TRUE) ? 
                 strtolower('DIBS' . $this->sApp . '_' . $sKey) : 
                 'DIBS' . $this->sApp . '_' . $sKey;
        
        if($sType == 'input'){
            $sConfig = '<input type="' . $sType .  '" ' . 'name="' . $sName . 
                       '" value="' . $sValue . '" />';

        }
        elseif($sType == 'checkbox') {
            if($sValue == 'yes') $sChecked = ' checked';
            else $sChecked = '';
                    
            $sConfig = '<input type="' . $sType .  '" name="' . $sName . 
                       '" value="yes"' . $sChecked . ' />';
        }
        elseif(is_callable(array('self', $sFuncName))) {
            $sConfig = '<select name="' . $sName . '">
                           ' . $this->arrayToOpts($this->$sFuncName(), $sValue) . '
                        </select>';
        }
        else {
            $sConfig = '<input type="text" name="' . $sName . 
                       '" value="' . $sValue . '" />';                        
        }
        
        return $sConfig;
    }
    
    private function arrayToOpts($aOptArray, $sSelected = "") {
        $sOpts = "";
        foreach($aOptArray as $sKey => $sVal) {
            if($sSelected == $sKey) $sFlag = " selected";
            else $sFlag = "";
            $sOpts .= '<option value="' . $sKey . '"' . $sFlag . '>' . 
                      $sVal . '</option>';            
        }
        
        return $sOpts;
    }
}
?>