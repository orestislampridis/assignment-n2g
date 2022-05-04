<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\RoutingKey;
use Doctrine\Persistence\ManagerRegistry;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReceiveController
{
    /**
     * @Route("/receive")
     */
    public function receive(ManagerRegistry $doctrine): Response
    {
        // Now it's time to save the routing key to our DB, so we can access it from the send controller
        $entityManager = $doctrine->getManager();

        $rkeys = $entityManager->getRepository("App\Entity\RoutingKey")->findAll();

        if (empty($rkeys)) {
            return new Response("No Routing keys saved. Please visit /subscribe to generate data");
        }

        $connection = new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST'],
            $_ENV['RABBITMQ_PORT'],
            $_ENV['RABBITMQ_USERNAME'],
            $_ENV['RABBITMQ_PASSWORD']
        );

        $channel = $connection->channel();

        $channel->exchange_declare($_ENV['RABBITMQ_EXCHANGE'], 'direct', true, true, false);

        $channel->queue_declare($_ENV['RABBITMQ_QUEUE_NAME'], true, true, true, false);

        foreach ($rkeys as $rkey) {
            $channel->queue_bind($_ENV['RABBITMQ_QUEUE_NAME'], $_ENV['RABBITMQ_EXCHANGE'], $rkey->getRoutingKey());
        }

        $callback = function ($msg) use ($doctrine) {
            $newMessage = $msg->body;
            $routing_key = strval($msg->get('routing_key'));
            $message = new Message();

            $messageParts = explode(".", $newMessage);
            $messageValue = $messageParts[0];
            $messageTimestamp = $messageParts[1];

            $message->setValue($messageValue);
            $message->setTimestamp($messageTimestamp);
            $message->setRoutingKey($routing_key);

            $entityManager = $doctrine->getManager();
            $entityManager->persist($message);

            $entityManager->flush();
        };

        $channel->basic_consume($_ENV['RABBITMQ_QUEUE_NAME'], '', false, true, false,
            false, $callback);

        while ($channel->is_open()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return new Response('Saved new message(s) to db!');
    }
}