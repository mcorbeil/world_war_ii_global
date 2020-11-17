<?php

namespace Axis;

use Monolog\Logger;
use Ratchet\ConnectionInterface;

class MessageHandler {
    private $logger;
    protected $auth;
    protected $lobbies;

    public function __construct(Logger $logger) {
        $this->logger = $logger;

        $this->auth = new Auth($this->logger);
        $this->lobbies = new LobbyContainer();
    }

    public function handle(ConnectionInterface $conn, array $message) {
        if (!isset($message["method"]) || empty($message["method"])) {
            throw new \Exception("No method specified");
        }

        $payload = $message["payload"];

        switch($message["method"]) {
            case "createLobby":
                return $this->lobbies->add($payload);
            case "renameLobby":
                return $this->lobbies->rename($payload);
            case "joinLobby":
                $payload["connectionId"] = $conn->resourceId;
                return $this->lobbies->addUser($payload);
            case "leaveLobby":
                $payload["connectionId"] = $conn->resourceId;
                return $this->lobbies->removeUser($payload);
            case "lobbyMessage":
                $this->lobbies->message($payload);
                break;
            case "login":
                $response = $this->auth->login($payload);

                ConnectionRegistry::SetName($conn->resourceId, $response["name"]);

                return $response;
            case "register":
                return $this->auth->register($payload);
            default:
                throw new \Exception("Unknown method: {$message["method"]}");
        }
    }
}
