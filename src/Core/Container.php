<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Service Container ligero para Inyección de Dependencias (DI).
 * Permite bindings explícitos, registro de singletons y autowiring mediante Reflexión.
 */
class Container
{
    /** @var Container|null Instancia única del contenedor (Singleton) */
    private static ?Container $instance = null;

    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /**
     * Constructor del Contenedor. Asigna la instancia global singleton si es el primero en crearse.
     */
    public function __construct()
    {
        if (self::$instance === null) {
            self::$instance = $this;
        }
    }

    /**
     * Obtiene la instancia activa del contenedor o crea una si no existe.
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registra una resolución para una interfaz o clase.
     */
    public function bind(string $key, callable $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    /**
     * Registra un Singleton (instancia única compartida).
     */
    public function singleton(string $key, callable $resolver): void
    {
        $this->bindings[$key] = function (Container $container) use ($resolver, $key) {
            if (!isset($this->instances[$key])) {
                $this->instances[$key] = $resolver($container);
            }
            return $this->instances[$key];
        };
    }

    /**
     * Obtiene y resuelve una instancia de la clase solicitada.
     */
    public function get(string $key)
    {
        if (isset($this->bindings[$key])) {
            return $this->bindings[$key]($this);
        }

        return $this->resolve($key);
    }

    /**
     * Autowiring automático utilizando la API de Reflexión de PHP.
     * Analiza el constructor de la clase para instanciar sus dependencias recursivamente.
     */
    private function resolve(string $className)
    {
        if (!class_exists($className)) {
            throw new \RuntimeException(sprintf("La clase '%s' no existe en el sistema.", $className));
        }

        $reflector = new \ReflectionClass($className);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException(sprintf("La clase '%s' no puede ser instanciada (es una interfaz o clase abstracta).", $className));
        }

        $constructor = $reflector->getConstructor();

        // Si no tiene constructor, se instancia directamente
        if ($constructor === null) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // Si el parámetro no está tipado o es un tipo nativo, no podemos auto-resolverlo
            if ($type === null || !$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new \RuntimeException(
                    sprintf("No se puede resolver el parámetro '%s' del constructor de '%s' porque no está tipado o no tiene un valor por defecto.", $parameter->getName(), $className)
                );
            }

            // Resolver recursivamente la dependencia solicitada por el tipo
            $dependencies[] = $this->get($type->getName());
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
