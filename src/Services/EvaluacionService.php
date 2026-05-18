<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\EvaluacionRepositoryInterface;
use App\Repositories\Contracts\PerfilObjetivoRepositoryInterface;
use App\Repositories\Contracts\PeriodoRepositoryInterface;
use App\Repositories\Contracts\EmpleadoRepositoryInterface;
use RuntimeException;

/**
 * Servicio de Aplicación para la gestión y orquestación de Evaluaciones.
 * Coordina transacciones, validaciones de negocio y reglas de periodos históricos.
 */
class EvaluacionService
{
    private EvaluacionRepositoryInterface $evaluacionRepo;
    private PerfilObjetivoRepositoryInterface $perfilObjetivoRepo;
    private PeriodoRepositoryInterface $periodoRepo;
    private EmpleadoRepositoryInterface $empleadoRepo;
    private CompetenciaCalculadoraServicio $calculadora;

    /**
     * Inyección de todos los repositorios y servicios colaboradores necesarios (DI).
     */
    public function __construct(
        EvaluacionRepositoryInterface $evaluacionRepo,
        PerfilObjetivoRepositoryInterface $perfilObjetivoRepo,
        PeriodoRepositoryInterface $periodoRepo,
        EmpleadoRepositoryInterface $empleadoRepo,
        CompetenciaCalculadoraServicio $calculadora
    ) {
        $this->evaluacionRepo = $evaluacionRepo;
        $this->perfilObjetivoRepo = $perfilObjetivoRepo;
        $this->periodoRepo = $periodoRepo;
        $this->empleadoRepo = $empleadoRepo;
        $this->calculadora = $calculadora;
    }

    /**
     * Registra o actualiza de forma completa la evaluación de un empleado para un periodo dado.
     * Realiza validaciones defensivas críticas como el estado cerrado del periodo.
     * 
     * @param array $data Parámetros de la evaluación (empleado_id, periodo_id, puntuaciones, comentarios, evaluado_por)
     * @return array Resumen financiero y de brechas de la evaluación para respuesta AJAX
     * @throws RuntimeException Si el periodo está cerrado o faltan configuraciones
     */
    public function registrarEvaluacion(array $data): array
    {
        $empleadoId = (int) $data['empleado_id'];
        $periodoId  = (int) $data['periodo_id'];
        $puntuaciones = $data['puntuaciones'] ?? []; // [competencia_id => puntuacion]
        $comentarios  = $data['comentarios'] ?? [];  // [competencia_id => comentario]
        $evaluadoPor  = isset($data['evaluado_por']) ? trim($data['evaluado_por']) : 'Evaluador ERP';

        // 1. Validar existencia y cierre del periodo de evaluación (Regla Inviolable de Históricos)
        $periodo = $this->periodoRepo->findById($periodoId);
        if (!$periodo) {
            throw new RuntimeException("El periodo de evaluación solicitado no existe, socio.");
        }

        if ((int) $periodo['cerrado'] === 1) {
            throw new RuntimeException("El periodo '{$periodo['nombre']}' ya se encuentra cerrado. No se admiten nuevas evaluaciones ni modificaciones, parce.");
        }

        // 2. Validar existencia del empleado
        $empleado = $this->empleadoRepo->findById($empleadoId);
        if (!$empleado) {
            throw new RuntimeException("El empleado solicitado no se encuentra registrado en el ERP.");
        }

        $puestoId = (int) $empleado['puesto_id'];

        // 3. Recuperar el perfil ideal para el puesto en ese periodo específico (Versionado de Perfil)
        $perfilIdeal = $this->perfilObjetivoRepo->findByPuestoAndPeriodo($puestoId, $periodoId);
        if (empty($perfilIdeal)) {
            throw new RuntimeException("No se ha configurado el perfil ideal para el cargo '{$empleado['puesto_nombre']}' en el periodo '{$periodo['nombre']}'.");
        }

        // 4. Obtener el límite máximo de la escala de valoración activa en este periodo
        $escalaMax = (float) $periodo['escala_max'];

        // 5. Ejecutar la calculadora matemática pura para obtener GAPs, semáforos e ideales ponderados
        $calculo = $this->calculadora->calcularResumen($puntuaciones, $perfilIdeal, $escalaMax);
        $porcentajeAjuste = (float) $calculo['porcentaje_ajuste'];

        // 6. Guardar / Modificar la Cabecera de la evaluación
        $cabeceraId = $this->evaluacionRepo->saveCabecera([
            'empleado_id'       => $empleadoId,
            'periodo_id'        => $periodoId,
            'puesto_id'         => $puestoId,
            'porcentaje_ajuste' => $porcentajeAjuste,
            'evaluado_por'      => $evaluadoPor
        ]);

        // 7. Iterar y persistir el Detalle de cada competencia evaluada
        foreach ($calculo['detalles'] as $detalle) {
            $competenciaId = (int) $detalle['competencia_id'];
            $comentario = isset($comentarios[$competenciaId]) ? trim($comentarios[$competenciaId]) : null;

            $this->evaluacionRepo->saveDetalle([
                'cabecera_id'    => $cabeceraId,
                'competencia_id' => $competenciaId,
                'puntuacion'     => $detalle['puntuacion'],
                'gap'            => $detalle['gap'],
                'semaforo'       => $detalle['semaforo'],
                'comentario'     => $comentario
            ]);
        }

        // 8. Recuperar los datos finales guardados directamente de BD para asegurar sincronía
        $evaluacionesGuardadas = $this->evaluacionRepo->findDetalle($cabeceraId);

        return [
            'status'            => 'success',
            'message'           => 'Evaluación guardada exitosamente, parce.',
            'porcentaje_ajuste' => $porcentajeAjuste,
            'evaluaciones'      => $evaluacionesGuardadas
        ];
    }

    /**
     * Recupera el histórico de evaluaciones y ficha descriptiva del empleado.
     * 
     * @param int $empleadoId
     * @return array
     */
    public function obtenerFichaIndividual(int $empleadoId): array
    {
        $empleado = $this->empleadoRepo->findById($empleadoId);
        if (!$empleado) {
            throw new RuntimeException("El empleado solicitado no existe, socio.");
        }

        $historial = $this->evaluacionRepo->findHistorialByEmpleado($empleadoId);

        return [
            'empleado'  => $empleado,
            'historial' => $historial
        ];
    }

    /**
     * Obtiene los datos correspondientes para renderizar la Matriz de Equipo por periodo y área.
     * 
     * @param int $periodoId
     * @param int|null $areaId Filtro de departamento/área opcional
     * @return array
     */
    public function obtenerMatrizResumen(int $periodoId, ?int $areaId = null): array
    {
        $periodo = $this->periodoRepo->findById($periodoId);
        if (!$periodo) {
            throw new RuntimeException("El periodo solicitado no existe.");
        }

        $matriz = $this->evaluacionRepo->findMatrizResumen($periodoId, $areaId);

        return [
            'periodo' => $periodo,
            'matriz'  => $matriz
        ];
    }
}
