<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Strategies\SemaforoStrategyInterface;
use App\Services\Strategies\SemaforoPorcentualStrategy;

/**
 * Motor de Cálculo Matemático del Módulo de Competencias.
 * Contiene la lógica pura para calcular GAPs, semáforos y porcentajes de ajuste global ponderados.
 */
class CompetenciaCalculadoraServicio
{
    private SemaforoStrategyInterface $defaultStrategy;

    /**
     * Inicializa la estrategia por defecto para el semáforo.
     */
    public function __construct(?SemaforoStrategyInterface $defaultStrategy = null)
    {
        $this->defaultStrategy = $defaultStrategy ?? new SemaforoPorcentualStrategy();
    }

    /**
     * Calcula la brecha o GAP (Puntuación Real - Puntuación Ideal).
     * Puede ser negativo (déficit) o positivo (supera expectativa).
     * 
     * @param float $puntuacionReal
     * @param float $puntuacionIdeal
     * @return float
     */
    public function calcularGap(float $puntuacionReal, float $puntuacionIdeal): float
    {
        return round($puntuacionReal - $puntuacionIdeal, 2);
    }

    /**
     * Determina el color del semáforo delegando al patrón Strategy.
     * 
     * @param float $gap
     * @param float $escalaMax
     * @param SemaforoStrategyInterface|null $strategy Estrategia alternativa opcional
     * @return string 'verde' | 'amarillo' | 'rojo'
     */
    public function calcularSemaforo(float $gap, float $escalaMax, ?SemaforoStrategyInterface $strategy = null): string
    {
        $activeStrategy = $strategy ?? $this->defaultStrategy;
        return $activeStrategy->calcular($gap, $escalaMax);
    }

    /**
     * Calcula los resultados individuales por competencia y el ajuste global ponderado
     * aplicando el tope matemático cuando se supera el perfil ideal (capping a 1.0).
     * 
     * @param array $puntuaciones Array asociativo de [competencia_id => puntuacion]
     * @param array $perfilObjetivo Lista de competencias con 'competencia_id', 'valor_ideal' y 'peso'
     * @param float $escalaMax Límite superior de la escala de valoración activa
     * @param SemaforoStrategyInterface|null $strategy Estrategia alternativa de semáforo
     * @return array Resumen estructurado con 'porcentaje_ajuste' y 'detalles'
     */
    public function calcularResumen(
        array $puntuaciones,
        array $perfilObjetivo,
        float $escalaMax,
        ?SemaforoStrategyInterface $strategy = null
    ): array {
        $detalles = [];
        $totalPonderado = 0.0;
        $pesoTotal = 0.0;

        // Mapear perfiles objetivo por competencia_id para acceso O(1)
        $perfilMap = [];
        foreach ($perfilObjetivo as $po) {
            $perfilMap[(int) $po['competencia_id']] = $po;
        }

        foreach ($puntuaciones as $compId => $puntuacion) {
            $competenciaId = (int) $compId;
            $puntuacionReal = (float) $puntuacion;

            // Extraer parámetros ideales
            $valorIdeal = isset($perfilMap[$competenciaId]) ? (float) $perfilMap[$competenciaId]['valor_ideal'] : 0.0;
            $peso = isset($perfilMap[$competenciaId]) ? (float) $perfilMap[$competenciaId]['peso'] : 0.0;

            // 1. Calcular GAP individual
            $gap = $this->calcularGap($puntuacionReal, $valorIdeal);

            // 2. Calcular semáforo aplicando la estrategia
            $semaforo = $this->calcularSemaforo($gap, $escalaMax, $strategy);

            // 3. Lógica matemática del porcentaje de ajuste individual (con tope/capping al 100%)
            $ratio = 0.0;
            if ($valorIdeal > 0.0) {
                // Aplicamos el tope (min 1.0) para evitar que superar una competencia compense déficits de otras
                $ratio = min($puntuacionReal / $valorIdeal, 1.0);
            }

            // Sumar a promedios ponderados
            $totalPonderado += $ratio * $peso;
            $pesoTotal += $peso;

            // Estructurar el detalle de esta competencia
            $detalles[] = [
                'competencia_id' => $competenciaId,
                'puntuacion'     => $puntuacionReal,
                'valor_ideal'    => $valorIdeal,
                'peso'           => $peso,
                'gap'            => $gap,
                'semaforo'       => $semaforo
            ];
        }

        // 4. Calcular Porcentaje de Ajuste Global Ponderado
        $porcentajeAjuste = 0.00;
        if ($pesoTotal > 0.0) {
            $porcentajeAjuste = round(($totalPonderado / $pesoTotal) * 100, 2);
        }

        return [
            'porcentaje_ajuste' => $porcentajeAjuste,
            'detalles'          => $detalles
        ];
    }
}
