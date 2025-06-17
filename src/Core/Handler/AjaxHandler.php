<?php

namespace Impulse\Core\Handler;

use Impulse\Attributes\Action;
use Impulse\Core\Component;
use Impulse\Core\ComponentResolver;
use JetBrains\PhpStorm\NoReturn;

class AjaxHandler
{
    /**
     * Liste des méthodes interdites (toujours en minuscules pour la comparaison)
     * @var array|string[]
     */
    private array $forbidden = [
        'setup', 'template', 'render', 'onevent', 'onbeforeaction', 'onafteraction'
    ];

    /**
     * @throws \JsonException
     */
    #[NoReturn]
    private function respondError(string $message, int $httpCode = 400): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * @throws \JsonException
     */
    public function handle(): void
    {
        header('Content-Type: text/html');
        try {
            $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->respondError("Erreur de décodage JSON: " . $e->getMessage());
        }

        // Gestion des emits
        if (isset($data['emit'])) {
            $event = $data['emit'];
            $payload = $data['payload'] ?? [];
            $componentIds = $data['components'] ?? [];

            $results = [];
            foreach ($componentIds as $componentId) {
                $component = ComponentResolver::resolve($componentId);
                if ($component && method_exists($component, 'onEvent')) {
                    $res = $component->onEvent($event, $payload);
                    if ($res !== null) {
                        $html = $component->render();
                        $results[] = [
                            'component' => $componentId,
                            'result' => $res,
                            'html' => $html,
                        ];
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'updates' => $results
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!isset($data['id'])) {
            $this->respondError("L'ID du composant est requis");
        }

        $component = ComponentResolver::resolve($data['id']);
        if (!$component) {
            $this->respondError("Composant non trouvé pour l'ID: {$data['id']}", 404);
        }

        if (isset($data['states']) && is_array($data['states'])) {
            foreach ($data['states'] as $name => $value) {
                $component->state($name, $value)->set($value);
            }
        }

        // Traitement des actions AJAX
        if (isset($data['action'])) {
            try {
                $rawAction = $data['action'];
                $args = [];

                if (preg_match('/^([a-zA-Z_]\w*)\((.*)\)$/', $rawAction, $matches)) {
                    $method = $matches[1];
                    $argsString = $matches[2];
                    if (trim($argsString) !== '') {
                        $args = array_map('trim', explode(',', $argsString));
                        $args = array_map(static function ($value) {
                            if (is_numeric($value)) {
                                return $value + 0;
                            }
                            return trim($value, '"\'');
                        }, $args);
                    }
                } else {
                    $method = $rawAction;
                }

                // Appel du hook avant exécution de l’action
                if ($component instanceof Component) {
                    $component->onBeforeAction($method, $args);
                }

                $actionCalled = false;

                if (method_exists($component, $method)) {
                    $refMethod = new \ReflectionMethod($component, $method);
                    if (
                        $refMethod->getAttributes(Action::class)
                        && $refMethod->isPublic()
                        && !str_starts_with($method, '__')
                        && !in_array(strtolower($method), $this->forbidden, true)
                    ) {
                        call_user_func_array([$component, $method], $args);
                        $actionCalled = true;
                    } else if($refMethod->getAttributes(Action::class) && !$refMethod->isPublic()) {
                        error_log("[Impulse] La méthode '$method' est décorée avec #[Action] mais n'est pas publique. Elle ne pourra pas être appelée.");
                    }
                }

                $methods = $component->getMethods();
                if (!$actionCalled && $methods->exists($method)) {
                    $callable = $methods->get($method);
                    if (empty($args) && array_key_exists('value', $data)) {
                        $args = [$data['value']];
                    }

                    $ref = new \ReflectionFunction($callable);
                    $requiredParams = $ref->getNumberOfRequiredParameters();
                    if (count($args) < $requiredParams) {
                        throw new \RuntimeException("La méthode '$method' attend au moins $requiredParams argument(s), " . count($args) . " fourni(s).");
                    }

                    if (!is_callable($callable)) {
                        throw new \RuntimeException("La méthode '$method' est introuvable ou non appelable dans le composant.");
                    }

                    $callable(...$args);
                    $actionCalled = true;
                }

                if (!$actionCalled) {
                    throw new \RuntimeException("Méthode '$method' non trouvée dans les méthodes définies.");
                }
            } catch (\Throwable $e) {
                $this->respondError("Erreur lors de l'exécution de l'action: " . $e->getMessage());
            }
        }

        // Appel du hook après exécution de l’action
        if ($component instanceof Component) {
            $component->onAfterAction();
        }

        ob_start();
        echo $component->render();
        $html = ob_get_clean();

        $states = $component->getStates();

        if (!empty($data['requestStates'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'html' => $html,
                'states' => $states,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!empty($data['update'])) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<!DOCTYPE html><meta charset="utf-8">' . $html);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);
            $node = $xpath->query("//*[@data-impulse-update='{$data['update']}']")->item(0);

            if ($node) {
                $fragmentHtml = '';
                foreach ($node->childNodes as $child) {
                    $fragmentHtml .= $dom->saveHTML($child);
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'result' => $fragmentHtml ?: $dom->saveHTML($node),
                    'states' => $component->getStates(),
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                exit;
            }
        }

        echo $html;
    }
}
