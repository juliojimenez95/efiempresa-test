<?php

declare(strict_types=1);

namespace App\Services\Strategies;

/**
 * Estrategia de Semáforo Porcentual.
 * Determina el color del semáforo en función de la desviación relativa porcentual del déficit
 * respecto al límite máximo de la escala de valoración activa.
 */
class SemaforoPorcentualStrategy implements SemaforoStrategyInterface
{
    private float $umbralVerde;
    private float $umbralAmarillo;

    /**
     * Inicializa los límites de porcentaje para las clasificaciones.
     * 
     * @param float $umbralVerde Máximo porcentaje de déficit tolerado para seguir en verde (por defecto 10%)
     * @param float $umbralAmarillo Máximo porcentaje de déficit tolerado para estar en amarillo (por defecto 30%)
     */
    public function __construct(float $umbralVerde = 0.10, float $umbralAmarillo = 0.30)
    {
        $this->umbralVerde = $umbralVerde;
        $this->umbralAmarillo = $umbralAmarillo;
    }

    /**
     * {@inheritdoc}
     */
    public function calcular(float $gap, float $escalaMax): string
    {
        // 1. Si el GAP es positivo o cero, el empleado cumple o excede el perfil esperado (Verde Absoluto)
        if ($gap >= 0) {
            return 'verde';
        }

        // 2. Si el GAP es negativo, calculamos la gravedad del déficit respecto al tope máximo de la escala activa
        $deficit = abs($gap);
        $deficitRatio = ($escalaMax > 0.0) ? ($deficit / $escalaMax) : 1.0;

        // 3. Evaluar umbrales dinámicos
        if ($deficitRatio <= $this->umbralVerde) {
            return 'verde';
        }
        if ($deficitRatio <= $this->umbralAmarillo) {
            return 'amarillo';
        }
        return 'rojo';
    }
}
