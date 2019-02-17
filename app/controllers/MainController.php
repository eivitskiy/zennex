<?php

namespace app\controllers;

use app\ControllerBase;
use app\models\Attachment;
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
        $messages = $m->getLast('created_at', 3);

        $u = new User();
        $a = new Attachment();

        foreach($messages as $key => $message) {
            $messages[$key]['author'] = $u->find($message['author_id']);
            if($messages[$key]['attachments']) {
                foreach (json_decode($messages[$key]['attachments']) as $attachment_id) {
                    $messages[$key]['attach'][] = $a->find($attachment_id);
                }
            }
        }

        return $this->render('main', [
            'messages' => $messages
        ]);
    }

    public function attachmentsUpload()
    {
        $u = new User();

        $status = $u->existsUser($_COOKIE['username'], $_COOKIE['token']);

        if($status !== true) {
            return false;
        }

        $user = $u->getByUsername($_COOKIE['username']);

        $a = new Attachment();

        $attachments = [];

        foreach($_FILES as $file) {
            $data = [
                'type' => 'img',
                'file' => addslashes(file_get_contents($file['tmp_name'])),
                'author_id' => $user['id']
            ];

            $attachments[] = $a->create($data);
        }

        if(isset($_POST['link'])) {
            foreach ($_POST['link'] as $link) {
                $data = [
                    'type' => 'link',
                    'link' => $link,
                    'author_id' => $user['id']
                ];

                $attachments[] = $a->create($data);
            }
        }

        if(isset($_POST['youtube'])) {
            foreach($_POST['youtube'] as $youtube) {
                $data = [
                    'type' => 'youtube',
                    'link' => $youtube,
                    'author_id' => $user['id']
                ];

                $attachments[] = $a->create($data);
            }
        }

        return json_encode($attachments);
    }

    public function getAttachment($params)
    {
        $id = array_shift($params);

        $a = new Attachment();
        $attachment = $a->find($id);

        switch ($attachment['type']) {
            case 'img':
                header("Content-type: image/jpg");
                echo $attachment['file'];
                break;
            case 'link':
                echo $attachment['link'];
                break;
        }
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