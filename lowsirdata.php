<?php
/**
 * Copyright © 2024-2026 Sirdata
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@sirdata.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Sirdata <support@sirdata.com>
 * @copyright 2024-2026 Sirdata
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Lowsirdata extends Module
{
    // affichage de la configuration du module
    public function __construct()
    {
        $this->name = 'lowsirdata';
        $this->tab = 'front_office_features';
        $this->version = '1.0.3';
        $this->author = 'Sirdata';
        $this->bootstrap = true;
        $this->need_instance = 0;
        $this->_html = '';
        $this->path = _PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR;
        $this->confirmUninstall = $this->l('Êtes-vous sûr(e) de vouloir désinstaller le module?');
        $this->name_config = strtoupper($this->name);

        parent::__construct();

        $this->displayName = $this->l('Sirdata CMP');
        $this->description = $this->l('Sirdata CMP is a Consent Management Platform made to reply to privacy regulations such like GDPR, ePrivacy, CCPA and already used by thousands of websites.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        // Ajouter un formule sur la configuration du module
        $this->addConfigForm($this->getFormConfiguration());
    }

    public $configDisplayName = 'Sirdata CMP';
    public $configDescription = 'Sirdata CMP is a Consent Management Platform made to reply to privacy regulations such like GDPR, ePrivacy, CCPA and already used by thousands of websites.';

    const CONFIG_COMPTE = 'cmp_id_compte';
    const CONFIG_ID_SITE = 'cmp_id_site';

    // Installation des hooks
    protected $_hook = [
        // Nom du hook
        'displayHeader' => [
            'position' => 1, // Position du hook
        ],
    ];

    public function getContent()
    {
        // Sauvegarde des données submit
        $this->addConfigForm($this->getFormConfigurationDev());

        $this->checkSubmit([$this->config_form]);

        if (version_compare(_PS_VERSION_, '1.6.0', '<')) {
            $this->_html .= '<link rel="stylesheet" href="/modules/lowsirdata/views/css/back.css" type="text/css" media="all" />';
        } else {
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }

        $this->smarty->assign([
            'img_module_dir' => $this->_path,
        ]);

        $this->_html .= $this->renderForm($this->config_form);
        $this->_html .= $this->getHtmlSirDataInfo();

        return $this->_html;
    }

    public function getFormConfiguration()
    {
        return [
            'form' => [
                'tab_name' => 'config_tab',
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'infos' => 'test ici',
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('SirData Account ID'),
                        'name' => self::getConfigName(self::CONFIG_COMPTE),
                        'desc' => '',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Site ID'),
                        'name' => self::getConfigName(self::CONFIG_ID_SITE),
                        'desc' => '',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    public function getHtmlSirDataInfo()
    {
        return $this->display($this->_path, 'admin-info.tpl');
    }

    public function hookDisplayHeader()
    {
        $idCompteCMP = self::getConfig(self::CONFIG_COMPTE);
        $idSiteCMP = self::getConfig(self::CONFIG_ID_SITE);

        if ($idCompteCMP && $idCompteCMP !== '') {
            $this->smarty->assign([
                'lowsirdata_cache' => 'https://cache.consentframework.com/js/pa/' . $idCompteCMP . '/c/' . $idSiteCMP . '/stub',
                'lowsirdata_choices' => 'https://choices.consentframework.com/js/pa/' . $idCompteCMP . '/c/' . $idSiteCMP . '/cmp',
            ]);

            return $this->display($this->_path, 'header.tpl');
        }

        return '';
    }

    const ADD = 'a';
    const REMOVE = 'd';
    const UPDATE = 'u';

    public $dataValueCompatibility = [];
    public $path;
    public $html;
    public $_html = '';
    public $name_config = '';
    public $config_form = [];
    protected $_customField = [];

    public $controllers = [];
    public $folderImg = '';
    public $rewriteControllers = [];
    protected $_tabController = [];
    protected $_tabsV2 = [];
    protected $_hook_other_module = [];
    protected $_unregister_hook_other_module = [];

    public static function getName()
    {
        return strtolower(get_called_class());
    }

    public static function getNameUpper()
    {
        return strtoupper(self::getName());
    }

    public function addConfigForm($form)
    {
        array_push($this->config_form, $form);
    }

    public function migrateUp()
    {
        Configuration::updateValue($this->name_config . '_ACTIVE', 1);

        $this->migrateUpBdd();

        return true;
    }

    public function migrateUpBdd()
    {
    }

    public function migrateDownBdd()
    {
    }

    public function migrateDown()
    {
        $this->migrateDownBdd();

        return true;
    }

    public function install()
    {
        return parent::install()
            && $this->overrideSetup(self::ADD)
            && $this->migrateUp()
            && $this->controllerSetup(self::ADD)
            && $this->installTab()
            && $this->hookSetup(self::ADD)
            && $this->tabSetup(self::ADD);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->migrateDown()
            && $this->controllerSetup(self::REMOVE)
            && $this->hookSetup(self::REMOVE)
            && $this->tabSetup(self::REMOVE)
            && $this->uninstallTab()
            && $this->overrideSetup(self::REMOVE);
    }

    private function installTab()
    {
        if (isset($this->_tabsV2) && count($this->_tabsV2)) {
            foreach ($this->_tabsV2 as $itemTab) {
                $tabId = (int) Tab::getIdFromClassName($itemTab['class_name']);
                if (!$tabId) {
                    $tabId = null;
                }

                $tab = new Tab($tabId);
                $tab->active = 1;
                $tab->class_name = $itemTab['class_name'];
                $tab->route_name = $itemTab['route_name'];
                $tab->name = [];

                if (is_array($itemTab['name'])) {
                    $defaultName = current($itemTab['name']);
                    foreach (Language::getLanguages() as $lang) {
                        $tab->name[$lang['id_lang']] = (isset($itemTab['name'][strtolower($lang['iso_code'])]) ? $itemTab['name'][strtolower($lang['iso_code'])] : $defaultName);
                    }
                } else {
                    foreach (Language::getLanguages() as $lang) {
                        $tab->name[$lang['id_lang']] = $itemTab['name'];
                    }
                }

                $tab->id_parent = (int) Tab::getIdFromClassName($itemTab['parent_class_name']);
                $tab->module = $this->name;

                $tab->save();
            }
        }

        return true;
    }

    private function uninstallTab()
    {
        if (isset($this->_tabsV2) && count($this->_tabsV2)) {
            foreach ($this->_tabsV2 as $itemTab) {
                $tabId = (int) Tab::getIdFromClassName($itemTab['class_name']);
                if (!$tabId) {
                    continue;
                }

                $tab = new Tab($tabId);

                $tab->delete();
            }
        }

        return true;
    }

    private function tabSetup($action)
    {
        $pass = true;

        return $pass;
    }

    private function controllerSetup($action)
    {
        if ($action == self::ADD) {
            $id_lang = $this->context->language->id;

            foreach ($this->controllers as $controller) {
                $name = 'module-' . self::getName() . '-' . $controller;
                /** @var MetaCore $meta */
                $metaReq = MetaCore::getMetaByPage($name, $id_lang);

                if ($metaReq) {
                    $meta = new MetaCore($metaReq['id_meta']);

                    foreach ($this->rewriteControllers[$controller]['meta_lang'] as $id_lang => $meta_lang) {
                        $meta->url_rewrite[$id_lang] = $meta_lang['url_rewrite'];

                        if (isset($meta_lang['title'])) {
                            $meta->title[$id_lang] = $meta_lang['title'];
                        }

                        if (isset($meta_lang['description'])) {
                            $meta->description[$id_lang] = $meta_lang['description'];
                        }

                        if (isset($meta_lang['keywords'])) {
                            $meta->keywords[$id_lang] = $meta_lang['keywords'];
                        }
                    }
                } else {
                    $meta = new MetaCore();
                    $meta->page = $name;
                    $meta->configurable = 1;

                    $meta->url_rewrite = [];
                    $meta->title = [];
                    $meta->description = [];
                    $meta->keywords = [];

                    foreach ($this->rewriteControllers[$controller]['meta_lang'] as $id_lang => $meta_lang) {
                        $meta->url_rewrite[$id_lang] = $meta_lang['url_rewrite'];

                        if (isset($meta_lang['title'])) {
                            $meta->title[$id_lang] = $meta_lang['title'];
                        }

                        if (isset($meta_lang['description'])) {
                            $meta->description[$id_lang] = $meta_lang['description'];
                        }

                        if (isset($meta_lang['keywords'])) {
                            $meta->keywords[$id_lang] = $meta_lang['keywords'];
                        }
                    }
                }

                $meta->save();
            }
        }

        if ($action == self::REMOVE) {
        }

        return true;
    }

    /**
     * Set up hooks
     *
     * @param string $action
     *
     * @return bool
     */
    private function hookSetup($action)
    {
        $expected_hooks = $this->_hook;

        $hookClass = new Hook();

        $pass = true;

        if ($action == self::ADD) {
            foreach ($expected_hooks as $expected_hook => $expected_hook_data) {
                if (!$this->registerHook($expected_hook)) {
                    $pass = false;
                }

                if (isset($expected_hook_data['position'])) {
                    if (version_compare(_PS_VERSION_, '1.6.0', '<')) {
                        $id_hook = $hookClass::get($expected_hook);
                    } else {
                        $id_hook = $hookClass::getIdByName($expected_hook);
                    }

                    $this->updatePosition($id_hook, 0, $expected_hook_data['position']);
                }
            }

            if (count($this->_hook_other_module)) {
                foreach ($this->_hook_other_module as $module_name => $module_hook) {
                    $module = Module::getInstanceByName($module_name);

                    if ($module) {
                        foreach ($module_hook as $expected_hook => $expected_hook_data) {
                            if (!$module->registerHook($expected_hook)) {
                                $pass = false;
                            }

                            if (isset($expected_hook_data['position'])) {
                                if (version_compare(_PS_VERSION_, '1.6.0', '<')) {
                                    $id_hook = $hookClass::get($expected_hook);
                                } else {
                                    $id_hook = $hookClass::getIdByName($expected_hook);
                                }

                                $module->updatePosition($id_hook, 0, $expected_hook_data['position']);
                            }
                        }
                    }
                }
            }

            if (count($this->_unregister_hook_other_module)) {
                foreach ($this->_unregister_hook_other_module as $module_name => $module_hook) {
                    $module = Module::getInstanceByName($module_name);

                    if ($module) {
                        foreach ($module_hook as $expected_hook => $expected_hook_data) {
                            if (!$module->unregisterHook($expected_hook)) {
                                $pass = false;
                            }
                        }
                    }
                }
            }
        }

        if ($action == self::REMOVE) {
            foreach ($expected_hooks as $expected_hook => $expected_hook_data) {
                if (!$this->unregisterHook($expected_hook)) {
                    $pass = false;
                }
            }
            foreach ($this->_hook_other_module as $module_name => $module_hook) {
                $module = Module::getInstanceByName($module_name);
                if ($module) {
                    foreach ($module_hook as $expected_hook => $expected_hook_data) {
                        if (!$module->unregisterHook($expected_hook)) {
                            $pass = false;
                        }
                    }
                }
            }
        }

        return $pass;
    }

    private function overrideSetup($action)
    {
        $pass = true;

        if ($action == self::ADD) {
            try {
                $this->installOverrides();
            } catch (Exception $e) {
                $pass = true;
                $this->_html .= $this->displayError('Erreur sur l\'installation automatique de l\'override : ' . $e->getMessage());
            }
        }

        if ($action == self::REMOVE) {
            try {
                $this->uninstallOverrides();
            } catch (Exception $e) {
                $pass = false;
            }
        }

        $this->clearAllCache();

        return $pass;
    }

    /**
     * Gestion du submit de la configuration du module
     */
    public function checkSubmit($forms)
    {
        if (Tools::isSubmit('submitUpdate')) {
            foreach ($forms as $form) {
                $this->setConfigFieldsValues($form);
            }
            $this->clearCacheModule();
            $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
        }

        if (Tools::isSubmit('resetHook')) {
            $this->hookSetup(self::REMOVE);
            $this->hookSetup(self::ADD);
            $this->_html .= $this->displayConfirmation($this->l('Hook Reset !'));
        }

        if (Tools::isSubmit('resetBDD')) {
            $this->migrateDown();
            $this->migrateUp();

            $this->_html .= $this->displayConfirmation($this->l('BDD Reset ! '));
        }

        if (Tools::isSubmit('resetTab')) {
            $this->tabSetup(self::REMOVE);
            $this->tabSetup(self::ADD);

            $this->_html .= $this->displayConfirmation($this->l('Tab Reset ! '));
        }

        if (Tools::isSubmit('resetOverride')) {
            $this->overrideSetup(self::REMOVE);
            $this->overrideSetup(self::ADD);

            $this->_html .= $this->displayConfirmation($this->l('Override Reset ! '));
        }

        if (Tools::isSubmit('resetControllerFront')) {
            $this->controllerSetup(self::REMOVE);
            $this->controllerSetup(self::ADD);

            $this->_html .= $this->displayConfirmation($this->l('Controller Front Reset ! '));
        }

        if (Tools::isSubmit('resetAuthorization')) {
            $this->autorisationSetup(self::REMOVE);
            $this->autorisationSetup(self::ADD);

            $this->_html .= $this->displayConfirmation($this->l('Authorization Reset ! '));
        }
    }

    public function autorisationSetup($actionSetup, $name = '')
    {
        $name = empty($name) ? $this->name : $name;
        if ($actionSetup == self::ADD) {
            // Permissions management
            foreach (['CREATE', 'READ', 'UPDATE', 'DELETE'] as $action) {
                $slug = 'ROLE_MOD_MODULE_' . strtoupper($name) . '_' . $action;

                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'authorization_role` (`slug`) VALUES ("' . $slug . '")'
                );

                Db::getInstance()->execute('
                INSERT INTO `' . _DB_PREFIX_ . 'module_access` (`id_profile`, `id_authorization_role`) (
                    SELECT id_profile, "' . Db::getInstance()->Insert_ID() . '"
                    FROM ' . _DB_PREFIX_ . 'access a
                    LEFT JOIN `' . _DB_PREFIX_ . 'authorization_role` r
                    ON r.id_authorization_role = a.id_authorization_role
                    WHERE r.slug = "ROLE_MOD_TAB_ADMINMODULESSF_' . $action . '"
            )');
            }
        } else {
            // Permissions management
            foreach (['CREATE', 'READ', 'UPDATE', 'DELETE'] as $action) {
                $slug = 'ROLE_MOD_MODULE_' . strtoupper($name) . '_' . $action;

                $rowAuth = Db::getInstance()->getValue('SELECT `id_authorization_role` FROM `' . _DB_PREFIX_ . 'authorization_role` WHERE `slug` ="' . $slug . '"');

                if ($rowAuth) {
                    Db::getInstance()->execute(
                        'DELETE FROM `' . _DB_PREFIX_ . 'module_access` WHERE `id_authorization_role` ="' . $rowAuth . '"'
                    );

                    Db::getInstance()->execute(
                        'DELETE FROM `' . _DB_PREFIX_ . 'authorization_role` WHERE `slug` ="' . $slug . '"'
                    );
                }
            }
        }
    }

    /**
     * Retourne les valeurs du formulaire
     *
     * @return bool
     */
    public function setConfigFieldsValues($formData)
    {
        $languages = Language::getLanguages(false);
        foreach ($formData as $form) {
            foreach ($form['form']['input'] as $input) {
                if (isset($input['lang']) && $input['lang']) {
                    $valueLang = [];
                    /** @var LanguageCore $lang */
                    foreach ($languages as $lang) {
                        if (Tools::getValue($input['name'] . '_' . $lang['id_lang']) !== false) {
                            $valueLang[$lang['id_lang']] = Tools::getValue($input['name'] . '_' . $lang['id_lang']);
                        }
                    }
                    Configuration::updateValue($input['name'], $valueLang, true);
                } else {
                    if ($this->isCustomField($input['type'])) {
                        $this->updateDataCustomField($input);
                    } else {
                        if (Tools::getValue($input['name']) !== false) {
                            if (isset($input['data_type']) && $input['data_type'] == 'array') {
                                $value = implode(',', Tools::getValue($input['name']));
                            } else {
                                $value = Tools::getValue($input['name']);
                            }

                            Configuration::updateValue($input['name'], $value, true);
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Retourne le tableau pour construire le formulaire
     *
     * @return array
     */
    public function getFormConfigurationDev()
    {
        return [
            'form' => [
                'tab_name' => 'configdev_tab',
                'legend' => [
                    'title' => $this->l('Developper Mode'),
                    'icon' => 'icon-code',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activer le module'),
                        'name' => self::getNameUpper() . '_ACTIVE',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->l('Dev'),
                        'name' => 'dev',
                        'html_content' => $this->getHtmlButtonDev(),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * @param $name
     *
     * @return string
     */
    public static function getConfigName($name)
    {
        return self::getNameUpper() . '_' . strtoupper($name);
    }

    public static function getConfig($name, $id_lang = null)
    {
        return Configuration::get(self::getConfigName($name), $id_lang);
    }

    /**
     * Retourne les valeurs du formulaire
     *
     * @return array
     */
    public function getConfigFieldsValues($formData)
    {
        $data = [];
        $languages = Language::getLanguages(false);
        foreach ($formData as $form) {
            foreach ($form['form']['input'] as $input) {
                switch (true) {
                    case isset($input['lang']) && $input['lang']:
                        $data[$input['name']] = [];
                        /** @var LanguageCore $lang */
                        foreach ($languages as $lang) {
                            $data[$input['name']][$lang['id_lang']] = Configuration::get($input['name'], $lang['id_lang']);
                        }
                        break;
                    case isset($input['data_type']) && $input['data_type'] == 'array':
                        $data[$input['name'] . (isset($input['multiple']) && $input['multiple'] ? '[]' : '')] = explode(',', Configuration::get($input['name']));
                        break;
                    case $this->isCustomField($input['type']):
                        $data[$input['name']] = $this->setDataCustomField($input);
                        break;
                    default:
                        $data[$input['name']] = Configuration::get($input['name']);
                        break;
                }
            }
        }

        return $data;
    }

    public function getConfigFormValues(&$formData)
    {
        $data = [];
        $languages = Language::getLanguages(false);
        foreach ($formData as &$form) {
            foreach ($form['form']['input'] as &$input) {
                switch (true) {
                    case $input['type'] == 'categories':
                        $input['tree']['selected_categories'] = explode(',', Configuration::get($input['name']));
                        break;
                    case isset($input['data_type']) && $input['data_type'] == 'array':
                        $data[$input['name']] = explode(',', Configuration::get($input['name']));
                        break;
                    case $this->isCustomField($input['type']):
                        $data[$input['name']] = $this->setDataCustomField($input);
                        break;
                    default:
                        $data[$input['name']] = Configuration::get($input['name']);
                        break;
                }
            }
        }

        return $data;
    }

    /**
     * Permet de sauvegarder les valeurs des inputs Custom si besoin
     *
     * @param array $input
     *
     * @return array|string
     */
    protected function updateDataCustomField($input)
    {
        switch ($input['type']) {
            default:
                return '';
        }
    }

    /**
     * Permet de setter les valeurs des inputs Custom si besoin
     *
     * @param array $input
     *
     * @return array|string
     */
    protected function setDataCustomField($input)
    {
        switch ($input['type']) {
            default:
                return '';
        }
    }

    /**
     * Permet de savoir si c'est un type d'input custom ou pas
     *
     * @param string $fieldType
     *
     * @return bool
     */
    protected function isCustomField($fieldType = 'text')
    {
        return in_array($fieldType, $this->_customField);
    }

    public function renderForm($form)
    {
        $this->dataValueCompatibility = $this->getConfigFormValues($form);

        /** @var HelperFormCore $helper */
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitUpdate';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues($form),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $formHtml = $helper->generateForm($form);

        return $formHtml;
    }

    public function getHtmlButtonDev()
    {
        return $this->display($this->_path, 'admin-button-dev.tpl');
    }

    public function clearCacheModule()
    {
    }

    public function clearAllCache()
    {
        if (method_exists('Tools', 'clearSmartyCache')) {
            Tools::clearSmartyCache();
        }

        if (method_exists('Tools', 'clearXMLCache')) {
            Tools::clearXMLCache();
        }

        if (method_exists('Media', 'clearCache')) {
            Media::clearCache();
        }

        if (method_exists('Tools', 'generateIndex')) {
            Tools::clearXMLCache();
        }
    }
}
