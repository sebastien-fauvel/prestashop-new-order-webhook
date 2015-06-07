<?php

if (!defined("_PS_VERSION_"))
    exit;

class NewOrderWebhook extends Module {

    public function __construct() {
        $this->name = "neworderwebhook";
        $this->tab = "checkout";
        $this->version = "0.1";
        $this->author = "Bob Maerten";
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6');
        $this->bootstrap = true;
        $this->dependencies = array();

        parent::__construct();

        $this->displayName = $this->l("New Order Webhook");
        $this->description = $this->l("Fires a POST request to a pre-defined URL upon customer order validation.");

        $this->confirmUninstall = $this->l("Are you sure you want to uninstall?");

        if (!Configuration::get('NEW_ORDER_WEBHOOK_URL'))
            $this->warning = $this->l('No URL provided.');
    }

    public function install() {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        return parent::install() &&
                $this->registerHook('actionValidateOrder') &&
                Configuration::updateValue('NEW_ORDER_WEBHOOK_URL', 'http://www.example.com/new-order-webhook');
    }

    public function uninstall() {
        return parent::uninstall() &&
                Configuration::deleteByName('NEW_ORDER_WEBHOOK_URL') &&
                $this->unregisterHook('actionValidateOrder');
    }

    public function hookActionValidateOrder($params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Configuration::get('NEW_ORDER_WEBHOOK_URL'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_exec($ch);
        curl_close($ch);
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            $my_module_name = strval(Tools::getValue('NEW_ORDER_WEBHOOK_URL'));
            if (!$my_module_name
                || empty($my_module_name)
                || !Validate::isGenericName($my_module_name))
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            else
            {
                Configuration::updateValue('NEW_ORDER_WEBHOOK_URL', $my_module_name);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Webhook POST URL'),
                    'name' => 'NEW_ORDER_WEBHOOK_URL',
                    'size' => 20,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
                'back' => array(
                    'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list')
                )
            );

        // Load current value
        $helper->fields_value['NEW_ORDER_WEBHOOK_URL'] = Configuration::get('NEW_ORDER_WEBHOOK_URL');

        return $helper->generateForm($fields_form);
    }
}
