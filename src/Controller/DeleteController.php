<?php

namespace App\Controller;

use App\Entity\RoutingKey;
use Doctrine\Persistence\ManagerRegistry;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReceiveController extends AbstractController
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

        /*
        else{
            foreach ($rkeys as $rkey) {
                $entityManager->remove($rkey);
            }
            $entityManager->flush();
            return new Response("ok");
        }
        */

        $connection = new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST'],
            $_ENV['RABBITMQ_PORT'],
            $_ENV['RABBITMQ_USERNAME'],
            $_ENV['RABBITMQ_PASSWORD']
        );

        $channel = $connection->channel();

        $channel->exchange_declare($_ENV['RABBITMQ_EXCHANGE'], 'direct', true, true, true);

        $channel->queue_declare($_ENV['RABBITMQ_QUEUE_NAME'], true, true, true);

        foreach ($rkeys as $rkey) {
            $channel->queue_bind($_ENV['RABBITMQ_QUEUE_NAME'], $_ENV['RABBITMQ_EXCHANGE'], $rkey->getRoutingKey());
        }

        echo " [*] Waiting for logs. To exit press CTRL+C\n";

        $callback = function ($msg) {
            $newMessage = $msg->body;
            return new Response($newMessage);
        };

        $channel->basic_consume($_ENV['RABBITMQ_QUEUE_NAME'], '', false, true, false, false, $callback);

        while ($channel->is_open()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return $this->json([
            'message' => '',
        ]);
    }
}