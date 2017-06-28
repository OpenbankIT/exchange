<?php

namespace App\Frontend\Controllers;

use \Smartmoney\Stellar\Account;
use Phalcon\Translate\Adapter\NativeArray;


class ControllerBase extends \Phalcon\Mvc\Controller
{
    protected $title;

    protected $_locale;

    protected $_session;


    public function beforeExecuteRoute()
    {

        //load locales
        if ($this->request->hasQuery('lang')) {
            $language = $this->request->getQuery('lang');
            $this->cookies->set('lang', $language);
        } else {
            if ($this->cookies->has('lang')) {
                $language = $this->cookies->get('lang')->getValue();
            } else {
                $language = strtok($this->request->getBestLanguage(), '-');
                $this->cookies->set('lang', $language);
            }
        }

        $translate_messages = $this->getTranslationMessages($language);
        $this->_locale = new NativeArray(["content" => $translate_messages]);

        $this->view->locale = $this->_locale;

        //get current page uri for menu building
        $this->view->currentPageUrl = $_SERVER['REQUEST_URI'];
        

        $menu = [];

        $menu['Main'] = [
            'href'     => '/',
            'icon'      => 'md md-dashboard',
            'subItems'  => null,
        ];

        $menu['Buy for btc'] = [
            'href'     => '/cashin/btc',
            'icon'      => 'md md-attach-money',
        ];

        $menu['Buy for privat24'] = [
            'href'     => '/cashin/privat24',
            'icon'      => 'md md-attach-money',
        ];
        
        $menu['Buy for wmu'] = [
            'href'     => '/cashin/wmu',
            'icon'      => 'md md-attach-money',
        ];

        //get session
        $this->_session = new \Phalcon\Session\Bag('exchange.smart');

        //check account id in get param
        if (!empty($this->request->get('acc'))) {
            $acc_param = $this->request->get('acc');

            if (Account::isValidAccountId($acc_param)) {
                $this->_session->acc = $acc_param;
            }
        }
        
        $this->view->mainMenu = $menu;

        $this->assets

            //add basic css
            ->addCss('/bower_components/bootstrap/dist/css/bootstrap.min.css')
            ->addCss('/bower_components/metisMenu/dist/metisMenu.min.css')
            ->addCss('/bower_components/morrisjs/morris.css')
            ->addCss('/bower_components/font-awesome/css/font-awesome.min.css')



            //add template css

            ->addCss('/template/plugins/switchery/switchery.min.css')
            ->addCss('/template/css/core.css')
            ->addCss('/template/css/icons.css')
            ->addCss('/template/css/components.css')
            ->addCss('/template/css/pages.css')
            ->addCss('/template/css/menu.css')
            ->addCss('/template/css/responsive.css')
            ->addCss('/template/css/jquery.alerts.css')
            ->addCss('/css/main.css')

            //add basic js
            ->addJs('/bower_components/jquery/dist/jquery.min.js')
            ->addJs('/bower_components/bootstrap/dist/js/bootstrap.min.js')
            ->addJs('/bower_components/metisMenu/dist/metisMenu.min.js')
            // ->addJs('/bower_components/raphael/raphael-min.js')
            ->addJs('/bower_components/morrisjs/morris.min.js')
            ->addJs('/bower_components/jquery-validation/dist/jquery.validate.min.js')
            // ->addJs('/js/morris-data.js')
            //->addJs('/node_modules/stellar-sdk/dist/stellar-sdk.min.js')
           // ->addJs('/node_modules/stellar-sdk/dist/stellar-sdk.js')
            //->addJs('/node_modules/sjcl/sjcl.js')

            //add template js


            ->addJs('/template/js/detect.js')
            ->addJs('/template/js/jquery.slimscroll.js')
            ->addJs('/template/js/fastclick.js')
            ->addJs('/template/js/waves.js')
            ->addJs('/template/js/wow.min.js')
            ->addJs('/template/plugins/switchery/switchery.min.js')

            // notify scripts
            ->addJs('/template/plugins/notifyjs/dist/notify.min.js')
            ->addJs('/template/plugins/notifications/notify-metro.js')

            ->addJs('/template/js/jquery.core.js')
            ->addJs('/template/js/jquery.app.js')
            ->addJs('/template/js/jquery.alerts.js')

        ;

        $this->view->copyright_link = $this->config->link->url;
        $this->view->copyright_name = $this->config->link->name;
        $this->view->copyright_tag = $this->_locale->_('Copyright tag');

    }

    public function afterExecuteRoute()
    {
        $this->tag->appendTitle((!empty($this->title)?  $this->title . ' | ' : '') . $this->config->project->title);

        $this->view->production  = $this->config->project->production;
        $this->view->meta        = '';
    }

    protected function maybeForward($controller, $action)
    {
        if ($this->dispatcher->getControllerName() != $controller || $this->dispatcher->getActionName() != $action) {
            return $this->dispatcher->forward(['controller' => $controller, 'action' => $action]);
        }
    }

    protected function getTranslationMessages($language)
    {
        $allowedLanguages = (array)$this->config->allowed_languages;
        if (!in_array($language, $allowedLanguages)) {
            $language = 'en';
        }

        $messages = []; // default array

        //Check if we have a translation file for that lang
        $fpath = __DIR__ . '/../locale/' . $language . '.php';
        if (strlen($language) == 2 && file_exists($fpath)) {
            require $fpath;
        }

        return $messages;
    }
}