<?php

namespace Axis;

class Lobby
{
    //array of int
    private array $members = [];
    private string $name;

    public function __construct(?string $name = null) {
        if ($name == null) {
            $name = "lobby-" + substr(md5(mt_rand()), 0, 5);
        }

        $this->rename($name);
    }

    public function rename(string $name) {
        $this->name = preg_replace("/[^a-zA-Z0-9\-]/", "", $name);
    }

    public function name() {
        return $this->name;
    }

    public function add(int $id) {
        if (!$this->member($id)) {
            $members[] = $id;
        }
    }

    public function remove(int $id) {
        $members = array_diff($members, $id);
    }

    public function member(int $id) {
        return in_array($id, $members)
    }

    public function message(array $payload, int $id) {
        if (!$this->member($id)) {
            throw new Exception("Connection Id $id is not a member of this room")
        }
        $message = ""; //$payload["some key"]; probably
        $recipients = ConnectionRegistry::GetListByIds(array_diff($members, $id));
        //TODO sort out name -> id mapping, probably in ConnectionRegistry
        $response = json_encode(["from" => $id, "message" => $message]);
        foreach ($recipients as $conn) {
            $conn->send($response);
        }
    }
}