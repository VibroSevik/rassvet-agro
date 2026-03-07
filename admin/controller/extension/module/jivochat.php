<?php

class ControllerExtensionModuleJivoChat extends Controller
{
    /** @var array  */
    protected $error = [];

    const INSTALL_URI = 'https://api.jivosite.com/web/integration/install';
    const LOGIN_PATH = 'extension/module/jivochat/login';
    const BASE_PATH = 'extension/module/jivochat';
    
    //fields
    const FIELD_PASSWORD = 'jivochat_userPassword';
    const FIELD_SITE_URL = 'jivochat_siteUrl';
    const FIELD_EMAIL = 'jivochat_email';
    const FIELD_NAME = 'jivochat_userDisplayName';
    const FIELD_TOKEN = 'jivochat_authToken';
    const FIELD_STATUS = 'jivochat_status';
    const FIELD_WIDGET_ID = 'jivochat_widget_id';

    /** @var array */
    protected $dataFields =[
        self::FIELD_EMAIL,
        self::FIELD_PASSWORD,
        self::FIELD_SITE_URL,
        self::FIELD_NAME,
        self::FIELD_TOKEN,
        self::FIELD_STATUS
    ];

    /** @var array */
    protected $translations = [
        'heading_title',
        'text_edit',
        'text_enabled',
        'text_disabled',
        'entry_status',
        'button_save',
        'button_cancel',
        'entry_email',
        'entry_userPassword',
        'entry_userDisplayName',
        'entry_helpm',
        'entry_helpp',
        'entry_up_text',
        'entry_up_text2',
        'entry_down_text',
        'error_email_validate',
    ];

    /**
     * @throws Exception
     */
    public function index()
    {
        $this->load->language(self::BASE_PATH);

        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get('heading_title2'));

        $authToken = md5(time() . HTTPS_CATALOG);
        
        $lang_p = substr($this->config->get('config_admin_language'), 0, 2);

        if ($lang_p=="ru"|| $lang_p=="ua" || $lang_p=="kz"|| $lang_p=="by") {
            $jivo_pricelist = 105;
        } 
        if ($lang_p=="pt" || $lang_p=="br") {
            $jivo_pricelist = 114;
        }
        else {
            $jivo_pricelist = 4;
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $query['partnerId'] = 'opencart';
            $query['siteUrl'] = $this->request->post[self::FIELD_SITE_URL];
            $query['email'] = $this->request->post[self::FIELD_EMAIL];
            $query['userPassword'] = $this->request->post[self::FIELD_PASSWORD];
            $query['userDisplayName'] = $this->request->post[self::FIELD_NAME];
            $query['authToken'] = $authToken;
            $query['lang'] = substr($this->config->get('config_admin_language'), 0, 2);
            $query['pricelist_id'] = $jivo_pricelist;

            $content = http_build_query($query);

            try{
                $response = $this->request($content);
            } catch (Exception $e) {
                $response = $this->language->get('error_response');
            }

            if (strstr($response, 'Error')) {
                $this->error['response_error'] = $response;
            } else {
                $this->request->post[self::FIELD_WIDGET_ID] = $response;
                $this->request->post[self::FIELD_TOKEN] = $authToken;

                $this->model_setting_setting->editSetting('jivochat', $this->request->post);

                $this->session->data['success'] = $this->language->get('text_success');

                if (isset($this->request->get['loginfalse'])) {
                    $this->response->redirect($this->url->link(self::LOGIN_PATH, 'user_token=' . $this->session->data['user_token'], true));
                } else {
                    $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
                }
            }
        }

        $data = array_merge(
            $this->getTranslates(),
            [
                'entry_siteUrl' => HTTPS_CATALOG,
                'jivochat_partnerId' =>  'opencart',
                'response_error' =>  !empty($this->error['response_error']) ? $this->error['response_error'] : '',
                'error_warning' =>  !empty($this->error['warning']) ? $this->error['warning'] : '',
                'error_email' =>  !empty($this->error['email']) ? $this->error['email'] : '',
                'error_userPassword' =>  !empty($this->error['userPassword']) ? $this->error['userPassword'] : '',
                'header' =>  $this->load->controller('common/header'),
                'column_left' =>  $this->load->controller('common/column_left'),
                'footer' =>  $this->load->controller('common/footer'),
            ]
        );

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link(self::BASE_PATH, 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        if (isset($this->request->get['loginfalse'])) {
            $data['cancel'] = $this->url->link(self::LOGIN_PATH, 'user_token=' . $this->session->data['user_token'], true);
            $data['action'] = $this->url->link(self::BASE_PATH . '&loginfalse=1', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
            $data['action'] = $this->url->link(self::BASE_PATH, 'user_token=' . $this->session->data['user_token'], true);
        }

        foreach ($this->dataFields as $field) {
            $data[$field] = isset($this->request->post[$field])
                ? $this->request->post[$field]
                : $this->config->get($field);
        }

        $this->response->setOutput($this->load->view(self::BASE_PATH, $data));
    }

    public function install()
    {
        if (!$this->model_setting_event->getEventByCode('jivochat_admin_column_left')) {
            $code = "jivochat_admin_column_left";
            $trigger = "admin/view/common/column_left/before";
            $action = "extension/module/jivochat/menu";
            $this->model_setting_event->addEvent($code, $trigger, $action);

            $code = "jivochat_footer";
            $trigger = "catalog/view/common/footer/before";
            $action = "extension/module/jivochat/footer";
            $this->model_setting_event->addEvent($code, $trigger, $action);

            $code = "jivochat_header";
            $trigger = "catalog/view/common/header/before";
            $action = "extension/module/jivochat/header";
            $this->model_setting_event->addEvent($code, $trigger, $action);
        }

    }

    public function uninstall()
    {
        $this->model_setting_event->deleteEventByCode('jivochat_admin_column_left');
        $this->model_setting_event->deleteEventByCode('jivochat_footer');
        $this->model_setting_event->deleteEventByCode('jivochat_header');
    }


    public function login()
    {
        $this->load->language(self::BASE_PATH);
        $data['heading_title'] = $this->language->get('heading_title2');

        if (!$this->user->hasPermission('access', self::BASE_PATH)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['response_error'])) {
            $data['response_error'] = $this->error['response_error'];
        } else {
            $data['response_error'] = '';
        }

        $data['language'] = substr($this->config->get('config_admin_language'), 0, 2);

        $data['text_edit'] = $this->language->get('text_edit2');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['button_nastr'] = $this->language->get('button_nastr');
        $data['button_setup'] = $this->language->get('button_setup');
        $data['button_newwind'] = $this->language->get('button_newwind');
        $data['button_newwind2'] = $this->language->get('button_newwind2');
        $data['nastr'] = $this->url->link(self::BASE_PATH . '&loginfalse=1', 'user_token=' . $this->session->data['user_token'], true);

        if ($this->config->get(self::FIELD_TOKEN)) {
            $data['logintrue'] = true;
            $data[self::FIELD_TOKEN] = $this->config->get(self::FIELD_TOKEN);
        } else {
            $data['logintrue'] = false;
            $data[self::FIELD_TOKEN] = '';
        }

        $this->response->setOutput($this->load->view('extension/module/jivochat_login', $data));
    }

    /**
     * @param $route
     * @param $data
     * @param $output
     */
    public function menu(&$route, &$data, &$output)
    {
        if ($this->user->hasPermission('access', self::BASE_PATH)) {
            $data['menus'][] = array(
                'id'       => 'menu-jivosite',
                'icon'	   => 'fa-dashboard',
                'name'	   => 'JivoChat',
                'href'     => $this->url->link(self::LOGIN_PATH, 'user_token=' . $this->session->data['user_token'], true),
                'children' => array()
            );
        }
    }

    /**
     * @param $content
     * @return bool|mixed|string
     * @throws Exception
     */
    protected function request($content)
    {
        $useCurl = true;

        if (ini_get('allow_url_fopen')) {
            $useCurl = false;
        } elseif (!extension_loaded('curl') || !dl('curl.so')) {
            $useCurl = false;
        }

        $path = self::INSTALL_URI;
        if (!extension_loaded('openssl')) {
            $path = str_replace('https:', 'http:', $path);
        }
        if ($useCurl && $curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $path);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            $response = curl_exec($curl);
            curl_close($curl);
        } else {
            $response = file_get_contents(
                $path,
                false,
                stream_context_create(
                    array(
                        'http' => array(
                            'method' => 'POST',
                            'header' => 'Content-Type: application/x-www-form-urlencoded',
                            'content' => $content
                        )
                    )
                )
            );
        }

        if (empty($response)) {
            throw new Exception('Cannot get response from JivoChat');
        }

        return $response;
    }

    /**
     * @return array
     */
    protected function getTranslates()
    {
        $translates = [];
        foreach ($this->translations as $translation) {
            $translates[$translation] = $this->language->get($translation);
        }

        return $translates;
    }

    /**
     * @return bool
     */
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', self::BASE_PATH)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $emailField = $this->request->post[self::FIELD_EMAIL];

        if (!$emailField ) {
            $this->error['email'] = $this->language->get('error_email');
        } elseif (!filter_var($emailField , FILTER_VALIDATE_EMAIL)){
            $this->error['email'] = sprintf($this->language->get('error_email_validate'), $emailField );
        }

        if (!$this->request->post[self::FIELD_PASSWORD]) {
            $this->error['userPassword'] = $this->language->get('error_userPassword');
        }

        return !$this->error;
    }
}