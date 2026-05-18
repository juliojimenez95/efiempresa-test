<?php

declare(strict_types=1);

namespace App\Services\Strategies;

/**
 * Interfaz para definir la estrategia de cálculo de semáforos de competencias.
 * Permite cambiar la lógica de clasificación sin modificar los servicios de negocio (Open/Closed Principle).
 */
interface SemaforoStrategyInterface
{
    /**
     * Calcula la categoría del semáforo ('verde', 'amarillo', 'rojo') basado en la brecha (GAP)
     * y el límite superior de la escala del periodo evaluado.
     * 
     * @param float $gap Brecha de valoración (Puntuación Real - Valor Ideal)
     * @param float $escalaMax Límite máximo de la escala de valoración
     * @return string 'verde' | 'amarillo' | 'rojo'
     */
    public function calcular(float $gap, float $escalaMax): string;
}
