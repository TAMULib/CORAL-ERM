<?php
/**
 * @author: Cesar Hernandez
 * getLanguage: This method return the name of the language according to the language code
 */
class LangCodes{
    public function getLanguage($code){
        $all_lang=array(
            'fr_FR'=>'fr_FR',
            'en_GB'=>'en_GB',
            'en_US'=>'en_US',
            'zh_CN'=>'zh_CN',
            'zh_TW'=>'zh_TW',
            'de_DE'=>'de_DE',
            'tr_TR'=>'tr_TR'
        );
        return array_key_exists($code, $all_lang) ? $all_lang[$code] : null;
    }
    public function getNameLang($code_lang){
        $name_lang=array(
            'fr_FR'=>'Français',
            'en_GB'=>'English (GB)',
            'en_US'=>'English (US)',
            'zh_CN'=>'中文 (简体)',
            'zh_TW'=>'中文 (正體)',
            'de_DE'=>'Deutsch',
            'tr_TR'=>'Türkçe'
        );
        return array_key_exists($code_lang, $name_lang) ? $name_lang[$code_lang] : null;
    }

    public function getBrowserLanguage() {
        return str_replace('-', '_', substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,5));
    }

    public function getLanguageSelector() {
        echo '<span id="setLanguage"><select name="lang" id="lang" class="dropDownLang">';
        $route='locale';
        $lang[]="en_US"; // add default language
        if (is_dir($route)) {
            if ($dh = opendir($route)) {
                while (($file = readdir($dh)) !== false) {
                    if (is_dir("$route/$file") && $file!="." && $file!=".." && $this->getLanguage($file) != null){
                        $lang[]=$file;
                    }
                }
                closedir($dh);
            }
        }else {
            echo "<br>"._("Invalid translation route!");
        }
        // Get language of navigator
        $defLang = $this->getBrowserLanguage();

        // Show an ordered list
        sort($lang);
        for($i=0; $i<count($lang); $i++){
            if(isset($_COOKIE["lang"])){
                if($_COOKIE["lang"]==$lang[$i]){
                    echo "<option value='".$lang[$i]."' selected='selected'>".$this->getNameLang($lang[$i])."</option>";
                }else{
                    echo "<option value='".$lang[$i]."'>".$this->getNameLang($lang[$i])."</option>";
                }
            }else{
                if($defLang==substr($lang[$i],0,5)){
                    echo "<option value='".$lang[$i]."' selected='selected'>".$this->getNameLang($lang[$i])."</option>";
                }else{
                    echo "<option value='".$lang[$i]."'>".$this->getNameLang($lang[$i])."</option>";
                }
            }
        }
        echo '</select></span>';
    }
}
?>
