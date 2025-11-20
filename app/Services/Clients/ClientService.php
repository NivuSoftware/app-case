<?php

namespace App\Services\Clients;

use App\Models\Clients\Client;
use App\Repositories\Clients\ClientRepository;
use Illuminate\Support\Facades\DB;

class ClientService
{
    protected ClientRepository $repository;

    public function __construct(ClientRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Listar clientes con filtros y paginación.
     *
     * Filtros soportados (propuestos):
     * - search: busca en business, identificación, teléfono
     * - tipo: natural / juridico
     * - estado: activo / inactivo
     * - ciudad
     */
    public function list(array $filters = [], int $perPage = 15)
    {
        return $this->repository->list($filters, $perPage);
    }

    /**
     * Obtener un cliente por ID.
     */
    public function find(int $id): Client
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Crear un cliente nuevo con sus emails (si vienen).
     *
     * $data puede incluir:
     * - tipo_identificacion, identificacion, business, tipo,
     *   telefono, direccion, ciudad, estado
     * - emails: array de strings (opcional)
     */
    public function create(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $emails = $data['emails'] ?? [];
            unset($data['emails']);

            // Crear cliente
            $client = $this->repository->create($data);

            // Crear correos si vienen
            if (!empty($emails) && is_array($emails)) {
                $this->repository->syncEmails($client, $emails);
            }

            return $client->load('emails');
        });
    }

    /**
     * Actualizar un cliente y, opcionalmente, reemplazar sus emails.
     *
     * Si en $data viene 'emails', se sobreescriben los correos anteriores
     * por la nueva lista. Si no viene 'emails', se mantiene lo que ya tiene.
     */
    public function update(int $id, array $data): Client
    {
        return DB::transaction(function () use ($id, $data) {
            $emails = $data['emails'] ?? null;
            unset($data['emails']);

            $client = $this->repository->update($id, $data);

            if (is_array($emails)) {
                $this->repository->syncEmails($client, $emails);
            }

            return $client->load('emails');
        });
    }

    /**
     * Eliminar (o soft delete si luego lo manejas así) un cliente.
     */
    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }

    /**
     * Buscar cliente por tipo + identificación (útil para facturación).
     */
    public function findByIdentificacion(string $tipoIdentificacion, string $identificacion): ?Client
    {
        return $this->repository->findByIdentificacion($tipoIdentificacion, $identificacion);
    }

    /**
     * Flujo pensado para la futura facturación:
     * - Si existe cliente con ese tipo + identificación → lo devuelve.
     * - Si no existe → lo crea con los datos enviados.
     *
     * Esto NO lo vas a usar todavía en el módulo puro de clientes,
     * pero queda listo para el módulo de facturación.
     */
    public function findOrCreateForInvoice(array $data): Client
    {
        $existing = $this->findByIdentificacion(
            $data['tipo_identificacion'],
            $data['identificacion'],
        );

        if ($existing) {
            return $existing;
        }

        return $this->create($data);
    }
}
