<?php
 
if (!defined('_PS_VERSION_')) { //Mandatory -> check that the environment have been loaded
    exit;
}
class Vm_GitCommit extends Module
{
    public function __construct()
    {
       
        $this->name = 'vm_gitcommit'; //define name of module
        $this->tab = 'front_office_features'; 
        $this->version = '1.0.0';
        $this->author = 'Vivien Marcadé';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;
 
        parent::__construct(); //call the constructor of parent's class (Module class)
 
        $this->displayName = $this->l('Module Git Commit'); //title on BO
        $this->description = $this->l('Show commits from git account');
 
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
 
        if (!Configuration::get('VM_GITCOMMIT_ACCOUNT')) { //check if a configuration is existing
            $this->warning = $this->l('Aucun nom fourni');
        }
    }
    public function install()
    {
        if (Shop::isFeatureActive()) { //check if multi-shop activated. If yes define the context for all of them
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook('home') ||
            !$this->registerHook('header') ||
            !Configuration::updateValue('VM_GITCOMMIT_ACCOUNT', 'Compte GitHub')||
            !Configuration::updateValue('VM_GITCOMMIT_DEPOSITE', 'dépot GitHub')||
            !Configuration::updateValue('VM_GITCOMMIT_COMMITCOUNT', 'Nombre de commit GitHub')
        ) {
            return false;
        }

        return true;
    }
    /*exemple to load an array
     * // Enregistre un tableau dans la table Configuration
        Configuration::updateValue('VM_GITCOMMIT_SETTINGS', serialize(array(true, true, false)));
    // Le récupère
        $configuration_array = unserialize(Configuration::get('VM_GITCOMMIT_SETTINGS'));
     */
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('VM_GITCOMMIT_ACCOUNT') || //allow to check the BDD, table ps_configuration
            !Configuration::deleteByName('VM_GITCOMMIT_DEPOSITE') || 
            !Configuration::deleteByName('VM_GITCOMMIT_COMMITCOUNT')
        ) {
            return false;
        }

        return true;
    }
    public function getContent() //create a form in BO with cfg button
    {
        $output = null;

        if (Tools::isSubmit('btnSubmit')) {
            $accountGitHUb = strval(Tools::getValue('VM_GITCOMMIT_ACCOUNT'));
            $depositeGitHub = strval(Tools::getValue('VM_GITCOMMIT_DEPOSITE'));
            $commitNumbers = strval(Tools::getValue('VM_GITCOMMIT_COMMITCOUNT'));
            if (
                !$accountGitHUb||
                empty($accountGitHUb)||
                !$depositeGitHub||
                empty($depositeGitHub)||
                !$commitNumbers||
                empty($commitNumbers)
            ) {
                $output .= $this->displayError($this->l('Invalid Configuration value')); // .= php method to appends in $output
            } else {
                Configuration::updateValue('VM_GITCOMMIT_ACCOUNT', $accountGitHUb); 
                Configuration::updateValue('VM_GITCOMMIT_DEPOSITE', $depositeGitHub);
                Configuration::updateValue('VM_GITCOMMIT_COMMITCOUNT', $commitNumbers); 
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output.$this->displayForm();
    }
    public function displayForm()
    {
        // Récupère la langue par défaut
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Initialise les champs du formulaire dans un tableau
        $form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Configuration value'),
                        'name' => 'VM_GITCOMMIT_ACCOUNT',
                        'size' => 20,
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Configuration value'),
                        'name' => 'VM_GITCOMMIT_DEPOSITE',
                        'size' => 20,
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Configuration value'),
                        'name' => 'VM_GITCOMMIT_COMMITCOUNT',
                        'size' => 20,
                        'required' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name'  => 'btnSubmit'
                )
            ),
        );

        $helper = new HelperForm(); 

        // Module, token & currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'& configure='.$this->name;

        // Langue
        $helper->default_form_language = $defaultLang;

        // Charge la valeur de VM_GITCOMMIT_ACCOUNT depuis la base
        $helper->fields_value['VM_GITCOMMIT_ACCOUNT'] = Configuration::get('VM_GITCOMMIT_ACCOUNT');
        $helper->fields_value['VM_GITCOMMIT_DEPOSITE'] = Configuration::get('VM_GITCOMMIT_DEPOSITE');
        $helper->fields_value['VM_GITCOMMIT_COMMITCOUNT'] = Configuration::get('VM_GITCOMMIT_COMMITCOUNT');

        return $helper->generateForm(array($form));
    }
    public function hookDisplayHome($params)
    {
        $ch = curl_init();
        
        $certificate_location = 'F:\www\wamp64\www\gitcommit\modules\vm_gitcommit\cert\cacert-2020-10-14.pem'; //mandatory to create a curl connection
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $certificate_location);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $certificate_location);

        curl_setopt($ch, CURLOPT_USERAGENT, 'trentaTest');     
        curl_setopt_array($ch, array(
            CURLOPT_URL => "https://api.github.com/repos/trentaTest/gitCommit/commits",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer enter your own token"
            ),
        ));
        
        $response = curl_exec($ch);
        /* To debug curl connection
        $err = curl_error($ch);
        curl_close($ch);
        $errNo = curl_errno($ch);
        $description = curl_strerror(curl_errno($ch)); 
        var_dump($description);
         */

        $gitCommit = json_decode($response);

        $this->context->smarty->assign([
            'gitCommit' => $gitCommit,
            'vm_page_name' => Configuration::get('VM_GITCOMMIT_DEPOSITE'),
            'vm_page_link' => $this->context->link->getModuleLink('vm_gitcommit', 'display'),
        ]);
        var_dump(__TRAIT__);
        return $this->display(__FILE__, 'vm_gitcommit.tpl');
    }
    public function hookDisplayHeader()
    {        
        $this->context->controller->registerStylesheet(
            'vm_gitcommit',
            $this->_path.'views/css/vm_gitcommit.css',
            ['server' => 'remote', 'position' => 'head', 'priority' => 150]
        );
    }
    public function GetApiGitHub() 
    {
        $ch = curl_init();
        
        $certificate_location = 'F:\www\wamp64\www\gitcommit\modules\vm_gitcommit\cert\cacert-2020-10-14.pem'; //mandatory to create a curl connection
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $certificate_location);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $certificate_location);

        curl_setopt($ch, CURLOPT_USERAGENT, 'trentaTest');     
        curl_setopt_array($ch, array(
            CURLOPT_URL => "https://api.github.com/repos/trentaTest/gitCommit/commits",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 100,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer enter your own token"
            ),
        ));
        
        $response = curl_exec($ch);
        /* To debug curl connection
        $err = curl_error($ch);
        curl_close($ch);
        $errNo = curl_errno($ch);
        $description = curl_strerror(curl_errno($ch)); 
        var_dump($description);
         */

        $gitCommit = json_decode($response);

        
        $this->context->smarty->assign(['gitCommit' => $gitCommit]);
        $pathOfView = 'F:\www\wamp64\www\gitcommit\modules\vm_gitcommit\views\templates\front\\';
        return $this->display(_MODULE_DIR_, 'vm_gitcommit/views/templates/front/display.tpl');        //_MODULE_DIR_	-> /prefix/modules/
        //return $this->display($pathOfView, 'display.tpl');  
        
    }    
}
