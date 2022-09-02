<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiVideosController extends Controller
{
    public function videos(){
        //$videos = DB::select('SELECT id,datacadastro,titulo,codigovimeo FROM videoscurso limit 20');
        $views = DB::select('SELECT count(datacadastro) as views,(select codigovimeo from videoscurso where id = video) as id_vimeo,(select titulo from videoscurso where id = video) as titulo FROM videoscurso_acessos group by video order by views limit 50');
        $array = array('views'=>$views);

        print_r(json_encode($array));
    }
    
}
