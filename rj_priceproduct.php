<?php

/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2021 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Rj_PriceProduct extends Module
{
    protected $_html = '';

    public function __construct()
    {
        $this->name = 'rj_priceproduct';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Roanja';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Roanja Price Product');
        $this->description = $this->l('Massively change the prices of the products keeping the current price in the database.');

        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];

    }

    /**
     * Instalación del modulo
     *
     * @return void
     */
    public function install()
    {
        return parent::install();
    }

    /**
     * Desinstalar el modulo
     *
     * @return void
     */
    public function uninstall()
    {
        Configuration::deleteByName('RJ_PRICE_INCREMENT');
        Configuration::deleteByName('RJ_PRICE_INCREMENT_TYPE');

        if (parent::uninstall()) {
            return true;
        }

        return false;
    }

    /**
     * Vista de configuración del modulo
     *
     * @return html
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitPriceIncrement')) {
            $this->postProcess();
        }

        $this->_html .= $this->renderFormPrice();

        return $this->_html;
    }

    /**
     * Procesos de eventos
     *
     * @return html
     */
    protected function postProcess()
    {
        $errors = [];
        $shop_context = Shop::getContext();
        $res = true;
        $form_values = [];

        if(Tools::isSubmit('submitPriceIncrement')){
            $form_values = $this->getConfigFieldsFormPrice();

            $shop_groups_list = array();
            $shops = Shop::getContextListShopID();

            foreach ($shops as $shop_id) {
                $shop_group_id = (int)Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }
                
                foreach (array_keys($form_values) as $key) {
                    $res &= Configuration::updateValue($key, Tools::getValue($key), false, $shop_group_id, $shop_id);
                }
            }

            /* Update global shop context if needed*/
            switch ($shop_context) {
                case Shop::CONTEXT_ALL:
                    foreach (array_keys($form_values) as $key) {
                        $res &= Configuration::updateValue($key, Tools::getValue($key));
                    }
                    if (count($shop_groups_list)) {
                        foreach ($shop_groups_list as $shop_group_id) {
                            foreach (array_keys($form_values) as $key) {
                                $res &= Configuration::updateValue($key, Tools::getValue($key), false, $shop_group_id);
                            }
                        }
                    }
                    break;
                case Shop::CONTEXT_GROUP:
                    if (count($shop_groups_list)) {
                        foreach ($shop_groups_list as $shop_group_id) {
                            foreach (array_keys($form_values) as $key) {
                                $res &= Configuration::updateValue($key, Tools::getValue($key), false, $shop_group_id);
                            }
                        }
                    }
                    break;
            }

            Tools::clearSmartyCache();

            if (!$res) {
                $this->_html .=  $this->displayError($this->getTranslator()->trans('The configuration could not be updated.', array(), 'Modules.Rj_PriceProduct.Admin'));
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=6&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
            }
        }
    }

    /**
     * Formulario de configuración de incremento de precios del catálogo 
     *
     * @return html
     */
    public function renderFormPrice()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->getTranslator()->trans('Settings prices', array(), 'Modules.Rj_makitosync.Admin'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Increment', array(), 'Modules.Rj_makitosync.Admin'),
                        'name' => 'RJ_PRICE_INCREMENT',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->getTranslator()->trans('Valor de incremento en precio.', array(), 'Modules.Rj_makitosync.Admin')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Tipo de incremento', array(), 'Modules.Rj_makitosync.Admin'),
                        'name' => 'RJ_PRICE_INCREMENT_TYPE',
                        'desc' => $this->getTranslator()->trans('Seleccione SI = Porcentaje ó NO = Valor', array(), 'Modules.Rj_makitosync.Admin'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Porcentaje', array(), 'Modules.Rj_makitosync.Admin')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('Valor', array(), 'Modules.Rj_makitosync.Admin')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->getTranslator()->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPriceIncrement';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsFormPrice(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Devuelve los datos guardados de incremento de precios del catálogo
     *
     * @return array
     */
    public function getConfigFieldsFormPrice()
    {
        return [
            'RJ_PRICE_INCREMENT' => Configuration::get('RJ_PRICE_INCREMENT', true),
            'RJ_PRICE_INCREMENT_TYPE' => Configuration::get('RJ_PRICE_INCREMENT_TYPE', true),
        ];
    }


    public static function incrementPriceRoanja($price)
    {
        $price_increment = (float)Configuration::get('RJ_PRICE_INCREMENT', true);
        if (Configuration::get('RJ_PRICE_INCREMENT_TYPE', true)) {
            $price += $price * $price_increment / 100;
        } else {
            $price += $price_increment;
        }
         return $price;
    }
}