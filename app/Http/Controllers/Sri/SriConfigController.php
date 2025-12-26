<?php

namespace App\Http\Controllers\Sri;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sri\UpsertSriConfigRequest;
use App\Services\Sri\SriConfigService;

class SriConfigController extends Controller
{
    public function __construct(private SriConfigService $service) {}

    public function edit()
    {
        $config = $this->service->get();

        return view('sri.config', [
            'config' => $config,
            'envHasPassword' => (bool) env('SRI_CERT_PASSWORD'),
        ]);
    }

    public function store(UpsertSriConfigRequest $request)
    {
        $data = $request->validated();

        $certFile = $request->file('certificado_p12');
        unset($data['certificado_p12']);

        $config = $this->service->save($data, $certFile);

        return redirect()
            ->route('sri.config.edit')
            ->with('success', 'Configuración SRI guardada correctamente.')
            ->with('clear_form', true); 
    }

}
