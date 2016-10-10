<?php
/**
 * Get Free Shipping PrestaShop module.
 *
 * @author    Niels Wouda <n.wouda@apium.nl>
 * @copyright 2016, Apium
 * @license   Academic Free License (AFL 3.0) <http://opensource.org/licenses/afl-3.0.php>
 */

defined('_PS_VERSION_') || exit;


class GetFreeShipping extends Module
{

    public function __construct()
    {
        $this->name = 'getfreeshipping';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Apium | Niels Wouda';

        $this->need_instance = false;
        $this->bootstrap = true;

        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->displayName = $this->l('Get Free Shipping');
        $this->description = $this->l('Display "spend another x" to get free shipping message in cart.');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()) {
            return false;
        }

        return $this->registerHook('shoppingCart')
            && Configuration::updateValue('GETFREESHIPPING_THRESHOLD', 0);
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        return $this->unregisterHook('shoppingCart')
            && Configuration::deleteByName('GETFREESHIPPING_THRESHOLD');
    }

    public function getContent()
    {
        $output = '';

        // free shipping is not enabled..
        if (!Configuration::get('PS_SHIPPING_FREE_PRICE')) {
            $output .= $this->displayWarning($this->l('This module can only be meaningfully used if you offer the free shipping option on your shop!'));
        }

        if (Tools::isSubmit('GetFreeShippingSubmit') && $this->_postValidation()) {
            // replace comma by dot, for user convenience
            if (Configuration::updateValue('GETFREESHIPPING_THRESHOLD', str_replace(',', '.', Tools::getValue('GETFREESHIPPING_THRESHOLD')))) {
                $output .= $this->displayConfirmation($this->l('Threshold updated succesfully!'));
            } else {
                $output .= $this->displayError($this->l('Something went wrong updating the threshold, are you sure it is a number?'));
            }
        }

        return $output . $this->renderForm();
    }

    public function hookShoppingCart($params)
    {
        $free_shipping = Configuration::get('PS_SHIPPING_FREE_PRICE');

        if (!$free_shipping) {
            return false;
        }

        $free_shipping_from = Tools::convertPrice($free_shipping);
        $total_without_shipping = $this->context->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
        $remaining = $free_shipping_from - $total_without_shipping;

        $this->context->smarty->assign(
            array(
                'remaining' => $remaining,
                'remaining_threshold' => (float) Configuration::get('GETFREESHIPPING_THRESHOLD')
            )
        );

        return $this->display(__FILE__, 'views/templates/hook/getfreeshipping.tpl');
    }

    private function renderForm()
    {
        $settings = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cog'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Threshold'),
                        'name' => 'GETFREESHIPPING_THRESHOLD',
                        'desc' => $this->l('What is the threshold for showing "spend x for free shipping"?'),
                        'hint' => $this->l('This is the maximum value for "x" in "spend x".'),
                        'required' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            )
        );

        return $this->renderFormUtil($settings, 'GetFreeShippingSubmit');
    }

    private function renderFormUtil($form, $submit_action)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = $submit_action;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => array(
                'GETFREESHIPPING_THRESHOLD' => Tools::getValue('GETFREESHIPPING_THRESHOLD', Configuration::get('GETFREESHIPPING_THRESHOLD')),
            ),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($form));
    }

    private function _postValidation()
    {
        // replace comma by dot, for user convenience
        $threshold_candidate = str_replace(',', '.', Tools::getValue('GETFREESHIPPING_THRESHOLD'));

        return Validate::isFloat($threshold_candidate) || Validate::isInt($threshold_candidate);
    }
}
