<?php

namespace App\Http\Controllers;

use App\Models\Competence;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DatasusController extends Controller
{
    use AuthorizesRequests;

    public function getCompetences()
    {
        $this->authorize('tfd/datasus listar');
        $competences = Competence::query()
            ->orderBy('id','desc')
            ->get();
        return response()->json($competences, 200);
    }
}
