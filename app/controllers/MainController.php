<?php

namespace app\controllers;

use app\ControllerBase;
use app\models\Message;
use app\models\User;

class MainController extends ControllerBase
{
    public function index()
    {
        if(!isset($_COOKIE['token']) || !isset($_COOKIE['username'])) {
            setcookie('token', bin2hex(random_bytes(16)));
        }

        $m = new Message();
        $messages = $m->getLast();

        $u = new User();

        foreach($messages as $key => $message) {
            $messages[$key]['author'] = $u->find($message['author_id']);
        }

        return $this->render('main', [
            'messages' => $messages
        ]);
    }

    public function test()
    {
//        $u = new User();
//
//        $user = $u->create([
//            'username' => 'testusername',
//            'token' => 'testtoken'
//        ]);
//
//        var_dump($user);

        echo strlen(bin2hex(random_bytes(16)));
    }
}