<?php

namespace App\Controller;

use App\Entity\RoutingKey;
use Doctrine\Persistence\ManagerRegistry;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteController
{
    /**
     * @Route("/delete")
     */
    public function delete(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();

        $rkeys = $entityManager->getRepository("App\Entity\Message")->findAll();

        if (empty($rkeys)) {
            return new Response("No Routing keys saved. Please visit /subscribe to generate data");
        } else {
            foreach ($rkeys as $rkey) {
                $entityManager->remove($rkey);
            }
            $entityManager->flush();
            return new Response("ok");
        }
    }
}