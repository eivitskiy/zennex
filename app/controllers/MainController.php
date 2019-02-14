<?php

namespace app\controllers;

use app\ControllerBase;
use app\models\Message;

class MainController extends ControllerBase
{
    public function index()
    {
        $users = [
            ['name' => "Вася"],
            ['name' => "Таня"],
            ['name' => "Петя"],
            ['name' => "Маша"],
            ['name' => "Катя"],
            ['name' => "Антон"],
            ['name' => "Игорь"],
            ['name' => "Федя"],
            ['name' => "Лёха"],
            ['name' => "Гена"],
            ['name' => "Миша"],
            ['name' => "Артур"],
            ['name' => "Серёга"],
            ['name' => "Настя"],
            ['name' => "Алёна"],
        ];

//        $messages = [
//            [
//                'created_at' => '2018-02-12 11:04:12',
//                'content' => 'Всем приветики в этом чатике',
//                'author' => "Миха"
//            ],
//            [
//                'created_at' => '2018-02-12 11:05:01',
//                'content' => 'Здоров',
//                'author' => "Алёша"
//            ],
//            [
//                'created_at' => '2018-02-12 11:05:49',
//                'content' => 'Хаюшки',
//                'author' => "Оксана",
//                'attachments' => [
//                    [
//                        'type' => 'img',
//                        'url' => 'https://via.placeholder.com/850',
//                        'desc' => 'Заглушка 850'
//                    ],
//                    [
//                        'type' => 'img',
//                        'url' => 'https://via.placeholder.com/630',
//                        'desc' => 'Заглушка 630'
//                    ],
//                    [
//                        'type' => 'img',
//                        'url' => 'https://via.placeholder.com/1080x720',
//                        'desc' => 'Заглушка 1080x720'
//                    ],
//                ]
//            ]
//        ];

        $m = new Message();
        $messages = $m->getLast();

        return $this->render('main', [
            'users' => $users,
            'messages' => $messages
        ]);
    }

    public function test()
    {
        $m = new Message();
        $message = $m->update(89, [
            'likes' => 12
        ]);
        var_dump($message);
    }
}