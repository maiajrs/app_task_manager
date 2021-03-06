<?php

namespace App\Http\Controllers;

use App\Exports\TarefasExport;
use App\Mail\NovaTarefaMail;
use App\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use function GuzzleHttp\Promise\all;

class TarefaController extends Controller
{
     /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_id = auth()->user()->id;
        $tarefas = Tarefa::where('user_id', $user_id)->paginate(2);
        return view('tarefa.index', ['tarefas' => $tarefas]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tarefa.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $regras = [
            'tarefa' => 'required|min:5|max:200',
            'data_limite_conclusao' => 'required'
        ];

        $feedback = [
            'tarefa.required' => 'O campo Tarefa precisa ser preenchido',
            'tarefa.min' => 'O campo Tarefa deve ter no mínimo 5 caracteres',
            'tarefa.max' => 'O campo Tarefa deve ter no máximo 200 caracteres',
            'data_limite_conclusao.required' => 'O campo Data dever ser preenchido'
        ];

        $request->validate($regras, $feedback);

        $dados = $request->all('tarefa', 'data_limite_conclusao');
        $dados['user_id'] = auth()->user()->id;

        $tarefa = Tarefa::create($dados);

        $destinatario = auth()->user()->email;

        // Mail::to($destinatario)->send(new NovaTarefaMail($tarefa));

        return redirect()->route('tarefa.show', ['tarefa' => $tarefa->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tarefa  $tarefa
     * @return \Illuminate\Http\Response
     */
    public function show(Tarefa $tarefa)
    {
        return view('tarefa.show', ['tarefa' => $tarefa]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tarefa  $tarefa
     * @return \Illuminate\Http\Response
     */
    public function edit(Tarefa $tarefa)
    {
        $user_id = auth()->user()->id;
        if($user_id == $tarefa->user_id) {
            return view('tarefa.edit', ['tarefa' => $tarefa]);
        } else {
            return view('acesso-negado');
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tarefa  $tarefa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tarefa $tarefa)
    {
        if(!$tarefa->user_id == auth()->user()->id) {
            return view('acesso-negado');
        }

        $regras = [
            'tarefa' => 'required|min:5|max:200',
            'data_limite_conclusao' => 'required'
        ];

        $feedback = [
            'tarefa.required' => 'O campo Tarefa precisa ser preenchido',
            'tarefa.min' => 'O campo Tarefa deve ter no mínimo 5 caracteres',
            'tarefa.max' => 'O campo Tarefa deve ter no máximo 200 caracteres',
            'data_limite_conclusao.required' => 'O campo Data dever ser preenchido'
        ];

        $request->validate($regras, $feedback);

        $tarefa->update($request->all());

        return redirect()->route('tarefa.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tarefa  $tarefa
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tarefa $tarefa)
    {
        if(!$tarefa->user_id == auth()->user()->id) {
            return view('acesso-negado');
        }

        $tarefa->delete();

        return redirect()->route('tarefa.index');
    }

    public function exportacao($type) {
        if (in_array($type, ['xlsx', 'csv', 'pdf'])) {
            return Excel::download(new TarefasExport, 'lista_de_tarefas.'.$type);
        }
        return redirect()->route('tarefa.index');
    }

    public function exportar() {
        $tarefas = auth()->user()->tarefas()->get();
        $pdf = PDF::loadView('tarefa.pdf', ['tarefas' => $tarefas]);
        $pdf->setPaper('a4', 'landscape');
        // return $pdf->download('lista_de_tarefas.pdf');
        return $pdf->stream('lista_de_tarefas.pdf');
    }
}
