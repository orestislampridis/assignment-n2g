<?php

namespace App\Controller;

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
        $url = 'https://a831bqiv1d.execute-api.eu-west-1.amazonaws.com/dev/results';
        $sock = fopen($url, 'r');
        $data = fgets($sock);

        # Convert string to json
        $content = json_decode($data, True);

        # Create body that will be sent to RabbitMQ
        $body = $content['timestamp'] . '.' . $content['value'];

        # Convert from hexadecimal to decimal
        $num = gmp_init('0x' . $content['gatewayEui']);
        $content['gatewayEui'] = gmp_strval($num, 10);
        $content['profileId'] = hexdec($content['profileId']);
        $content['endpointId'] = hexdec($content['endpointId']);
        $content['clusterId'] = hexdec($content['clusterId']);
        $content['attributeId'] = hexdec($content['attributeId']);

        # Remove unwanted keys for RabbitMQ routing_key
        unset($content['value'], $content['timestamp']);

        # Implode data to valid format for passing as routing_key to RabbitMq
        $routing_key = implode(".", $content);

        $connection = new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST'],
            $_ENV['RABBITMQ_PORT'],
            $_ENV['RABBITMQ_USERNAME'],
            $_ENV['RABBITMQ_PASSWORD']
        );

        $channel = $connection->channel();

        $channel->exchange_declare($_ENV['RABBITMQ_EXCHANGE'], 'direct', true, true, true);

        # Create the queue if it doesnt already exist.
        $channel->queue_declare(
            $queue = $_ENV['RABBITMQ_QUEUE_NAME'],
            $passive = true,
            $durable = true,
            $exclusive = true
        );

        $channel->queue_bind($_ENV['RABBITMQ_QUEUE_NAME'], $_ENV['RABBITMQ_EXCHANGE'], $routing_key);

        $msg = new AMQPMessage($body);

        $channel->basic_publish($msg, $_ENV['RABBITMQ_EXCHANGE'], $routing_key);

        $channel->close();
        $connection->close();

        // Now it's time to save the routing key to our DB, so we can access it from the send controller
        $entityManager = $doctrine->getManager();

        $rkey = new RoutingKey();
        $rkey->setRoutingKey($routing_key);

        // tell Doctrine we want to save the routing key
        $entityManager->persist($rkey);

        // actually execute the query (i.e. the INSERT query)
        $entityManager->flush();

        return new Response('Saved new routing_key with id ' . $rkey->getId());
    }
}