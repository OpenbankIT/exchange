<?php

namespace App\Lib;

use Phalcon\DI;
use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Password;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;

class Auth
{
    private $_session_key = 'user_id';
    private $_cookies_key = 'rmmecook';

    private $user;
    private static $form;

    public function __construct()
    {
        $session  = DI::getDefault()->getSession();
        $cookies  = DI::getDefault()->getCookies();
        $security = DI::getDefault()->getSecurity();
        $request  = DI::getDefault()->getRequest();

        if ($request->getQuery('logout')) {
            $cookies->get($this->_cookies_key)->delete();
            $session->remove($this->_session_key);

            return;
        }

        if (is_null($this->user)) {
            # Check login form
            $form = self::getAuthForm();
            if (!$session->has($this->_session_key) && $request->isPost() && $form->isValid($_POST)) {
                $user = \App\Models\Users::findFirst(['usr_email = :email:', 'bind' => ['email' => $request->getPost('email')]]);
                if (!empty($user) && $security->checkHash($request->getPost('password'), $user->usr_password)) {
                    $this->authenticate($user->usr_id);
                } else {
                    $form->get('password')->appendMessage(new \Phalcon\Validation\Message('Email/pwd combination does not exist'));
                }
            }

            # Check 'remember me' cookie
            if (!$session->has($this->_session_key) && $cookies->has($this->_cookies_key)) {
                # Delete old tokens
                \App\Models\Auth_tokens::removeOld();

                list($atk_id, $token) = explode('_', $cookies->get($this->_cookies_key)->getValue(), 2);

                if (!empty($atk_id) && !empty($token)) {
                    $atk  = \App\Models\Auth_tokens::findFirst($atk_id);
                    if (!empty($atk) && $security->checkHash($token, $atk->atk_hash)) {
                        $this->authenticate($atk->usr_id);
                    }
                }
            }

            # Check saved user id in session
            if ($session->has($this->_session_key)) {
                $this->user = \App\Models\Users::findFirst($session->get($this->_session_key));
            }
        }
    }

    public function authenticate($usr_id, $remember_me = true)
    {
        if (empty($usr_id)) {
            return false;
        }

        $session  = DI::getDefault()->getSession();
        $cookies  = DI::getDefault()->getCookies();
        $security = DI::getDefault()->getSecurity();

        if ($remember_me) {
            # Delete old auth_token
            $atk = \App\Models\Auth_tokens::findFirst(['usr_id = :id:', 'bind' => ['id' => $usr_id]]);
            if (!empty($atk)) {
                $atk->delete();
            }

            $token = \App\Lib\Tools::randString(30, true);

            $atk = new \App\Models\Auth_tokens();
            $atk->usr_id   = $usr_id;
            $atk->atk_hash = $security->hash($token);
            if ($atk->save()) {
                $cookies->set($this->_cookies_key, $atk->atk_id . '_' . $token, time() + 86400 * 7); // 7 days
            }
        }

        $session->set($this->_session_key, $usr_id);
    }

    public static function getAuthForm()
    {
        if (empty(self::$form)) {
            self::$form = new Form();

            $input = new Text('email');
            $input->addValidator(new Email(['message' => 'E-mail is not valid']));
            $input->setLabel('Email');
            self::$form->add($input);

            $input = new Password('password');
            $input->setLabel('Password');
            $input->addValidator(new PresenceOf(['message' => 'Password required']));
            self::$form->add($input);
        }

        return self::$form;
    }

    public static function getLogoutUrl()
    {
        return '/?logout=true';
    }

    public function getUser()
    {
        return $this->user;
    }
}