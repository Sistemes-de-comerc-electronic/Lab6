<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class CommandRunnerController extends AbstractController
{
    #[Route('/command/{name}', name: 'command_runner')]
    public function index(string $name, KernelInterface $kernel): Response
    {
        $application = new SymfonyApplication($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => $name,
            '--no-interaction' => true,
        ]);

        $output = new BufferedOutput();
        try{
            $application->run($input, $output);
        }catch(\Exception $e){
            return new Response($e->getMessage());
        }

        $content = $output->fetch();

        return new Response($content);
    }
}
