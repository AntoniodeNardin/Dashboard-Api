<?php

namespace App\Http\Controllers;

use App\Models\Turmas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use LDAP\Result;
use PhpParser\Node\Expr\Cast\Array_;
use Psy\Util\Json;

class ApiTurmasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $turmas = DB::select('SELECT id,produto, (select (select (select user.nome from user where id = professores.user) from professores where professores.id = coordenadoresturmas.professor) as professor from coordenadoresturmas where turma = turmas.id && principal = "S") as cordenador,nome AS Name, dtinicio AS Start_Date, dttermino AS End_Date, encerrada as Ended  from turmas where (dtinicio between curdate() - interval 160 day and curdate() + interval 20 day) && (encerrada = "N") order by dtinicio desc limit 20');

        foreach ($turmas as $turma) {

            $turma->status = $this->status_turma($turma->Start_Date);
            $turma->src = $this->img_produto($turma->produto);

            if (!isset($turma->cordenador)) {

                $turma->cordenador = $this->getCordenador($turma->id);
            }
        }
        return response()->json($turmas, 200);
    }

    public function create()
    {
        //
    }
    public function store(Request $request)
    {
    }

    public function show(int $id):JsonResponse
    {
        if (Turmas::where('id', $id)->exists()) {
            $turma = Turmas::select('produto as idproduto', 'nome AS name', 'dtinicio AS start_date', 'dttermino AS end_date', 'encerrada AS end')->where('id', $id)->get();
            return response()->json($turma, 200);
        } else {
            return response()->json([
                "message" => "Turma não encontrada"
            ], 404);
        }
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {

    }

    public function aprovacoes()
    {
        $result = DB::table('aprovacoes')
        ->select('qtdaprovacoes','concurso','datacadastro')
        ->orderBy('qtdaprovacoes','desc')
        ->get();

        return $result;
    }

    public function alunos_turmas(){

        $result = DB::select('SELECT aluno,count(turma) as n_turmas FROM alunosturmas  group by aluno order by n_turmas limit 10');

        foreach ($result as $data) {
            $aluno = $data->aluno;
            $n_de_tumas = $data->n_turmas;
            $nome = $this->getNome($aluno);
            $turmas = $this->getTurma($aluno);
            $array[] = array('id_aluno'=>$aluno,'nome'=>$nome,'n_de_turmas'=>$n_de_tumas,'turmas'=>$turmas);
        }

        $retorno[] = $array;
        return $retorno;

    }
    private function getNome($aluno){

        $result = DB::table('alunos')
        ->select('nome')
        ->join('user','user.id','=','alunos.user')
        ->where('alunos.id',$aluno)
        ->first();

        //$result = DB::select('SELECT (select nome from user where id = user) as nome from alunos where id = '.$aluno.'');
        
        $nome = $result->nome;

        return $nome ;

    }
    private function getTurma($aluno){

        $turma = DB::table('alunosturmas')
        ->select('turma','nome as nome_turma')
        ->join('turmas','turmas.id','=','alunosturmas.turma')
        ->where('aluno',$aluno)
        ->first();
        $id_turma = $turma->turma;
        $nome_turma = $turma->nome_turma;
        $produto = $this->getProduto($id_turma);
        $turmas[] = array('id_turma'=>$id_turma,'nome_turma'=>$nome_turma,'produto'=>$produto);
        return $turmas;

    }
    private function getProduto($turma){

       $produtos = DB::table('turmasprodutos')
       ->select('produto')
       ->where('turma',$turma)
       ->first();

        if(isset($produtos)){
        $produto = $produtos->produto;
        $img_produto = $this->img_produto($produto);

        $array= array('id'=>$produto,'img_produto'=>$img_produto);
        }
        else{
            $array= array('id'=>'nao encontrado','img_produto'=>'nao encontrado');
        }

        return $array;
    }

    private function status_turma(string $data_inicio): string
    {
        $data_atual = new DateTime('-140days');
        $proxsemana = new DateTime('-147days');
        $dtinicio  = new DateTime($data_inicio);

        if ($dtinicio > $data_atual) {
            $status = 'comecou';
        } else {
            if ($dtinicio > $proxsemana) {
                $status = 'embreve';
            } else {
                $status = 'distante';
            }
        }
        return $status;
    }

    private function img_produto(int $produto): string
    {
        $src = "https://curso.mege.com.br/m2ne/web/sgc/administracao/bancoDeImagens/produto_{$produto}_2.jpg";
        return $src;
    }
    private function getCordenador(int $idturma):string
    {
        // $cordenador = DB::select('select (select (select user.nome from user where id = professores.user) from professores where professores.id = coordenadoresturmas.professor) as professor from coordenadoresturmas where turma = ' . $idturma . ' limit 1');

        $teste = DB::table('coordenadoresturmas')
        ->select('professor')
        ->where('turma',$idturma)
        ->first();

        if(isset($teste->professor)){
            $professor = DB::table('professores')
            ->where('id',$teste->professor)
            ->first();
    
            $cordenador = DB::table('user')
            ->where('id',$professor->id)
            ->first();
        }

        if (isset($cordenador->nome)) {
            $result = $cordenador->nome;
            return $result;
        } else {
            $result = 'cordenador não definido';
            return $result;
        }
    }
}
