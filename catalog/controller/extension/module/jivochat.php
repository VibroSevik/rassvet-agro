<?php

class ControllerExtensionModuleJivoChat extends Controller
{
    protected $error = array();

    const SCRIPT_PATTERN =
        '<script>
            var jivoWidgetId = "%s";
            var jivoStatus = "%s";
            var jivoLogged = "%s";
            var jivoName = "%s";
            var jivoEmail = "%s";
            var jivoPhone = "%s";
            var jivoDescription = "%s";
         </script>';

    const JIVOCHAT_STATUS = 'jivochat_status';

    public function header(&$route, &$args, &$output)
    {
        if ($this->config->get(self::JIVOCHAT_STATUS)) {
            $jivoName = '';
            $jivoEmail = '';
            $jivoPhone = '';
            $jivoDescription = '';
            if ($this->customer->isLogged()) {
                $jivoName = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
                $jivoEmail = $this->customer->getEmail();
                $jivoPhone = $this->customer->getTelephone();
                $this->load->model('account/address');
                $address = $this->model_account_address->getAddress($this->customer->getAddressId());
                $jivoDescription = $address['postcode'] . ' ' . $address['city'] . ' ' . $address['address_1'];
            }
            $args['analytics'][] = sprintf(
                self::SCRIPT_PATTERN,
                $this->config->get('jivochat_widget_id'),
                true,
                $this->customer->isLogged(),
                $jivoName,
                $jivoEmail,
                $jivoPhone,
                $jivoDescription
            );
        }
    }

    public function footer(&$route, &$args, &$output)
    {
        if ($this->config->get(self::JIVOCHAT_STATUS)) {
            $args['scripts'][] = 'catalog/view/javascript/jivochat/JivoChat.js';
        }
    }

}