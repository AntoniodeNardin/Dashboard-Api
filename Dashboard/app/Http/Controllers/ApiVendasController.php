<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendas;
use Hamcrest\Arrays\IsArray;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use stdClass;

class ApiVendasController extends Controller
{
    public function index(Request $request):JsonResponse //passar quantidade por parametro (query params)
    {

        $amount = $request->query('amount');

        if (!isset($amount)) {
            $amount = 30;
        }

        $vendas = DB::select('SELECT (SELECT nome FROM user WHERE user.id = vendas.user) AS name ,
            (SELECT id FROM user WHERE user.id = vendas.user ) AS user_id,
            (SELECT (SELECT nome FROM produtos WHERE produtos.id = itensvendas.produto LIMIT 1) FROM itensvendas WHERE vendas.id = itensvendas.venda LIMIT 1 ) AS name_product,
            (SELECT (SELECT id FROM produtos WHERE produtos.id = itensvendas.produto LIMIT 1) FROM itensvendas WHERE vendas.id = itensvendas.venda LIMIT 1) AS id_product,
            (SELECT nome FROM formaspagamento WHERE formaspagamento.id = vendas.formapagamento) AS payment,
            datavenda AS sale_date,total,user FROM vendas WHERE status in(3,4,11,12) ORDER BY datavenda DESC LIMIT ' . $amount . '  ');


        foreach ($vendas as $venda) {

            $venda->img = $this->img_user($venda->user_id);

            $venda = $this->Sales($venda);

            $lista[] = $venda;
        }
        return response()->json($lista, 200);
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
    }

    public function show(int $id):JsonResponse
    {
        if (Vendas::where('id', $id)->exists()) {
            $venda = DB::select("SELECT  (select nome from user where user.id = vendas.user) as name,
                (select (select nome from produtos where produtos.id = itensvendas.produto) from itensvendas where itensvendas.venda = vendas.id) as name_product,
                (select (select id from produtos where produtos.id = itensvendas.produto) from itensvendas where vendas.id = itensvendas.venda ) as id_product,
                (select nome from formaspagamento where formaspagamento.id = vendas.formapagamento) as payment,
                datavenda as sale_date,
                total
                from vendas 
                where vendas.id = ' $id ' ");
            return response()->json($venda, 200);
        } else {
            return response()->json([
                "message" => "Venda não encontrada"
            ], 404);
        }
    }

    public function edit($id)
    {
    }
    public function update(Request $request, $id)
    {
        //
    }
    public function destroy($id)
    {
        //
    }

    public function sales_year(Request $request):JsonResponse
    {

        $year = $request->query('year');

        if (is_array($year)) {
            $year = implode(",", $year);
        }

        $result = DB::table('vendas')
        ->select(DB::raw('sum(total) as total, month(datavenda) as month'))
        ->whereIn('status', array(3, 4, 11, 12))
        ->whereYear('datavenda','=',$year)
        ->groupByRaw('month')
        ->orderByRaw('month')
        ->get();

    //   $total = DB::select('SELECT sum(total) as total,month(datavenda) as month FROM nonilton_mege.vendas where status in(3,4,11,12) and year(datavenda) in(' . $year . ') group by month order by month');

        return response()->json($result, 200);
    }

    public function sales_simulations_year(Request $request):JsonResponse
    {

        $year = $request->query('year');

        // $total = DB::select('SELECT sum(vendas.total) as total, month(datavenda) as month from vendas 
        //     inner join itensvendas on vendas.id = itensvendas.venda
        //     inner join produtos on itensvendas.produto = produtos.id 
        //     where categoriaproduto in(2,34,37,42,46) and vendas.status in(3,4,11,12) and year(datavenda) = ' . $year . ' group by month
        // ');

        $result = DB::table('vendas')
        ->select(DB::raw('sum(vendas.total) as total, month(datavenda) as month'))
        ->join('itensvendas','vendas.id','=','itensvendas.venda')
        ->join('produtos','itensvendas.produto','=','produtos.id')
        ->whereIn('categoriaproduto',array(2,34,37,42,46))
        ->whereIn('vendas.status',array(3,4,11,12))
        ->whereYear('datavenda','=',$year)
        ->groupByRaw('month')
        ->get();
        
        return response()->json($result, 200);
    }

    public function sales_product_year(Request $request):JsonResponse
    {

        $year = $request->query('year');

        $total = DB::select('SELECT sum(vendas.total) as total, month(datavenda) as month from vendas
        inner join itensvendas on vendas.id = itensvendas.venda
        inner join produtos on itensvendas.produto = produtos.id
        where categoriaproduto not in(2,34,37,42,46) and vendas.status in(3,4,11,12) and year(datavenda) = ' . $year . ' group by month');

        return response()->json($total, 200);
    }

    private function Sales($venda)
    {

        $sales = new stdClass;
        $sales->name = $venda->name;
        $sales->name_product = $venda->name_product;
        $sales->user_id = $venda->user_id;
        $sales->id_product = $venda->id_product;
        $sales->payment = $venda->payment;
        $sales->sale_date = $venda->sale_date;
        $sales->total = $venda->total;
        $sales->pathimg = $venda->img;

        return $sales;
    }

    private function img_user(int $user_id):string
    {

        $file = 'https://curso.mege.com.br/m2ne/web/sgc/administracao/bancoDeImagens/avatar_' . $user_id . '.jpg';

        $file_headers = @get_headers($file);

        if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
            $img = 'https://curso.mege.com.br/static/img/ico_profile.gif';
            return  $img;
        } else {
            $img = 'https://curso.mege.com.br/m2ne/web/sgc/administracao/bancoDeImagens/avatar_' . $user_id . '.jpg';
            return $img;
        }
    }
}
