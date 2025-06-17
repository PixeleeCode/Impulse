<?php

namespace Impulse\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'make:renderer',
    description: 'Crée un renderer personnalisé',
    aliases: [
        'r:new',
        'r:make',
    ]
)]
class MakeRendererCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $question = new Question('[<fg=cyan>Impulse</>] Nom du renderer (ex: MustacheRenderer) : ');
        $name = $helper->ask($input, $output, $question);

        if (!$name) {
            $output->writeln('[<fg=cyan>Impulse</>] <error>Nom invalide.</error>');
            return Command::FAILURE;
        }

        $className = str_ends_with(strtolower($name), 'renderer') ? ucfirst($name) : ucfirst($name) . 'Renderer';
        $filePath = dirname(__DIR__, 2) . "/src/Rendering/{$className}.php";

        if (file_exists($filePath)) {
            $output->writeln("[<fg=cyan>Impulse</>] <error>Le fichier $className existe déjà.</error>");
            return Command::FAILURE;
        }

        $namespace = 'Impulse\\Rendering';
        $code = <<<PHP
        <?php
        
        namespace $namespace;
        
        use Impulse\\Attributes\\Renderer;
        use Impulse\\Interfaces\\TemplateRendererInterface;
        
        #[Renderer(
            name: '$name',
            bundle: '$name/$name'
        )]
        class $className implements TemplateRendererInterface
        {
            public function __construct(string \$viewsPath = '')
            {
                // "\$viewPath" contient le chemin vers le dossier des templates
            }
            
            public function render(string \$template, array \$data = []): string
            {
                // Implémenter le rendu ici
                return '';
            }
        }
        
        PHP;

        file_put_contents($filePath, $code);
        $output->writeln("[<fg=cyan>Impulse</>] 🎉 Renderer <info>$className</info> créé avec succès : <info>src/Rendering/{$className}.php</info>");

        return Command::SUCCESS;
    }
}
