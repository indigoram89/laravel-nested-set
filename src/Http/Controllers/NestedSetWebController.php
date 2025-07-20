<?php

namespace Indigoram89\NestedSet\Http\Controllers;

use Illuminate\Routing\Controller;

class NestedSetWebController extends Controller
{
    /**
     * Отобразить веб-интерфейс управления деревьями
     */
    public function index()
    {
        return view('nested-set::nested-set-app');
    }
}