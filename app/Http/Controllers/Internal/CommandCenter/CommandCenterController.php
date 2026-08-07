<?php

namespace App\Http\Controllers\Internal\CommandCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCenter\UpdateIncidentRequest;
use App\Models\CommandCenterIncident;
use App\Services\CommandCenter\CommandCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommandCenterController extends Controller
{
    public function __construct(private CommandCenterService $service) {}

    public function index(Request $request): View
    {
        $stationId = $this->service->scopeFor($request->user());

        return view('internal.command-center.index', [
            'summary'   => $this->service->summary($stationId),
            'cameras'   => $this->service->cameras($stationId),
            'alerts'    => $this->service->alerts($stationId),
            'incidents' => $this->service->incidents($stationId),
            'offices'   => $this->service->offices($stationId),
            'canManage' => $request->user()->hasPermission('command-center.manage'),
            'isScoped'  => $stationId !== null,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $stationId = $this->service->scopeFor($request->user());

        return response()->json($this->service->snapshot($stationId));
    }

    public function cameras(Request $request): JsonResponse
    {
        $stationId = $this->service->scopeFor($request->user());

        return response()->json(['cameras' => $this->service->cameras($stationId)->values()]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $stationId = $this->service->scopeFor($request->user());

        return response()->json(['alerts' => $this->service->alerts($stationId)->values()]);
    }

    public function incidents(Request $request): JsonResponse
    {
        $stationId = $this->service->scopeFor($request->user());

        return response()->json(['incidents' => $this->service->incidents($stationId)->values()]);
    }

    public function offices(Request $request): JsonResponse
    {
        $stationId = $this->service->scopeFor($request->user());

        return response()->json(['offices' => $this->service->offices($stationId)->values()]);
    }

    public function updateIncident(UpdateIncidentRequest $request, CommandCenterIncident $incident): JsonResponse
    {
        $updated = $this->service->updateIncidentStatus($incident, $request->validated()['status']);

        return response()->json(['incident' => $updated]);
    }
}
