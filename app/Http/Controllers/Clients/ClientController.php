<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Services\Clients\ClientService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    protected ClientService $service;

    public function __construct(ClientService $service)
    {
        $this->service = $service;
    }

   
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'tipo', 'estado', 'ciudad']);
        $perPage = (int) $request->get('per_page', 15);

        $clients = $this->service->list($filters, $perPage);

        if ($request->wantsJson()) {
            return response()->json($clients);
        }

        return view('clients.index', compact('clients', 'filters'));
    }

   
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $client = $this->service->create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cliente creado correctamente',
                'data'    => $client,
            ], Response::HTTP_CREATED);
        }

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente creado correctamente.');
    }

   
    public function show(int $id)
    {
        $client = $this->service->find($id);

        return response()->json($client);
    }

    
    public function update(Request $request, int $id)
    {
        $data = $this->validateData($request, $id);

        $client = $this->service->update($id, $data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cliente actualizado correctamente',
                'data'    => $client,
            ]);
        }

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Request $request, int $id)
    {
        $this->service->delete($id);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cliente eliminado correctamente',
            ], Response::HTTP_OK);
        }

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

   
    public function findByIdentificacion(Request $request)
    {
        $data = $request->validate([
            'tipo_identificacion' => 'required|in:CEDULA,RUC,PASAPORTE',
            'identificacion'      => 'required|string|max:20',
        ]);

        $client = $this->service->findByIdentificacion(
            $data['tipo_identificacion'],
            $data['identificacion']
        );

        if (!$client) {
            return response()->json([
                'message' => 'Cliente no encontrado',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($client);
    }

   
    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        $tipoIdentificacion = $request->input('tipo_identificacion');

        $identificacionRule = 'required|string|max:20';

        if ($tipoIdentificacion) {
            if ($ignoreId) {
                $identificacionRule .= '|unique:clients,identificacion,' .
                    $ignoreId . ',id,tipo_identificacion,' . $tipoIdentificacion;
            } else {
                $identificacionRule .= '|unique:clients,identificacion,NULL,id,tipo_identificacion,' .
                    $tipoIdentificacion;
            }
        }

        return $request->validate([
            'tipo_identificacion' => 'required|in:CEDULA,RUC,PASAPORTE',
            'identificacion'      => $identificacionRule,
            'business'            => 'required|string|max:191',
            'tipo'                => 'required|in:natural,juridico',
            'telefono'            => 'nullable|string|max:50',
            'direccion'           => 'nullable|string|max:255',
            'ciudad'              => 'nullable|string|max:100',
            'estado'              => 'required|in:activo,inactivo',

            'emails'              => 'nullable|array',
            'emails.*'            => 'nullable|email:rfc,dns|max:191',
        ]);
    }
}
