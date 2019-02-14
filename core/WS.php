<?php

namespace core;

use app\models\Message;
use app\models\User;

class WS
{
    private $local_socket = "tcp://0.0.0.0:8000";

    public $errNo, $errStr;     //ошибки при создании сокета

    public $socket, $connections = [], $clients = [];

    private $users = [];

    private $connectionsInfo = [];

    public function __construct()
    {
        $this->socket = stream_socket_server($this->local_socket, $this->errNo, $this->errStr);
    }

    public function run()
    {
        while (true) {
            //формируем массив прослушиваемых сокетов:
            $read = $this->connections;

            $read[] = $this->socket;

            $write = $except = null;

            if (!stream_select($read, $write, $except, null)) {     //ожидаем сокеты доступные для чтения (без таймаута)
                break;
            }

            if (in_array($this->socket, $read)) {   //есть новое соединение
                //принимаем новое соединение и производим рукопожатие:
                if (($connect = stream_socket_accept($this->socket, -1)) && $info = $this->handshake($connect)) {
                    $this->connections[] = $connect;    //добавляем его в список необходимых для обработки
                    $this->connectionsInfo[intval($connect)] = $info;
                    $this->onOpen($connect, $info);     //вызываем пользовательский сценарий
                }
                unset($read[array_search($this->socket, $read)]);
            }

            foreach ($read as $connect) {   //обрабатываем все соединения
                $data = fread($connect, 100000);

                if (!$data) {       //соединение было закрыто
                    $this->onClose($connect);
                    unset($this->connections[array_search($connect, $this->connections)]);      //удаляем подключение из списка
                    unset($this->users[$this->connectionsInfo[intval($connect)]['Sec-WebSocket-Key']]); // удаляем пользователя из списка
                    fclose($connect);
                    continue;
                }

                $this->onMessage($connect, $data);  //вызываем пользовательский сценарий
            }
        }
    }

    private function handshake($connect)
    {
        $info = array();

        $line = fgets($connect);
        $header = explode(' ', $line);
        $info['method'] = $header[0];
        $info['uri'] = $header[1];

        //считываем заголовки из соединения
        while ($line = rtrim(fgets($connect))) {
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $info[$matches[1]] = $matches[2];
            } else {
                break;
            }
        }

        $address = explode(':', stream_socket_get_name($connect, true)); //получаем адрес клиента
        $info['ip'] = $address[0];
        $info['port'] = $address[1];

        if (empty($info['Sec-WebSocket-Key'])) {
            return false;
        }

        //отправляем заголовок согласно протоколу вебсокета
        $SecWebSocketAccept = base64_encode(pack('H*', sha1($info['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:$SecWebSocketAccept\r\n\r\n";
        fwrite($connect, $upgrade);

        return $info;
    }

    private function encode($payload, $type = 'text', $masked = false)
    {
        $frameHead = array();
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0
            if ($frameHead[2] > 127) {
                return array('type' => '', 'payload' => '', 'error' => 'frame too large (1004)');
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) {
            // generate a random mask:
            $mask = array();
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);

        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    private function decode($data)
    {
        $unmaskedPayload = '';
        $decodedData = array();

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($data[1]) & 127;

        // unmasked frame is received:
        if (!$isMasked) {
            return array('type' => '', 'payload' => '', 'error' => 'protocol error (1002)');
        }

        switch ($opcode) {
            // text frame:
            case 1:
                $decodedData['type'] = 'text';
                break;

            case 2:
                $decodedData['type'] = 'binary';
                break;

            // connection close frame:
            case 8:
                $decodedData['type'] = 'close';
                break;

            // ping frame:
            case 9:
                $decodedData['type'] = 'ping';
                break;

            // pong frame:
            case 10:
                $decodedData['type'] = 'pong';
                break;

            default:
                return array('type' => '', 'payload' => '', 'error' => 'unknown opcode (1003)');
        }

        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferd.
         */
        if (strlen($data) < $dataLength) {
            return false;
        }

        if ($isMasked) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }

    private function getCookieArray($cookieString)
    {
        $cookies = [];
        foreach(explode('; ', $cookieString) as $str) {
            $arr = array_values(explode('=', $str));
            $cookies[$arr[0]] = $arr[1];
        }

        return $cookies;
    }

    private function checkUser($cookies)
    {
        $u = new User();

        var_dump($cookies);

        $status = $u->existsUser($cookies['username'], $cookies['token']);

        var_dump($status);

        if($status === null) {
            $user_id = $u->create([
                'username' => $cookies['username'],
                'token' => $cookies['token']
            ]);

            return true;
        } elseif ($status === false) {
            return false;
        } else {
            return true;
        }
    }

    /*******************************************************************************************************************
    |* пользовательские сценарии:
    |******************************************************************************************************************/
    private function onOpen($connect, $info)
    {
        $cookies = $this->getCookieArray($info['Cookie']);

        if(!$this->checkUser($cookies)) {
            fwrite($connect, $this->encode(json_encode([
                'type' => 'changeUsername'
            ])));
        } else {
            $username = $cookies['username'];

            $this->users[$info['Sec-WebSocket-Key']] = $username;

            foreach ($this->connections as $connection) {
                if ($connection == $connect) {
                    fwrite($connection, $this->encode(json_encode([
                        'type' => 'info',
                        'content' => 'Соединение установлено'
                    ])));

                    foreach ($this->users as $id => $user) {
                        fwrite($connection, $this->encode(json_encode([
                            'type' => 'addUser',
                            'id' => $id,
                            'username' => $user,
                        ])));
                    }
                } else {
                    fwrite($connection, $this->encode(json_encode([
                        'type' => 'info',
                        'content' => "Пользователь '{$username}' присоединился к чату"
                    ])));
                    // новый пользователь в чате
                    fwrite($connection, $this->encode(json_encode([
                        'type' => 'addUser',
                        'id' => $info['Sec-WebSocket-Key'],
                        'username' => $username,
                    ])));
                }
            }
        }

        echo "opened for " . $info['Sec-WebSocket-Key'] . PHP_EOL;
    }

    private function onClose($connect)
    {
        // пользователь ушел из чата
        foreach($this->connections as $connection) {
            fwrite($connection, $this->encode(json_encode([
                'type' => 'removeUser',
                'id' => $this->connectionsInfo[intval($connect)]['Sec-WebSocket-Key'],
                'username' => $this->users[$this->connectionsInfo[intval($connect)]['Sec-WebSocket-Key']],
            ])));
        }
        echo "closed" . PHP_EOL;
    }

    private function onMessage($connect, $data)
    {
        $message = json_decode($this->decode($data)['payload']);

        $cookies = $this->getCookieArray($this->connectionsInfo[intval($connect)]['Cookie']);
        $u = new User();
        $user = $u->getByUsername($cookies['username']);

        if($message) {
            switch($message->type) {
                case 'newUsername':
                    if(!$this->checkUser($cookies)) {
                        fwrite($connect, $this->encode(json_encode([
                            'type' => 'changeUsername'
                        ])));
                    }
                    break;
                case 'message':
                    $this->message($message, $user);
                    break;
                case 'like':
                    $this->like($message);
                    break;
                case 'remove':

                    $this->remove($message, $user);
                    break;
            }
        } else {
            fwrite($connect, $data);
        }
    }

    private function message($message, $user)
    {
        $m = new Message();

        $m_id = $m->create([
            'content' => $message->content,
            'author_id' => $user['id']
        ]);

        $message = $m->find($m_id);
        $message['author'] = $user;
        $message['type'] = 'message';

        foreach($this->connections as $connection) {
            fwrite($connection, $this->encode(json_encode($message)));
        }
    }

    private function like($message)
    {
        $m = new Message();

        $msg = $m->find($message->message_id);
        $message = $m->update($msg['id'], [
            'likes' => ++$msg['likes']
        ]);
        $message['type'] = 'liked';

        foreach($this->connections as $connection) {
            fwrite($connection, $this->encode(json_encode($message)));
        }
    }

    private function remove($message, $user)
    {
        $m = new Message();

        $datetime = new \DateTime();

        $msg = $m->find($message->message_id);

        if($user['id'] == $msg['author_id']) {
            $message = $m->update($msg['id'], [
                'deleted_at' => $datetime->format('Y-m-d H:i:s')
            ]);
            $message['type'] = 'removed';

            foreach ($this->connections as $connection) {
                fwrite($connection, $this->encode(json_encode($message)));
            }
        } else {
            new \Exception('Кто-то пытается удалить не своё сообщение!');
        }
    }
}