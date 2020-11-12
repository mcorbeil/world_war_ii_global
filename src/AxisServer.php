<?php

namespace Axis;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class AxisServer implements MessageComponentInterface {
    protected $logRoot = "/var/log/axis";
    protected $logger;
    protected $handler;

    public function __construct() {
        @mkdir($logRoot, 0777, true);

        $this->logger = new Logger('axis',
            [
                new RotatingFileHandler("$logRoot/all.log", 10),
                new RotatingFileHandler("$logRoot/error.log", 10, Logger::ERROR)
            ]
        );

        $this->handler = new MessageHandler($this->logger);
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->logger->info("New connection! ({$conn->resourceId})");
        ConnectionRegistry::Add($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $this->logger->debug("Message from {$from->resourceId} : $msg");
        $this->handleMessage($from, $msg);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->logger->info("Connection {$conn->resourceId} has disconnected");
        ConnectionRegistry::Remove($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->logger->critical("An error has occurred: {$e->getMessage()}");

        $conn->close();
    }

    public function handleMessage(ConnectionInterface $conn, $msg){
        $message = json_decode($msg, true);

        if ($message == null) {
            $this->logger->error("Invalid Json: $msg");
            $conn->send(json_encode(["error" => "Invalid Json"]));
            return;
        }

        try {
            $this->handler->handle($message);
        } catch (\Exception $e) {
            $conn->send(json_encode(["error" => $e->getMessage()]));
        }
    }
}