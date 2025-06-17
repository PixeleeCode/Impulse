<?php

namespace Impulse\Commands;

use Impulse\Attributes\Renderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'renderer:configure',
    description: 'Initialise le projet Impulse avec un moteur de template',
    aliases: [
        'r:config',
        'renderer:setup',
        'renderer:config',
    ]
)]
class RendererConfigureCommand extends Command
{
    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $engines = $this->discoverRenderers();
        $options = array_map('ucfirst', array_keys($engines));

        $question = new ChoiceQuestion(
            '[<fg=cyan>Impulse</>] Quel moteur de template souhaitez-vous utiliser ?',
            array_values($options),
            0
        );

        $question->setErrorMessage('Le moteur %s est invalide.');

        $engine = $helper->ask($input, $output, $question);
        $engine = $engine === 'Aucun' ? null : strtolower($engine);

        // Ask user for the template directory path
        $questionPath = new \Symfony\Component\Console\Question\Question(
            '[<fg=cyan>Impulse</>] Où se trouvent vos templates ? [<fg=yellow>resources/views</>]',
            'resources/views'
        );
        $templatePath = $helper->ask($input, $output, $questionPath);

        $config = [
            'template_engine' => $engine ?? '',
            'template_path' => $templatePath,
        ];

        $configDir = getcwd() . '/.impulse';
        $configPath = $configDir . '/config.json';

        if (!is_dir($configDir) && !mkdir($configDir, 0755, true) && !is_dir($configDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $configDir));
        }

        file_put_contents($configPath, json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $output->writeln("[<fg=cyan>Impulse</>] Fichier de configuration généré : <info>.impulse/config.json</info>.");

        $bundle = $engines[$engine]['bundle'] ?? null;
        if ($bundle && $engine && isset($engines[$engine])) {
            return $this->addEngine($bundle, $output);
        }

        $output->writeln("[<fg=cyan>Impulse</>] 🎉 Impulse est prêt à être utilisé !");

        return Command::SUCCESS;
    }

    private function discoverRenderers(): array
    {
        $directory = __DIR__ . '/../Rendering';
        $files = glob($directory . '/*Renderer.php');

        $engines = [];
        $engines['aucun'] = [
            'bundle' => null,
        ];

        foreach ($files as $file) {
            $className = 'Impulse\\Rendering\\' . basename($file, '.php');
            $autoload = require getcwd() . '/vendor/autoload.php';
            $autoload->loadClass($className);

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);
            $attributes = $reflection->getAttributes(Renderer::class);

            if ($attributes) {
                /** @var Renderer $instance */
                $instance = $attributes[0]->newInstance();

                $engines[$instance->name] = [
                    'bundle' => $instance->bundle,
                ];
            }
        }

        return $engines;
    }

    /**
     * @throws \JsonException
     */
    private function addEngine(string $package, OutputInterface $output): int
    {
        if (!file_exists('composer.json')) {
            $output->writeln('[<fg=cyan>Impulse</>] <error>Erreur : composer.json introuvable à la racine du projet.</error>');
            return Command::FAILURE;
        }

        $composerData = json_decode(file_get_contents('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $package = explode(':', $package)[0];

        if (!isset($composerData['require'][$package])) {
            $output->writeln("[<fg=cyan>Impulse</>] 📦  Ajout de <info>$package</info> au fichier composer.json...");

            // On ajoute la dépendance via Composer directement (plus sûr que modifier à la main)
            $process = new Process(['composer', 'require', $package]);
            $process->setTty(Process::isTtySupported());
            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $output->writeln('[<fg=cyan>Impulse</>] <error>Erreur pendant l’installation du package Composer.</error>');
                return Command::FAILURE;
            }
        } else {
            $output->writeln("[<fg=cyan>Impulse</>] ✅  Le package <info>$package</info> est déjà présent.");
        }

        $output->writeln("[<fg=cyan>Impulse</>] 🎉  Impulse est prêt à être utilisé avec le moteur <info>$package</info> !");

        return Command::SUCCESS;
    }
}
